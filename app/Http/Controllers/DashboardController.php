<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\Campaign;
use App\Models\BacklinkOpportunity;
use Stripe\Stripe;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user()->load('plan');
        $campaignIds = Campaign::where('user_id', $user->id)->pluck('id');
        
        // Get stats from opportunities (where user's links were added)
        $totalOpportunities = BacklinkOpportunity::whereIn('campaign_id', $campaignIds)->count();
        
        $linksToday = BacklinkOpportunity::whereIn('campaign_id', $campaignIds)
            ->whereDate('created_at', today())->count();
        
        $activeCampaigns = Campaign::where('user_id', $user->id)
            ->where('status', Campaign::STATUS_ACTIVE)
            ->count();
        
        $verifiedLinks = BacklinkOpportunity::whereIn('campaign_id', $campaignIds)
            ->where('status', BacklinkOpportunity::STATUS_VERIFIED)->count();
        
        // Get daily limit from user's plan
        $dailyLimit = $user->plan?->daily_backlink_limit ?? 0;
        
        // Get recent opportunities (where links were added)
        $recentOpportunities = BacklinkOpportunity::whereIn('campaign_id', $campaignIds)
            ->with(['campaign:id,name', 'backlink:id,url,pa,da'])
            ->latest()
            ->limit(10)
            ->get();
        
        // Get recent campaigns
        $recentCampaigns = Campaign::where('user_id', $user->id)
            ->withCount('opportunities')
            ->latest()
            ->limit(5)
            ->get();
        
        // Get subscription info
        $subscription = null;
        if ($user->stripe_subscription_id && config('services.stripe.secret')) {
            try {
                Stripe::setApiKey(config('services.stripe.secret'));
                $subscription = \Stripe\Subscription::retrieve($user->stripe_subscription_id);
            } catch (\Exception $e) {
                // Handle error silently
            }
        }
        
        // Get analytics preview (last 30 days)
        $dailyBacklinks = BacklinkOpportunity::whereIn('campaign_id', $campaignIds)
            ->whereBetween('created_at', [now()->subDays(30), now()])
            ->selectRaw('DATE(created_at) as date, count(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        // Get opportunities by type
        $backlinksByType = BacklinkOpportunity::whereIn('campaign_id', $campaignIds)
            ->selectRaw('type, count(*) as count')
            ->groupBy('type')
            ->get()
            ->pluck('count', 'type');
        
        return Inertia::render('Dashboard', [
            'user' => $user,
            'subscription' => $subscription ? [
                'status' => $subscription->status,
                'current_period_end' => $subscription->current_period_end,
            ] : null,
            'stats' => [
                'total_backlinks' => $totalOpportunities,
                'links_today' => $linksToday,
                'daily_limit' => $dailyLimit,
                'active_campaigns' => $activeCampaigns,
                'verified_links' => $verifiedLinks,
            ],
            'recentBacklinks' => $recentOpportunities,
            'recentCampaigns' => $recentCampaigns,
            'dailyBacklinks' => $dailyBacklinks,
            'backlinksByType' => $backlinksByType,
        ]);
    }
}
