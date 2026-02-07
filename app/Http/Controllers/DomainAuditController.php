<?php

namespace App\Http\Controllers;

use App\Jobs\Audits\StartDomainAuditJob;
use App\Models\Domain;
use App\Models\DomainAudit;
use App\Services\Auth\DomainAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
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

        // Check quota limits
        $user = Auth::user();
        try {
            $quotaService = app(\App\Services\Usage\QuotaService::class);
            $quotaService->assertCan($user, 'audits.runs_per_month', 1);
        } catch (\App\Exceptions\QuotaExceededException $e) {
            return back()->withErrors([
                'quota' => $e->getMessage()
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
        StartDomainAuditJob::dispatch($audit->id);

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

        // Load relationships
        $audit->load(['domain', 'pages', 'issues', 'metrics']);

        // Get filters from query
        $filters = [
            'severity' => $request->query('severity'),
            'type' => $request->query('type'),
            'status_code' => $request->query('status_code'),
            'indexable' => $request->query('indexable'),
            'search' => $request->query('search'),
            'tab' => $request->query('tab', 'issues'),
        ];

        // Paginate pages
        $pagesQuery = $audit->pages()->latest();
        if ($filters['status_code']) {
            $pagesQuery->where('status_code', $filters['status_code']);
        }
        if ($filters['indexable'] !== null) {
            $pagesQuery->where('is_indexable', $filters['indexable'] === '1');
        }
        if ($filters['search']) {
            $pagesQuery->where(function($q) use ($filters) {
                $q->where('url', 'like', "%{$filters['search']}%")
                  ->orWhere('title', 'like', "%{$filters['search']}%");
            });
        }
        $pages = $pagesQuery->paginate(20)->withQueryString();

        // Paginate issues
        $issuesQuery = $audit->issues()->with('page')->latest();
        if ($filters['severity']) {
            $issuesQuery->where('severity', $filters['severity']);
        }
        if ($filters['type']) {
            $issuesQuery->where('type', $filters['type']);
        }
        if ($filters['search']) {
            $issuesQuery->whereHas('page', function($q) use ($filters) {
                $q->where('url', 'like', "%{$filters['search']}%");
            });
        }
        $issues = $issuesQuery->paginate(20)->withQueryString();

        // Get unique issue types for filter
        $issueTypes = $audit->issues()
            ->distinct()
            ->pluck('type')
            ->sort()
            ->values();

        return Inertia::render('Domains/Audits/Show', [
            'domain' => $domain,
            'audit' => [
                'id' => $audit->id,
                'domain_id' => $audit->domain_id,
                'organization_id' => $audit->organization_id,
                'status' => $audit->status,
            ],
            'pages' => $pages,
            'issues' => $issues,
            'issueTypes' => $issueTypes,
            'filters' => $filters,
        ]);
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
