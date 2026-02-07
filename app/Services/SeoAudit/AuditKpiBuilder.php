<?php

namespace App\Services\SeoAudit;

use App\Models\Audit;
use Illuminate\Support\Facades\Http;
use App\Services\SeoAudit\SitemapDiscovery;
use App\Services\SeoAudit\RulesEngine;
use App\Services\SeoAudit\TextAnalyzer;

class AuditKpiBuilder
{
    public function build(Audit $audit): array
    {
        // KPI payload is computed here and stored in audits.audit_kpis by the finalize jobs.
        $pages = $audit->pages()->get();
        $homepage = $pages->firstWhere('url', $audit->normalized_url) ?? $pages->first();
        $issues = $audit->issues()->get();
        $links = $audit->links()->get();
        $assets = $audit->assets()->get();

        $categoryScores = $audit->category_scores ?? [];
        $categoryGrades = $this->categoryGrades($categoryScores);

        $siteSignals = $this->collectSiteSignals($audit);

        $keywordsText = $this->buildKeywordText($homepage);
        $topKeywords = TextAnalyzer::topKeywords($keywordsText, 10);
        $phrases = TextAnalyzer::topPhrases($homepage?->content_excerpt ?? '', 10, 2);
        $keywordConsistencyFlag = $this->keywordConsistency($topKeywords, $homepage?->title, $homepage?->h1_text);

        $schemaTypes = $pages->pluck('schema_types')->filter()->flatten()->unique()->values()->toArray();
        $schemaDetected = !empty($schemaTypes);

        $onPage = [
            'title_tag_text' => $homepage?->title,
            'title_length' => $homepage?->title_len,
            'meta_description_text' => $homepage?->meta_description,
            'meta_description_length' => $homepage?->meta_len,
            'hreflang_used' => $pages->where('hreflang_present', true)->count() > 0,
            'lang_declared' => $homepage?->lang,
            'h1_present' => ($homepage?->h1_count ?? 0) > 0,
            'header_tag_frequency' => [
                'h2_count' => $homepage?->h2_count,
                'h3_count' => $homepage?->h3_count,
                'h4_count' => $homepage?->h4_count,
                'h5_count' => $homepage?->h5_count,
                'h6_count' => $homepage?->h6_count,
            ],
            'keyword_consistency_flag' => $keywordConsistencyFlag,
            'top_keywords' => $topKeywords,
            'phrases' => $phrases,
            'content_word_count' => $homepage?->word_count,
            'images_missing_alt_count' => $homepage?->images_missing_alt,
            'canonical_tag_present' => !empty($homepage?->canonical_url),
            'canonical_url' => $homepage?->canonical_url,
            'noindex_meta_present' => $this->hasNoIndexMeta($homepage?->robots_meta),
            'noindex_header_present' => $this->hasNoIndexHeader($homepage?->x_robots_tag),
            'ssl_enabled' => str_starts_with($audit->normalized_url, 'https://'),
            'https_redirect_ok' => $siteSignals['https_redirect_ok'],
            'robots_txt_present' => $siteSignals['robots_txt_present'],
            'robots_txt_url' => $siteSignals['robots_txt_url'],
            'blocked_by_robots' => $siteSignals['blocked_by_robots'],
            'llms_txt_present' => $siteSignals['llms_txt_present'],
            'llms_txt_url' => $siteSignals['llms_txt_url'],
            'xml_sitemap_present' => $siteSignals['xml_sitemap_present'],
            'sitemap_url' => $siteSignals['sitemap_url'],
            'analytics_detected' => !empty($homepage?->analytics_tool),
            'analytics_tool_name' => $homepage?->analytics_tool,
            'schema_detected' => $schemaDetected,
            'schema_format' => $schemaDetected ? 'JSON-LD' : null,
            'schema_types' => $schemaTypes,
            'rendered_content_percentage' => null,
        ];

        $linkTotals = $this->buildLinkTotals($links);
        $linksSection = [
            'backlink_summary_text_flag' => 'not_available',
            'domain_strength' => null,
            'page_strength' => null,
            'total_backlinks_count' => null,
            'referring_domains_count' => null,
            'nofollow_backlinks_count' => null,
            'dofollow_backlinks_count' => null,
            'edu_backlinks_count' => null,
            'gov_backlinks_count' => null,
            'ips_count' => null,
            'subnets_count' => null,
            'top_backlinks_table' => [],
            'top_pages_by_backlinks' => [],
            'top_anchors_by_backlinks' => [],
            'top_referring_tlds' => [],
            'top_referring_countries' => [],
            'on_page_link_structure' => $linkTotals,
            'friendly_links_issue_flag' => $linkTotals['friendly_links_issue_flag'],
        ];

        $performance = $this->buildPerformance($audit, $pages, $assets);
        $usability = $this->buildUsability($homepage, $performance);
        $social = $this->buildSocial($homepage);
        $local = $this->buildLocal($homepage, $schemaTypes);
        $techEmail = $this->buildTechEmail($homepage, $siteSignals);
        $technical = $this->buildTechnical($audit, $pages, $links, $siteSignals, $schemaDetected);

        return [
            'overview' => [
                'overall_grade' => $audit->overall_grade,
                'category_grades' => [
                    'on_page_seo_grade' => $categoryGrades['onpage'] ?? null,
                    'links_grade' => $categoryGrades['links'] ?? null,
                    'usability_grade' => $categoryGrades['usability'] ?? null,
                    'performance_grade' => $categoryGrades['performance'] ?? null,
                    'social_grade' => $categoryGrades['social'] ?? null,
                    'technical_grade' => $categoryGrades['technical'] ?? null,
                    'security_grade' => $categoryGrades['security'] ?? null,
                ],
                'recommendations_count' => $issues->count(),
                'report_generated_datetime_utc' => now()->utc()->toIso8601String(),
                'pages_crawled_count' => $audit->pages_scanned ?? $pages->count(),
                'crawl_depth_used' => $audit->crawl_depth,
            ],
            'on_page_seo' => $onPage,
            'links' => $linksSection,
            'technical' => $technical,
            'performance' => $performance,
            'usability' => $usability,
            'social' => $social,
            'local_seo' => $local,
            'tech_email' => $techEmail,
        ];
    }

