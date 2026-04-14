<?php

namespace App\Http\Controllers;

use App\Jobs\Audits\StartDomainAuditJob;
use App\Models\Domain;
use App\Models\DomainAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DomainAuditController extends Controller
{
    /**
     * List audits for a domain
     */
    public function index(Domain $domain)
    {
        Gate::authorize('analyzer.view', $domain);

        $audits = DomainAudit::where('domain_id', $domain->id)
            ->where('user_id', Auth::id())
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Domains/Audits/Index', [
            'domain' => $domain,
            'audits' => $audits,
        ]);
    }

    /**
     * Store a new audit
     */
    public function store(Request $request, Domain $domain)
    {
        // Authorize domain ownership
        if ($domain->user_id !== Auth::id()) {
            abort(403);
        }

        $activeAudit = DomainAudit::where('domain_id', $domain->id)
            ->where('user_id', Auth::id())
            ->whereIn('status', [DomainAudit::STATUS_QUEUED, DomainAudit::STATUS_RUNNING])
            ->latest('id')
            ->first();

        if ($activeAudit) {
            if ($request->input('return_to') === 'index-crawl') {
                return redirect()->route('index-crawl.index', [
                    'domain_id' => $domain->id,
                    'audit_id' => $activeAudit->id,
                ])->with('warning', 'A crawl is already in progress for this domain.');
            }

            return back()->withErrors([
                'active_audit' => 'A crawl is already in progress for this domain.',
            ]);
        }

        // Rate limiting: max 5 audits per hour per domain
        $recentAudits = DomainAudit::where('domain_id', $domain->id)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($recentAudits >= 5) {
            return back()->withErrors([
                'rate_limit' => 'You can only start 5 audits per hour per domain. Please wait before starting another audit.',
            ]);
        }

        $validated = $request->validate([
            'crawl_limit' => 'required|integer|min:1|max:1000',
            'max_depth' => 'required|integer|min:0|max:5',
            'include_sitemap' => 'boolean',
            'include_cwv' => 'boolean',
        ]);

        // Check quota limits (runs and pages per month)
        $user = Auth::user();
        try {
            $quotaService = app(\App\Services\Usage\QuotaService::class);
            $quotaService->assertCan($user, 'audits.runs_per_month', 1);
            $quotaService->assertCan($user, 'audits.pages_per_month', $validated['crawl_limit']);
        } catch (\App\Exceptions\QuotaExceededException $e) {
            return back()->withErrors([
                'quota' => $e->getMessage()
            ]);
        }

        // Create audit
        $audit = DomainAudit::create([
            'domain_id' => $domain->id,
            'user_id' => Auth::id(),
            'status' => DomainAudit::STATUS_QUEUED,
            'settings_json' => [
                'crawl_limit' => $validated['crawl_limit'],
                'max_depth' => $validated['max_depth'],
                'include_sitemap' => $validated['include_sitemap'] ?? false,
                'include_cwv' => $validated['include_cwv'] ?? false,
            ],
        ]);

        // Consume quota
        $quotaService = app(\App\Services\Usage\QuotaService::class);
        $quotaService->consume($user, 'audits.runs_per_month', 1, 'month', [
            'audit_id' => $audit->id,
            'domain_id' => $domain->id,
        ]);

        // Dispatch job
        StartDomainAuditJob::dispatch($audit->id)->onQueue('audits');

        if ($request->input('return_to') === 'index-crawl') {
            return redirect()->route('index-crawl.index', [
                'domain_id' => $domain->id,
                'audit_id' => $audit->id,
            ])->with('success', 'Crawl started successfully.');
        }

        return redirect()->route('domains.audits.show', [$domain->id, $audit->id])
            ->with('success', 'Audit started successfully. It will begin processing shortly.');
    }

    /**
     * Show audit details
     */
    public function show(Request $request, Domain $domain, $auditId)
    {
        // Resolve audit manually since route parameter doesn't match model name
        $audit = DomainAudit::where('domain_id', $domain->id)
            ->where('user_id', Auth::id())
            ->findOrFail($auditId);
        
        // Authorize ownership
        if ($domain->user_id !== Auth::id() || $audit->domain_id !== $domain->id || $audit->user_id !== Auth::id()) {
            abort(403);
        }

        // Load relationships needed for detail workspace
        $audit->load(['domain', 'metrics']);

        // Get filters from query
        $filters = [
            'severity' => $request->query('severity'),
            'type' => $request->query('type'),
            'status_code' => $request->query('status_code'),
            'issue_status_code' => $request->query('issue_status_code'),
            'indexable' => $request->query('indexable'),
            'issue_indexable' => $request->query('issue_indexable'),
            'search' => $request->query('search'),
            'missing_title' => $request->boolean('missing_title'),
            'missing_meta' => $request->boolean('missing_meta'),
            'canonical_issue' => $request->boolean('canonical_issue'),
            'noindex' => $request->boolean('noindex'),
            'redirected' => $request->boolean('redirected'),
            'status_4xx' => $request->boolean('status_4xx'),
            'status_5xx' => $request->boolean('status_5xx'),
            'has_issues' => $request->boolean('has_issues'),
            'issue_type' => $request->query('issue_type'),
            'min_issues' => $request->query('min_issues'),
            'sort' => $request->query('sort', 'updated_at'),
            'direction' => $request->query('direction', 'desc'),
            'page_id' => $request->integer('page_id'),
            'tab' => $request->query('tab', 'issues'),
        ];

        // Paginate pages with advanced filters
        $pagesQuery = $audit->pages()->withCount('issues');
        if ($filters['status_code']) {
            $pagesQuery->where('status_code', $filters['status_code']);
        }
        if ($filters['indexable'] !== null) {
            $pagesQuery->where('is_indexable', $filters['indexable'] === '1');
        }
        if ($filters['search']) {
            $pagesQuery->where(function($q) use ($filters) {
                $q->where('url', 'like', "%{$filters['search']}%")
                  ->orWhere('final_url', 'like', "%{$filters['search']}%")
                  ->orWhere('title', 'like', "%{$filters['search']}%");
            });
        }
        if ($filters['missing_title']) {
            $pagesQuery->where(function ($q) {
                $q->whereNull('title')->orWhere('title', '');
            });
        }
        if ($filters['missing_meta']) {
            $pagesQuery->where(function ($q) {
                $q->whereNull('meta_description')->orWhere('meta_description', '');
            });
        }
        if ($filters['canonical_issue']) {
            $pagesQuery->where(function ($q) {
                $q->whereNull('canonical')
                    ->orWhereColumn('canonical', '!=', 'url');
            });
        }
        if ($filters['noindex']) {
            $pagesQuery->where('robots_meta', 'like', '%noindex%');
        }
        if ($filters['redirected']) {
            $pagesQuery->whereBetween('status_code', [300, 399]);
        }
        if ($filters['status_4xx']) {
            $pagesQuery->whereBetween('status_code', [400, 499]);
        }
        if ($filters['status_5xx']) {
            $pagesQuery->where('status_code', '>=', 500);
        }
        if ($filters['has_issues']) {
            $pagesQuery->where('issues_count', '>', 0);
        }
        if ($filters['issue_type']) {
            $pagesQuery->whereHas('issues', function ($q) use ($filters) {
                $q->where('type', $filters['issue_type']);
            });
        }
        if (!empty($filters['min_issues']) && is_numeric($filters['min_issues'])) {
            $pagesQuery->where('issues_count', '>=', (int) $filters['min_issues']);
        }
        $this->applyPageSorting($pagesQuery, $filters['sort'] ?? 'updated_at', $filters['direction'] ?? 'desc');

        $pages = $pagesQuery
            ->paginate(25)
            ->withQueryString()
            ->through(function ($page) {
                $titleLength = $page->title ? mb_strlen($page->title) : 0;
                $metaLength = $page->meta_description ? mb_strlen($page->meta_description) : 0;

                return [
                    'id' => $page->id,
                    'url' => $page->url,
                    'path' => $page->path,
                    'status_code' => $page->status_code,
                    'final_url' => $page->final_url,
                    'response_time_ms' => $page->response_time_ms,
                    'content_type' => $page->content_type,
                    'title' => $page->title,
                    'title_length' => $titleLength,
                    'meta_description' => $page->meta_description,
                    'meta_description_length' => $metaLength,
                    'canonical' => $page->canonical,
                    'canonical_status' => !$page->canonical ? 'missing' : ($page->canonical === $page->url ? 'self' : 'mismatch'),
                    'robots_meta' => $page->robots_meta,
                    'is_noindex' => str_contains(strtolower((string) $page->robots_meta), 'noindex'),
                    'h1_count' => $page->h1_count,
                    'word_count' => $page->word_count,
                    'is_indexable' => (bool) $page->is_indexable,
                    'issues_count' => (int) ($page->issues_count ?? 0),
                    'created_at' => $page->created_at,
                    'updated_at' => $page->updated_at,
                ];
            });

        // Paginate issues with stronger context
        $issuesQuery = $audit->issues()->with(['page:id,domain_audit_id,url,title,status_code,is_indexable,canonical,robots_meta'])->latest();
        if ($filters['severity']) {
            $issuesQuery->where('severity', $filters['severity']);
        }
        if ($filters['type']) {
            $issuesQuery->where('type', $filters['type']);
        }
        if ($filters['issue_status_code']) {
            $issuesQuery->whereHas('page', function ($q) use ($filters) {
                $q->where('status_code', (int) $filters['issue_status_code']);
            });
        }
        if ($filters['issue_indexable'] !== null && $filters['issue_indexable'] !== '') {
            $issuesQuery->whereHas('page', function ($q) use ($filters) {
                $q->where('is_indexable', $filters['issue_indexable'] === '1');
            });
        }
        if ($filters['search']) {
            $issuesQuery->where(function ($q) use ($filters) {
                $q->where('type', 'like', "%{$filters['search']}%")
                    ->orWhere('message', 'like', "%{$filters['search']}%")
                    ->orWhereHas('page', function($pq) use ($filters) {
                        $pq->where('url', 'like', "%{$filters['search']}%")
                            ->orWhere('title', 'like', "%{$filters['search']}%");
                    });
            });
        }
        $issues = $issuesQuery
            ->orderByRaw("FIELD(severity, 'critical','warning','info')")
            ->paginate(25)
            ->withQueryString()
            ->through(function ($issue) {
                $meta = $this->issueTypeMeta($issue->type);
                return [
                    'id' => $issue->id,
                    'severity' => $issue->severity,
                    'type' => $issue->type,
                    'type_label' => $meta['label'],
                    'message' => $issue->message,
                    'explanation' => $meta['explanation'],
                    'recommendation' => $meta['recommendation'],
                    'page' => $issue->page ? [
                        'id' => $issue->page->id,
                        'url' => $issue->page->url,
                        'title' => $issue->page->title,
                        'status_code' => $issue->page->status_code,
                        'is_indexable' => (bool) $issue->page->is_indexable,
                    ] : null,
                ];
            });

        // Get unique issue types for filter
        $issueTypes = $audit->issues()
            ->distinct()
            ->pluck('type')
            ->sort()
            ->values();

        // Aggregate insights for premium summary workspace
        $pagesBase = $audit->pages();
        $issuesBase = $audit->issues();
        $totalPages = (clone $pagesBase)->count();
        $indexablePages = (clone $pagesBase)->where('is_indexable', true)->count();
        $missingTitles = (clone $pagesBase)->where(function ($q) {
            $q->whereNull('title')->orWhere('title', '');
        })->count();
        $missingMetaDescriptions = (clone $pagesBase)->where(function ($q) {
            $q->whereNull('meta_description')->orWhere('meta_description', '');
        })->count();
        $missingH1 = (clone $pagesBase)->where(function ($q) {
            $q->whereNull('h1_count')->orWhere('h1_count', 0);
        })->count();
        $noindexPages = (clone $pagesBase)->where('robots_meta', 'like', '%noindex%')->count();
        $redirectPages = (clone $pagesBase)->whereBetween('status_code', [300, 399])->count();
        $status4xx = (clone $pagesBase)->whereBetween('status_code', [400, 499])->count();
        $status5xx = (clone $pagesBase)->where('status_code', '>=', 500)->count();
        $canonicalIssues = (clone $pagesBase)->where(function ($q) {
            $q->whereNull('canonical')->orWhereColumn('canonical', '!=', 'url');
        })->count();
        $avgTitleLength = (clone $pagesBase)->whereNotNull('title')->where('title', '!=', '')->avg(DB::raw('CHAR_LENGTH(title)'));
        $avgMetaLength = (clone $pagesBase)->whereNotNull('meta_description')->where('meta_description', '!=', '')->avg(DB::raw('CHAR_LENGTH(meta_description)'));
        $avgResponse = (clone $pagesBase)->whereNotNull('response_time_ms')->avg('response_time_ms');
        $titleCoverage = $totalPages > 0 ? (int) round((($totalPages - $missingTitles) / $totalPages) * 100) : 0;
        $metaCoverage = $totalPages > 0 ? (int) round((($totalPages - $missingMetaDescriptions) / $totalPages) * 100) : 0;

        $duplicateTitleCandidates = (clone $pagesBase)
            ->select('title', DB::raw('COUNT(*) as cnt'))
            ->whereNotNull('title')
            ->where('title', '!=', '')
            ->groupBy('title')
            ->having('cnt', '>', 1)
            ->get()
            ->sum('cnt');

        $duplicateMetaCandidates = (clone $pagesBase)
            ->select('meta_description', DB::raw('COUNT(*) as cnt'))
            ->whereNotNull('meta_description')
            ->where('meta_description', '!=', '')
            ->groupBy('meta_description')
            ->having('cnt', '>', 1)
            ->get()
            ->sum('cnt');

        $issueSeverityCounts = (clone $issuesBase)
            ->select('severity', DB::raw('COUNT(*) as total'))
            ->groupBy('severity')
            ->pluck('total', 'severity');

        $issueGroups = (clone $issuesBase)
            ->select('type', 'severity', DB::raw('COUNT(*) as total'), DB::raw('COUNT(DISTINCT domain_audit_page_id) as affected_urls'))
            ->groupBy('type', 'severity')
            ->orderByRaw('COUNT(DISTINCT domain_audit_page_id) DESC')
            ->get()
            ->map(function ($item) {
                $meta = $this->issueTypeMeta($item->type);
                return [
                    'type' => $item->type,
                    'label' => $meta['label'],
                    'severity' => $item->severity,
                    'explanation' => $meta['explanation'],
                    'recommendation' => $meta['recommendation'],
                    'total' => (int) $item->total,
                    'affected_urls' => (int) $item->affected_urls,
                ];
            })
            ->values();

        $topFixes = $issueGroups
            ->sortByDesc(function ($issue) {
                $severityWeight = match ($issue['severity']) {
                    'critical' => 1000,
                    'warning' => 500,
                    default => 100,
                };
                return $severityWeight + $issue['affected_urls'];
            })
            ->take(5)
            ->values();

        $selectedPage = null;
        if (!empty($filters['page_id'])) {
            $page = $audit->pages()
                ->with(['issues' => function ($q) {
                    $q->orderByRaw("FIELD(severity, 'critical','warning','info')")->latest('id');
                }])
                ->find($filters['page_id']);

            if ($page) {
                $selectedPage = [
                    'id' => $page->id,
                    'url' => $page->url,
                    'final_url' => $page->final_url,
                    'status_code' => $page->status_code,
                    'response_time_ms' => $page->response_time_ms,
                    'content_type' => $page->content_type,
                    'title' => $page->title,
                    'title_length' => $page->title ? mb_strlen($page->title) : 0,
                    'meta_description' => $page->meta_description,
                    'meta_description_length' => $page->meta_description ? mb_strlen($page->meta_description) : 0,
                    'canonical' => $page->canonical,
                    'robots_meta' => $page->robots_meta,
                    'h1_count' => $page->h1_count,
                    'word_count' => $page->word_count,
                    'is_indexable' => (bool) $page->is_indexable,
                    'issues_count' => $page->issues_count,
                    'issues' => $page->issues->map(function ($issue) {
                        $meta = $this->issueTypeMeta($issue->type);
                        return [
                            'id' => $issue->id,
                            'severity' => $issue->severity,
                            'type' => $issue->type,
                            'label' => $meta['label'],
                            'message' => $issue->message,
                            'explanation' => $meta['explanation'],
                            'recommendation' => $meta['recommendation'],
                        ];
                    })->values(),
                ];
            }
        }

        $metrics = $audit->metrics()->latest()->get();
        $performanceSummary = [
            'total_rows' => $metrics->count(),
            'avg_score' => $metrics->whereNotNull('performance_score')->count() > 0
                ? (int) round($metrics->whereNotNull('performance_score')->avg('performance_score'))
                : null,
            'good_scores' => $metrics->where('performance_score', '>=', 90)->count(),
            'needs_improvement_scores' => $metrics->whereBetween('performance_score', [50, 89])->count(),
            'poor_scores' => $metrics->whereNotNull('performance_score')->where('performance_score', '<', 50)->count(),
            'mobile_rows' => $metrics->where('strategy', 'mobile')->count(),
            'desktop_rows' => $metrics->where('strategy', 'desktop')->count(),
        ];

        return Inertia::render('Domains/Audits/Show', [
            'domain' => $domain,
            'audit' => [
                'id' => $audit->id,
                'domain_id' => $audit->domain_id,
                'organization_id' => $audit->organization_id,
                'domain_host' => $domain->host,
                'status' => $audit->status,
                'started_at' => $audit->started_at?->toIso8601String(),
                'finished_at' => $audit->finished_at?->toIso8601String(),
                'duration_seconds' => $audit->duration,
                'health_score' => $audit->health_score,
                'summary_json' => $audit->summary_json ?? [],
                'settings_json' => $audit->settings_json ?? [],
                'error_message' => $audit->error_message,
                'metrics' => $metrics,
            ],
            'pages' => $pages,
            'issues' => $issues,
            'issueTypes' => $issueTypes,
            'issueGroups' => $issueGroups,
            'stats' => [
                'total_urls_crawled' => $totalPages,
                'indexable_urls' => $indexablePages,
                'non_indexable_urls' => max(0, $totalPages - $indexablePages),
                'critical_issues' => (int) ($issueSeverityCounts['critical'] ?? 0),
                'warning_issues' => (int) ($issueSeverityCounts['warning'] ?? 0),
                'info_issues' => (int) ($issueSeverityCounts['info'] ?? 0),
                'redirect_urls' => $redirectPages,
                'status_4xx_urls' => $status4xx,
                'status_5xx_urls' => $status5xx,
                'noindex_urls' => $noindexPages,
                'missing_titles' => $missingTitles,
                'missing_meta_descriptions' => $missingMetaDescriptions,
                'missing_h1' => $missingH1,
                'duplicate_title_candidates' => $duplicateTitleCandidates,
                'duplicate_meta_candidates' => $duplicateMetaCandidates,
                'canonical_issues' => $canonicalIssues,
                'blocked_pages' => 0,
                'avg_title_length' => $avgTitleLength ? round($avgTitleLength, 1) : null,
                'avg_meta_length' => $avgMetaLength ? round($avgMetaLength, 1) : null,
                'avg_response_time_ms' => $avgResponse ? (int) round($avgResponse) : null,
                'title_coverage_percent' => $titleCoverage,
                'meta_coverage_percent' => $metaCoverage,
            ],
            'topFixes' => $topFixes,
            'selectedPage' => $selectedPage,
            'performanceSummary' => $performanceSummary,
            'hasPageSpeedApiKey' => (bool) config('services.google.pagespeed_api_key'),
            'filters' => $filters,
        ]);
    }

    protected function applyPageSorting($query, string $sort, string $direction): void
    {
        $allowed = ['updated_at', 'status_code', 'issues_count', 'response_time_ms', 'word_count', 'title', 'url'];
        if (!in_array($sort, $allowed, true)) {
            $sort = 'updated_at';
        }
        $direction = strtolower($direction) === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sort, $direction)->orderBy('id', 'desc');
    }

    protected function issueTypeMeta(?string $type): array
    {
        $type = (string) $type;
        $map = [
            'missing_h1' => [
                'label' => 'Missing H1',
                'explanation' => 'Page has no primary heading, which weakens topical clarity.',
                'recommendation' => 'Add one clear H1 aligned with search intent.',
            ],
            'missing_title' => [
                'label' => 'Missing Title',
                'explanation' => 'No title tag found; search snippets become unpredictable.',
                'recommendation' => 'Add a unique, descriptive title of 20-60 characters.',
            ],
            'title_too_short' => [
                'label' => 'Title Too Short',
                'explanation' => 'Short titles reduce relevance signals and CTR potential.',
                'recommendation' => 'Expand title with primary keyword and context.',
            ],
            'title_too_long' => [
                'label' => 'Title Too Long',
                'explanation' => 'Long titles are often truncated in SERPs.',
                'recommendation' => 'Trim to concise format while retaining intent.',
            ],
            'meta_description_missing' => [
                'label' => 'Missing Meta Description',
                'explanation' => 'No meta description means SERPs may auto-generate weak snippets.',
                'recommendation' => 'Write unique 70-160 character descriptions.',
            ],
            'meta_description_too_short' => [
                'label' => 'Meta Too Short',
                'explanation' => 'Very short descriptions provide weak snippet context.',
                'recommendation' => 'Expand description with value proposition and keywords.',
            ],
            'meta_description_too_long' => [
                'label' => 'Meta Too Long',
                'explanation' => 'Descriptions exceeding common limits are truncated in results.',
                'recommendation' => 'Shorten to around 155 characters.',
            ],
            'missing_canonical' => [
                'label' => 'Missing Canonical',
                'explanation' => 'Canonical absence increases duplicate-content ambiguity.',
                'recommendation' => 'Set canonical to preferred indexable URL.',
            ],
            'status_4xx_5xx' => [
                'label' => 'Error Status URL',
                'explanation' => 'Broken or server-error pages waste crawl budget and harm UX.',
                'recommendation' => 'Fix destination, restore content, or redirect appropriately.',
            ],
            'not_indexable_noindex' => [
                'label' => 'Noindex URL',
                'explanation' => 'Noindex directive excludes page from search index.',
                'recommendation' => 'Remove noindex on pages meant to rank.',
            ],
            'title_duplicate' => [
                'label' => 'Duplicate Title Candidate',
                'explanation' => 'Multiple pages share the same title and can cannibalize relevance.',
                'recommendation' => 'Differentiate titles by unique page intent.',
            ],
            'multiple_h1' => [
                'label' => 'Multiple H1',
                'explanation' => 'Several H1 headings can dilute primary topic signal.',
                'recommendation' => 'Keep one primary H1 and use H2/H3 for structure.',
            ],
        ];

        if (isset($map[$type])) {
            return $map[$type];
        }

        return [
            'label' => str_replace('_', ' ', ucfirst($type)),
            'explanation' => 'Technical SEO issue detected during crawl analysis.',
            'recommendation' => 'Review affected pages and apply a focused fix.',
        ];
    }

    /**
     * Export audit data as CSV
     */
    public function export(Domain $domain, $auditId)
    {
        // Resolve audit manually since route parameter doesn't match model name
        $audit = DomainAudit::where('domain_id', $domain->id)
            ->where('user_id', Auth::id())
            ->findOrFail($auditId);
        
        // Authorize ownership
        if ($domain->user_id !== Auth::id() || $audit->domain_id !== $domain->id || $audit->user_id !== Auth::id()) {
            abort(403);
        }

        $filename = "audit-{$audit->id}-" . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($audit) {
            $file = fopen('php://output', 'w');

            // Pages sheet
            fputcsv($file, ['Pages']);
            fputcsv($file, ['URL', 'Status Code', 'Title', 'H1 Count', 'Word Count', 'Indexable', 'Issues Count']);
            
            foreach ($audit->pages as $page) {
                fputcsv($file, [
                    $page->url,
                    $page->status_code,
                    $page->title,
                    $page->h1_count,
                    $page->word_count,
                    $page->is_indexable ? 'Yes' : 'No',
                    $page->issues_count,
                ]);
            }

            fputcsv($file, []); // Empty row

            // Issues sheet
            fputcsv($file, ['Issues']);
            fputcsv($file, ['URL', 'Severity', 'Type', 'Message']);
            
            foreach ($audit->issues as $issue) {
                fputcsv($file, [
                    $issue->page->url ?? '',
                    $issue->severity,
                    $issue->type,
                    $issue->message,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
