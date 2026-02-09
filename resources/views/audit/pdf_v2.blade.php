<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEO Audit Report - {{ $audit->url }}</title>
    <style>
        @page {
            margin: 2cm;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #1a202c;
            font-size: 11pt;
            background: #f5f7fb;
        }
        .page {
            max-width: 900px;
            margin: 0 auto;
            padding: 1.5cm 1.5cm 2cm;
        }
        .toolbar {
            position: sticky;
            top: 0;
            z-index: 10;
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 0.75cm 1cm;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .toolbar-title {
            font-size: 12pt;
            font-weight: 600;
            color: #1a202c;
        }
        .download-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.35cm 0.8cm;
            border-radius: 8px;
            background: #0f172a;
            color: #ffffff;
            text-decoration: none;
            font-size: 9pt;
            font-weight: 600;
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.15);
        }
        .download-note {
            font-size: 8pt;
            color: #718096;
            margin-top: 0.2cm;
        }
        .cover-page {
            page-break-after: always;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            text-align: center;
            padding: 3cm;
        }
        .cover-logo {
            max-width: 200px;
            margin-bottom: 2cm;
        }
        .cover-title {
            font-size: 36pt;
            font-weight: bold;
            color: {{ $branding->primary_color ?? '#2d3748' }};
            margin-bottom: 1cm;
        }
        .cover-subtitle {
            font-size: 18pt;
            color: #4a5568;
            margin-bottom: 2cm;
        }
        .cover-url {
            font-size: 14pt;
            color: #4299e1;
            word-break: break-all;
        }
        .cover-date {
            margin-top: 2cm;
            font-size: 12pt;
            color: #718096;
        }
        h1 {
            color: #1a202c;
            font-size: 24pt;
            margin-top: 1.5cm;
            margin-bottom: 0.5cm;
            border-bottom: 3px solid {{ $branding->primary_color ?? '#4299e1' }};
            padding-bottom: 0.3cm;
        }
        h2 {
            color: #2d3748;
            font-size: 18pt;
            margin-top: 1cm;
            margin-bottom: 0.5cm;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 0.2cm;
        }
        h3 {
            color: #4a5568;
            font-size: 14pt;
            margin-top: 0.8cm;
            margin-bottom: 0.3cm;
        }
        .section-lead {
            font-size: 9pt;
            color: #718096;
            margin-top: -0.2cm;
            margin-bottom: 0.5cm;
        }
        .executive-summary {
            background-color: #f7fafc;
            padding: 1cm;
            border-radius: 8px;
            margin: 1cm 0;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.5cm;
            margin-top: 0.8cm;
        }
        .summary-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 0.6cm;
            text-align: center;
        }
        .summary-label {
            font-size: 8pt;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 0.2cm;
        }
        .summary-value {
            font-size: 18pt;
            font-weight: bold;
            color: #1a202c;
        }
        .score-large {
            font-size: 72pt;
            font-weight: bold;
            color: {{ $branding->primary_color ?? '#4299e1' }};
            text-align: center;
            line-height: 1;
        }
        .grade-large {
            font-size: 36pt;
            font-weight: bold;
            color: {{ $branding->primary_color ?? '#4299e1' }};
            text-align: center;
            margin-top: 0.3cm;
        }
        .category-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0.5cm;
            margin: 1cm 0;
        }
        .category-card {
            background-color: #f7fafc;
            padding: 0.5cm;
            border-radius: 4px;
            text-align: center;
        }
        .category-name {
            font-size: 9pt;
            color: #718096;
            text-transform: capitalize;
            margin-bottom: 0.2cm;
        }
        .category-score {
            font-size: 20pt;
            font-weight: bold;
            color: #2d3748;
        }
        .section-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 0.8cm;
            margin: 0.8cm 0;
        }
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0.4cm;
        }
        .kpi-box {
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 0.45cm;
            text-align: center;
        }
        .kpi-label {
            font-size: 8pt;
            color: #718096;
        }
        .kpi-value {
            font-size: 14pt;
            font-weight: 700;
            color: #1a202c;
        }
        .status-pill {
            display: inline-block;
            padding: 0.12cm 0.35cm;
            border-radius: 999px;
            font-size: 7.5pt;
            font-weight: 700;
            margin-top: 0.2cm;
        }
        .status-good {
            background: #c6f6d5;
            color: #22543d;
        }
        .status-ni {
            background: #feebc8;
            color: #7c2d12;
        }
        .status-poor {
            background: #fed7d7;
            color: #742a2a;
        }
        .bar {
            display: flex;
            height: 6px;
            border-radius: 999px;
            overflow: hidden;
            background: #e2e8f0;
            margin-top: 0.2cm;
        }
        .bar span {
            display: inline-block;
            height: 100%;
        }
        .bar-good { background: #10b981; }
        .bar-ni { background: #f59e0b; }
        .bar-poor { background: #ef4444; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 0.8cm 0;
            font-size: 9pt;
            background: #ffffff;
        }
        th, td {
            padding: 0.4cm;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        th {
            background-color: #f7fafc;
            font-weight: bold;
            color: #2d3748;
        }
        tr:nth-child(even) td {
            background: #fafbfd;
        }
        .badge {
            display: inline-block;
            padding: 0.15cm 0.4cm;
            border-radius: 4px;
            font-size: 8pt;
            font-weight: bold;
        }
        .badge-high {
            background-color: #fed7d7;
            color: #742a2a;
        }
        .badge-medium {
            background-color: #feebc8;
            color: #7c2d12;
        }
        .badge-low {
            background-color: #bee3f8;
            color: #2c5282;
        }
        .badge-pass {
            background-color: #c6f6d5;
            color: #22543d;
        }
        .badge-fail {
            background-color: #fed7d7;
            color: #742a2a;
        }
        .code-block {
            background-color: #1a202c;
            color: #68d391;
            padding: 0.5cm;
            border-radius: 4px;
            overflow-x: auto;
            font-family: 'Courier New', monospace;
            font-size: 8pt;
            margin: 0.3cm 0;
        }
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.5cm;
            margin: 0.8cm 0;
        }
        .metric-box {
            background-color: #f7fafc;
            padding: 0.5cm;
            border-radius: 4px;
        }
        .footer {
            margin-top: 2cm;
            padding-top: 0.5cm;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            color: #718096;
            font-size: 8pt;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            .cover-page {
                page-break-after: always;
            }
            h1, h2 {
                page-break-after: avoid;
            }
            table {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    @php
        $downloadUrl = request()->fullUrl();
        $downloadUrl = preg_replace('/([&?])download=1(&?)/', '$1', $downloadUrl);
        $downloadUrl = rtrim($downloadUrl, '?&');
        $downloadUrl .= (str_contains($downloadUrl, '?') ? '&' : '?') . 'download=1';
    @endphp

    <div class="toolbar no-print">
        <div>
            <div class="toolbar-title">SEO Audit Report</div>
            <div class="download-note">Click download to save a PDF file.</div>
        </div>
        <a class="download-btn" href="{{ $downloadUrl }}">Download PDF</a>
    </div>

    @php
        $kpis = $audit->audit_kpis ?? [];
        $google = $kpis['google'] ?? [];
        $pagespeed = $google['pagespeed'] ?? null;
        $ga4 = $google['ga4'] ?? null;
        $gsc = $google['gsc'] ?? null;
        $crux = $google['crux'] ?? null;
        $cruxMobile = $crux['mobile']['kpis'] ?? null;
        $cruxDesktop = $crux['desktop']['kpis'] ?? null;
        $reportDate = $audit->created_at ? $audit->created_at->format('F j, Y') : now()->format('F j, Y');
        $completedAt = $audit->finished_at ? $audit->finished_at->format('F j, Y g:i A') : null;
    @endphp

    <!-- Cover Page -->
    <div class="cover-page">
        @if($logoUrl)
            <img src="{{ $logoUrl }}" alt="Logo" class="cover-logo">
        @endif
        <div class="cover-title">SEO Audit Report</div>
        <div class="cover-subtitle">Comprehensive Website Analysis</div>
        <div class="cover-url">{{ $audit->url }}</div>
        <div class="cover-date">{{ $reportDate }}</div>
    </div>

    <div class="page">
        <!-- Executive Summary -->
        <div class="executive-summary">
            <h1>Executive Summary</h1>
            @if($audit->status === 'completed')
                <div class="score-large">{{ $audit->overall_score ?? 'N/A' }}</div>
                <div class="grade-large">{{ $audit->overall_grade ?? 'N/A' }}</div>

                <div class="summary-grid">
                    <div class="summary-card">
                        <div class="summary-label">Pages Scanned</div>
                        <div class="summary-value">{{ $audit->pages_scanned ?? 0 }}</div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-label">Total Issues</div>
                        <div class="summary-value">{{ $issues->count() }}</div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-label">High Impact</div>
                        <div class="summary-value">{{ $issues->where('impact', 'high')->count() }}</div>
                    </div>
                </div>

                @if($audit->category_scores)
                    <div class="category-grid">
                        @foreach($audit->category_scores as $category => $score)
                            <div class="category-card">
                                <div class="category-name">{{ $category }}</div>
                                <div class="category-score">{{ $score }}</div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <h2>Report Overview</h2>
                <table>
                    <tbody>
                        <tr><th>Report Date</th><td>{{ $reportDate }}</td></tr>
                        <tr><th>Completion Time</th><td>{{ $completedAt ?? 'N/A' }}</td></tr>
                        <tr><th>Target URL</th><td style="word-break: break-all;">{{ $audit->url }}</td></tr>
                        <tr><th>Audit Status</th><td>{{ ucfirst($audit->status) }}</td></tr>
                    </tbody>
                </table>
            @endif
        </div>

        @if($audit->status === 'completed')
            <h1>Key Metrics Snapshot</h1>
            <p class="section-lead">A quick glance at the most important signals from lab data, real-user data, and search performance.</p>
            <div class="section-card">
                <div class="kpi-grid">
                    <div class="kpi-box">
                        <div class="kpi-label">PageSpeed Perf</div>
                        <div class="kpi-value">{{ data_get($pagespeed, 'mobile.kpis.categories.performance_score', 'N/A') }}</div>
                    </div>
                    <div class="kpi-box">
                        <div class="kpi-label">CrUX LCP p75 (ms)</div>
                        <div class="kpi-value">{{ data_get($cruxMobile, 'lcp_p75_ms', 'N/A') }}</div>
                    </div>
                    <div class="kpi-box">
                        <div class="kpi-label">GA4 Sessions</div>
                        <div class="kpi-value">{{ data_get($ga4, 'current.sessions', 'N/A') }}</div>
                    </div>
                    <div class="kpi-box">
                        <div class="kpi-label">GSC Impressions</div>
                        <div class="kpi-value">{{ data_get($gsc, 'current.impressions', 'N/A') }}</div>
                    </div>
                </div>
            </div>

            <h1>Content & Metadata</h1>
            <p class="section-lead">Homepage metadata quality, content structure, and core signals.</p>
            <table>
                <tbody>
                    <tr><th>Title</th><td>{{ $page->title ?? 'N/A' }}</td></tr>
                    <tr><th>Title Length</th><td>{{ $page->title_len ?? 'N/A' }}</td></tr>
                    <tr><th>Meta Description</th><td>{{ $page->meta_description ?? 'N/A' }}</td></tr>
                    <tr><th>Meta Length</th><td>{{ $page->meta_len ?? 'N/A' }}</td></tr>
                    <tr><th>H1 / H2 / H3</th><td>{{ $page->h1_count ?? 'N/A' }} / {{ $page->h2_count ?? 'N/A' }} / {{ $page->h3_count ?? 'N/A' }}</td></tr>
                    <tr><th>Word Count</th><td>{{ $page->word_count ?? 'N/A' }}</td></tr>
                    <tr><th>Images (Missing Alt)</th><td>{{ $page->images_total ?? 'N/A' }} ({{ $page->images_missing_alt ?? 'N/A' }})</td></tr>
                    <tr><th>Canonical URL</th><td style="word-break: break-all;">{{ $page->canonical_url ?? 'N/A' }}</td></tr>
                </tbody>
            </table>

            <h1>Technical & Security</h1>
            <p class="section-lead">Server headers, protocol hints, and security signals.</p>
            <table>
                <tbody>
                    <tr><th>Server</th><td>{{ $page->server_header ?? 'N/A' }}</td></tr>
                    <tr><th>X-Powered-By</th><td>{{ $page->x_powered_by ?? 'N/A' }}</td></tr>
                    <tr><th>Content Type</th><td>{{ $page->content_type ?? 'N/A' }}</td></tr>
                    <tr><th>Charset</th><td>{{ $page->charset ?? 'N/A' }}</td></tr>
                    <tr><th>Robots Meta</th><td>{{ $page->robots_meta ?? 'N/A' }}</td></tr>
                    <tr><th>X-Robots-Tag</th><td>{{ $page->x_robots_tag ?? 'N/A' }}</td></tr>
                </tbody>
            </table>
        @endif


        <!-- Top Issues -->
    @if($issues->count() > 0)
        <h1>Top Issues</h1>
        <table>
            <thead>
                <tr>
                    <th>Issue</th>
                    <th>Impact</th>
                    <th>Effort</th>
                    <th>Penalty</th>
                </tr>
            </thead>
            <tbody>
                @foreach($issues->take(20) as $issue)
                    <tr>
                        <td>
                            <strong>{{ $issue->title }}</strong><br>
                            <small>{{ $issue->description }}</small>
                            @if($issue->recommendation)
                                <br><br>
                                <strong>Recommendation:</strong> {{ $issue->recommendation }}
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-{{ $issue->impact }}">
                                {{ ucfirst($issue->impact) }}
                            </span>
                        </td>
                        <td>{{ ucfirst($issue->effort) }}</td>
                        <td>-{{ $issue->score_penalty }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

        <!-- Performance Summary -->
    @if($audit->performance_summary)
        <h1>Performance Summary</h1>
        <div class="metrics-grid">
            <div class="metric-box">
                <h3>Mobile Performance</h3>
                <div style="font-size: 24pt; font-weight: bold; color: #4299e1;">
                    {{ $audit->performance_summary['mobile_avg_score'] ?? 'N/A' }}
                </div>
            </div>
            <div class="metric-box">
                <h3>Desktop Performance</h3>
                <div style="font-size: 24pt; font-weight: bold; color: #4299e1;">
                    {{ $audit->performance_summary['desktop_avg_score'] ?? 'N/A' }}
                </div>
            </div>
        </div>
        
        @if($audit->performance_summary['worst_lcp'])
            <p style="margin-top: 0.5cm;">
                <strong>Worst LCP:</strong> {{ $audit->performance_summary['worst_lcp'] }}ms
                @if($audit->performance_summary['worst_lcp_page'])
                    on {{ $audit->performance_summary['worst_lcp_page'] }}
                @endif
            </p>
        @endif

        @if($topPages->count() > 0)
            <h2>Performance Metrics by Page</h2>
            <table>
                <thead>
                    <tr>
                        <th>URL</th>
                        <th>Mobile Score</th>
                        <th>Desktop Score</th>
                        <th>LCP (ms)</th>
                        <th>CLS</th>
                        <th>TBT (ms)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($topPages as $page)
                        <tr>
                            <td style="word-break: break-all;">{{ $page->url }}</td>
                            <td>{{ $page->performance_metrics['mobile']['score'] ?? 'N/A' }}</td>
                            <td>{{ $page->performance_metrics['desktop']['score'] ?? 'N/A' }}</td>
                            <td>{{ $page->performance_metrics['mobile']['lcp'] ?? 'N/A' }}</td>
                            <td>{{ $page->performance_metrics['mobile']['cls'] ?? 'N/A' }}</td>
                            <td>{{ $page->performance_metrics['mobile']['tbt'] ?? 'N/A' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endif

        <!-- Google KPIs -->
        <h1>Google KPIs</h1>
        <div class="section-card">
            <h3>PageSpeed (Lab)</h3>
            <div class="kpi-grid">
                <div class="kpi-box">
                    <div class="kpi-label">Performance</div>
                    <div class="kpi-value">{{ data_get($pagespeed, 'mobile.kpis.categories.performance_score', 'N/A') }}</div>
                </div>
                <div class="kpi-box">
                    <div class="kpi-label">SEO</div>
                    <div class="kpi-value">{{ data_get($pagespeed, 'mobile.kpis.categories.seo_score', 'N/A') }}</div>
                </div>
                <div class="kpi-box">
                    <div class="kpi-label">Accessibility</div>
                    <div class="kpi-value">{{ data_get($pagespeed, 'mobile.kpis.categories.accessibility_score', 'N/A') }}</div>
                </div>
                <div class="kpi-box">
                    <div class="kpi-label">Best Practices</div>
                    <div class="kpi-value">{{ data_get($pagespeed, 'mobile.kpis.categories.best_practices_score', 'N/A') }}</div>
                </div>
            </div>
        </div>

        <div class="section-card">
            <h3>Google Analytics (GA4)</h3>
            <div class="kpi-grid">
                <div class="kpi-box">
                    <div class="kpi-label">Sessions</div>
                    <div class="kpi-value">{{ data_get($ga4, 'current.sessions', 'N/A') }}</div>
                </div>
                <div class="kpi-box">
                    <div class="kpi-label">Active Users</div>
                    <div class="kpi-value">{{ data_get($ga4, 'current.active_users', 'N/A') }}</div>
                </div>
                <div class="kpi-box">
                    <div class="kpi-label">Engagement Rate</div>
                    <div class="kpi-value">{{ data_get($ga4, 'current.engagement_rate', 'N/A') }}</div>
                </div>
                <div class="kpi-box">
                    <div class="kpi-label">Conversions</div>
                    <div class="kpi-value">{{ data_get($ga4, 'current.conversions', 'N/A') }}</div>
                </div>
            </div>
        </div>

        <div class="section-card">
            <h3>Search Console (GSC)</h3>
            <div class="kpi-grid">
                <div class="kpi-box">
                    <div class="kpi-label">Clicks</div>
                    <div class="kpi-value">{{ data_get($gsc, 'current.clicks', 'N/A') }}</div>
                </div>
                <div class="kpi-box">
                    <div class="kpi-label">Impressions</div>
                    <div class="kpi-value">{{ data_get($gsc, 'current.impressions', 'N/A') }}</div>
                </div>
                <div class="kpi-box">
                    <div class="kpi-label">CTR</div>
                    <div class="kpi-value">{{ data_get($gsc, 'current.ctr', 'N/A') }}</div>
                </div>
                <div class="kpi-box">
                    <div class="kpi-label">Avg Position</div>
                    <div class="kpi-value">{{ data_get($gsc, 'current.position', 'N/A') }}</div>
                </div>
            </div>
        </div>

        <div class="section-card">
            <h3>Real User Core Web Vitals (CrUX)</h3>
            <table>
                <thead>
                    <tr>
                        <th>Metric</th>
                        <th>Mobile p75</th>
                        <th>Desktop p75</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>LCP (ms)</td>
                        <td>{{ data_get($cruxMobile, 'lcp_p75_ms', 'N/A') }}</td>
                        <td>{{ data_get($cruxDesktop, 'lcp_p75_ms', 'N/A') }}</td>
                    </tr>
                    <tr>
                        <td>INP (ms)</td>
                        <td>{{ data_get($cruxMobile, 'inp_p75_ms', 'N/A') }}</td>
                        <td>{{ data_get($cruxDesktop, 'inp_p75_ms', 'N/A') }}</td>
                    </tr>
                    <tr>
                        <td>CLS</td>
                        <td>{{ data_get($cruxMobile, 'cls_p75', 'N/A') }}</td>
                        <td>{{ data_get($cruxDesktop, 'cls_p75', 'N/A') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        @php
            $psOpportunities = data_get($pagespeed, 'mobile.kpis.opportunities', []);
            $ga4TopPages = data_get($ga4, 'top_pages', []);
            $gscTopQueries = data_get($gsc, 'top_queries', []);
            $gscTopPages = data_get($gsc, 'top_pages', []);
        @endphp

        @if(!empty($psOpportunities))
            <div class="section-card">
                <h3>PageSpeed Opportunities (Top)</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Opportunity</th>
                            <th>Savings (ms)</th>
                            <th>Savings (KB)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(collect($psOpportunities)->take(8) as $opp)
                            <tr>
                                <td>{{ $opp['title'] ?? 'N/A' }}</td>
                                <td>{{ $opp['savings_ms'] ?? 'N/A' }}</td>
                                <td>
                                    @if(isset($opp['savings_bytes']))
                                        {{ round(($opp['savings_bytes'] ?? 0) / 1024) }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if(!empty($ga4TopPages))
            <div class="section-card">
                <h3>GA4 Top Pages</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Page</th>
                            <th>Views</th>
                            <th>Active Users</th>
                            <th>Conversions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(collect($ga4TopPages)->take(8) as $row)
                            <tr>
                                <td style="word-break: break-all;">{{ $row['page_path'] ?? 'N/A' }}</td>
                                <td>{{ $row['views'] ?? 'N/A' }}</td>
                                <td>{{ $row['active_users'] ?? 'N/A' }}</td>
                                <td>{{ $row['conversions'] ?? 'N/A' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if(!empty($gscTopQueries))
            <div class="section-card">
                <h3>Search Console Top Queries</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Query</th>
                            <th>Clicks</th>
                            <th>Impressions</th>
                            <th>CTR</th>
                            <th>Position</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(collect($gscTopQueries)->take(8) as $row)
                            <tr>
                                <td>{{ $row['query'] ?? 'N/A' }}</td>
                                <td>{{ $row['clicks'] ?? 'N/A' }}</td>
                                <td>{{ $row['impressions'] ?? 'N/A' }}</td>
                                <td>{{ $row['ctr'] ?? 'N/A' }}</td>
                                <td>{{ $row['position'] ?? 'N/A' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if(!empty($gscTopPages))
            <div class="section-card">
                <h3>Search Console Top Pages</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Page</th>
                            <th>Clicks</th>
                            <th>Impressions</th>
                            <th>CTR</th>
                            <th>Position</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(collect($gscTopPages)->take(8) as $row)
                            <tr>
                                <td style="word-break: break-all;">{{ $row['page_url'] ?? 'N/A' }}</td>
                                <td>{{ $row['clicks'] ?? 'N/A' }}</td>
                                <td>{{ $row['impressions'] ?? 'N/A' }}</td>
                                <td>{{ $row['ctr'] ?? 'N/A' }}</td>
                                <td>{{ $row['position'] ?? 'N/A' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

    <!-- Security Headers -->
    @php
        $securityPages = $pages->whereNotNull('security_headers')->take(5);
    @endphp
    @if($securityPages->count() > 0)
        <h1>Security Headers</h1>
        <table>
            <thead>
                <tr>
                    <th>Page</th>
                    <th>HSTS</th>
                    <th>X-Frame-Options</th>
                    <th>X-Content-Type-Options</th>
                    <th>Referrer-Policy</th>
                    <th>CSP</th>
                </tr>
            </thead>
            <tbody>
                @foreach($securityPages as $page)
                    <tr>
                        <td style="word-break: break-all;">{{ $page->url }}</td>
                        <td>
                            <span class="badge {{ ($page->security_headers['hsts'] ?? false) ? 'badge-pass' : 'badge-fail' }}">
                                {{ ($page->security_headers['hsts'] ?? false) ? 'Present' : 'Missing' }}
                            </span>
                        </td>
                        <td>
                            <span class="badge {{ ($page->security_headers['x_frame_options'] ?? false) ? 'badge-pass' : 'badge-fail' }}">
                                {{ ($page->security_headers['x_frame_options'] ?? false) ? 'Present' : 'Missing' }}
                            </span>
                        </td>
                        <td>
                            <span class="badge {{ ($page->security_headers['x_content_type_options'] ?? false) ? 'badge-pass' : 'badge-fail' }}">
                                {{ ($page->security_headers['x_content_type_options'] ?? false) ? 'Present' : 'Missing' }}
                            </span>
                        </td>
                        <td>
                            <span class="badge {{ ($page->security_headers['referrer_policy'] ?? false) ? 'badge-pass' : 'badge-fail' }}">
                                {{ ($page->security_headers['referrer_policy'] ?? false) ? 'Present' : 'Missing' }}
                            </span>
                        </td>
                        <td>
                            <span class="badge {{ ($page->security_headers['csp'] ?? false) ? 'badge-pass' : 'badge-fail' }}">
                                {{ ($page->security_headers['csp'] ?? false) ? 'Present' : 'Missing' }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <!-- Links Summary -->
    @if($audit->crawl_stats)
        <h1>Links Summary</h1>
        <div class="metrics-grid">
            <div class="metric-box">
                <h3>Broken Links</h3>
                <div style="font-size: 24pt; font-weight: bold; color: #e53e3e;">
                    {{ $audit->crawl_stats['broken_links_count'] ?? 0 }}
                </div>
            </div>
            <div class="metric-box">
                <h3>Redirect Chains</h3>
                <div style="font-size: 24pt; font-weight: bold; color: #d69e2e;">
                    {{ $audit->crawl_stats['redirect_chain_count'] ?? 0 }}
                </div>
            </div>
            <div class="metric-box">
                <h3>Duplicate Titles</h3>
                <div style="font-size: 24pt; font-weight: bold; color: #d69e2e;">
                    {{ $audit->crawl_stats['duplicate_titles_groups'] ?? 0 }}
                </div>
            </div>
            <div class="metric-box">
                <h3>Duplicate Meta</h3>
                <div style="font-size: 24pt; font-weight: bold; color: #d69e2e;">
                    {{ $audit->crawl_stats['duplicate_meta_groups'] ?? 0 }}
                </div>
            </div>
        </div>
    @endif

    <!-- Appendix: All Issues -->
    @if($issues->count() > 20)
        <h1>Appendix: All Issues</h1>
        <table>
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Title</th>
                    <th>Impact</th>
                    <th>Penalty</th>
                </tr>
            </thead>
            <tbody>
                @foreach($issues->skip(20) as $issue)
                    <tr>
                        <td>{{ $issue->code }}</td>
                        <td>{{ $issue->title }}</td>
                        <td>
                            <span class="badge badge-{{ $issue->impact }}">
                                {{ ucfirst($issue->impact) }}
                            </span>
                        </td>
                        <td>-{{ $issue->score_penalty }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <!-- Footer -->
    <div class="footer">
        @if($branding && $branding->report_footer_text)
            <p>{{ $branding->report_footer_text }}</p>
        @elseif(!$hideBranding)
            <p>Generated by BacklinkPro SEO Audit Tool</p>
        @endif
        <p>{{ now()->format('F j, Y g:i A') }}</p>
    </div>
    </div>
</body>
</html>
