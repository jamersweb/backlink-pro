<?php

namespace App\Services\SeoAudit;

use App\Models\Audit;
use App\Models\AuditIssue;

class ReportModuleBuilder
{
    public function __construct(
        protected ReportModuleRegistry $registry,
        protected CrawlModuleConfig $crawlModuleConfig
    ) {}

    public function build(Audit $audit): array
    {
        $kpis = $audit->audit_kpis ?? [];
        $issues = $audit->issues()->get();
        $flags = $audit->crawl_module_flags ?? [];
        $segmentOptions = array_keys(($kpis['segmentation']['url_counts'] ?? []));
        $enabledOptionalKeys = $this->crawlModuleConfig->enabledModuleKeys($flags);
        $orderedKeys = $this->registry->orderedModuleKeys($enabledOptionalKeys);
        $titles = $this->registry->titlesByKey();

        $modules = [];
        foreach ($orderedKeys as $moduleKey) {
            $moduleIssues = $this->issuesForModule($issues->all(), $moduleKey);
            $summaryMetrics = $this->summaryMetricsForModule($moduleKey, $kpis);
            $recommendations = $this->recommendationsForIssues($moduleIssues);
            $severityCounts = $this->severityCounts($moduleIssues);
            $topExamples = $this->topExamples($moduleIssues);
            $affectedUrls = collect($moduleIssues)
                ->pluck('url')
                ->filter()
                ->unique()
                ->values()
                ->toArray();

            $filterPayload = [
                'severities' => ['critical', 'warning', 'info'],
                'statuses' => ['open', 'in_progress', 'resolved', 'ignored'],
                'segments' => $segmentOptions,
            ];
            if ($moduleKey === 'js_rendering') {
                $filterPayload['presets'] = [
                    ['key' => 'changed_after_render', 'label' => 'Only changed after render'],
                    ['key' => 'missing_in_raw', 'label' => 'Missing in raw HTML'],
                    ['key' => 'missing_in_rendered', 'label' => 'Missing after render'],
                    ['key' => 'indexability_mismatch', 'label' => 'Indexability mismatch'],
                ];
            }
            if ($moduleKey === 'segmentation') {
                $filterPayload['presets'] = [
                    ['key' => 'top_problem_segments', 'label' => 'Top problem segments'],
                ];
            }
            if ($moduleKey === 'spelling_grammar') {
                $filterPayload['presets'] = [
                    ['key' => 'high_confidence', 'label' => 'Highest confidence only'],
                    ['key' => 'spelling', 'label' => 'Spelling only'],
                    ['key' => 'grammar', 'label' => 'Grammar only'],
                    ['key' => 'repeated_word', 'label' => 'Repeated words'],
                    ['key' => 'punctuation', 'label' => 'Punctuation'],
                ];
            }
            if ($moduleKey === 'custom_source_search') {
                $filterPayload['rule_keys'] = $kpis['custom_source_search']['rule_keys'] ?? [];
            }
            if ($moduleKey === 'custom_extraction') {
                $filterPayload['rule_keys'] = $kpis['custom_extraction']['rule_keys'] ?? [];
                $filterPayload['presets'] = [
                    ['key' => 'missing_only', 'label' => 'Missing values only'],
                ];
            }
            if ($moduleKey === 'link_metrics') {
                $eqIssues = array_values(array_filter($issues->all(), function (AuditIssue $issue) {
                    return is_array($issue->details_json['link_equity'] ?? null);
                }));
                $filterPayload['presets'] = [
                    ['key' => 'all', 'label' => 'All equity rows'],
                    ['key' => 'high_tier_only', 'label' => 'High tier only'],
                    ['key' => 'medium_or_high', 'label' => 'Medium + high tier'],
                ];
                $filterPayload['issue_types'] = collect($eqIssues)->pluck('issue_type')->unique()->sort()->values()->toArray();
                $filterPayload['metric_thresholds'] = [
                    'min_referring_domains' => (int) ($kpis['link_metrics']['thresholds']['medium_referring_domains'] ?? 10),
                    'min_backlinks' => (int) ($kpis['link_metrics']['thresholds']['medium_backlinks'] ?? 40),
                ];
            }

            $card = [
                'overview_count' => count($moduleIssues),
                'affected_urls' => count($affectedUrls),
                'severity_split' => $severityCounts,
                'top_examples' => $topExamples,
                'recommended_actions' => array_slice($recommendations, 0, 3),
            ];
            if ($moduleKey === 'spelling_grammar') {
                $sg = $kpis['spelling_grammar'] ?? [];
                $card['high_confidence_issues'] = (int) ($sg['high_confidence_issues'] ?? 0);
            }
            if ($moduleKey === 'forms_auth_summary') {
                $fa = $kpis['forms_auth_summary'] ?? [];
                $card['login_success'] = (bool) ($fa['login_success'] ?? false);
                $card['pages_likely_authenticated'] = (int) ($fa['pages_likely_authenticated'] ?? 0);
                $card['pages_blocked_http'] = (int) ($fa['pages_blocked_http'] ?? 0);
                $card['pages_login_redirect_suspected'] = (int) ($fa['pages_login_redirect_suspected'] ?? 0);
            }

            $modules[] = [
                'module_key' => $moduleKey,
                'module_title' => $titles[$moduleKey] ?? ucfirst(str_replace('_', ' ', $moduleKey)),
                'summary_metrics' => $summaryMetrics,
                'issues' => array_map(fn (AuditIssue $issue) => $this->toIssuePayload($issue), $moduleIssues),
                'tables' => $this->tablesForModule($moduleKey, $kpis, $moduleIssues),
                'charts' => $this->chartsForModule($moduleKey, $summaryMetrics, $severityCounts, $kpis),
                'exports' => [
                    'csv' => ['module_key' => $moduleKey, 'enabled' => true],
                    'json' => ['module_key' => $moduleKey, 'enabled' => true],
                ],
                'filters' => $filterPayload,
                'severity_counts' => $severityCounts,
                'recommendations' => $recommendations,
                'card' => $card,
            ];
        }

        return [
            'version' => 1,
            'generated_at' => now()->toIso8601String(),
            'module_order' => $orderedKeys,
            'modules' => $modules,
        ];
    }