    public function categoryGrades(array $scores): array
    {
        $engine = new RulesEngine();
        $grades = [];
        foreach ($scores as $category => $score) {
            $grades[$category] = $engine->scoreToGrade((int) $score);
        }
        return $grades;
    }

    protected function buildKeywordText($homepage): string
    {
        if (!$homepage) {
            return '';
        }
        return trim(implode(' ', array_filter([
            $homepage->title,
            $homepage->meta_description,
            $homepage->h1_text,
            $homepage->content_excerpt,
        ])));
    }

    protected function keywordConsistency(array $topKeywords, ?string $title, ?string $h1): ?bool
    {
        if (empty($topKeywords)) {
            return null;
        }
        $primary = $topKeywords[0]['keyword'] ?? null;
        if (!$primary) {
            return null;
        }
        $titleMatch = $title ? str_contains(strtolower($title), strtolower($primary)) : false;
        $h1Match = $h1 ? str_contains(strtolower($h1), strtolower($primary)) : false;
        return $titleMatch && $h1Match;
    }

    protected function hasNoIndexMeta(?string $robotsMeta): ?bool
    {
        if ($robotsMeta === null) {
            return null;
        }
        return str_contains(strtolower($robotsMeta), 'noindex');
    }

    protected function hasNoIndexHeader(?string $xRobots): ?bool
    {
        if ($xRobots === null) {
            return null;
        }
        return str_contains(strtolower($xRobots), 'noindex');
    }

    protected function buildLinkTotals($links): array
    {
        $total = $links->count();
        $internal = $links->where('type', 'internal')->count();
        $external = $links->where('type', 'external')->count();
        $externalFollow = $links->where('type', 'external')->where('rel_nofollow', false)->count();
        $externalNofollow = $links->where('type', 'external')->where('rel_nofollow', true)->count();

        $externalPercent = $total > 0 ? round(($external / $total) * 100, 2) : null;
        $nofollowPercent = $external > 0 ? round(($externalNofollow / $external) * 100, 2) : null;

        $friendlyIssue = $this->friendlyLinksIssue($links);

        return [
            'total_links' => $total,
            'internal_links' => $internal,
            'external_links_follow' => $externalFollow,
            'external_links_nofollow' => $externalNofollow,
            'external_links_percent' => $externalPercent,
            'nofollow_links_percent' => $nofollowPercent,
            'friendly_links_issue_flag' => $friendlyIssue,
        ];
    }

