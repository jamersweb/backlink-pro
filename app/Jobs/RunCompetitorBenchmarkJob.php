<?php

namespace App\Jobs;

use App\Models\Audit;
use App\Models\CompetitorRun;
use App\Models\CompetitorSnapshot;
use App\Models\AiGeneration;
use App\Services\AI\LLMClient;
use App\Services\AI\PromptBuilder;
use App\Services\AI\SchemaValidator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class RunCompetitorBenchmarkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;
    public $tries = 1;

    public function __construct(
        public int $competitorRunId
    ) {}

    public function handle(): void
    {
        $run = CompetitorRun::find($this->competitorRunId);
        if (!$run) {
            return;
        }

        $run->update(['status' => CompetitorRun::STATUS_RUNNING]);

        try {
            // Fetch competitor data: use run's competitor_urls (1–5 URLs) or fallback to empty
            $competitors = $this->fetchCompetitors($run);
            
            // Run light audits on competitors
            foreach ($competitors as $competitor) {
                $snapshot = $this->createSnapshot($run, $competitor);
                $this->auditCompetitor($snapshot);
            }

            // Generate AI summary (only if we have snapshots)
            if ($run->snapshots()->count() > 0) {
                $this->generateCompetitorSummary($run);
            }

            $run->update(['status' => CompetitorRun::STATUS_COMPLETED]);

        } catch (\Exception $e) {
            Log::error("Competitor benchmark failed", [
                'run_id' => $run->id,
                'error' => $e->getMessage(),
            ]);

            $run->update([
                'status' => CompetitorRun::STATUS_FAILED,
            ]);
        }
    }

    /**
     * Fetch competitors from run's competitor_urls (1–5 URLs). Returns list of [url, keyword] for snapshot creation.
     */
    protected function fetchCompetitors(CompetitorRun $run): array
    {
        $urls = $run->competitor_urls ?? [];
        if (!is_array($urls)) {
            $urls = [];
        }
        $competitors = [];
        $keywords = $run->keywords ?? [];
        foreach ($urls as $index => $url) {
            $url = is_string($url) ? trim($url) : '';
            if ($url === '') {
                continue;
            }
            $keyword = $keywords[$index] ?? ('Competitor ' . (count($competitors) + 1));
            if (is_array($keyword)) {
                $keyword = 'Competitor ' . (count($competitors) + 1);
            }
            $competitors[] = [
                'url' => $url,
                'keyword' => $keyword,
                'title' => null,
                'meta_description' => null,
            ];
        }
        return $competitors;
    }

    /**
     * Create competitor snapshot
     */
    protected function createSnapshot(CompetitorRun $run, array $competitor): CompetitorSnapshot
    {
        return CompetitorSnapshot::create([
            'competitor_run_id' => $run->id,
            'keyword' => $competitor['keyword'] ?? '',
            'competitor_url' => $competitor['url'],
            'domain' => parse_url($competitor['url'], PHP_URL_HOST),
            'title' => $competitor['title'] ?? null,
            'meta_description' => $competitor['meta_description'] ?? null,
        ]);
    }

    /**
     * Run light audit on competitor URL: fetch HTML with timeout + user-agent, extract title/meta/word count.
     */
    protected function auditCompetitor(CompetitorSnapshot $snapshot): void
    {
        try {
            $response = Http::timeout(15)
                ->withHeaders(['User-Agent' => 'BacklinkPro-CompetitorBenchmark/1.0'])
                ->get($snapshot->competitor_url);
            $html = $response->body();
            
            preg_match('/<title>(.*?)<\/title>/is', $html, $titleMatch);
            preg_match('/<meta\s+name=["\']description["\']\s+content=["\'](.*?)["\']/i', $html, $metaMatch);
            if (!isset($metaMatch[1])) {
                preg_match('/<meta\s+content=["\'](.*?)["\']\s+name=["\']description["\']/i', $html, $metaMatch);
            }
            
            $wordCount = str_word_count(strip_tags($html));
            $pageWeight = strlen($html);
            
            $snapshot->update([
                'title' => isset($titleMatch[1]) ? trim($titleMatch[1]) : null,
                'meta_description' => isset($metaMatch[1]) ? trim($metaMatch[1]) : null,
                'word_count' => $wordCount,
                'page_weight_bytes' => $pageWeight,
            ]);
        } catch (\Exception $e) {
            Log::warning("Failed to audit competitor", [
                'snapshot_id' => $snapshot->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Generate AI competitor summary
     */
    protected function generateCompetitorSummary(CompetitorRun $run): void
    {
        $audit = $run->audit;
        $snapshots = $run->snapshots;

        $promptBuilder = new PromptBuilder(new LLMClient());
        $validator = new SchemaValidator();

        // Build competitor data
        $competitorData = $snapshots->map(function ($snapshot) {
            return [
                'domain' => $snapshot->domain,
                'url' => $snapshot->competitor_url,
                'keyword' => $snapshot->keyword,
                'title' => $snapshot->title,
                'meta_description' => $snapshot->meta_description,
                'word_count' => $snapshot->word_count,
                'page_weight_bytes' => $snapshot->page_weight_bytes,
            ];
        })->toArray();

        // Build client audit data
        $clientData = [
            'overall_score' => $audit->overall_score,
            'pages_scanned' => $audit->pages_scanned,
            'issues_count' => $audit->issues()->count(),
        ];

        $systemPrompt = "You are an expert SEO consultant. Analyze competitor data and provide insights comparing the client's audit against competitors. Return valid JSON only.";
        $userPrompt = "Compare this client audit:\n\n" . json_encode($clientData, JSON_PRETTY_PRINT) . 
                     "\n\nAgainst these competitors:\n\n" . json_encode($competitorData, JSON_PRETTY_PRINT) .
                     "\n\nReturn JSON with: where_competitors_beat_you (array), fastest_competitor, content_depth_comparison, snippet_opportunities (array).";

        $generation = AiGeneration::create([
            'organization_id' => $run->organization_id,
            'audit_id' => $run->audit_id,
            'type' => AiGeneration::TYPE_COMPETITOR_SUMMARY,
            'input_fingerprint' => hash('sha256', json_encode(['run_id' => $run->id])),
            'status' => AiGeneration::STATUS_RUNNING,
            'input' => [
                'competitor_data' => $competitorData,
                'client_data' => $clientData,
            ],
        ]);

        try {
            $client = new LLMClient();
            $response = $client->generateWithSystemPrompt(
                $systemPrompt,
                $userPrompt,
                ['json_mode' => true]
            );

            $validation = $validator->validate($response->content);
            if (!$validation['valid']) {
                throw new \Exception("Invalid JSON: " . json_encode($validation['error'] ?? $validation['errors'] ?? []));
            }

            $generation->update([
                'status' => AiGeneration::STATUS_COMPLETED,
                'output' => $validation['data'],
                'tokens_in' => $response->tokensIn,
                'tokens_out' => $response->tokensOut,
                'cost_cents' => (int) round($response->costCents ?? 0),
            ]);

        } catch (\Exception $e) {
            Log::error("Competitor summary generation failed", [
                'run_id' => $run->id,
                'error' => $e->getMessage(),
            ]);

            $generation->update([
                'status' => AiGeneration::STATUS_FAILED,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
