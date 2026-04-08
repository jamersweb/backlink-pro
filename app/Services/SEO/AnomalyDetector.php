<?php

namespace App\Services\SEO;

use App\Models\Organization;
use App\Models\SeoAlertRule;
use App\Models\SeoAlert;
use App\Models\GscPageMetric;
use Illuminate\Support\Facades\Log;

class AnomalyDetector
{
    /**
     * Detect anomalies for organization
     */
    public function detectAnomalies(Organization $organization, string $date): array
    {
        $alerts = [];
        $rules = SeoAlertRule::where('organization_id', $organization->id)
            ->where('is_enabled', true)
            ->get();

        foreach ($rules as $rule) {
            $detected = $this->checkRule($rule, $organization, $date);
            if ($detected !== null) {
                if (isset($detected[0]) && is_array($detected[0]) && array_key_exists('rule_id', $detected[0])) {
                    foreach ($detected as $a) {
                        $alerts[] = $a;
                    }
                } else {
                    $alerts[] = $detected;
                }
            }
        }

        return $alerts;
    }

    /**
     * Check a specific rule
     */
    protected function checkRule(SeoAlertRule $rule, Organization $organization, string $date): ?array
    {
        $config = $rule->config;
        $lookbackDays = $config['lookback_days'] ?? 7;
        $threshold = $config['threshold'] ?? 30;

        switch ($rule->type) {
            case 'gsc_clicks_drop':
                return $this->checkGscClicksDrop($rule, $organization, $date, $lookbackDays, $threshold);
            
            case 'ga4_sessions_drop':
                return $this->checkGa4SessionsDrop($rule, $organization, $date, $lookbackDays, $threshold);
            
            case 'rank_drop':
                return $this->checkRankDrop($rule, $organization, $date, $lookbackDays);
            
            case 'conversion_drop':
                return $this->checkConversionDrop($rule, $organization, $date, $lookbackDays, $threshold);

            case 'page_content_decay':
                return $this->checkPageContentDecay($rule, $organization, $date);
        }

        return null;
    }

    /**
     * Check page-level content decay (GSC clicks/impressions/position drop per page_url).
     * Compare recent lookback_days vs previous lookback_days; alert when drop > threshold
     * and (impressions drop OR position worsens), with min_baseline_clicks.
     */
    protected function checkPageContentDecay(SeoAlertRule $rule, Organization $organization, string $date): ?array
    {
        if (!\App\Support\Feature::enabled('content_decay')) {
            return null;
        }

        $config = $rule->config;
        $lookbackDays = (int) ($config['lookback_days'] ?? 7);
        $thresholdDropPct = (float) ($config['threshold_drop_pct'] ?? $config['threshold'] ?? 30);
        $minBaselineClicks = (int) ($config['min_baseline_clicks'] ?? 5);

        $dt = \Carbon\Carbon::parse($date);
        $recentStart = $dt->copy()->subDays($lookbackDays - 1)->toDateString();
        $recentEnd = $dt->toDateString();
        $baselineEnd = $dt->copy()->subDays($lookbackDays)->toDateString();
        $baselineStart = $dt->copy()->subDays(2 * $lookbackDays - 1)->toDateString();

        $recentRows = GscPageMetric::where('organization_id', $organization->id)
            ->whereBetween('date', [$recentStart, $recentEnd])
            ->get();

        $baselineRows = GscPageMetric::where('organization_id', $organization->id)
            ->whereBetween('date', [$baselineStart, $baselineEnd])
            ->get();

        $recentByPage = $recentRows->groupBy('page_url')->map(function ($rows) {
            return [
                'clicks' => $rows->sum('clicks'),
                'impressions' => $rows->sum('impressions'),
                'position' => $rows->avg('position'),
            ];
        });

        $baselineByPage = $baselineRows->groupBy('page_url')->map(function ($rows) {
            return [
                'clicks' => $rows->sum('clicks'),
                'impressions' => $rows->sum('impressions'),
                'position' => $rows->avg('position'),
            ];
        });

        $alerts = [];
        $pageUrls = $recentByPage->keys()->merge($baselineByPage->keys())->unique();

        foreach ($pageUrls as $pageUrl) {
            $recent = $recentByPage->get($pageUrl, ['clicks' => 0, 'impressions' => 0, 'position' => null]);
            $baseline = $baselineByPage->get($pageUrl, ['clicks' => 0, 'impressions' => 0, 'position' => null]);

            $baselineClicks = (int) $baseline['clicks'];
            $recentClicks = (int) $recent['clicks'];

            if ($baselineClicks < $minBaselineClicks || $baselineClicks <= 0) {
                continue;
            }

            $dropPct = (($baselineClicks - $recentClicks) / $baselineClicks) * 100;
            if ($dropPct < $thresholdDropPct) {
                continue;
            }

            $impressionsDropped = (int) $recent['impressions'] < (int) $baseline['impressions'];
            $recentPos = $recent['position'] !== null ? (float) $recent['position'] : null;
            $baselinePos = $baseline['position'] !== null ? (float) $baseline['position'] : null;
            $positionWorsened = $recentPos !== null && $baselinePos !== null && $recentPos > $baselinePos;

            if (!$impressionsDropped && !$positionWorsened) {
                continue;
            }

            $severity = $dropPct >= 50 ? SeoAlert::SEVERITY_CRITICAL : SeoAlert::SEVERITY_WARNING;
            $alerts[] = [
                'rule_id' => $rule->id,
                'severity' => $severity,
                'title' => 'Page content decay',
                'message' => sprintf(
                    'Page dropped %.1f%% clicks (baseline %d → recent %d). Consider updating content or meta.',
                    $dropPct,
                    $baselineClicks,
                    $recentClicks
                ),
                'diff' => [
                    'page_url' => $pageUrl,
                    'recent_clicks' => $recentClicks,
                    'baseline_clicks' => $baselineClicks,
                    'drop_percent' => round($dropPct, 1),
                    'recent_impressions' => (int) $recent['impressions'],
                    'baseline_impressions' => (int) $baseline['impressions'],
                    'recent_position' => $recentPos,
                    'baseline_position' => $baselinePos,
                ],
                'related_date' => $date,
            ];
        }

        return $alerts === [] ? null : $alerts;
    }