    protected function friendlyLinksIssue($links): bool
    {
        $internalLinks = $links->where('type', 'internal');
        if ($internalLinks->isEmpty()) {
            return false;
        }
        $queryCount = $internalLinks->filter(function ($link) {
            return str_contains($link->to_url ?? '', '?');
        })->count();
        $ratio = $queryCount / $internalLinks->count();
        return $ratio > 0.3;
    }

    protected function buildPerformance(Audit $audit, $pages, $assets): array
    {
        $homepage = $pages->firstWhere('url', $audit->normalized_url) ?? $pages->first();
        $metrics = $homepage?->performance_metrics ?? [];

        $mobile = $metrics['mobile'] ?? [];
        $desktop = $metrics['desktop'] ?? [];

        $totalHtmlBytes = $pages->sum('html_size_bytes');
        $totalAssetBytes = $assets->sum('size_bytes');
        $totalBytes = ($totalHtmlBytes ?: 0) + ($totalAssetBytes ?: 0);

        $breakdown = [
            'html_mb' => $this->bytesToMb($totalHtmlBytes),
            'css_mb' => $this->bytesToMb($assets->where('type', 'css')->sum('size_bytes')),
            'js_mb' => $this->bytesToMb($assets->where('type', 'js')->sum('size_bytes')),
            'images_mb' => $this->bytesToMb($assets->where('type', 'img')->sum('size_bytes')),
            'other_mb' => $this->bytesToMb($assets->where('type', 'other')->sum('size_bytes') + $assets->where('type', 'font')->sum('size_bytes')),
        ];

        $resources = [
            'total_objects' => $assets->count(),
            'html_pages_count' => $pages->count(),
            'js_resources_count' => $assets->where('type', 'js')->count(),
            'css_resources_count' => $assets->where('type', 'css')->count(),
            'images_count' => $assets->where('type', 'img')->count(),
            'other_resources_count' => $assets->whereIn('type', ['font', 'other'])->count(),
        ];

        return [
            'website_load_timeline' => [
                'server_response_sec' => null,
                'all_page_content_loaded_sec' => isset($mobile['lcp']) ? round($mobile['lcp'] / 1000, 2) : null,
                'all_page_scripts_complete_sec' => isset($mobile['tti']) ? round($mobile['tti'] / 1000, 2) : null,
            ],
            'total_download_size_mb' => $this->bytesToMb($totalBytes),
            'download_size_breakdown_mb' => $breakdown,
            'compression_usage_ok' => null,
            'compression_rate_summary' => [
                'html_percent' => null,
                'css_percent' => null,
                'js_percent' => null,
                'images_percent' => null,
                'other_percent' => null,
                'total_percent' => null,
                'total_original_mb' => null,
            ],
            'resources_breakdown' => $resources,
            'amp_enabled' => null,
            'js_errors_detected' => null,
            'http2_enabled' => null,
            'minification_ok' => null,
        ];
    }

    protected function buildUsability($homepage, array $performance): array
    {
        $metrics = $homepage?->performance_metrics ?? [];
        $mobile = $metrics['mobile'] ?? [];
        $desktop = $metrics['desktop'] ?? [];

        return [
            'core_web_vitals_real_world_data_status' => null,
            'viewport_configured' => $homepage?->viewport_present,
            'pagespeed_mobile_score' => $mobile['score'] ?? null,
            'pagespeed_desktop_score' => $desktop['score'] ?? null,
            'mobile_lab_metrics' => $this->labMetricsToSeconds($mobile),
            'mobile_opportunities' => $this->mapOpportunities($mobile['opportunities'] ?? []),
            'desktop_lab_metrics' => $this->labMetricsToSeconds($desktop),
            'desktop_opportunities' => $this->mapOpportunities($desktop['opportunities'] ?? []),
            'favicon_present' => $homepage?->favicon_present,
            'tap_target_ok' => $mobile['tap_targets_ok'] ?? null,
            'font_legible' => $mobile['font_size_ok'] ?? null,
            'iframes_used' => ($homepage?->iframes_count ?? 0) > 0,
            'flash_used' => $homepage?->flash_used,
        ];
    }

