<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEO Audit Report - {{ $audit->url }}</title>
    <style>
        @page { margin: 18mm 14mm 18mm 14mm; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; line-height: 1.45; color: #0f172a; background: #f3f6fb; }
        .report-shell { width: 100%; }
        .hero { background: #ffffff; border: 1px solid #dbe5f1; border-radius: 18px; padding: 20px 22px; margin-bottom: 16px; }
        .brand-row { width: 100%; margin-bottom: 16px; }
        .brand-cell, .meta-cell { vertical-align: top; }
        .brand { font-size: 24px; font-weight: 700; color: #2563eb; }
        .domain { font-size: 12px; color: #64748b; margin-top: 4px; word-break: break-all; }
        .date-pill { display: inline-block; padding: 7px 12px; border: 1px solid #dbe5f1; border-radius: 999px; background: #f8fbff; color: #334155; font-size: 10px; }
        .hero-grid { width: 100%; border-collapse: separate; border-spacing: 0; }
        .score-side { width: 180px; vertical-align: top; padding-right: 18px; }
        .content-side { vertical-align: top; }
        .score-ring { width: 138px; height: 138px; border-radius: 50%; margin: 0 auto; text-align: center; border: 10px solid {{ ($audit->overall_score ?? 0) >= 90 ? '#22c55e' : (($audit->overall_score ?? 0) >= 70 ? '#f59e0b' : '#ef4444') }}; color: {{ ($audit->overall_score ?? 0) >= 90 ? '#16a34a' : (($audit->overall_score ?? 0) >= 70 ? '#d97706' : '#dc2626') }}; }
        .score-inner { padding-top: 28px; }
        .score-value { font-size: 34px; font-weight: 800; line-height: 1; }
        .score-label { font-size: 11px; color: #64748b; margin-top: 6px; }
        .score-grade { font-size: 18px; font-weight: 800; margin-top: 6px; }
        .hero-title { font-size: 26px; font-weight: 700; margin: 0 0 6px 0; color: #0f172a; }
        .hero-subtitle { font-size: 13px; color: #64748b; margin-bottom: 18px; }
        .mini-stat { display: inline-block; width: 23%; margin-right: 1.5%; vertical-align: top; background: #f8fbff; border: 1px solid #dbe5f1; border-radius: 14px; padding: 12px; min-height: 78px; }
        .mini-stat.last { margin-right: 0; }
        .mini-stat-label { font-size: 9px; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }
        .mini-stat-value { font-size: 24px; font-weight: 800; margin-top: 8px; }
        .section-card { background: #ffffff; border: 1px solid #dbe5f1; border-radius: 18px; padding: 18px 20px; margin-bottom: 16px; page-break-inside: avoid; }
        .section-title { font-size: 20px; font-weight: 700; color: #0f172a; margin: 0 0 4px 0; }
        .section-subtitle { font-size: 11px; color: #64748b; margin-bottom: 14px; }
        .stat-card { display: inline-block; width: 23.5%; margin-right: 1.3%; vertical-align: top; background: #ffffff; border: 1px solid #e5edf6; border-radius: 14px; padding: 12px; min-height: 86px; }
        .stat-card.last { margin-right: 0; }
        .stat-name { font-size: 9px; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }
        .stat-value { font-size: 23px; line-height: 1.1; font-weight: 800; margin-top: 9px; color: #0f172a; }
        .muted-note { font-size: 10px; color: #94a3b8; margin-top: 4px; }
        .split-row { width: 100%; border-collapse: separate; border-spacing: 0; }
        .split-col { width: 49%; vertical-align: top; }
        .split-gap { width: 2%; }
        .score-box { background: #f8fbff; border: 1px solid #dbe5f1; border-radius: 16px; padding: 14px; margin-bottom: 12px; }
        .score-box-title { font-size: 12px; font-weight: 700; margin-bottom: 10px; }
        .score-pill { display: inline-block; min-width: 94px; text-align: center; border-radius: 999px; padding: 6px 10px; font-size: 10px; font-weight: 700; color: #92400e; background: #fef3c7; }
        .meter-row { margin-bottom: 8px; }
        .meter-label { font-size: 10px; color: #334155; margin-bottom: 4px; }
        .meter-track { width: 100%; height: 8px; background: #e2e8f0; border-radius: 999px; overflow: hidden; }
        .meter-fill { height: 8px; border-radius: 999px; background: #22c55e; }
        .meter-fill.warn { background: #f59e0b; }
        .meter-fill.bad { background: #ef4444; }
        .score-table, .report-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .score-table td, .report-table th, .report-table td { border-bottom: 1px solid #e2e8f0; padding: 9px 10px; vertical-align: top; }
        .report-table th { background: #f8fbff; color: #475569; text-align: left; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; }
        .label-cell { color: #64748b; width: 48%; }
        .value-cell { font-weight: 700; color: #0f172a; }
        .badge { display: inline-block; padding: 4px 9px; border-radius: 999px; font-size: 9px; font-weight: 700; }
        .badge-high { background: #fee2e2; color: #b91c1c; }
        .badge-medium { background: #fef3c7; color: #b45309; }
        .badge-low { background: #dbeafe; color: #1d4ed8; }
        .status-ok { color: #16a34a; font-weight: 700; }
        .status-missing { color: #dc2626; font-weight: 700; }
        .page-break { page-break-before: always; }
        .footer { margin-top: 12px; text-align: center; color: #94a3b8; font-size: 10px; }
        .small { font-size: 10px; color: #64748b; }
        .truncate-long { word-break: break-word; }
        .small { font-size: 10px; color: #64748b; }
        .truncate-long { word-break: break-word; }
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
    $google = $kpis['google'] ?? [];
    $pagespeed = $google['pagespeed'] ?? ($kpis['google']['pagespeed'] ?? null);
    $ga4 = $kpis['ga4'] ?? [];
    $gsc = $kpis['gsc'] ?? [];
    $pages = $audit->pages ?? collect();
    $summary = $audit->summary ?? [];
    $generatedAt = now()->format('F j, Y g:i A');
    $mobilePsi = $pagespeed['mobile']['kpis'] ?? [];
    $desktopPsi = $pagespeed['desktop']['kpis'] ?? [];
    $mobileScore = $mobilePsi['score'] ?? null;
    $desktopScore = $desktopPsi['score'] ?? null;
    $topIssues = $issues->take(12);
    $moreIssues = $issues->slice(12);
    $gaTopPages = collect($ga4['top_pages'] ?? [])->take(8);
    $gscQueries = collect($gsc['top_queries'] ?? [])->take(8);
    $gscPages = collect($gsc['top_pages'] ?? [])->take(8);
    $heavyAssets = collect($performance['heavy_assets'] ?? [])->take(10);
    $securityHeaders = collect($technical['security_headers_list'] ?? []);
    $duplicateTitles = collect($onPage['duplicate_titles_table'] ?? [])->take(8);
    $missingMeta = collect($onPage['missing_meta_table'] ?? [])->take(8);
    $missingH1 = collect($onPage['missing_h1_table'] ?? [])->take(8);
    $brokenLinks = collect($technical['broken_links_examples'] ?? [])->take(10);
    $redirectChains = collect($technical['redirect_chains_examples'] ?? [])->take(10);
    $categoryCards = [
        ['label' => 'On-Page', 'value' => data_get($audit->category_scores, 'onpage', 'N/A')],
        ['label' => 'Technical', 'value' => data_get($audit->category_scores, 'technical', 'N/A')],
        ['label' => 'Performance', 'value' => data_get($audit->category_scores, 'performance', 'N/A')],
        ['label' => 'Links', 'value' => data_get($audit->category_scores, 'links', 'N/A')],
    ];
@endphp

<div class="report-shell">
    <div class="hero">
        <table class="brand-row">
            <tr>
                <td class="brand-cell" style="width: 70%;">
                    <div class="brand">BacklinkPro Insights</div>
                    <div class="domain">{{ $audit->url }}</div>
                </td>
                <td class="meta-cell" style="width: 30%; text-align: right;">
                    <span class="date-pill">{{ $audit->created_at?->format('M j, Y') ?? now()->format('M j, Y') }}</span>
                </td>
            </tr>
        </table>

        <table class="hero-grid">
            <tr>
                <td class="score-side">
                    <div class="score-ring">
                        <div class="score-inner">
                            <div class="score-value">{{ $audit->overall_score ?? 'N/A' }}</div>
                            <div class="score-label">Overall</div>
                            <div class="score-grade">{{ $audit->overall_grade ?? 'N/A' }}</div>
                        </div>
                    </div>
                </td>
                <td class="content-side">
                    <div class="hero-title">SEO Health Report</div>
                    <div class="hero-subtitle">{{ parse_url($audit->url, PHP_URL_HOST) ?? $audit->url }} - Full audit completed</div>

                    @foreach($categoryCards as $index => $card)
                        <div class="mini-stat {{ $index === 3 ? 'last' : '' }}">
                            <div class="mini-stat-label">{{ $card['label'] }}</div>
                            <div class="mini-stat-value">{{ $card['value'] }}</div>
                        </div>
                    @endforeach
                </td>
            </tr>
        </table>
    </div>

    <div class="section-card">
        <div class="section-title">Executive Snapshot</div>
        <div class="section-subtitle">A compact summary of the report outcome, issue volume, and crawl coverage.</div>

        <div class="stat-card">
            <div class="stat-name">Pages Crawled</div>
            <div class="stat-value">{{ $overview['pages_crawled_count'] ?? $audit->pages_scanned ?? 0 }}</div>
            <div class="muted-note">{{ $audit->pages_discovered ?? 0 }} discovered</div>
        </div>
        <div class="stat-card">
            <div class="stat-name">Recommendations</div>
            <div class="stat-value">{{ $overview['recommendations_count'] ?? $issues->count() }}</div>
            <div class="muted-note">{{ $summary['high_impact_issues'] ?? 0 }} high impact</div>
        </div>
        <div class="stat-card">
            <div class="stat-name">Warnings</div>
            <div class="stat-value">{{ $overview['warnings_count'] ?? $summary['medium_impact_issues'] ?? 0 }}</div>
            <div class="muted-note">{{ $summary['low_impact_issues'] ?? 0 }} low impact</div>
        </div>
        <div class="stat-card last">
            <div class="stat-name">Generated</div>
            <div class="stat-value" style="font-size: 16px;">{{ $audit->finished_at?->format('M j') ?? now()->format('M j') }}</div>
            <div class="muted-note">{{ $audit->finished_at?->format('g:i A') ?? now()->format('g:i A') }}</div>
        </div>
    </div>

    <div class="section-card">
        <div class="section-title">Performance And Search Data</div>
        <div class="section-subtitle">PDF-safe summary cards based on captured PageSpeed, GA4, and Search Console data.</div>

        <table class="split-row">
            <tr>
                <td class="split-col">
                    <div class="score-box">
                        <div class="score-box-title">PageSpeed Insights</div>
                        <div class="stat-card" style="width:48%; margin-right:3%; min-height:120px; text-align:center;">
                            <div class="stat-name">Mobile</div>
                            <div class="stat-value" style="font-size: 30px; margin-top: 18px;">{{ $mobileScore ?? 'N/A' }}</div>
                            <div class="score-pill">{{ $mobileScore === null ? 'Not run' : ($mobileScore >= 90 ? 'Good' : ($mobileScore >= 70 ? 'Needs work' : 'Poor')) }}</div>
                        </div>
                        <div class="stat-card last" style="width:48%; min-height:120px; text-align:center;">
                            <div class="stat-name">Desktop</div>
                            <div class="stat-value" style="font-size: 30px; margin-top: 18px; color:#16a34a;">{{ $desktopScore ?? 'N/A' }}</div>
                            <div class="score-pill" style="background:#dcfce7; color:#166534;">{{ $desktopScore === null ? 'Not run' : ($desktopScore >= 90 ? 'Good' : ($desktopScore >= 70 ? 'Needs work' : 'Poor')) }}</div>
                        </div>
                        <div style="clear: both;"></div>
                        <div style="margin-top: 12px;">
                            <div class="meter-row"><div class="meter-label">LCP {{ $mobilePsi['lcp'] ?? 'N/A' }}</div><div class="meter-track"><div class="meter-fill {{ isset($mobilePsi['lcp']) && $mobilePsi['lcp'] > 4000 ? 'bad' : (isset($mobilePsi['lcp']) && $mobilePsi['lcp'] > 2500 ? 'warn' : '') }}" style="width: {{ isset($mobilePsi['lcp']) ? min(100, max(8, round(($mobilePsi['lcp'] / 5000) * 100))) : 8 }}%;"></div></div></div>
                            <div class="meter-row"><div class="meter-label">CLS {{ $mobilePsi['cls'] ?? 'N/A' }}</div><div class="meter-track"><div class="meter-fill {{ isset($mobilePsi['cls']) && $mobilePsi['cls'] > 0.25 ? 'bad' : (isset($mobilePsi['cls']) && $mobilePsi['cls'] > 0.1 ? 'warn' : '') }}" style="width: {{ isset($mobilePsi['cls']) ? min(100, max(8, round($mobilePsi['cls'] * 250))) : 8 }}%;"></div></div></div>
                            <div class="meter-row"><div class="meter-label">TBT {{ $mobilePsi['tbt'] ?? 'N/A' }}</div><div class="meter-track"><div class="meter-fill {{ isset($mobilePsi['tbt']) && $mobilePsi['tbt'] > 600 ? 'bad' : (isset($mobilePsi['tbt']) && $mobilePsi['tbt'] > 200 ? 'warn' : '') }}" style="width: {{ isset($mobilePsi['tbt']) ? min(100, max(8, round(($mobilePsi['tbt'] / 1000) * 100))) : 8 }}%;"></div></div></div>
                        </div>
                    </div>
                </td>
                <td class="split-gap"></td>
                <td class="split-col">
                    <div class="score-box">
                        <div class="score-box-title">Google Analytics - 30 Days</div>
                        <div class="stat-card" style="width:23%; min-height:88px;"><div class="stat-name">Users</div><div class="stat-value" style="font-size: 20px;">{{ $ga4['summary']['total_users'] ?? 'N/A' }}</div></div>
                        <div class="stat-card" style="width:23%; min-height:88px;"><div class="stat-name">Sessions</div><div class="stat-value" style="font-size: 20px;">{{ $ga4['summary']['total_sessions'] ?? 'N/A' }}</div></div>
                        <div class="stat-card" style="width:23%; min-height:88px;"><div class="stat-name">Engagement</div><div class="stat-value" style="font-size: 20px;">{{ isset($ga4['summary']['avg_engagement_rate']) ? $ga4['summary']['avg_engagement_rate'].'%' : 'N/A' }}</div></div>
                        <div class="stat-card last" style="width:23%; min-height:88px;"><div class="stat-name">Period</div><div class="stat-value" style="font-size: 13px;">{{ $ga4['period'] ?? 'Not connected' }}</div></div>
                        <div style="clear: both;"></div>

                        <div class="score-box-title" style="margin-top: 14px;">Google Search Console</div>
                        <div class="stat-card" style="width:23%; min-height:88px;"><div class="stat-name">Clicks</div><div class="stat-value" style="font-size: 20px;">{{ $gsc['summary']['total_clicks'] ?? 'N/A' }}</div></div>
                        <div class="stat-card" style="width:23%; min-height:88px;"><div class="stat-name">Impressions</div><div class="stat-value" style="font-size: 20px;">{{ $gsc['summary']['total_impressions'] ?? 'N/A' }}</div></div>
                        <div class="stat-card" style="width:23%; min-height:88px;"><div class="stat-name">CTR</div><div class="stat-value" style="font-size: 20px;">{{ isset($gsc['summary']['avg_ctr']) ? $gsc['summary']['avg_ctr'].'%' : 'N/A' }}</div></div>
                        <div class="stat-card last" style="width:23%; min-height:88px;"><div class="stat-name">Avg Position</div><div class="stat-value" style="font-size: 20px;">{{ $gsc['summary']['avg_position'] ?? 'N/A' }}</div></div>
                        <div style="clear: both;"></div>
                    </div>
                </td>
            </tr>
        </table>
    </div>
    <div class="page-break"></div>

    <div class="section-card">
        <div class="section-title">On-Page Summary</div>
        <div class="section-subtitle">Metadata quality, content structure, and homepage fundamentals.</div>
        <table class="split-row">
            <tr>
                <td class="split-col">
                    <table class="score-table">
                        <tr><td class="label-cell">Title</td><td class="value-cell truncate-long">{{ $page->title ?? 'N/A' }}</td></tr>
                        <tr><td class="label-cell">Title Length</td><td class="value-cell">{{ $page->title_len ?? 'N/A' }}</td></tr>
                        <tr><td class="label-cell">Meta Description</td><td class="value-cell truncate-long">{{ $page->meta_description ?? 'N/A' }}</td></tr>
                        <tr><td class="label-cell">Meta Length</td><td class="value-cell">{{ $page->meta_len ?? 'N/A' }}</td></tr>
                        <tr><td class="label-cell">Canonical URL</td><td class="value-cell truncate-long">{{ $page->canonical_url ?? 'Missing' }}</td></tr>
                        <tr><td class="label-cell">Word Count</td><td class="value-cell">{{ $page->word_count ?? 'N/A' }}</td></tr>
                    </table>
                </td>
                <td class="split-gap"></td>
                <td class="split-col">
                    <table class="score-table">
                        <tr><td class="label-cell">H1 Count</td><td class="value-cell">{{ $page->h1_count ?? 'N/A' }}</td></tr>
                        <tr><td class="label-cell">H2 Count</td><td class="value-cell">{{ $page->h2_count ?? 'N/A' }}</td></tr>
                        <tr><td class="label-cell">H3 Count</td><td class="value-cell">{{ $page->h3_count ?? 'N/A' }}</td></tr>
                        <tr><td class="label-cell">Images Total</td><td class="value-cell">{{ $page->images_total ?? 'N/A' }}</td></tr>
                        <tr><td class="label-cell">Missing Alt</td><td class="value-cell">{{ $page->images_missing_alt ?? 'N/A' }}</td></tr>
                        <tr><td class="label-cell">Analytics Detected</td><td class="value-cell">{{ $onPage['analytics_tool_name'] ?? 'Not detected' }}</td></tr>
                    </table>
                </td>
            </tr>
        </table>

        <div class="stat-card"><div class="stat-name">Title Missing</div><div class="stat-value">{{ $onPage['title_missing_count'] ?? 'N/A' }}</div></div>
        <div class="stat-card"><div class="stat-name">Meta Missing</div><div class="stat-value">{{ $onPage['meta_missing_count'] ?? 'N/A' }}</div></div>
        <div class="stat-card"><div class="stat-name">H1 Missing</div><div class="stat-value">{{ $onPage['h1_missing_count'] ?? 'N/A' }}</div></div>
        <div class="stat-card last"><div class="stat-name">Thin Pages</div><div class="stat-value">{{ $onPage['thin_pages_count'] ?? 'N/A' }}</div></div>
    </div>

    <div class="section-card">
        <div class="section-title">Technical Summary</div>
        <div class="section-subtitle">Crawl, indexability, server response, and security signal overview.</div>
        <div class="stat-card"><div class="stat-name">HTTPS Enabled</div><div class="stat-value" style="font-size: 18px;">{!! ($technical['https_enabled'] ?? false) ? '<span class="status-ok">Yes</span>' : '<span class="status-missing">No</span>' !!}</div></div>
        <div class="stat-card"><div class="stat-name">robots.txt</div><div class="stat-value" style="font-size: 18px;">{!! ($technical['robots_txt_present'] ?? false) ? '<span class="status-ok">Found</span>' : '<span class="status-missing">Missing</span>' !!}</div></div>
        <div class="stat-card"><div class="stat-name">Broken Links</div><div class="stat-value">{{ $technical['broken_links_count'] ?? 0 }}</div></div>
        <div class="stat-card last"><div class="stat-name">Indexability Issues</div><div class="stat-value">{{ $technical['indexability_issues_count'] ?? 0 }}</div></div>

        <table class="report-table">
            <thead>
                <tr><th>Signal</th><th>Value</th><th>Signal</th><th>Value</th></tr>
            </thead>
            <tbody>
                <tr><td>robots.txt URL</td><td class="truncate-long">{{ $technical['robots_txt_url'] ?? 'N/A' }}</td><td>Sitemap URL</td><td class="truncate-long">{{ $technical['sitemap_url'] ?? 'N/A' }}</td></tr>
                <tr><td>Canonical Present Count</td><td>{{ $technical['canonical_present_count'] ?? 'N/A' }}</td><td>Structured Data</td><td>{{ ($technical['structured_data_detected'] ?? false) ? 'Detected' : 'Not detected' }}</td></tr>
                <tr><td>Server</td><td>{{ $techEmail['web_server'] ?? 'N/A' }}</td><td>Server IP</td><td>{{ $techEmail['server_ip'] ?? 'N/A' }}</td></tr>
            </tbody>
        </table>
    <div class="page-break"></div>

    <div class="section-card">
        <div class="section-title">Issue Register</div>
        <div class="section-subtitle">Top issues sorted by impact and penalty.</div>
        <table class="report-table">
            <thead>
                <tr><th style="width: 32%;">Issue</th><th style="width: 12%;">Impact</th><th style="width: 10%;">Affected</th><th style="width: 10%;">Penalty</th><th>Recommendation</th></tr>
            </thead>
            <tbody>
                @forelse($topIssues as $issue)
                    <tr>
                        <td><strong>{{ $issue->title }}</strong>@if($issue->description)<div class="small" style="margin-top: 4px;">{{ $issue->description }}</div>@endif</td>
                        <td><span class="badge badge-{{ $issue->impact }}">{{ ucfirst($issue->impact) }}</span></td>
                        <td>{{ $issue->affected_count ?? 'N/A' }}</td>
                        <td>{{ $issue->score_penalty ? '-'.$issue->score_penalty : 'N/A' }}</td>
                        <td class="truncate-long">{{ $issue->recommendation ?? 'Review manually' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">No issues stored for this audit.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($gaTopPages->count() || $gscQueries->count() || $gscPages->count())
    <div class="section-card">
        <div class="section-title">Traffic And Search Highlights</div>
        <div class="section-subtitle">Top pages and queries available from connected Google data sources.</div>

        @if($gaTopPages->count())
            <div class="score-box-title">GA4 Top Pages</div>
            <table class="report-table">
                <thead><tr><th>Page</th><th>Views / Sessions</th><th>Users</th><th>Conversions</th></tr></thead>
                <tbody>
                    @foreach($gaTopPages as $row)
                        <tr><td class="truncate-long">{{ $row['page_path'] ?? 'N/A' }}</td><td>{{ $row['views'] ?? $row['sessions'] ?? 'N/A' }}</td><td>{{ $row['active_users'] ?? $row['total_users'] ?? 'N/A' }}</td><td>{{ $row['conversions'] ?? 'N/A' }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if($gscQueries->count())
            <div class="score-box-title" style="margin-top: 12px;">Search Console Top Queries</div>
            <table class="report-table">
                <thead><tr><th>Query</th><th>Clicks</th><th>Impressions</th><th>CTR</th><th>Position</th></tr></thead>
                <tbody>
                    @foreach($gscQueries as $row)
                        <tr><td>{{ $row['query'] ?? 'N/A' }}</td><td>{{ $row['clicks'] ?? 'N/A' }}</td><td>{{ $row['impressions'] ?? 'N/A' }}</td><td>{{ $row['ctr'] ?? 'N/A' }}</td><td>{{ $row['position'] ?? 'N/A' }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if($gscPages->count())
            <div class="score-box-title" style="margin-top: 12px;">Search Console Top Pages</div>
            <table class="report-table">
                <thead><tr><th>Page</th><th>Clicks</th><th>Impressions</th><th>CTR</th><th>Position</th></tr></thead>
                <tbody>
                    @foreach($gscPages as $row)
                        <tr><td class="truncate-long">{{ $row['page_url'] ?? 'N/A' }}</td><td>{{ $row['clicks'] ?? 'N/A' }}</td><td>{{ $row['impressions'] ?? 'N/A' }}</td><td>{{ $row['ctr'] ?? 'N/A' }}</td><td>{{ $row['position'] ?? 'N/A' }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
    @endif
    @if($duplicateTitles->count() || $missingMeta->count() || $missingH1->count() || $brokenLinks->count() || $redirectChains->count() || $heavyAssets->count() || $securityHeaders->count())
    <div class="page-break"></div>
    <div class="section-card">
        <div class="section-title">Detailed Findings Appendix</div>
        <div class="section-subtitle">Supporting tables for metadata, links, assets, and security coverage.</div>

        @if($duplicateTitles->count())
            <div class="score-box-title">Duplicate Titles</div>
            <table class="report-table"><thead><tr><th>URL</th><th>Title</th></tr></thead><tbody>@foreach($duplicateTitles as $row)<tr><td class="truncate-long">{{ $row['url'] ?? 'N/A' }}</td><td>{{ $row['title'] ?? 'N/A' }}</td></tr>@endforeach</tbody></table>
        @endif

        @if($missingMeta->count())
            <div class="score-box-title" style="margin-top: 12px;">Missing Meta Descriptions</div>
            <table class="report-table"><thead><tr><th>URL</th></tr></thead><tbody>@foreach($missingMeta as $row)<tr><td class="truncate-long">{{ $row['url'] ?? 'N/A' }}</td></tr>@endforeach</tbody></table>
        @endif

        @if($missingH1->count())
            <div class="score-box-title" style="margin-top: 12px;">Pages Missing H1</div>
            <table class="report-table"><thead><tr><th>URL</th></tr></thead><tbody>@foreach($missingH1 as $row)<tr><td class="truncate-long">{{ $row['url'] ?? 'N/A' }}</td></tr>@endforeach</tbody></table>
        @endif

        @if($brokenLinks->count())
            <div class="score-box-title" style="margin-top: 12px;">Broken Links</div>
            <table class="report-table"><thead><tr><th>From</th><th>To</th><th>Status</th></tr></thead><tbody>@foreach($brokenLinks as $row)<tr><td class="truncate-long">{{ $row['from_url'] ?? 'N/A' }}</td><td class="truncate-long">{{ $row['to_url'] ?? 'N/A' }}</td><td>{{ $row['status_code'] ?? 'N/A' }}</td></tr>@endforeach</tbody></table>
        @endif

        @if($redirectChains->count())
            <div class="score-box-title" style="margin-top: 12px;">Redirect Chains</div>
            <table class="report-table"><thead><tr><th>From</th><th>To</th><th>Hops</th></tr></thead><tbody>@foreach($redirectChains as $row)<tr><td class="truncate-long">{{ $row['from_url'] ?? 'N/A' }}</td><td class="truncate-long">{{ $row['to_url'] ?? 'N/A' }}</td><td>{{ $row['redirect_hops'] ?? 'N/A' }}</td></tr>@endforeach</tbody></table>
        @endif

        @if($heavyAssets->count())
            <div class="score-box-title" style="margin-top: 12px;">Heavy Assets</div>
            <table class="report-table"><thead><tr><th>Asset</th><th>Type</th><th>KB</th></tr></thead><tbody>@foreach($heavyAssets as $row)<tr><td class="truncate-long">{{ $row['asset_url'] ?? 'N/A' }}</td><td>{{ $row['type'] ?? 'N/A' }}</td><td>{{ $row['size_kb'] ?? 'N/A' }}</td></tr>@endforeach</tbody></table>
        @endif

        @if($securityHeaders->count())
            <div class="score-box-title" style="margin-top: 12px;">Security Header Coverage</div>
            <table class="report-table"><thead><tr><th>Header</th><th>Pages With Header</th><th>Total Pages</th></tr></thead><tbody>@foreach($securityHeaders as $row)<tr><td>{{ $row['header'] ?? 'N/A' }}</td><td>{{ $row['pages_with_header'] ?? 'N/A' }}</td><td>{{ $row['total_pages'] ?? 'N/A' }}</td></tr>@endforeach</tbody></table>
        @endif
    </div>
    @endif

    @if($moreIssues->count())
    <div class="page-break"></div>
    <div class="section-card">
        <div class="section-title">Additional Issues</div>
        <div class="section-subtitle">Overflow issue list for longer reports.</div>
        <table class="report-table">
            <thead><tr><th>Issue</th><th>Impact</th><th>Affected</th><th>Penalty</th></tr></thead>
            <tbody>
                @foreach($moreIssues as $issue)
                    <tr><td class="truncate-long">{{ $issue->title }}</td><td><span class="badge badge-{{ $issue->impact }}">{{ ucfirst($issue->impact) }}</span></td><td>{{ $issue->affected_count ?? 'N/A' }}</td><td>{{ $issue->score_penalty ? '-'.$issue->score_penalty : 'N/A' }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="footer">
        <div>BacklinkPro SEO Audit Report</div>
        <div>Generated {{ $generatedAt }}</div>
    </div>
</div>
</body>
</html>









