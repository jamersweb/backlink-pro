<?php

namespace App\Services\Planner;

use App\Models\Domain;
use App\Models\DomainAudit;
use App\Models\DomainAuditIssue;
use App\Models\DomainAuditMetric;
use App\Models\GscDailyMetric;
use App\Models\GscTopQuery;
use App\Models\Ga4DailyMetric;
use App\Models\DomainBacklinkRun;
use App\Models\DomainBacklinkDelta;
use App\Models\DomainMetaChange;
use App\Models\DomainTask;
use Carbon\Carbon;

class DomainActionPlanner
{
    protected $domain;
    protected $periodDays;
    protected $narrativeProvider;

    public function __construct(Domain $domain, int $periodDays = 28)
    {
        $this->domain = $domain;
        $this->periodDays = $periodDays;
        $this->narrativeProvider = new HeuristicNarrativeProvider();
    }

    /**
     * Generate plan
     */
    public function generatePlan(): array
    {
        $items = [];

        // Pull signals from all sources
        $items = array_merge($items, $this->analyzeAuditSignals());
        $items = array_merge($items, $this->analyzeGscSignals());
        $items = array_merge($items, $this->analyzeGa4Signals());
        $items = array_merge($items, $this->analyzeBacklinkSignals());
        $items = array_merge($items, $this->analyzeMetaSignals());

        // Prioritize and bucket
        $prioritized = $this->prioritizeAndBucket($items);

        // Generate checklists and narratives
        foreach ($prioritized as &$item) {
            $item['why'] = $this->narrativeProvider->explainWhy($item);
            $item['checklist'] = $this->narrativeProvider->generateChecklist($item);
        }

        return $prioritized;
    }

    /**
     * Analyze audit signals
     */
    protected function analyzeAuditSignals(): array
    {
        $items = [];
        $latestAudit = DomainAudit::where('domain_id', $this->domain->id)
            ->where('status', DomainAudit::STATUS_COMPLETED)
            ->latest()
            ->first();

        if (!$latestAudit) {
            return $items;
        }

        $summary = $latestAudit->summary_json ?? [];
        $criticalCount = $summary['issues_critical'] ?? 0;
        $healthScore = $latestAudit->health_score ?? 100;

        // Critical SEO issues
        if ($criticalCount > 0) {
            $items[] = [
                'type' => 'fix_critical_seo',
                'priority_score' => min(95, 70 + ($criticalCount * 2)),
                'effort' => 'medium',
                'evidence' => [
                    'critical_issues' => $criticalCount,
                    'health_score' => $healthScore,
                    'audit_id' => $latestAudit->id,
                ],
                'related_url' => url("/domains/{$this->domain->id}/audits/{$latestAudit->id}"),
                'links' => [
                    ['label' => 'View Audit', 'url' => url("/domains/{$this->domain->id}/audits/{$latestAudit->id}")],
                ],
                'domain_id' => $this->domain->id,
            ];
        }

        // Core Web Vitals
        $worstCwv = DomainAuditMetric::where('domain_audit_id', $latestAudit->id)
            ->whereNotNull('performance_score')
            ->orderBy('performance_score', 'asc')
            ->first();

        if ($worstCwv && $worstCwv->performance_score < 70) {
            $items[] = [
                'type' => 'improve_cwv',
                'priority_score' => 75,
                'effort' => 'high',
                'evidence' => [
                    'worst_page' => $worstCwv->url,
                    'performance_score' => $worstCwv->performance_score,
                ],
                'related_url' => $worstCwv->url,
                'links' => [
                    ['label' => 'View Audit', 'url' => url("/domains/{$this->domain->id}/audits/{$latestAudit->id}")],
                ],
                'domain_id' => $this->domain->id,
            ];
        }

        return $items;
    }