    protected function buildSocial($homepage): array
    {
        $links = $homepage?->social_links ?? [];
        return [
            'facebook_page_linked' => !empty($links['facebook']),
            'facebook_url' => $links['facebook'] ?? null,
            'open_graph_tags_present' => $homepage?->og_present,
            'facebook_pixel_present' => $homepage?->analytics_tool === 'Facebook Pixel',
            'pixel_id' => null,
            'x_profile_linked' => !empty($links['x']),
            'x_url' => $links['x'] ?? null,
            'x_cards_present' => $homepage?->twitter_cards_present,
            'instagram_linked' => !empty($links['instagram']),
            'instagram_url' => $links['instagram'] ?? null,
            'linkedin_linked' => !empty($links['linkedin']),
            'linkedin_url' => $links['linkedin'] ?? null,
            'youtube_channel_linked' => !empty($links['youtube']),
            'youtube_url' => $links['youtube'] ?? null,
            'youtube_activity_detected' => null,
        ];
    }

    protected function buildLocal($homepage, array $schemaTypes): array
    {
        $text = $homepage?->content_excerpt ?? '';
        $addressFound = (bool) preg_match('/\\d+\\s+[^,\\n]+(street|st\\.|road|rd\\.|avenue|ave\\.|lane|ln\\.|boulevard|blvd\\.|drive|dr\\.|block|sector)/i', $text);
        $phoneFound = (bool) preg_match('/(\\+?\\d[\\d\\s\\-\\(\\)]{6,})/', $text);
        $localSchema = collect($schemaTypes)->contains(fn($type) => stripos($type, 'LocalBusiness') !== false);

        return [
            'address_found' => $addressFound,
            'phone_found' => $phoneFound,
            'local_business_schema_present' => $localSchema,
            'google_business_profile_identified' => null,
        ];
    }

    protected function buildTechEmail($homepage, array $siteSignals): array
    {
        $technologies = array_values(array_filter([
            $homepage?->analytics_tool ? $homepage->analytics_tool : null,
            $homepage?->x_powered_by ? $homepage->x_powered_by : null,
            $homepage?->server_header ? $homepage->server_header : null,
        ]));

        return [
            'detected_technologies' => array_map(fn($tech) => ['name' => $tech, 'version' => null], $technologies),
            'web_server' => $homepage?->server_header,
            'server_ip' => $siteSignals['server_ip'],
            'charset' => $homepage?->charset,
            'dmarc_present' => $siteSignals['dmarc_present'],
            'dmarc_record' => $siteSignals['dmarc_record'],
            'spf_present' => $siteSignals['spf_present'],
            'spf_record' => $siteSignals['spf_record'],
        ];
    }

    protected function buildTechnical(Audit $audit, $pages, $links, array $siteSignals, bool $schemaDetected): array
    {
        $statusDist = [
            '2xx' => $pages->filter(fn($p) => $p->status_code >= 200 && $p->status_code < 300)->count(),
            '3xx' => $pages->filter(fn($p) => $p->status_code >= 300 && $p->status_code < 400)->count(),
            '4xx' => $pages->filter(fn($p) => $p->status_code >= 400 && $p->status_code < 500)->count(),
            '5xx' => $pages->filter(fn($p) => $p->status_code >= 500)->count(),
        ];

        $brokenLinks = $links->where('is_broken', true);
        $brokenSamples = $brokenLinks->take(10)->map(fn($link) => [
            'from_url' => $link->from_url,
            'to_url' => $link->to_url,
            'status_code' => $link->status_code,
        ])->toArray();

        $redirectChains = $links->where('redirect_hops', '>=', 2);
        $redirectSamples = $redirectChains->take(10)->map(fn($link) => [
            'from_url' => $link->from_url,
            'to_url' => $link->to_url,
            'redirect_hops' => $link->redirect_hops,
        ])->toArray();

        $securitySummary = $this->summarizeSecurityHeaders($pages);

        return [
            'https_enabled' => str_starts_with($audit->normalized_url, 'https://'),
            'https_redirect_ok' => $siteSignals['https_redirect_ok'],
            'robots_txt_present' => $siteSignals['robots_txt_present'],
            'robots_txt_url' => $siteSignals['robots_txt_url'],
            'blocked_by_robots' => $siteSignals['blocked_by_robots'],
            'xml_sitemap_present' => $siteSignals['xml_sitemap_present'],
            'sitemap_url' => $siteSignals['sitemap_url'],
            'broken_links_count' => $brokenLinks->count(),
            'broken_links_examples' => $brokenSamples,
            'redirect_chains_count' => $redirectChains->count(),
            'redirect_chains_examples' => $redirectSamples,
            'status_code_distribution' => $statusDist,
            'structured_data_detected' => $schemaDetected,
            'analytics_detected' => $pages->whereNotNull('analytics_tool')->count() > 0,
            'favicon_present' => $pages->where('favicon_present', true)->count() > 0,
            'security_headers' => $securitySummary,
        ];
    }

