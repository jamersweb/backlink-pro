<?php

namespace App\Services\Insights;

use App\Models\Domain;
use App\Models\DomainTask;
use App\Models\DomainAlert;
use App\Models\DomainKpiSnapshot;
use App\Models\DomainAudit;
use App\Models\DomainAuditIssue;
use App\Models\GscDailyMetric;
use App\Models\GscTopPage;
use App\Models\GscTopQuery;
use App\Models\Ga4DailyMetric;
use App\Models\DomainBacklinkRun;
use App\Models\DomainBacklinkDelta;
use App\Models\DomainMetaChange;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DomainInsightsEngine
{
    protected $domain;
    protected $periodDays;
    protected $summary = [];
    protected $tasks = [];
    protected $alerts = [];

    public function __construct(Domain $domain, int $periodDays = 28)
    {
        $this->domain = $domain;
        $this->periodDays = $periodDays;
    }

    /**
     * Generate insights
     */
    public function generate(): array
    {
        // Analyze each data source
        $this->analyzeAudit();
        $this->analyzeGsc();
        $this->analyzeGa4();
        $this->analyzeBacklinks();
        $this->analyzeMeta();
        $this->analyzeSnippet();

        // Create tasks and alerts
        $this->createTasks();
        $this->createAlerts();

        // Build summary
        $this->buildSummary();

        // Create KPI snapshot
        $this->createKpiSnapshot();

        return [
            'summary' => $this->summary,
            'tasks_created' => count($this->tasks),
            'alerts_created' => count($this->alerts),
        ];
    }

    /**
     * Analyze Website Analyzer data
     */
    protected function analyzeAudit(): void
    {
        $latestAudit = DomainAudit::where('domain_id', $this->domain->id)
            ->where('status', DomainAudit::STATUS_COMPLETED)
            ->latest()
            ->first();

        if (!$latestAudit) {
            return;
        }

        $summary = $latestAudit->summary_json ?? [];
        $criticalIssues = $summary['issues_critical'] ?? 0;
        $warningIssues = $summary['issues_warning'] ?? 0;
        $healthScore = $latestAudit->health_score ?? 0;

        $this->summary['health_score'] = $healthScore;
        $this->summary['critical_issues'] = $criticalIssues;
        $this->summary['warning_issues'] = $warningIssues;

        // Critical issues alert
        if ($criticalIssues > 0) {
            $this->alerts[] = [
                'type' => 'audit_critical',
                'severity' => DomainAlert::SEVERITY_CRITICAL,
                'title' => "{$criticalIssues} Critical SEO Issues Found",
                'message' => "Your latest audit found {$criticalIssues} critical issues that need immediate attention.",
                'related_url' => "/domains/{$this->domain->id}/audits/{$latestAudit->id}",
                'related_entity' => [
                    'audit_id' => $latestAudit->id,
                    'issue_type' => 'critical',
                ],
            ];

            $this->tasks[] = [
                'source' => DomainTask::SOURCE_ANALYZER,
                'title' => 'Fix Critical Technical SEO Issues',
                'description' => "Address {$criticalIssues} critical issues found in your latest audit.",
                'priority' => DomainTask::PRIORITY_P1,
                'impact_score' => 85,
                'effort' => DomainTask::EFFORT_MEDIUM,
                'related_url' => "/domains/{$this->domain->id}/audits/{$latestAudit->id}",
                'related_entity' => [
                    'type' => 'audit_critical',
                    'audit_id' => $latestAudit->id,
                ],
            ];
        }

        // Health score task
        if ($healthScore < 70) {
            $priority = $criticalIssues > 5 ? DomainTask::PRIORITY_P1 : DomainTask::PRIORITY_P2;
            $this->tasks[] = [
                'source' => DomainTask::SOURCE_ANALYZER,
                'title' => 'Improve SEO Health Score',
                'description' => "Your SEO health score is {$healthScore}/100. Focus on fixing critical and warning issues.",
                'priority' => $priority,
                'impact_score' => max(60, 100 - $healthScore),
                'effort' => DomainTask::EFFORT_HIGH,
                'related_url' => "/domains/{$this->domain->id}/audits/{$latestAudit->id}",
                'related_entity' => [
                    'type' => 'health_score',
                    'audit_id' => $latestAudit->id,
                ],
            ];
        }

        // CWV poor pages
        $poorCwvPages = $latestAudit->metrics()
            ->where('performance_score', '<', 50)
            ->limit(5)
            ->get();

        if ($poorCwvPages->count() > 0) {
            $worstPage = $poorCwvPages->sortBy('performance_score')->first();
            $this->tasks[] = [
                'source' => DomainTask::SOURCE_ANALYZER,
                'title' => 'Improve Core Web Vitals for Top Pages',
                'description' => "Several pages have poor Core Web Vitals scores. Start with: {$worstPage->url}",
                'priority' => DomainTask::PRIORITY_P2,
                'impact_score' => 65,
                'effort' => DomainTask::EFFORT_HIGH,
                'related_url' => $worstPage->url,
                'related_entity' => [
                    'type' => 'cwv_poor',
                    'audit_id' => $latestAudit->id,
                    'page_url' => $worstPage->url,
                ],
            ];
        }
    }

    /**
     * Analyze Google Search Console data
     */
    protected function analyzeGsc(): void
    {
        $endDate = Carbon::today();
        $startDate = $endDate->copy()->subDays($this->periodDays);
        $prevStartDate = $startDate->copy()->subDays($this->periodDays);
        $prevEndDate = $startDate->copy();

        // Get current period metrics
        $currentClicks = GscDailyMetric::where('domain_id', $this->domain->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('clicks');

        $currentImpressions = GscDailyMetric::where('domain_id', $this->domain->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('impressions');

        // Get previous period metrics
        $prevClicks = GscDailyMetric::where('domain_id', $this->domain->id)
            ->whereBetween('date', [$prevStartDate, $prevEndDate])
            ->sum('clicks');

        $this->summary['gsc_clicks_28d'] = $currentClicks;
        $this->summary['gsc_impressions_28d'] = $currentImpressions;

        // Click drop alert
        if ($prevClicks > 0 && $currentClicks > 0) {
            $dropPercent = (($prevClicks - $currentClicks) / $prevClicks) * 100;
            
            if ($dropPercent >= 20) {
                $severity = $dropPercent >= 40 ? DomainAlert::SEVERITY_CRITICAL : DomainAlert::SEVERITY_WARNING;
                $this->alerts[] = [
                    'type' => 'gsc_drop',
                    'severity' => $severity,
                    'title' => 'GSC Clicks Dropped ' . round($dropPercent) . '%',
                    'message' => "Clicks decreased from {$prevClicks} to {$currentClicks} in the last {$this->periodDays} days.",
                    'related_url' => "/domains/{$this->domain->id}/integrations/google",
                    'related_entity' => [
                        'type' => 'gsc_drop',
                        'current_clicks' => $currentClicks,
                        'previous_clicks' => $prevClicks,
                    ],
                ];

                $this->tasks[] = [
                    'source' => DomainTask::SOURCE_GSC,
                    'title' => 'Investigate GSC Clicks Drop',
                    'description' => "Clicks dropped by " . round($dropPercent) . "%. Review top pages and queries.",
                    'priority' => $dropPercent >= 40 ? DomainTask::PRIORITY_P1 : DomainTask::PRIORITY_P2,
                    'impact_score' => min(80, 50 + ($dropPercent / 2)),
                    'effort' => DomainTask::EFFORT_MEDIUM,
                    'related_url' => "/domains/{$this->domain->id}/integrations/google",
                    'related_entity' => [
                        'type' => 'gsc_drop',
                    ],
                ];
            }
        }

        // CTR opportunity
        $lowCtrQueries = GscTopQuery::where('domain_id', $this->domain->id)
            ->where('date', '>=', $startDate)
            ->where('impressions', '>', 100)
            ->where('ctr', '<', 0.02)
            ->orderBy('impressions', 'desc')
            ->limit(5)
            ->get();

        if ($lowCtrQueries->count() > 0) {
            $queries = $lowCtrQueries->pluck('query')->toArray();
            $this->tasks[] = [
                'source' => DomainTask::SOURCE_GSC,
                'title' => 'Improve CTR for High-Impression Queries',
                'description' => "Optimize titles and meta descriptions for: " . implode(', ', array_slice($queries, 0, 3)),
                'priority' => DomainTask::PRIORITY_P2,
                'impact_score' => 55,
                'effort' => DomainTask::EFFORT_LOW,
                'related_url' => "/domains/{$this->domain->id}/integrations/google",
                'related_entity' => [
                    'type' => 'ctr_opportunity',
                    'queries' => $queries,
                ],
            ];
        }
    }

    /**
     * Analyze Google Analytics 4 data
     */
    protected function analyzeGa4(): void
    {
        $endDate = Carbon::today();
        $startDate = $endDate->copy()->subDays($this->periodDays);
        $prevStartDate = $startDate->copy()->subDays($this->periodDays);
        $prevEndDate = $startDate->copy();

        $currentSessions = Ga4DailyMetric::where('domain_id', $this->domain->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('sessions');

        $prevSessions = Ga4DailyMetric::where('domain_id', $this->domain->id)
            ->whereBetween('date', [$prevStartDate, $prevEndDate])
            ->sum('sessions');

        $this->summary['ga_sessions_28d'] = $currentSessions;

        // Sessions drop alert
        if ($prevSessions > 0 && $currentSessions > 0) {
            $dropPercent = (($prevSessions - $currentSessions) / $prevSessions) * 100;
            
            if ($dropPercent >= 20) {
                $this->alerts[] = [
                    'type' => 'ga_drop',
                    'severity' => DomainAlert::SEVERITY_WARNING,
                    'title' => 'GA4 Sessions Dropped ' . round($dropPercent) . '%',
                    'message' => "Sessions decreased from {$prevSessions} to {$currentSessions} in the last {$this->periodDays} days.",
                    'related_url' => "/domains/{$this->domain->id}/integrations/google",
                    'related_entity' => [
                        'type' => 'ga_drop',
                        'current_sessions' => $currentSessions,
                        'previous_sessions' => $prevSessions,
                    ],
                ];
            }
        }
    }

    /**
     * Analyze Backlinks data
     */
    protected function analyzeBacklinks(): void
    {
        $latestRun = DomainBacklinkRun::where('domain_id', $this->domain->id)
            ->where('status', DomainBacklinkRun::STATUS_COMPLETED)
            ->latest()
            ->first();

        if (!$latestRun) {
            return;
        }

        $delta = $latestRun->delta;
        if (!$delta) {
            return;
        }

        $newLinks = $delta->new_links ?? 0;
        $lostLinks = $delta->lost_links ?? 0;

        $this->summary['backlinks_new'] = $newLinks;
        $this->summary['backlinks_lost'] = $lostLinks;

        // Lost links spike alert
        if ($lostLinks > 0) {
            $previousRun = DomainBacklinkRun::where('domain_id', $this->domain->id)
                ->where('id', '<', $latestRun->id)
                ->where('status', DomainBacklinkRun::STATUS_COMPLETED)
                ->latest()
                ->first();

            $prevLostLinks = $previousRun?->delta?->lost_links ?? 0;
            $threshold = max(20, $prevLostLinks * 2);

            if ($lostLinks >= $threshold) {
                $this->alerts[] = [
                    'type' => 'backlinks_lost_spike',
                    'severity' => DomainAlert::SEVERITY_WARNING,
                    'title' => "Lost {$lostLinks} Backlinks",
                    'message' => "Your latest backlink check shows {$lostLinks} lost backlinks. This is a significant increase.",
                    'related_url' => "/domains/{$this->domain->id}/backlinks/{$latestRun->id}",
                    'related_entity' => [
                        'type' => 'backlinks_lost',
                        'run_id' => $latestRun->id,
                    ],
                ];

                $this->tasks[] = [
                    'source' => DomainTask::SOURCE_BACKLINKS,
                    'title' => 'Investigate Lost Backlinks',
                    'description' => "{$lostLinks} backlinks were lost. Review the backlinks report to identify patterns.",
                    'priority' => DomainTask::PRIORITY_P2,
                    'impact_score' => 65,
                    'effort' => DomainTask::EFFORT_MEDIUM,
                    'related_url' => "/domains/{$this->domain->id}/backlinks/{$latestRun->id}",
                    'related_entity' => [
                        'type' => 'backlinks_lost',
                        'run_id' => $latestRun->id,
                    ],
                ];
            }
        }
    }

    /**
     * Analyze Meta Editor data
     */
    protected function analyzeMeta(): void
    {
        $last7Days = Carbon::now()->subDays(7);

        $failedChanges = DomainMetaChange::where('domain_id', $this->domain->id)
            ->where('status', DomainMetaChange::STATUS_FAILED)
            ->where('created_at', '>=', $last7Days)
            ->count();

        $oldDrafts = DomainMetaChange::where('domain_id', $this->domain->id)
            ->where('status', DomainMetaChange::STATUS_DRAFT)
            ->where('created_at', '<', $last7Days)
            ->count();

        $this->summary['meta_failed'] = $failedChanges;

        // Failed meta alert
        if ($failedChanges > 0) {
            $this->alerts[] = [
                'type' => 'meta_failed',
                'severity' => DomainAlert::SEVERITY_WARNING,
                'title' => "{$failedChanges} Failed Meta Publishes",
                'message' => "{$failedChanges} meta tag updates failed to publish in the last 7 days.",
                'related_url' => "/domains/{$this->domain->id}/meta",
                'related_entity' => [
                    'type' => 'meta_failed',
                ],
            ];

            $this->tasks[] = [
                'source' => DomainTask::SOURCE_META,
                'title' => 'Fix Failed Meta Publishes',
                'description' => "{$failedChanges} meta tag updates failed. Check connector connection and retry.",
                'priority' => DomainTask::PRIORITY_P1,
                'impact_score' => 75,
                'effort' => DomainTask::EFFORT_LOW,
                'related_url' => "/domains/{$this->domain->id}/meta",
                'related_entity' => [
                    'type' => 'meta_failed',
                ],
            ];
        }

        // Old drafts task
        if ($oldDrafts > 0) {
            $this->tasks[] = [
                'source' => DomainTask::SOURCE_META,
                'title' => 'Publish Pending Meta Drafts',
                'description' => "{$oldDrafts} meta tag drafts are older than 7 days. Review and publish them.",
                'priority' => DomainTask::PRIORITY_P3,
                'impact_score' => 30,
                'effort' => DomainTask::EFFORT_LOW,
                'related_url' => "/domains/{$this->domain->id}/meta",
                'related_entity' => [
                    'type' => 'meta_drafts_old',
                ],
            ];
        }
    }

    /**
     * Create tasks with de-duplication
     */
    protected function createTasks(): void
    {
        foreach ($this->tasks as $taskData) {
            $signature = hash('sha1', ($taskData['related_entity']['type'] ?? $taskData['source']) . '|' . ($taskData['related_url'] ?? '') . '|' . $this->domain->id);

            // Check for duplicate open tasks in last 7 days
            $type = $taskData['related_entity']['type'] ?? $taskData['source'];
            $existing = DomainTask::where('domain_id', $this->domain->id)
                ->where('status', DomainTask::STATUS_OPEN)
                ->where('created_at', '>=', Carbon::now()->subDays(7))
                ->where(function($q) use ($type, $taskData) {
                    $q->whereJsonContains('related_entity->type', $type)
                      ->orWhere('source', $taskData['source']);
                })
                ->where('related_url', $taskData['related_url'] ?? null)
                ->first();

            if ($existing) {
                continue; // Skip duplicate
            }

            DomainTask::create([
                'domain_id' => $this->domain->id,
                'user_id' => $this->domain->user_id,
                'source' => $taskData['source'],
                'title' => $taskData['title'],
                'description' => $taskData['description'] ?? null,
                'priority' => $taskData['priority'],
                'impact_score' => $taskData['impact_score'],
                'effort' => $taskData['effort'],
                'status' => DomainTask::STATUS_OPEN,
                'related_url' => $taskData['related_url'] ?? null,
                'related_entity' => $taskData['related_entity'],
                'created_by' => DomainTask::CREATED_BY_SYSTEM,
            ]);
        }
    }

    /**
     * Create alerts
     */
    protected function createAlerts(): void
    {
        foreach ($this->alerts as $alertData) {
            DomainAlert::create([
                'domain_id' => $this->domain->id,
                'user_id' => $this->domain->user_id,
                'type' => $alertData['type'],
                'severity' => $alertData['severity'],
                'title' => $alertData['title'],
                'message' => $alertData['message'] ?? null,
                'related_url' => $alertData['related_url'] ?? null,
                'related_entity' => $alertData['related_entity'] ?? null,
                'is_read' => false,
            ]);
        }
    }

    /**
     * Build summary JSON
     */
    protected function buildSummary(): void
    {
        $this->summary = array_merge([
            'health_score' => 0,
            'critical_issues' => 0,
            'warning_issues' => 0,
            'gsc_clicks_28d' => 0,
            'gsc_impressions_28d' => 0,
            'ga_sessions_28d' => 0,
            'backlinks_new' => 0,
            'backlinks_lost' => 0,
            'meta_failed' => 0,
        ], $this->summary);
    }

    /**
     * Create KPI snapshot
     */
    protected function createKpiSnapshot(): void
    {
        DomainKpiSnapshot::updateOrCreate(
            [
                'domain_id' => $this->domain->id,
                'date' => Carbon::today(),
            ],
            [
                'seo_health_score' => $this->summary['health_score'] ?? null,
                'gsc_clicks_28d' => $this->summary['gsc_clicks_28d'] ?? null,
                'gsc_impressions_28d' => $this->summary['gsc_impressions_28d'] ?? null,
                'ga_sessions_28d' => $this->summary['ga_sessions_28d'] ?? null,
                'backlinks_new' => $this->summary['backlinks_new'] ?? null,
                'backlinks_lost' => $this->summary['backlinks_lost'] ?? null,
                'meta_failed_count' => $this->summary['meta_failed'] ?? null,
            ]
        );
    }
}

