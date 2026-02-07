<?php

namespace App\Jobs;

use App\Models\RankProject;
use App\Models\RankKeyword;
use App\Models\RankResult;
use App\Services\SEO\SerpProvider;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class RunRankChecksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;
    public $tries = 2;
    public $queue = 'rank';

    public function __construct(
        public int $projectId
    ) {}

    public function handle(): void
    {
        $project = RankProject::find($this->projectId);
        if (!$project || $project->status !== RankProject::STATUS_ACTIVE) {
            return;
        }

        $keywords = $project->activeKeywords()->get();
        $serpProvider = new SerpProvider();
        $today = Carbon::today();

        foreach ($keywords as $keyword) {
            try {
                // Check if already checked today
                $existing = RankResult::where('rank_keyword_id', $keyword->id)
                    ->whereDate('fetched_at', $today)
                    ->first();

                if ($existing) {
                    continue; // Already checked today
                }

                // Get rank from SERP API
                $rankData = $serpProvider->getRank(
                    $keyword->keyword,
                    $project->target_domain,
                    $project->country_code,
                    $keyword->device
                );

                if ($rankData) {
                    RankResult::create([
                        'rank_keyword_id' => $keyword->id,
                        'keyword' => $keyword->keyword,
                        'position' => $rankData['position'],
                        'found_url' => $rankData['url'] ?? null,
                        'features_json' => $rankData['serp_features'] ?? null,
                        'fetched_at' => $today,
                    ]);
                }

            } catch (\Exception $e) {
                Log::warning("Rank check failed for keyword", [
                    'keyword_id' => $keyword->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