    /**
     * Check GSC clicks drop
     */
    protected function checkGscClicksDrop(SeoAlertRule $rule, Organization $organization, string $date, int $lookbackDays, float $threshold): ?array
    {
        $yesterday = \Carbon\Carbon::parse($date);
        $baselineStart = $yesterday->copy()->subDays($lookbackDays + 1);
        $baselineEnd = $yesterday->copy()->subDay();

        // Get yesterday's clicks
        $yesterdayMetric = \App\Models\GscDailyMetric::where('organization_id', $organization->id)
            ->where('date', $date)
            ->sum('clicks');

        // Get baseline average
        $baselineMetrics = \App\Models\GscDailyMetric::where('organization_id', $organization->id)
            ->whereBetween('date', [$baselineStart->toDateString(), $baselineEnd->toDateString()])
            ->get();

        if ($baselineMetrics->isEmpty() || $yesterdayMetric === 0) {
            return null;
        }

        $baselineAvg = $baselineMetrics->sum('clicks') / $baselineMetrics->count();
        $dropPercent = (($baselineAvg - $yesterdayMetric) / $baselineAvg) * 100;

        if ($dropPercent >= $threshold) {
            $severity = $dropPercent >= 50 ? 'critical' : 'warning';

            return [
                'rule_id' => $rule->id,
                'severity' => $severity,
                'title' => 'GSC Clicks Drop Detected',
                'message' => "Clicks dropped by " . round($dropPercent, 1) . "% compared to baseline ({$baselineAvg} → {$yesterdayMetric})",
                'diff' => [
                    'baseline_avg' => round($baselineAvg),
                    'yesterday' => $yesterdayMetric,
                    'drop_percent' => round($dropPercent, 1),
                ],
                'related_date' => $date,
            ];
        }

        return null;
    }

    /**
     * Check GA4 sessions drop
     */
    protected function checkGa4SessionsDrop(SeoAlertRule $rule, Organization $organization, string $date, int $lookbackDays, float $threshold): ?array
    {
        $yesterday = \Carbon\Carbon::parse($date);
        $baselineStart = $yesterday->copy()->subDays($lookbackDays + 1);
        $baselineEnd = $yesterday->copy()->subDay();

        $yesterdayMetric = \App\Models\Ga4DailyMetric::where('organization_id', $organization->id)
            ->where('date', $date)
            ->sum('sessions');

        $baselineMetrics = \App\Models\Ga4DailyMetric::where('organization_id', $organization->id)
            ->whereBetween('date', [$baselineStart->toDateString(), $baselineEnd->toDateString()])
            ->get();

        if ($baselineMetrics->isEmpty() || $yesterdayMetric === 0) {
            return null;
        }

        $baselineAvg = $baselineMetrics->sum('sessions') / $baselineMetrics->count();
        $dropPercent = (($baselineAvg - $yesterdayMetric) / $baselineAvg) * 100;

        if ($dropPercent >= $threshold) {
            $severity = $dropPercent >= 50 ? 'critical' : 'warning';

            return [
                'rule_id' => $rule->id,
                'severity' => $severity,
                'title' => 'GA4 Sessions Drop Detected',
                'message' => "Sessions dropped by " . round($dropPercent, 1) . "% ({$baselineAvg} → {$yesterdayMetric})",
                'diff' => [
                    'baseline_avg' => round($baselineAvg),
                    'yesterday' => $yesterdayMetric,
                    'drop_percent' => round($dropPercent, 1),
                ],
                'related_date' => $date,
            ];
        }

        return null;
    }

