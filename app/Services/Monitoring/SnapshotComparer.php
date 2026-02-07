<?php

namespace App\Services\Monitoring;

use App\Models\Audit;
use App\Models\AuditMonitor;
use App\Models\AuditSnapshot;

class SnapshotComparer
{
    /**
     * Compare snapshots and detect changes
     */
    public function compareAndAlert(AuditMonitor $monitor, Audit $newAudit): array
    {
        $newSnapshot = $this->createSnapshot($newAudit);
        
        // Get previous snapshot
        $previousAudit = $monitor->audits()
            ->where('id', '!=', $newAudit->id)
            ->orderBy('finished_at', 'desc')
            ->first();

        if (!$previousAudit) {
            // First audit for this monitor
            AuditSnapshot::create([
                'audit_id' => $newAudit->id,
                'snapshot' => $newSnapshot,
            ]);
            return [];
        }

        $previousSnapshot = AuditSnapshot::where('audit_id', $previousAudit->id)->first();
        if (!$previousSnapshot) {
            return [];
        }

        // Compare
        $alerts = $this->detectChanges($previousSnapshot->snapshot, $newSnapshot, $monitor);

        // Save new snapshot
        AuditSnapshot::create([
            'audit_id' => $newAudit->id,
            'snapshot' => $newSnapshot,
        ]);

        return $alerts;
    }

    /**
     * Create snapshot from audit
     */
    protected function createSnapshot(Audit $audit): array
    {
        return [
            'overall_score' => $audit->overall_score,
            'category_scores' => $audit->category_scores,
            'broken_links_count' => $audit->crawl_stats['broken_links_count'] ?? 0,
            'redirect_chains_count' => $audit->crawl_stats['redirect_chain_count'] ?? 0,
            'duplicate_titles_groups' => $audit->crawl_stats['duplicate_titles_groups'] ?? 0,
            'mobile_avg_score' => $audit->performance_summary['mobile_avg_score'] ?? null,
            'worst_lcp' => $audit->performance_summary['worst_lcp'] ?? null,
            'total_pages_scanned' => $audit->pages_scanned,
        ];
    }

    /**
     * Detect changes and create alerts
     */
    protected function detectChanges(array $old, array $new, AuditMonitor $monitor): array
    {
        $alerts = [];

        // Score drop
        if (isset($old['overall_score'], $new['overall_score'])) {
            $drop = $old['overall_score'] - $new['overall_score'];
            if ($drop >= 8) {
                $alerts[] = [
                    'severity' => 'critical',
                    'title' => 'Significant Score Drop Detected',
                    'message' => "Overall score dropped by {$drop} points (from {$old['overall_score']} to {$new['overall_score']})",
                    'diff' => ['overall_score' => ['old' => $old['overall_score'], 'new' => $new['overall_score']]],
                ];
            }
        }

        // Broken links increase
        if (isset($old['broken_links_count'], $new['broken_links_count'])) {
            $increase = $new['broken_links_count'] - $old['broken_links_count'];
            if ($increase >= 10) {
                $alerts[] = [
                    'severity' => 'warning',
                    'title' => 'Broken Links Increased',
                    'message' => "Broken links increased by {$increase} (from {$old['broken_links_count']} to {$new['broken_links_count']})",
                    'diff' => ['broken_links_count' => ['old' => $old['broken_links_count'], 'new' => $new['broken_links_count']]],
                ];
            }
        }

        // Performance regression
        if (isset($old['worst_lcp'], $new['worst_lcp'])) {
            $increase = $new['worst_lcp'] - $old['worst_lcp'];
            if ($increase >= 1500) { // 1.5 seconds
                $alerts[] = [
                    'severity' => 'warning',
                    'title' => 'Performance Regression Detected',
                    'message' => "Worst LCP increased by " . round($increase / 1000, 1) . "s (from " . round($old['worst_lcp'] / 1000, 1) . "s to " . round($new['worst_lcp'] / 1000, 1) . "s)",
                    'diff' => ['worst_lcp' => ['old' => $old['worst_lcp'], 'new' => $new['worst_lcp']]],
                ];
            }
        }

        return $alerts;
    }
}
