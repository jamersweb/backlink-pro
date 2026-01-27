<?php

namespace App\Services\RankTracking;

use App\Models\Domain;
use App\Models\RankKeyword;
use App\Models\RankCheck;
use App\Models\RankResult;
use App\Models\KeywordMap;
use App\Models\KeywordOpportunity;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RankTracker
{
    protected Domain $domain;

    public function __construct(Domain $domain)
    {
        $this->domain = $domain;
    }

    /**
     * Sync keywords from sources (Keyword Map, GSC opportunities)
     */
    public function syncKeywordsFromSources(int $limitFromOpportunities = 50): array
    {
        $synced = ['keyword_map' => 0, 'opportunities' => 0];

        // Sync from Keyword Map
        $keywordMapEntries = KeywordMap::where('domain_id', $this->domain->id)->get();
        foreach ($keywordMapEntries as $entry) {
            try {
                RankKeyword::firstOrCreate(
                    [
                        'domain_id' => $this->domain->id,
                        'keyword' => $entry->keyword,
                        'location_code' => null,
                        'device' => RankKeyword::DEVICE_DESKTOP,
                    ],
                    [
                        'target_url' => $entry->url,
                        'language_code' => 'en',
                        'schedule' => RankKeyword::SCHEDULE_WEEKLY,
                        'is_active' => true,
                        'source' => RankKeyword::SOURCE_KEYWORD_MAP,
                    ]
                );
                $synced['keyword_map']++;
            } catch (\Exception $e) {
                // Duplicate or other error - skip
                continue;
            }
        }

        // Sync from Keyword Opportunities (top N by score)
        $opportunities = KeywordOpportunity::where('domain_id', $this->domain->id)
            ->where('status', KeywordOpportunity::STATUS_NEW)
            ->orderBy('opportunity_score', 'desc')
            ->limit($limitFromOpportunities)
            ->get();

        foreach ($opportunities as $opp) {
            try {
                RankKeyword::firstOrCreate(
                    [
                        'domain_id' => $this->domain->id,
                        'keyword' => $opp->query,
                        'location_code' => null,
                        'device' => RankKeyword::DEVICE_DESKTOP,
                    ],
                    [
                        'target_url' => $opp->page_url,
                        'language_code' => 'en',
                        'schedule' => RankKeyword::SCHEDULE_WEEKLY,
                        'is_active' => true,
                        'source' => RankKeyword::SOURCE_GSC,
                    ]
                );
                $synced['opportunities']++;
            } catch (\Exception $e) {
                continue;
            }
        }

        return $synced;
    }

    /**
     * Create a rank check
     */
    public function createCheck(User $user, array $keywordIds = [], string $providerCode = 'gsc_fallback'): RankCheck
    {
        // If no keywords specified, use all active keywords
        if (empty($keywordIds)) {
            $keywordIds = RankKeyword::where('domain_id', $this->domain->id)
                ->where('is_active', true)
                ->pluck('id')
                ->toArray();
        }

        $check = RankCheck::create([
            'domain_id' => $this->domain->id,
            'user_id' => $user->id,
            'provider_code' => $providerCode,
            'status' => RankCheck::STATUS_QUEUED,
            'keywords_count' => count($keywordIds),
        ]);

        return $check;
    }

    /**
     * Match domain from URL
     */
    public function matchDomain(string $foundUrl, string $domainHost): bool
    {
        $foundHost = parse_url($foundUrl, PHP_URL_HOST);
        if (!$foundHost) {
            return false;
        }

        // Normalize: remove www, lowercase
        $foundHost = strtolower(preg_replace('/^www\./', '', $foundHost));
        $domainHost = strtolower(preg_replace('/^www\./', '', $domainHost));

        // Compare eTLD+1 (for now, simple comparison)
        // In production, use library like jstewart/url for proper eTLD+1 extraction
        return $foundHost === $domainHost;
    }

    /**
     * Compute deltas (position changes) for keywords
     */
    public function computeDeltas(int $periodDays = 7): array
    {
        $keywords = RankKeyword::where('domain_id', $this->domain->id)
            ->where('is_active', true)
            ->with(['latestResult' => function($query) {
                $query->latest('fetched_at');
            }])
            ->get();

        $deltas = [];

        foreach ($keywords as $keyword) {
            $latestResult = RankResult::where('domain_id', $this->domain->id)
                ->where('rank_keyword_id', $keyword->id)
                ->latest('fetched_at')
                ->first();

            if (!$latestResult) {
                continue;
            }

            $previousResult = RankResult::where('domain_id', $this->domain->id)
                ->where('rank_keyword_id', $keyword->id)
                ->where('fetched_at', '<', $latestResult->fetched_at->subDays($periodDays))
                ->latest('fetched_at')
                ->first();

            $currentPosition = $latestResult->position;
            $previousPosition = $previousResult?->position;

            if ($previousPosition === null) {
                continue; // No comparison point
            }

            // Delta: positive = improvement (lower position number = better)
            // E.g., position 5 -> 3 = delta +2 (improvement)
            // Position 3 -> 5 = delta -2 (drop)
            $delta = $previousPosition - $currentPosition;

            $deltas[] = [
                'keyword_id' => $keyword->id,
                'keyword' => $keyword->keyword,
                'current_position' => $currentPosition,
                'previous_position' => $previousPosition,
                'delta' => $delta,
                'is_winner' => $delta > 0,
                'is_loser' => $delta < 0,
            ];
        }

        return $deltas;
    }

    /**
     * Get winners (improved positions)
     */
    public function getWinners(int $periodDays = 7, int $limit = 10): array
    {
        $deltas = $this->computeDeltas($periodDays);
        return collect($deltas)
            ->where('is_winner', true)
            ->sortByDesc('delta')
            ->take($limit)
            ->values()
            ->toArray();
    }

    /**
     * Get losers (dropped positions)
     */
    public function getLosers(int $periodDays = 7, int $limit = 10): array
    {
        $deltas = $this->computeDeltas($periodDays);
        return collect($deltas)
            ->where('is_loser', true)
            ->sortBy('delta') // Most negative first
            ->take($limit)
            ->values()
            ->toArray();
    }

    /**
     * Get average position
     */
    public function getAveragePosition(): ?float
    {
        $result = RankResult::where('domain_id', $this->domain->id)
            ->whereNotNull('position')
            ->where('fetched_at', '>=', now()->subDays(7))
            ->selectRaw('AVG(position) as avg_position')
            ->first();

        return $result?->avg_position ? (float)$result->avg_position : null;
    }
}


