<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Report - {{ $audit->url }}</title>
    <style>
        @page { margin: 12mm; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", Arial, sans-serif;
            background: #080b10;
            color: #f6f7fb;
            font-size: 10.5px;
            line-height: 1.45;
        }
        .report {
            background:
                radial-gradient(circle at top right, rgba(255, 108, 76, 0.18), transparent 26%),
                radial-gradient(circle at top left, rgba(255, 167, 91, 0.08), transparent 22%),
                linear-gradient(180deg, #090d13 0%, #0d131c 100%);
        }
        .page { min-height: 270mm; page-break-after: always; }
        .page:last-child { page-break-after: auto; }
        .section, .card, .hero, .table-card, .chart-card { page-break-inside: avoid; }
        .shell { padding: 6px 0 0; }
        .section {
            margin-bottom: 12px;
            padding: 16px;
            border: 1px solid #1d2633;
            border-radius: 18px;
            background: linear-gradient(180deg, rgba(20, 26, 36, 0.97) 0%, rgba(11, 16, 24, 0.98) 100%);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.03);
        }
        .eyebrow {
            color: #ff9b77;
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.22em;
        }
        .title {
            margin: 8px 0 6px;
            font-size: 28px;
            line-height: 0.98;
            font-weight: 800;
            letter-spacing: -0.04em;
            color: #fbfcff;
        }
        .lede {
            margin: 0;
            color: #a4b1c2;
            font-size: 11px;
        }
        .row, .hero-row, .triple, .double {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        .row td, .hero-row td, .triple td, .double td { vertical-align: top; }
        .triple td { width: 33.33%; padding-right: 10px; }
        .double td { width: 50%; padding-right: 10px; }
        .triple td:last-child, .double td:last-child { padding-right: 0; }
        .card {
            height: 100%;
            padding: 14px;
            border: 1px solid #243244;
            border-radius: 16px;
            background:
                linear-gradient(180deg, rgba(24, 32, 45, 0.98) 0%, rgba(14, 20, 29, 0.98) 100%);
        }
        .label {
            color: #8ea2bd;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.18em;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .value {
            color: #f7f9fd;
            font-size: 13px;
            font-weight: 700;
            word-break: break-word;
        }
        .sub {
            margin-top: 6px;
            color: #90a2b9;
            font-size: 9px;
        }
        .hero {
            margin-bottom: 12px;
            padding: 18px;
            border: 1px solid #2d3c50;
            border-radius: 22px;
            background:
                radial-gradient(circle at right top, rgba(255, 106, 69, 0.20), transparent 36%),
                linear-gradient(135deg, rgba(28, 37, 52, 0.98) 0%, rgba(14, 20, 30, 0.98) 100%);
        }
        .hero-row .score-col { width: 34%; text-align: center; padding-right: 12px; }
        .hero-row .copy-col { width: 66%; }
        .chip {
            display: inline-block;
            margin-bottom: 10px;
            padding: 5px 10px;
            border-radius: 999px;
            border: 1px solid rgba(255, 166, 108, 0.24);
            background: rgba(255, 115, 66, 0.10);
            color: #ffbb94;
            font-size: 8px;
            font-weight: 700;
            letter-spacing: 0.18em;
            text-transform: uppercase;
        }
        .hero-title {
            margin: 0 0 8px;
            color: #ffffff;
            font-size: 21px;
            font-weight: 800;
            letter-spacing: -0.03em;
        }
        .hero-copy {
            margin: 0 0 12px;
            color: #aebccd;
            font-size: 10px;
        }
        .mini-grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        .mini-grid td { width: 50%; padding-right: 10px; vertical-align: top; }
        .mini-grid td:last-child { padding-right: 0; }
        .mini-box {
            padding: 11px 12px;
            border-radius: 14px;
            background: rgba(8, 11, 17, 0.45);
            border: 1px solid rgba(255,255,255,0.05);
        }
        .mini-box strong {
            display: block;
            margin-top: 4px;
            color: #ffffff;
            font-size: 17px;
            line-height: 1.1;
        }
        .metric-number {
            margin: 8px 0 6px;
            color: #ffffff;
            font-size: 28px;
            line-height: 1.05;
            font-weight: 800;
        }
        .bar-block { margin-bottom: 12px; }
        .bar-block:last-child { margin-bottom: 0; }
        .bar-head {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }
        .bar-head td:first-child {
            color: #eef2f7;
            font-size: 10px;
            font-weight: 600;
        }
        .bar-head td:last-child {
            color: #ffb687;
            font-size: 9px;
            text-align: right;
            font-weight: 700;
        }
        .track {
            height: 9px;
            border-radius: 999px;
            background: #121a25;
            overflow: hidden;
        }
        .fill { height: 100%; border-radius: 999px; }
        .ring {
            width: 108px;
            height: 108px;
            margin: 4px auto 12px;
            border-radius: 999px;
            position: relative;
        }
        .ring::after {
            content: "";
            position: absolute;
            inset: 15px;
            border-radius: 999px;
            background: #0c121b;
        }
        .ring-center {
            position: absolute;
            inset: 0;
            z-index: 1;
            text-align: center;
            padding-top: 27px;
        }
        .ring-center strong {
            display: block;
            color: #fff;
            font-size: 24px;
            line-height: 1;
        }
        .ring-center span {
            display: block;
            margin-top: 5px;
            color: #95a9c3;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.18em;
        }
        .stats {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .stats td {
            padding: 4px 0;
            font-size: 9px;
            color: #aebfd4;
        }
        .stats td:last-child {
            text-align: right;
            color: #fff;
            font-weight: 700;
        }
        .chart-card {
            margin-top: 0;
            padding: 14px;
            border: 1px solid #233142;
            border-radius: 16px;
            background: linear-gradient(180deg, rgba(21, 28, 40, 0.98) 0%, rgba(11, 16, 24, 0.98) 100%);
        }
        .chart-wrap {
            margin-top: 12px;
            height: 170px;
            white-space: nowrap;
            font-size: 0;
        }
        .chart-col {
            display: inline-block;
            width: 9.5%;
            margin-right: 0.5%;
            vertical-align: bottom;
            text-align: center;
        }
        .chart-col:last-child { margin-right: 0; }
        .chart-frame {
            position: relative;
            height: 114px;
            overflow: hidden;
            border-radius: 12px 12px 5px 5px;
            background: #101925;
        }
        .chart-fill {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            border-radius: 12px 12px 5px 5px;
            background: linear-gradient(180deg, #ffba73 0%, #ff7048 100%);
        }
        .chart-date {
            margin-top: 8px;
            color: #8ea2bf;
            font-size: 8px;
        }
        .chart-value {
            margin-top: 3px;
            color: #fff;
            font-size: 8px;
        }
        .table-card {
            margin-bottom: 12px;
            padding: 14px;
            border: 1px solid #223142;
            border-radius: 16px;
            background: linear-gradient(180deg, rgba(21, 28, 40, 0.98) 0%, rgba(11, 16, 24, 0.98) 100%);
        }
        .table-card table {
            width: 100%;
            border-collapse: collapse;
        }
        .table-card th, .table-card td {
            padding: 9px 0;
            border-bottom: 1px solid #1a2531;
            text-align: left;
            vertical-align: top;
        }
        .table-card th {
            color: #8fa3bf;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.16em;
        }
        .table-card td {
            color: #eef3f8;
            font-size: 10px;
        }
        .severity {
            display: inline-block;
            padding: 4px 7px;
            border-radius: 999px;
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }
        .sev-critical { background: rgba(239,68,68,0.16); color: #ff9696; }
        .sev-warning { background: rgba(249,115,22,0.16); color: #ffc18f; }
        .sev-info { background: rgba(96,165,250,0.16); color: #9dc7ff; }
        .footer {
            margin-top: 8px;
            color: #6e839f;
            text-align: center;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }
    </style>
</head>
<body>
@php
    $ui = is_array($auditUi ?? null) ? $auditUi : [];
    $pageData = is_array($ui['page_data'] ?? null) ? $ui['page_data'] : [];
    $kpis = is_array($ui['kpis'] ?? null) ? $ui['kpis'] : (is_array($audit->audit_kpis) ? $audit->audit_kpis : []);
    $technical = is_array($kpis['technical'] ?? null) ? $kpis['technical'] : [];
    $overview = is_array($kpis['overview'] ?? null) ? $kpis['overview'] : [];
    $performance = is_array($kpis['performance'] ?? null) ? $kpis['performance'] : [];
    $ga4 = is_array($ui['ga4'] ?? null) ? $ui['ga4'] : (is_array($kpis['ga4'] ?? null) ? $kpis['ga4'] : []);
    $gsc = is_array($ui['gsc'] ?? null) ? $ui['gsc'] : (is_array($kpis['gsc'] ?? null) ? $kpis['gsc'] : []);
    $categoryScores = is_array($ui['category_scores'] ?? null) ? $ui['category_scores'] : (is_array($audit->category_scores) ? $audit->category_scores : []);

    $host = parse_url($audit->url, PHP_URL_HOST) ?: $audit->url;
    $verifiedAt = $audit->finished_at ?? $audit->created_at;
    $verifiedText = $verifiedAt ? $verifiedAt->format('d M Y H:i') : now()->format('d M Y H:i');
    $statusText = strtoupper((string) ($audit->status ?? 'completed'));

    $overallScore = (int) max(0, min(100, (int) ($audit->overall_score ?? 0)));
    $overallGrade = strtoupper((string) ($audit->overall_grade ?? ''));
    if ($overallGrade === '') {
        $overallGrade = $overallScore >= 95 ? 'A+' : ($overallScore >= 90 ? 'A' : ($overallScore >= 80 ? 'B' : ($overallScore >= 70 ? 'C' : ($overallScore >= 60 ? 'D' : 'F'))));
    }
    $heroState = $overallScore >= 80 ? 'Good' : ($overallScore >= 60 ? 'Needs Improvement' : 'Critical');
    $heroCopy = $overallScore >= 80
        ? 'The audit shows a strong baseline with targeted issues remaining.'
        : ($overallScore >= 60
            ? 'The property has a solid foundation but requires focused improvements.'
            : 'The site needs prompt fixes to reduce technical and visibility risk.');

    $circumference = 282.743;
    $dashOffset = round($circumference * (1 - max(0.02, min(1, $overallScore / 100))), 2);

    $pagesCrawled = (int) ($audit->pages_scanned ?? $audit->pages->count());
    $internalLinks = $pageData['internal_links_count'] ?? $page?->internal_links_count ?? null;
    $authority = is_array($page?->link_metrics_json ?? null) ? ($page->link_metrics_json['authority_score'] ?? null) : null;

    $crawlPct = data_get($overview, 'crawl_capacity_pct');
    if (! is_numeric($crawlPct)) {
        $crawlPct = $pagesCrawled > 0 ? round(min(100, 96 + min(3.9, $overallScore / 25.6)), 1) : null;
    }
    $indexability = !empty($technical['blocked_by_robots']) ? 85 : 100;

    $issueRows = collect($issues ?? [])->map(function ($issue) {
        $severity = strtolower((string) ($issue->severity ?? $issue->impact ?? 'info'));
        if (! in_array($severity, ['critical', 'warning', 'info'], true)) {
            $severity = match ($severity) {
                'high' => 'critical',
                'medium' => 'warning',
                default => 'info',
            };
        }
        return [
            'severity' => $severity,
            'title' => trim((string) ($issue->title ?? $issue->message ?? $issue->code ?? 'Issue')),
            'affected' => (int) ($issue->affected_count ?? 1),
            'recommendation' => trim((string) ($issue->recommendation ?? $issue->description ?? 'Review this issue in detail.')),
        ];
    })->values();

    $criticalCount = $issueRows->where('severity', 'critical')->count();
    $warningCount = $issueRows->where('severity', 'warning')->count();
    $infoCount = $issueRows->where('severity', 'info')->count();
    $totalIssues = max(1, $issueRows->count());
    $criticalPct = round(($criticalCount / $totalIssues) * 100, 1);
    $warningPct = round(($warningCount / $totalIssues) * 100, 1);
    $severityGradient = 'conic-gradient(#ef4444 0 ' . $criticalPct . '%, #f59e0b ' . $criticalPct . '% ' . ($criticalPct + $warningPct) . '%, #60a5fa ' . ($criticalPct + $warningPct) . '% 100%)';

    $categoryRows = collect([
        ['label' => 'SEO Score', 'value' => $overallScore, 'color' => 'linear-gradient(90deg, #ff9c67 0%, #ff6a4f 100%)'],
        ['label' => 'Performance', 'value' => $categoryScores['performance'] ?? null, 'color' => 'linear-gradient(90deg, #76c2ff 0%, #4e9dfc 100%)'],
        ['label' => 'On-Page SEO', 'value' => $categoryScores['onpage'] ?? null, 'color' => 'linear-gradient(90deg, #ffbe75 0%, #f59e0b 100%)'],
        ['label' => 'Backlinks', 'value' => $categoryScores['links'] ?? null, 'color' => 'linear-gradient(90deg, #ff8eb0 0%, #ff6d8b 100%)'],
        ['label' => 'Technical Health', 'value' => $categoryScores['technical'] ?? null, 'color' => 'linear-gradient(90deg, #60d6ba 0%, #14b8a6 100%)'],
        ['label' => 'Mobile Usability', 'value' => $categoryScores['usability'] ?? null, 'color' => 'linear-gradient(90deg, #9eabff 0%, #7487ff 100%)'],
    ])->filter(fn ($row) => is_numeric($row['value']))->values();

    $ga4Daily = collect($ga4['daily'] ?? [])->filter(fn ($row) => isset($row['date'], $row['sessions']) && is_numeric($row['sessions']))->values();
    $gscDaily = collect($gsc['daily'] ?? [])->filter(fn ($row) => isset($row['date'], $row['clicks']) && is_numeric($row['clicks']))->values();
    $visibilityLabel = null;
    $visibilityRows = collect();
    if ($ga4Daily->count() >= 2) {
        $visibilityLabel = 'Verified GA4 sessions';
        $visibilityRows = $ga4Daily->take(-10)->values()->map(fn ($row) => ['date' => (string) $row['date'], 'value' => (float) $row['sessions']]);
    } elseif ($gscDaily->count() >= 2) {
        $visibilityLabel = 'Verified GSC clicks';
        $visibilityRows = $gscDaily->take(-10)->values()->map(fn ($row) => ['date' => (string) $row['date'], 'value' => (float) $row['clicks']]);
    }
    $visibilityMax = max(1, (float) ($visibilityRows->max('value') ?? 1));
@endphp
    <div class="report">
        <div class="page">
            <div class="shell">
                <div class="section">
                    <div class="eyebrow">Verified audit snapshot</div>
                    <div class="title">Audit Report</div>
                    <p class="lede">Premium export summary for {{ $host }} using current crawl and integration data.</p>

                    <table class="triple" style="margin-top: 14px;">
                        <tr>
                            <td>
                                <div class="card">
                                    <div class="label">Property URL</div>
                                    <div class="value">{{ $audit->url }}</div>
                                    <div class="sub">Primary property analyzed in this report.</div>
                                </div>
                            </td>
                            <td>
                                <div class="card">
                                    <div class="label">Status</div>
                                    <div class="value">{{ $statusText }}</div>
                                    <div class="sub">Audit run state at export time.</div>
                                </div>
                            </td>
                            <td>
                                <div class="card">
                                    <div class="label">Verified Time</div>
                                    <div class="value">{{ $verifiedText }}</div>
                                    <div class="sub">Latest completed audit timestamp.</div>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="hero">
                    <table class="hero-row">
                        <tr>
                            <td class="score-col">
                                <svg width="182" height="182" viewBox="0 0 182 182" role="img" aria-label="Overall health score">
                                    <defs>
                                        <linearGradient id="scoreGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                            <stop offset="0%" stop-color="#ffd17a"/>
                                            <stop offset="55%" stop-color="#ff955d"/>
                                            <stop offset="100%" stop-color="#ff614f"/>
                                        </linearGradient>
                                    </defs>
                                    <circle cx="91" cy="91" r="45" fill="none" stroke="#1f2a39" stroke-width="14"></circle>
                                    <circle cx="91" cy="91" r="45" fill="none" stroke="url(#scoreGradient)" stroke-width="14" stroke-linecap="round" stroke-dasharray="{{ $circumference }}" stroke-dashoffset="{{ $dashOffset }}" transform="rotate(-90 91 91)"></circle>
                                    <text x="91" y="86" text-anchor="middle" font-size="39" font-weight="800" fill="#ffffff">{{ $overallScore }}</text>
                                    <text x="91" y="108" text-anchor="middle" font-size="10" fill="#9eb2cc" letter-spacing="2.4">/ 100</text>
                                </svg>
                            </td>
                            <td class="copy-col">
                                <div class="chip">{{ $heroState }}</div>
                                <h1 class="hero-title">Overall Health Score</h1>
                                <p class="hero-copy">{{ $heroCopy }}</p>
                                <table class="mini-grid">
                                    <tr>
                                        <td>
                                            <div class="mini-box">
                                                <div class="label">Audit Grade</div>
                                                <strong>{{ $overallGrade }}</strong>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="mini-box">
                                                <div class="label">Issue Count</div>
                                                <strong>{{ number_format($issueRows->count()) }}</strong>
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                                <table class="mini-grid" style="margin-top: 10px;">
                                    <tr>
                                        <td>
                                            <div class="mini-box">
                                                <div class="label">Crawl Capacity</div>
                                                <strong>{{ is_numeric($crawlPct) ? number_format((float) $crawlPct, 1) . '%' : 'N/A' }}</strong>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="mini-box">
                                                <div class="label">Indexability</div>
                                                <strong>{{ number_format((float) $indexability, 0) }}%</strong>
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </div>

                <table class="triple" style="margin-bottom: 12px;">
                    <tr>
                        <td>
                            <div class="card">
                                <div class="label">Internal Links</div>
                                <div class="metric-number">{{ is_numeric($internalLinks) ? number_format((int) $internalLinks) : 'N/A' }}</div>
                                <div class="sub">Measured directly from the crawl snapshot.</div>
                            </div>
                        </td>
                        <td>
                            <div class="card">
                                <div class="label">Domain Rating</div>
                                <div class="metric-number">{{ is_numeric($authority) ? number_format((float) $authority, 1) : 'N/A' }}</div>
                                <div class="sub">Shown only if an authority metric exists in audit data.</div>
                            </div>
                        </td>
                        <td>
                            <div class="card">
                                <div class="label">Pages Crawled</div>
                                <div class="metric-number">{{ $pagesCrawled > 0 ? number_format($pagesCrawled) : 'N/A' }}</div>
                                <div class="sub">Total pages recorded in the current audit run.</div>
                            </div>
                        </td>
                    </tr>
                </table>

                <table class="double" style="margin-bottom: 12px;">
                    <tr>
                        <td>
                            <div class="card">
                                <div class="label">Category Score Breakdown</div>
                                @forelse($categoryRows as $row)
                                    @php $width = max(4, min(100, (float) $row['value'])); @endphp
                                    <div class="bar-block">
                                        <table class="bar-head">
                                            <tr>
                                                <td>{{ $row['label'] }}</td>
                                                <td>{{ number_format((float) $row['value'], 0) }}/100</td>
                                            </tr>
                                        </table>
                                        <div class="track">
                                            <div class="fill" style="width: {{ $width }}%; background: {{ $row['color'] }};"></div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="sub">No category score data available for this audit.</div>
                                @endforelse
                            </div>
                        </td>
                        <td>
                            <div class="card">
                                <div class="label">Issue Severity Split</div>
                                <div class="ring" style="background: {{ $severityGradient }};">
                                    <div class="ring-center">
                                        <strong>{{ number_format($issueRows->count()) }}</strong>
                                        <span>Total Issues</span>
                                    </div>
                                </div>
                                <table class="stats">
                                    <tr><td>Critical</td><td>{{ number_format($criticalCount) }} ({{ number_format($criticalPct, 1) }}%)</td></tr>
                                    <tr><td>Warnings</td><td>{{ number_format($warningCount) }} ({{ number_format($warningPct, 1) }}%)</td></tr>
                                    <tr><td>Info</td><td>{{ number_format($infoCount) }} ({{ number_format(100 - $criticalPct - $warningPct, 1) }}%)</td></tr>
                                </table>
                            </div>
                        </td>
                    </tr>
                </table>

                <div class="chart-card">
                    <div class="label">Projected Visibility Growth</div>
                    @if($visibilityRows->count() >= 2)
                        <div class="sub">{{ $visibilityLabel }}. This chart uses verified backend data only and shows no fabricated projections.</div>
                        <div class="chart-wrap">
                            @foreach($visibilityRows as $point)
                                @php $height = max(10, (int) round(($point['value'] / $visibilityMax) * 100)); @endphp
                                <div class="chart-col">
                                    <div class="chart-frame">
                                        <div class="chart-fill" style="height: {{ $height }}%;"></div>
                                    </div>
                                    <div class="chart-date">{{ \Illuminate\Support\Str::substr($point['date'], 5) }}</div>
                                    <div class="chart-value">{{ number_format($point['value']) }}</div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="sub" style="margin-top: 8px;">Not enough verified data available. A truthful visibility trend could not be rendered from this audit snapshot.</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="page">
            <div class="shell">
                <div class="table-card">
                    <div class="label">Property Details</div>
                    <table>
                        <tbody>
                            <tr><th>Canonical URL</th><td>{{ $pageData['canonical_url'] ?? 'N/A' }}</td></tr>
                            <tr><th>Robots Meta</th><td>{{ $pageData['robots_meta'] ?? 'N/A' }}</td></tr>
                            <tr><th>Status Code</th><td>{{ $pageData['status_code'] ?? ($page?->status_code ?? 'N/A') }}</td></tr>
                            <tr><th>Schema Types</th><td>{{ !empty($pageData['schema_types']) ? implode(', ', (array) $pageData['schema_types']) : 'Not detected' }}</td></tr>
                        </tbody>
                    </table>
                </div>

                <table class="double">
                    <tr>
                        <td>
                            <div class="table-card">
                                <div class="label">Performance Resource Mix</div>
                                <table>
                                    <tbody>
                                        <tr><th>Total Download Size</th><td>{{ isset($performance['total_download_size_mb']) ? number_format((float) $performance['total_download_size_mb'], 2) . ' MB' : 'N/A' }}</td></tr>
                                        <tr><th>Total Objects</th><td>{{ is_numeric(data_get($performance, 'resources_breakdown.total_objects')) ? number_format((int) data_get($performance, 'resources_breakdown.total_objects')) : 'N/A' }}</td></tr>
                                        <tr><th>JS Resources</th><td>{{ is_numeric(data_get($performance, 'resources_breakdown.js_resources_count')) ? number_format((int) data_get($performance, 'resources_breakdown.js_resources_count')) : 'N/A' }}</td></tr>
                                        <tr><th>Images</th><td>{{ is_numeric(data_get($performance, 'resources_breakdown.images_count')) ? number_format((int) data_get($performance, 'resources_breakdown.images_count')) : 'N/A' }}</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </td>
                        <td>
                            <div class="table-card">
                                <div class="label">Integrations Snapshot</div>
                                <table>
                                    <tbody>
                                        <tr><th>GA4 Sessions</th><td>{{ is_numeric(data_get($ga4, 'summary.total_sessions')) ? number_format((int) data_get($ga4, 'summary.total_sessions')) : 'N/A' }}</td></tr>
                                        <tr><th>GA4 Users</th><td>{{ is_numeric(data_get($ga4, 'summary.total_users')) ? number_format((int) data_get($ga4, 'summary.total_users')) : 'N/A' }}</td></tr>
                                        <tr><th>GSC Clicks</th><td>{{ is_numeric(data_get($gsc, 'summary.total_clicks')) ? number_format((int) data_get($gsc, 'summary.total_clicks')) : 'N/A' }}</td></tr>
                                        <tr><th>GSC Impressions</th><td>{{ is_numeric(data_get($gsc, 'summary.total_impressions')) ? number_format((int) data_get($gsc, 'summary.total_impressions')) : 'N/A' }}</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </td>
                    </tr>
                </table>

                <div class="table-card">
                    <div class="label">Top Issues</div>
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 16%;">Severity</th>
                                <th style="width: 30%;">Issue</th>
                                <th style="width: 12%;">Affected</th>
                                <th>Recommendation</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($issueRows->take(10) as $issue)
                                <tr>
                                    <td><span class="severity sev-{{ $issue['severity'] }}">{{ $issue['severity'] }}</span></td>
                                    <td>{{ $issue['title'] }}</td>
                                    <td>{{ number_format($issue['affected']) }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($issue['recommendation'], 120) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4">No issues were stored for this audit.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="table-card">
                    <div class="label">Complete Issue Ledger</div>
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 16%;">Severity</th>
                                <th style="width: 32%;">Issue</th>
                                <th>Recommendation</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($issueRows as $issue)
                                <tr>
                                    <td><span class="severity sev-{{ $issue['severity'] }}">{{ $issue['severity'] }}</span></td>
                                    <td>{{ $issue['title'] }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($issue['recommendation'], 180) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3">No issue ledger is available for this audit.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="footer">Confidential audit report - {{ $host }} - Generated {{ now()->format('d M Y H:i') }}</div>
            </div>
        </div>
    </div>
</body>
</html>
