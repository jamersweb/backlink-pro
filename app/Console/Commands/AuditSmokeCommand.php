<?php

namespace App\Console\Commands;

use App\Models\Audit;
use App\Models\AuditUrlQueue;
use App\Services\SeoAudit\UrlNormalizer;
use App\Jobs\StartAuditPipelineJob;
use App\Jobs\FetchAndParsePageJob;
use App\Jobs\FinalizeAuditJob;
use Illuminate\Console\Command;

class AuditSmokeCommand extends Command
{
    protected $signature = 'audit:smoke {url} {--pages=3} {--depth=1}';
    protected $description = 'Run a synchronous SEO audit smoke test for a URL';

    public function handle(): int
    {
        $url = $this->argument('url');
        $pagesLimit = (int) $this->option('pages');
        $crawlDepth = (int) $this->option('depth');

        $normalized = UrlNormalizer::normalize($url, $url);
        if (!$normalized) {
            $this->error('Invalid URL.');
            return 1;
        }

        $audit = Audit::create([
            'url' => $url,
            'normalized_url' => $normalized,
            'status' => Audit::STATUS_QUEUED,
            'mode' => Audit::MODE_GUEST,
            'pages_limit' => $pagesLimit,
            'crawl_depth' => $crawlDepth,
            'is_gated' => false,
        ]);

        $this->info("Created audit #{$audit->id} for {$audit->normalized_url}");

        (new StartAuditPipelineJob($audit->id))->handle();

        while (true) {
            $queued = AuditUrlQueue::where('audit_id', $audit->id)
                ->where('status', AuditUrlQueue::STATUS_QUEUED)
                ->orderBy('id')
                ->first();

            if (!$queued) {
                break;
            }

            if ($audit->pages_scanned >= $audit->pages_limit) {
                break;
            }

            (new FetchAndParsePageJob($audit->id, $queued->id))->handle();
            $audit->refresh();
        }

        (new FinalizeAuditJob($audit->id))->handle();
        $audit->refresh();

        $this->info("Pages scanned: {$audit->pages_scanned}");
        $this->info("Issues found: " . $audit->issues()->count());
        $this->info("Overall score: " . ($audit->overall_score ?? 'N/A'));

        if ($audit->pages_scanned < 2) {
            $this->warn('Smoke test scanned less than 2 pages. Increase pages/depth for a fuller crawl.');
        }

        return 0;
    }
}
