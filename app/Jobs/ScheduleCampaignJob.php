<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Campaign;
use App\Models\AutomationTask;
use App\Models\Backlink;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ScheduleCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Get all active campaigns
        $campaigns = Campaign::where('status', Campaign::STATUS_ACTIVE)
            ->where(function ($query) {
                $query->whereNull('start_date')
                    ->orWhere('start_date', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            })
            ->get();

        foreach ($campaigns as $campaign) {
            try {
                $this->processCampaign($campaign);
            } catch (\Exception $e) {
                Log::error('Error processing campaign in ScheduleCampaignJob', [
                    'campaign_id' => $campaign->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Process a single campaign
     */
    protected function processCampaign(Campaign $campaign): void
    {
        // Check plan limits
        $user = $campaign->user;
        $plan = $user->plan;

        if (!$plan) {
            Log::warning('Campaign user has no plan', ['campaign_id' => $campaign->id]);
            return;
        }

        // Check daily limit
        $todayBacklinks = Backlink::whereHas('campaign', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->whereDate('created_at', today())->count();

        if ($todayBacklinks >= $plan->daily_backlink_limit) {
            Log::info('Daily limit reached for user', [
                'user_id' => $user->id,
                'daily_limit' => $plan->daily_backlink_limit,
                'today_count' => $todayBacklinks,
            ]);
            return;
        }

        // Check campaign daily limit
        $campaignTodayBacklinks = $campaign->backlinks()
            ->whereDate('created_at', today())
            ->count();

        if ($campaignTodayBacklinks >= ($campaign->daily_limit ?? 10)) {
            return;
        }

        // Check campaign total limit
        $campaignTotalBacklinks = $campaign->backlinks()->count();
        if ($campaignTotalBacklinks >= ($campaign->total_limit ?? 100)) {
            $campaign->update(['status' => Campaign::STATUS_COMPLETED]);
            return;
        }

        // Get campaign settings
        $settings = $campaign->settings ?? [];
        $backlinkTypes = $settings['backlink_types'] ?? [];

        if (empty($backlinkTypes)) {
            Log::warning('Campaign has no backlink types configured', [
                'campaign_id' => $campaign->id,
            ]);
            return;
        }

        // Calculate how many tasks to create
        $remainingDailyLimit = min(
            ($campaign->daily_limit ?? 10) - $campaignTodayBacklinks,
            $plan->daily_backlink_limit - $todayBacklinks
        );

        if ($remainingDailyLimit <= 0) {
            return;
        }

        // Create tasks for each backlink type
        $tasksPerType = max(1, floor($remainingDailyLimit / count($backlinkTypes)));

        foreach ($backlinkTypes as $type) {
            // Check if plan allows this backlink type
            if (!$plan->allowsBacklinkType($type)) {
                continue;
            }

            // Count pending tasks of this type for this campaign
            $pendingTasks = AutomationTask::where('campaign_id', $campaign->id)
                ->where('type', $type)
                ->whereIn('status', [AutomationTask::STATUS_PENDING, AutomationTask::STATUS_RUNNING])
                ->count();

            // Create new tasks if needed
            $tasksToCreate = max(0, $tasksPerType - $pendingTasks);

            for ($i = 0; $i < $tasksToCreate; $i++) {
                AutomationTask::create([
                    'campaign_id' => $campaign->id,
                    'type' => $type,
                    'status' => AutomationTask::STATUS_PENDING,
                    'payload' => [
                        'campaign_id' => $campaign->id,
                        'target_urls' => $campaign->web_target ?? [],
                        'keywords' => $campaign->web_keyword ?? '',
                        'anchor_text_strategy' => $settings['anchor_text_strategy'] ?? 'variation',
                        'content_tone' => $settings['content_tone'] ?? 'professional',
                    ],
                    'max_retries' => 3,
                    'retry_count' => 0,
                ]);
            }
        }

        Log::info('Campaign scheduled', [
            'campaign_id' => $campaign->id,
            'tasks_created' => $tasksPerType * count($backlinkTypes),
        ]);
    }
}

