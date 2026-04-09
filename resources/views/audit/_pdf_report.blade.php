<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Report - {{ $audit->url }}</title>
    <style>
        @page { margin: 18mm 14mm; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", Arial, sans-serif;
            color: #e7ecf5;
            background: #08111f;
            font-size: 12px;
            line-height: 1.45;
        }
        .report-shell { width: 100%; }
        .section {
            margin-top: 18px;
            padding: 20px;
            border: 1px solid #1b2a42;
            border-radius: 18px;
            background: linear-gradient(180deg, #0c1728 0%, #09111d 100%);
            page-break-inside: avoid;
        }
        .eyebrow {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.18em;
            color: #8fa6c7;
        }
        .title {
            margin: 8px 0 6px;
            font-size: 28px;
            line-height: 1.1;
            color: #f6f8fb;
        }
        .muted { color: #9dafc8; }
        .strong { color: #f7fbff; }
        .hero {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        .hero-copy { width: 68%; padding-right: 18px; vertical-align: top; }
        .hero-score { width: 32%; text-align: center; vertical-align: middle; }
        .pill {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 999px;
            border: 1px solid #2f4365;
            background: #111d31;
            color: #d7e4f6;
            font-size: 10px;
            margin-right: 6px;
            margin-bottom: 6px;
        }
        .meta-table, .grid-table, .issues-table, .ledger-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        .meta-card, .mini-card {
            width: 33.33%;
            vertical-align: top;
            padding: 0 8px 0 0;
        }
        .mini-card.two-col { width: 50%; }
        .card {
            padding: 16px;
            border: 1px solid #1f314d;
            border-radius: 16px;
            background: #0d1828;
        }
        .card-label {
            margin: 0 0 8px;
            font-size: 10px;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #8ea6c9;
        }
        .card-value {
            font-size: 24px;
            font-weight: 700;
            color: #f6f8fb;
        }
        .section-title {
            margin: 0 0 8px;
            font-size: 18px;
            color: #f6f8fb;
        }
        .section-copy {
            margin: 0 0 14px;
            color: #9dafc8;
        }
        .score-grade {
            margin-top: 6px;
            font-size: 12px;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: #ffb57f;
        }
        .chart-shell {
            height: 190px;
            padding: 14px 14px 10px;
            border: 1px solid #1f314d;
            border-radius: 16px;
            background: #0d1828;
        }
        .chart-wrap {
            height: 128px;
            padding-top: 12px;
            white-space: nowrap;
            font-size: 0;
        }
        .chart-bar {
            display: inline-block;
            width: 9%;
            margin-right: 1%;
            height: 100%;
            vertical-align: bottom;
            text-align: center;
        }
        .bar-track {
            position: relative;
            height: 100px;
            border-radius: 12px 12px 0 0;
            background: linear-gradient(180deg, #101b2d 0%, #0a1321 100%);
            overflow: hidden;
        }
        .bar-fill {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            border-radius: 12px 12px 0 0;
            background: linear-gradient(180deg, #ffb866 0%, #ff7a45 100%);
        }
        .bar-label {
            margin-top: 8px;
            font-size: 10px;
            color: #89a1c2;
        }
        .bar-value {
            margin-top: 4px;
            font-size: 10px;
            color: #f4f7fb;
        }
        .kv {
            width: 100%;
            border-collapse: collapse;
        }
        .kv td {
            padding: 8px 0;
            border-bottom: 1px solid #17263c;
            vertical-align: top;
        }
        .kv td:first-child {
            width: 38%;
            color: #8ea6c9;
            padding-right: 12px;
        }
        .issues-table th, .issues-table td, .ledger-table th, .ledger-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #17263c;
            text-align: left;
            vertical-align: top;
        }
        .issues-table th, .ledger-table th {
            font-size: 10px;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #88a3c6;
        }
        .severity {
            display: inline-block;
            padding: 4px 9px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .severity-critical { background: rgba(255, 106, 93, 0.18); color: #ff8f82; }
        .severity-warning { background: rgba(255, 184, 102, 0.18); color: #ffc37d; }
        .severity-info { background: rgba(103, 165, 255, 0.18); color: #8abdff; }
        .footer {
            margin-top: 18px;
            text-align: center;
            color: #7087aa;
            font-size: 10px;
        }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
@php
    $ui = $auditUi ?? [];
    $pageData = $ui['page_data'] ?? [];
    $kpis = $ui['kpis'] ?? [];
    $ga4 = $kpis['ga4'] ?? [];
    $gsc = $kpis['gsc'] ?? [];
    $reportModules = $ui['report_modules'] ?? [];
    $overview = $kpis['overview'] ?? [];
    $linksKpi = $kpis['links'] ?? [];
    $technical = $kpis['technical'] ?? [];
    $performance = $kpis['performance'] ?? [];
    $social = $kpis['social'] ?? [];
    $localSeo = $kpis['local_seo'] ?? [];
    $techEmail = $kpis['tech_email'] ?? [];
    $statusLabel = ucfirst($audit->status ?? 'unknown');
    $verifiedFlags = array_values(array_filter([
        !empty($ui['ga4_ready_at']) ? 'GA4 verified' : null,
        !empty($ui['gsc_ready_at']) ? 'GSC verified' : null,
        !empty(data_get($kpis, 'google.pagespeed')) ? 'PageSpeed synced' : null,
    ]));
    $verifiedText = $verifiedFlags ? implode(' / ', $verifiedFlags) : 'Audit-only data';
    $overallScore = is_numeric($audit->overall_score) ? max(0, min(100, (int) $audit->overall_score)) : null;
    $grade = $audit->overall_grade ?: 'N/A';
    $circumference = 2 * pi() * 52;
    $dashOffset = $overallScore !== null ? $circumference * (1 - ($overallScore / 100)) : $circumference;
    $internalLinks = $pageData['internal_links_count'] ?? data_get($linksKpi, 'on_page_link_structure.internal_links');
    $domainRating = data_get($linksKpi, 'citation_flow');
    if ($domainRating === null) {
        $domainRating = data_get($linksKpi, 'authority_score');
    }
    $issuesList = collect($issues ?? [])->map(function ($issue) {
        $severity = strtolower($issue->severity ?? $issue->impact ?? 'info');
        if (!in_array($severity, ['critical', 'warning', 'info'], true)) {
            $severity = match ($severity) {
                'high' => 'critical',
                'medium' => 'warning',
                default => 'info',
            };
        }
        return [
            'severity' => $severity,
            'title' => $issue->title ?? $issue->message ?? 'Issue',
            'affected' => $issue->affected_count ?? 1,
            'recommendation' => $issue->recommendation ?? $issue->description ?? 'Review this issue in detail.',
        ];
    });
    $ga4Daily = collect($ga4['daily'] ?? [])->filter(fn ($row) => isset($row['date'], $row['sessions']) && is_numeric($row['sessions']))->values();
    $gscDaily = collect($gsc['daily'] ?? [])->filter(fn ($row) => isset($row['date'], $row['clicks']) && is_numeric($row['clicks']))->values();
    $visibilityLabel = null;
    $visibilityRows = collect();
    if ($ga4Daily->count() >= 2) {
        $visibilityLabel = 'Verified GA4 sessions';
        $visibilityRows = $ga4Daily->take(-10)->values()->map(fn ($row) => ['date' => $row['date'], 'value' => (float) $row['sessions']]);
    } elseif ($gscDaily->count() >= 2) {
        $visibilityLabel = 'Verified GSC clicks';
        $visibilityRows = $gscDaily->take(-10)->values()->map(fn ($row) => ['date' => $row['date'], 'value' => (float) $row['clicks']]);
    }
    $visibilityMax = max(1, (float) $visibilityRows->max('value'));
@endphp
    <div class="report-shell">
        <div class="section">
            <div class="eyebrow">Client-facing SEO document</div>
            <h1 class="title">AUDIT REPORT</h1>
            <p class="muted">Prepared from the live audit snapshot captured for <span class="strong">{{ parse_url($audit->url, PHP_URL_HOST) ?: $audit->url }}</span>.</p>

            <table class="grid-table" style="margin-top:16px;">
                <tr>
                    <td class="meta-card">
                        <div class="card">
                            <div class="card-label">Property</div>
                            <div class="strong">{{ $audit->url }}</div>
                        </div>
                    </td>
                    <td class="meta-card">
                        <div class="card">
                            <div class="card-label">Status</div>
                            <div class="strong">{{ $statusLabel }}</div>
                        </div>
                    </td>
                    <td class="meta-card" style="padding-right:0;">
                        <div class="card">
                            <div class="card-label">Verified metadata</div>
                            <div class="strong">{{ $verifiedText }}</div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="section">
            <table class="hero">
                <tr>
                    <td class="hero-copy">
                        <div class="eyebrow">Overall health score</div>
                        <h2 class="title" style="font-size:26px;">{{ $overallScore !== null ? $overallScore . '/100' : 'Score unavailable' }}</h2>
                        <p class="section-copy">This full-width score block is optimized for PDF export stability. It summarizes the current audit grade, issue density, and crawl confidence without using navigation-style UI.</p>
                        <div>
                            <span class="pill">Grade {{ $grade }}</span>
                            <span class="pill">{{ $issuesList->count() }} tracked issues</span>
                            <span class="pill">{{ $page ? ($page->status_code ?? 'N/A') : ($pageData['status_code'] ?? 'N/A') }} HTTP status</span>
                        </div>
                    </td>
                    <td class="hero-score">
                        <svg width="170" height="170" viewBox="0 0 170 170" role="img" aria-label="Overall score donut">
                            <defs>
                                <linearGradient id="scoreGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" stop-color="#ffd07a"/>
                                    <stop offset="55%" stop-color="#ff9e5c"/>
                                    <stop offset="100%" stop-color="#ff6b4a"/>
                                </linearGradient>
                            </defs>
                            <circle cx="85" cy="85" r="52" fill="none" stroke="#17263b" stroke-width="16"></circle>
                            <circle cx="85" cy="85" r="52" fill="none" stroke="url(#scoreGradient)" stroke-width="16" stroke-linecap="round" stroke-dasharray="{{ $circumference }}" stroke-dashoffset="{{ $dashOffset }}" transform="rotate(-90 85 85)"></circle>
                            <text x="85" y="80" text-anchor="middle" font-size="36" font-weight="700" fill="#f6f8fb">{{ $overallScore !== null ? $overallScore : 'N/A' }}</text>
                            <text x="85" y="104" text-anchor="middle" font-size="12" letter-spacing="2" fill="#8ea6c9">HEALTH</text>
                        </svg>
                        <div class="score-grade">{{ $grade }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="section">
            <table class="grid-table">
                <tr>
                    <td class="mini-card two-col">
                        <div class="card">
                            <div class="card-label">Internal Links</div>
                            <div class="card-value">{{ $internalLinks !== null && $internalLinks !== '' ? number_format((int) $internalLinks) : 'N/A' }}</div>
                            <p class="muted">Directly measured from the audited property crawl.</p>
                        </div>
                    </td>
                    <td class="mini-card two-col" style="padding-right:0;">
                        <div class="card">
                            <div class="card-label">Domain Rating</div>
                            <div class="card-value">{{ $domainRating !== null && $domainRating !== '' ? number_format((float) $domainRating, 1) : 'Not available' }}</div>
                            <p class="muted">Shown only when a trustworthy authority metric exists in this audit payload.</p>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="eyebrow">Projected Visibility Growth</div>
            <h2 class="section-title">Verified visibility baseline</h2>
            @if($visibilityRows->count() >= 2)
                <p class="section-copy">{{ $visibilityLabel }} from the audit integration snapshot. No fabricated forecasts or placeholder projections are shown.</p>
                <div class="chart-shell">
                    <div class="muted">{{ $visibilityLabel }}</div>
                    <div class="chart-wrap">
                        @foreach($visibilityRows as $point)
                            @php
                                $height = max(10, (int) round(($point['value'] / $visibilityMax) * 100));
                            @endphp
                            <div class="chart-bar">
                                <div class="bar-track">
                                    <div class="bar-fill" style="height: {{ $height }}%;"></div>
                                </div>
                                <div class="bar-label">{{ \Illuminate\Support\Str::substr($point['date'], 5) }}</div>
                                <div class="bar-value">{{ number_format($point['value']) }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="card">
                    <div class="strong">Not enough verified data available</div>
                    <p class="section-copy" style="margin-top:8px;">This audit does not currently include enough trusted GA4 or Search Console daily points to render a truthful visibility trend.</p>
                </div>
            @endif
        </div>

        <div class="section">
            <div class="eyebrow">Audit snapshot</div>
            <h2 class="section-title">Property details</h2>
            <table class="kv">
                <tr><td>Canonical URL</td><td>{{ $pageData['canonical_url'] ?? 'N/A' }}</td></tr>
                <tr><td>Protocol</td><td>{{ str_starts_with($audit->normalized_url, 'https://') ? 'HTTPS secure' : 'HTTP' }}</td></tr>
                <tr><td>Created</td><td>{{ optional($audit->created_at)->format('F j, Y g:i A') ?: 'N/A' }}</td></tr>
                <tr><td>Finished</td><td>{{ optional($audit->finished_at)->format('F j, Y g:i A') ?: 'N/A' }}</td></tr>
                <tr><td>Robots Meta</td><td>{{ $pageData['robots_meta'] ?? 'N/A' }}</td></tr>
                <tr><td>Schema types</td><td>{{ !empty($pageData['schema_types']) ? implode(', ', (array) $pageData['schema_types']) : 'Not detected' }}</td></tr>
            </table>
        </div>

        <div class="section">
            <div class="eyebrow">Top findings</div>
            <h2 class="section-title">Priority issues</h2>
            <table class="issues-table">
                <thead>
                    <tr>
                        <th style="width:14%;">Severity</th>
                        <th style="width:26%;">Issue</th>
                        <th style="width:10%;">Affected</th>
                        <th>Recommendation</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($issuesList->take(8) as $issue)
                        <tr>
                            <td><span class="severity severity-{{ $issue['severity'] }}">{{ $issue['severity'] }}</span></td>
                            <td>{{ $issue['title'] }}</td>
                            <td>{{ number_format((int) $issue['affected']) }}</td>
                            <td>{{ $issue['recommendation'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="muted">No issues were stored for this audit.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="section page-break">
            <div class="eyebrow">Core report sections</div>
            <h2 class="section-title">Performance and integrity</h2>
            <table class="grid-table">
                <tr>
                    <td class="mini-card two-col">
                        <div class="card">
                            <div class="card-label">Performance resource mix</div>
                            <table class="kv">
                                <tr><td>Total download size</td><td>{{ isset($performance['total_download_size_mb']) ? number_format((float) $performance['total_download_size_mb'], 2) . ' MB' : 'N/A' }}</td></tr>
                                <tr><td>Total objects</td><td>{{ isset($performance['resources_breakdown']['total_objects']) ? number_format((int) $performance['resources_breakdown']['total_objects']) : 'N/A' }}</td></tr>
                                <tr><td>JavaScript resources</td><td>{{ isset($performance['resources_breakdown']['js_resources_count']) ? number_format((int) $performance['resources_breakdown']['js_resources_count']) : 'N/A' }}</td></tr>
                                <tr><td>Images</td><td>{{ isset($performance['resources_breakdown']['images_count']) ? number_format((int) $performance['resources_breakdown']['images_count']) : 'N/A' }}</td></tr>
                            </table>
                        </div>
                    </td>
                    <td class="mini-card two-col" style="padding-right:0;">
                        <div class="card">
                            <div class="card-label">Technical integrity</div>
                            <table class="kv">
                                <tr><td>HTTPS enabled</td><td>{{ !empty($technical['https_enabled']) ? 'Yes' : 'No' }}</td></tr>
                                <tr><td>HTTPS redirect</td><td>{{ array_key_exists('https_redirect_ok', $technical) ? (!empty($technical['https_redirect_ok']) ? 'Yes' : 'No') : 'N/A' }}</td></tr>
                                <tr><td>robots.txt present</td><td>{{ !empty($technical['robots_txt_present']) ? 'Yes' : 'No' }}</td></tr>
                                <tr><td>XML sitemap present</td><td>{{ !empty($technical['xml_sitemap_present']) ? 'Yes' : 'No' }}</td></tr>
                                <tr><td>Broken links</td><td>{{ isset($technical['broken_links_count']) ? number_format((int) $technical['broken_links_count']) : 'N/A' }}</td></tr>
                                <tr><td>Indexability issues</td><td>{{ isset($technical['indexability_issues_count']) ? number_format((int) $technical['indexability_issues_count']) : 'N/A' }}</td></tr>
                            </table>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="eyebrow">Integrations snapshot</div>
            <h2 class="section-title">Connected data sources</h2>
            <table class="ledger-table">
                <thead>
                    <tr>
                        <th>Source</th>
                        <th>Current metric</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Google Analytics 4</td>
                        <td>{{ isset($ga4['summary']['total_sessions']) ? number_format((int) $ga4['summary']['total_sessions']) . ' sessions' : 'N/A' }}</td>
                        <td>{{ !empty($ui['ga4_ready_at']) ? 'Verified' : (!empty($ga4['connected']) ? 'Connected' : 'Unavailable') }}</td>
                    </tr>
                    <tr>
                        <td>Google Search Console</td>
                        <td>{{ isset($gsc['summary']['total_clicks']) ? number_format((int) $gsc['summary']['total_clicks']) . ' clicks' : 'N/A' }}</td>
                        <td>{{ !empty($ui['gsc_ready_at']) ? 'Verified' : (!empty($gsc['connected']) ? 'Connected' : 'Unavailable') }}</td>
                    </tr>
                    <tr>
                        <td>Social Signals</td>
                        <td>{{ !empty($social['open_graph_tags_present']) ? 'Open Graph present' : 'Limited metadata' }}</td>
                        <td>{{ !empty($social) ? 'Captured' : 'Unavailable' }}</td>
                    </tr>
                    <tr>
                        <td>Local and Email Signals</td>
                        <td>{{ !empty($localSeo['address_found']) || !empty($techEmail['spf_present']) ? 'Partial evidence found' : 'No strong signal stored' }}</td>
                        <td>{{ !empty($localSeo) || !empty($techEmail) ? 'Captured' : 'Unavailable' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="section">
            <div class="eyebrow">Complete issue ledger</div>
            <h2 class="section-title">Full recommendations list</h2>
            <table class="ledger-table">
                <thead>
                    <tr>
                        <th style="width:16%;">Severity</th>
                        <th style="width:28%;">Issue</th>
                        <th>Recommendation</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($issuesList as $issue)
                        <tr>
                            <td><span class="severity severity-{{ $issue['severity'] }}">{{ $issue['severity'] }}</span></td>
                            <td>{{ $issue['title'] }}</td>
                            <td>{{ $issue['recommendation'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="muted">No issue ledger is available for this audit.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="footer">
            Confidential audit report - {{ parse_url($audit->url, PHP_URL_HOST) ?: $audit->url }} - Generated {{ now()->format('F j, Y g:i A') }}
        </div>
    </div>
</body>
</html>