    protected function summarizeSecurityHeaders($pages): array
    {
        $summary = [
            'hsts' => 0,
            'x_frame_options' => 0,
            'x_content_type_options' => 0,
            'referrer_policy' => 0,
            'permissions_policy' => 0,
            'csp' => 0,
            'pages_with_headers' => 0,
        ];

        foreach ($pages as $page) {
            $headers = $page->security_headers ?? null;
            if (!$headers) {
                continue;
            }
            $summary['pages_with_headers']++;
            foreach (['hsts','x_frame_options','x_content_type_options','referrer_policy','permissions_policy','csp'] as $key) {
                if (!empty($headers[$key])) {
                    $summary[$key]++;
                }
            }
        }

        return $summary;
    }

    protected function collectSiteSignals(Audit $audit): array
    {
        $baseUrl = $this->baseUrl($audit->normalized_url);
        $robotsUrl = $baseUrl . '/robots.txt';
        $robotsTxtPresent = false;
        $blockedByRobots = null;
        $sitemapUrl = null;

        try {
            $robotsResponse = Http::timeout(10)->get($robotsUrl);
            if ($robotsResponse->successful()) {
                $robotsTxtPresent = true;
                $robotsBody = $robotsResponse->body();
                $blockedByRobots = $this->robotsDisallowAll($robotsBody);
                $sitemapUrl = $this->extractFirstSitemap($robotsBody);
            }
        } catch (\Exception $e) {
            $robotsTxtPresent = false;
        }

        $sitemaps = SitemapDiscovery::discoverSitemaps($audit->normalized_url);
        if (!$sitemapUrl && !empty($sitemaps)) {
            $sitemapUrl = $sitemaps[0];
        }

        $llmsUrl = $baseUrl . '/llms.txt';
        $llmsPresent = false;
        try {
            $llmsResp = Http::timeout(6)->get($llmsUrl);
            $llmsPresent = $llmsResp->successful();
        } catch (\Exception $e) {
            $llmsPresent = false;
        }

        $httpsRedirectOk = $this->checkHttpsRedirect($audit->normalized_url);

        $host = parse_url($audit->normalized_url, PHP_URL_HOST);
        $serverIp = $host ? gethostbyname($host) : null;

        [$spfPresent, $spfRecord] = $this->checkSpf($host);
        [$dmarcPresent, $dmarcRecord] = $this->checkDmarc($host);

        return [
            'robots_txt_present' => $robotsTxtPresent,
            'robots_txt_url' => $robotsTxtPresent ? $robotsUrl : null,
            'blocked_by_robots' => $blockedByRobots,
            'xml_sitemap_present' => !empty($sitemapUrl),
            'sitemap_url' => $sitemapUrl,
            'llms_txt_present' => $llmsPresent,
            'llms_txt_url' => $llmsPresent ? $llmsUrl : null,
            'https_redirect_ok' => $httpsRedirectOk,
            'server_ip' => $serverIp,
            'spf_present' => $spfPresent,
            'spf_record' => $spfRecord,
            'dmarc_present' => $dmarcPresent,
            'dmarc_record' => $dmarcRecord,
        ];
    }

