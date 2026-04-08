<?php

namespace App\Services\SeoAudit;

use App\Models\Audit;
use App\Models\AuditIssue;
use App\Models\AuditCustomSearchResult;
use App\Models\AuditCustomExtractionResult;
use Illuminate\Support\Facades\Http;
use App\Services\SeoAudit\SitemapDiscovery;
use App\Services\SeoAudit\RulesEngine;
use App\Services\SeoAudit\TextAnalyzer;
use App\Services\SeoAudit\LinkMetrics\LinkEquityScoring;

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

        $missingTitleCount = $pages->filter(fn($page) => empty($page->title))->count();
        $missingMetaCount = $pages->filter(fn($page) => empty($page->meta_description))->count();
        $missingH1Count = $pages->filter(fn($page) => ($page->h1_count ?? 0) === 0)->count();
        $multipleH1Count = $pages->filter(fn($page) => ($page->h1_count ?? 0) > 1)->count();

        $duplicateTitles = $pages->filter(fn($page) => !empty($page->title))
            ->groupBy('title')
            ->filter(fn($group) => $group->count() > 1);
        $duplicateTitlePages = $duplicateTitles->flatten();

        $duplicateMeta = $pages->filter(fn($page) => !empty($page->meta_description))
            ->groupBy('meta_description')
            ->filter(fn($group) => $group->count() > 1);
        $duplicateMetaPages = $duplicateMeta->flatten();

        $titleTooLong = $pages->filter(fn($page) => ($page->title_len ?? 0) > 60)->count();
        $titleTooShort = $pages->filter(fn($page) => ($page->title_len ?? 0) > 0 && ($page->title_len ?? 0) < 30)->count();
        $metaTooLong = $pages->filter(fn($page) => ($page->meta_len ?? 0) > 160)->count();
        $metaTooShort = $pages->filter(fn($page) => ($page->meta_len ?? 0) > 0 && ($page->meta_len ?? 0) < 70)->count();

        $avgWordCount = $pages->count() > 0 ? round($pages->avg('word_count')) : null;
        $thinPagesCount = $pages->filter(fn($page) => ($page->word_count ?? 0) > 0 && ($page->word_count ?? 0) < 300)->count();

        $duplicateTitlesTable = $duplicateTitlePages->take(50)->map(fn($page) => [
            'url' => $page->url,
            'title' => $page->title,
        ])->values()->toArray();

        $missingMetaTable = $pages->filter(fn($page) => empty($page->meta_description))->take(50)->map(fn($page) => [
            'url' => $page->url,
        ])->values()->toArray();

        $missingH1Table = $pages->filter(fn($page) => ($page->h1_count ?? 0) === 0)->take(50)->map(fn($page) => [
            'url' => $page->url,
        ])->values()->toArray();

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
            'title_missing_count' => $missingTitleCount,
            'title_duplicate_count' => $duplicateTitlePages->count(),
            'title_too_long_count' => $titleTooLong,
            'title_too_short_count' => $titleTooShort,
            'meta_missing_count' => $missingMetaCount,
            'meta_duplicate_count' => $duplicateMetaPages->count(),
            'meta_too_long_count' => $metaTooLong,
            'meta_too_short_count' => $metaTooShort,
            'h1_missing_count' => $missingH1Count,
            'h1_multiple_count' => $multipleH1Count,
            'images_total' => $pages->sum('images_total'),
            'images_missing_alt_total' => $pages->sum('images_missing_alt'),
            'avg_word_count' => $avgWordCount,
            'thin_pages_count' => $thinPagesCount,
            'duplicate_titles_table' => $duplicateTitlesTable,
            'missing_meta_table' => $missingMetaTable,
            'missing_h1_table' => $missingH1Table,
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
            'new_backlinks_count' => null,
            'lost_backlinks_count' => null,
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

        $issuesTotal = $issues->count();
        $issuesHigh = $issues->where('impact', 'high')->count();
        $issuesMedium = $issues->where('impact', 'medium')->count();
        $issuesLow = $issues->where('impact', 'low')->count();

        $jsRendering = $this->buildJsRenderingSection($audit, $pages, $issues);
        $nearDuplicate = $this->buildNearDuplicateSection($audit, $pages, $issues);
        $segmentation = $this->buildSegmentationSection($audit, $pages, $issues);
        $siteVisualisations = $this->buildSiteVisualisationsSection($audit, $pages, $links, $nearDuplicate, $segmentation);
        $spellingGrammar = $this->buildSpellingGrammarSection($audit, $pages, $issues);
        $customSourceSearch = $this->buildCustomSourceSearchSection($audit);
        $customExtraction = $this->buildCustomExtractionSection($audit);
        $formsAuthSummary = $this->buildFormsAuthSummarySection($audit);
        $linkMetrics = $this->buildLinkMetricsSection($audit, $pages, $audit->crawl_module_flags ?? []);

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
                'overall_score' => $audit->overall_score,
                'issues_total' => $issuesTotal,
                'issues_high' => $issuesHigh,
                'issues_medium' => $issuesMedium,
                'issues_low' => $issuesLow,
                'warnings_count' => $issuesMedium,
                'passed_checks' => max(0, ($pages->count() ?: 0) - $issuesTotal),
            ],
            'on_page_seo' => $onPage,
            'links' => $linksSection,
            'technical' => $technical,
            'performance' => $performance,
            'usability' => $usability,
            'social' => $social,
            'local_seo' => $local,
            'tech_email' => $techEmail,
            'js_rendering' => $jsRendering,
            'near_duplicate_content' => $nearDuplicate,
            'segmentation' => $segmentation,
            'site_visualisations' => $siteVisualisations,
            'spelling_grammar' => $spellingGrammar,
            'custom_source_search' => $customSourceSearch,
            'custom_extraction' => $customExtraction,
            'forms_auth_summary' => $formsAuthSummary,
            'link_metrics' => $linkMetrics,
        ];
    }

    protected function buildLinkMetricsSection(Audit $audit, $pages, array $flags): array
    {
        if (empty($flags['link_metrics_enabled'])) {
            return ['module_enabled' => false];
        }

        $tiers = config('seo_audit.link_metrics.tiers', []);
        $lowInternalMax = (int) config('seo_audit.link_metrics.low_internal_links_max', 3);
        $minEq = (int) config('seo_audit.link_metrics.low_internal_min_equity_score', 24);

        $toRow = function ($page) use ($tiers) {
            $m = is_array($page->link_metrics_json) ? $page->link_metrics_json : [];
            $tier = LinkEquityScoring::tier($m, $tiers);
            $anchors = $m['top_anchors'] ?? [];
            $themes = [];
            if (is_array($anchors)) {
                foreach (array_slice($anchors, 0, 5) as $a) {
                    if (is_array($a) && isset($a['anchor'])) {
                        $themes[] = $a['anchor'];
                    }
                }
            }

            return [
                'url' => $page->url,
                'segment' => $page->segment_key ?: 'other',
                'status_code' => $page->status_code,
                'referring_domains' => (int) ($m['referring_domains'] ?? 0),
                'backlinks' => (int) ($m['backlinks'] ?? 0),
                'authority_score' => $m['authority_score'] ?? null,
                'anchor_themes' => $themes,
                'internal_links_count' => (int) ($page->internal_links_count ?? 0),
                'equity_score' => LinkEquityScoring::equityScore($m),
                'equity_tier' => $tier,
            ];
        };

        $sortFn = function ($subset) use ($toRow) {
            return $subset->map(fn ($p) => $toRow($p))->sortByDesc('equity_score')->values()->take(50)->all();
        };

        $broken = $pages->filter(fn ($p) => ($p->status_code ?? 0) >= 400);
        $redirected = $pages->filter(fn ($p) => ($p->status_code ?? 0) >= 300 && ($p->status_code ?? 0) < 400);
        $noindex = $pages->filter(fn ($p) => $this->pageHasNoIndexSignal($p));
        $duplicate = $pages->filter(fn ($p) => ! empty($p->near_duplicate_cluster_id));
        $lowSupport = $pages->filter(function ($p) use ($lowInternalMax, $toRow, $minEq) {
            $r = $toRow($p);

            return $r['internal_links_count'] <= $lowInternalMax && $r['equity_score'] >= $minEq;
        });

        $tierDist = ['high' => 0, 'medium' => 0, 'low' => 0];
        foreach ($pages as $p) {
            $m = is_array($p->link_metrics_json) ? $p->link_metrics_json : [];
            $t = LinkEquityScoring::tier($m, $tiers);
            if (isset($tierDist[$t])) {
                $tierDist[$t]++;
            }
        }

        $withRd = $pages->filter(function ($p) {
            if (! is_array($p->link_metrics_json)) {
                return false;
            }

            return (int) ($p->link_metrics_json['referring_domains'] ?? 0) > 0;
        });

        $sampleThemes = [];
        foreach ($pages as $p) {
            if (is_array($p->link_metrics_json) && ! empty($p->link_metrics_json['global_anchor_themes'])) {
                $sampleThemes = $p->link_metrics_json['global_anchor_themes'];
                break;
            }
        }

        return [
            'module_enabled' => true,
            'overview' => [
                'pages_enriched' => $pages->count(),
                'pages_with_referring_domains' => $withRd->count(),
                'total_backlink_rows_tracked' => $pages->sum(function ($p) {
                    if (! is_array($p->link_metrics_json)) {
                        return 0;
                    }

                    return (int) ($p->link_metrics_json['backlinks'] ?? 0);
                }),
                'tier_distribution' => $tierDist,
            ],
            'thresholds' => $tiers,
            'top_linked_broken_pages' => $sortFn($broken),
            'top_linked_redirected_pages' => $sortFn($redirected),
            'top_linked_noindex_pages' => $sortFn($noindex),
            'top_linked_duplicate_pages' => $sortFn($duplicate),
            'top_opportunity_low_internal_pages' => $sortFn($lowSupport),
            'global_anchor_themes_sample' => $sampleThemes,
        ];
    }

    protected function pageHasNoIndexSignal($page): bool
    {
        $robots = strtolower($page->robots_meta ?? '');
        $xrobots = strtolower($page->x_robots_tag ?? '');

        return str_contains($robots, 'noindex') || str_contains($xrobots, 'noindex');
    }

    protected function buildFormsAuthSummarySection(Audit $audit): array
    {
        $flags = $audit->crawl_module_flags ?? [];
        if (empty($flags['forms_auth_enabled'])) {
            return ['module_enabled' => false];
        }

        $state = $audit->forms_auth_state ?? [];
        $pages = $audit->pages()->get();
        $blockedUrls = $pages->filter(fn ($p) => ! empty($p->auth_crawl_metadata['http_auth_blocked']))->pluck('url')->take(30)->values()->all();
        $loginRedirUrls = $pages->filter(fn ($p) => ! empty($p->auth_crawl_metadata['redirected_to_login_suspected']))->pluck('url')->take(30)->values()->all();
        $authOkUrls = $pages->filter(fn ($p) => ! empty($p->auth_crawl_metadata['likely_authenticated_content']))->pluck('url')->take(30)->values()->all();

        return [
            'module_enabled' => true,
            'login_success' => (bool) ($state['login_success'] ?? false),
            'login_error_public' => $state['login_error'] ?? null,
            'username_masked' => $state['username_masked'] ?? null,
            'login_url_display' => $audit->forms_auth_login_url,
            'selectors_configured' => [
                'username' => trim((string) ($audit->forms_auth_username_selector ?? '')) !== '',
                'password' => trim((string) ($audit->forms_auth_password_selector ?? '')) !== '',
                'submit' => trim((string) ($audit->forms_auth_submit_selector ?? '')) !== '',
                'success_indicator' => trim((string) ($audit->forms_auth_success_indicator ?? '')) !== '',
            ],
            'pages_total' => (int) ($state['total_pages_crawled'] ?? $pages->count()),
            'pages_likely_authenticated' => (int) ($state['pages_likely_authenticated'] ?? 0),
            'pages_blocked_http' => (int) ($state['pages_blocked_http'] ?? 0),
            'pages_login_redirect_suspected' => (int) ($state['pages_login_redirect_suspected'] ?? 0),
            'final_url_after_login' => $state['final_url_after_login'] ?? null,
            'blocked_urls_sample' => $blockedUrls,
            'login_redirect_urls_sample' => $loginRedirUrls,
            'authenticated_urls_sample' => $authOkUrls,
        ];
    }

    protected function buildCustomSourceSearchSection(Audit $audit): array
    {
        $flags = $audit->crawl_module_flags ?? [];
        $enabled = !empty($flags['custom_source_search_enabled']);
        if (!$enabled) {
            return ['module_enabled' => false, 'rule_summaries' => [], 'results_table' => []];
        }

        $rows = AuditCustomSearchResult::where('audit_id', $audit->id)->get();
        $byRule = $rows->groupBy('rule_key');
        $summaries = [];
        foreach ($byRule as $ruleKey => $group) {
            $first = $group->first();
            $pagesTotal = $audit->pages()->count();
            $matchedUrls = $group->where('matched', true)->pluck('url')->unique()->count();
            $policyMiss = $group->filter(function ($r) {
                if ($r->error_message) {
                    return false;
                }
                $exp = $r->expect_match;

                return ($exp && ! $r->matched) || (! $exp && $r->matched);
            })->count();

            $summaries[] = [
                'rule_key' => $ruleKey,
                'rule_name' => $first->rule_name,
                'target_scope' => $first->target_scope,
                'match_type' => $first->match_type,
                'severity' => $first->severity,
                'pages_evaluated' => $group->count(),
                'match_count_sum' => (int) $group->sum('match_count'),
                'urls_with_match' => $matchedUrls,
                'policy_miss_count' => $policyMiss,
                'pages_total' => $pagesTotal,
            ];
        }

        $resultsTable = $rows->take(150)->map(function ($r) {
            return [
                'url' => $r->url,
                'rule_name' => $r->rule_name,
                'rule_key' => $r->rule_key,
                'target_scope' => $r->target_scope,
                'matched' => $r->matched,
                'match_count' => $r->match_count,
                'sample_match' => $r->sample_match ? mb_substr($r->sample_match, 0, 400) : null,
                'expect_match' => $r->expect_match,
                'severity' => $r->severity,
                'error_message' => $r->error_message,
                'segment' => $r->segment_key,
            ];
        })->values()->toArray();

        return [
            'module_enabled' => true,
            'rule_summaries' => $summaries,
            'results_table' => $resultsTable,
            'rule_keys' => collect($summaries)->pluck('rule_key')->unique()->values()->all(),
        ];
    }

    protected function buildCustomExtractionSection(Audit $audit): array
    {
        $flags = $audit->crawl_module_flags ?? [];
        $enabled = !empty($flags['custom_extraction_enabled']);
        if (!$enabled) {
            return ['module_enabled' => false, 'rule_cards' => [], 'per_url_table' => [], 'duplicate_groups' => []];
        }

        $rows = AuditCustomExtractionResult::where('audit_id', $audit->id)->get();
        $pagesTotal = max(1, $audit->pages()->count());
        $byRule = $rows->groupBy('rule_key');
        $cards = [];
        foreach ($byRule as $ruleKey => $group) {
            $first = $group->first();
            $missing = $group->where('missing', true)->count();
            $withValues = $group->where('missing', false)->count();
            $cards[] = [
                'rule_key' => $ruleKey,
                'rule_name' => $first->rule_name,
                'extraction_type' => $first->extraction_type,
                'target_scope' => $first->target_scope,
                'coverage_pct' => round(100 * $withValues / $pagesTotal, 1),
                'missing_count' => $missing,
                'urls_with_value' => $withValues,
                'pages_total' => $pagesTotal,
            ];
        }

        $duplicateGroups = [];
        $dupIndex = $rows->filter(fn ($r) => $r->fingerprint !== null && ! $r->missing)->groupBy(fn ($r) => $r->rule_key.'|'.$r->fingerprint);
        foreach ($dupIndex as $key => $grp) {
            if ($grp->count() < 2) {
                continue;
            }
            $duplicateGroups[] = [
                'rule_key' => $grp->first()->rule_key,
                'value_fingerprint' => $grp->first()->fingerprint,
                'sample_values' => array_slice($grp->first()->values ?? [], 0, 3),
                'url_count' => $grp->count(),
                'urls' => $grp->pluck('url')->values()->take(20)->all(),
            ];
        }

        $perUrl = $rows->take(150)->map(function ($r) {
            return [
                'url' => $r->url,
                'rule_key' => $r->rule_key,
                'rule_name' => $r->rule_name,
                'values' => $r->values,
                'missing' => $r->missing,
                'error_message' => $r->error_message,
                'segment' => $r->segment_key,
            ];
        })->values()->toArray();

        return [
            'module_enabled' => true,
            'rule_cards' => $cards,
            'per_url_table' => $perUrl,
            'duplicate_groups' => array_slice($duplicateGroups, 0, 40),
            'rule_keys' => collect($cards)->pluck('rule_key')->unique()->values()->all(),
        ];
    }

    protected function buildSpellingGrammarSection(Audit $audit, $pages, $issues): array
    {
        $flags = $audit->crawl_module_flags ?? [];
        $enabled = !empty($flags['spelling_grammar_enabled']);
        $modIssues = $issues->where('module_key', 'spelling_grammar')->values();
        $highConfCut = (int) config('seo_audit.spelling.high_confidence', 78);
        $highConf = $modIssues->filter(fn ($i) => (int) ($i->details_json['confidence'] ?? 0) >= $highConfCut);

        $byKind = [];
        foreach ($modIssues as $issue) {
            $k = (string) ($issue->details_json['issue_kind'] ?? 'unknown');
            $byKind[$k] = ($byKind[$k] ?? 0) + 1;
        }

        $rows = $modIssues->take(100)->map(function ($issue) {
            $d = $issue->details_json ?? [];

            return [
                'url' => $issue->url,
                'issue_kind' => $d['issue_kind'] ?? null,
                'issue_text' => $d['issue_text'] ?? null,
                'suggested_correction' => $d['suggested_correction'] ?? null,
                'confidence' => $d['confidence'] ?? null,
                'context_snippet' => $d['context_snippet'] ?? null,
                'severity' => $issue->severity ?? null,
            ];
        })->values()->toArray();

        $pagesWith = $modIssues->pluck('url')->filter()->unique()->count();

        return [
            'module_enabled' => $enabled,
            'pages_with_issues' => $pagesWith,
            'total_issues' => $modIssues->count(),
            'high_confidence_issues' => $highConf->count(),
            'by_kind' => $byKind,
            'summary_cards' => [
                'pages_with_issues' => $pagesWith,
                'total_issues' => $modIssues->count(),
                'high_confidence_issues' => $highConf->count(),
            ],
            'affected_urls_table' => $rows,
        ];
    }

    protected function buildJsRenderingSection(Audit $audit, $pages, $issues): array
    {
        $flags = $audit->crawl_module_flags ?? [];
        $enabled = !empty($flags['js_rendering_enabled']);
        $modIssues = $issues->where('module_key', 'js_rendering')->values();
        $withSnap = $pages->filter(fn ($p) => !empty($p->js_render_snapshot));

        $byType = [];
        foreach ($modIssues as $issue) {
            $dt = $issue->details_json['diff_type'] ?? 'unknown';
            $byType[$dt] = ($byType[$dt] ?? 0) + 1;
        }

        $rows = $modIssues->take(100)->map(function ($issue) {
            $d = $issue->details_json ?? [];

            return [
                'url' => $issue->url,
                'diff_type' => $d['diff_type'] ?? null,
                'severity' => $issue->severity ?? ($d['severity'] ?? null),
                'impact' => $issue->impact,
                'message' => $issue->message ?? $issue->title,
                'raw' => $d['raw'] ?? [],
                'rendered' => $d['rendered'] ?? [],
                'recommendation' => $issue->recommendation,
                'filter_tags' => $d['filter_tags'] ?? [],
            ];
        })->values()->toArray();

        return [
            'module_enabled' => $enabled,
            'pages_with_render_snapshot' => $withSnap->count(),
            'diff_issue_count' => $modIssues->count(),
            'affected_urls' => $modIssues->pluck('url')->unique()->count(),
            'diff_types_breakdown' => $byType,
            'summary_cards' => [
                'pages_rendered' => $withSnap->count(),
                'urls_with_diffs' => $modIssues->pluck('url')->unique()->count(),
                'critical_severity' => $modIssues->where('severity', AuditIssue::SEVERITY_CRITICAL)->count(),
            ],
            'affected_urls_table' => $rows,
        ];
    }

    protected function buildNearDuplicateSection(Audit $audit, $pages, $issues): array
    {
        $flags = $audit->crawl_module_flags ?? [];
        $enabled = !empty($flags['near_duplicate_enabled']);
        $moduleIssues = $issues->where('module_key', 'near_duplicate_content')->values();
        $clusterGroups = $pages->filter(fn ($p) => !empty($p->near_duplicate_cluster_id))
            ->groupBy('near_duplicate_cluster_id');
        $clusters = $clusterGroups->map(function ($group, $clusterId) use ($moduleIssues) {
            $canonical = $group->sortByDesc('internal_links_count')->first();
            $related = $moduleIssues->first(function ($issue) use ($clusterId) {
                return ($issue->details_json['cluster_id'] ?? null) === $clusterId;
            });
            $pair = $related->details_json['strongest_pair'] ?? null;

            return [
                'cluster_id' => $clusterId,
                'cluster_size' => $group->count(),
                'canonical_url' => $canonical?->url,
                'member_urls' => $group->pluck('url')->values()->toArray(),
                'max_similarity' => $related->details_json['max_similarity'] ?? null,
                'strongest_pair' => $pair,
                'recommendation' => $related->recommendation ?? 'Consolidate near duplicates and keep one canonical URL.',
            ];
        })->sortByDesc('cluster_size')->values()->toArray();

        $pairs = $moduleIssues->map(function ($issue) {
            $d = $issue->details_json ?? [];
            return [
                'url' => $issue->url,
                'issue_code' => $issue->code,
                'similarity' => $d['max_similarity'] ?? ($d['similarity'] ?? null),
                'strongest_pair' => $d['strongest_pair'] ?? ($d['pair'] ?? null),
            ];
        })->take(30)->values()->toArray();

        return [
            'module_enabled' => $enabled,
            'cluster_count' => count($clusters),
            'largest_cluster_size' => collect($clusters)->max('cluster_size') ?? 0,
            'duplicate_clusters' => $clusters,
            'strongest_match_pairs' => $pairs,
            'affected_urls' => $moduleIssues->pluck('url')->filter()->unique()->count(),
        ];
    }

    protected function buildSegmentationSection(Audit $audit, $pages, $issues): array
    {
        $flags = $audit->crawl_module_flags ?? [];
        $enabled = !empty($flags['segmentation_enabled']);
        $counts = $pages->groupBy(fn ($page) => $page->segment_key ?: 'other')
            ->map(fn ($group) => $group->count())
            ->sortDesc();

        $issueBySegment = $issues->groupBy(function ($issue) {
            return $issue->details_json['segment'] ?? 'other';
        })->map(fn ($group) => $group->count())->sortDesc();

        $topProblems = $issues->groupBy(function ($issue) {
            return $issue->details_json['segment'] ?? 'other';
        })->map(function ($segmentIssues, $segment) {
            $top = $segmentIssues->groupBy('code')->map(fn ($items) => $items->count())->sortDesc()->take(5);
            return [
                'segment' => $segment,
                'issue_count' => $segmentIssues->count(),
                'top_issues' => $top->toArray(),
            ];
        })->values()->toArray();

        return [
            'module_enabled' => $enabled,
            'url_counts' => $counts->toArray(),
            'issue_counts' => $issueBySegment->toArray(),
            'top_problems_by_segment' => $topProblems,
        ];
    }

    protected function buildSiteVisualisationsSection(Audit $audit, $pages, $links, array $nearDuplicate, array $segmentation): array
    {
        $flags = $audit->crawl_module_flags ?? [];
        $enabled = !empty($flags['site_visualisation_enabled']);
        $status = $pages->groupBy(function ($p) {
            $code = (int) ($p->status_code ?? 0);
            if ($code >= 500) return '5xx';
            if ($code >= 400) return '4xx';
            if ($code >= 300) return '3xx';
            if ($code >= 200) return '2xx';
            return 'other';
        })->map->count()->toArray();

        $depthDist = [];
        $host = parse_url($audit->normalized_url, PHP_URL_HOST) ?: '';
        foreach ($pages as $page) {
            $path = (string) parse_url($page->url, PHP_URL_PATH);
            $depth = max(0, substr_count(trim($path, '/'), '/'));
            $key = (string) min(5, $depth);
            $depthDist[$key] = ($depthDist[$key] ?? 0) + 1;
        }
        ksort($depthDist);

        $linkDist = [
            '0-10' => 0, '11-30' => 0, '31-75' => 0, '76-150' => 0, '151+' => 0,
        ];
        foreach ($pages as $page) {
            $count = (int) ($page->internal_links_count ?? 0);
            if ($count <= 10) $linkDist['0-10']++;
            elseif ($count <= 30) $linkDist['11-30']++;
            elseif ($count <= 75) $linkDist['31-75']++;
            elseif ($count <= 150) $linkDist['76-150']++;
            else $linkDist['151+']++;
        }

        $graph = $links->where('type', 'internal')->take(500)->map(function ($link) use ($host) {
            $from = $this->normalizeUrlForGraph($link->from_url, $host);
            $to = $this->normalizeUrlForGraph($link->to_url_normalized ?: $link->to_url, $host);
            return ['from' => $from, 'to' => $to];
        })->values()->toArray();

        $clusterSize = collect($nearDuplicate['duplicate_clusters'] ?? [])
            ->groupBy(fn ($cluster) => (string) ($cluster['cluster_size'] ?? 0))
            ->map(fn ($items) => $items->count())
            ->toArray();

        return [
            'module_enabled' => $enabled,
            'status_code_distribution' => $status,
            'crawl_depth_distribution' => $depthDist,
            'urls_by_segment' => $segmentation['url_counts'] ?? [],
            'duplicate_clusters_by_size' => $clusterSize,
            'internal_link_count_distribution' => $linkDist,
            'internal_link_graph' => [
                'nodes' => array_values(array_unique(array_merge(array_column($graph, 'from'), array_column($graph, 'to')))),
                'edges' => $graph,
            ],
        ];
    }

    protected function normalizeUrlForGraph(?string $url, string $host): string
    {
        if (!$url) {
            return '/';
        }
        $parsed = parse_url($url);
        if (!$parsed) {
            return $url;
        }
        $pHost = strtolower($parsed['host'] ?? $host);
        $path = $parsed['path'] ?? '/';
        $q = isset($parsed['query']) ? '?' . $parsed['query'] : '';

        return ($pHost === strtolower($host) ? '' : $pHost) . ($path ?: '/') . $q;
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

        $heavyAssets = $assets->sortByDesc('size_bytes')->take(10)->map(function ($asset) {
            return [
                'asset_url' => $asset->asset_url,
                'type' => $asset->type,
                'size_kb' => $asset->size_bytes ? round($asset->size_bytes / 1024, 1) : null,
            ];
        })->values()->toArray();

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
            'heavy_assets' => $heavyAssets,
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
        $securityList = collect($securitySummary)->except('pages_with_headers')->map(function ($count, $header) use ($pages) {
            return [
                'header' => strtoupper(str_replace('_', '-', $header)),
                'pages_with_header' => $count,
                'total_pages' => $pages->count(),
            ];
        })->values()->toArray();

        $indexabilityIssues = $pages->filter(function ($page) {
            $robots = strtolower($page->robots_meta ?? '');
            $xrobots = strtolower($page->x_robots_tag ?? '');
            return str_contains($robots, 'noindex') || str_contains($robots, 'nofollow')
                || str_contains($xrobots, 'noindex') || str_contains($xrobots, 'nofollow');
        });

        $non200Pages = $pages->filter(fn($page) => ($page->status_code ?? 0) < 200 || ($page->status_code ?? 0) >= 300)
            ->take(50)
            ->map(fn($page) => [
                'url' => $page->url,
                'status_code' => $page->status_code,
            ])->values()->toArray();

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
            'canonical_present_count' => $pages->filter(fn($page) => !empty($page->canonical_url))->count(),
            'indexability_issues_count' => $indexabilityIssues->count(),
            'non_200_pages' => $non200Pages,
            'structured_data_detected' => $schemaDetected,
            'analytics_detected' => $pages->whereNotNull('analytics_tool')->count() > 0,
            'favicon_present' => $pages->where('favicon_present', true)->count() > 0,
            'security_headers' => $securitySummary,
            'security_headers_list' => $securityList,
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
