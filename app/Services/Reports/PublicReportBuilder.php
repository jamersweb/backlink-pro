<?php

namespace App\Services\Reports;

use App\Models\Domain;
use App\Models\PublicReport;
use App\Models\DomainAudit;
use App\Models\DomainAuditIssue;
use App\Models\GscDailyMetric;
use App\Models\Ga4DailyMetric;
use App\Models\DomainBacklinkRun;
use App\Models\DomainBacklinkDelta;
use App\Models\DomainRefDomain;
use App\Models\DomainMetaChange;
use App\Models\DomainTask;
use App\Models\DomainAlert;
use App\Models\SnippetEvent;
use App\Models\ContentBrief;
use App\Models\KeywordOpportunity;
use Carbon\Carbon;

class PublicReportBuilder
{
    protected $domain;
    protected $report;
    protected $sections;

    public function __construct(Domain $domain, PublicReport $report)
    {
        $this->domain = $domain;
        $this->report = $report;
        $this->sections = $report->settings_json['sections'] ?? [];
    }

    /**
     * Build snapshot
     */
    public function build(): array
    {
        $snapshot = [
            'generated_at' => now()->toIso8601String(),
            'domain' => [
                'name' => $this->domain->name,
                'host' => $this->domain->host,
                'url' => $this->domain->url,
            ],
        ];

        if ($this->sections['analyzer'] ?? false) {
            $snapshot['analyzer'] = $this->buildAnalyzerSection();
        }

        if ($this->sections['google'] ?? false) {
            $snapshot['google'] = $this->buildGoogleSection();
        }

        if ($this->sections['backlinks'] ?? false) {
            $snapshot['backlinks'] = $this->buildBacklinksSection();
        }

        if ($this->sections['meta'] ?? false) {
            $snapshot['meta'] = $this->buildMetaSection();
        }

        if ($this->sections['insights'] ?? false) {
            $snapshot['insights'] = $this->buildInsightsSection();
        }

        // Add snippet data as fallback if Google data missing
        if (($this->sections['google'] ?? false) && empty($snapshot['google']['gsc']['clicks']) && empty($snapshot['google']['ga4']['sessions'])) {
            $snapshot['snippet_tracking'] = $this->buildSnippetTrackingSection();
        }

        if ($this->sections['content'] ?? false) {
            $snapshot['content'] = $this->buildContentSection();
        }

        return $snapshot;
    }

    /**
     * Build analyzer section
     */
    protected function buildAnalyzerSection(): array
    {
        $latestAudit = DomainAudit::where('domain_id', $this->domain->id)
            ->where('status', DomainAudit::STATUS_COMPLETED)
            ->latest()
            ->first();

        if (!$latestAudit) {
            return null;
        }

        $summary = $latestAudit->summary_json ?? [];
        $topIssues = DomainAuditIssue::where('domain_audit_id', $latestAudit->id)
            ->where('severity', 'critical')
            ->with('page')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($issue) {
                return [
                    'type' => $issue->type,
                    'message' => $issue->message,
                    'url' => $issue->page?->url ?? null,
                ];
            });

