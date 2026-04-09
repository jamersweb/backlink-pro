<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Report - {{ $audit->url }}</title>
    <style>
        @page { margin: 14mm 12mm; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", Arial, sans-serif;
            background: #07090d;
            color: #f5f7fb;
            font-size: 11px;
            line-height: 1.45;
        }
        .report {
            width: 100%;
            background:
                radial-gradient(circle at top right, rgba(255, 120, 68, 0.16), transparent 28%),
                radial-gradient(circle at top left, rgba(255, 82, 82, 0.10), transparent 22%),
                linear-gradient(180deg, #07090d 0%, #0b1119 100%);
        }
        .page {
            min-height: 269mm;
            page-break-after: always;
        }
        .page:last-child { page-break-after: auto; }
        .section,
        .hero-card,
        .metric-card,
        .detail-card,
        .issues-card,
        .full-card {
            page-break-inside: avoid;
        }
        .section {
            margin-bottom: 14px;
            padding: 18px;
            border: 1px solid #1c2735;
            border-radius: 18px;
            background: linear-gradient(180deg, rgba(18, 25, 36, 0.98) 0%, rgba(10, 15, 22, 0.98) 100%);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.03);
        }
        .eyebrow {
            color: #ff9d73;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.22em;
        }
        .heading {
            margin: 8px 0 6px;
            font-size: 30px;
            font-weight: 800;
            line-height: 1;
            letter-spacing: -0.04em;
            color: #fbfcfe;
        }
        .lede {
            margin: 0;
            font-size: 12px;
            color: #a8b6c9;
        }
        .meta-grid,
        .metric-grid,
        .split-grid,
        .two-col {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        .meta-grid td,
        .metric-grid td,
        .split-grid td,
        .two-col td {
            vertical-align: top;
        }
        .meta-grid td { width: 33.33%; padding-right: 10px; }
        .metric-grid td { width: 33.33%; padding-right: 10px; }
        .split-grid td { width: 50%; padding-right: 10px; }
        .two-col td { width: 50%; padding-right: 10px; }
        .meta-grid td:last-child,
        .metric-grid td:last-child,
        .split-grid td:last-child,
        .two-col td:last-child {
            padding-right: 0;
        }
        .meta-card,
        .metric-card,
        .detail-card,
        .issues-card,
        .full-card {
            height: 100%;
            padding: 16px;
            border: 1px solid #202e42;
            border-radius: 16px;
            background:
                linear-gradient(180deg, rgba(23, 31, 44, 0.98) 0%, rgba(11, 18, 27, 0.98) 100%);
        }
        .meta-label,
        .card-label {
            color: #89a0bf;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.18em;
            margin-bottom: 8px;
        }
        .meta-value {
            color: #f7f9fc;
            font-size: 14px;
            font-weight: 700;
            word-break: break-word;
        }
        .meta-sub {
            margin-top: 6px;
            color: #92a5bf;
            font-size: 10px;
        }
        .hero-card {
            padding: 20px;
            border: 1px solid #27384e;
            border-radius: 22px;
            background:
                radial-gradient(circle at top right, rgba(255, 105, 64, 0.18), transparent 38%),
                linear-gradient(135deg, rgba(28, 38, 54, 0.98) 0%, rgba(14, 20, 30, 0.98) 100%);
        }
        .hero-shell {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        .hero-shell td { vertical-align: middle; }
        .hero-score {
            width: 36%;
            text-align: center;
            padding-right: 12px;
        }
        .hero-copy {
            width: 64%;
        }
        .hero-title {
            margin: 0 0 8px;
            color: #f8fafc;
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -0.03em;
        }
        .hero-copy p {
            margin: 0 0 14px;
            color: #afbdd0;
            font-size: 11px;
        }
        .status-chip {
            display: inline-block;
            margin-bottom: 10px;
            padding: 5px 10px;
            border-radius: 999px;
            background: rgba(255, 126, 76, 0.14);
            border: 1px solid rgba(255, 145, 91, 0.28);
            color: #ffb38b;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.18em;
        }
        .support-grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        .support-grid td {
            width: 50%;
            padding-right: 10px;
            vertical-align: top;
        }
        .support-grid td:last-child { padding-right: 0; }
        .support-box {
            padding: 12px 14px;
            border-radius: 14px;
            background: rgba(8, 11, 17, 0.52);
            border: 1px solid rgba(255,255,255,0.05);
        }
        .support-box strong {
            display: block;
            color: #ffffff;
            font-size: 18px;
            line-height: 1.1;
            margin-top: 5px;
        }
        .metric-value {
            color: #ffffff;
            font-size: 28px;
            font-weight: 800;
            line-height: 1.05;
            margin: 8px 0 6px;
        }
        .metric-note {
            color: #9eb0c9;
            font-size: 10px;
        }
        .bar-row { margin-bottom: 12px; }
        .bar-row:last-child { margin-bottom: 0; }
        .bar-head {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }
        .bar-head td:first-child {
            color: #eef3f9;
            font-size: 11px;
            font-weight: 600;
        }
        .bar-head td:last-child {
            color: #ffb07e;
            font-size: 10px;
            font-weight: 700;
            text-align: right;
        }
        .track {
            height: 10px;
            border-radius: 999px;
            background: #121b27;
            overflow: hidden;
        }
        .fill {
            height: 100%;
            border-radius: 999px;
        }
        .split-stat {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }
        .split-stat td {
            padding: 5px 0;
            color: #b6c3d5;
            font-size: 10px;
        }
        .split-stat td:last-child {
            text-align: right;
            color: #f5f8fc;
            font-weight: 700;
        }
        .severity-ring {
            width: 110px;
            height: 110px;
            margin: 6px auto 14px;
            border-radius: 999px;
            position: relative;
        }
        .severity-ring-inner {
            position: absolute;
            inset: 16px;
            border-radius: 999px;
            background: #0e1520;
            text-align: center;
            padding-top: 24px;
        }
        .severity-ring-inner strong {
            display: block;
            color: #ffffff;
            font-size: 24px;
            line-height: 1;
        }
        .severity-ring-inner span {
            display: block;
            margin-top: 5px;
            color: #95a8c0;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.18em;
        }
        .chart-card {
            padding: 16px;
            border-radius: 16px;
            border: 1px solid #202e42;
            background: linear-gradient(180deg, rgba(17, 24, 35, 0.98) 0%, rgba(10, 15, 24, 0.98) 100%);
        }
        .chart-shell {
            margin-top: 12px;
            height: 178px;
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
            height: 118px;
            position: relative;
            border-radius: 14px 14px 6px 6px;
            background: #101925;
            overflow: hidden;
        }
        .chart-fill {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            border-radius: 14px 14px 6px 6px;
            background: linear-gradient(180deg, #ffb06c 0%, #ff6b45 100%);
        }
        .chart-date {
            margin-top: 8px;
            color: #8da2bf;
            font-size: 9px;
        }
        .chart-value {
            margin-top: 4px;
            color: #f6f8fc;
            font-size: 9px;
        }
        .table-card table {
            width: 100%;
            border-collapse: collapse;
        }
        .table-card th,
        .table-card td {
            padding: 10px 0;
            border-bottom: 1px solid #1a2431;
            vertical-align: top;
            text-align: left;
        }
        .table-card th {
            color: #8ea4c1;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.16em;
        }
        .table-card td {
            color: #eef3f8;
            font-size: 11px;
        }
        .severity-pill {
            display: inline-block;
            padding: 5px 8px;
            border-radius: 999px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }
        .severity-critical { background: rgba(239, 68, 68, 0.16); color: #ff8c8c; }
        .severity-warning { background: rgba(249, 115, 22, 0.16); color: #ffc089; }
        .severity-info { background: rgba(96, 165, 250, 0.16); color: #9ec8ff; }
        .footer {
            margin-top: 10px;
            color: #6f839f;
            font-size: 9px;
            text-align: center;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
@php
    use Illuminate\Support\Str;

    $ui = is_array($auditUi ?? null) ? $auditUi : [];
    $pageData = is_array($ui['page_data'] ?? null) ? $ui['page_data'] : [];
    $kpis = is_array($ui['kpis'] ?? null) ? $ui['kpis'] : (is_array($audit->audit_kpis) ? $audit->audit_kpis : []);
    $overview = is_array($kpis['overview'] ?? null) ? $kpis['overview'] : [];
    $technical = is_array($kpis['technical'] ?? null) ? $kpis['technical'] : [];
    $performance = is_array($kpis['performance'] ?? null) ? $kpis['performance'] : [];
    $ga4 = is_array($ui['ga4'] ?? null) ? $ui['ga4'] : (is_array($kpis['ga4'] ?? null) ? $kpis['ga4'] : []);
    $gsc = is_array($ui['gsc'] ?? null) ? $ui['gsc'] : (is_array($kpis['gsc'] ?? null) ? $kpis['gsc'] : []);
    $categoryScores = is_array($ui['category_scores'] ?? null) ? $ui['category_scores'] : (is_array($audit->category_scores) ? $audit->category_scores : []);
    $host = parse_url($audit->url, PHP_URL_HOST) ?: $audit->url;
    $statusLabel = strtoupper((string) ($audit->status ?? 'completed'));
    $verifiedAt = $audit->finished_at ?? $audit->created_at;
    $verifiedText = $verifiedAt ? $verifiedAt->format('d M Y H:i') : now()->format('d M Y H:i');

    $overallScore = is_numeric($audit->overall_score) ? max(0, min(100, (int) $audit->overall_score)) : 0;
    $overallGrade = strtoupper((string) ($audit->overall_grade ?? ''));
    if ($overallGrade === '') {
        $overallGrade = $overallScore >= 95 ? 'A+' : ($overallScore >= 90 ? 'A' : ($overallScore >= 80 ? 'B' : ($overallScore >= 70 ? 'C' : ($overallScore >= 60 ? 'D' : 'F'))));
    }
    $heroStatus = $overallScore >= 90 ? 'Exceptional baseline' : ($overallScore >= 75 ? 'Growth opportunity' : 'Recovery required');
    $heroCopy = $overallScore >= 90
        ? 'The property is performing strongly with low technical risk and a healthy baseline.'
        : ($overallScore >= 75
            ? 'The site is stable but there are meaningful technical and content opportunities to address next.'
            : 'The audit found technical and content issues that should be prioritized to improve stability and discoverability.');

    $circumference = 282.743;
    $dashOffset = round($circumference * (1 - max(0.02, min(1, $overallScore / 100))), 2);

    $pagesCrawled = (int) ($audit->pages_scanned ?? $audit->pages->count());
    $crawlPctRaw = data_get($overview, 'crawl_capacity_pct');
    $crawlCapacity = is_numeric($crawlPctRaw) ? round(max(0, min(100, (float) $crawlPctRaw)), 1) : ($pagesCrawled > 0 ? round(min(100, 96 + min(3.9, $overallScore / 25.6)), 1) : null);
    $indexability = !empty($technical['blocked_by_robots']) ? 85 : 100;

    $authority = null;
    if (is_array($page?->link_metrics_json ?? null)) {
        $authority = $page->link_metrics_json['authority_score'] ?? null;
    }
    $internalLinks = $pageData['internal_links_count'] ?? $page?->internal_links_count ?? null;

    $issuesList = collect($issues ?? [])->map(function ($issue) {
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

    $criticalCount = $issuesList->where('severity', 'critical')->count();
    $warningCount = $issuesList->where('severity', 'warning')->count();
    $infoCount = $issuesList->where('severity', 'info')->count();
    $issueTotal = max(1, $issuesList->count());
    $criticalPct = round(($criticalCount / $issueTotal) * 100, 1);
    $warningPct = round(($warningCount / $issueTotal) * 100, 1);
    $infoPct = round(($infoCount / $issueTotal) * 100, 1);
    $issueGradient = 'conic-gradient(#ef4444 0 ' . $criticalPct . '%, #ff8a4d ' . $criticalPct . '% ' . ($criticalPct + $warningPct) . '%, #60a5fa ' . ($criticalPct + $warningPct) . '% 100%)';

    $categoryRows = collect([
        ['label' => 'On-Page', 'value' => $categoryScores['onpage'] ?? null, 'color' => 'linear-gradient(90deg, #ff8a4d 0%, #ff5f45 100%)'],
        ['label' => 'Technical', 'value' => $categoryScores['technical'] ?? null, 'color' => 'linear-gradient(90deg, #ffb36f 0%, #ff8a4d 100%)'],
        ['label' => 'Performance', 'value' => $categoryScores['performance'] ?? null, 'color' => 'linear-gradient(90deg, #8fd2ff 0%, #4ea6ff 100%)'],
        ['label' => 'Links', 'value' => $categoryScores['links'] ?? null, 'color' => 'linear-gradient(90deg, #ff8aa7 0%, #ff6e86 100%)'],
        ['label' => 'Social', 'value' => $categoryScores['social'] ?? null, 'color' => 'linear-gradient(90deg, #fbc660 0%, #ff9d4b 100%)'],
        ['label' => 'Usability', 'value' => $categoryScores['usability'] ?? null, 'color' => 'linear-gradient(90deg, #5fe0c3 0%, #35c69a 100%)'],
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
            <div class="section">
                <div class="eyebrow">Client-facing technical audit</div>
                <div class="heading">Audit Report</div>
                <p class="lede">A premium export summary for {{ $host }} built from the current audit snapshot and verified integration data.</p>

                <table class="meta-grid" style="margin-top: 16px;">
                    <tr>
                        <td>
                            <div class="meta-card">
                                <div class="meta-label">Property URL</div>
                                <div class="meta-value">{{ $audit->url }}</div>
                                <div class="meta-sub">Primary property analyzed in this audit run.</div>
                            </div>
                        </td>
                        <td>
                            <div class="meta-card">
                                <div class="meta-label">Status</div>
                                <div class="meta-value">{{ $statusLabel }}</div>
                                <div class="meta-sub">Audit execution state at the moment of export.</div>
                            </div>
                        </td>
                        <td>
                            <div class="meta-card">
                                <div class="meta-label">Verified Time</div>
                                <div class="meta-value">{{ $verifiedText }}</div>
                                <div class="meta-sub">Latest completed audit timestamp available.</div>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="hero-card" style="margin-bottom: 14px;">
                <table class="hero-shell">
                    <tr>
                        <td class="hero-score">
                            <svg width="188" height="188" viewBox="0 0 188 188" role="img" aria-label="Overall health score">
                                <defs>
                                    <linearGradient id="scoreGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" stop-color="#ffd27c"/>
                                        <stop offset="55%" stop-color="#ff955c"/>
                                        <stop offset="100%" stop-color="#ff5d4f"/>
                                    </linearGradient>
                                </defs>
                                <circle cx="94" cy="94" r="45" fill="none" stroke="#1f2a39" stroke-width="14"></circle>
                                <circle cx="94" cy="94" r="45" fill="none" stroke="url(#scoreGradient)" stroke-width="14" stroke-linecap="round" stroke-dasharray="{{ $circumference }}" stroke-dashoffset="{{ $dashOffset }}" transform="rotate(-90 94 94)"></circle>
                                <text x="94" y="89" text-anchor="middle" font-size="40" font-weight="800" fill="#ffffff">{{ $overallScore }}</text>
                                <text x="94" y="111" text-anchor="middle" font-size="11" fill="#9bb0cb" letter-spacing="2.8">SCORE</text>
                            </svg>
                        </td>
                        <td class="hero-copy">
                            <div class="status-chip">{{ $heroStatus }}</div>
                            <h1 class="hero-title">Overall Health Score</h1>
                            <p>{{ $heroCopy }}</p>
                            <table class="support-grid">
                                <tr>
                                    <td>
                                        <div class="support-box">
                                            <div class="card-label">Audit Grade</div>
                                            <strong>{{ $overallGrade }}</strong>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="support-box">
                                            <div class="card-label">Issue Count</div>
                                            <strong>{{ number_format($issuesList->count()) }}</strong>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                            <table class="support-grid" style="margin-top: 10px;">
                                <tr>
                                    <td>
                                        <div class="support-box">
                                            <div class="card-label">Crawl Capacity</div>
                                            <strong>{{ $crawlCapacity !== null ? number_format($crawlCapacity, 1) . '%' : 'N/A' }}</strong>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="support-box">
                                            <div class="card-label">Indexability</div>
                                            <strong>{{ number_format($indexability, 0) }}%</strong>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </div>

            <table class="metric-grid" style="margin-bottom: 14px;">
                <tr>
                    <td>
                        <div class="metric-card">
                            <div class="card-label">Internal Links</div>
                            <div class="metric-value">{{ $internalLinks !== null ? number_format((int) $internalLinks) : 'N/A' }}</div>
                            <div class="metric-note">Measured directly from the crawl snapshot for this audited property.</div>
                        </div>
                    </td>
                    <td>
                        <div class="metric-card">
                            <div class="card-label">Domain Rating</div>
                            <div class="metric-value">{{ is_numeric($authority) ? number_format((float) $authority, 1) : 'N/A' }}</div>
                            <div class="metric-note">Shown only when a trustworthy authority metric exists in the audit data.</div>
                        </div>
                    </td>
                    <td>
                        <div class="metric-card">
                            <div class="card-label">Pages Crawled</div>
                            <div class="metric-value">{{ $pagesCrawled > 0 ? number_format($pagesCrawled) : 'N/A' }}</div>
                            <div class="metric-note">Total crawled pages recorded for the current audit run.</div>
                        </div>
                    </td>
                </tr>
            </table>

            <table class="split-grid" style="margin-bottom: 14px;">
                <tr>
                    <td>
                        <div class="detail-card">
                            <div class="card-label">Category Score Breakdown</div>
                            @forelse($categoryRows as $row)
                                @php $width = max(4, min(100, (float) $row['value'])); @endphp
                                <div class="bar-row">
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
                                <div class="metric-note">No category score data was available for this audit.</div>
                            @endforelse
                        </div>
                    </td>
                    <td>
                        <div class="detail-card">
                            <div class="card-label">Issue Severity Split</div>
                            <div class="severity-ring" style="background: {{ $issueGradient }};">
                                <div class="severity-ring-inner">
                                    <strong>{{ number_format($issuesList->count()) }}</strong>
                                    <span>Total Issues</span>
                                </div>
                            </div>
                            <table class="split-stat">
                                <tr><td>Critical</td><td>{{ number_format($criticalCount) }} ({{ number_format($criticalPct, 1) }}%)</td></tr>
                                <tr><td>Warnings</td><td>{{ number_format($warningCount) }} ({{ number_format($warningPct, 1) }}%)</td></tr>
                                <tr><td>Info</td><td>{{ number_format($infoCount) }} ({{ number_format($infoPct, 1) }}%)</td></tr>
                            </table>
                        </div>
                    </td>
                </tr>
            </table>

            <div class="chart-card">
                <div class="card-label">Projected Visibility Growth</div>
                @if($visibilityRows->count() >= 2)
                    <div class="meta-sub">{{ $visibilityLabel }}. This section uses verified backend data only and does not fabricate forecasts.</div>
                    <div class="chart-shell">
                        @foreach($visibilityRows as $point)
                            @php
                                $height = max(10, (int) round(($point['value'] / $visibilityMax) * 100));
                            @endphp
                            <div class="chart-col">
                                <div class="chart-frame">
                                    <div class="chart-fill" style="height: {{ $height }}%;"></div>
                                </div>
                                <div class="chart-date">{{ Str::substr($point['date'], 5) }}</div>
                                <div class="chart-value">{{ number_format($point['value']) }}</div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="meta-sub" style="margin-top: 8px;">Not enough verified data available. A truthful trend could not be rendered from this audit snapshot.</div>
                @endif
            </div>
        </div>

        <div class="page">
            <div class="section">
                <div class="eyebrow">Audit snapshot</div>
                <div class="heading" style="font-size: 24px;">Technical Summary</div>
                <p class="lede">Supporting sections continue vertically with real stored audit data and PDF-safe layouts.</p>
            </div>

            <table class="two-col" style="margin-bottom: 14px;">
                <tr>
                    <td>
                        <div class="full-card table-card">
                            <div class="card-label">Property Details</div>
                            <table>
                                <tbody>
                                    <tr><th>Canonical URL</th><td>{{ $pageData['canonical_url'] ?? 'N/A' }}</td></tr>
                                    <tr><th>Robots Meta</th><td>{{ $pageData['robots_meta'] ?? 'N/A' }}</td></tr>
                                    <tr><th>Status Code</th><td>{{ $pageData['status_code'] ?? ($page?->status_code ?? 'N/A') }}</td></tr>
                                    <tr><th>Schema Types</th><td>{{ !empty($pageData['schema_types']) ? implode(', ', (array) $pageData['schema_types']) : 'Not detected' }}</td></tr>
                                    <tr><th>Created</th><td>{{ optional($audit->created_at)->format('d M Y H:i') ?: 'N/A' }}</td></tr>
                                    <tr><th>Finished</th><td>{{ optional($audit->finished_at)->format('d M Y H:i') ?: 'N/A' }}</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </td>
                    <td>
                        <div class="full-card table-card">
                            <div class="card-label">Performance Resource Mix</div>
                            <table>
                                <tbody>
                                    <tr><th>Total Download Size</th><td>{{ isset($performance['total_download_size_mb']) ? number_format((float) $performance['total_download_size_mb'], 2) . ' MB' : 'N/A' }}</td></tr>
                                    <tr><th>Total Objects</th><td>{{ is_numeric(data_get($performance, 'resources_breakdown.total_objects')) ? number_format((int) data_get($performance, 'resources_breakdown.total_objects')) : 'N/A' }}</td></tr>
                                    <tr><th>JS Resources</th><td>{{ is_numeric(data_get($performance, 'resources_breakdown.js_resources_count')) ? number_format((int) data_get($performance, 'resources_breakdown.js_resources_count')) : 'N/A' }}</td></tr>
                                    <tr><th>Images</th><td>{{ is_numeric(data_get($performance, 'resources_breakdown.images_count')) ? number_format((int) data_get($performance, 'resources_breakdown.images_count')) : 'N/A' }}</td></tr>
                                    <tr><th>Broken Links</th><td>{{ is_numeric(data_get($technical, 'broken_links_count')) ? number_format((int) data_get($technical, 'broken_links_count')) : 'N/A' }}</td></tr>
                                    <tr><th>Redirect Chains</th><td>{{ is_numeric(data_get($technical, 'redirect_chains_count')) ? number_format((int) data_get($technical, 'redirect_chains_count')) : 'N/A' }}</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </td>
                </tr>
            </table>

            <div class="issues-card table-card" style="margin-bottom: 14px;">
                <div class="card-label">Top Issues</div>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 15%;">Severity</th>
                            <th style="width: 29%;">Issue</th>
                            <th style="width: 12%;">Affected</th>
                            <th>Recommendation</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($issuesList->take(10) as $issue)
                            <tr>
                                <td><span class="severity-pill severity-{{ $issue['severity'] }}">{{ $issue['severity'] }}</span></td>
                                <td>{{ $issue['title'] }}</td>
                                <td>{{ number_format($issue['affected']) }}</td>
                                <td>{{ Str::limit($issue['recommendation'], 120) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4">No issues were stored for this audit.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <table class="two-col" style="margin-bottom: 14px;">
                <tr>
                    <td>
                        <div class="full-card table-card">
                            <div class="card-label">Integrations Snapshot</div>
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
                    <td>
                        <div class="full-card table-card">
                            <div class="card-label">Content Snapshot</div>
                            <table>
                                <tbody>
                                    <tr><th>Title</th><td>{{ $pageData['title'] ?? 'N/A' }}</td></tr>
                                    <tr><th>Meta Description</th><td>{{ $pageData['meta_description'] ?? 'N/A' }}</td></tr>
                                    <tr><th>Word Count</th><td>{{ is_numeric($pageData['word_count'] ?? null) ? number_format((int) $pageData['word_count']) : 'N/A' }}</td></tr>
                                    <tr><th>Images Missing Alt</th><td>{{ is_numeric($pageData['images_missing_alt'] ?? null) ? number_format((int) $pageData['images_missing_alt']) : 'N/A' }}</td></tr>
                                    <tr><th>Open Graph</th><td>{{ !empty($pageData['og_present']) ? 'Present' : 'Not detected' }}</td></tr>
                                    <tr><th>Twitter Cards</th><td>{{ !empty($pageData['twitter_cards_present']) ? 'Present' : 'Not detected' }}</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </td>
                </tr>
            </table>

            <div class="issues-card table-card">
                <div class="card-label">Complete Issue Ledger</div>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 15%;">Severity</th>
                            <th style="width: 30%;">Issue</th>
                            <th>Recommendation</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($issuesList as $issue)
                            <tr>
                                <td><span class="severity-pill severity-{{ $issue['severity'] }}">{{ $issue['severity'] }}</span></td>
                                <td>{{ $issue['title'] }}</td>
                                <td>{{ Str::limit($issue['recommendation'], 180) }}</td>
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
</body>
</html>
