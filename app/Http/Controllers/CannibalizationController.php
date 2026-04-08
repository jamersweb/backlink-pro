<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Services\Google\CannibalizationDetector;
use App\Services\Google\GscQueryPageSyncService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CannibalizationController extends Controller
{
    /**
     * Show cannibalization page: Run scan button + table of query + pages + suggestion.
     */
    public function index(Domain $domain)
    {
        $this->authorize('update', $domain);

        $detector = new CannibalizationDetector();
        $candidates = $detector->getCandidates($domain);

        $rows = $candidates->map(function ($item) {
            $pages = collect($item['pages'])->map(fn ($p) => [
                'page_url' => $p->page_url,
                'impressions' => $p->impressions,
                'clicks' => $p->clicks,
                'position' => $p->position,
            ])->all();
            return [
                'query' => $item['query'],
                'pages' => $pages,
                'total_impressions' => $item['total_impressions'],
                'total_clicks' => $item['total_clicks'],
                'suggestion' => 'Consider consolidating or differentiating content so one page ranks clearly for this query.',
            ];
        });

        return Inertia::render('Domains/Seo/Cannibalization', [
            'domain' => [
                'id' => $domain->id,
                'name' => $domain->name,
                'host' => $domain->host,
            ],
            'candidates' => $rows,
            'hasGsc' => (bool) $domain->googleIntegration?->gsc_property,
        ]);
    }

    /**
     * Run scan: sync GSC query+page data then redirect back.
     */
    public function runScan(Request $request, Domain $domain)
    {
        $this->authorize('update', $domain);

        $service = new GscQueryPageSyncService();
        $result = $service->syncForDomain($domain);

        return redirect()->route('domains.seo.cannibalization', $domain)
            ->with('success', $result['message']);
    }
}
