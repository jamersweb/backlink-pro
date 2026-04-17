<?php

namespace App\Services\Integrations;

use App\Models\Audit;
use App\Models\ConnectedAccount;
use App\Services\Google\GoogleSeoClientFactory;
use App\Services\Google\Ga4Service;
use App\Services\Google\SearchConsoleService;
use App\Services\SeoAudit\AuditKpiSanitizer;
use Illuminate\Support\Facades\Http;

/**
 * Heavy Google KPI fetch for a single Audit record — intended for queued jobs, not HTTP requests.
 */
class AuditGoogleKpiSyncService
{
    public function __construct(
        protected AuditKpiSanitizer $auditKpiSanitizer
    ) {}

    public function syncGsc(Audit $audit, ConnectedAccount $account, ?string $siteUrl): void
    {
        $gsc = new SearchConsoleService($account);
        $sites = $gsc->listSites();

        $resolved = $siteUrl
            ?: data_get($audit->audit_kpis, 'gsc.selected_site_url')
            ?: data_get($audit->audit_kpis, 'gsc.site_url')
            ?: $this->deriveDefaultSiteUrl($sites, $audit->normalized_url);

        if (! $resolved) {
            throw new \RuntimeException('No matching Search Console property found for this audit.');
        }

        if (! collect($sites)->contains(fn ($site) => ($site['siteUrl'] ?? null) === $resolved)) {
            throw new \RuntimeException('Selected Search Console property is no longer available.');
        }

        $endDate = now()->subDay();
        $startDate = now()->subDays(28);

        $dailyMetrics = $gsc->fetchDailyMetrics($resolved, $startDate, $endDate);
        $topQueries = $gsc->fetchTopQueries($resolved, $startDate, $endDate, 20);
        $topPages = $gsc->fetchTopPages($resolved, $startDate, $endDate, 20);
        $indexCoverage = $this->fetchIndexCoverageSummary($account, $resolved, $topPages);

        $totalClicks = array_sum(array_column($dailyMetrics, 'clicks'));
        $totalImpressions = array_sum(array_column($dailyMetrics, 'impressions'));
        $avgCtr = $totalImpressions > 0 ? round(($totalClicks / $totalImpressions) * 100, 2) : 0;
        $avgPosition = ! empty($dailyMetrics)
            ? round(array_sum(array_column($dailyMetrics, 'position')) / count($dailyMetrics), 1)
            : 0;

        $gscPayload = [
            'connected' => true,
            'site_url' => $resolved,
            'selected_site_url' => $resolved,
            'period' => $startDate->format('M d') . ' - ' . $endDate->format('M d, Y'),
            'summary' => [
                'total_clicks' => $totalClicks,
                'total_impressions' => $totalImpressions,
                'avg_ctr' => $avgCtr,
                'avg_position' => $avgPosition,
            ],
            'daily' => $dailyMetrics,
            'top_queries' => $topQueries,
            'top_pages' => $topPages,
            'index_coverage' => $indexCoverage,
        ];

        $kpis = is_array($audit->audit_kpis) ? $audit->audit_kpis : [];
        $merged = array_merge($kpis['gsc'] ?? [], $gscPayload);
        unset($merged['sync_status'], $merged['sync_error']);
        $kpis['gsc'] = $merged;
        $audit->audit_kpis = $this->auditKpiSanitizer->sanitize($kpis);
        $audit->gsc_ready_at = now();
        $audit->save();
    }

    public function syncGa4(Audit $audit, ConnectedAccount $account, ?string $propertyId): void
    {
        $ga4 = new Ga4Service($account);
        $properties = $ga4->listProperties();

        $resolved = $propertyId
            ?: data_get($audit->audit_kpis, 'ga4.selected_property_id')
            ?: data_get($audit->audit_kpis, 'ga4.property_id')
            ?: ($properties[0]['propertyName'] ?? null);
        $resolved = $resolved ? $this->normalizePropertyId($resolved) : null;

        if (! $resolved) {
            throw new \RuntimeException('No GA4 property found for this account.');
        }

        $selectedProperty = collect($properties)->first(fn ($p) => ($p['propertyName'] ?? null) === $resolved);
        if (! $selectedProperty) {
            throw new \RuntimeException('Selected GA4 property is no longer available.');
        }

        $endDate = new \DateTime('now');
        $startDate = (clone $endDate)->modify('-' . $this->resolveReportPeriodDays($audit) . ' days');

        $dailyMetrics = $ga4->runDailyReport($resolved, $startDate, $endDate);
        $landingPages = $ga4->runLandingPagesReport($resolved, $startDate, $endDate, 20);
        $topSources = $ga4->runTopSourcesReport($resolved, $startDate, $endDate, 20);

        $totalSessions = array_sum(array_column($dailyMetrics, 'sessions'));
        $totalUsers = array_sum(array_column($dailyMetrics, 'total_users'));
        $avgEngagementRate = ! empty($dailyMetrics)
            ? round(array_sum(array_column($dailyMetrics, 'engagement_rate')) / count($dailyMetrics) * 100, 1)
            : 0;

        $ga4Payload = [
            'connected' => true,
            'property' => $selectedProperty['displayName'] ?? $resolved,
            'property_id' => $resolved,
            'selected_property_id' => $resolved,
            'period' => $startDate->format('Y-m-d') . ' to ' . $endDate->format('Y-m-d'),
            'summary' => [
                'total_sessions' => $totalSessions,
                'total_users' => $totalUsers,
                'avg_engagement_rate' => $avgEngagementRate,
            ],
            'daily' => $dailyMetrics,
            'top_pages' => $landingPages,
            'top_sources' => $topSources,
        ];

        $kpis = is_array($audit->audit_kpis) ? $audit->audit_kpis : [];
        $merged = array_merge($kpis['ga4'] ?? [], $ga4Payload);
        unset($merged['sync_status'], $merged['sync_error']);
        $kpis['ga4'] = $merged;
        $audit->audit_kpis = $this->auditKpiSanitizer->sanitize($kpis);
        $audit->ga4_ready_at = now();
        $audit->save();
    }

