<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\KeywordOpportunity;
use App\Models\GscTopQuery;
use App\Services\Content\OpportunityScorer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Inertia\Inertia;

class ContentDashboardController extends Controller
{
    protected $scorer;

    public function __construct(OpportunityScorer $scorer)
    {
        $this->scorer = $scorer;
    }

    /**
     * Show opportunities dashboard
     */
    public function index(Domain $domain, Request $request)
    {
        Gate::authorize('domain.view', $domain);

        $filters = [
            'min_score' => $request->query('min_score', 30),
            'min_position' => $request->query('min_position', 1),
            'max_position' => $request->query('max_position', 100),
            'max_ctr' => $request->query('max_ctr', 0.05),
            'status' => $request->query('status', 'new'),
        ];

        $query = KeywordOpportunity::where('domain_id', $domain->id)
            ->where('opportunity_score', '>=', $filters['min_score'])
            ->whereBetween('position', [$filters['min_position'], $filters['max_position']])
            ->where('ctr', '<=', $filters['max_ctr']);

        if ($filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        $opportunities = $query->orderByDesc('opportunity_score')
            ->paginate(25);

        return Inertia::render('Domains/Content/Index', [
            'domain' => $domain,
            'opportunities' => $opportunities,
            'filters' => $filters,
        ]);
    }

    /**
     * Refresh opportunities from GSC data
     */
    public function refreshOpportunities(Domain $domain)
    {
        Gate::authorize('domain.view', $domain);

        $endDate = Carbon::today();
        $startDate = $endDate->copy()->subDays(28);

        // Get GSC queries from last 28 days
        // Note: GscTopQuery doesn't have page field, so we aggregate by query only
        $gscQueries = GscTopQuery::where('domain_id', $domain->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->select('query', DB::raw('SUM(impressions) as total_impressions'), 
                     DB::raw('SUM(clicks) as total_clicks'),
                     DB::raw('AVG(ctr) as avg_ctr'),
                     DB::raw('AVG(position) as avg_position'))
            ->groupBy('query')
            ->having('total_impressions', '>=', 100) // Minimum impressions threshold
            ->having('avg_position', '>=', 8) // Position 8+
            ->having('avg_position', '<=', 100) // But not too far
            ->get();

        // Try to get suggested page from GscTopPage for each query
        $created = 0;
        foreach ($gscQueries as $gscQuery) {
            // Find top page for this query (if we had query+page data, we'd use it)
            // For now, we'll leave page_url as null and let user specify
            $pageHash = null;
            
            // Check if opportunity already exists
            $exists = KeywordOpportunity::where('domain_id', $domain->id)
                ->where('date_range_start', $startDate)
                ->where('date_range_end', $endDate)
                ->where('query', $gscQuery->query)
                ->where('page_hash', $pageHash)
                ->exists();

            if ($exists) {
                continue;
            }

            // Score the opportunity
            $score = $this->scorer->score(
                $gscQuery->total_impressions,
                $gscQuery->avg_position,
                $gscQuery->avg_ctr
            );

            // Only create if score is meaningful
            if ($score >= 20) {
                KeywordOpportunity::create([
                    'domain_id' => $domain->id,
                    'date_range_start' => $startDate,
                    'date_range_end' => $endDate,
                    'query' => $gscQuery->query,
                    'page_url' => null, // Will be suggested from GscTopPage or user-specified
                    'page_hash' => $pageHash,
                    'impressions' => $gscQuery->total_impressions,
                    'clicks' => $gscQuery->total_clicks,
                    'ctr' => $gscQuery->avg_ctr,
                    'position' => $gscQuery->avg_position,
                    'opportunity_score' => $score,
                    'status' => KeywordOpportunity::STATUS_NEW,
                ]);
                $created++;
            }
        }

        return back()->with('success', "Created {$created} new opportunities");
    }

    /**
     * Ignore an opportunity
     */
    public function ignoreOpportunity(Domain $domain, KeywordOpportunity $opportunity)
    {
        Gate::authorize('domain.view', $domain);

        if ($opportunity->domain_id !== $domain->id) {
            abort(403);
        }

        $opportunity->update(['status' => KeywordOpportunity::STATUS_IGNORED]);

        return back()->with('success', 'Opportunity ignored');
    }
}
