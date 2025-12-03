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
use App\Services\BlocklistService;
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
            // Pause campaign if user has no plan
            $campaign->update(['status' => Campaign::STATUS_PAUSED]);
            return;
        }

        // Check daily limit (-1 means unlimited)
        $planDailyLimit = $plan->daily_backlink_limit ?? 10;
        $todayBacklinks = 0;
        
        if ($planDailyLimit !== -1) {
            $todayBacklinks = Backlink::whereHas('campaign', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->whereDate('created_at', today())->count();

            if ($todayBacklinks >= $planDailyLimit) {
                Log::info('Daily limit reached for user', [
                    'user_id' => $user->id,
                    'daily_limit' => $planDailyLimit,
                    'today_count' => $todayBacklinks,
                ]);
                return;
            }
        }

        // Check campaign daily limit
        $campaignDailyLimit = $campaign->daily_limit ?? 10;
        $campaignTodayBacklinks = 0;
        
        if ($campaignDailyLimit !== -1) {
            $campaignTodayBacklinks = $campaign->backlinks()
                ->whereDate('created_at', today())
                ->count();

            if ($campaignTodayBacklinks >= $campaignDailyLimit) {
                Log::info('Campaign daily limit reached', [
                    'campaign_id' => $campaign->id,
                    'daily_limit' => $campaignDailyLimit,
                    'today_count' => $campaignTodayBacklinks,
                ]);
                return;
            }
        }

        // Check campaign total limit (-1 means unlimited)
        $campaignTotalLimit = $campaign->total_limit ?? 100;
        if ($campaignTotalLimit !== -1) {
            $campaignTotalBacklinks = $campaign->backlinks()->count();
            if ($campaignTotalBacklinks >= $campaignTotalLimit) {
                $campaign->update(['status' => Campaign::STATUS_COMPLETED]);
                Log::info('Campaign total limit reached, marking as completed', [
                    'campaign_id' => $campaign->id,
                    'total_limit' => $campaignTotalLimit,
                    'total_count' => $campaignTotalBacklinks,
                ]);
                
                // Send notification
                \App\Services\NotificationService::notifyCampaignCompleted($campaign);
                
                return;
            }
        }

        // Check if campaign target URLs are blocked
        $targetUrls = $campaign->web_target ?? [];
        if (!empty($targetUrls)) {
            foreach ($targetUrls as $url) {
                if (BlocklistService::isBlocked($url)) {
                    $reason = BlocklistService::getBlockReason($url);
                    Log::warning('Campaign target URL is blocked', [
                        'campaign_id' => $campaign->id,
                        'url' => $url,
                        'reason' => $reason,
                    ]);
                    // Skip this campaign if any target URL is blocked
                    return;
                }
            }
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
        // Calculate remaining limits
        $campaignRemaining = ($campaignDailyLimit === -1) ? PHP_INT_MAX : ($campaignDailyLimit - $campaignTodayBacklinks);
        $planRemaining = ($planDailyLimit === -1) ? PHP_INT_MAX : ($planDailyLimit - $todayBacklinks);
        
        $remainingDailyLimit = min($campaignRemaining, $planRemaining);

        if ($remainingDailyLimit <= 0) {
            Log::info('No remaining daily limit for campaign', [
                'campaign_id' => $campaign->id,
                'campaign_remaining' => $campaignRemaining,
                'plan_remaining' => $planRemaining,
            ]);
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

            // Filter target URLs by domain rate limit
            $targetUrls = $campaign->web_target ?? [];
            $filteredUrls = [];
            
            foreach ($targetUrls as $url) {
                // Check domain rate limit before creating task
                if (RateLimitingService::checkDomainRateLimit($url, $campaign->id)) {
                    $filteredUrls[] = $url;
                } else {
                    Log::info('Skipping URL due to domain rate limit', [
                        'url' => $url,
                        'campaign_id' => $campaign->id,
                    ]);
                }
            }

            // Only create tasks if we have valid URLs
            if (empty($filteredUrls)) {
                Log::info('No valid URLs after rate limit filtering', [
                    'campaign_id' => $campaign->id,
                    'type' => $type,
                ]);
                continue;
            }

            for ($i = 0; $i < $tasksToCreate; $i++) {
                AutomationTask::create([
                    'campaign_id' => $campaign->id,
                    'type' => $type,
                    'status' => AutomationTask::STATUS_PENDING,
                    'payload' => [
                        'campaign_id' => $campaign->id,
                        'target_urls' => $filteredUrls, // Use filtered URLs
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

