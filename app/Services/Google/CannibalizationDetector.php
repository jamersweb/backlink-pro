<?php

namespace App\Services\Google;

use App\Models\Domain;
use App\Models\GscQueryPageMetric;
use Illuminate\Support\Collection;

class CannibalizationDetector
{
    /** Min impressions per page to consider */
    public int $minImpressions = 10;

    /** Min number of pages competing for the same query to flag */
    public int $minPagesPerQuery = 2;

    /**
     * Get cannibalization candidates: queries where >= minPagesPerQuery pages have >= minImpressions.
     */
    public function getCandidates(Domain $domain, ?string $siteUrl = null): Collection
    {
        $query = GscQueryPageMetric::where('domain_id', $domain->id);
        if ($siteUrl !== null) {
            $query->where('site_url', $siteUrl);
        }

        $rows = $query->get();

        $byQuery = $rows->groupBy('query')->map(function ($items, $queryStr) {
            $pages = $items->filter(fn ($r) => $r->impressions >= $this->minImpressions);
            return [
                'query' => $queryStr,
                'pages' => $pages->values()->all(),
                'total_impressions' => $items->sum('impressions'),
                'total_clicks' => $items->sum('clicks'),
            ];
        })->filter(fn ($data) => count($data['pages']) >= $this->minPagesPerQuery);

        return $byQuery->values();
    }
}
