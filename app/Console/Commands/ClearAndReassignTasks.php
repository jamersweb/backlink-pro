<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AutomationTask;
use App\Models\Campaign;
use App\Models\BacklinkOpportunity;
use Illuminate\Support\Facades\DB;

class ClearAndReassignTasks extends Command
{
    protected $signature = 'tasks:clear-reassign {type=comment : Task type to clear and reassign}';
    protected $description = 'Clear all tasks of a type and reassign them with different URLs';

    public function handle()
    {
        $type = $this->argument('type');
        
        $this->info("Clearing and reassigning {$type} tasks...");
        
        // Get all pending/failed tasks of this type
        $tasks = AutomationTask::where('type', $type)
            ->whereIn('status', ['pending', 'failed', 'cancelled'])
            ->get();
        
        $this->info("Found {$tasks->count()} {$type} tasks to clear");
        
        if ($tasks->isEmpty()) {
            $this->warn('No tasks found');
            return 0;
        }
        
        // Group by campaign
        $byCampaign = $tasks->groupBy('campaign_id');
        
        DB::beginTransaction();
        try {
            // Delete all tasks
            AutomationTask::where('type', $type)
                ->whereIn('status', ['pending', 'failed', 'cancelled'])
                ->delete();
            
            $this->info("Deleted {$tasks->count()} tasks");
            
            // Recreate tasks with different opportunities
            $totalCreated = 0;
            
            foreach ($byCampaign as $campaignId => $campaignTasks) {
                $campaign = Campaign::find($campaignId);
                if (!$campaign) continue;
                
                // Get available opportunities
                $opportunities = BacklinkOpportunity::where('campaign_id', $campaignId)
                    ->where('type', $type)
                    ->where('status', 'pending')
                    ->with('backlink:id,url')
                    ->get()
                    ->unique('url'); // Remove duplicate URLs
                
                if ($opportunities->isEmpty()) {
                    $this->warn("No opportunities for campaign {$campaignId}");
                    continue;
                }
                
                $keywords = is_string($campaign->web_keyword) 
                    ? array_filter(array_map('trim', explode(',', $campaign->web_keyword)))
                    : [];
                if (empty($keywords)) {
                    $keywords = [$campaign->web_name ?? 'SEO'];
                }
                
                $settings = is_array($campaign->settings) 
                    ? $campaign->settings 
                    : (json_decode($campaign->settings, true) ?? []);
                
                // Create new tasks - one per unique opportunity
                $tasksToCreate = min($campaignTasks->count(), $opportunities->count());
                $opportunitiesArray = $opportunities->values()->all();
                
                for ($i = 0; $i < $tasksToCreate; $i++) {
                    $opp = $opportunitiesArray[$i % count($opportunitiesArray)];
                    $url = $opp->url ?? $opp->backlink->url ?? null;
                    
                    if (!$url) continue;
                    
                    AutomationTask::create([
                        'campaign_id' => $campaignId,
                        'type' => $type,
                        'status' => AutomationTask::STATUS_PENDING,
                        'payload' => [
                            'campaign_id' => $campaignId,
                            'target_urls' => [$url],
                            'opportunity_id' => $opp->id,
                            'keywords' => $keywords,
                            'anchor_text_strategy' => $settings['anchor_text_strategy'] ?? 'variation',
                        ],
                        'max_retries' => 3,
                        'retry_count' => 0,
                    ]);
                    $totalCreated++;
                }
                
                $this->info("Campaign {$campaignId}: Created {$tasksToCreate} tasks");
            }
            
            DB::commit();
            $this->info("Success! Created {$totalCreated} new tasks");
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}