    /**
     * Check rank drop
     */
    protected function checkRankDrop(SeoAlertRule $rule, Organization $organization, string $date, int $lookbackDays): ?array
    {
        $config = $rule->config;
        $dropThreshold = $config['drop_positions'] ?? 10; // Default: 10 position drop
        $minPosition = $config['min_position'] ?? 100; // Only alert for keywords in top 100

        $yesterday = \Carbon\Carbon::parse($date);
        $baselineStart = $yesterday->copy()->subDays($lookbackDays + 1);
        $baselineEnd = $yesterday->copy()->subDay();

        // Get all active keywords for this organization
        $keywords = \App\Models\RankKeyword::whereHas('project', function ($query) use ($organization) {
            $query->where('organization_id', $organization->id)
                  ->where('status', \App\Models\RankProject::STATUS_ACTIVE);
        })
        ->where('is_active', true)
        ->get();

        $affectedKeywords = [];
        $criticalDrops = [];

        foreach ($keywords as $keyword) {
            // Get yesterday's rank
            $yesterdayResult = \App\Models\RankResult::where('rank_keyword_id', $keyword->id)
                ->whereDate('fetched_at', $date)
                ->first();

            if (!$yesterdayResult || $yesterdayResult->position === null) {
                continue; // No data for yesterday
            }

            // Get baseline average position
            $baselineResults = \App\Models\RankResult::where('rank_keyword_id', $keyword->id)
                ->whereBetween('fetched_at', [$baselineStart, $baselineEnd])
                ->whereNotNull('position')
                ->get();

            if ($baselineResults->isEmpty()) {
                continue; // No baseline data
            }

            $baselineAvg = $baselineResults->avg('position');
            $currentPosition = $yesterdayResult->position;

            // Skip if keyword was already outside top 100 in baseline
            if ($baselineAvg > $minPosition) {
                continue;
            }

            // Calculate position change
            $positionChange = $currentPosition - $baselineAvg;

            // Check if it's a significant drop
            if ($positionChange >= $dropThreshold) {
                // Check if it fell out of top 100 (critical)
                if ($currentPosition > $minPosition && $baselineAvg <= $minPosition) {
                    $criticalDrops[] = [
                        'keyword' => $keyword->keyword,
                        'baseline_avg' => round($baselineAvg, 1),
                        'current' => $currentPosition,
                        'drop' => round($positionChange, 1),
                        'url' => $yesterdayResult->found_url,
                    ];
                } else {
                    $affectedKeywords[] = [
                        'keyword' => $keyword->keyword,
                        'baseline_avg' => round($baselineAvg, 1),
                        'current' => $currentPosition,
                        'drop' => round($positionChange, 1),
                        'url' => $yesterdayResult->found_url,
                    ];
                }
            }
        }

        if (empty($affectedKeywords) && empty($criticalDrops)) {
            return null;
        }

        // Determine severity
        $severity = !empty($criticalDrops) ? 'critical' : 'warning';
        $allAffected = array_merge($criticalDrops, $affectedKeywords);

        // Build message
        $topAffected = array_slice($allAffected, 0, 5);
        $keywordList = implode(', ', array_column($topAffected, 'keyword'));
        $totalCount = count($allAffected);

        $message = "{$totalCount} keyword(s) dropped significantly: {$keywordList}";
        if ($totalCount > 5) {
            $message .= " and " . ($totalCount - 5) . " more";
        }

        return [
            'rule_id' => $rule->id,
            'severity' => $severity,
            'title' => 'Rank Drop Detected',
            'message' => $message,
            'diff' => [
                'affected_count' => $totalCount,
                'critical_count' => count($criticalDrops),
                'top_affected' => $topAffected,
                'baseline_window_days' => $lookbackDays,
            ],
            'related_date' => $date,
        ];
    }

    /**
     * Check conversion drop
     */
    protected function checkConversionDrop(SeoAlertRule $rule, Organization $organization, string $date, int $lookbackDays, float $threshold): ?array
    {
        $yesterday = \Carbon\Carbon::parse($date);
        $baselineStart = $yesterday->copy()->subDays($lookbackDays + 1);
        $baselineEnd = $yesterday->copy()->subDay();

        $yesterdayMetric = \App\Models\Ga4DailyMetric::where('organization_id', $organization->id)
            ->where('date', $date)
            ->sum('conversions');

        $baselineMetrics = \App\Models\Ga4DailyMetric::where('organization_id', $organization->id)
            ->whereBetween('date', [$baselineStart->toDateString(), $baselineEnd->toDateString()])
            ->get();

        if ($baselineMetrics->isEmpty()) {
            return null;
        }

        $baselineAvg = $baselineMetrics->sum('conversions') / $baselineMetrics->count();
        
        if ($baselineAvg > 0) {
            $dropPercent = (($baselineAvg - $yesterdayMetric) / $baselineAvg) * 100;

            if ($dropPercent >= $threshold) {
                return [
                    'rule_id' => $rule->id,
                    'severity' => 'critical',
                    'title' => 'Conversion Drop Detected',
                    'message' => "Conversions dropped by " . round($dropPercent, 1) . "% ({$baselineAvg} → {$yesterdayMetric})",
                    'diff' => [
                        'baseline_avg' => round($baselineAvg, 2),
                        'yesterday' => $yesterdayMetric,
                        'drop_percent' => round($dropPercent, 1),
                    ],
                    'related_date' => $date,
                ];
            }
        }

        return null;
    }
}
