<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\BacklinkOpportunity;
use App\Models\Backlink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OpportunityController extends Controller
{
    /**
     * GET /api/opportunities/for-campaign/{campaign_id}
     * Get opportunities for a campaign based on category, plan limits, and daily limits
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
        
        // Build query for opportunities
        $query = BacklinkOpportunity::query()
            ->where('status', 'active')
            ->whereBetween('pa', [$minPa, $maxPa])
            ->whereBetween('da', [$minDa, $maxDa])
            ->whereHas('categories', function($q) use ($categoryIds) {
                $q->whereIn('categories.id', $categoryIds);
            });
        
        // Filter by site type if needed (optional)
        if ($request->has('site_type')) {
            $query->where('site_type', $request->site_type);
        }
        
        // Get today's date for daily limit checking
        $today = Carbon::today();
        
        // Get opportunities with daily limit tracking
        $opportunities = $query->with('categories')
            ->orderByDesc(DB::raw('(pa + da)')) // Prioritize higher PA+DA
            ->limit($count * 10) // Get more than needed for filtering
            ->get();
        
        // Filter by daily limits
        $filteredOpportunities = [];
        foreach ($opportunities as $opportunity) {
            // Check campaign daily limit
            $campaignTodayCount = Backlink::where('campaign_id', $campaignId)
                ->whereDate('created_at', $today)
                ->count();
            
            if ($campaign->daily_limit && $campaignTodayCount >= $campaign->daily_limit) {
                continue; // Campaign daily limit reached
            }
            
            // Check site daily limit
            $siteTodayCount = Backlink::where('backlink_opportunity_id', $opportunity->id)
                ->whereDate('created_at', $today)
                ->count();
            
            if ($opportunity->daily_site_limit && $siteTodayCount >= $opportunity->daily_site_limit) {
                continue; // Site daily limit reached
            }
            
            // Check if this opportunity was already used by this campaign today
            $campaignSiteTodayCount = Backlink::where('campaign_id', $campaignId)
                ->where('backlink_opportunity_id', $opportunity->id)
                ->whereDate('created_at', $today)
                ->count();
            
            if ($campaignSiteTodayCount > 0) {
                continue; // Already used by this campaign today
            }
            
            $filteredOpportunities[] = $opportunity;
            
            if (count($filteredOpportunities) >= $count) {
                break;
            }
        }
        
        // Randomize selection (prioritize higher PA/DA but add randomness)
        if (count($filteredOpportunities) > $count) {
            // Take top 50% by PA+DA, then randomize
            $topHalf = array_slice($filteredOpportunities, 0, ceil(count($filteredOpportunities) / 2));
            shuffle($topHalf);
            $filteredOpportunities = array_slice($topHalf, 0, $count);
        }
        
        return response()->json([
            'success' => true,
            'opportunities' => array_map(function($opp) {
                return [
                    'id' => $opp->id,
                    'url' => $opp->url,
                    'pa' => $opp->pa,
                    'da' => $opp->da,
                    'site_type' => $opp->site_type,
                    'daily_site_limit' => $opp->daily_site_limit,
                    'categories' => $opp->categories->pluck('id')->toArray(),
                ];
            }, $filteredOpportunities),
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

