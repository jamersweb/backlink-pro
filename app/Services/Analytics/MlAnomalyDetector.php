<?php

namespace App\Services\Analytics;

use App\Models\MlAnomaly;
use App\Models\Organization;
use Carbon\Carbon;

class MlAnomalyDetector
{
    /**
     * Detect anomalies for organization
     */
    public function detectAnomalies(Organization $organization, string $date): array
    {
        $anomalies = [];

        // Check GSC clicks
        $anomalies[] = $this->detectMetricAnomaly($organization, 'gsc_clicks', $date);
        
        // Check GA4 sessions
        $anomalies[] = $this->detectMetricAnomaly($organization, 'ga4_sessions', $date);
        
        // Check conversions
        $anomalies[] = $this->detectMetricAnomaly($organization, 'conversions', $date);
        
        // Check average rank
        $anomalies[] = $this->detectMetricAnomaly($organization, 'avg_rank', $date);

        return array_filter($anomalies);
    }

    /**
     * Detect anomaly for a specific metric
     */
    protected function detectMetricAnomaly(Organization $organization, string $metricKey, string $date): ?array
    {
        $historyDays = 30;
        $checkDate = Carbon::parse($date);
        $historyStart = $checkDate->copy()->subDays($historyDays);

        // Get historical values
        $values = $this->getHistoricalValues($organization, $metricKey, $historyStart, $checkDate->copy()->subDay());
        
        if (count($values) < 7) {
            return null; // Not enough data
        }

        // Get actual value
        $actualValue = $this->getActualValue($organization, $metricKey, $date);
        
        if ($actualValue === null) {
            return null;
        }

        // Calculate expected value (seasonal moving average)
        $expectedValue = $this->calculateExpectedValue($values, $checkDate);
        
        // Calculate anomaly score (robust z-score)
        $anomalyScore = $this->calculateAnomalyScore($actualValue, $expectedValue, $values);
        
        if ($anomalyScore < 0.5) {
            return null; // Not significant enough
        }

        $severity = $this->determineSeverity($anomalyScore, $actualValue, $expectedValue);

        // Store anomaly
        MlAnomaly::updateOrCreate(
            [
                'organization_id' => $organization->id,
                'metric_key' => $metricKey,
                'date' => $date,
            ],
            [
                'actual_value' => $actualValue,
                'expected_value' => $expectedValue,
                'anomaly_score' => $anomalyScore,
                'severity' => $severity,
                'explanation' => $this->generateExplanation($metricKey, $actualValue, $expectedValue, $anomalyScore),
            ]
        );

        return [
            'metric_key' => $metricKey,
            'anomaly_score' => $anomalyScore,
            'severity' => $severity,
        ];
    }

    /**
     * Get historical values
     */
    protected function getHistoricalValues(Organization $organization, string $metricKey, Carbon $start, Carbon $end): array
    {
        // Simplified - would query appropriate tables based on metric_key
        switch ($metricKey) {
            case 'gsc_clicks':
                return \App\Models\GscDailyMetric::where('organization_id', $organization->id)
                    ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                    ->pluck('clicks')
                    ->toArray();
            
            case 'ga4_sessions':
                return \App\Models\Ga4DailyMetric::where('organization_id', $organization->id)
                    ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                    ->pluck('sessions')
                    ->toArray();
            
            case 'conversions':
                return \App\Models\Ga4DailyMetric::where('organization_id', $organization->id)
                    ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                    ->pluck('conversions')
                    ->toArray();
            
            default:
                return [];
        }
    }

    /**
     * Get actual value
     */
    protected function getActualValue(Organization $organization, string $metricKey, string $date): ?float
    {
        switch ($metricKey) {
            case 'gsc_clicks':
                return \App\Models\GscDailyMetric::where('organization_id', $organization->id)
                    ->where('date', $date)
                    ->sum('clicks');
            
            case 'ga4_sessions':
                return \App\Models\Ga4DailyMetric::where('organization_id', $organization->id)
                    ->where('date', $date)
                    ->sum('sessions');
            
            case 'conversions':
                return \App\Models\Ga4DailyMetric::where('organization_id', $organization->id)
                    ->where('date', $date)
                    ->sum('conversions');
            
            default:
                return null;
        }
    }

    /**
     * Calculate expected value (seasonal moving average)
     */
    protected function calculateExpectedValue(array $values, Carbon $date): float
    {
        // Simple moving average for MVP
        // In production, would use seasonal decomposition
        return array_sum($values) / count($values);
    }

    /**
     * Calculate anomaly score (robust z-score)
     */
    protected function calculateAnomalyScore(float $actual, float $expected, array $values): float
    {
        if ($expected == 0) {
            return $actual > 0 ? 1.0 : 0.0;
        }

        // Calculate median absolute deviation (MAD)
        $deviations = array_map(fn($v) => abs($v - $expected), $values);
        $mad = $this->median($deviations);
        
        if ($mad == 0) {
            $mad = 1; // Avoid division by zero
        }

        // Robust z-score
        $zScore = abs($actual - $expected) / $mad;
        
        // Normalize to 0-1
        return min(1.0, $zScore / 3.0);
    }

    /**
     * Calculate median
     */
    protected function median(array $values): float
    {
        sort($values);
        $count = count($values);
        $middle = floor(($count - 1) / 2);
        
        if ($count % 2) {
            return $values[$middle];
        }
        
        return ($values[$middle] + $values[$middle + 1]) / 2;
    }

    /**
     * Determine severity
     */
    protected function determineSeverity(float $score, float $actual, float $expected): string
    {
        if ($score >= 0.8) {
            return 'critical';
        }
        
        if ($score >= 0.6) {
            return 'warning';
        }
        
        return 'info';
    }

    /**
     * Generate explanation
     */
    protected function generateExplanation(string $metricKey, float $actual, float $expected, float $score): array
    {
        $change = $actual - $expected;
        $changePercent = $expected > 0 ? ($change / $expected) * 100 : 0;
        
        return [
            'change' => $change,
            'change_percent' => round($changePercent, 1),
            'direction' => $change > 0 ? 'increase' : 'decrease',
            'reason' => $score >= 0.8 ? 'significant_deviation' : 'moderate_deviation',
        ];
    }
}