    protected function resolveReportPeriodDays(Audit $audit): int
    {
        $audit->loadMissing('organization.brandingProfile');
        $days = (int) ($audit->organization?->brandingProfile?->report_period_days ?: 30);

        return in_array($days, [7, 15, 30], true) ? $days : 30;
    }

    protected function normalizePropertyId(string $propertyId): string
    {
        $propertyId = trim($propertyId);
        if ($propertyId === '') {
            return $propertyId;
        }

        return str_starts_with($propertyId, 'properties/')
            ? $propertyId
            : 'properties/' . $propertyId;
    }

    protected function deriveDefaultSiteUrl(array $sites, string $normalizedUrl): ?string
    {
        $auditHost = parse_url($normalizedUrl, PHP_URL_HOST) ?: '';
        foreach ($sites as $site) {
            $siteUrl = $site['siteUrl'] ?? null;
            if (! $siteUrl) {
                continue;
            }
            $siteHost = parse_url($siteUrl, PHP_URL_HOST) ?: str_replace('sc-domain:', '', $siteUrl);
            if ($auditHost !== '' && ($siteHost === $auditHost || str_contains($siteUrl, $auditHost))) {
                return $siteUrl;
            }
        }

        return $sites[0]['siteUrl'] ?? null;
    }

    protected function fetchIndexCoverageSummary(ConnectedAccount $account, string $siteUrl, array $topPages): array
    {
        $urls = collect($topPages)
            ->pluck('page')
            ->filter()
            ->unique()
            ->values()
            ->take(15)
            ->all();

        if ($urls === []) {
            return [
                'summary' => [
                    'inspected_urls' => 0,
                    'indexed' => 0,
                    'not_indexed' => 0,
                    'errors' => 0,
                ],
                'issues' => [],
            ];
        }

        $client = GoogleSeoClientFactory::create($account);
        $tokenPayload = $client->getAccessToken();
        $token = is_array($tokenPayload)
            ? ($tokenPayload['access_token'] ?? null)
            : (is_string($tokenPayload) ? $tokenPayload : null);
        if (! $token) {
            throw new \RuntimeException('Google access token unavailable for URL inspection.');
        }

        $issues = [];
        $indexed = 0;
        $notIndexed = 0;
        $errors = 0;

        foreach ($urls as $url) {
            $res = Http::timeout(20)
                ->withToken($token)
                ->post('https://searchconsole.googleapis.com/v1/urlInspection/index:inspect', [
                    'inspectionUrl' => $url,
                    'siteUrl' => $siteUrl,
                    'languageCode' => 'en-US',
                ]);

            if (! $res->ok()) {
                $errors++;
                $issues[] = [
                    'url' => $url,
                    'severity' => 'error',
                    'coverage_state' => 'Inspection failed',
                    'indexing_state' => 'unknown',
                    'page_fetch_state' => 'unknown',
                    'message' => data_get($res->json(), 'error.message', 'Unable to inspect URL.'),
                ];
                continue;
            }

            $indexStatus = data_get($res->json(), 'inspectionResult.indexStatusResult', []);
            $coverageState = (string) ($indexStatus['coverageState'] ?? 'Unknown');
            $indexingState = (string) ($indexStatus['indexingState'] ?? 'Unknown');
            $pageFetchState = (string) ($indexStatus['pageFetchState'] ?? 'Unknown');

            $isIndexed = str_contains(strtolower($coverageState), 'indexed')
                || str_contains(strtolower($indexingState), 'allowed');

            if ($isIndexed) {
                $indexed++;
            } else {
                $notIndexed++;
            }

            $isError = str_contains(strtolower($coverageState), 'error')
                || str_contains(strtolower($indexingState), 'blocked')
                || str_contains(strtolower($pageFetchState), 'error');

            if ($isError) {
                $errors++;
            }

            $issues[] = [
                'url' => $url,
                'severity' => $isError ? 'error' : ($isIndexed ? 'info' : 'warning'),
                'coverage_state' => $coverageState,
                'indexing_state' => $indexingState,
                'page_fetch_state' => $pageFetchState,
                'robots_txt_state' => (string) ($indexStatus['robotsTxtState'] ?? 'Unknown'),
                'last_crawl_time' => $indexStatus['lastCrawlTime'] ?? null,
                'message' => $indexStatus['verdict'] ?? ($isIndexed ? 'Indexed' : 'Not indexed'),
            ];
        }

        return [
            'summary' => [
                'inspected_urls' => count($urls),
                'indexed' => $indexed,
                'not_indexed' => $notIndexed,
                'errors' => $errors,
            ],
            'issues' => $issues,
        ];
    }
}
