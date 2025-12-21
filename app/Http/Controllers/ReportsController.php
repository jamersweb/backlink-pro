<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\BacklinkOpportunity;
use App\Services\ExportService;
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
            'total_backlinks' => BacklinkOpportunity::whereIn('campaign_id', $campaignIds)->count(),
            'verified_backlinks' => BacklinkOpportunity::whereIn('campaign_id', $campaignIds)->where('status', BacklinkOpportunity::STATUS_VERIFIED)->count(),
            'pending_backlinks' => BacklinkOpportunity::whereIn('campaign_id', $campaignIds)->where('status', BacklinkOpportunity::STATUS_PENDING)->count(),
            'error_backlinks' => BacklinkOpportunity::whereIn('campaign_id', $campaignIds)->where('status', BacklinkOpportunity::STATUS_FAILED)->count(),
        ];

        // Backlinks by type
        $backlinksByType = BacklinkOpportunity::whereIn('campaign_id', $campaignIds)
            ->select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->get()
            ->pluck('count', 'type');

        // Backlinks by status
        $backlinksByStatus = BacklinkOpportunity::whereIn('campaign_id', $campaignIds)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        // Daily backlinks created (last 30 days)
        $dailyBacklinks = BacklinkOpportunity::whereIn('campaign_id', $campaignIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Campaign performance
        $campaignPerformance = Campaign::where('user_id', $user->id)
            ->withCount(['opportunities', 'opportunities as verified_backlinks_count' => function($query) {
                $query->where('status', BacklinkOpportunity::STATUS_VERIFIED);
            }])
            ->get()
            ->map(function($campaign) {
                return [
                    'id' => $campaign->id,
                    'name' => $campaign->name,
                    'status' => $campaign->status,
                    'total_backlinks' => $campaign->opportunities_count,
                    'verified_backlinks' => $campaign->verified_backlinks_count,
                    'success_rate' => $campaign->opportunities_count > 0 
                        ? round(($campaign->verified_backlinks_count / $campaign->opportunities_count) * 100, 2)
                        : 0,
                ];
            })
            ->sortByDesc('opportunities_count')
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

    /**
     * Export reports data
     */
    public function export(Request $request)
    {
        $user = Auth::user();
        $campaignIds = Campaign::where('user_id', $user->id)->pluck('id');

        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $format = $request->get('format', 'csv');

        // Get campaign performance data
        $campaignPerformance = Campaign::where('user_id', $user->id)
            ->withCount(['opportunities', 'opportunities as verified_backlinks_count' => function($query) {
                $query->where('status', BacklinkOpportunity::STATUS_VERIFIED);
            }])
            ->get()
            ->map(function($campaign) {
                return [
                    'id' => $campaign->id,
                    'name' => $campaign->name,
                    'status' => $campaign->status,
                    'total_backlinks' => $campaign->opportunities_count,
                    'verified_backlinks' => $campaign->verified_backlinks_count,
                    'success_rate' => $campaign->opportunities_count > 0 
                        ? round(($campaign->verified_backlinks_count / $campaign->opportunities_count) * 100, 2)
                        : 0,
                    'created_at' => $campaign->created_at->toDateTimeString(),
                ];
            });

        // Get daily backlinks
        $dailyBacklinks = BacklinkOpportunity::whereIn('campaign_id', $campaignIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function($item) {
                return [
                    'date' => $item->date,
                    'count' => $item->count,
                ];
            });

        if ($format === 'json') {
            $data = [
                'report_period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
                'campaign_performance' => $campaignPerformance,
                'daily_backlinks' => $dailyBacklinks,
            ];

            return ExportService::exportJson(
                $data,
                'reports-' . now()->format('Y-m-d') . '.json'
            );
        }

        // CSV export
        $headers = ['Campaign Name', 'Status', 'Total Backlinks', 'Verified Backlinks', 'Success Rate %', 'Created At'];
        $data = $campaignPerformance->map(function($campaign) {
            return [
                $campaign['name'],
                $campaign['status'],
                $campaign['total_backlinks'],
                $campaign['verified_backlinks'],
                $campaign['success_rate'],
                $campaign['created_at'],
            ];
        })->toArray();

        return ExportService::exportCsv(
            $data,
            $headers,
            'reports-' . now()->format('Y-m-d') . '.csv'
        );
    }
}

