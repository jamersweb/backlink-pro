<?php

namespace App\Services\SEO;

use App\Models\Organization;
use Carbon\Carbon;

class ReportBuilder
{
    /**
     * Build monthly executive report data
     */
    public function buildMonthlyReport(Organization $organization, Carbon $month): array
    {
        $startDate = $month->copy()->startOfMonth();
        $endDate = $month->copy()->endOfMonth();
        $previousMonth = $month->copy()->subMonth();

        // GSC metrics
        $gscCurrent = $this->getGscMonthlyMetrics($organization, $startDate, $endDate);
        $gscPrevious = $this->getGscMonthlyMetrics($organization, $previousMonth->startOfMonth(), $previousMonth->endOfMonth());

        // GA4 metrics
        $ga4Current = $this->getGa4MonthlyMetrics($organization, $startDate, $endDate);
        $ga4Previous = $this->getGa4MonthlyMetrics($organization, $previousMonth->startOfMonth(), $previousMonth->endOfMonth());

        // Rankings summary
        $rankings = $this->getRankingsSummary($organization, $startDate, $endDate);

        // Alerts recap
        $alerts = $this->getAlertsRecap($organization, $startDate, $endDate);

        return [
            'month' => $month->format('F Y'),
            'executive_summary' => $this->buildExecutiveSummary($gscCurrent, $gscPrevious, $ga4Current, $ga4Previous),
            'gsc' => [
                'current' => $gscCurrent,
                'previous' => $gscPrevious,
                'delta' => $this->calculateDelta($gscCurrent, $gscPrevious),
                'top_queries' => $this->getTopQueries($organization, $startDate, $endDate),
            ],
            'ga4' => [
                'current' => $ga4Current,
                'previous' => $ga4Previous,
                'delta' => $this->calculateDelta($ga4Current, $ga4Previous),
                'top_pages' => $this->getTopPages($organization, $startDate, $endDate),
            ],
            'rankings' => $rankings,
            'alerts' => $alerts,
        ];
    }

    /**
     * Get GSC monthly metrics
     */
    protected function getGscMonthlyMetrics(Organization $organization, Carbon $start, Carbon $end): array
    {
        $metrics = \App\Models\GscDailyMetric::where('organization_id', $organization->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get();

        return [
            'clicks' => $metrics->sum('clicks'),
            'impressions' => $metrics->sum('impressions'),
            'avg_ctr' => $metrics->avg('ctr'),
            'avg_position' => $metrics->avg('position'),
        ];
    }

    /**
     * Get GA4 monthly metrics
     */
    protected function getGa4MonthlyMetrics(Organization $organization, Carbon $start, Carbon $end): array
    {
        $metrics = \App\Models\Ga4DailyMetric::where('organization_id', $organization->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get();

        return [
            'sessions' => $metrics->sum('sessions'),
            'users' => $metrics->sum('users'),
            'new_users' => $metrics->sum('new_users'),
            'avg_engagement_rate' => $metrics->avg('engagement_rate'),
            'conversions' => $metrics->sum('conversions'),
            'revenue' => $metrics->sum('revenue'),
        ];
    }

    /**
     * Get rankings summary
     */
    protected function getRankingsSummary(Organization $organization, Carbon $start, Carbon $end): array
    {
        // Simplified - would query rank_results properly
        return [
            'top_movers' => [],
            'entering_top_10' => [],
            'leaving_top_10' => [],
        ];
    }

    /**
     * Get alerts recap
     */
    protected function getAlertsRecap(Organization $organization, Carbon $start, Carbon $end): array
    {
        return \App\Models\SeoAlert::where('organization_id', $organization->id)
            ->whereBetween('created_at', [$start, $end])
            ->get()
            ->groupBy('severity')
            ->map(fn($group) => $group->count())
            ->toArray();
    }

    /**
     * Calculate delta between two periods
     */
    protected function calculateDelta(array $current, array $previous): array
    {
        $delta = [];
        foreach ($current as $key => $value) {
            $prevValue = $previous[$key] ?? 0;
            if ($prevValue > 0) {
                $delta[$key] = (($value - $prevValue) / $prevValue) * 100;
            } else {
                $delta[$key] = $value > 0 ? 100 : 0;
            }
        }
        return $delta;
    }

    /**
     * Build executive summary
     */
    protected function buildExecutiveSummary(array $gscCurrent, array $gscPrevious, array $ga4Current, array $ga4Previous): array
    {
        $gscDelta = $this->calculateDelta($gscCurrent, $gscPrevious);
        $ga4Delta = $this->calculateDelta($ga4Current, $ga4Previous);

        return [
            'gsc_clicks_delta' => $gscDelta['clicks'] ?? 0,
            'gsc_impressions_delta' => $gscDelta['impressions'] ?? 0,
            'ga4_sessions_delta' => $ga4Delta['sessions'] ?? 0,
            'ga4_conversions_delta' => $ga4Delta['conversions'] ?? 0,
        ];
    }

    /**
     * Get top queries
     */
    protected function getTopQueries(Organization $organization, Carbon $start, Carbon $end): array
    {
        return \App\Models\GscQueryMetric::where('organization_id', $organization->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('query, SUM(clicks) as total_clicks, SUM(impressions) as total_impressions, AVG(position) as avg_position')
            ->groupBy('query')
            ->orderByDesc('total_clicks')
            ->limit(20)
            ->get()
            ->toArray();
    }

    /**
     * Get top pages
     */
    protected function getTopPages(Organization $organization, Carbon $start, Carbon $end): array
    {
        return \App\Models\Ga4PageMetric::where('organization_id', $organization->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('page_path, SUM(views) as total_views, SUM(active_users) as total_users')
            ->groupBy('page_path')
            ->orderByDesc('total_views')
            ->limit(20)
            ->get()
            ->toArray();
    }
}