    protected function issuesForModule(array $issues, string $moduleKey): array
    {
        if ($moduleKey === 'overview') {
            return $issues;
        }

        if ($moduleKey === 'link_metrics') {
            return array_values(array_filter($issues, function (AuditIssue $issue) {
                $eq = $issue->details_json['link_equity'] ?? null;
                if (! is_array($eq)) {
                    return false;
                }
                $tier = $eq['tier'] ?? 'low';
                $rd = (int) ($eq['referring_domains'] ?? 0);
                $bl = (int) ($eq['backlinks'] ?? 0);

                return $tier !== 'low' || $rd > 0 || $bl > 0;
            }));
        }

        return array_values(array_filter($issues, function (AuditIssue $issue) use ($moduleKey) {
            $key = $issue->module_key ?: match ($issue->category) {
                'onpage' => 'on_page_seo',
                'social', 'local', 'security', 'usability' => 'integrations',
                default => $issue->category,
            };
            return $key === $moduleKey;
        }));
    }

    protected function summaryMetricsForModule(string $moduleKey, array $kpis): array
    {
        if ($moduleKey === 'overview') {
            $overview = $kpis['overview'] ?? [];
            return [
                ['key' => 'overall_score', 'label' => 'Overall Score', 'value' => $overview['overall_score'] ?? null],
                ['key' => 'issues_total', 'label' => 'Issues', 'value' => $overview['issues_total'] ?? null],
                ['key' => 'pages_crawled', 'label' => 'Pages Crawled', 'value' => $overview['pages_crawled_count'] ?? null],
            ];
        }

        if ($moduleKey === 'js_rendering') {
            $jr = $kpis['js_rendering'] ?? [];

            return [
                ['key' => 'pages_rendered', 'label' => 'Pages rendered', 'value' => $jr['pages_with_render_snapshot'] ?? 0],
                ['key' => 'diff_findings', 'label' => 'Findings', 'value' => $jr['diff_issue_count'] ?? 0],
                ['key' => 'affected_urls', 'label' => 'Affected URLs', 'value' => $jr['affected_urls'] ?? 0],
                ['key' => 'critical', 'label' => 'Critical severity', 'value' => $jr['summary_cards']['critical_severity'] ?? 0],
            ];
        }
        if ($moduleKey === 'near_duplicate_content') {
            $nd = $kpis['near_duplicate_content'] ?? [];
            return [
                ['key' => 'clusters', 'label' => 'Duplicate clusters', 'value' => $nd['cluster_count'] ?? 0],
                ['key' => 'largest_cluster', 'label' => 'Largest cluster', 'value' => $nd['largest_cluster_size'] ?? 0],
                ['key' => 'affected_urls', 'label' => 'Affected URLs', 'value' => $nd['affected_urls'] ?? 0],
            ];
        }
        if ($moduleKey === 'segmentation') {
            $sg = $kpis['segmentation'] ?? [];
            return [
                ['key' => 'segments', 'label' => 'Segments', 'value' => count($sg['url_counts'] ?? [])],
                ['key' => 'urls_total', 'label' => 'URLs segmented', 'value' => array_sum($sg['url_counts'] ?? [])],
                ['key' => 'segments_with_issues', 'label' => 'Segments with issues', 'value' => count(array_filter($sg['issue_counts'] ?? []))],
            ];
        }
        if ($moduleKey === 'site_visualisations') {
            $sv = $kpis['site_visualisations'] ?? [];
            return [
                ['key' => 'status_buckets', 'label' => 'Status buckets', 'value' => count($sv['status_code_distribution'] ?? [])],
                ['key' => 'segment_buckets', 'label' => 'Segment buckets', 'value' => count($sv['urls_by_segment'] ?? [])],
                ['key' => 'graph_edges', 'label' => 'Graph edges', 'value' => count($sv['internal_link_graph']['edges'] ?? [])],
            ];
        }
        if ($moduleKey === 'spelling_grammar') {
            $sg = $kpis['spelling_grammar'] ?? [];
            return [
                ['key' => 'pages_with_issues', 'label' => 'Pages with issues', 'value' => $sg['pages_with_issues'] ?? 0],
                ['key' => 'total_issues', 'label' => 'Total issues', 'value' => $sg['total_issues'] ?? 0],
                ['key' => 'high_confidence_issues', 'label' => 'Highest-confidence issues', 'value' => $sg['high_confidence_issues'] ?? 0],
                ['key' => 'issue_kinds', 'label' => 'Issue categories', 'value' => count($sg['by_kind'] ?? [])],
            ];
        }
        if ($moduleKey === 'custom_source_search') {
            $cs = $kpis['custom_source_search'] ?? [];
            $summ = $cs['rule_summaries'] ?? [];
            $miss = collect($summ)->sum('policy_miss_count');

            return [
                ['key' => 'rules', 'label' => 'Active rules', 'value' => count($summ)],
                ['key' => 'policy_misses', 'label' => 'Policy misses', 'value' => $miss],
                ['key' => 'result_rows', 'label' => 'Evaluated rows', 'value' => count($cs['results_table'] ?? [])],
            ];
        }
        if ($moduleKey === 'custom_extraction') {
            $ce = $kpis['custom_extraction'] ?? [];
            $cards = $ce['rule_cards'] ?? [];

            return [
                ['key' => 'rules', 'label' => 'Extraction rules', 'value' => count($cards)],
                ['key' => 'dup_groups', 'label' => 'Duplicate value groups', 'value' => count($ce['duplicate_groups'] ?? [])],
                ['key' => 'rows', 'label' => 'Per-URL rows (sample)', 'value' => count($ce['per_url_table'] ?? [])],
            ];
        }
        if ($moduleKey === 'forms_auth_summary') {
            $fa = $kpis['forms_auth_summary'] ?? [];

            return [
                ['key' => 'login_ok', 'label' => 'Login established', 'value' => ($fa['login_success'] ?? false) ? 'Yes' : 'No'],
                ['key' => 'auth_pages', 'label' => 'Likely authenticated pages', 'value' => $fa['pages_likely_authenticated'] ?? 0],
                ['key' => 'blocked', 'label' => 'HTTP blocked (401/403)', 'value' => $fa['pages_blocked_http'] ?? 0],
                ['key' => 'login_redirects', 'label' => 'Login redirect suspects', 'value' => $fa['pages_login_redirect_suspected'] ?? 0],
            ];
        }
        if ($moduleKey === 'link_metrics') {
            $lm = $kpis['link_metrics'] ?? [];
            $ov = $lm['overview'] ?? [];
            $dist = $ov['tier_distribution'] ?? [];

            return [
                ['key' => 'pages_enriched', 'label' => 'Pages enriched', 'value' => $ov['pages_enriched'] ?? 0],
                ['key' => 'pages_with_rd', 'label' => 'URLs with referring domains', 'value' => $ov['pages_with_referring_domains'] ?? 0],
                ['key' => 'tracked_backlinks', 'label' => 'Backlink rows (tracked)', 'value' => $ov['total_backlink_rows_tracked'] ?? 0],
                ['key' => 'high_tier', 'label' => 'High-equity URLs', 'value' => $dist['high'] ?? 0],
                ['key' => 'medium_tier', 'label' => 'Medium-equity URLs', 'value' => $dist['medium'] ?? 0],
            ];
        }

        $section = $kpis[$moduleKey] ?? [];
        $metrics = [];
        foreach ($section as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $metrics[] = [
                    'key' => $key,
                    'label' => ucfirst(str_replace('_', ' ', $key)),
                    'value' => $value,
                ];
            }
        }

