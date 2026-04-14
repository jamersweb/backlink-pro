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

        // Check quota limits (runs and links fetched per month)
        $user = Auth::user();
        try {
            $quotaService = app(\App\Services\Usage\QuotaService::class);
            $quotaService->assertCan($user, 'backlinks.runs_per_month', 1);
            $quotaService->assertCan($user, 'backlinks.links_fetched_per_month', $validated['limit_backlinks']);
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

        $deltaDetails = $this->buildDeltaDetails($run, 75);

        return Inertia::render('Domains/Backlinks/Show', [
            'domain' => $domain,
            'run' => $run,
            'backlinks' => $backlinks,
            'refDomains' => $refDomains,
            'anchors' => $anchors,
            'uniqueTlds' => $uniqueTlds,
            'filters' => $filters,
            'deltaDetails' => $deltaDetails,
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

        $deltaDetails = $this->buildDeltaDetails($run, null);
        $domainQualityMap = $run->backlinks()
            ->selectRaw("source_domain, AVG(risk_score) as avg_risk_score, AVG(quality_score) as avg_quality_score, SUM(CASE WHEN rel = 'follow' THEN 1 ELSE 0 END) as follow_links")
            ->groupBy('source_domain')
            ->get()
            ->keyBy('source_domain');

        $callback = function() use ($run, $deltaDetails, $domainQualityMap) {
            $file = fopen('php://output', 'w');

            // Backlinks sheet
            fputcsv($file, ['Backlinks']);
            fputcsv($file, [
                'Source URL',
                'Source Domain',
                'Target URL',
                'Anchor',
                'Rel',
                'TLD',
                'Country',
                'First Seen',
                'Last Seen',
                'Risk Score',
                'Quality Score',
                'Action Status',
                'Risk/Quality Flags',
            ]);
            
            foreach ($run->backlinks as $backlink) {
                fputcsv($file, [
                    $backlink->source_url,
                    $backlink->source_domain,
                    $backlink->target_url,
                    $backlink->anchor,
                    $backlink->rel,
                    $backlink->tld,
                    $backlink->country,
                    optional($backlink->first_seen)->toDateString(),
                    optional($backlink->last_seen)->toDateString(),
                    $backlink->risk_score,
                    $backlink->quality_score,
                    $backlink->action_status,
                    $this->stringifyFlags($backlink->flags_json),
                ]);
            }

            fputcsv($file, []); // Empty row

            // Ref Domains sheet
            fputcsv($file, ['Referring Domains']);
            fputcsv($file, [
                'Domain',
                'Backlinks Count',
                'Follow Links',
                'TLD',
                'Country',
                'Ref Domain Risk Score',
                'Avg Backlink Risk',
                'Avg Backlink Quality',
            ]);
            
            foreach ($run->refDomains as $refDomain) {
                $qualityAgg = $domainQualityMap->get($refDomain->domain);
                fputcsv($file, [
                    $refDomain->domain,
                    $refDomain->backlinks_count,
                    $qualityAgg ? (int) $qualityAgg->follow_links : 0,
                    $refDomain->tld,
                    $refDomain->country,
                    $refDomain->risk_score,
                    $qualityAgg ? (int) round((float) $qualityAgg->avg_risk_score) : 0,
                    $qualityAgg ? (int) round((float) $qualityAgg->avg_quality_score) : 0,
                ]);
            }

            fputcsv($file, []);

            fputcsv($file, ['New Links (vs previous completed run)']);
            fputcsv($file, ['Source URL', 'Source Domain', 'Target URL', 'Anchor', 'Rel', 'Risk Score', 'Quality Score', 'Action']);
            foreach ($deltaDetails['new_links'] as $item) {
                fputcsv($file, [
                    $item['source_url'] ?? '',
                    $item['source_domain'] ?? '',
                    $item['target_url'] ?? '',
                    $item['anchor'] ?? '',
                    $item['rel'] ?? '',
                    $item['risk_score'] ?? 0,
                    $item['quality_score'] ?? 0,
                    $item['action_status'] ?? '',
                ]);
            }

            fputcsv($file, []);

            fputcsv($file, ['Lost Links (vs previous completed run)']);
            fputcsv($file, ['Source URL', 'Source Domain', 'Target URL', 'Anchor', 'Rel', 'Risk Score', 'Quality Score', 'Action']);
            foreach ($deltaDetails['lost_links'] as $item) {
                fputcsv($file, [
                    $item['source_url'] ?? '',
                    $item['source_domain'] ?? '',
                    $item['target_url'] ?? '',
                    $item['anchor'] ?? '',
                    $item['rel'] ?? '',
                    $item['risk_score'] ?? 0,
                    $item['quality_score'] ?? 0,
                    $item['action_status'] ?? '',
                ]);
            }

            fputcsv($file, []);

            fputcsv($file, ['New Referring Domains']);
            fputcsv($file, ['Domain']);
            foreach ($deltaDetails['new_ref_domains'] as $domainName) {
                fputcsv($file, [$domainName]);
            }

            fputcsv($file, []);

            fputcsv($file, ['Lost Referring Domains']);
            fputcsv($file, ['Domain']);
            foreach ($deltaDetails['lost_ref_domains'] as $domainName) {
                fputcsv($file, [$domainName]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    protected function buildDeltaDetails(DomainBacklinkRun $run, ?int $limit = 100): array
    {
        $empty = [
            'new_links' => [],
            'lost_links' => [],
            'new_ref_domains' => [],
            'lost_ref_domains' => [],
        ];

        if (!$run->delta || !$run->delta->previous_run_id) {
            return $empty;
        }

        $previousRun = $run->delta->previousRun;
        if (!$previousRun) {
            return $empty;
        }

        $currentFingerprints = $run->backlinks()->pluck('fingerprint')->toArray();
        $previousFingerprints = $previousRun->backlinks()->pluck('fingerprint')->toArray();

        $newFingerprints = array_values(array_diff($currentFingerprints, $previousFingerprints));
        $lostFingerprints = array_values(array_diff($previousFingerprints, $currentFingerprints));

        $newLinksQuery = $run->backlinks()
            ->whereIn('fingerprint', $newFingerprints)
            ->latest();
        $lostLinksQuery = $previousRun->backlinks()
            ->whereIn('fingerprint', $lostFingerprints)
            ->latest();

        if ($limit !== null) {
            $newLinksQuery->limit($limit);
            $lostLinksQuery->limit($limit);
        }

        $newLinks = $newLinksQuery->get([
            'source_url',
            'source_domain',
            'target_url',
            'anchor',
            'rel',
            'risk_score',
            'quality_score',
            'action_status',
        ])->toArray();

        $lostLinks = $lostLinksQuery->get([
            'source_url',
            'source_domain',
            'target_url',
            'anchor',
            'rel',
            'risk_score',
            'quality_score',
            'action_status',
        ])->toArray();

        $currentDomains = $run->refDomains()->pluck('domain')->filter()->values()->toArray();
        $previousDomains = $previousRun->refDomains()->pluck('domain')->filter()->values()->toArray();

        $newRefDomains = array_values(array_diff($currentDomains, $previousDomains));
        $lostRefDomains = array_values(array_diff($previousDomains, $currentDomains));

        if ($limit !== null) {
            $newRefDomains = array_slice($newRefDomains, 0, $limit);
            $lostRefDomains = array_slice($lostRefDomains, 0, $limit);
        }

        return [
            'new_links' => $newLinks,
            'lost_links' => $lostLinks,
            'new_ref_domains' => $newRefDomains,
            'lost_ref_domains' => $lostRefDomains,
        ];
    }

    protected function stringifyFlags(?array $flags): string
    {
        if (empty($flags)) {
            return '';
        }

        if (array_is_list($flags)) {
            return implode('|', array_map('strval', $flags));
        }

        $enabled = [];
        foreach ($flags as $key => $value) {
            if ($value) {
                $enabled[] = (string) $key;
            }
        }

        return implode('|', $enabled);
    }
}