    protected function robotsDisallowAll(string $robotsTxt): bool
    {
        $lines = preg_split('/\\r?\\n/', $robotsTxt);
        $currentAgent = null;
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (stripos($line, 'User-agent:') === 0) {
                $currentAgent = trim(substr($line, strlen('User-agent:')));
                continue;
            }
            if ($currentAgent === '*' && stripos($line, 'Disallow:') === 0) {
                $value = trim(substr($line, strlen('Disallow:')));
                if ($value === '/') {
                    return true;
                }
            }
        }
        return false;
    }

    protected function extractFirstSitemap(string $robotsTxt): ?string
    {
        $lines = preg_split('/\\r?\\n/', $robotsTxt);
        foreach ($lines as $line) {
            if (preg_match('/^Sitemap:\\s*(.+)$/i', trim($line), $matches)) {
                $url = trim($matches[1]);
                if (filter_var($url, FILTER_VALIDATE_URL)) {
                    return $url;
                }
            }
        }
        return null;
    }

    protected function checkHttpsRedirect(string $url): ?bool
    {
        $parsed = parse_url($url);
        if (!$parsed || empty($parsed['host'])) {
            return null;
        }
        $httpUrl = 'http://' . $parsed['host'] . ($parsed['path'] ?? '/');
        try {
            $response = Http::timeout(6)->withoutRedirecting()->head($httpUrl);
            if (in_array($response->status(), [301, 302, 307, 308], true)) {
                $location = $response->header('Location') ?? '';
                return str_starts_with(strtolower($location), 'https://');
            }
            return false;
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function checkSpf(?string $host): array
    {
        if (!$host) {
            return [null, null];
        }
        $records = dns_get_record($host, DNS_TXT) ?: [];
        foreach ($records as $record) {
            $txt = $record['txt'] ?? '';
            if (stripos($txt, 'v=spf1') !== false) {
                return [true, $txt];
            }
        }
        return [false, null];
    }

    protected function checkDmarc(?string $host): array
    {
        if (!$host) {
            return [null, null];
        }
        $records = dns_get_record('_dmarc.' . $host, DNS_TXT) ?: [];
        foreach ($records as $record) {
            $txt = $record['txt'] ?? '';
            if (stripos($txt, 'v=DMARC1') !== false) {
                return [true, $txt];
            }
        }
        return [false, null];
    }

    protected function mapOpportunities(array $opportunities): array
    {
        return collect($opportunities)->map(function ($item) {
            $savingsSec = null;
            if (($item['savingsType'] ?? null) === 'ms' && isset($item['savings'])) {
                $savingsSec = round($item['savings'] / 1000, 2);
            }
            return [
                'name' => $item['title'] ?? $item['id'] ?? 'Opportunity',
                'estimated_savings_sec' => $savingsSec,
            ];
        })->toArray();
    }

    protected function labMetricsToSeconds(array $metrics): ?array
    {
        if (empty($metrics)) {
            return null;
        }
        return [
            'fcp_sec' => isset($metrics['fcp']) ? round($metrics['fcp'] / 1000, 2) : null,
            'speed_index_sec' => isset($metrics['si']) ? round($metrics['si'] / 1000, 2) : null,
            'lcp_sec' => isset($metrics['lcp']) ? round($metrics['lcp'] / 1000, 2) : null,
            'tti_sec' => isset($metrics['tti']) ? round($metrics['tti'] / 1000, 2) : null,
            'tbt_sec' => isset($metrics['tbt']) ? round($metrics['tbt'] / 1000, 2) : null,
            'cls' => $metrics['cls'] ?? null,
        ];
    }

    protected function bytesToMb(?int $bytes): ?float
    {
        if (!$bytes) {
            return $bytes === 0 ? 0.0 : null;
        }
        return round($bytes / 1024 / 1024, 2);
    }

    protected function baseUrl(string $url): string
    {
        $parsed = parse_url($url);
        $scheme = $parsed['scheme'] ?? 'https';
        $host = $parsed['host'] ?? '';
        return $scheme . '://' . $host;
    }
}