    /**
     * Analyze GSC signals
     */
    protected function analyzeGscSignals(): array
    {
        $items = [];
        $endDate = Carbon::today();
        $startDate = $endDate->copy()->subDays($this->periodDays);
        $prevStartDate = $startDate->copy()->subDays($this->periodDays);

        $current = GscDailyMetric::where('domain_id', $this->domain->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $previous = GscDailyMetric::where('domain_id', $this->domain->id)
            ->whereBetween('date', [$prevStartDate, $startDate->copy()->subDay()])
            ->get();

        if ($current->isEmpty() || $previous->isEmpty()) {
            return $items;
        }

        $currentClicks = $current->sum('clicks');
        $previousClicks = $previous->sum('clicks');
        $currentImpressions = $current->sum('impressions');
        $currentCtr = $current->avg('ctr') ?? 0;

        // Clicks drop
        if ($previousClicks > 0) {
            $dropPct = (($previousClicks - $currentClicks) / $previousClicks) * 100;
            if ($dropPct >= 20) {
                $items[] = [
                    'type' => 'gsc_clicks_drop',
                    'priority_score' => min(90, 60 + ($dropPct / 2)),
                    'effort' => 'medium',
                    'evidence' => [
                        'clicks_drop_pct' => round($dropPct, 1),
                        'current_clicks' => $currentClicks,
                        'previous_clicks' => $previousClicks,
                    ],
                    'related_url' => url("/domains/{$this->domain->id}/integrations/google"),
                    'links' => [
                        ['label' => 'GSC Dashboard', 'url' => url("/domains/{$this->domain->id}/integrations/google")],
                    ],
                    'domain_id' => $this->domain->id,
                ];
            }
        }

        // CTR optimization opportunity
        if ($currentCtr < 0.02 && $currentImpressions > 1000) {
            $lowCtrQueries = GscTopQuery::where('domain_id', $this->domain->id)
                ->where('date', '>=', $startDate)
                ->where('impressions', '>', 100)
                ->where('ctr', '<', 0.02)
                ->count();

            if ($lowCtrQueries > 0) {
                $items[] = [
                    'type' => 'ctr_optimization',
                    'priority_score' => 65,
                    'effort' => 'low',
                    'evidence' => [
                        'low_ctr_queries' => $lowCtrQueries,
                        'avg_ctr' => round($currentCtr * 100, 2),
                    ],
                    'related_url' => url("/domains/{$this->domain->id}/integrations/google"),
                    'links' => [
                        ['label' => 'GSC Dashboard', 'url' => url("/domains/{$this->domain->id}/integrations/google")],
                        ['label' => 'Meta Editor', 'url' => url("/domains/{$this->domain->id}/meta")],
                    ],
                    'domain_id' => $this->domain->id,
                ];
            }
        }

        return $items;
    }

    /**
     * Analyze GA4 signals
     */
    protected function analyzeGa4Signals(): array
    {
        $items = [];
        $endDate = Carbon::today();
        $startDate = $endDate->copy()->subDays($this->periodDays);
        $prevStartDate = $startDate->copy()->subDays($this->periodDays);

        $current = Ga4DailyMetric::where('domain_id', $this->domain->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $previous = Ga4DailyMetric::where('domain_id', $this->domain->id)
            ->whereBetween('date', [$prevStartDate, $startDate->copy()->subDay()])
            ->get();

        if ($current->isEmpty() || $previous->isEmpty()) {
            return $items;
        }

        $currentSessions = $current->sum('sessions');
        $previousSessions = $previous->sum('sessions');

        if ($previousSessions > 0) {
            $dropPct = (($previousSessions - $currentSessions) / $previousSessions) * 100;
            if ($dropPct >= 20) {
                $items[] = [
                    'type' => 'ga_sessions_drop',
                    'priority_score' => min(85, 55 + ($dropPct / 2)),
                    'effort' => 'medium',
                    'evidence' => [
                        'sessions_drop_pct' => round($dropPct, 1),
                        'current_sessions' => $currentSessions,
                        'previous_sessions' => $previousSessions,
                    ],
                    'related_url' => url("/domains/{$this->domain->id}/integrations/google"),
                    'links' => [
                        ['label' => 'GA4 Dashboard', 'url' => url("/domains/{$this->domain->id}/integrations/google")],
                    ],
                    'domain_id' => $this->domain->id,
                ];
            }
        }

        return $items;
    }

    /**
     * Analyze backlink signals
     */
    protected function analyzeBacklinkSignals(): array
    {
        $items = [];
        $latestRun = DomainBacklinkRun::where('domain_id', $this->domain->id)
            ->where('status', DomainBacklinkRun::STATUS_COMPLETED)
            ->latest()
            ->first();

        if (!$latestRun) {
            return $items;
        }

        $delta = $latestRun->delta;
        if (!$delta) {
            return $items;
        }

        $lostLinks = $delta->lost_links ?? 0;
        if ($lostLinks > 20) {
            $items[] = [
                'type' => 'lost_backlinks',
                'priority_score' => min(80, 50 + ($lostLinks / 10)),
                'effort' => 'high',
                'evidence' => [
                    'lost_links' => $lostLinks,
                    'new_links' => $delta->new_links ?? 0,
                    'run_id' => $latestRun->id,
                ],
                'related_url' => url("/domains/{$this->domain->id}/backlinks/{$latestRun->id}"),
                'links' => [
                    ['label' => 'Backlinks Report', 'url' => url("/domains/{$this->domain->id}/backlinks/{$latestRun->id}")],
                ],
                'domain_id' => $this->domain->id,
            ];
        }

        return $items;
    }

    /**
     * Analyze meta signals
     */
    protected function analyzeMetaSignals(): array
    {
        $items = [];
        $failedCount = DomainMetaChange::where('domain_id', $this->domain->id)
            ->where('status', DomainMetaChange::STATUS_FAILED)
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->count();

        if ($failedCount > 0) {
            $items[] = [
                'type' => 'meta_failed_fix',
                'priority_score' => 75,
                'effort' => 'low',
                'evidence' => [
                    'failed_count' => $failedCount,
                ],
                'related_url' => url("/domains/{$this->domain->id}/meta"),
                'links' => [
                    ['label' => 'Meta Editor', 'url' => url("/domains/{$this->domain->id}/meta")],
                ],
                'domain_id' => $this->domain->id,
            ];
        }

        return $items;
    }

    /**
     * Prioritize and bucket items
     */
    protected function prioritizeAndBucket(array $items): array
    {
        foreach ($items as &$item) {
            $score = $item['priority_score'] ?? 0;
            $effort = $item['effort'] ?? 'medium';

            // Bucket logic
            if ($score >= 80 || in_array($item['type'], ['meta_failed_fix', 'fix_critical_seo'])) {
                $item['planner_group'] = 'today';
            } elseif ($score >= 55 || ($score >= 50 && $effort === 'high')) {
                $item['planner_group'] = 'week';
            } else {
                $item['planner_group'] = 'month';
            }

            // Adjust for high effort
            if ($effort === 'high' && $item['planner_group'] === 'today' && $score < 90) {
                $item['planner_group'] = 'week';
            }
        }

        // Sort by priority score descending
        usort($items, function($a, $b) {
            return ($b['priority_score'] ?? 0) <=> ($a['priority_score'] ?? 0);
        });

        return $items;
    }

    /**
     * Apply plan to tasks
     */
    public function applyPlan(array $planItems, int $userId): array
    {
        $created = 0;
        $updated = 0;

        foreach ($planItems as $item) {
            $signature = $this->generateSignature($item);

            // Check for existing task
            $existing = DomainTask::where('domain_id', $this->domain->id)
                ->where('source_signature', $signature)
                ->whereIn('status', [DomainTask::STATUS_OPEN, DomainTask::STATUS_DOING])
                ->first();

            // Skip if dismissed recently (within 14 days)
            $dismissed = DomainTask::where('domain_id', $this->domain->id)
                ->where('source_signature', $signature)
                ->where('status', DomainTask::STATUS_DISMISSED)
                ->where('updated_at', '>=', Carbon::now()->subDays(14))
                ->exists();

            if ($dismissed) {
                continue;
            }

            $taskData = [
                'domain_id' => $this->domain->id,
                'user_id' => $userId,
                'source' => $this->mapTypeToSource($item['type']),
                'title' => $this->generateTitle($item),
                'description' => $item['why'] ?? null,
                'priority' => $this->scoreToPriority($item['priority_score'] ?? 0),
                'impact_score' => (int)($item['priority_score'] ?? 0),
                'effort' => $item['effort'] ?? 'medium',
                'status' => DomainTask::STATUS_OPEN,
                'related_url' => $item['related_url'] ?? null,
                'related_entity' => [
                    'type' => $item['type'],
                    'evidence' => $item['evidence'] ?? [],
                ],
                'created_by' => DomainTask::CREATED_BY_SYSTEM,
                'planner_group' => $item['planner_group'] ?? 'month',
                'checklist_json' => $item['checklist'] ?? [],
                'why_json' => $item['evidence'] ?? [],
                'estimated_minutes' => $this->estimateMinutes($item['effort'] ?? 'medium'),
                'source_signature' => $signature,
            ];

            if ($existing) {
                $existing->update($taskData);
                $updated++;
            } else {
                DomainTask::create($taskData);
                $created++;
            }
        }

        return ['created' => $created, 'updated' => $updated];
    }

    /**
     * Generate signature for de-duplication
     */
    protected function generateSignature(array $item): string
    {
        $type = $item['type'] ?? '';
        $url = $item['related_url'] ?? '';
        $evidenceKey = isset($item['evidence']) ? json_encode($item['evidence']) : '';
        return hash('sha1', $this->domain->id . '|' . $type . '|' . $url . '|' . $evidenceKey);
    }

    /**
     * Map type to source
     */
    protected function mapTypeToSource(string $type): string
    {
        $map = [
            'fix_critical_seo' => DomainTask::SOURCE_ANALYZER,
            'improve_cwv' => DomainTask::SOURCE_ANALYZER,
            'ctr_optimization' => DomainTask::SOURCE_GSC,
            'gsc_clicks_drop' => DomainTask::SOURCE_GSC,
            'ga_sessions_drop' => DomainTask::SOURCE_GA4,
            'lost_backlinks' => DomainTask::SOURCE_BACKLINKS,
            'meta_failed_fix' => DomainTask::SOURCE_META,
        ];
        return $map[$type] ?? DomainTask::SOURCE_INSIGHTS;
    }

    /**
     * Generate title
     */
    protected function generateTitle(array $item): string
    {
        $type = $item['type'] ?? '';
        $titles = [
            'fix_critical_seo' => 'Fix Critical SEO Issues',
            'improve_cwv' => 'Improve Core Web Vitals',
            'ctr_optimization' => 'Optimize Click-Through Rates',
            'gsc_clicks_drop' => 'Address Search Console Clicks Drop',
            'ga_sessions_drop' => 'Investigate Analytics Sessions Drop',
            'lost_backlinks' => 'Recover Lost Backlinks',
            'meta_failed_fix' => 'Fix Failed Meta Tag Publishes',
        ];
        return $titles[$type] ?? 'Improve SEO Performance';
    }

    /**
     * Convert score to priority
     */
    protected function scoreToPriority(int $score): string
    {
        if ($score >= 75) {
            return DomainTask::PRIORITY_P1;
        } elseif ($score >= 50) {
            return DomainTask::PRIORITY_P2;
        }
        return DomainTask::PRIORITY_P3;
    }

    /**
     * Estimate minutes based on effort
     */
    protected function estimateMinutes(string $effort): int
    {
        $map = [
            'low' => 30,
            'medium' => 120,
            'high' => 480,
        ];
        return $map[$effort] ?? 120;
    }
}