        return [
            'health_score' => $latestAudit->health_score ?? 0,
            'pages_crawled' => $summary['pages_crawled'] ?? 0,
            'issues' => [
                'critical' => $summary['issues_critical'] ?? 0,
                'warning' => $summary['issues_warning'] ?? 0,
                'info' => $summary['issues_info'] ?? 0,
            ],
            'top_critical_issues' => $topIssues,
            'audit_date' => $latestAudit->finished_at?->toIso8601String(),
        ];
    }

    /**
     * Build Google section
     */
    protected function buildGoogleSection(): array
    {
        $endDate = Carbon::today();
        $startDate = $endDate->copy()->subDays(28);

        // GSC metrics
        $gscMetrics = GscDailyMetric::where('domain_id', $this->domain->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $gscData = [
            'clicks' => $gscMetrics->sum('clicks'),
            'impressions' => $gscMetrics->sum('impressions'),
            'ctr' => $gscMetrics->avg('ctr') ?? 0,
            'position' => $gscMetrics->avg('position') ?? 0,
            'trend' => $gscMetrics->map(function ($metric) {
                return [
                    'date' => $metric->date->toDateString(),
                    'clicks' => $metric->clicks,
                    'impressions' => $metric->impressions,
                ];
            })->values(),
        ];

        // GA4 metrics
        $ga4Metrics = Ga4DailyMetric::where('domain_id', $this->domain->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $ga4Data = [
            'sessions' => $ga4Metrics->sum('sessions'),
            'users' => $ga4Metrics->sum('total_users'),
            'engagement_rate' => $ga4Metrics->avg('engagement_rate') ?? 0,
            'trend' => $ga4Metrics->map(function ($metric) {
                return [
                    'date' => $metric->date->toDateString(),
                    'sessions' => $metric->sessions,
                ];
            })->values(),
        ];

        return [
            'gsc' => $gscData,
            'ga4' => $ga4Data,
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
        ];
    }

    /**
     * Build backlinks section
     */
    protected function buildBacklinksSection(): array
    {
        $latestRun = DomainBacklinkRun::where('domain_id', $this->domain->id)
            ->where('status', DomainBacklinkRun::STATUS_COMPLETED)
            ->latest()
            ->first();

        if (!$latestRun) {
            return null;
        }

        $summary = $latestRun->summary_json ?? [];
        $delta = $latestRun->delta;

        $topRefDomains = DomainRefDomain::where('run_id', $latestRun->id)
            ->orderBy('backlinks_count', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($ref) {
                return [
                    'domain' => $ref->domain,
                    'backlinks_count' => $ref->backlinks_count,
                ];
            });

        return [
            'total_backlinks' => $summary['total_backlinks'] ?? 0,
            'ref_domains' => $summary['ref_domains'] ?? 0,
            'follow' => $summary['follow'] ?? 0,
            'nofollow' => $summary['nofollow'] ?? 0,
            'delta' => [
                'new_links' => $delta->new_links ?? 0,
                'lost_links' => $delta->lost_links ?? 0,
                'new_ref_domains' => $delta->new_ref_domains ?? 0,
                'lost_ref_domains' => $delta->lost_ref_domains ?? 0,
            ],
            'top_ref_domains' => $topRefDomains,
            'run_date' => $latestRun->finished_at?->toIso8601String(),
        ];
    }

    /**
     * Build meta section
     */
    protected function buildMetaSection(): array
    {
        $drafts = DomainMetaChange::where('domain_id', $this->domain->id)
            ->where('status', DomainMetaChange::STATUS_DRAFT)
            ->count();

        $failed = DomainMetaChange::where('domain_id', $this->domain->id)
            ->where('status', DomainMetaChange::STATUS_FAILED)
            ->count();

        $lastPublished = DomainMetaChange::where('domain_id', $this->domain->id)
            ->where('status', DomainMetaChange::STATUS_PUBLISHED)
            ->latest('updated_at')
            ->first();

        return [
            'drafts_count' => $drafts,
            'failed_count' => $failed,
            'last_published_at' => $lastPublished?->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Build insights section
     */
    protected function buildInsightsSection(): array
    {
        $topTasks = DomainTask::where('domain_id', $this->domain->id)
            ->where('status', DomainTask::STATUS_OPEN)
            ->whereIn('priority', [DomainTask::PRIORITY_P1, DomainTask::PRIORITY_P2])
            ->orderByRaw("FIELD(priority, 'p1', 'p2')")
            ->orderBy('impact_score', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($task) {
                return [
                    'title' => $task->title,
                    'priority' => $task->priority,
                    'impact_score' => $task->impact_score,
                ];
            });

        $unreadAlerts = DomainAlert::where('domain_id', $this->domain->id)
            ->where('is_read', false)
            ->count();

        return [
            'top_tasks' => $topTasks,
            'unread_alerts' => $unreadAlerts,
        ];
    }

    /**
     * Build snippet tracking section (fallback when Google data missing)
     */
    protected function buildSnippetTrackingSection(): ?array
    {
        $endDate = Carbon::today();
        $startDate = $endDate->copy()->subDays(28);

        $events = SnippetEvent::where('domain_id', $this->domain->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        if ($events->isEmpty()) {
            return null;
        }

        $totalViews = $events->sum('views');
        $totalUniques = $events->sum('uniques');

        $topPages = SnippetEvent::where('domain_id', $this->domain->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->select('path', \DB::raw('SUM(views) as views'))
            ->groupBy('path')
            ->orderByDesc('views')
            ->limit(10)
            ->get()
            ->map(function($event) {
                return [
                    'path' => $event->path,
                    'views' => $event->views,
                ];
            });

        return [
            'total_views' => $totalViews,
            'total_uniques' => $totalUniques,
            'top_pages' => $topPages,
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'source' => 'snippet_tracking',
        ];
    }

    /**
     * Build content plan section
     */
    protected function buildContentSection(): array
    {
        $briefs = ContentBrief::where('domain_id', $this->domain->id)->get();
        
        $statusCounts = [
            'draft' => $briefs->where('status', ContentBrief::STATUS_DRAFT)->count(),
            'writing' => $briefs->where('status', ContentBrief::STATUS_WRITING)->count(),
            'published' => $briefs->where('status', ContentBrief::STATUS_PUBLISHED)->count(),
        ];

        $topOpportunities = KeywordOpportunity::where('domain_id', $this->domain->id)
            ->where('status', KeywordOpportunity::STATUS_NEW)
            ->orderByDesc('opportunity_score')
            ->limit(5)
            ->get()
            ->map(function($opp) {
                return [
                    'keyword' => $opp->query,
                    'score' => $opp->opportunity_score,
                    'position' => $opp->position,
                ];
            });

        return [
            'briefs' => [
                'draft' => $statusCounts['draft'],
                'writing' => $statusCounts['writing'],
                'published' => $statusCounts['published'],
                'total' => $briefs->count(),
            ],
            'top_opportunities' => $topOpportunities,
        ];
    }

    /**
     * Check if snapshot is fresh (within 60 minutes)
     */
    public function isSnapshotFresh(): bool
    {
        if (!$this->report->snapshot_generated_at) {
            return false;
        }

        return $this->report->snapshot_generated_at->isAfter(
            Carbon::now()->subMinutes(60)
        );
    }
}

