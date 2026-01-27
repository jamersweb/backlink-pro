<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\RankKeyword;
use App\Models\RankCheck;
use App\Models\RankResult;
use App\Services\RankTracking\RankTracker;
use App\Services\Usage\QuotaService;
use App\Jobs\RankTracking\RunRankCheckJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class RankTrackingController extends Controller
{
    /**
     * Show rank tracking index
     */
    public function index(Domain $domain)
    {
        Gate::authorize('domain.view', $domain);

        $rankTracker = new RankTracker($domain);

        $keywords = RankKeyword::where('domain_id', $domain->id)
            ->orderBy('is_active', 'desc')
            ->orderBy('keyword')
            ->with(['latestResult'])
            ->paginate(50);

        $recentChecks = RankCheck::where('domain_id', $domain->id)
            ->orderBy('created_at', 'desc')
            ->with('user')
            ->limit(10)
            ->get();

        $winners = $rankTracker->getWinners(7, 10);
        $losers = $rankTracker->getLosers(7, 10);
        $avgPosition = $rankTracker->getAveragePosition();

        return Inertia::render('Domains/RankTracking/Index', [
            'domain' => $domain,
            'keywords' => $keywords,
            'recentChecks' => $recentChecks,
            'stats' => [
                'total_keywords' => RankKeyword::where('domain_id', $domain->id)->where('is_active', true)->count(),
                'winners_count' => count($winners),
                'losers_count' => count($losers),
                'avg_position' => $avgPosition,
            ],
            'winners' => $winners,
            'losers' => $losers,
        ]);
    }

    /**
     * Store keyword (manual add)
     */
    public function storeKeyword(Domain $domain, Request $request)
    {
        Gate::authorize('domain.view', $domain);

        $validated = $request->validate([
            'keyword' => 'required|string|max:255',
            'target_url' => 'nullable|url',
            'location_code' => 'nullable|string|max:50',
            'language_code' => 'nullable|string|max:10',
            'device' => 'required|in:desktop,mobile',
            'schedule' => 'required|in:daily,weekly,manual',
        ]);

        try {
            RankKeyword::create([
                'domain_id' => $domain->id,
                'keyword' => $validated['keyword'],
                'target_url' => $validated['target_url'] ?? null,
                'location_code' => $validated['location_code'] ?? null,
                'language_code' => $validated['language_code'] ?? 'en',
                'device' => $validated['device'],
                'schedule' => $validated['schedule'],
                'is_active' => true,
                'source' => RankKeyword::SOURCE_MANUAL,
            ]);

            return back()->with('success', 'Keyword added');
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                return back()->with('error', 'This keyword combination already exists');
            }
            return back()->with('error', 'Failed to add keyword: ' . $e->getMessage());
        }
    }

    /**
     * Sync keywords from sources
     */
    public function syncFromSources(Domain $domain, Request $request)
    {
        Gate::authorize('domain.view', $domain);

        $rankTracker = new RankTracker($domain);
        $synced = $rankTracker->syncKeywordsFromSources();

        return back()->with('success', "Synced {$synced['keyword_map']} from Keyword Map, {$synced['opportunities']} from GSC opportunities");
    }

    /**
     * Toggle keyword active status
     */
    public function toggleKeyword(Domain $domain, RankKeyword $keyword, Request $request)
    {
        Gate::authorize('domain.view', $domain);

        if ($keyword->domain_id !== $domain->id) {
            abort(404);
        }

        $keyword->update(['is_active' => !$keyword->is_active]);

        return back()->with('success', $keyword->is_active ? 'Keyword activated' : 'Keyword deactivated');
    }

    /**
     * Run rank check now
     */
    public function runNow(Domain $domain, Request $request)
    {
        Gate::authorize('domain.view', $domain);

        $validated = $request->validate([
            'keyword_ids' => 'nullable|array',
            'keyword_ids.*' => 'exists:rank_keywords,id',
            'provider_code' => 'nullable|string',
        ]);

        $user = Auth::user();
        $rankTracker = new RankTracker($domain);

        // Check quota
        $quotaService = app(QuotaService::class);
        $keywordIds = $validated['keyword_ids'] ?? [];
        $keywordCount = empty($keywordIds) 
            ? RankKeyword::where('domain_id', $domain->id)->where('is_active', true)->count()
            : count($keywordIds);

        try {
            $quotaService->assertCan($user, 'rank.keywords_checked_per_month', $keywordCount, [
                'domain_id' => $domain->id,
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Quota exceeded: ' . $e->getMessage());
        }

        $providerCode = $validated['provider_code'] ?? 'gsc_fallback';
        $check = $rankTracker->createCheck($user, $keywordIds, $providerCode);

        // Consume quota
        $quotaService->consume($user, 'rank.keywords_checked_per_month', $keywordCount, 'month', [
            'domain_id' => $domain->id,
            'check_id' => $check->id,
        ]);

        // Dispatch job
        RunRankCheckJob::dispatch($check->id);

        return back()->with('success', "Rank check queued for {$keywordCount} keywords");
    }

    /**
     * Show check details
     */
    public function showCheck(Domain $domain, RankCheck $check)
    {
        Gate::authorize('domain.view', $domain);

        if ($check->domain_id !== $domain->id) {
            abort(404);
        }

        $results = RankResult::where('rank_check_id', $check->id)
            ->with('rankKeyword')
            ->orderBy('keyword')
            ->paginate(50);

        return Inertia::render('Domains/RankTracking/CheckShow', [
            'domain' => $domain,
            'check' => $check->load('user'),
            'results' => $results,
        ]);
    }
}
