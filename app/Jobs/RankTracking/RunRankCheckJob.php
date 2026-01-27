<?php

namespace App\Jobs\RankTracking;

use App\Models\RankCheck;
use App\Models\RankKeyword;
use App\Models\RankResult;
use App\Models\User;
use App\Services\Crawl\CrawlManager;
use App\Services\System\ActivityLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class RunRankCheckJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes for large batches
    public $tries = 3;
    public $backoff = [30, 60, 120];
    public $queue = 'rank-tracking';

    public function __construct(
        public int $checkId
    ) {}

    public function handle(): void
    {
        $check = RankCheck::findOrFail($this->checkId);
        $domain = $check->domain;
        $user = $check->user;

        $check->update([
            'status' => RankCheck::STATUS_RUNNING,
            'started_at' => now(),
        ]);

        try {
            // Get keywords for this check (if check was created with specific keywords, store them in context)
            // For now, get all active keywords for the domain
            $keywords = RankKeyword::where('domain_id', $domain->id)
                ->where('is_active', true)
                ->get();

            if ($keywords->isEmpty()) {
                $check->update([
                    'status' => RankCheck::STATUS_COMPLETED,
                    'finished_at' => now(),
                ]);
                return;
            }

            $crawlManager = new CrawlManager($user, $domain);
            $results = [];

            // Process keywords in batches by location/device
            $grouped = $keywords->groupBy(function($keyword) {
                return ($keyword->location_code ?? 'default') . '|' . $keyword->device;
            });

            foreach ($grouped as $groupKey => $groupKeywords) {
                foreach ($groupKeywords as $keyword) {
                    try {
                        $keywordData = [
                            'keyword' => $keyword->keyword,
                            'location_code' => $keyword->location_code,
                            'device' => $keyword->device,
                        ];

                        $result = $crawlManager->runSerpRankCheck([$keywordData], [
                            'check_id' => $check->id,
                            'keyword_id' => $keyword->id,
                        ]);

                        if (isset($result['results'][0])) {
                            $serpResult = $result['results'][0];

                            // Match domain
                            $matched = false;
                            $foundUrl = $serpResult['found_url'] ?? null;
                            if ($foundUrl) {
                                $domainHost = parse_url($domain->url, PHP_URL_HOST) ?? $domain->host;
                                $matched = $this->matchDomain($foundUrl, $domainHost);
                            } else {
                                // If no found_url but position exists, check if position <= 100
                                $matched = isset($serpResult['position']) && $serpResult['position'] <= 100;
                            }

                            RankResult::create([
                                'rank_check_id' => $check->id,
                                'domain_id' => $domain->id,
                                'rank_keyword_id' => $keyword->id,
                                'keyword' => $keyword->keyword,
                                'position' => $serpResult['position'] ?? null,
                                'found_url' => $foundUrl,
                                'matched' => $matched,
                                'serp_top_urls_json' => $serpResult['serp_top_urls_json'] ?? null,
                                'features_json' => $serpResult['features_json'] ?? null,
                                'fetched_at' => now(),
                            ]);
                        }
                    } catch (\Exception $e) {
                        Log::error('Rank check keyword failed', [
                            'check_id' => $check->id,
                            'keyword_id' => $keyword->id,
                            'error' => $e->getMessage(),
                        ]);
                        // Continue with next keyword
                    }
                }
            }

            $check->update([
                'status' => RankCheck::STATUS_COMPLETED,
                'finished_at' => now(),
            ]);

            // Log activity
            app(ActivityLogger::class)->logActivity(
                'rank_check.completed',
                'Rank check completed',
                $domain->id,
                $user->id,
                [
                    'check_id' => $check->id,
                    'keywords_count' => $keywords->count(),
                    'provider' => $check->provider_code,
                ]
            );

            // Trigger insights for ranking drops (see Insights integration)
            $this->triggerInsights($check, $domain, $user);

        } catch (Throwable $e) {
            $check->update([
                'status' => RankCheck::STATUS_FAILED,
                'finished_at' => now(),
                'error_code' => 'EXECUTION_ERROR',
                'error_message' => $e->getMessage(),
            ]);

            app(ActivityLogger::class)->logJobFailure(
                'rank_tracking',
                'RunRankCheckJob',
                $e,
                $domain->id,
                $user->id,
                ActivityLogger::runRef('rank_check', $check->id),
                ['check_id' => $check->id]
            );

            throw $e;
        }
    }

    /**
     * Match domain from URL
     */
    protected function matchDomain(string $foundUrl, string $domainHost): bool
    {
        $foundHost = parse_url($foundUrl, PHP_URL_HOST);
        if (!$foundHost) {
            return false;
        }

        $foundHost = strtolower(preg_replace('/^www\./', '', $foundHost));
        $domainHost = strtolower(preg_replace('/^www\./', '', $domainHost));

        return $foundHost === $domainHost;
    }

    /**
     * Trigger insights for ranking drops
     */
    protected function triggerInsights(RankCheck $check, $domain, $user): void
    {
        // Get latest results for this check
        $results = RankResult::where('rank_check_id', $check->id)
            ->with('rankKeyword')
            ->get();

        foreach ($results as $result) {
            if (!$result->position) {
                continue;
            }

            // Get previous result (7 days ago)
            $previousResult = RankResult::where('domain_id', $domain->id)
                ->where('rank_keyword_id', $result->rank_keyword_id)
                ->where('fetched_at', '<', $result->fetched_at->copy()->subDays(7))
                ->latest('fetched_at')
                ->first();

            if (!$previousResult || !$previousResult->position) {
                continue;
            }

            $delta = $previousResult->position - $result->position;

            // Alert if dropped 5+ positions OR lost top 10
            $droppedSignificantly = $delta <= -5;
            $lostTop10 = $previousResult->position <= 10 && $result->position > 10;

            if ($droppedSignificantly || $lostTop10) {
                // Create insight/task (see Insights integration)
                // For now, log activity
                app(ActivityLogger::class)->logActivity(
                    'rank_drop.detected',
                    "Ranking drop detected: {$result->keyword} ({$previousResult->position} → {$result->position})",
                    $domain->id,
                    $user->id,
                    [
                        'keyword' => $result->keyword,
                        'old_position' => $previousResult->position,
                        'new_position' => $result->position,
                        'check_id' => $check->id,
                        'keyword_id' => $result->rank_keyword_id,
                    ]
                );
            }

            // Alert if entered top 20 (opportunity)
            if ($previousResult->position > 20 && $result->position <= 20 && $result->position > 10) {
                app(ActivityLogger::class)->logActivity(
                    'rank_opportunity.detected',
                    "Ranking opportunity: {$result->keyword} entered top 20 (position {$result->position})",
                    $domain->id,
                    $user->id,
                    [
                        'keyword' => $result->keyword,
                        'position' => $result->position,
                        'check_id' => $check->id,
                        'keyword_id' => $result->rank_keyword_id,
                    ]
                );
            }
        }
    }

    public function retryUntil(): \DateTime
    {
        return now()->addHours(2);
    }
}
