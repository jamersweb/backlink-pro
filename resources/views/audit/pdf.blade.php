<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEO Audit Report - {{ $audit->url }}</title>
    <style>
        @page { margin: 10mm 10mm 12mm 10mm; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; line-height: 1.42; color: #142235; background: #f3f5f9; }
        h1, h2, h3, p { margin: 0; }
        table { width: 100%; border-collapse: collapse; }
        .page-break { page-break-before: always; }
        .card { background: #ffffff; border: 1px solid #dde5ef; border-radius: 14px; padding: 12px; margin-bottom: 10px; page-break-inside: auto; }
        .hero { }
        .brand { font-size: 18px; font-weight: 800; color: #1d4ed8; }
        .domain { margin-top: 3px; font-size: 10px; color: #64748b; word-break: break-word; }
        .hero-title { font-size: 24px; font-weight: 800; color: #0f172a; margin-top: 8px; }
        .hero-subtitle { margin-top: 4px; font-size: 10px; color: #64748b; }
        .date-badge { display: inline-block; padding: 5px 10px; border: 1px solid #dbe7f3; border-radius: 999px; background: #f8fbff; font-size: 8px; font-weight: 700; color: #334155; }
        .summary-box { margin-top: 8px; padding: 9px 10px; background: #f8fbff; border: 1px solid #dbe7f3; border-radius: 10px; color: #42556d; font-size: 9px; line-height: 1.55; }
        .section-kicker { display: inline-block; margin-bottom: 5px; padding: 2px 8px; border-radius: 999px; background: #eef4ff; color: #2f6bff; font-size: 7px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; }
        .section-title { font-size: 15px; font-weight: 800; color: #0f172a; margin-bottom: 2px; }
        .section-subtitle { font-size: 9px; color: #64748b; margin-bottom: 8px; }
        .metric { background: #f8fbff; border: 1px solid #dbe7f3; border-radius: 10px; padding: 9px; }
        .metric-label { font-size: 8px; letter-spacing: 0.08em; text-transform: uppercase; color: #64748b; }
        .metric-value { font-size: 18px; font-weight: 800; color: #102033; margin-top: 6px; }
        .metric-note { margin-top: 2px; font-size: 8px; color: #64748b; }
        .split td { vertical-align: top; }
        .split .left { width: 32%; padding-right: 7px; }
        .split .right { width: 68%; padding-left: 7px; }
        .grid-4 td { width: 25%; padding-right: 6px; vertical-align: top; }
        .grid-4 td:last-child { padding-right: 0; }
        .grid-2 td { width: 50%; vertical-align: top; }
        .grid-2 td:first-child { padding-right: 6px; }
        .grid-2 td:last-child { padding-left: 6px; }
        .grid-3 td { width: 33.333%; padding-right: 6px; vertical-align: top; }
        .grid-3 td:last-child { padding-right: 0; }
        .donut-wrap { text-align: center; }
        .donut-title { font-size: 10px; font-weight: 700; color: #102033; margin-bottom: 4px; }
        .donut-value { font-size: 30px; font-weight: 800; margin-top: -102px; }
        .donut-grade { font-size: 15px; font-weight: 800; margin-top: 2px; }
        .pill { display: inline-block; padding: 3px 8px; border-radius: 999px; font-size: 8px; font-weight: 700; }
        .pill.good { background: #dcfce7; color: #166534; }
        .pill.warn { background: #fff2d8; color: #b45309; }
        .pill.bad { background: #fee2e2; color: #b91c1c; }
        .bar-row { margin-bottom: 8px; }
        .bar-head { font-size: 9px; color: #334155; margin-bottom: 4px; }
        .bar-head .right { float: right; font-weight: 700; }
        .bar-track { clear: both; height: 8px; background: #e2e8f0; border-radius: 999px; overflow: hidden; }
        .bar-fill { height: 8px; border-radius: 999px; }
        .blue { background: #2f6bff; }
        .green { background: #18a957; }
        .orange { background: #f59e0b; }
        .red { background: #ef4444; }
        .stack { height: 12px; background: #e2e8f0; border-radius: 999px; overflow: hidden; }
        .stack span { display: block; float: left; height: 12px; }
        .legend { margin-top: 6px; font-size: 8px; color: #64748b; }
        .legend span { display: inline-block; margin-right: 10px; }
        .dot { display: inline-block; width: 7px; height: 7px; border-radius: 50%; margin-right: 4px; }
        .table th, .table td { border-bottom: 1px solid #e4edf6; padding: 7px 8px; text-align: left; vertical-align: top; }
        .table th { background: #f8fbff; font-size: 8px; text-transform: uppercase; letter-spacing: 0.05em; color: #475569; }
        .truncate { word-break: break-word; }
        .badge { display: inline-block; padding: 3px 7px; border-radius: 999px; font-size: 8px; font-weight: 700; }
        .badge-high { background: #fee2e2; color: #b91c1c; }
        .badge-medium { background: #fef3c7; color: #92400e; }
        .badge-low { background: #dbeafe; color: #1d4ed8; }
        .chip { display: inline-block; margin: 0 4px 4px 0; padding: 4px 8px; border-radius: 999px; background: #e8f1ff; color: #1d4ed8; font-size: 8px; font-weight: 700; }
        .status-row { margin-top: 6px; padding: 6px 8px; border-radius: 9px; font-size: 8px; }
        .status-row.good { background: #ebf9f1; color: #17663b; }
        .status-row.warn { background: #fff7e7; color: #9a6708; }
        .status-row.bad { background: #fff0f1; color: #b42318; }
        .section-note { margin-top: 8px; padding: 8px 10px; border-radius: 10px; background: #f8fbff; border: 1px solid #dbe7f3; color: #51657c; font-size: 9px; }
        .footer { text-align: center; margin-top: 4px; font-size: 8px; color: #94a3b8; }
        .avoid-split { page-break-inside: avoid; }
    </style>
</head>
<body>
@php
    $kpis = $audit->audit_kpis ?? [];
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
    $psi = $kpis['google']['pagespeed'] ?? null;
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
            'source' => !empty($metrics) ? 'psi' : (!empty($fallback) ? 'page' : null),
        ];
    };
    $mobile = $normalizePsi(data_get($psi, 'mobile'), $page?->lighthouse_mobile);
    $desktop = $normalizePsi(data_get($psi, 'desktop'), $page?->lighthouse_desktop);
    $host = parse_url($audit->url, PHP_URL_HOST) ?: $audit->url;
    $summaryText = trim((string) ($audit->summary['overview'] ?? $audit->summary['summary'] ?? ''));
    if ($summaryText === '') {
        $summaryText = 'This report combines crawl findings, performance data, metadata checks, issue severity, and available Google integrations to surface the clearest SEO priorities.';
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
    $scoreTone = function ($score) {
        if (!is_numeric($score)) return ['color' => '#2f6bff', 'pill' => 'warn', 'bar' => 'blue'];
        if ($score >= 90) return ['color' => '#18a957', 'pill' => 'good', 'bar' => 'green'];
        if ($score >= 70) return ['color' => '#f59e0b', 'pill' => 'warn', 'bar' => 'orange'];
        return ['color' => '#ef4444', 'pill' => 'bad', 'bar' => 'red'];
    };
    $scoreArc = function ($score, $circ = 327) {
        $safe = max(0, min(100, (float) $score));
        return round(($safe / 100) * $circ);
    };
    $scoreWidth = function ($score) {
        if (!is_numeric($score)) return 4;
        return max(4, min(100, (float) $score));
    };
    $issuesHigh = $issues->where('impact', 'high')->count();
    $issuesMedium = $issues->where('impact', 'medium')->count();
    $issuesLow = $issues->where('impact', 'low')->count();
    $issuesTotal = max(1, $issues->count());
    $overallScore = (int) ($audit->overall_score ?? 0);
    $overallGrade = $audit->overall_grade ?? 'N/A';
    $overallTone = $scoreTone($overallScore);
    $categoryCards = [
        ['label' => 'On-Page', 'score' => data_get($audit->category_scores, 'onpage'), 'grade' => data_get($audit->category_grades, 'onpage')],
        ['label' => 'Technical', 'score' => data_get($audit->category_scores, 'technical'), 'grade' => data_get($audit->category_grades, 'technical')],
        ['label' => 'Performance', 'score' => data_get($audit->category_scores, 'performance'), 'grade' => data_get($audit->category_grades, 'performance')],
        ['label' => 'Links', 'score' => data_get($audit->category_scores, 'links'), 'grade' => data_get($audit->category_grades, 'links')],
    ];
    $homepageCards = [
        ['label' => 'Title Length', 'value' => $onPage['title_length'] ?? $page?->title_len],
        ['label' => 'Meta Length', 'value' => $onPage['meta_description_length'] ?? $page?->meta_len],
        ['label' => 'Word Count', 'value' => $page?->word_count ?? $onPage['word_count']],
        ['label' => 'Status Code', 'value' => $page?->status_code ?? null],
    ];
    $duplicateTitles = collect($onPage['duplicate_titles_table'] ?? [])->take(8);
    $missingMeta = collect($onPage['missing_meta_table'] ?? [])->take(8);
    $missingH1 = collect($onPage['missing_h1_table'] ?? [])->take(8);
    $brokenLinks = collect($technical['broken_links_examples'] ?? [])->take(8);
    $redirectChains = collect($technical['redirect_chains_examples'] ?? [])->take(8);
    $heavyAssets = collect($performance['heavy_assets'] ?? [])->take(8);
    $securityHeaders = collect($technical['security_headers_list'] ?? [])->take(8);
    $topKeywords = collect($onPage['top_keywords'] ?? [])->take(10);
    $schemaTypes = collect($onPage['schema_types'] ?? ($page?->schema_types ?? []))->take(8);
    $gaTopPages = collect($ga4['top_pages'] ?? [])->take(8);
    $gscQueries = collect($gsc['top_queries'] ?? [])->take(8);
    $topIssues = $issues->take(12);
@endphp

<div class="card hero">
    <table>
        <tr>
            <td style="width:68%;vertical-align:top;">
                <div class="brand">BacklinkPro Insights</div>
                <div class="domain">{{ $audit->url }}</div>
                <div class="hero-title">SEO Audit Report</div>
                <div class="hero-subtitle">{{ $host }} - overview, on-page SEO, technical signals, performance, analytics, and issue priorities</div>
            </td>
            <td style="width:32%;vertical-align:top;">
                <div style="text-align:right;">
                    <span class="date-badge">{{ $audit->created_at?->format('M j, Y') ?? now()->format('M j, Y') }}</span>
                </div>
            </td>
        </tr>
    </table>

    <table class="split" style="margin-top:10px;">
        <tr>
            <td class="right" style="padding-left:0;padding-right:7px;width:68%;">
                <div class="summary-box">{{ $summaryText }}</div>
                <div class="section-note">Reference style matched with a soft neutral canvas, white report cards, blue accent headings, and orange or green score visuals while keeping the audit content real.</div>
            </td>
            <td class="left" style="padding-right:0;padding-left:7px;width:32%;">
                <div class="metric donut-wrap" style="margin-top:0;">
                    <div class="donut-title">Overall SEO Score</div>
                    <svg width="132" height="132" viewBox="0 0 132 132">
                        <circle cx="66" cy="66" r="52" fill="none" stroke="#e2e8f0" stroke-width="12"></circle>
                        <circle cx="66" cy="66" r="52" fill="none" stroke="{{ $overallTone['color'] }}" stroke-width="12" stroke-linecap="round" transform="rotate(-90 66 66)" stroke-dasharray="{{ $scoreArc($overallScore) }} 327"></circle>
                    </svg>
                    <div class="donut-value" style="color:{{ $overallTone['color'] }};">{{ $fmtNumber($overallScore) }}</div>
                    <div style="font-size:8px;color:#64748b;">Overall</div>
                    <div class="donut-grade" style="color:{{ $overallTone['color'] }};">{{ $overallGrade }}</div>
                    <div style="margin-top:5px;">
                        <span class="pill {{ $overallTone['pill'] }}">{{ $overallScore >= 90 ? 'Strong' : ($overallScore >= 70 ? 'Needs improvement' : 'Needs attention') }}</span>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <table class="grid-4" style="margin-top:10px;">
        <tr>
            <td><div class="metric"><div class="metric-label">Pages Crawled</div><div class="metric-value">{{ $fmtNumber($overview['pages_crawled_count'] ?? $audit->pages_scanned) }}</div><div class="metric-note">{{ $fmtNumber($audit->pages_discovered) }} discovered</div></div></td>
            <td><div class="metric"><div class="metric-label">Issues Found</div><div class="metric-value">{{ $fmtNumber($issues->count()) }}</div><div class="metric-note">{{ $fmtNumber($issuesHigh) }} critical impact</div></div></td>
            <td><div class="metric"><div class="metric-label">Recommendations</div><div class="metric-value">{{ $fmtNumber($overview['recommendations_count'] ?? $audit->recommendations_count ?? $issues->count()) }}</div><div class="metric-note">actionable fixes</div></div></td>
            <td><div class="metric"><div class="metric-label">Generated</div><div class="metric-value" style="font-size:15px;">{{ $audit->finished_at?->format('M j') ?? now()->format('M j') }}</div><div class="metric-note">{{ $audit->finished_at?->format('g:i A') ?? now()->format('g:i A') }}</div></div></td>
        </tr>
    </table>

    <div class="card" style="margin:8px 0 0;padding:10px 12px;">
        <div class="section-kicker">Overview</div>
        <div class="section-title">Category Scores</div>
        <div class="section-subtitle">High-level audit summary across the main SEO areas.</div>
        @foreach($categoryCards as $card)
            @php($tone = $scoreTone($card['score']))
            <div class="bar-row">
                <div class="bar-head">{{ $card['label'] }}<span class="right">{{ $fmtNumber($card['score']) }} / {{ $card['grade'] ?? 'N/A' }}</span></div>
                <div class="bar-track" style="height:10px;"><div class="bar-fill {{ $tone['bar'] }}" style="width:{{ $scoreWidth($card['score']) }}%;height:10px;"></div></div>
            </div>
        @endforeach
    </div>
</div>
<div class="card">
    <div class="section-kicker">Overview</div>
    <div class="section-title">Issue Distribution</div>
    <div class="section-subtitle">Severity split based on the stored issue register.</div>
    <div class="stack">
        <span style="width:{{ round(($issuesHigh / $issuesTotal) * 100, 2) }}%;background:#ef4444;"></span>
        <span style="width:{{ round(($issuesMedium / $issuesTotal) * 100, 2) }}%;background:#f59e0b;"></span>
        <span style="width:{{ round(($issuesLow / $issuesTotal) * 100, 2) }}%;background:#2f6bff;"></span>
    </div>
    <div class="legend">
        <span><i class="dot" style="background:#ef4444;"></i>High: {{ $fmtNumber($issuesHigh) }}</span>
        <span><i class="dot" style="background:#f59e0b;"></i>Medium: {{ $fmtNumber($issuesMedium) }}</span>
        <span><i class="dot" style="background:#2f6bff;"></i>Low: {{ $fmtNumber($issuesLow) }}</span>
    </div>
    <table class="grid-4" style="margin-top:8px;">
        <tr>
            <td><div class="metric"><div class="metric-label">Warnings</div><div class="metric-value">{{ $fmtNumber($overview['warnings_count'] ?? $issuesMedium) }}</div></div></td>
            <td><div class="metric"><div class="metric-label">Passed Checks</div><div class="metric-value">{{ $fmtNumber($overview['passed_checks'] ?? null) }}</div></div></td>
            <td><div class="metric"><div class="metric-label">Status</div><div class="metric-value" style="font-size:15px;">{{ ucfirst($audit->status ?? 'n/a') }}</div></div></td>
            <td><div class="metric"><div class="metric-label">Mode</div><div class="metric-value" style="font-size:15px;">{{ strtoupper($audit->mode ?? 'n/a') }}</div></div></td>
        </tr>
    </table>
    <div class="status-row {{ $issuesHigh > 0 ? 'bad' : 'good' }}">High-impact findings should be addressed first because they carry the greatest SEO risk.</div>
    <div class="status-row {{ $issuesMedium > 0 ? 'warn' : 'good' }}">Medium issues often affect consistency, coverage, or metadata quality.</div>
</div>

<div class="page-break"></div>
<div class="card avoid-split">
    <div class="section-kicker">Overview</div>
    <div class="section-title">Homepage Snapshot</div>
    <div class="section-subtitle">Primary metadata and content signals for the audited page.</div>
    <table class="grid-4">
        <tr>
            @foreach($homepageCards as $item)
                <td><div class="metric"><div class="metric-label">{{ $item['label'] }}</div><div class="metric-value" style="font-size:16px;">{{ $fmtNumber($item['value']) }}</div></div></td>
            @endforeach
        </tr>
    </table>
    <table class="table" style="margin-top:8px;">
        <tr><th style="width:22%;">Field</th><th>Value</th></tr>
        <tr><td>Title</td><td class="truncate">{{ $page?->title ?: 'Missing' }}</td></tr>
        <tr><td>Meta Description</td><td class="truncate">{{ $page?->meta_description ?: 'Missing' }}</td></tr>
        <tr><td>Canonical URL</td><td class="truncate">{{ $page?->canonical_url ?: ($onPage['canonical_url'] ?? 'Missing') }}</td></tr>
        <tr><td>Robots Meta</td><td class="truncate">{{ $page?->robots_meta ?: 'N/A' }}</td></tr>
    </table>
</div>

<div class="card">
    <div class="section-kicker">On-Page SEO</div>
    <div class="section-title">On-Page SEO</div>
    <div class="section-subtitle">Metadata, content structure, keyword signals, and page-level content quality.</div>
    <table class="grid-4">
        <tr>
            <td><div class="metric"><div class="metric-label">H1 Present</div><div class="metric-value" style="font-size:16px;">{{ $fmtBool($onPage['h1_present'] ?? ($page?->h1_count > 0 ? true : null)) }}</div></div></td>
            <td><div class="metric"><div class="metric-label">H1 Count</div><div class="metric-value" style="font-size:16px;">{{ $fmtNumber($page?->h1_count) }}</div></div></td>
            <td><div class="metric"><div class="metric-label">Images</div><div class="metric-value" style="font-size:16px;">{{ $fmtNumber($page?->images_total) }}</div></div></td>
            <td><div class="metric"><div class="metric-label">Missing Alt</div><div class="metric-value" style="font-size:16px;">{{ $fmtNumber($page?->images_missing_alt) }}</div></div></td>
        </tr>
    </table>
    <table class="grid-2" style="margin-top:8px;">
        <tr>
            <td>
                <table class="table">
                    <tr><th style="width:38%;">Signal</th><th>Value</th></tr>
                    <tr><td>Language</td><td>{{ $onPage['lang_declared'] ?? 'N/A' }}</td></tr>
                    <tr><td>Analytics Tool</td><td>{{ $onPage['analytics_tool_name'] ?? 'Not detected' }}</td></tr>
                    <tr><td>Internal Links</td><td>{{ $fmtNumber($page?->internal_links_count) }}</td></tr>
                    <tr><td>External Links</td><td>{{ $fmtNumber($page?->external_links_count) }}</td></tr>
                </table>
            </td>
            <td>
                <div class="metric">
                    <div class="metric-label">Top Keywords</div>
                    <div style="margin-top:8px;">
                        @forelse($topKeywords as $item)
                            <span class="chip">{{ $item['keyword'] ?? 'keyword' }}{{ isset($item['count']) ? ' (' . $item['count'] . ')' : '' }}</span>
                        @empty
                            <span style="font-size:9px;color:#64748b;">No keyword extraction data stored.</span>
                        @endforelse
                    </div>
                    <div class="metric-label" style="margin-top:10px;">Schema Types</div>
                    <div style="margin-top:8px;">
                        @forelse($schemaTypes as $type)
                            <span class="chip">{{ is_array($type) ? ($type['type'] ?? 'Schema') : $type }}</span>
                        @empty
                            <span style="font-size:9px;color:#64748b;">No schema types captured.</span>
                        @endforelse
                    </div>
                </div>
            </td>
        </tr>
    </table>
    <table class="grid-3" style="margin-top:8px;">
        <tr>
            <td><table class="table"><tr><th>Duplicate Titles</th></tr>@forelse($duplicateTitles as $row)<tr><td class="truncate">{{ $row['url'] ?? 'N/A' }}</td></tr>@empty<tr><td>No duplicate-title examples stored.</td></tr>@endforelse</table></td>
            <td><table class="table"><tr><th>Missing Meta</th></tr>@forelse($missingMeta as $row)<tr><td class="truncate">{{ $row['url'] ?? 'N/A' }}</td></tr>@empty<tr><td>No missing-meta examples stored.</td></tr>@endforelse</table></td>
            <td><table class="table"><tr><th>Missing H1</th></tr>@forelse($missingH1 as $row)<tr><td class="truncate">{{ $row['url'] ?? 'N/A' }}</td></tr>@empty<tr><td>No missing-H1 examples stored.</td></tr>@endforelse</table></td>
        </tr>
    </table>
</div>
<div class="card">
    <div class="section-kicker">Technical</div>
    <div class="section-title">Technical</div>
    <div class="section-subtitle">Crawlability, discovery, security coverage, redirects, and broken-link signals.</div>
    <table class="grid-4">
        <tr>
            <td><div class="metric"><div class="metric-label">HTTPS Enabled</div><div class="metric-value" style="font-size:16px;">{{ $fmtBool($technical['https_enabled'] ?? null) }}</div></div></td>
            <td><div class="metric"><div class="metric-label">HTTPS Redirect</div><div class="metric-value" style="font-size:16px;">{{ $fmtBool($technical['https_redirect_ok'] ?? null) }}</div></div></td>
            <td><div class="metric"><div class="metric-label">robots.txt</div><div class="metric-value" style="font-size:16px;">{{ $fmtBool($technical['robots_txt_present'] ?? null) }}</div></div></td>
            <td><div class="metric"><div class="metric-label">XML Sitemap</div><div class="metric-value" style="font-size:16px;">{{ $fmtBool($technical['xml_sitemap_present'] ?? null) }}</div></div></td>
        </tr>
    </table>
    <table class="grid-2" style="margin-top:8px;">
        <tr>
            <td><table class="table"><tr><th style="width:38%;">Discovery</th><th>Value</th></tr><tr><td>robots.txt URL</td><td class="truncate">{{ $technical['robots_txt_url'] ?? 'Not found' }}</td></tr><tr><td>Sitemap URL</td><td class="truncate">{{ $technical['sitemap_url'] ?? 'Not found' }}</td></tr><tr><td>Blocked by Robots</td><td>{{ $fmtBool($technical['blocked_by_robots'] ?? null) }}</td></tr></table></td>
            <td><table class="table"><tr><th>Header</th><th style="width:18%;">Present</th><th style="width:18%;">Total</th></tr>@forelse($securityHeaders as $row)<tr><td class="truncate">{{ $row['header'] ?? 'N/A' }}</td><td>{{ $fmtNumber($row['pages_with_header'] ?? null) }}</td><td>{{ $fmtNumber($row['total_pages'] ?? null) }}</td></tr>@empty<tr><td colspan="3">Security header coverage was not captured.</td></tr>@endforelse</table></td>
        </tr>
    </table>
    <table class="grid-2" style="margin-top:8px;">
        <tr>
            <td><table class="table"><tr><th>Broken Links</th><th style="width:14%;">Code</th></tr>@forelse($brokenLinks as $row)<tr><td class="truncate">{{ $row['to_url'] ?? 'N/A' }}</td><td>{{ $fmtNumber($row['status_code'] ?? null) }}</td></tr>@empty<tr><td colspan="2">No broken-link examples stored.</td></tr>@endforelse</table></td>
            <td><table class="table"><tr><th>Redirect Chains</th><th style="width:14%;">Hops</th></tr>@forelse($redirectChains as $row)<tr><td class="truncate">{{ $row['to_url'] ?? 'N/A' }}</td><td>{{ $fmtNumber($row['redirect_hops'] ?? null) }}</td></tr>@empty<tr><td colspan="2">No redirect-chain examples stored.</td></tr>@endforelse</table></td>
        </tr>
    </table>
</div>

<div class="card">
    <div class="section-kicker">Performance</div>
    <div class="section-title">Performance</div>
    <div class="section-subtitle">PageSpeed scores, core metrics, and heaviest assets affecting page load.</div>
    <table class="grid-2">
        <tr>
            <td>
                @php($mobileTone = $scoreTone($mobile['score'] ?? null))
                <div class="metric">
                    <div class="donut-wrap">
                        <div class="donut-title">Mobile PageSpeed</div>
                        <svg width="120" height="120" viewBox="0 0 120 120">
                            <circle cx="60" cy="60" r="46" fill="none" stroke="#e2e8f0" stroke-width="10"></circle>
                            <circle cx="60" cy="60" r="46" fill="none" stroke="{{ $mobileTone['color'] }}" stroke-width="10" stroke-linecap="round" transform="rotate(-90 60 60)" stroke-dasharray="{{ $scoreArc($mobile['score'] ?? 0, 289) }} 289"></circle>
                        </svg>
                        <div class="donut-value" style="margin-top:-92px;font-size:24px;color:{{ $mobileTone['color'] }};">{{ $fmtNumber($mobile['score'] ?? null) }}</div>
                        <div style="margin-top:20px;"><span class="pill {{ $mobileTone['pill'] }}">{{ $mobile['score'] !== null ? 'PSI score' : 'Not captured' }}</span></div>
                    </div>
                    <div style="margin-top:8px;">
                        <div class="bar-row"><div class="bar-head">LCP <span class="right">{{ $fmtSeconds($mobile['lcp'] ?? null) }}</span></div><div class="bar-track"><div class="bar-fill green" style="width:{{ $scoreWidth(100 - min(100, (($mobile['lcp'] ?? 0) / 50))) }}%;"></div></div></div>
                        <div class="bar-row"><div class="bar-head">FCP <span class="right">{{ $fmtSeconds($mobile['fcp'] ?? null) }}</span></div><div class="bar-track"><div class="bar-fill orange" style="width:{{ $scoreWidth(100 - min(100, (($mobile['fcp'] ?? 0) / 40))) }}%;"></div></div></div>
                        <div class="bar-row"><div class="bar-head">CLS <span class="right">{{ $fmtNumber($mobile['cls'] ?? null) }}</span></div><div class="bar-track"><div class="bar-fill blue" style="width:{{ $scoreWidth(100 - min(100, (($mobile['cls'] ?? 0) * 300))) }}%;"></div></div></div>
                    </div>
                </div>
            </td>
            <td>
                @php($desktopTone = $scoreTone($desktop['score'] ?? null))
                <div class="metric">
                    <div class="donut-wrap">
                        <div class="donut-title">Desktop PageSpeed</div>
                        <svg width="120" height="120" viewBox="0 0 120 120">
                            <circle cx="60" cy="60" r="46" fill="none" stroke="#e2e8f0" stroke-width="10"></circle>
                            <circle cx="60" cy="60" r="46" fill="none" stroke="{{ $desktopTone['color'] }}" stroke-width="10" stroke-linecap="round" transform="rotate(-90 60 60)" stroke-dasharray="{{ $scoreArc($desktop['score'] ?? 0, 289) }} 289"></circle>
                        </svg>
                        <div class="donut-value" style="margin-top:-92px;font-size:24px;color:{{ $desktopTone['color'] }};">{{ $fmtNumber($desktop['score'] ?? null) }}</div>
                        <div style="margin-top:20px;"><span class="pill {{ $desktopTone['pill'] }}">{{ $desktop['score'] !== null ? 'PSI score' : 'Not captured' }}</span></div>
                    </div>
                    <div style="margin-top:8px;">
                        <div class="bar-row"><div class="bar-head">LCP <span class="right">{{ $fmtSeconds($desktop['lcp'] ?? null) }}</span></div><div class="bar-track"><div class="bar-fill green" style="width:{{ $scoreWidth(100 - min(100, (($desktop['lcp'] ?? 0) / 50))) }}%;"></div></div></div>
                        <div class="bar-row"><div class="bar-head">FCP <span class="right">{{ $fmtSeconds($desktop['fcp'] ?? null) }}</span></div><div class="bar-track"><div class="bar-fill orange" style="width:{{ $scoreWidth(100 - min(100, (($desktop['fcp'] ?? 0) / 40))) }}%;"></div></div></div>
                        <div class="bar-row"><div class="bar-head">TTI <span class="right">{{ $fmtSeconds($desktop['tti'] ?? null) }}</span></div><div class="bar-track"><div class="bar-fill blue" style="width:{{ $scoreWidth(100 - min(100, (($desktop['tti'] ?? 0) / 80))) }}%;"></div></div></div>
                    </div>
                </div>
            </td>
        </tr>
    </table>
    <table class="table" style="margin-top:8px;">
        <tr><th>Asset</th><th style="width:18%;">Type</th><th style="width:14%;">KB</th></tr>
        @forelse($heavyAssets as $row)
            <tr><td class="truncate">{{ $row['asset_url'] ?? 'N/A' }}</td><td>{{ strtoupper($row['type'] ?? 'n/a') }}</td><td>{{ $fmtNumber($row['size_kb'] ?? null) }}</td></tr>
        @empty
            <tr><td colspan="3">Heavy asset data was not stored for this audit.</td></tr>
        @endforelse
    </table>
</div>
<div class="card">
    <div class="section-kicker">Analytics</div>
    <div class="section-title">Analytics And Search</div>
    <div class="section-subtitle">Google Analytics and Search Console summaries where connected data is available.</div>
    <table class="grid-2">
        <tr>
            <td>
                <div class="metric">
                    <div class="metric-label">GA4 Summary</div>
                    <table class="table" style="margin-top:8px;">
                        <tr><th>Metric</th><th>Value</th></tr>
                        <tr><td>Sessions</td><td>{{ $fmtNumber(data_get($ga4, 'summary.total_sessions')) }}</td></tr>
                        <tr><td>Users</td><td>{{ $fmtNumber(data_get($ga4, 'summary.total_users')) }}</td></tr>
                        <tr><td>Engagement Rate</td><td>{{ data_get($ga4, 'summary.avg_engagement_rate') !== null ? $fmtNumber(data_get($ga4, 'summary.avg_engagement_rate')) . '%' : 'N/A' }}</td></tr>
                        <tr><td>Period</td><td>{{ $ga4['period'] ?? 'N/A' }}</td></tr>
                    </table>
                </div>
            </td>
            <td>
                <div class="metric">
                    <div class="metric-label">GSC Summary</div>
                    <table class="table" style="margin-top:8px;">
                        <tr><th>Metric</th><th>Value</th></tr>
                        <tr><td>Clicks</td><td>{{ $fmtNumber(data_get($gsc, 'summary.total_clicks')) }}</td></tr>
                        <tr><td>Impressions</td><td>{{ $fmtNumber(data_get($gsc, 'summary.total_impressions')) }}</td></tr>
                        <tr><td>CTR</td><td>{{ data_get($gsc, 'summary.avg_ctr') !== null ? $fmtNumber(data_get($gsc, 'summary.avg_ctr')) . '%' : 'N/A' }}</td></tr>
                        <tr><td>Avg Position</td><td>{{ $fmtNumber(data_get($gsc, 'summary.avg_position')) }}</td></tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>
    <table class="grid-2" style="margin-top:8px;">
        <tr>
            <td><table class="table"><tr><th>Top GA4 Pages</th><th style="width:18%;">Sessions</th></tr>@forelse($gaTopPages as $row)<tr><td class="truncate">{{ $row['page_path'] ?? 'N/A' }}</td><td>{{ $fmtNumber($row['sessions'] ?? $row['views'] ?? null) }}</td></tr>@empty<tr><td colspan="2">GA4 top-page data was not stored.</td></tr>@endforelse</table></td>
            <td><table class="table"><tr><th>Top GSC Queries</th><th style="width:16%;">Clicks</th><th style="width:20%;">Impressions</th></tr>@forelse($gscQueries as $row)<tr><td class="truncate">{{ $row['query'] ?? 'N/A' }}</td><td>{{ $fmtNumber($row['clicks'] ?? null) }}</td><td>{{ $fmtNumber($row['impressions'] ?? null) }}</td></tr>@empty<tr><td colspan="3">Top query data was not captured for this audit.</td></tr>@endforelse</table></td>
        </tr>
    </table>
</div>

<div class="card">
    <div class="section-kicker">Issues</div>
    <div class="section-title">Priority Issues</div>
    <div class="section-subtitle">Highest-impact issues, recommendations, and practical next actions.</div>
    <table class="table">
        <tr><th style="width:11%;">Impact</th><th style="width:28%;">Issue</th><th style="width:10%;">Affected</th><th style="width:10%;">Penalty</th><th>Recommendation</th></tr>
        @forelse($topIssues as $issue)
            <tr>
                <td><span class="badge badge-{{ $issue->impact === 'high' ? 'high' : ($issue->impact === 'medium' ? 'medium' : 'low') }}">{{ strtoupper($issue->impact ?? 'n/a') }}</span></td>
                <td class="truncate">{{ $issue->title ?? $issue->code }}</td>
                <td>{{ $fmtNumber($issue->affected_count) }}</td>
                <td>{{ $fmtNumber($issue->score_penalty) }}</td>
                <td class="truncate">{{ $issue->recommendation ?: ($issue->description ?: 'No recommendation stored.') }}</td>
            </tr>
        @empty
            <tr><td colspan="5">No issues were stored for this audit.</td></tr>
        @endforelse
    </table>
    <table class="grid-2" style="margin-top:8px;">
        <tr>
            <td>
                <div class="metric">
                    <div class="metric-label">Social Signals</div>
                    <table class="table" style="margin-top:8px;">
                        <tr><th>Signal</th><th>Value</th></tr>
                        <tr><td>Open Graph</td><td>{{ $fmtBool($social['open_graph_tags_present'] ?? $page?->og_present) }}</td></tr>
                        <tr><td>X Cards</td><td>{{ $fmtBool($social['x_cards_present'] ?? $page?->twitter_cards_present) }}</td></tr>
                        <tr><td>Facebook</td><td>{{ $fmtBool($social['facebook_page_linked'] ?? null) }}</td></tr>
                        <tr><td>LinkedIn</td><td>{{ $fmtBool($social['linkedin_linked'] ?? null) }}</td></tr>
                    </table>
                </div>
            </td>
            <td>
                <div class="metric">
                    <div class="metric-label">Local And Email Signals</div>
                    <table class="table" style="margin-top:8px;">
                        <tr><th>Signal</th><th>Value</th></tr>
                        <tr><td>Address Found</td><td>{{ $fmtBool($localSeo['address_found'] ?? null) }}</td></tr>
                        <tr><td>Phone Found</td><td>{{ $fmtBool($localSeo['phone_found'] ?? null) }}</td></tr>
                        <tr><td>SPF</td><td>{{ $fmtBool($techEmail['spf_present'] ?? null) }}</td></tr>
                        <tr><td>DMARC</td><td>{{ $fmtBool($techEmail['dmarc_present'] ?? null) }}</td></tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>
    <div class="footer">BacklinkPro SEO Audit Report - Generated {{ now()->format('M j, Y g:i A') }}</div>
</div>
</body>
</html>
