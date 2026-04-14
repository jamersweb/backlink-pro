<?php

namespace App\Http\Controllers;

use App\Models\Audit;
use App\Models\ConnectedAccount;
use App\Services\Google\GoogleSeoClientFactory;
use App\Services\Google\SearchConsoleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AuditGscController extends Controller
{
    public function properties(int $id)
    {
        $audit = $this->loadOwnedAudit($id);
        $account = $this->activeGoogleSeoAccount();

        if (! $account) {
            return response()->json([
                'connected' => false,
                'sites' => [],
                'selected_site_url' => null,
                'message' => 'Search Console account is not connected.',
            ]);
        }

        $gsc = new SearchConsoleService($account);
        $sites = $gsc->listSites();

        $selected = data_get($audit->audit_kpis, 'gsc.selected_site_url')
            ?: data_get($audit->audit_kpis, 'gsc.site_url')
            ?: $this->deriveDefaultSiteUrl($sites, $audit->normalized_url);

        return response()->json([
            'connected' => true,
            'sites' => $sites,
            'selected_site_url' => $selected,
        ]);
    }

    public function selectProperty(Request $request, int $id)
    {
        $audit = $this->loadOwnedAudit($id);
        $account = $this->activeGoogleSeoAccount();

        if (! $account) {
            return response()->json([
                'error' => 'Search Console account is not connected.',
            ], 400);
        }

        $validated = $request->validate([
            'site_url' => ['required', 'string', 'max:512'],
        ]);

        $gsc = new SearchConsoleService($account);
        $sites = collect($gsc->listSites());
        $selectedSite = $validated['site_url'];

        if (! $sites->contains(fn ($site) => ($site['siteUrl'] ?? null) === $selectedSite)) {
            return response()->json([
                'error' => 'Selected property is not available in your Search Console account.',
            ], 422);
        }

        $kpis = $audit->audit_kpis ?? [];
        $kpis['gsc'] = $kpis['gsc'] ?? [];
        $kpis['gsc']['connected'] = true;
        $kpis['gsc']['selected_site_url'] = $selectedSite;
        $kpis['gsc']['site_url'] = $selectedSite;
        $audit->audit_kpis = $kpis;
        $audit->save();

        return response()->json([
            'selected_site_url' => $selectedSite,
            'message' => 'Search Console property selected.',
        ]);
    }

    public function sync(Request $request, int $id)
    {
        $audit = $this->loadOwnedAudit($id);
        $account = $this->activeGoogleSeoAccount();

        if (! $account) {
            return response()->json([
                'error' => 'Search Console account is not connected.',
            ], 400);
        }

        $validated = $request->validate([
            'site_url' => ['nullable', 'string', 'max:512'],
        ]);

        $gsc = new SearchConsoleService($account);
        $sites = $gsc->listSites();

        $siteUrl = $validated['site_url']
            ?? data_get($audit->audit_kpis, 'gsc.selected_site_url')
            ?? data_get($audit->audit_kpis, 'gsc.site_url')
            ?? $this->deriveDefaultSiteUrl($sites, $audit->normalized_url);

        if (! $siteUrl) {
            return response()->json([
                'error' => 'No matching Search Console property found for this audit.',
            ], 422);
        }

        $isKnownSite = collect($sites)->contains(fn ($site) => ($site['siteUrl'] ?? null) === $siteUrl);
        if (! $isKnownSite) {
            return response()->json([
                'error' => 'Selected Search Console property is no longer available.',
            ], 422);
        }

        $endDate = now()->subDay();
        $startDate = now()->subDays(28);

        try {
            $dailyMetrics = $gsc->fetchDailyMetrics($siteUrl, $startDate, $endDate);
            $topQueries = $gsc->fetchTopQueries($siteUrl, $startDate, $endDate, 20);
            $topPages = $gsc->fetchTopPages($siteUrl, $startDate, $endDate, 20);
            $indexCoverage = $this->fetchIndexCoverageSummary($account, $siteUrl, $topPages);

            $totalClicks = array_sum(array_column($dailyMetrics, 'clicks'));
            $totalImpressions = array_sum(array_column($dailyMetrics, 'impressions'));
            $avgCtr = $totalImpressions > 0 ? round(($totalClicks / $totalImpressions) * 100, 2) : 0;
            $avgPosition = ! empty($dailyMetrics)
                ? round(array_sum(array_column($dailyMetrics, 'position')) / count($dailyMetrics), 1)
                : 0;

            $gscPayload = [
                'connected' => true,
                'site_url' => $siteUrl,
                'selected_site_url' => $siteUrl,
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

            $kpis = $audit->audit_kpis ?? [];
            $kpis['gsc'] = $gscPayload;
            $audit->audit_kpis = $kpis;
            $audit->gsc_ready_at = now();
            $audit->save();

            return response()->json([
                'gsc' => $gscPayload,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Audit GSC sync failed', [
                'audit_id' => $audit->id,
                'site_url' => $siteUrl,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Search Console sync failed: ' . $e->getMessage(),
            ], 500);
        }
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

    protected function activeGoogleSeoAccount(): ?ConnectedAccount
    {
        return ConnectedAccount::where('user_id', Auth::id())
            ->where('provider', 'google')
            ->where('service', 'seo')
            ->where('status', 'active')
            ->first();
    }

    protected function loadOwnedAudit(int $id): Audit
    {
        return Audit::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();
    }
}

