<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\DomainBacklinkRun;
use App\Jobs\Backlinks\StartBacklinkRunJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DomainBacklinksController extends Controller
{
    /**
     * List backlink runs for a domain
     */
    public function index(Domain $domain)
    {
        // Authorize domain ownership
        if ($domain->user_id !== Auth::id()) {
            abort(403);
        }

        $runs = DomainBacklinkRun::where('domain_id', $domain->id)
            ->where('user_id', Auth::id())
            ->latest()
            ->paginate(15)
            ->withQueryString();

        // Get latest completed run summary
        $latestRun = DomainBacklinkRun::where('domain_id', $domain->id)
            ->where('user_id', Auth::id())
            ->where('status', DomainBacklinkRun::STATUS_COMPLETED)
            ->latest()
            ->first();

        return Inertia::render('Domains/Backlinks/Index', [
            'domain' => $domain,
            'runs' => $runs,
            'latestSummary' => $latestRun ? $latestRun->summary_json : null,
        ]);
    }

    /**
     * Store a new backlink run
     */
    public function store(Request $request, Domain $domain)
    {
        // Authorize domain ownership
        if ($domain->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'limit_backlinks' => 'required|integer|min:1|max:5000',
            'limit_ref_domains' => 'required|integer|min:1|max:2000',
            'limit_anchors' => 'required|integer|min:1|max:1000',
        ]);

        // Check quota limits
        $user = Auth::user();
        try {
            $quotaService = app(\App\Services\Usage\QuotaService::class);
            $quotaService->assertCan($user, 'backlinks.runs_per_month', 1);
        } catch (\App\Exceptions\QuotaExceededException $e) {
            return back()->withErrors([
                'quota' => $e->getMessage()
            ]);
        }

        // Create run
        $run = DomainBacklinkRun::create([
            'domain_id' => $domain->id,
            'user_id' => Auth::id(),
            'status' => DomainBacklinkRun::STATUS_QUEUED,
            'provider' => config('services.backlinks.provider', 'dataforseo'),
            'settings_json' => [
                'limit_backlinks' => $validated['limit_backlinks'],
                'limit_ref_domains' => $validated['limit_ref_domains'],
                'limit_anchors' => $validated['limit_anchors'],
            ],
        ]);

        // Consume quota
        $quotaService = app(\App\Services\Usage\QuotaService::class);
        $quotaService->consume($user, 'backlinks.runs_per_month', 1, 'month', [
            'run_id' => $run->id,
            'domain_id' => $domain->id,
        ]);

        // Dispatch job
        StartBacklinkRunJob::dispatch($run->id);

        return redirect()->route('domains.backlinks.show', [$domain->id, $run->id])
            ->with('success', 'Backlink fetch started. It will begin processing shortly.');
    }

    /**
     * Show backlink run details
     */
    public function show(Request $request, Domain $domain, $runId)
    {
        // Resolve run manually
        $run = DomainBacklinkRun::where('domain_id', $domain->id)
            ->where('user_id', Auth::id())
            ->findOrFail($runId);

        // Authorize ownership
        if ($domain->user_id !== Auth::id() || $run->domain_id !== $domain->id || $run->user_id !== Auth::id()) {
            abort(403);
        }

        // Load relationships
        $run->load(['delta', 'delta.previousRun']);

        // Get filters from query
        $filters = [
            'rel' => $request->query('rel'),
            'tld' => $request->query('tld'),
            'search' => $request->query('search'),
            'tab' => $request->query('tab', 'backlinks'),
        ];

        // Paginate backlinks
        $backlinksQuery = $run->backlinks()->latest();
        if ($filters['rel']) {
            $backlinksQuery->where('rel', $filters['rel']);
        }
        if ($filters['tld']) {
            $backlinksQuery->where('tld', $filters['tld']);
        }
        if ($filters['search']) {
            $backlinksQuery->where(function($q) use ($filters) {
                $q->where('source_domain', 'like', "%{$filters['search']}%")
                  ->orWhere('anchor', 'like', "%{$filters['search']}%")
                  ->orWhere('source_url', 'like', "%{$filters['search']}%");
            });
        }
        $backlinks = $backlinksQuery->paginate(20)->withQueryString();

        // Paginate ref domains
        $refDomainsQuery = $run->refDomains()->orderBy('backlinks_count', 'desc');
        if ($filters['search']) {
            $refDomainsQuery->where('domain', 'like', "%{$filters['search']}%");
        }
        $refDomains = $refDomainsQuery->paginate(20)->withQueryString();

        // Paginate anchors
        $anchorsQuery = $run->anchorSummaries()->orderBy('count', 'desc');
        if ($filters['search']) {
            $anchorsQuery->where('anchor', 'like', "%{$filters['search']}%");
        }
        $anchors = $anchorsQuery->paginate(20)->withQueryString();

        // Get unique values for filters
        $uniqueTlds = $run->backlinks()
            ->whereNotNull('tld')
            ->distinct()
            ->pluck('tld')
            ->sort()
            ->values();

        return Inertia::render('Domains/Backlinks/Show', [
            'domain' => $domain,
            'run' => $run,
            'backlinks' => $backlinks,
            'refDomains' => $refDomains,
            'anchors' => $anchors,
            'uniqueTlds' => $uniqueTlds,
            'filters' => $filters,
        ]);
    }

    /**
     * Export backlink data as CSV
     */
    public function export(Domain $domain, $runId)
    {
        // Resolve run manually
        $run = DomainBacklinkRun::where('domain_id', $domain->id)
            ->where('user_id', Auth::id())
            ->findOrFail($runId);

        // Authorize ownership
        if ($domain->user_id !== Auth::id() || $run->domain_id !== $domain->id || $run->user_id !== Auth::id()) {
            abort(403);
        }

        $filename = "backlinks-{$run->id}-" . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($run) {
            $file = fopen('php://output', 'w');

            // Backlinks sheet
            fputcsv($file, ['Backlinks']);
            fputcsv($file, ['Source URL', 'Source Domain', 'Target URL', 'Anchor', 'Rel', 'TLD', 'Country']);
            
            foreach ($run->backlinks as $backlink) {
                fputcsv($file, [
                    $backlink->source_url,
                    $backlink->source_domain,
                    $backlink->target_url,
                    $backlink->anchor,
                    $backlink->rel,
                    $backlink->tld,
                    $backlink->country,
                ]);
            }

            fputcsv($file, []); // Empty row

            // Ref Domains sheet
            fputcsv($file, ['Referring Domains']);
            fputcsv($file, ['Domain', 'Backlinks Count', 'TLD', 'Country', 'Risk Score']);
            
            foreach ($run->refDomains as $refDomain) {
                fputcsv($file, [
                    $refDomain->domain,
                    $refDomain->backlinks_count,
                    $refDomain->tld,
                    $refDomain->country,
                    $refDomain->risk_score,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
