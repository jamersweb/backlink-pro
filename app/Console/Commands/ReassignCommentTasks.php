<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AutomationTask;
use App\Models\Campaign;
use App\Models\BacklinkOpportunity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReassignCommentTasks extends Command
{
    protected $signature = 'tasks:reassign-comments {--campaign-id= : Specific campaign ID} {--dry-run : Show what would be done without making changes}';
    protected $description = 'Clear all comment tasks and reassign them with different opportunity URLs';

    public function handle()
    {
        $campaignId = $this->option('campaign-id');
        $dryRun = $this->option('dry-run');

        $this->info('Reassigning comment tasks...');
        $this->newLine();

        // Build query for comment tasks
        $query = AutomationTask::where('type', 'comment');
        
        if ($campaignId) {
            $query->where('campaign_id', $campaignId);
            $this->info("Filtering by campaign ID: {$campaignId}");
        }

        // Get all comment tasks
        $tasks = $query->get();
        $this->info("Found {$tasks->count()} comment tasks");

        if ($tasks->isEmpty()) {
            $this->warn('No comment tasks found');
            return 0;
        }

        // Group by campaign
        $tasksByCampaign = $tasks->groupBy('campaign_id');
        $this->info("Tasks grouped into " . $tasksByCampaign->count() . " campaigns");
        $this->newLine();

        $totalDeleted = 0;
        $totalCreated = 0;

        foreach ($tasksByCampaign as $campaignId => $campaignTasks) {
            $campaign = Campaign::find($campaignId);
            if (!$campaign) {
                $this->warn("Campaign {$campaignId} not found, skipping...");
                continue;
            }

            $this->info("Processing Campaign: {$campaign->name} (ID: {$campaignId})");
            
            // Get available opportunities for this campaign
            $opportunities = BacklinkOpportunity::where('campaign_id', $campaignId)
                ->where('type', 'comment')
                ->where('status', 'pending')
                ->with('backlink:id,url,pa,da')
                ->get();

            $this->info("  Found {$opportunities->count()} available comment opportunities");

            // If no opportunities exist, use backlinks directly
            if ($opportunities->isEmpty()) {
                $this->warn("  No opportunities found, using backlinks directly...");
                
                // Get backlinks that can be used for comments
                $backlinks = \App\Models\Backlink::where('status', 'active')
                    ->where(function($query) {
                        $query->where('site_type', 'comment')
                              ->orWhere('site_type', 'blog')
                              ->orWhereNull('site_type');
                    })
                    ->select('id', 'url', 'pa', 'da', 'site_type')
                    ->limit(100) // Limit to avoid too many
                    ->get();
                
                $this->info("  Found {$backlinks->count()} available backlinks");
                
                if ($backlinks->isEmpty()) {
                    $this->warn("  No backlinks available for campaign {$campaignId}");
                    continue;
                }
                
                // Convert backlinks to opportunity-like structure
                $opportunities = $backlinks->map(function($backlink) use ($campaignId) {
                    return (object)[
                        'id' => $backlink->id,
                        'url' => $backlink->url,
                        'pa' => $backlink->pa,
                        'da' => $backlink->da,
                        'backlink' => $backlink,
                        'campaign_id' => $campaignId,
                        'type' => 'comment',
                    ];
                });
            }

            // Delete existing comment tasks for this campaign
            $deletedCount = $campaignTasks->count();
            if (!$dryRun) {
                AutomationTask::where('campaign_id', $campaignId)
                    ->where('type', 'comment')
                    ->whereIn('status', ['pending', 'failed', 'cancelled'])
                    ->delete();
                $this->info("  Deleted {$deletedCount} existing comment tasks");
            } else {
                $this->info("  [DRY RUN] Would delete {$deletedCount} existing comment tasks");
            }
            $totalDeleted += $deletedCount;

            // Get campaign settings
            $keywords = $campaign->web_keyword ?? '';
            if (is_string($keywords)) {
                $keywords = !empty($keywords) ? explode(',', $keywords) : [];
                $keywords = array_map('trim', $keywords);
                $keywords = array_filter($keywords);
            }
            if (empty($keywords)) {
                $keywords = [$campaign->web_name ?? 'SEO'];
            }

            $settings = $campaign->settings ?? [];
            if (!is_array($settings)) {
                $settings = json_decode($settings, true) ?? [];
            }

            // Create new tasks with different opportunity URLs
            $opportunitiesArray = $opportunities->values()->all();
            $opportunityIndex = 0;

            // Create tasks (one per opportunity, or reuse if needed)
            $tasksToCreate = min($deletedCount, $opportunities->count());
            
            for ($i = 0; $i < $tasksToCreate; $i++) {
                $opportunity = $opportunitiesArray[$opportunityIndex % count($opportunitiesArray)];
                $opportunityIndex++;

                // Handle both BacklinkOpportunity objects and converted backlink objects
                $targetUrl = null;
                $opportunityId = null;
                
                if (is_object($opportunity)) {
                    $targetUrl = $opportunity->url ?? ($opportunity->backlink->url ?? null);
                    $opportunityId = $opportunity->id ?? null;
                } else {
                    $targetUrl = $opportunity['url'] ?? ($opportunity['backlink']['url'] ?? null);
                    $opportunityId = $opportunity['id'] ?? null;
                }
                
                if (!$targetUrl) {
                    $this->warn("  Skipping opportunity {$opportunity->id} - no URL");
                    continue;
                }

                if (!$dryRun) {
                    AutomationTask::create([
                        'campaign_id' => $campaignId,
                        'type' => 'comment',
                        'status' => AutomationTask::STATUS_PENDING,
                        'payload' => [
                            'campaign_id' => $campaignId,
                            'target_urls' => [$targetUrl], // Assign specific URL
                            'opportunity_id' => $opportunityId,
                            'backlink_id' => $opportunityId, // Also store backlink_id if using backlinks directly
                            'keywords' => $keywords,
                            'anchor_text_strategy' => $settings['anchor_text_strategy'] ?? 'variation',
                            'content_tone' => $settings['content_tone'] ?? 'professional',
                        ],
                        'max_retries' => 3,
                        'retry_count' => 0,
                    ]);
                }

                $this->line("  Task " . ($i + 1) . ": {$targetUrl}");
            }

            if (!$dryRun) {
                $this->info("  Created {$tasksToCreate} new comment tasks");
            } else {
                $this->info("  [DRY RUN] Would create {$tasksToCreate} new comment tasks");
            }
            $totalCreated += $tasksToCreate;
            $this->newLine();
        }

        $this->newLine();
        $this->info("Summary:");
        $this->info("  Deleted: {$totalDeleted} tasks");
        $this->info("  Created: {$totalCreated} tasks");

        if ($dryRun) {
            $this->warn("\nThis was a DRY RUN. No changes were made.");
            $this->info("Run without --dry-run to apply changes.");
        }

        return 0;
    }
}

