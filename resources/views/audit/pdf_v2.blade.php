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
        .executive-summary {
            background-color: #f7fafc;
            padding: 1cm;
            border-radius: 8px;
            margin: 1cm 0;
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 0.8cm 0;
            font-size: 9pt;
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
    <!-- Cover Page -->
    <div class="cover-page">
        @if($logoUrl)
            <img src="{{ $logoUrl }}" alt="Logo" class="cover-logo">
        @endif
        <div class="cover-title">SEO Audit Report</div>
        <div class="cover-subtitle">Comprehensive Website Analysis</div>
        <div class="cover-url">{{ $audit->url }}</div>
        <div class="cover-date">{{ $audit->created_at->format('F j, Y') }}</div>
    </div>

    <!-- Executive Summary -->
    <div class="executive-summary">
        <h1>Executive Summary</h1>
        @if($audit->status === 'completed')
            <div class="score-large">{{ $audit->overall_score ?? 'N/A' }}</div>
            <div class="grade-large">{{ $audit->overall_grade ?? 'N/A' }}</div>
            
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

            <h2>Key Findings</h2>
            <ul style="margin-left: 1.5cm; margin-top: 0.5cm;">
                <li>Total Issues Found: {{ $issues->count() }}</li>
                <li>High Impact Issues: {{ $issues->where('impact', 'high')->count() }}</li>
                <li>Pages Scanned: {{ $audit->pages_scanned ?? 0 }}</li>
                @if($audit->crawl_stats)
                    <li>Broken Links: {{ $audit->crawl_stats['broken_links_count'] ?? 0 }}</li>
                    <li>Redirect Chains: {{ $audit->crawl_stats['redirect_chain_count'] ?? 0 }}</li>
                @endif
            </ul>
        @endif
    </div>


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
</body>
</html>
