<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Backlink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReportsController extends Controller
{
    /**
     * Show analytics/reports
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $campaignIds = Campaign::where('user_id', $user->id)->pluck('id');

        // Date range
        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        // Overall stats
        $overallStats = [
            'total_campaigns' => Campaign::where('user_id', $user->id)->count(),
            'active_campaigns' => Campaign::where('user_id', $user->id)->where('status', 'active')->count(),
            'total_backlinks' => Backlink::whereIn('campaign_id', $campaignIds)->count(),
            'verified_backlinks' => Backlink::whereIn('campaign_id', $campaignIds)->where('status', 'verified')->count(),
            'pending_backlinks' => Backlink::whereIn('campaign_id', $campaignIds)->where('status', 'pending')->count(),
            'error_backlinks' => Backlink::whereIn('campaign_id', $campaignIds)->where('status', 'error')->count(),
        ];

        // Backlinks by type
        $backlinksByType = Backlink::whereIn('campaign_id', $campaignIds)
            ->select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->get()
            ->pluck('count', 'type');

        // Backlinks by status
        $backlinksByStatus = Backlink::whereIn('campaign_id', $campaignIds)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        // Daily backlinks created (last 30 days)
        $dailyBacklinks = Backlink::whereIn('campaign_id', $campaignIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Campaign performance
        $campaignPerformance = Campaign::where('user_id', $user->id)
            ->withCount(['backlinks', 'backlinks as verified_backlinks_count' => function($query) {
                $query->where('status', 'verified');
            }])
            ->get()
            ->map(function($campaign) {
                return [
                    'id' => $campaign->id,
                    'name' => $campaign->name,
                    'status' => $campaign->status,
                    'total_backlinks' => $campaign->backlinks_count,
                    'verified_backlinks' => $campaign->verified_backlinks_count,
                    'success_rate' => $campaign->backlinks_count > 0 
                        ? round(($campaign->verified_backlinks_count / $campaign->backlinks_count) * 100, 2)
                        : 0,
                ];
            })
            ->sortByDesc('total_backlinks')
            ->take(10)
            ->values();

        return Inertia::render('Reports/Index', [
            'overallStats' => $overallStats,
            'backlinksByType' => $backlinksByType,
            'backlinksByStatus' => $backlinksByStatus,
            'dailyBacklinks' => $dailyBacklinks,
            'campaignPerformance' => $campaignPerformance,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }
}