        return array_slice($metrics, 0, 8);
    }

    protected function toIssuePayload(AuditIssue $issue): array
    {
        return [
            'id' => $issue->id,
            'audit_run_id' => $issue->audit_run_id ?? $issue->audit_id,
            'url' => $issue->url,
            'module_key' => $issue->module_key,
            'issue_type' => $issue->issue_type ?? $issue->code,
            'severity' => $issue->severity,
            'status' => $issue->status,
            'score_penalty' => $issue->score_penalty,
            'message' => $issue->message ?? $issue->title,
            'details_json' => $issue->details_json,
            'discovered_at' => optional($issue->discovered_at)->toIso8601String(),
            'code' => $issue->code,
            'title' => $issue->title,
            'description' => $issue->description,
            'impact' => $issue->impact,
            'recommendation' => $issue->recommendation,
            'segment' => $issue->details_json['segment'] ?? null,
        ];
    }

    protected function recommendationsForIssues(array $issues): array
    {
        return collect($issues)
            ->pluck('recommendation')
            ->filter()
            ->unique()
            ->values()
            ->take(10)
            ->toArray();
    }

    protected function severityCounts(array $issues): array
    {
        $counts = ['critical' => 0, 'warning' => 0, 'info' => 0];
        foreach ($issues as $issue) {
            $severity = $issue->severity ?? 'info';
            if (!array_key_exists($severity, $counts)) {
                $counts[$severity] = 0;
            }
            $counts[$severity]++;
        }

        return $counts;
    }

    protected function topExamples(array $issues): array
    {
        return collect($issues)
            ->take(5)
            ->map(function (AuditIssue $issue) {
                return [
                    'url' => $issue->url,
                    'message' => $issue->message ?? $issue->title,
                    'severity' => $issue->severity ?? 'info',
                ];
            })
            ->values()
            ->toArray();
    }

    protected function tablesForModule(string $moduleKey, array $kpis, array $issues): array
    {
        $tables = [];
        if ($moduleKey === 'on_page_seo') {
            $onPage = $kpis['on_page_seo'] ?? [];
            $tables[] = [
                'key' => 'duplicate_titles',
                'title' => 'Duplicate Titles',
                'rows' => $onPage['duplicate_titles_table'] ?? [],
            ];
            $tables[] = [
                'key' => 'missing_meta',
                'title' => 'Missing Meta',
                'rows' => $onPage['missing_meta_table'] ?? [],
            ];
        }

        if ($moduleKey === 'technical') {
            $technical = $kpis['technical'] ?? [];
            $tables[] = [
                'key' => 'broken_links',
                'title' => 'Broken Links',
                'rows' => $technical['broken_links_examples'] ?? [],
            ];
        }

        if ($moduleKey === 'js_rendering') {
            $jr = $kpis['js_rendering'] ?? [];
            $tables[] = [
                'key' => 'js_rendering_diffs',
                'title' => 'Raw vs rendered (affected URLs)',
                'rows' => $jr['affected_urls_table'] ?? [],
            ];
        }
        if ($moduleKey === 'near_duplicate_content') {
            $nd = $kpis['near_duplicate_content'] ?? [];
            $tables[] = [
                'key' => 'duplicate_clusters',
                'title' => 'Duplicate Clusters',
                'rows' => $nd['duplicate_clusters'] ?? [],
            ];
            $tables[] = [
                'key' => 'strongest_pairs',
                'title' => 'Strongest Match Pairs',
                'rows' => $nd['strongest_match_pairs'] ?? [],
            ];
        }
        if ($moduleKey === 'segmentation') {
            $sg = $kpis['segmentation'] ?? [];
            $tables[] = [
                'key' => 'segmentation_overview',
                'title' => 'Segmentation Overview',
                'rows' => collect($sg['url_counts'] ?? [])->map(fn ($count, $segment) => [
                    'segment' => $segment,
                    'url_count' => $count,
                    'issue_count' => ($sg['issue_counts'][$segment] ?? 0),
                ])->values()->toArray(),
            ];
            $tables[] = [
                'key' => 'top_problems_by_segment',
                'title' => 'Top Problems by Segment',
                'rows' => $sg['top_problems_by_segment'] ?? [],
            ];
        }
        if ($moduleKey === 'site_visualisations') {
            $sv = $kpis['site_visualisations'] ?? [];
            $tables[] = [
                'key' => 'internal_link_graph',
                'title' => 'Internal Link Graph Dataset',
                'rows' => $sv['internal_link_graph']['edges'] ?? [],
            ];
        }
        if ($moduleKey === 'spelling_grammar') {
            $sg = $kpis['spelling_grammar'] ?? [];
            $tables[] = [
                'key' => 'spelling_findings',
                'title' => 'Spelling & grammar findings',
                'rows' => $sg['affected_urls_table'] ?? [],
            ];
        }
        if ($moduleKey === 'custom_source_search') {
            $cs = $kpis['custom_source_search'] ?? [];
            $tables[] = [
                'key' => 'custom_search_rule_summaries',
                'title' => 'Rule summaries',
                'rows' => $cs['rule_summaries'] ?? [],
            ];
            $tables[] = [
                'key' => 'custom_search_results',
                'title' => 'Per-URL results',
                'rows' => $cs['results_table'] ?? [],
            ];
        }
        if ($moduleKey === 'custom_extraction') {
            $ce = $kpis['custom_extraction'] ?? [];
            $tables[] = [
                'key' => 'custom_extraction_rules',
                'title' => 'Coverage by rule',
                'rows' => $ce['rule_cards'] ?? [],
            ];
            $tables[] = [
                'key' => 'custom_extraction_duplicates',
                'title' => 'Duplicate extracted values',
                'rows' => $ce['duplicate_groups'] ?? [],
            ];
            $tables[] = [
                'key' => 'custom_extraction_per_url',
                'title' => 'Per-URL extractions',
                'rows' => $ce['per_url_table'] ?? [],
            ];
        }
        if ($moduleKey === 'link_metrics') {
            $lm = $kpis['link_metrics'] ?? [];
            if (! empty($lm['global_anchor_themes_sample'])) {
                $tables[] = [
                    'key' => 'link_metrics_anchor_themes',
                    'title' => 'Top anchor themes (backlink profile)',
                    'rows' => $lm['global_anchor_themes_sample'],
                ];
            }
            $tables[] = [
                'key' => 'top_linked_broken_pages',
                'title' => 'Top linked broken pages (4xx/5xx)',
                'rows' => $lm['top_linked_broken_pages'] ?? [],
            ];
            $tables[] = [
                'key' => 'top_linked_redirected_pages',
                'title' => 'Top linked redirected pages',
                'rows' => $lm['top_linked_redirected_pages'] ?? [],
            ];
            $tables[] = [
                'key' => 'top_linked_noindex_pages',
                'title' => 'Top linked noindex pages',
                'rows' => $lm['top_linked_noindex_pages'] ?? [],
            ];
            $tables[] = [
                'key' => 'top_linked_duplicate_pages',
                'title' => 'Top linked duplicate/near-duplicate pages',
                'rows' => $lm['top_linked_duplicate_pages'] ?? [],
            ];
            $tables[] = [
                'key' => 'top_opportunity_low_internal_pages',
                'title' => 'Opportunity: strong inbound links, low internal links',
                'rows' => $lm['top_opportunity_low_internal_pages'] ?? [],
            ];
        }

        if ($moduleKey === 'forms_auth_summary') {
            $fa = $kpis['forms_auth_summary'] ?? [];
            $tables[] = [
                'key' => 'forms_auth_summary_card',
                'title' => 'Authentication summary',
                'rows' => [[
                    'login_success' => ($fa['login_success'] ?? false) ? 'yes' : 'no',
                    'username_masked' => $fa['username_masked'] ?? '',
                    'login_url' => $fa['login_url_display'] ?? '',
                    'pages_total' => $fa['pages_total'] ?? 0,
                    'pages_likely_authenticated' => $fa['pages_likely_authenticated'] ?? 0,
                    'pages_blocked_http' => $fa['pages_blocked_http'] ?? 0,
                    'pages_login_redirect_suspected' => $fa['pages_login_redirect_suspected'] ?? 0,
                    'login_error' => $fa['login_error_public'] ?? '',
                ]],
            ];
            $tables[] = [
                'key' => 'forms_auth_blocked_urls',
                'title' => 'Blocked URLs (sample)',
                'rows' => collect($fa['blocked_urls_sample'] ?? [])->map(fn ($u) => ['url' => $u])->all(),
            ];
            $tables[] = [
                'key' => 'forms_auth_login_redirect_urls',
                'title' => 'Login redirect suspects (sample)',
                'rows' => collect($fa['login_redirect_urls_sample'] ?? [])->map(fn ($u) => ['url' => $u])->all(),
            ];
            $tables[] = [
                'key' => 'forms_auth_authenticated_urls',
                'title' => 'Likely authenticated content (sample)',
                'rows' => collect($fa['authenticated_urls_sample'] ?? [])->map(fn ($u) => ['url' => $u])->all(),
            ];
        }

        if ($moduleKey !== 'overview' && $moduleKey !== 'link_metrics' && empty($tables)) {
            $tables[] = [
                'key' => 'issues',
                'title' => 'Issues',
                'rows' => array_map(fn (AuditIssue $issue) => $this->toIssuePayload($issue), $issues),
            ];
        }

        return $tables;
    }

    protected function chartsForModule(string $moduleKey, array $summaryMetrics, array $severityCounts, array $kpis): array
    {
        if ($moduleKey === 'custom_source_search') {
            $cs = $kpis['custom_source_search'] ?? [];
            $summ = $cs['rule_summaries'] ?? [];
            $dataset = [];
            foreach ($summ as $s) {
                $dataset[$s['rule_key'] ?? 'rule'] = $s['urls_with_match'] ?? 0;
            }

            return [
                [
                    'key' => 'custom_search_matches_by_rule',
                    'type' => 'bar',
                    'title' => 'URLs with match (by rule)',
                    'dataset' => $dataset,
                ],
                [
                    'key' => 'custom_search_severity',
                    'type' => 'pie',
                    'title' => 'Severity (issues)',
                    'dataset' => $severityCounts,
                ],
            ];
        }
        if ($moduleKey === 'custom_extraction') {
            $ce = $kpis['custom_extraction'] ?? [];
            $dataset = [];
            foreach (($ce['rule_cards'] ?? []) as $c) {
                $dataset[$c['rule_key'] ?? 'rule'] = $c['coverage_pct'] ?? 0;
            }

            return [
                [
                    'key' => 'custom_extraction_coverage',
                    'type' => 'bar',
                    'title' => 'Coverage % by rule',
                    'dataset' => $dataset,
                ],
            ];
        }
        if ($moduleKey === 'forms_auth_summary') {
            $fa = $kpis['forms_auth_summary'] ?? [];

            return [
                [
                    'key' => 'forms_auth_crawl_split',
                    'type' => 'pie',
                    'title' => 'Crawl outcome (approx.)',
                    'dataset' => [
                        'likely_authenticated' => (int) ($fa['pages_likely_authenticated'] ?? 0),
                        'http_blocked' => (int) ($fa['pages_blocked_http'] ?? 0),
                        'login_redirect_suspected' => (int) ($fa['pages_login_redirect_suspected'] ?? 0),
                    ],
                ],
                [
                    'key' => 'forms_auth_severity',
                    'type' => 'pie',
                    'title' => 'Severity (issues)',
                    'dataset' => $severityCounts,
                ],
            ];
        }
        if ($moduleKey === 'link_metrics') {
            $lm = $kpis['link_metrics'] ?? [];
            $dist = $lm['overview']['tier_distribution'] ?? [];

            return [
                [
                    'key' => 'link_metrics_equity_tiers',
                    'type' => 'pie',
                    'title' => 'Equity tiers (crawled URLs)',
                    'dataset' => $dist,
                ],
                [
                    'key' => 'link_metrics_severity',
                    'type' => 'pie',
                    'title' => 'Severity (prioritized issues)',
                    'dataset' => $severityCounts,
                ],
            ];
        }
        if ($moduleKey === 'spelling_grammar') {
            $sg = $kpis['spelling_grammar'] ?? [];

            return [
                [
                    'key' => 'spelling_issues_by_kind',
                    'type' => 'bar',
                    'title' => 'Issues by kind',
                    'dataset' => $sg['by_kind'] ?? [],
                ],
                [
                    'key' => 'spelling_severity_split',
                    'type' => 'pie',
                    'title' => 'Severity split',
                    'dataset' => $severityCounts,
                ],
            ];
        }
        if ($moduleKey === 'site_visualisations') {
            $sv = $kpis['site_visualisations'] ?? [];
            return [
                [
                    'key' => 'status_code_distribution',
                    'type' => 'bar',
                    'title' => 'Status Code Distribution',
                    'dataset' => $sv['status_code_distribution'] ?? [],
                ],
                [
                    'key' => 'crawl_depth_distribution',
                    'type' => 'bar',
                    'title' => 'Crawl Depth Distribution',
                    'dataset' => $sv['crawl_depth_distribution'] ?? [],
                ],
                [
                    'key' => 'urls_by_segment',
                    'type' => 'bar',
                    'title' => 'URLs by Segment',
                    'dataset' => $sv['urls_by_segment'] ?? [],
                ],
                [
                    'key' => 'duplicate_clusters_by_size',
                    'type' => 'bar',
                    'title' => 'Duplicate Clusters by Size',
                    'dataset' => $sv['duplicate_clusters_by_size'] ?? [],
                ],
                [
                    'key' => 'internal_link_count_distribution',
                    'type' => 'bar',
                    'title' => 'Internal Link Count Distribution',
                    'dataset' => $sv['internal_link_count_distribution'] ?? [],
                ],
            ];
        }
        return [
            [
                'key' => $moduleKey . '_severity_split',
                'type' => 'pie',
                'title' => 'Severity Split',
                'dataset' => $severityCounts,
            ],
            [
                'key' => $moduleKey . '_summary',
                'type' => 'bar',
                'title' => 'Summary Metrics',
                'dataset' => collect($summaryMetrics)->pluck('value', 'key')->toArray(),
            ],
        ];
    }
}

