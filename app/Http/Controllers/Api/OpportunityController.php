<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\BacklinkOpportunity;
use App\Models\Backlink;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OpportunityController extends Controller
{
    use ApiResponse;
    /**
     * GET /api/opportunities/for-campaign/{campaign_id}
     * Get backlinks from the store for a campaign based on category, plan limits, and daily limits
     * Returns backlinks that can be used to create opportunities
     */
    public function getForCampaign(Request $request, $campaignId)
    {
        $campaign = Campaign::with(['user.plan', 'category', 'subcategory'])->findOrFail($campaignId);
        
        // Get plan PA/DA limits
        $plan = $campaign->user->plan;
        if (!$plan) {
            return response()->json([
                'success' => false,
                'error' => 'User does not have a plan assigned',
            ], 400);
        }
        
        $minPa = $plan->min_pa ?? 0;
        $maxPa = $plan->max_pa ?? 100;
        $minDa = $plan->min_da ?? 0;
        $maxDa = $plan->max_da ?? 100;
        
        // Get campaign category and subcategory IDs
        $categoryIds = [];
        if ($campaign->category_id) {
            $categoryIds[] = $campaign->category_id;
        }
        if ($campaign->subcategory_id) {
            $categoryIds[] = $campaign->subcategory_id;
        }
        
        if (empty($categoryIds)) {
            return response()->json([
                'success' => false,
                'error' => 'Campaign must have a category or subcategory selected',
            ], 400);
        }
        
        // Get count needed (default 1, can be specified)
        $count = $request->get('count', 1);
        $taskType = $request->get('task_type', 'comment'); // comment, profile, forum, guest
        
        // Build query for backlinks from the store
        $query = Backlink::query()
            ->where('status', Backlink::STATUS_ACTIVE)
            ->whereBetween('pa', [$minPa, $maxPa])
            ->whereBetween('da', [$minDa, $maxDa])
            ->whereHas('categories', function($q) use ($categoryIds) {
                $q->whereIn('categories.id', $categoryIds);
            });
        
        // Filter by site type if needed (optional)
        if ($request->has('site_type')) {
            $query->where('site_type', $request->site_type);
        } else {
            // Map task_type to site_type
            $siteTypeMap = [
                'comment' => 'comment',
                'profile' => 'profile',
                'forum' => 'forum',
                'guest' => 'guestposting',
                'guestposting' => 'guestposting',
            ];
            if (isset($siteTypeMap[$taskType])) {
                $query->where('site_type', $siteTypeMap[$taskType]);
            }
        }
        
        // Get today's date for daily limit checking
        $today = Carbon::today();
        
        // Get backlinks with daily limit tracking
        $backlinks = $query->with('categories')
            ->orderByDesc(DB::raw('(pa + da)')) // Prioritize higher PA+DA
            ->limit($count * 10) // Get more than needed for filtering
            ->get();
        
        // Filter by daily limits and exclude recently failed backlinks
        $filteredBacklinks = [];
        foreach ($backlinks as $backlink) {
            // Check campaign daily limit (count opportunities created today)
            $campaignTodayCount = BacklinkOpportunity::where('campaign_id', $campaignId)
                ->whereDate('created_at', $today)
                ->count();
            
            if ($campaign->daily_limit && $campaignTodayCount >= $campaign->daily_limit) {
                continue; // Campaign daily limit reached
            }
            
            // Check site daily limit (count opportunities created from this backlink today)
            $siteTodayCount = BacklinkOpportunity::where('backlink_id', $backlink->id)
                ->whereDate('created_at', $today)
                ->count();
            
            if ($backlink->daily_site_limit && $siteTodayCount >= $backlink->daily_site_limit) {
                continue; // Site daily limit reached
            }
            
            // Check if this backlink was already used by this campaign today
            $campaignSiteTodayCount = BacklinkOpportunity::where('campaign_id', $campaignId)
                ->where('backlink_id', $backlink->id)
                ->whereDate('created_at', $today)
                ->count();
            
            if ($campaignSiteTodayCount > 0) {
                continue; // Already used by this campaign today
            }
            
            // Check if this backlink has failed multiple times recently (last 2 hours)
            // Skip backlinks that have failed 3+ times in the last 2 hours
            $recentFailures = \App\Models\AutomationTask::where('status', \App\Models\AutomationTask::STATUS_FAILED)
                ->where('created_at', '>', now()->subHours(2))
                ->where(function($query) use ($backlink) {
                    $query->whereJsonContains('result->backlink_id', $backlink->id)
                          ->orWhereJsonContains('payload->backlink_id', $backlink->id);
                })
                ->count();
            
            if ($recentFailures >= 3) {
                continue; // Skip backlinks with too many recent failures
            }
            
            $filteredBacklinks[] = $backlink;
            
            if (count($filteredBacklinks) >= $count) {
                break;
            }
        }
        
        // Randomize selection (prioritize higher PA/DA but add randomness)
        if (count($filteredBacklinks) > $count) {
            // Take top 50% by PA+DA, then randomize
            $topHalf = array_slice($filteredBacklinks, 0, ceil(count($filteredBacklinks) / 2));
            shuffle($topHalf);
            $filteredBacklinks = array_slice($topHalf, 0, $count);
        }
        
        return response()->json([
            'success' => true,
            'opportunities' => array_map(function($backlink) {
                return [
                    'id' => $backlink->id,
                    'url' => $backlink->url,
                    'pa' => $backlink->pa,
                    'da' => $backlink->da,
                    'site_type' => $backlink->site_type,
                    'daily_site_limit' => $backlink->daily_site_limit,
                    'categories' => $backlink->categories->pluck('id')->toArray(),
                ];
            }, $filteredBacklinks),
            'campaign' => [
                'id' => $campaign->id,
                'category_id' => $campaign->category_id,
                'subcategory_id' => $campaign->subcategory_id,
            ],
            'plan_limits' => [
                'min_pa' => $minPa,
                'max_pa' => $maxPa,
                'min_da' => $minDa,
                'max_da' => $maxDa,
            ],
        ]);
    }
}
