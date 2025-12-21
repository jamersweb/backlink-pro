<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BacklinkOpportunity;
use App\Models\AutomationTask;
use App\Models\Backlink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MLController extends Controller
{
    /**
     * GET /api/ml/historical-data
     * Get historical backlink data for ML training
     * Returns tasks and opportunities with success/failure outcomes
     */
    public function getHistoricalData(Request $request)
    {
        // Validate API token
        $apiToken = $request->header('X-API-Token');
        if ($apiToken !== config('app.api_token')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Get limit (default: 1000 records)
        $limit = $request->get('limit', 1000);
        $minDate = $request->get('min_date'); // Optional: filter by date

        // Get all completed tasks (success or failed) with their related data
        $query = AutomationTask::query()
            ->whereIn('status', [AutomationTask::STATUS_SUCCESS, AutomationTask::STATUS_FAILED])
            ->with(['campaign:id,name,daily_limit,total_limit,category_id,subcategory_id'])
            ->orderBy('created_at', 'desc');

        if ($minDate) {
            $query->where('created_at', '>=', $minDate);
        }

        $tasks = $query->limit($limit)->get();

        // Build historical records
        $historicalData = [];

        foreach ($tasks as $task) {
            // Determine success (status === 'success')
            $success = $task->status === AutomationTask::STATUS_SUCCESS;

            // Get backlink_id from result or payload
            $backlinkId = null;
            if ($task->result && isset($task->result['backlink_id'])) {
                $backlinkId = $task->result['backlink_id'];
            } elseif ($task->payload && isset($task->payload['backlink_id'])) {
                $backlinkId = $task->payload['backlink_id'];
            }

            // Get backlink data if available
            $backlink = null;
            if ($backlinkId) {
                $backlink = Backlink::find($backlinkId);
            }

            // If no backlink found, try to get from opportunity
            if (!$backlink) {
                $opportunity = BacklinkOpportunity::where('campaign_id', $task->campaign_id)
                    ->where('type', $task->type)
                    ->whereDate('created_at', $task->created_at->toDateString())
                    ->first();
                
                if ($opportunity && $opportunity->backlink_id) {
                    $backlink = Backlink::find($opportunity->backlink_id);
                    $backlinkId = $opportunity->backlink_id;
                }
            }

            // Skip if no backlink data available
            if (!$backlink) {
                continue;
            }

            // Get opportunity if exists
            $opportunity = null;
            if ($task->result && isset($task->result['opportunity_id'])) {
                $opportunity = BacklinkOpportunity::find($task->result['opportunity_id']);
            }

            // Build record
            $record = [
                'success' => $success,
                'created_at' => $task->created_at->toISOString(),
                'task' => [
                    'id' => $task->id,
                    'type' => $task->type,
                    'status' => $task->status,
                    'error_message' => $task->error_message,
                ],
                'backlink' => [
                    'id' => $backlink->id,
                    'url' => $backlink->url,
                    'pa' => $backlink->pa,
                    'da' => $backlink->da,
                    'site_type' => $backlink->site_type,
                    'status' => $backlink->status,
                ],
                'campaign' => $task->campaign ? [
                    'id' => $task->campaign->id,
                    'daily_limit' => $task->campaign->daily_limit,
                    'total_limit' => $task->campaign->total_limit,
                    'category_id' => $task->campaign->category_id,
                    'subcategory_id' => $task->campaign->subcategory_id,
                ] : null,
            ];

            if ($opportunity) {
                $record['opportunity'] = [
                    'id' => $opportunity->id,
                    'status' => $opportunity->status,
                    'type' => $opportunity->type,
                    'verified_at' => $opportunity->verified_at ? $opportunity->verified_at->toISOString() : null,
                ];
            }

            $historicalData[] = $record;
        }

        return response()->json([
            'success' => true,
            'count' => count($historicalData),
            'data' => $historicalData,
        ]);
    }

    /**
     * GET /api/ml/action-recommendation
     * Get AI recommendation for which action type to use for a backlink
     * This endpoint can be called by the decision service
     */
    public function getActionRecommendation(Request $request, $campaignId)
    {
        // Validate API token
        $apiToken = $request->header('X-API-Token');
        if ($apiToken !== config('app.api_token')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $backlinkId = $request->get('backlink_id');
        if (!$backlinkId) {
            return response()->json(['error' => 'backlink_id is required'], 400);
        }

        $backlink = Backlink::findOrFail($backlinkId);
        $campaign = \App\Models\Campaign::findOrFail($campaignId);

        // This is a placeholder - actual recommendation should come from Python ML service
        // For now, return basic recommendation based on site_type
        $siteType = $backlink->site_type;
        $recommendedType = $siteType === 'guestposting' ? 'guest' : $siteType;

        return response()->json([
            'success' => true,
            'recommended_action_type' => $recommendedType,
            'backlink_id' => $backlinkId,
            'campaign_id' => $campaignId,
            'note' => 'This is a basic recommendation. Use Python ML service for AI-based recommendations.',
        ]);
    }
}

