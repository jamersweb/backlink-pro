<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\GscDailyMetric;
use App\Models\Ga4DailyMetric;
use App\Models\RankProject;
use App\Models\RankKeyword;
use App\Models\RankResult;
use App\Models\SeoAlert;
use App\Models\MonthlyReport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SeoController extends Controller
{
    /**
     * Show SEO dashboard
     */
    public function dashboard(Organization $organization, Request $request)
    {
        $this->authorize('view', $organization);

        $dateRange = $request->input('date_range', '30');
        $startDate = Carbon::now()->subDays($dateRange)->format('Y-m-d');
        $endDate = Carbon::today()->format('Y-m-d');

        // GSC metrics
        $gscMetrics = GscDailyMetric::where('organization_id', $organization->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get();

        // GA4 metrics
        $ga4Metrics = Ga4DailyMetric::where('organization_id', $organization->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get();

        // Latest alerts
        $alerts = SeoAlert::where('organization_id', $organization->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Rankings summary
        $rankings = $this->getRankingsSummary($organization);

        return Inertia::render('SEO/Dashboard', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
            ],
            'gscMetrics' => $gscMetrics->map(function ($metric) {
                return [
                    'date' => $metric->date->format('Y-m-d'),
                    'clicks' => $metric->clicks,
                    'impressions' => $metric->impressions,
                    'ctr' => $metric->ctr,
                    'position' => $metric->position,
                ];
            }),
            'ga4Metrics' => $ga4Metrics->map(function ($metric) {
                return [
                    'date' => $metric->date->format('Y-m-d'),
                    'sessions' => $metric->sessions,
                    'users' => $metric->users,
                    'conversions' => $metric->conversions,
                    'revenue' => $metric->revenue,
                ];
            }),
            'alerts' => $alerts->map(function ($alert) {
                return [
                    'id' => $alert->id,
                    'severity' => $alert->severity,
                    'title' => $alert->title,
                    'message' => $alert->message,
                    'created_at' => $alert->created_at->toIso8601String(),
                ];
            }),
            'rankings' => $rankings,
            'dateRange' => $dateRange,
        ]);
    }

    /**
     * Get rankings summary
     */
    protected function getRankingsSummary(Organization $organization): array
    {
        $projects = RankProject::where('organization_id', $organization->id)
            ->where('status', RankProject::STATUS_ACTIVE)
            ->with(['keywords' => function ($query) {
                $query->where('is_active', true);
            }])
            ->get();

        $summary = [];
        foreach ($projects as $project) {
            foreach ($project->keywords as $keyword) {
                $latest = $keyword->latestResult();
                if (!$latest) continue;

                $previous = RankResult::where('rank_keyword_id', $keyword->id)
                    ->where('fetched_at', '<', $latest->fetched_at)
                    ->orderBy('fetched_at', 'desc')
                    ->first();

                $summary[] = [
                    'keyword' => $keyword->keyword,
                    'current_position' => $latest->position,
                    'previous_position' => $previous?->position,
                    'change' => $previous ? $previous->position - $latest->position : null,
                    'url' => $latest->found_url,
                ];
            }
        }

        return $summary;
    }

    /**
     * Get GSC queries
     */
    public function gscQueries(Organization $organization, Request $request)
    {
        $this->authorize('view', $organization);

        $dateRange = $request->input('date_range', '7');
        $startDate = Carbon::now()->subDays($dateRange)->format('Y-m-d');
        $endDate = Carbon::today()->format('Y-m-d');

        $queries = \App\Models\GscQueryMetric::where('organization_id', $organization->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->select('query', DB::raw('SUM(clicks) as clicks'), DB::raw('SUM(impressions) as impressions'), DB::raw('AVG(ctr) as ctr'), DB::raw('AVG(position) as position'))
            ->groupBy('query')
            ->orderByDesc('clicks')
            ->limit(100)
            ->get();

        return response()->json($queries);
    }

    /**
     * Get GA4 pages
     */
    public function ga4Pages(Organization $organization, Request $request)
    {
        $this->authorize('view', $organization);

        $dateRange = $request->input('date_range', '7');
        $startDate = Carbon::now()->subDays($dateRange)->format('Y-m-d');
        $endDate = Carbon::today()->format('Y-m-d');

        $pages = \App\Models\Ga4PageMetric::where('organization_id', $organization->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->select('page_path', 'page_title', DB::raw('SUM(views) as views'), DB::raw('SUM(active_users) as active_users'), DB::raw('SUM(conversions) as conversions'))
            ->groupBy('page_path', 'page_title')
            ->orderByDesc('views')
            ->limit(100)
            ->get();

        return response()->json($pages);
    }

    /**
     * List monthly reports
     */
    public function reports(Organization $organization)
    {
        $this->authorize('view', $organization);

        $reports = MonthlyReport::where('organization_id', $organization->id)
            ->orderBy('month', 'desc')
            ->get();

        return Inertia::render('SEO/Reports', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
            ],
            'reports' => $reports->map(function ($report) {
                return [
                    'id' => $report->id,
                    'month' => $report->month,
                    'generated_at' => $report->generated_at->toIso8601String(),
                    'download_url' => route('seo.reports.download', [
                        'organization' => $organization->id,
                        'report' => $report->id,
                    ]),
                ];
            }),
        ]);
    }

    /**
     * Download monthly report
     */
    public function downloadReport(Organization $organization, MonthlyReport $report)
    {
        $this->authorize('view', $organization);

        if ($report->organization_id !== $organization->id) {
            abort(403);
        }

        if (!\Illuminate\Support\Facades\Storage::exists($report->file_path)) {
            abort(404, 'Report file not found.');
        }

        return \Illuminate\Support\Facades\Storage::download($report->file_path, "seo-report-{$report->month}.pdf");
    }
}
