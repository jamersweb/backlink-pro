<?php

namespace App\Services\IndexCrawl;

use App\Models\Domain;
use App\Models\IndexCrawlIssue;
use App\Models\IndexCrawlRun;
use App\Models\IndexCrawlSitemap;
use App\Models\IndexCrawlUrl;
use App\Services\Audits\HtmlExtractor;
use App\Services\Audits\UrlNormalizer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class IndexCrawlAnalyzer
{
    public function __construct(
        protected RobotsTxtService $robotsTxtService,
        protected SitemapDiscoveryService $sitemapDiscoveryService
    ) {
    }

    public function execute(IndexCrawlRun $run, Domain $domain, array $settings = []): array
    {
        $maxUrls = (int) ($settings['max_urls'] ?? 250);
        $maxDepth = (int) ($settings['max_depth'] ?? 3);
        $respectRobots = (bool) ($settings['respect_robots'] ?? true);
        $timeout = (int) ($settings['timeout_seconds'] ?? 20);
        $userAgent = (string) ($settings['user_agent'] ?? 'BacklinkProBot/1.0');

        $startUrl = $this->normalizeBaseUrl($domain);
        $baseHost = parse_url($startUrl, PHP_URL_HOST) ?: $domain->host;
        $baseHost = Str::lower((string) preg_replace('/^www\./i', '', $baseHost));

        $robotsUrl = rtrim($startUrl, '/') . '/robots.txt';
        $robotsContent = '';
        $robotsParsed = ['rules' => [], 'sitemaps' => []];

        try {
            $robotsResponse = Http::timeout($timeout)->withHeaders(['User-Agent' => $userAgent])->get($robotsUrl);
            if ($robotsResponse->successful()) {
                $robotsContent = $robotsResponse->body();
                $robotsParsed = $this->robotsTxtService->parse($robotsContent);
            }
        } catch (\Throwable $e) {
            // Robots failures should not stop crawl execution.
        }

        $sitemapScan = $this->sitemapDiscoveryService->discover($startUrl, $baseHost, $robotsParsed['sitemaps'] ?? [], max($maxUrls * 2, 200));
        $sitemapUrls = collect($sitemapScan['urls'] ?? [])->take(max($maxUrls * 2, 200))->values()->all();
        $sitemapLookup = array_fill_keys($sitemapUrls, true);

        $queue = [
            ['url' => UrlNormalizer::normalize($startUrl, $baseHost), 'depth' => 0, 'source' => 'crawl'],
        ];

        foreach (array_slice($sitemapUrls, 0, min(count($sitemapUrls), max($maxUrls, 50))) as $sitemapUrl) {
            $queue[] = ['url' => $sitemapUrl, 'depth' => 0, 'source' => 'sitemap'];
        }

        $seen = [];
        $rows = [];
        $inlinksMap = [];
        $outlinksMap = [];
        $crawlCount = 0;

        while (!empty($queue) && count($seen) < $maxUrls) {
            $item = array_shift($queue);
            $url = $item['url'] ?? null;
            $depth = (int) ($item['depth'] ?? 0);
            $source = (string) ($item['source'] ?? 'crawl');

            if (!$url || isset($seen[$url])) {
                continue;
            }

            $seen[$url] = true;
            $parsedUrl = parse_url($url);
            $pathForRobots = ($parsedUrl['path'] ?? '/') . (isset($parsedUrl['query']) ? '?' . $parsedUrl['query'] : '');
            $isRobotsBlocked = $this->robotsTxtService->isBlocked($pathForRobots, $robotsParsed);

            $row = [
                'url' => $url,
                'normalized_url' => $url,
                'normalized_url_hash' => hash('sha256', $url),
                'source_found_from' => $source,
                'final_url' => null,
                'status_code' => null,
                'content_type' => null,
                'is_html' => false,
                'title' => null,
                'meta_description' => null,
                'canonical_url' => null,
                'meta_robots' => null,
                'x_robots_tag' => null,
                'is_blocked_by_robots' => $isRobotsBlocked,
                'is_noindex' => false,
                'is_in_sitemap' => isset($sitemapLookup[$url]),
                'click_depth' => $depth,
                'internal_inlinks_count' => 0,
                'internal_outlinks_count' => 0,
                'crawlability_status' => 'crawlable',
                'indexability_status' => 'indexable',
                'issue_flags_json' => [],
                'last_seen_at' => now(),
            ];

            if ($isRobotsBlocked && $respectRobots) {
                $row['crawlability_status'] = 'blocked_by_robots';
                $row['indexability_status'] = 'blocked_from_crawl';
                $row['issue_flags_json'][] = 'blocked_by_robots';
                $rows[$url] = $row;
                continue;
            }

            try {
                $response = Http::timeout($timeout)
                    ->retry(1, 120)
                    ->withHeaders(['User-Agent' => $userAgent, 'Accept' => 'text/html,application/xhtml+xml'])
                    ->withOptions([
                        'allow_redirects' => [
                            'max' => 6,
                            'strict' => true,
                            'referer' => true,
                            'protocols' => ['http', 'https'],
                        ],
                    ])
                    ->get($url);

                $crawlCount++;
                $statusCode = $response->status();
                $effectiveUri = (string) ($response->effectiveUri() ?? '');
                $finalUrl = UrlNormalizer::normalize($effectiveUri ?: $url, $baseHost) ?: $url;
                $contentType = (string) $response->header('Content-Type');
                $xRobots = (string) $response->header('X-Robots-Tag');
                $body = (string) $response->body();
                $isHtml = str_contains(Str::lower($contentType), 'text/html');

                $row['status_code'] = $statusCode;
                $row['final_url'] = $finalUrl === $url ? null : $finalUrl;
                $row['content_type'] = $contentType ?: null;
                $row['is_html'] = $isHtml;
                $row['x_robots_tag'] = $xRobots ?: null;

                if ($statusCode >= 500) {
                    $row['crawlability_status'] = 'server_error';
                    $row['indexability_status'] = 'server_error';
                    $row['issue_flags_json'][] = 'status_5xx';
                } elseif ($statusCode >= 400) {
                    $row['crawlability_status'] = 'client_error';
                    $row['indexability_status'] = 'not_found';
                    $row['issue_flags_json'][] = 'status_4xx';
                } elseif ($statusCode >= 300) {
                    $row['crawlability_status'] = 'redirected';
                    $row['indexability_status'] = 'redirect';
                    $row['issue_flags_json'][] = 'redirected';
                }

                if ($isHtml) {
                    $htmlData = HtmlExtractor::extract($body);
                    $row['title'] = $htmlData['title'] ?? null;
                    $row['meta_description'] = $htmlData['meta_description'] ?? null;
                    $row['meta_robots'] = $htmlData['robots_meta'] ?? null;
                    $canonical = $this->normalizeAgainstDomain($htmlData['canonical'] ?? null, $url, $baseHost);
                    $row['canonical_url'] = $canonical;

                    $metaRobots = Str::lower((string) ($row['meta_robots'] ?? ''));
                    $xRobotsLower = Str::lower((string) $xRobots);
                    if (str_contains($metaRobots, 'noindex') || str_contains($xRobotsLower, 'noindex')) {
                        $row['is_noindex'] = true;
                        $row['indexability_status'] = 'noindex';
                        $row['issue_flags_json'][] = 'noindex';
                    }

                    if ($canonical && $canonical !== $url) {
                        $row['indexability_status'] = 'canonicalized';
                        $row['issue_flags_json'][] = 'canonicalized_to_other';
                    }

                    if (($statusCode === 200) && (!$row['title'] || str_contains(Str::lower($row['title']), 'not found'))) {
                        $row['issue_flags_json'][] = 'soft_404_candidate';
                        if ($row['indexability_status'] === 'indexable') {
                            $row['indexability_status'] = 'soft_404_candidate';
                        }
                    }

                    $internalLinks = HtmlExtractor::extractInternalLinks($body, $baseHost);
                    $outlinksMap[$url] = count($internalLinks);
                    foreach ($internalLinks as $linkedUrl) {
                        $inlinksMap[$linkedUrl] = ($inlinksMap[$linkedUrl] ?? 0) + 1;
                        if (!isset($seen[$linkedUrl]) && $depth + 1 <= $maxDepth && count($seen) + count($queue) < ($maxUrls * 2)) {
                            $queue[] = ['url' => $linkedUrl, 'depth' => $depth + 1, 'source' => 'crawl'];
                        }
                    }
                } else {
                    $row['crawlability_status'] = $statusCode >= 400 ? $row['crawlability_status'] : 'non_html';
                    $row['indexability_status'] = $statusCode >= 400 ? $row['indexability_status'] : 'non_html';
                    $row['issue_flags_json'][] = 'non_html_resource';
                }

                if (str_contains($url, '?') && $this->queryParamCount($url) >= 3) {
                    $row['issue_flags_json'][] = 'parameter_clutter_candidate';
                }

                if ($depth >= max(3, $maxDepth)) {
                    $row['issue_flags_json'][] = 'deep_page';
                }
            } catch (\Throwable $e) {
                $row['crawlability_status'] = 'fetch_failed';
                $row['indexability_status'] = 'crawl_failed';
                $row['issue_flags_json'][] = 'fetch_failed';
            }

            $rows[$url] = $row;
        }

        foreach ($rows as $url => &$row) {
            $row['internal_inlinks_count'] = $inlinksMap[$url] ?? 0;
            $row['internal_outlinks_count'] = $outlinksMap[$url] ?? 0;

            if (($row['internal_inlinks_count'] ?? 0) === 0 && $url !== UrlNormalizer::normalize($startUrl, $baseHost)) {
                $row['issue_flags_json'][] = 'orphan_page';
            }

            if (($row['click_depth'] ?? 0) <= 1 && ($row['indexability_status'] ?? null) !== 'indexable') {
                $row['issue_flags_json'][] = 'important_page_not_indexable';
            }

            if (($row['is_in_sitemap'] ?? false) && in_array($row['indexability_status'], ['noindex', 'canonicalized', 'redirect', 'not_found', 'server_error'], true)) {
                $row['issue_flags_json'][] = 'sitemap_url_non_indexable';
            }

            $row['issue_flags_json'] = array_values(array_unique($row['issue_flags_json']));
        }
        unset($row);

        $titleGroups = [];
        foreach ($rows as $url => $row) {
            $title = trim((string) ($row['title'] ?? ''));
            if ($title !== '') {
                $titleGroups[Str::lower($title)][] = $url;
            }
        }
        foreach ($titleGroups as $groupUrls) {
            if (count($groupUrls) > 1) {
                foreach ($groupUrls as $url) {
                    $rows[$url]['issue_flags_json'][] = 'internal_duplicate_cluster_candidate';
                    $rows[$url]['issue_flags_json'] = array_values(array_unique($rows[$url]['issue_flags_json']));
                }
            }
        }

        $this->persistRunData($run, $domain, $rows, $sitemapScan);

        return $this->buildSummary($rows, $sitemapScan, $crawlCount);
    }

    protected function persistRunData(IndexCrawlRun $run, Domain $domain, array $rows, array $sitemapScan): void
    {
        IndexCrawlUrl::where('index_crawl_run_id', $run->id)->delete();
        IndexCrawlIssue::where('index_crawl_run_id', $run->id)->delete();
        IndexCrawlSitemap::where('index_crawl_run_id', $run->id)->delete();

        $now = now();
        $insertRows = [];
        foreach ($rows as $row) {
            $row['issue_flags_json'] = json_encode($row['issue_flags_json'] ?? []);
            $insertRows[] = array_merge($row, [
                'index_crawl_run_id' => $run->id,
                'domain_id' => $domain->id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach (array_chunk($insertRows, 200) as $chunk) {
            IndexCrawlUrl::insert($chunk);
        }

        $persistedUrls = IndexCrawlUrl::where('index_crawl_run_id', $run->id)->get()->keyBy('normalized_url');
        $sitemapRows = [];
        foreach (($sitemapScan['files'] ?? []) as $file) {
            $issues = $file['issues'] ?? [];
            $totalUrlsFound = count($file['urls'] ?? []);
            $validUrlsFound = 0;
            $non200 = 0;
            $redirected = 0;
            $noindex = 0;
            $nonCanonical = 0;
            $notLinked = 0;

            foreach (($file['urls'] ?? []) as $sitemapUrl) {
                $row = $persistedUrls->get($sitemapUrl);
                if (!$row) {
                    continue;
                }
                $validUrlsFound++;
                if (($row->status_code ?? 0) >= 300 && ($row->status_code ?? 0) < 400) {
                    $redirected++;
                }
                if (($row->status_code ?? 0) >= 400 || (($row->status_code ?? 0) > 0 && ($row->status_code ?? 0) !== 200)) {
                    $non200++;
                }
                if ($row->is_noindex) {
                    $noindex++;
                }
                if ($row->canonical_url && $row->canonical_url !== $row->normalized_url) {
                    $nonCanonical++;
                }
                if (($row->internal_inlinks_count ?? 0) === 0) {
                    $notLinked++;
                }
            }

            if ($redirected > 0) {
                $issues[] = 'redirected_urls_in_sitemap';
            }
            if ($non200 > 0) {
                $issues[] = 'non_200_urls_in_sitemap';
            }
            if ($noindex > 0) {
                $issues[] = 'noindex_urls_in_sitemap';
            }
            if ($nonCanonical > 0) {
                $issues[] = 'non_canonical_urls_in_sitemap';
            }
            if ($notLinked > 0) {
                $issues[] = 'sitemap_urls_not_linked_internally';
            }

            $penalty = ($non200 * 4) + ($redirected * 3) + ($noindex * 5) + ($nonCanonical * 3) + ($notLinked * 2);
            $health = max(0, min(100, 100 - $penalty));

            $sitemapRows[] = [
                'index_crawl_run_id' => $run->id,
                'domain_id' => $domain->id,
                'sitemap_url' => $file['sitemap_url'] ?? '',
                'type' => $file['type'] ?? 'sitemap',
                'fetch_status' => $file['fetch_status'] ?? 'failed',
                'total_urls_found' => $totalUrlsFound,
                'valid_urls_found' => $validUrlsFound,
                'health_score' => $health,
                'issues_json' => json_encode([
                    'keys' => array_values(array_unique($issues)),
                    'counts' => [
                        'non_200' => $non200,
                        'redirected' => $redirected,
                        'noindex' => $noindex,
                        'non_canonical' => $nonCanonical,
                        'not_linked_internally' => $notLinked,
                    ],
                ]),
                'fetched_at' => $file['fetched_at'] ?? $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($sitemapRows, 100) as $chunk) {
            IndexCrawlSitemap::insert($chunk);
        }

        $this->persistIssueGroups($run, $domain, $rows, $now);
    }

    protected function persistIssueGroups(IndexCrawlRun $run, Domain $domain, array $rows, $now): void
    {
        $issueCatalog = $this->issueCatalog();
        $grouped = [];

        foreach ($rows as $row) {
            foreach (($row['issue_flags_json'] ?? []) as $issueKey) {
                $grouped[$issueKey]['urls'][] = $row['normalized_url'];
            }
        }

        $insertIssues = [];
        foreach ($grouped as $issueKey => $payload) {
            $catalog = $issueCatalog[$issueKey] ?? [
                'name' => Str::headline(str_replace('_', ' ', $issueKey)),
                'severity' => 'low',
                'description' => 'Issue detected during crawl.',
                'recommendation' => 'Review affected URLs and apply technical fix.',
            ];
            $insertIssues[] = [
                'index_crawl_run_id' => $run->id,
                'domain_id' => $domain->id,
                'issue_key' => $issueKey,
                'issue_name' => $catalog['name'],
                'severity' => $catalog['severity'],
                'affected_urls_count' => count(array_unique($payload['urls'] ?? [])),
                'description' => $catalog['description'],
                'recommendation' => $catalog['recommendation'],
                'metadata_json' => json_encode([
                    'example_urls' => array_slice(array_values(array_unique($payload['urls'] ?? [])), 0, 5),
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($insertIssues, 100) as $chunk) {
            IndexCrawlIssue::insert($chunk);
        }
    }

    protected function buildSummary(array $rows, array $sitemapScan, int $crawlCount = 0): array
    {
        $total = count($rows);
        $crawlable = 0;
        $nonCrawlable = 0;
        $indexable = 0;
        $nonIndexable = 0;
        $inSitemap = 0;
        $notInSitemap = 0;
        $blockedByRobots = 0;
        $noindex = 0;
        $redirected = 0;
        $status404 = 0;
        $status5xx = 0;
        $orphan = 0;
        $issueTotals = ['critical' => 0, 'high' => 0, 'medium' => 0, 'low' => 0];
        $crawlabilityDistribution = [];
        $indexabilityDistribution = [];
        $depthDistribution = [];
        $issueDistribution = [];

        $issueCatalog = $this->issueCatalog();

        foreach ($rows as $row) {
            $crawlabilityDistribution[$row['crawlability_status']] = ($crawlabilityDistribution[$row['crawlability_status']] ?? 0) + 1;
            $indexabilityDistribution[$row['indexability_status']] = ($indexabilityDistribution[$row['indexability_status']] ?? 0) + 1;
            $depth = (string) ($row['click_depth'] ?? 0);
            $depthDistribution[$depth] = ($depthDistribution[$depth] ?? 0) + 1;

            if (($row['crawlability_status'] ?? '') === 'crawlable') {
                $crawlable++;
            } else {
                $nonCrawlable++;
            }

            if (($row['indexability_status'] ?? '') === 'indexable') {
                $indexable++;
            } else {
                $nonIndexable++;
            }

            if (!empty($row['is_in_sitemap'])) {
                $inSitemap++;
            } else {
                $notInSitemap++;
            }

            if (!empty($row['is_blocked_by_robots'])) {
                $blockedByRobots++;
            }
            if (!empty($row['is_noindex'])) {
                $noindex++;
            }
            if (($row['status_code'] ?? 0) >= 300 && ($row['status_code'] ?? 0) < 400) {
                $redirected++;
            }
            if (($row['status_code'] ?? 0) === 404) {
                $status404++;
            }
            if (($row['status_code'] ?? 0) >= 500) {
                $status5xx++;
            }
            if (in_array('orphan_page', $row['issue_flags_json'] ?? [], true)) {
                $orphan++;
            }

            foreach (($row['issue_flags_json'] ?? []) as $issueKey) {
                $issueDistribution[$issueKey] = ($issueDistribution[$issueKey] ?? 0) + 1;
                $severity = $issueCatalog[$issueKey]['severity'] ?? 'low';
                $issueTotals[$severity] = ($issueTotals[$severity] ?? 0) + 1;
            }
        }

        $crawlabilityHealth = $total > 0 ? (int) round(($crawlable / $total) * 100) : 100;
        $indexabilityHealth = $total > 0 ? (int) round(($indexable / $total) * 100) : 100;
        $internalDiscoveryHealth = $total > 0 ? (int) round(((max(0, $total - $orphan)) / $total) * 100) : 100;

        $sitemapScores = [];
        foreach (($sitemapScan['files'] ?? []) as $file) {
            $penalty = count($file['issues'] ?? []) * 15;
            $sitemapScores[] = max(0, 100 - $penalty);
        }
        $sitemapHealth = count($sitemapScores) > 0 ? (int) round(array_sum($sitemapScores) / count($sitemapScores)) : 100;

        $issueSeverityHealth = 100
            - min(50, ($issueTotals['critical'] * 8))
            - min(25, ($issueTotals['high'] * 4))
            - min(15, ($issueTotals['medium'] * 2))
            - min(10, ($issueTotals['low']));
        $issueSeverityHealth = max(0, $issueSeverityHealth);

        $score = (int) round(
            ($crawlabilityHealth * 0.30) +
            ($indexabilityHealth * 0.35) +
            ($sitemapHealth * 0.15) +
            ($internalDiscoveryHealth * 0.10) +
            ($issueSeverityHealth * 0.10)
        );

        return [
            'total_urls_discovered' => $total,
            'total_urls_crawled' => $crawlCount,
            'crawlable_urls' => $crawlable,
            'non_crawlable_urls' => $nonCrawlable,
            'indexable_urls' => $indexable,
            'non_indexable_urls' => $nonIndexable,
            'in_sitemap_urls' => $inSitemap,
            'not_in_sitemap_urls' => $notInSitemap,
            'blocked_by_robots_urls' => $blockedByRobots,
            'noindex_urls' => $noindex,
            'redirected_urls' => $redirected,
            'status_404_urls' => $status404,
            'status_5xx_urls' => $status5xx,
            'orphan_urls' => $orphan,
            'sitemaps_detected' => count($sitemapScan['files'] ?? []),
            'issue_totals' => $issueTotals,
            'score' => $score,
            'component_scores' => [
                'crawlability' => $crawlabilityHealth,
                'indexability' => $indexabilityHealth,
                'sitemap' => $sitemapHealth,
                'internal_discovery' => $internalDiscoveryHealth,
                'issue_severity_distribution' => $issueSeverityHealth,
            ],
            'distributions' => [
                'crawlability' => $crawlabilityDistribution,
                'indexability' => $indexabilityDistribution,
                'depth' => $depthDistribution,
                'issues' => $issueDistribution,
            ],
            'gsc_status_mapping_ready' => [
                'crawled_currently_not_indexed' => null,
                'discovered_currently_not_indexed' => null,
                'duplicate_without_user_selected_canonical' => null,
                'alternate_page_with_canonical' => null,
                'excluded_by_noindex' => $noindex,
                'blocked_by_robots_txt' => $blockedByRobots,
                'soft_404' => $issueDistribution['soft_404_candidate'] ?? 0,
                'page_with_redirect' => $redirected,
                'not_found_404' => $status404,
                'server_error_5xx' => $status5xx,
            ],
        ];
    }

    protected function normalizeBaseUrl(Domain $domain): string
    {
        $base = $domain->url ?: ('https://' . ltrim((string) $domain->host, '/'));
        if (!preg_match('/^https?:\/\//i', $base)) {
            $base = 'https://' . $base;
        }

        return rtrim($base, '/');
    }

    protected function normalizeAgainstDomain(?string $candidateUrl, string $currentUrl, string $baseHost): ?string
    {
        if (!$candidateUrl) {
            return null;
        }

        $resolved = $this->resolveUrl($candidateUrl, $currentUrl);

        return UrlNormalizer::normalize($resolved, $baseHost);
    }

    protected function resolveUrl(string $href, string $baseUrl): string
    {
        if (preg_match('/^https?:\/\//i', $href)) {
            return $href;
        }

        $base = parse_url($baseUrl);
        $scheme = $base['scheme'] ?? 'https';
        $host = $base['host'] ?? '';
        if ($host === '') {
            return $href;
        }

        if (str_starts_with($href, '//')) {
            return $scheme . ':' . $href;
        }
        if (str_starts_with($href, '/')) {
            return $scheme . '://' . $host . $href;
        }

        $path = $base['path'] ?? '/';
        $dir = str_contains($path, '/') ? substr($path, 0, strrpos($path, '/') + 1) : '/';

        return $scheme . '://' . $host . $dir . ltrim($href, '/');
    }

    protected function queryParamCount(string $url): int
    {
        $query = parse_url($url, PHP_URL_QUERY);
        if (!$query) {
            return 0;
        }
        parse_str($query, $params);

        return count($params);
    }

    protected function issueCatalog(): array
    {
        return [
            'blocked_by_robots' => [
                'name' => 'Blocked by robots.txt',
                'severity' => 'critical',
                'description' => 'These URLs are blocked from crawler access by robots.txt directives.',
                'recommendation' => 'Allow important URLs in robots.txt and keep only non-essential sections blocked.',
            ],
            'noindex' => [
                'name' => 'Noindex directive found',
                'severity' => 'high',
                'description' => 'Pages are carrying noindex directives and may be excluded from search results.',
                'recommendation' => 'Remove noindex from pages that should appear in Google index.',
            ],
            'redirected' => [
                'name' => 'Redirected URL',
                'severity' => 'medium',
                'description' => 'Pages redirect to other destinations and may reduce crawl efficiency.',
                'recommendation' => 'Link directly to final destination URLs and reduce redirects in sitemaps.',
            ],
            'status_4xx' => [
                'name' => 'Client error URL (4xx)',
                'severity' => 'critical',
                'description' => 'Pages return client errors and are not accessible to users or crawlers.',
                'recommendation' => 'Restore pages, fix internal links, or redirect obsolete URLs to relevant destinations.',
            ],
            'status_5xx' => [
                'name' => 'Server error URL (5xx)',
                'severity' => 'critical',
                'description' => 'Server-side failures prevent crawling and indexing.',
                'recommendation' => 'Investigate server logs, hosting stability, and application exceptions for these URLs.',
            ],
            'fetch_failed' => [
                'name' => 'URL fetch failed',
                'severity' => 'high',
                'description' => 'Crawler could not fetch these URLs due to timeout, network, DNS, or SSL issues.',
                'recommendation' => 'Verify DNS, SSL certificates, and origin uptime for failed URLs.',
            ],
            'canonicalized_to_other' => [
                'name' => 'Canonicalized to another URL',
                'severity' => 'medium',
                'description' => 'Canonical tag points to a different URL which may de-index this page.',
                'recommendation' => 'Ensure canonical URLs represent preferred indexable versions only.',
            ],
            'orphan_page' => [
                'name' => 'Orphan page',
                'severity' => 'high',
                'description' => 'Page has no internal inlinks and may be hard for crawlers to discover.',
                'recommendation' => 'Add contextual internal links from related pages and navigation paths.',
            ],
            'soft_404_candidate' => [
                'name' => 'Soft 404 candidate',
                'severity' => 'high',
                'description' => 'Page appears low-value or not-found while returning 200 status.',
                'recommendation' => 'Return proper 404/410 for removed content or add meaningful unique content.',
            ],
            'sitemap_url_non_indexable' => [
                'name' => 'Sitemap URL is non-indexable',
                'severity' => 'high',
                'description' => 'Sitemap includes URLs that are redirected, noindexed, canonicalized, or errored.',
                'recommendation' => 'Keep only canonical, indexable 200 pages inside sitemap files.',
            ],
            'important_page_not_indexable' => [
                'name' => 'Important page not indexable',
                'severity' => 'critical',
                'description' => 'Shallow, likely important URLs are currently non-indexable.',
                'recommendation' => 'Fix directives and technical blockers on key landing and conversion pages.',
            ],
            'deep_page' => [
                'name' => 'Deep page',
                'severity' => 'medium',
                'description' => 'Page requires many clicks to reach and may receive weak crawl priority.',
                'recommendation' => 'Improve internal architecture and bring key pages closer to top-level navigation.',
            ],
            'parameter_clutter_candidate' => [
                'name' => 'Parameterized URL clutter',
                'severity' => 'low',
                'description' => 'URL includes many parameters and could generate crawl waste or duplicates.',
                'recommendation' => 'Control crawl of faceted parameters and canonicalize to clean versions.',
            ],
            'non_html_resource' => [
                'name' => 'Non-HTML URL in inventory',
                'severity' => 'low',
                'description' => 'Inventory includes non-HTML resources that are not indexable pages.',
                'recommendation' => 'Exclude assets from SEO page inventory when focusing on indexable documents.',
            ],
            'internal_duplicate_cluster_candidate' => [
                'name' => 'Internal duplicate cluster candidate',
                'severity' => 'medium',
                'description' => 'Multiple pages share near-identical title signals and may compete for indexing.',
                'recommendation' => 'Consolidate duplicate pages or differentiate canonical targets and content intent.',
            ],
        ];
    }
}
