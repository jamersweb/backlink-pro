<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\BacklinkOpportunity;
use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ActivityController extends Controller
{
    /**
     * Show activity feed
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Get activity logs for the user
        $query = ActivityLog::where('user_id', $user->id)
            ->with('user:id,name,email')
            ->latest();

        // Apply filters
        if ($request->has('action') && $request->action) {
            $query->where('action', $request->action);
        }

        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $activities = $query->paginate(50)->withQueryString();

        // Get stats
        $campaignIds = Campaign::where('user_id', $user->id)->pluck('id');
        $stats = [
            'total_backlinks' => BacklinkOpportunity::whereIn('campaign_id', $campaignIds)->count(),
            'verified_backlinks' => BacklinkOpportunity::whereIn('campaign_id', $campaignIds)->where('status', BacklinkOpportunity::STATUS_VERIFIED)->count(),
            'pending_backlinks' => BacklinkOpportunity::whereIn('campaign_id', $campaignIds)->where('status', BacklinkOpportunity::STATUS_PENDING)->count(),
            'active_campaigns' => Campaign::where('user_id', $user->id)->where('status', 'active')->count(),
            'total_activities' => ActivityLog::where('user_id', $user->id)->count(),
        ];

        // Get action types for filter
        $actionTypes = [
            ActivityLog::ACTION_CAMPAIGN_CREATED => 'Campaign Created',
            ActivityLog::ACTION_CAMPAIGN_UPDATED => 'Campaign Updated',
            ActivityLog::ACTION_CAMPAIGN_DELETED => 'Campaign Deleted',
            ActivityLog::ACTION_CAMPAIGN_PAUSED => 'Campaign Paused',
            ActivityLog::ACTION_CAMPAIGN_RESUMED => 'Campaign Resumed',
            ActivityLog::ACTION_BACKLINK_CREATED => 'Backlink Created',
            ActivityLog::ACTION_BACKLINK_VERIFIED => 'Backlink Verified',
            ActivityLog::ACTION_BACKLINK_FAILED => 'Backlink Failed',
            ActivityLog::ACTION_DOMAIN_CREATED => 'Domain Created',
            ActivityLog::ACTION_DOMAIN_UPDATED => 'Domain Updated',
            ActivityLog::ACTION_DOMAIN_DELETED => 'Domain Deleted',
        ];

        return Inertia::render('Activity/Index', [
            'activities' => $activities,
            'stats' => $stats,
            'actionTypes' => $actionTypes,
            'filters' => $request->only(['action', 'date_from', 'date_to']),
        ]);
    }
}

