<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Audit Report — {{ parse_url($audit->url, PHP_URL_HOST) ?: $audit->url }}</title>
    <style>
        @page { margin: 9mm 11mm 11mm 11mm; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            font-size: 8.5px;
            line-height: 1.42;
            color: #2b3437;
            background: #f8f9fa;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        table { border-collapse: collapse; width: 100%; }
        .page-break { page-break-before: always; }
        .truncate { word-break: break-word; overflow-wrap: break-word; }

        .brand-bar { padding: 10px 0 14px; border-bottom: 2px solid #1A237E; margin-bottom: 16px; }
        .headline { font-size: 17px; font-weight: 800; letter-spacing: -0.02em; color: #1A237E; }
        .sub-brand { font-size: 6.5px; font-weight: 800; letter-spacing: 0.22em; text-transform: uppercase; color: #64748b; margin-top: 3px; }

        .pill-badge { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 6.5px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; background: #d6e4f9; color: #455364; }
        .hero-domain { font-size: 21px; font-weight: 800; letter-spacing: -0.03em; color: #1e293b; line-height: 1.1; }
        .hero-url { font-size: 8px; color: #006b60; word-break: break-all; margin-top: 4px; font-weight: 600; }
        .hero-lead { font-size: 9px; color: #586064; margin-top: 8px; max-width: 540px; }
        .meta-k { font-size: 6.5px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: #64748b; }
        .meta-v { font-size: 8.5px; font-weight: 600; color: #2b3437; margin-top: 2px; }

        .donut-wrap { text-align: center; width: 150px; margin: 0 auto; }
        .donut-score { font-size: 34px; font-weight: 800; color: #006b60; line-height: 1; }
        .donut-grade { font-size: 7.5px; font-weight: 800; letter-spacing: 0.12em; color: #64748b; margin-top: 4px; }

        .card-3 { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px 12px; vertical-align: top; width: 33%; }
        .card-3 .big { font-size: 20px; font-weight: 800; color: #1e293b; }
        .card-3 h3 { font-size: 7.5px; font-weight: 800; letter-spacing: 0.1em; text-transform: uppercase; color: #64748b; margin: 8px 0 0; }
        .card-3 p { font-size: 7.5px; color: #64748b; margin: 5px 0 0; }
        .icon-box { display: inline-block; padding: 5px; border-radius: 6px; font-size: 13px; }

        .section-wrap { background: #eef2f6; border-radius: 10px; padding: 16px 14px; margin-top: 12px; border: 1px solid #e2e8f0; }
        .section-title { font-size: 13px; font-weight: 800; color: #1e293b; }
        .section-sub { font-size: 7.5px; color: #64748b; margin-top: 3px; }
        .device-tag { font-size: 6.5px; font-family: DejaVu Sans Mono, monospace; background: #dbe4e7; padding: 4px 8px; border-radius: 4px; font-weight: 700; }

        .core-card { background: #ffffff; border-radius: 8px; padding: 10px 6px; text-align: center; vertical-align: top; width: 25%; border: 1px solid #e8edf2; }
        .core-val { font-size: 18px; font-weight: 800; color: #006b60; }
        .core-lbl { font-size: 6.5px; font-weight: 800; letter-spacing: 0.1em; text-transform: uppercase; color: #64748b; margin-top: 3px; }
        .bar-bg { height: 3px; background: #b8f0e8; border-radius: 99px; margin-top: 7px; overflow: hidden; }
        .bar-fg { height: 3px; background: #006b60; border-radius: 99px; }

        .col-title { font-size: 11px; font-weight: 800; border-left: 4px solid #1A237E; padding-left: 10px; margin-bottom: 10px; color: #1e293b; }
        .row-tech { background: #ffffff; border: 1px solid #e8edf2; border-radius: 6px; padding: 9px 11px; margin-bottom: 7px; }
        .quote-box { background: #ffffff; border: 1px solid #e8edf2; border-radius: 8px; padding: 14px; font-size: 8.5px; color: #475569; font-style: italic; line-height: 1.55; }

        .metric-hero { background: linear-gradient(135deg,#4a5568,#525f71); color: #f5f8ff; border-radius: 8px; padding: 12px; text-align: center; vertical-align: middle; width: 25%; }
        .metric-hero .v { font-size: 17px; font-weight: 800; }
        .metric-hero .l { font-size: 6.5px; font-weight: 800; letter-spacing: 0.1em; text-transform: uppercase; opacity: 0.9; margin-top: 3px; }
        .metric-plain { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; text-align: center; vertical-align: middle; width: 25%; }
        .metric-plain .v { font-size: 15px; font-weight: 800; color: #1e293b; }
        .metric-plain .l { font-size: 6.5px; font-weight: 800; letter-spacing: 0.1em; text-transform: uppercase; color: #64748b; margin-top: 3px; }

        .tbl { border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; margin-top: 6px; }
        .tbl th { background: #e8eef2; text-align: left; padding: 8px 10px; font-size: 6.5px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: #475569; }
        .tbl td { padding: 8px 10px; border-top: 1px solid #eef2f4; vertical-align: top; font-size: 8px; }
        .tag-crit { background: #fecaca; color: #991b1b; padding: 2px 7px; border-radius: 99px; font-size: 6.5px; font-weight: 800; }
        .tag-warn { background: #bfdbfe; color: #1e40af; padding: 2px 7px; border-radius: 99px; font-size: 6.5px; font-weight: 800; }
        .tag-info { background: #e0e7ff; color: #3730a3; padding: 2px 7px; border-radius: 99px; font-size: 6.5px; font-weight: 800; }
        .complex-bar { display: inline-block; width: 12px; height: 3px; border-radius: 2px; margin-right: 2px; }
        .bx-on { background: #525f71; }
        .bx-off { background: #e5e7eb; }

        .cta-dark { background: #0f172a; color: #e2e8f0; border-radius: 10px; padding: 18px 16px; margin-top: 14px; border: 1px solid #1e293b; }
        .cta-dark h2 { font-size: 14px; font-weight: 800; margin: 0 0 6px; color: #f1f5f9; }
        .cta-dark p { font-size: 8.5px; color: #94a3b8; margin: 0; line-height: 1.5; }
        .cta-accent { color: #5eead4; font-weight: 800; }
        .cta-stat { background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12); border-radius: 8px; padding: 10px; text-align: center; }
        .cta-stat .v { color: #5eead4; font-size: 14px; font-weight: 800; }
        .cta-stat .l { font-size: 6.5px; text-transform: uppercase; letter-spacing: 0.1em; color: #94a3b8; margin-top: 3px; }

        .footer { margin-top: 16px; padding-top: 12px; border-top: 1px solid #e2e8f0; font-size: 6.5px; color: #64748b; letter-spacing: 0.06em; text-transform: uppercase; }
        .badge-ok { font-size: 6.5px; font-weight: 800; padding: 2px 7px; border-radius: 4px; }
        .b-opt { background: rgba(0, 107, 96, 0.15); color: #006b60; }
        .b-act { background: rgba(72, 97, 126, 0.18); color: #48617e; }
        .b-slow { background: rgba(158, 63, 78, 0.15); color: #9e3f4e; }

        .sec-major { margin-top: 18px; padding-top: 12px; border-top: 1px solid #dce3e9; page-break-inside: avoid; }
        .sec-h { font-size: 14px; font-weight: 800; color: #1A237E; letter-spacing: -0.02em; margin: 0 0 10px; }
        .sec-h2 { font-size: 10px; font-weight: 800; color: #334155; margin: 12px 0 6px; }
        .metric-grid td { width: 25%; vertical-align: top; padding: 5px; }
        .metric-cell { background: #fff; border: 1px solid #e8edf2; border-radius: 8px; padding: 10px 8px; min-height: 44px; }
        .metric-cell .l { font-size: 7px; color: #64748b; font-weight: 600; }
        .metric-cell .v { font-size: 14px; font-weight: 800; color: #1e293b; margin-top: 3px; }

        .data-table { width: 100%; border: 1px solid #e2e8f0; margin-top: 6px; font-size: 7.5px; }
        .data-table th { background: #f1f5f9; padding: 6px 8px; text-align: left; font-size: 6.5px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #475569; }
        .data-table td { padding: 6px 8px; border-top: 1px solid #eef2f4; vertical-align: top; }
        .data-table tr:nth-child(even) td { background: #fafbfc; }

        .mod-shell { margin-top: 14px; padding: 12px; border: 1px solid #dbe4e7; border-radius: 10px; background: #f8fafc; page-break-inside: avoid; }
        .mod-title { font-size: 11px; font-weight: 800; color: #1e293b; margin-bottom: 8px; }
        .kv-grid { width: 100%; font-size: 7.5px; margin-bottom: 8px; }
        .kv-grid td { padding: 4px 8px; border: 1px solid #e8edf2; background: #fff; }
        .tbl-caption { font-size: 7px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; color: #64748b; margin: 10px 0 4px; }
        .rec-list { margin: 4px 0 0 16px; padding: 0; font-size: 8px; color: #334155; }
        .rec-list li { margin: 2px 0; }

        .bg-error-soft { background: #fff1f2; }
        .bg-warn-soft { background: #eff6ff; }
        .bg-ok-soft { background: #e6fffa; }
    </style>
</head>
<body>
@php
    $ui = is_array($auditUi ?? null) ? $auditUi : [];
    $pageData = is_array($ui['page_data'] ?? null) ? $ui['page_data'] : [];
    $kpis = is_array($ui['kpis'] ?? null) ? $ui['kpis'] : ($audit->audit_kpis ?? []);
    $overview = $kpis['overview'] ?? [];
    $onPage = $kpis['on_page_seo'] ?? [];
    $technical = $kpis['technical'] ?? [];
    $performance = $kpis['performance'] ?? [];
    $usability = $kpis['usability'] ?? [];
    $social = $kpis['social'] ?? [];
    $localSeo = $kpis['local_seo'] ?? [];
    $techEmail = $kpis['tech_email'] ?? [];
    $ga4 = $kpis['ga4'] ?? [];
    $gsc = $kpis['gsc'] ?? [];
    $psi = $ui['psi'] ?? data_get($kpis, 'google.pagespeed');
    $categoryScoresUi = is_array($ui['category_scores'] ?? null) ? $ui['category_scores'] : ($audit->category_scores ?? []);
    $categoryGradesUi = is_array($ui['category_grades'] ?? null) ? $ui['category_grades'] : ($audit->category_grades ?? []);

    $normalizePsi = function ($run, $fallback = []) {
        $metrics = (array) data_get($run, 'kpis', []);
        $fallback = (array) $fallback;
        return [
            'score' => data_get($metrics, 'score', data_get($metrics, 'categories.performance_score')),
            'lcp' => data_get($metrics, 'lcp', data_get($metrics, 'lcp_ms', data_get($metrics, 'lab_metrics.lcp_ms'))),
            'fcp' => data_get($metrics, 'fcp', data_get($metrics, 'fcp_ms', data_get($metrics, 'lab_metrics.fcp_ms'))),
            'cls' => data_get($metrics, 'cls', data_get($metrics, 'lab_metrics.cls')),
            'tti' => data_get($metrics, 'tti', data_get($metrics, 'tti_ms', data_get($metrics, 'lab_metrics.tti_ms'))),
            'tbt' => data_get($metrics, 'tbt', data_get($metrics, 'tbt_ms', data_get($metrics, 'lab_metrics.tbt_ms'))),
            'error' => data_get($run, 'error'),
            'status' => data_get($run, 'status'),
        ];
    };
    $lmMobile = $pageData['lighthouse_mobile'] ?? $page?->lighthouse_mobile;
    $lmDesktop = $pageData['lighthouse_desktop'] ?? $page?->lighthouse_desktop;
    $mobile = $normalizePsi(data_get($psi, 'mobile'), $lmMobile);
    $desktop = $normalizePsi(data_get($psi, 'desktop'), $lmDesktop);
    $host = parse_url($audit->url, PHP_URL_HOST) ?: $audit->url;
    $summaryArr = is_array($audit->summary) ? $audit->summary : [];
    $summaryText = trim((string) ($summaryArr['overview'] ?? $summaryArr['summary'] ?? ''));
    if ($summaryText === '') {
        $summaryText = 'This report combines crawl findings, performance data, metadata checks, issue severity, and optional integrations to surface the clearest SEO priorities.';
    }
    $fmtNumber = function ($value) {
        if ($value === null || $value === '' || $value === false) return 'N/A';
        return is_numeric($value) ? number_format((float) $value, $value == (int) $value ? 0 : 2) : $value;
    };
    $fmtBool = function ($value) {
        if ($value === null || $value === '') return 'N/A';
        return $value ? 'Yes' : 'No';
    };
    $fmtSeconds = function ($valueMs) {
        if ($valueMs === null || $valueMs === '') return 'N/A';
        return number_format(((float) $valueMs) / 1000, 2) . 's';
    };
    $fmtMb = function ($value) {
        if ($value === null || $value === '') return 'N/A';
        return is_numeric($value) ? number_format((float) $value, 2) . ' MB' : $value;
    };
    $scoreWidth = function ($score) {
        if (!is_numeric($score)) return 4;
        return max(4, min(100, (float) $score));
    };
    $gradeFromScore = function ($score) {
        if ($score === null || $score === '' || ! is_numeric($score)) return null;
        $s = (int) round((float) $score);
        if ($s >= 95) return 'A+';
        if ($s >= 90) return 'A';
        if ($s >= 80) return 'B';
        if ($s >= 70) return 'C';
        if ($s >= 60) return 'D';
        return 'F';
    };
    $issuesHigh = $issues->where('impact', 'high')->count();
    $issuesMedium = $issues->where('impact', 'medium')->count();
    $issuesLow = $issues->where('impact', 'low')->count();
    $critUi = $issues->filter(fn ($i) => ($i->impact ?? '') === 'high')->count();
    $warnUi = $issues->filter(fn ($i) => ($i->impact ?? '') === 'medium')->count();
    $infoUi = max(0, $issues->count() - $critUi - $warnUi);
    $overallScore = (int) ($audit->overall_score ?? 0);
    $overallScoreDisp = min(100, max(0, $overallScore));
    $overallGrade = $audit->overall_grade ?? null;
    if (($overallGrade === null || $overallGrade === '') && $overallScore > 0) $overallGrade = $gradeFromScore($overallScore);
    if ($overallGrade === null || $overallGrade === '') $overallGrade = 'N/A';
    $reportWhen = $audit->finished_at ?? $audit->created_at;
    $protocolLabel = str_starts_with(strtolower((string) $audit->url), 'https') ? 'HTTPS Secure' : 'HTTP (review TLS)';
    $robotsSnippet = strtolower((string) ($pageData['robots_meta'] ?? $page?->robots_meta ?? ''));
    if (str_contains($robotsSnippet, 'noindex')) $indexingLabel = 'Noindex on page';
    elseif (!empty($technical['blocked_by_robots'])) $indexingLabel = 'Crawl restricted';
    else $indexingLabel = 'Indexable (baseline)';
    $lcpMs = $mobile['lcp'] ?? data_get($lmMobile, 'lab_metrics.lcp_ms');
    $loadTimeLabel = $fmtSeconds($lcpMs);
    if (is_numeric($lcpMs) && (float) $lcpMs <= 2500) $loadTimeNote = 'On target';
    elseif (is_numeric($lcpMs) && (float) $lcpMs <= 4000) $loadTimeNote = 'Needs work';
    elseif (is_numeric($lcpMs)) $loadTimeNote = 'Slow LCP';
    else $loadTimeNote = 'N/A';
    $passedVerifications = (int) ($overview['passed_checks'] ?? 0);
    if ($passedVerifications < 1) {
        $checklist = [
            str_starts_with(strtolower((string) $audit->url), 'https'),
            (int) ($pageData['status_code'] ?? $page?->status_code ?? 0) === 200,
            !empty($pageData['title'] ?? $page?->title),
            !empty($pageData['meta_description'] ?? $page?->meta_description),
            (int) ($pageData['h1_count'] ?? $page?->h1_count ?? 0) >= 1,
            !empty($technical['robots_txt_present']),
            !empty($technical['xml_sitemap_present']),
            empty($technical['blocked_by_robots']),
        ];
        $passedVerifications = count(array_filter($checklist));
    }
    $lhCats = data_get($lmMobile, 'categories', []);
    $lhPerf = $lhCats['performance_score'] ?? $mobile['score'] ?? null;
    $lhA11y = $lhCats['accessibility_score'] ?? null;
    $lhBp = $lhCats['best_practices_score'] ?? null;
    $lhSeo = $lhCats['seo_score'] ?? null;
    $pageLm = is_array($page?->link_metrics_json) ? $page->link_metrics_json : [];
    $lmKpi = is_array($kpis['link_metrics'] ?? null) ? $kpis['link_metrics'] : [];
    $authScoreRaw = $pageLm['authority_score'] ?? null;
    $totalBl = (int) (data_get($lmKpi, 'overview.total_backlink_rows_tracked') ?? $pageLm['backlinks'] ?? 0);
    $refDom = (int) ($pageLm['referring_domains'] ?? 0);
    $fmtCompact = function ($n) {
        if (!is_numeric($n) || (int) $n <= 0) return '—';
        $n = (float) $n;
        return $n >= 1000 ? number_format($n / 1000, 1) . 'K' : (string) (int) $n;
    };
    $authScoreDisp = is_numeric($authScoreRaw) ? (string) (int) round((float) $authScoreRaw) : '—';
    $schemaList = $onPage['schema_types'] ?? ($pageData['schema_types'] ?? []);
    if ($schemaList instanceof \Illuminate\Support\Collection) $schemaList = $schemaList->all();
    $schemaCount = is_array($schemaList) ? count($schemaList) : 0;
    $schemaBadge = $schemaCount > 0 ? 'OPTIMIZED' : 'REVIEW';
    $schemaBadgeClass = $schemaCount > 0 ? 'b-opt' : 'b-slow';
    $httpsOn = !empty($technical['https_enabled']) || str_starts_with(strtolower((string) $audit->url), 'https');
    $secBadge = $httpsOn ? 'ACTIVE' : 'ATTENTION';
    $secBadgeClass = $httpsOn ? 'b-act' : 'b-slow';
    $lcpBadgeClass = is_numeric($lcpMs) && (float) $lcpMs > 4000 ? 'b-slow' : (is_numeric($lcpMs) && (float) $lcpMs > 2500 ? 'b-act' : 'b-opt');
    $linkEquityNote = ($issuesMedium + $issuesHigh) > 0 ? (string) ($issuesMedium + $issuesHigh) . ' risk themes' : '—';
    $gscClicks = data_get($gsc, 'summary.total_clicks');
    $gaSess = data_get($ga4, 'summary.total_sessions');
    $nums = array_values(array_filter($categoryScoresUi, 'is_numeric'));
    $avgCat = $nums !== [] ? (int) round(array_sum($nums) / count($nums)) : null;

    $complexityTier = function ($issue) {
        $eff = strtolower((string) ($issue->effort ?? ''));
        if (str_contains($eff, 'high') || $eff === 'h') return 3;
        if (str_contains($eff, 'med') || str_contains($eff, 'mid') || $eff === 'm') return 2;
        return match ($issue->impact ?? '') { 'high' => 3, 'medium' => 2, default => 1 };
    };
    $impactTag = function ($issue) {
        $s = $issue->severity ?? match ($issue->impact ?? '') { 'high' => 'critical', 'medium' => 'warning', default => 'info' };
        return match ($s) {
            'critical' => ['label' => 'Critical', 'class' => 'tag-crit'],
            'warning' => ['label' => 'Warning', 'class' => 'tag-warn'],
            default => ['label' => 'Info', 'class' => 'tag-info'],
        };
    };

    $extMap = [
        'segmentation' => 'overview', 'near_duplicate_content' => 'onpage', 'spelling_grammar' => 'onpage',
        'js_rendering' => 'technical', 'site_visualisations' => 'technical', 'custom_source_search' => 'technical',
        'custom_extraction' => 'technical', 'forms_auth_summary' => 'technical', 'link_metrics' => 'integrations',
    ];
    $coreKeys = ['overview', 'on_page_seo', 'technical', 'performance', 'integrations'];
    $rm = $ui['report_modules'] ?? null;
    $modulesRaw = is_array($rm) ? ($rm['modules'] ?? []) : [];
    $moduleOrder = is_array($rm) ? ($rm['module_order'] ?? []) : [];
    $orderedMods = [];
    if (count($moduleOrder)) {
        foreach ($moduleOrder as $key) {
            foreach ($modulesRaw as $m) {
                if (($m['module_key'] ?? '') === $key) { $orderedMods[] = $m; break; }
            }
        }
        $seen = array_column($orderedMods, 'module_key');
        foreach ($modulesRaw as $m) {
            if (!in_array($m['module_key'] ?? '', $seen, true)) { $orderedMods[] = $m; $seen[] = $m['module_key'] ?? ''; }
        }
    } else {
        $orderedMods = $modulesRaw;
    }
    $extendedMods = array_values(array_filter($orderedMods, fn ($m) => !in_array($m['module_key'] ?? '', $coreKeys, true)));
    $buckets = ['overview' => [], 'onpage' => [], 'technical' => [], 'performance' => [], 'integrations' => []];
    foreach ($extendedMods as $m) {
        $tab = $extMap[$m['module_key'] ?? ''] ?? 'technical';
        if (isset($buckets[$tab])) $buckets[$tab][] = $m;
        else $buckets['technical'][] = $m;
    }

    $ogGrades = is_array($overview['category_grades'] ?? null) ? $overview['category_grades'] : [];
    $duplicateTitles = collect($onPage['duplicate_titles_table'] ?? [])->take(100);
    $missingMeta = collect($onPage['missing_meta_table'] ?? [])->take(100);
    $missingH1 = collect($onPage['missing_h1_table'] ?? [])->take(100);
    $brokenLinks = collect($technical['broken_links_examples'] ?? [])->take(100);
    $redirectChains = collect($technical['redirect_chains_examples'] ?? [])->take(100);
    $non200Pages = collect($technical['non_200_pages'] ?? [])->take(100);
    $heavyAssets = collect($performance['heavy_assets'] ?? [])->take(80);
    $securityHeaders = collect($technical['security_headers_list'] ?? [])->take(50);
    $topKeywords = collect($onPage['top_keywords'] ?? [])->take(30);
    $mobileOpp = collect($usability['mobile_opportunities'] ?? [])->take(40);
    $desktopOpp = collect($usability['desktop_opportunities'] ?? [])->take(40);
    $gaTopPages = collect($ga4['top_pages'] ?? [])->take(40);
    $gscQueries = collect($gsc['top_queries'] ?? [])->take(40);
    $statusDist = $technical['status_code_distribution'] ?? [];
    $resBreak = $performance['resources_breakdown'] ?? [];

    $fmtVal = function ($v) {
        if ($v === null || $v === '') return '—';
        if (! is_numeric($v)) return is_scalar($v) ? (string) $v : json_encode($v);
        $f = (float) $v;
        return ((int) $f == $f) ? (string) (int) $f : number_format($f, 2);
    };
    $strTrunc = fn ($s, $n = 160) => \Illuminate\Support\Str::limit(is_scalar($s) ? (string) $s : json_encode($s), $n);
    $issueCap = 250;
@endphp

{{-- Brand (no faux navigation) --}}
<div class="brand-bar">
    <div class="headline">Backlink Pro</div>
    <div class="sub-brand">SEO Intelligence Ledger</div>
</div>
@if(($audit->status ?? '') === 'failed' && !empty($audit->error))
    <div style="margin-bottom:12px;padding:10px 12px;border:1px solid #fecaca;background:#fef2f2;border-radius:8px;font-size:8px;color:#991b1b;">
        <strong>Audit failed.</strong> {{ $strTrunc($audit->error, 500) }}
    </div>
@endif

<table cellpadding="0" cellspacing="0" style="margin-bottom:14px;">
    <tr>
        <td style="width:62%; vertical-align:top; padding-right:12px;">
            <div class="pill-badge">Architectural Audit Report</div>
            <div class="hero-domain" style="margin-top:10px;">{{ $host }}</div>
            <div class="hero-url">{{ $audit->url }}</div>
            <div class="hero-lead">
                Executive summary — performance and crawl integrity review
                @if($reportWhen) as of {{ $reportWhen->format('F j, Y') }}. @endif
            </div>
            <table cellpadding="0" cellspacing="0" style="margin-top:12px;">
                <tr>
                    <td style="padding-right:14px;">
                        <div class="meta-k">Protocol</div>
                        <div class="meta-v">{{ $protocolLabel }}</div>
                    </td>
                    <td style="border-left:1px solid #e2e8f0; padding:0 14px;">
                        <div class="meta-k">Indexing</div>
                        <div class="meta-v">{{ $indexingLabel }}</div>
                    </td>
                    <td style="border-left:1px solid #e2e8f0; padding-left:14px;">
                        <div class="meta-k">Load (LCP)</div>
                        <div class="meta-v">{{ $loadTimeLabel }} — {{ $loadTimeNote }}</div>
                    </td>
                </tr>
            </table>
        </td>
        <td style="width:38%; vertical-align:middle;">
            <div class="donut-wrap">
                <svg width="130" height="130" viewBox="0 0 36 36" style="display:block;margin:0 auto;">
                    <path fill="none" stroke="#eaeff1" stroke-width="3" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                    <path fill="none" stroke="#006b60" stroke-width="3" stroke-linecap="round" stroke-dasharray="{{ $overallScoreDisp }} {{ 100 - $overallScoreDisp }}"
                          d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" transform="rotate(-90 18 18)"/>
                </svg>
                <div style="margin-top:-112px;">
                    <div class="donut-score">{{ $overallScoreDisp }}</div>
                    <div class="donut-grade">GRADE {{ strtoupper($overallGrade) }}</div>
                </div>
            </div>
        </td>
    </tr>
</table>

<table cellpadding="0" cellspacing="8" style="margin:0 -4px;">
    <tr>
        <td class="card-3">
            <table width="100%" cellpadding="0" cellspacing="0"><tr>
                <td><span class="icon-box bg-error-soft" style="color:#9e3f4e;">&#9888;</span></td>
                <td class="big" style="text-align:right;">{{ str_pad((string) $issuesHigh, 2, '0', STR_PAD_LEFT) }}</td>
            </tr></table>
            <h3>Critical</h3>
            <p>High-impact findings from this crawl.</p>
        </td>
        <td class="card-3">
            <table width="100%" cellpadding="0" cellspacing="0"><tr>
                <td><span class="icon-box bg-warn-soft" style="color:#48617e;">&#9888;</span></td>
                <td class="big" style="text-align:right;">{{ str_pad((string) $issuesMedium, 2, '0', STR_PAD_LEFT) }}</td>
            </tr></table>
            <h3>Warnings</h3>
            <p>Medium-impact issues to schedule.</p>
        </td>
        <td class="card-3">
            <table width="100%" cellpadding="0" cellspacing="0"><tr>
                <td><span class="icon-box bg-ok-soft" style="color:#006b60;">&#10003;</span></td>
                <td class="big" style="text-align:right;">{{ str_pad((string) min(99, max(0, $passedVerifications)), 2, '0', STR_PAD_LEFT) }}</td>
            </tr></table>
            <h3>Passed checks</h3>
            <p>Baseline checks satisfied.</p>
        </td>
    </tr>
</table>

<div class="section-wrap">
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td><div class="section-title">Performance core (mobile lab)</div><div class="section-sub">Lighthouse categories when available.</div></td>
            <td style="text-align:right;"><span class="device-tag">MOBILE · LAB</span></td>
        </tr>
    </table>
    <table cellpadding="0" cellspacing="6" style="margin-top:8px;">
        <tr>
            @foreach([['Performance',$lhPerf],['Accessibility',$lhA11y],['Best practices',$lhBp],['SEO',$lhSeo]] as $core)
                <td class="core-card">
                    <div class="core-val">{{ $fmtNumber($core[1]) }}</div>
                    <div class="core-lbl">{{ $core[0] }}</div>
                    <div class="bar-bg"><div class="bar-fg" style="width:{{ $scoreWidth($core[1]) }}%;"></div></div>
                </td>
            @endforeach
        </tr>
    </table>
</div>

<table cellpadding="0" cellspacing="0" style="margin-top:12px;">
    <tr>
        <td style="width:50%; vertical-align:top; padding-right:8px;">
            <div class="col-title">Technical integrity</div>
            <div class="row-tech"><table width="100%"><tr><td style="font-weight:700;">Structured data</td><td style="text-align:right;"><span class="badge-ok {{ $schemaBadgeClass }}">{{ $schemaBadge }}</span></td></tr></table></div>
            <div class="row-tech"><table width="100%"><tr><td style="font-weight:700;">HTTPS & crawl</td><td style="text-align:right;"><span class="badge-ok {{ $secBadgeClass }}">{{ $secBadge }}</span></td></tr></table></div>
            <div class="row-tech"><table width="100%"><tr><td style="font-weight:700;">LCP</td><td style="text-align:right;"><span class="badge-ok {{ $lcpBadgeClass }}">{{ $loadTimeLabel }}</span></td></tr></table></div>
        </td>
        <td style="width:50%; vertical-align:top; padding-left:8px;">
            <div class="col-title">Content narrative</div>
            <div class="quote-box">"{{ $summaryText }}"</div>
        </td>
    </tr>
</table>

<table cellpadding="0" cellspacing="8" style="margin:12px -4px 0;">
    <tr>
        <td class="metric-hero"><div class="v">{{ $authScoreDisp }}</div><div class="l">Authority</div></td>
        <td class="metric-plain"><div class="v">{{ $fmtCompact($totalBl > 0 ? $totalBl : null) }}</div><div class="l">Backlinks</div></td>
        <td class="metric-plain"><div class="v">{{ $refDom > 0 ? $fmtNumber($refDom) : '—' }}</div><div class="l">Ref. domains</div></td>
        <td class="metric-plain"><div class="v">{{ $linkEquityNote }}</div><div class="l">Equity notes</div></td>
    </tr>
</table>

<div style="margin-top:14px;">
    <div class="section-title" style="margin-bottom:6px;">Priority issues (sample)</div>
    <table class="tbl" cellspacing="0">
        <thead><tr><th style="width:12%;">Status</th><th style="width:44%;">Issue</th><th style="width:14%;">Complex</th><th style="width:30%;">Summary</th></tr></thead>
        <tbody>
        @foreach($issues->take(18) as $issue)
            @php
                $tag = $impactTag($issue);
                $cx = $complexityTier($issue);
            @endphp
            <tr>
                <td><span class="{{ $tag['class'] }}">{{ $tag['label'] }}</span></td>
                <td class="truncate"><strong>{{ $issue->title ?? $issue->message ?? $issue->code }}</strong></td>
                <td>@for($i=1;$i<=3;$i++)<span class="complex-bar {{ $i <= $cx ? 'bx-on' : 'bx-off' }}"></span>@endfor</td>
                <td class="truncate">{{ $strTrunc(strip_tags((string) ($issue->recommendation ?: $issue->description ?: '')), 200) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<div class="cta-dark">
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width:56%; vertical-align:top; padding-right:10px;">
                <h2>Impact profile</h2>
                <p><span class="cta-accent">{{ $critUi }} critical</span>, <span class="cta-accent">{{ $warnUi }} warnings</span>, <span class="cta-accent">{{ $infoUi }} info</span>.
                    @if($avgCat !== null) Avg scored pillar: <span class="cta-accent">{{ $avgCat }}/100</span>.@endif</p>
            </td>
            <td style="width:44%;">
                <table width="100%" cellpadding="0" cellspacing="6"><tr>
                    <td class="cta-stat" width="50%"><div class="v">{{ $fmtNumber($gscClicks) }}</div><div class="l">GSC clicks</div></td>
                    <td class="cta-stat" width="50%"><div class="v">{{ $fmtNumber($gaSess) }}</div><div class="l">GA4 sessions</div></td>
                </tr></table>
            </td>
        </tr>
    </table>
</div>

<div class="footer">
    <table width="100%"><tr>
        <td>&copy; {{ now()->format('Y') }} Backlink Pro — confidential</td>
        <td style="text-align:right;">{{ now()->format('M j, Y g:i A') }}</td>
    </tr></table>
</div>

{{-- ===== Full report (matches app tabs) ===== --}}
<div class="page-break"></div>
<div class="sec-major">
    <h1 class="sec-h">Overview</h1>
    <table class="metric-grid" cellspacing="6"><tr>
        @foreach([
            ['Pages crawled', $fmtNumber($overview['pages_crawled_count'] ?? $audit->pages_scanned ?? $audit->pages->count())],
            ['Recommendations', $fmtNumber($overview['recommendations_count'] ?? $issues->count())],
            ['Warnings', $fmtNumber($overview['warnings_count'] ?? $issuesMedium)],
            ['Passed checks', $fmtNumber($overview['passed_checks'] ?? $passedVerifications)],
        ] as $mc)
            <td><div class="metric-cell"><div class="l">{{ $mc[0] }}</div><div class="v">{{ $mc[1] }}</div></div></td>
        @endforeach
    </tr></table>
    <table class="metric-grid" cellspacing="6" style="margin-top:4px;"><tr>
        @foreach([
            ['Critical (UI)', $fmtNumber($critUi)], ['Warnings (UI)', $fmtNumber($warnUi)], ['Info / opportunities', $fmtNumber($infoUi)], ['Status', strtoupper($audit->status ?? '—')],
        ] as $mc)
            <td><div class="metric-cell"><div class="l">{{ $mc[0] }}</div><div class="v">{{ $mc[1] }}</div></div></td>
        @endforeach
    </tr></table>

    @if(count($ogGrades))
        <h2 class="sec-h2">Category grades (KPI)</h2>
        <table class="data-table" cellspacing="0">
            <thead><tr><th>Pillar</th><th>Grade</th></tr></thead>
            <tbody>
            @foreach($ogGrades as $gk => $gv)
                <tr><td>{{ str_replace('_', ' ', $gk) }}</td><td>{{ $gv ?? '—' }}</td></tr>
            @endforeach
            </tbody>
        </table>
    @endif

    <h2 class="sec-h2">Category scores & grades (audit)</h2>
    <table class="data-table" cellspacing="0">
        <thead><tr><th>Key</th><th>Score</th><th>Grade</th></tr></thead>
        <tbody>
        @foreach($categoryScoresUi as $ck => $cv)
            <tr>
                <td>{{ str_replace('_', ' ', $ck) }}</td>
                <td>{{ $fmtNumber($cv) }}</td>
                <td>{{ $categoryGradesUi[$ck] ?? ($gradeFromScore($cv) ?? '—') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <h2 class="sec-h2">Homepage snapshot</h2>
    <table class="data-table" cellspacing="0">
        <tbody>
            <tr><th>Title</th><td>{{ $strTrunc($pageData['title'] ?? $page?->title ?? '—', 400) }}</td></tr>
            <tr><th>Meta</th><td>{{ $strTrunc($pageData['meta_description'] ?? $page?->meta_description ?? '—', 400) }}</td></tr>
            <tr><th>Status</th><td>{{ $fmtNumber($pageData['status_code'] ?? $page?->status_code) }}</td></tr>
            <tr><th>Words</th><td>{{ $fmtNumber($pageData['word_count'] ?? $page?->word_count) }}</td></tr>
        </tbody>
    </table>

    <h2 class="sec-h2">All issues ({{ $issues->count() }}, showing {{ min($issueCap, $issues->count()) }})</h2>
    <table class="data-table" cellspacing="0">
        <thead><tr><th>Sev</th><th>Issue</th><th>Affected</th><th>Recommendation</th></tr></thead>
        <tbody>
        @foreach($issues->take($issueCap) as $issue)
            @php
                $tag = $impactTag($issue);
            @endphp
            <tr>
                <td><span class="{{ $tag['class'] }}">{{ $tag['label'] }}</span></td>
                <td class="truncate">{{ $issue->title ?? $issue->code }}</td>
                <td>{{ $fmtNumber($issue->affected_count) }}</td>
                <td class="truncate">{{ $strTrunc($issue->recommendation ?: $issue->description, 220) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    @if(count($buckets['overview']))
        <h2 class="sec-h2">Advanced modules — overview</h2>
        @include('audit.pdf-modules', ['modules' => $buckets['overview'], 'fmtVal' => $fmtVal, 'strTrunc' => $strTrunc])
    @endif
</div>

<div class="sec-major page-break"></div>
<h1 class="sec-h">On-Page SEO</h1>
@php $onPageMetrics = [
    ['Title len', $fmtNumber($onPage['title_length'] ?? $pageData['title_len'] ?? null)],
    ['Meta len', $fmtNumber($onPage['meta_description_length'] ?? $pageData['meta_len'] ?? null)],
    ['H1 present', $fmtBool($onPage['h1_present'] ?? null)],
    ['Missing alt', $fmtNumber($onPage['images_missing_alt_total'] ?? $pageData['images_missing_alt'] ?? null)],
    ['Thin pages', $fmtNumber($onPage['thin_pages_count'] ?? null)],
    ['Title dups', $fmtNumber($onPage['title_duplicate_count'] ?? null)],
    ['Meta dups', $fmtNumber($onPage['meta_duplicate_count'] ?? null)],
    ['Keyword consistency', $fmtBool($onPage['keyword_consistency_flag'] ?? null)],
]; @endphp
@foreach(array_chunk($onPageMetrics, 4) as $chunk)
<table class="metric-grid" cellspacing="6" style="margin-top:4px;"><tr>
    @foreach($chunk as $mc)
        <td><div class="metric-cell"><div class="l">{{ $mc[0] }}</div><div class="v">{{ $mc[1] }}</div></div></td>
    @endforeach
</tr></table>
@endforeach
<h2 class="sec-h2">Content signals</h2>
<table class="data-table" cellspacing="0">
    <tbody>
        <tr><th>Canonical</th><td class="truncate">{{ $strTrunc($onPage['canonical_url'] ?? $pageData['canonical_url'] ?? '—', 300) }}</td></tr>
        <tr><th>Language</th><td>{{ $onPage['lang_declared'] ?? 'N/A' }}</td></tr>
        <tr><th>Analytics</th><td>{{ $onPage['analytics_tool_name'] ?? 'Not detected' }}</td></tr>
        <tr><th>Schema types</th><td>{{ is_array($schemaList) && count($schemaList) ? collect($schemaList)->flatten()->filter()->take(20)->implode(', ') : 'Not detected' }}</td></tr>
    </tbody>
</table>
<h2 class="sec-h2">Top keywords</h2>
<p style="font-size:8px;color:#475569;">{{ $topKeywords->map(fn($i) => ($i['keyword'] ?? '') . (isset($i['count']) ? ' ('.$i['count'].')' : ''))->filter()->take(40)->implode(' · ') ?: '—' }}</p>

<h2 class="sec-h2">Duplicate titles</h2>
<table class="data-table" cellspacing="0"><thead><tr><th>URL</th><th>Title</th></tr></thead><tbody>
@forelse($duplicateTitles as $row)
    <tr><td class="truncate">{{ $strTrunc($row['url'] ?? '', 120) }}</td><td class="truncate">{{ $strTrunc($row['title'] ?? '', 120) }}</td></tr>
@empty <tr><td colspan="2">—</td></tr> @endforelse
</tbody></table>
<h2 class="sec-h2">Missing meta</h2>
<table class="data-table" cellspacing="0"><thead><tr><th>URL</th></tr></thead><tbody>
@forelse($missingMeta as $row) <tr><td class="truncate">{{ $strTrunc($row['url'] ?? '', 200) }}</td></tr> @empty <tr><td>—</td></tr> @endforelse
</tbody></table>
<h2 class="sec-h2">Missing H1</h2>
<table class="data-table" cellspacing="0"><thead><tr><th>URL</th></tr></thead><tbody>
@forelse($missingH1 as $row) <tr><td class="truncate">{{ $strTrunc($row['url'] ?? '', 200) }}</td></tr> @empty <tr><td>—</td></tr> @endforelse
</tbody></table>
@if(count($buckets['onpage']))
    <h2 class="sec-h2">Advanced modules — on-page</h2>
    @include('audit.pdf-modules', ['modules' => $buckets['onpage'], 'fmtVal' => $fmtVal, 'strTrunc' => $strTrunc])
@endif

<div class="sec-major page-break"></div>
<h1 class="sec-h">Technical</h1>
@php $techMetrics = [
    ['HTTPS', $fmtBool($technical['https_enabled'] ?? null)], ['HTTPS redirect', $fmtBool($technical['https_redirect_ok'] ?? null)],
    ['robots.txt', $fmtBool($technical['robots_txt_present'] ?? null)], ['XML sitemap', $fmtBool($technical['xml_sitemap_present'] ?? null)],
    ['Broken links', $fmtNumber($technical['broken_links_count'] ?? null)], ['Redirect chains', $fmtNumber($technical['redirect_chains_count'] ?? null)],
    ['Indexability', $fmtNumber($technical['indexability_issues_count'] ?? null)], ['Canonical present', $fmtNumber($technical['canonical_present_count'] ?? null)],
]; @endphp
@foreach(array_chunk($techMetrics, 4) as $chunk)
<table class="metric-grid" cellspacing="6" style="margin-top:4px;"><tr>
    @foreach($chunk as $mc)
        <td><div class="metric-cell"><div class="l">{{ $mc[0] }}</div><div class="v">{{ $mc[1] }}</div></div></td>
    @endforeach
</tr></table>
@endforeach
<h2 class="sec-h2">Discovery</h2>
<table class="data-table" cellspacing="0">
    <tbody>
        <tr><th>robots.txt URL</th><td class="truncate">{{ $strTrunc($technical['robots_txt_url'] ?? 'Not found', 250) }}</td></tr>
        <tr><th>Sitemap URL</th><td class="truncate">{{ $strTrunc($technical['sitemap_url'] ?? 'Not found', 250) }}</td></tr>
        <tr><th>Blocked</th><td>{{ $fmtBool($technical['blocked_by_robots'] ?? null) }}</td></tr>
    </tbody>
</table>
<h2 class="sec-h2">Status distribution</h2>
<table class="metric-grid" cellspacing="6"><tr>
    @foreach(['2xx','3xx','4xx','5xx'] as $sx)
        <td><div class="metric-cell"><div class="l">{{ $sx }}</div><div class="v">{{ $fmtNumber($statusDist[$sx] ?? null) }}</div></div></td>
    @endforeach
</tr></table>

<h2 class="sec-h2">Broken links, redirects, non-200</h2>
<table class="data-table" cellspacing="0"><thead><tr><th>From</th><th>To</th><th>Code</th></tr></thead><tbody>
@forelse($brokenLinks as $row) <tr><td class="truncate">{{ $strTrunc($row['from_url'] ?? $row['from'] ?? '', 100) }}</td><td class="truncate">{{ $strTrunc($row['to_url'] ?? '', 100) }}</td><td>{{ $fmtNumber($row['status_code'] ?? null) }}</td></tr> @empty <tr><td colspan="3">—</td></tr> @endforelse
</tbody></table>
<table class="data-table" cellspacing="0" style="margin-top:8px;"><thead><tr><th>From</th><th>To</th><th>Hops</th></tr></thead><tbody>
@forelse($redirectChains as $row) <tr><td class="truncate">{{ $strTrunc($row['from_url'] ?? '', 100) }}</td><td class="truncate">{{ $strTrunc($row['to_url'] ?? '', 100) }}</td><td>{{ $fmtNumber($row['redirect_hops'] ?? null) }}</td></tr> @empty <tr><td colspan="3">—</td></tr> @endforelse
</tbody></table>
<table class="data-table" cellspacing="0" style="margin-top:8px;"><thead><tr><th>URL</th><th>Status</th></tr></thead><tbody>
@forelse($non200Pages as $row) <tr><td class="truncate">{{ $strTrunc($row['url'] ?? '', 200) }}</td><td>{{ $fmtNumber($row['status_code'] ?? null) }}</td></tr> @empty <tr><td colspan="2">—</td></tr> @endforelse
</tbody></table>

<h2 class="sec-h2">Security headers</h2>
<table class="data-table" cellspacing="0"><thead><tr><th>Header</th><th>With</th><th>Total</th></tr></thead><tbody>
@forelse($securityHeaders as $row)
    <tr><td>{{ $row['header'] ?? '—' }}</td><td>{{ $fmtNumber($row['pages_with_header'] ?? null) }}</td><td>{{ $fmtNumber($row['total_pages'] ?? null) }}</td></tr>
@empty <tr><td colspan="3">—</td></tr> @endforelse
</tbody></table>
@if(count($buckets['technical']))
    <h2 class="sec-h2">Advanced modules — technical</h2>
    @include('audit.pdf-modules', ['modules' => $buckets['technical'], 'fmtVal' => $fmtVal, 'strTrunc' => $strTrunc])
@endif

<div class="sec-major page-break"></div>
<h1 class="sec-h">Performance</h1>
@if($psi)
    @foreach(['mobile' => 'Mobile', 'desktop' => 'Desktop'] as $modeKey => $modeLabel)
        @php
            $run = data_get($psi, $modeKey);
            $fb = $modeKey === 'mobile' ? $lmMobile : $lmDesktop;
            $met = $normalizePsi($run, $fb);
        @endphp
        <h2 class="sec-h2">{{ $modeLabel }} PageSpeed</h2>
        @if(is_array($run) && ($run['status'] ?? '') === 'failed' && $met['score'] === null)
            <p style="font-size:8px;color:#9e3f4e;">{{ $run['error'] ?? 'Run failed.' }}</p>
        @else
            <table class="metric-grid" cellspacing="6"><tr>
                @foreach([['Score',$met['score']], ['LCP (s)', is_numeric($met['lcp'] ?? null) ? number_format((float)$met['lcp']/1000,2) : 'N/A'], ['FCP (s)', is_numeric($met['fcp'] ?? null) ? number_format((float)$met['fcp']/1000,2) : 'N/A'], ['CLS',$met['cls'] ?? 'N/A']] as $mc)
                    <td><div class="metric-cell"><div class="l">{{ $mc[0] }}</div><div class="v">{{ is_numeric($mc[1] ?? null) ? $fmtNumber($mc[1]) : $mc[1] }}</div></div></td>
                @endforeach
            </tr></table>
            <table class="metric-grid" cellspacing="6"><tr>
                @foreach([['TBT (s)', is_numeric($met['tbt'] ?? null) ? number_format((float)$met['tbt']/1000,2) : 'N/A'], ['TTI (s)', is_numeric($met['tti'] ?? null) ? number_format((float)$met['tti']/1000,2) : 'N/A']] as $mc)
                    <td><div class="metric-cell"><div class="l">{{ $mc[0] }}</div><div class="v">{{ $mc[1] }}</div></div></td>
                @endforeach
            </tr></table>
        @endif
    @endforeach
@else
    <p style="font-size:8px;color:#64748b;">No aggregated PageSpeed payload on audit KPIs (per-page lighthouse may still exist below).</p>
@endif

<table class="metric-grid" cellspacing="6"><tr>
    @foreach([
        ['Download size', $fmtMb($performance['total_download_size_mb'] ?? null)],
        ['Total objects', $fmtNumber($resBreak['total_objects'] ?? null)],
        ['JS resources', $fmtNumber($resBreak['js_resources_count'] ?? null)],
        ['Images', $fmtNumber($resBreak['images_count'] ?? null)],
    ] as $mc)
        <td><div class="metric-cell"><div class="l">{{ $mc[0] }}</div><div class="v">{{ $mc[1] }}</div></div></td>
    @endforeach
</tr></table>
<h2 class="sec-h2">Heavy assets</h2>
<table class="data-table" cellspacing="0"><thead><tr><th>Asset</th><th>Type</th><th>KB</th></tr></thead><tbody>
@forelse($heavyAssets as $row) <tr><td class="truncate">{{ $strTrunc($row['asset_url'] ?? '', 200) }}</td><td>{{ $row['type'] ?? '—' }}</td><td>{{ $fmtNumber($row['size_kb'] ?? null) }}</td></tr> @empty <tr><td colspan="3">—</td></tr> @endforelse
</tbody></table>
<h2 class="sec-h2">Optimization opportunities (mobile + desktop usability)</h2>
<table class="data-table" cellspacing="0"><thead><tr><th>Opportunity</th><th>Est. savings (s)</th></tr></thead><tbody>
@php $oppRows = $mobileOpp->concat($desktopOpp); @endphp
@forelse($oppRows as $row)
    <tr><td class="truncate">{{ $strTrunc($row['name'] ?? $row['title'] ?? '', 200) }}</td><td>{{ isset($row['estimated_savings_sec']) ? number_format((float) $row['estimated_savings_sec'], 2) : '—' }}</td></tr>
@empty <tr><td colspan="2">—</td></tr> @endforelse
</tbody></table>
@if(count($buckets['performance']))
    <h2 class="sec-h2">Advanced modules — performance</h2>
    @include('audit.pdf-modules', ['modules' => $buckets['performance'], 'fmtVal' => $fmtVal, 'strTrunc' => $strTrunc])
@endif

<div class="sec-major page-break"></div>
<h1 class="sec-h">Integrations</h1>
<h2 class="sec-h2">Google Analytics 4</h2>
@if(is_array($ga4) && ($ga4['connected'] ?? true) === false)
    <p style="font-size:8px;">{{ $ga4['message'] ?? 'Not connected.' }}</p>
@elseif(!empty($ga4['summary']))
    <table class="metric-grid" cellspacing="6"><tr>
        @foreach([['Sessions', data_get($ga4,'summary.total_sessions')], ['Users', data_get($ga4,'summary.total_users')], ['Engagement %', data_get($ga4,'summary.avg_engagement_rate')], ['Period', $ga4['period'] ?? 'N/A']] as $mc)
            <td><div class="metric-cell"><div class="l">{{ $mc[0] }}</div><div class="v">{{ is_numeric($mc[1] ?? null) ? $fmtNumber($mc[1]) : ($mc[1] ?? '—') }}</div></div></td>
        @endforeach
    </tr></table>
@else
    <p style="font-size:8px;color:#64748b;">GA4 summary not captured.</p>
@endif

<h2 class="sec-h2">Google Search Console</h2>
@if(is_array($gsc) && ($gsc['connected'] ?? true) === false)
    <p style="font-size:8px;">{{ $gsc['message'] ?? 'Not connected.' }}</p>
@elseif(!empty($gsc['summary']))
    <table class="metric-grid" cellspacing="6"><tr>
        @foreach([
            ['Clicks', data_get($gsc,'summary.total_clicks')], ['Impressions', data_get($gsc,'summary.total_impressions')],
            ['CTR %', data_get($gsc,'summary.avg_ctr')], ['Avg pos', data_get($gsc,'summary.avg_position')],
        ] as $mc)
            <td><div class="metric-cell"><div class="l">{{ $mc[0] }}</div><div class="v">{{ $mc[1] !== null && $mc[1] !== '' ? (is_numeric($mc[1]) ? $fmtNumber($mc[1]) : $mc[1]) : '—' }}</div></div></td>
        @endforeach
    </tr></table>
@else
    <p style="font-size:8px;color:#64748b;">GSC summary not captured.</p>
@endif

<h2 class="sec-h2">Top GA4 pages</h2>
<table class="data-table" cellspacing="0"><thead><tr><th>Page</th><th>Sessions</th><th>Users</th></tr></thead><tbody>
@forelse($gaTopPages as $row) <tr><td class="truncate">{{ $strTrunc($row['page_path'] ?? '', 200) }}</td><td>{{ $fmtNumber($row['sessions'] ?? $row['views'] ?? null) }}</td><td>{{ $fmtNumber($row['total_users'] ?? $row['active_users'] ?? null) }}</td></tr> @empty <tr><td colspan="3">—</td></tr> @endforelse
</tbody></table>
<h2 class="sec-h2">Top GSC queries</h2>
<table class="data-table" cellspacing="0"><thead><tr><th>Query</th><th>Clicks</th><th>Impr.</th><th>Pos</th></tr></thead><tbody>
@forelse($gscQueries as $row) <tr><td class="truncate">{{ $strTrunc($row['query'] ?? '', 160) }}</td><td>{{ $fmtNumber($row['clicks'] ?? null) }}</td><td>{{ $fmtNumber($row['impressions'] ?? null) }}</td><td>{{ $fmtNumber($row['position'] ?? null) }}</td></tr> @empty <tr><td colspan="4">—</td></tr> @endforelse
</tbody></table>

<h2 class="sec-h2">Social signals</h2>
@php $socialMetrics = [
    ['Open Graph', $fmtBool($social['open_graph_tags_present'] ?? $pageData['og_present'] ?? null)], ['X cards', $fmtBool($social['x_cards_present'] ?? $pageData['twitter_cards_present'] ?? null)],
    ['Facebook', $fmtBool($social['facebook_page_linked'] ?? null)], ['Instagram', $fmtBool($social['instagram_linked'] ?? null)],
    ['LinkedIn', $fmtBool($social['linkedin_linked'] ?? null)], ['YouTube', $fmtBool($social['youtube_channel_linked'] ?? null)],
]; @endphp
@foreach(array_chunk($socialMetrics, 4) as $chunk)
<table class="metric-grid" cellspacing="6" style="margin-top:4px;"><tr>
    @foreach($chunk as $mc)
        <td><div class="metric-cell"><div class="l">{{ $mc[0] }}</div><div class="v">{{ $mc[1] }}</div></div></td>
    @endforeach
</tr></table>
@endforeach
<h2 class="sec-h2">Local & email</h2>
@php $localMetrics = [
    ['Address', $fmtBool($localSeo['address_found'] ?? null)], ['Phone', $fmtBool($localSeo['phone_found'] ?? null)], ['Local schema', $fmtBool($localSeo['local_business_schema_present'] ?? null)],
    ['SPF', $fmtBool($techEmail['spf_present'] ?? null)], ['DMARC', $fmtBool($techEmail['dmarc_present'] ?? null)], ['Server', $techEmail['web_server'] ?? 'N/A'],
]; @endphp
@foreach(array_chunk($localMetrics, 4) as $chunk)
<table class="metric-grid" cellspacing="6" style="margin-top:4px;"><tr>
    @foreach($chunk as $mc)
        <td><div class="metric-cell"><div class="l">{{ $mc[0] }}</div><div class="v">{{ $mc[1] }}</div></div></td>
    @endforeach
</tr></table>
@endforeach
@if(count($buckets['integrations']))
    <h2 class="sec-h2">Advanced modules — integrations</h2>
    @include('audit.pdf-modules', ['modules' => $buckets['integrations'], 'fmtVal' => $fmtVal, 'strTrunc' => $strTrunc])
@endif

</body>
</html>
