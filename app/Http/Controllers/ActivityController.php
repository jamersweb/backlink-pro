<?php

namespace App\Http\Controllers;

use App\Models\Log;
use App\Models\Backlink;
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
        
        // Get user's campaign IDs
        $campaignIds = Campaign::where('user_id', $user->id)->pluck('id');
        
        // Get recent backlinks
        $recentBacklinks = Backlink::whereIn('campaign_id', $campaignIds)
            ->with(['campaign' => function($query) {
                $query->select('id', 'name');
            }])
            ->latest()
            ->limit(20)
            ->get();

        // Get recent logs
        $recentLogs = Log::whereIn('campaign_id', $campaignIds)
            ->with(['campaign' => function($query) {
                $query->select('id', 'name');
            }])
            ->latest()
            ->limit(20)
            ->get();

        // Combine and sort activities
        $activities = collect()
            ->merge($recentBacklinks->map(function($backlink) {
                return [
                    'type' => 'backlink',
                    'id' => $backlink->id,
                    'message' => "New {$backlink->type} backlink created",
                    'campaign' => $backlink->campaign->name ?? 'Unknown',
                    'status' => $backlink->status,
                    'created_at' => $backlink->created_at,
                ];
            }))
            ->merge($recentLogs->map(function($log) {
                return [
                    'type' => 'log',
                    'id' => $log->id,
                    'message' => $log->message ?? 'Activity logged',
                    'campaign' => $log->campaign->name ?? 'Unknown',
                    'level' => $log->level ?? 'info',
                    'created_at' => $log->created_at,
                ];
            }))
            ->sortByDesc('created_at')
            ->take(50)
            ->values();

        // Get stats
        $stats = [
            'total_backlinks' => Backlink::whereIn('campaign_id', $campaignIds)->count(),
            'verified_backlinks' => Backlink::whereIn('campaign_id', $campaignIds)->where('status', 'verified')->count(),
            'pending_backlinks' => Backlink::whereIn('campaign_id', $campaignIds)->where('status', 'pending')->count(),
            'active_campaigns' => Campaign::where('user_id', $user->id)->where('status', 'active')->count(),
        ];

        return Inertia::render('Activity/Index', [
            'activities' => $activities,
            'stats' => $stats,
        ]);
    }
}

