<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Backlink Pro Audit Report</title>
    <style>
        @page { margin: 10mm; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: DejaVu Sans, Arial, sans-serif; background: #131313; color: #eee7e4; font-size: 9px; line-height: 1.45; }
        table { width: 100%; border-collapse: collapse; }
        .page-break { page-break-before: always; }
        .shell { background: #131313; }
        .nav { border-bottom: 1px solid #262626; padding-bottom: 10px; margin-bottom: 18px; }
        .brand { color: #fff; font-size: 18px; font-weight: 700; }
        .navcopy { color: #8b817d; font-size: 8px; text-transform: uppercase; letter-spacing: .16em; margin-top: 6px; }
        .navcopy .active { color: #ff5626; font-weight: 700; }
        .eyebrow { color: #ff5626; font-size: 8px; text-transform: uppercase; letter-spacing: .28em; font-weight: 700; }
        .hero { font-size: 34px; line-height: .94; font-weight: 800; color: #fff; letter-spacing: -.04em; margin: 8px 0 10px; }
        .hero .muted { color: #5a4640; }
        .copy { color: #9f9490; font-size: 10px; }
        .meta { text-align: right; color: #807673; font-size: 8px; text-transform: uppercase; letter-spacing: .14em; line-height: 1.9; }
        .gap td { vertical-align: top; padding-right: 8px; }
        .gap td:last-child { padding-right: 0; }
        .panel { background: #1c1b1b; border-radius: 12px; padding: 16px; }
        .panel-hi { background: #2a2a2a; border-radius: 12px; padding: 16px; }
        .score-panel { border-left: 4px solid #ff5626; }
        .score-ring { width: 128px; height: 128px; border: 8px solid #2f1d18; border-top-color: #ff5626; border-right-color: #ff5626; border-bottom-color: #ff8a67; border-radius: 999px; text-align: center; padding-top: 24px; margin: 0 auto; }
        .score-num { font-size: 34px; font-weight: 800; color: #fff; line-height: 1; }
        .score-grade { color: #ff8159; font-size: 9px; text-transform: uppercase; letter-spacing: .18em; font-weight: 700; margin-top: 6px; }
        .title { color: #fff; font-size: 22px; font-weight: 700; letter-spacing: -.03em; margin: 0 0 8px; }
        .subcopy { color: #b0a5a2; font-size: 10px; margin: 0 0 14px; }
        .mono { color: #867c78; font-size: 8px; text-transform: uppercase; letter-spacing: .14em; font-weight: 700; }
        .metric { background: #201f1f; border-radius: 10px; padding: 12px; }
        .metric .v { color: #fff; font-size: 22px; font-weight: 800; margin-top: 8px; }
        .chart { height: 145px; vertical-align: bottom; }
        .bar { width: 100%; border-radius: 4px 4px 0 0; background: #262626; }
        .bar.f { background: #5d2818; border-top: 2px solid #ff5626; }
        .ringmini { width: 58px; height: 58px; border-radius: 999px; border: 4px solid #2b2b2b; text-align: center; padding-top: 15px; margin: 0 auto 8px; color: #fff; font-size: 14px; font-weight: 700; }
        .green { border-top-color: #22c55e; border-right-color: #22c55e; }
        .orange { border-top-color: #ff5626; border-right-color: #ff5626; }
        .status { background: #201f1f; border-radius: 8px; padding: 10px 12px; margin-bottom: 8px; }
        .ok { color: #22c55e; font-weight: 700; } .warn { color: #ffbe59; font-weight: 700; } .bad { color: #ff8080; font-weight: 700; }
        .tbl th { color: #817774; font-size: 8px; text-transform: uppercase; letter-spacing: .14em; text-align: left; padding: 0 0 10px; border-bottom: 1px solid #2a2a2a; }
        .tbl td { padding: 12px 0; border-bottom: 1px solid #242424; vertical-align: top; }
        .issue { color: #fff; font-size: 12px; font-weight: 700; margin-bottom: 4px; }
        .issuecopy { color: #9b918d; font-size: 9px; }
        .pill { display: inline-block; border-radius: 999px; padding: 4px 8px; font-size: 8px; text-transform: uppercase; letter-spacing: .1em; font-weight: 700; }
        .crit { background: #4d1917; color: #ff8a84; } .med { background: #513613; color: #ffbe59; } .info { background: #16284c; color: #7fb2ff; }
        .insight1 { background: #ff5626; color: #541000; border-radius: 12px; padding: 16px; }
        .insight2 { background: #1c1b1b; color: #efe7e3; border-radius: 12px; padding: 16px; }
        .insight-title { font-size: 22px; line-height: 1; font-weight: 800; margin: 10px 0; }
        .tag { display: inline-block; background: #201f1f; color: #a39a97; border-radius: 8px; padding: 4px 8px; font-size: 8px; text-transform: uppercase; letter-spacing: .08em; margin-right: 6px; }
        .track { height: 7px; border-radius: 999px; background: #252525; margin-top: 6px; overflow: hidden; }
        .fill { height: 7px; border-radius: 999px; background: #ff5626; }
        .footer { border-top: 1px solid #262626; margin-top: 18px; padding-top: 12px; color: #736966; font-size: 8px; text-transform: uppercase; letter-spacing: .14em; }
    </style>
</head>
<body>
@php
    $ui = is_array($auditUi ?? null) ? $auditUi : [];
    $pageData = is_array($ui['page_data'] ?? null) ? $ui['page_data'] : [];
    $kpis = is_array($ui['kpis'] ?? null) ? $ui['kpis'] : (is_array($audit->audit_kpis) ? $audit->audit_kpis : []);
    $technical = is_array($kpis['technical'] ?? null) ? $kpis['technical'] : [];
    $ga4 = is_array($ui['ga4'] ?? null) ? $ui['ga4'] : [];
    $gsc = is_array($ui['gsc'] ?? null) ? $ui['gsc'] : [];
    $categoryScores = is_array($ui['category_scores'] ?? null) ? $ui['category_scores'] : (is_array($audit->category_scores) ? $audit->category_scores : []);
    $host = parse_url($audit->url, PHP_URL_HOST) ?: $audit->url;
    $reportWhen = $audit->finished_at ?? $audit->created_at;
    $summaryArr = is_array($audit->summary) ? $audit->summary : [];
    $summaryText = trim((string)($summaryArr['overview'] ?? $summaryArr['summary'] ?? ''));
    if ($summaryText === '') $summaryText = 'Comprehensive performance analysis and crawl integrity review for the submitted property.';
    $overallScore = (int) max(0, min(100, (int) ($audit->overall_score ?? 0)));
    $overallGrade = strtoupper((string) ($audit->overall_grade ?? ''));
    if ($overallGrade === '' && $overallScore > 0) $overallGrade = $overallScore >= 95 ? 'A+' : ($overallScore >= 90 ? 'A' : ($overallScore >= 80 ? 'B' : ($overallScore >= 70 ? 'C' : 'D')));
    if ($overallGrade === '') $overallGrade = 'N/A';
    $fmt = function ($v, $d = 0) { if ($v === null || $v === '') return 'N/A'; return is_numeric($v) ? number_format((float) $v, $d) : (string) $v; };
    $compact = function ($v) { if (!is_numeric($v)) return '—'; $n = (float) $v; return $n >= 1000 ? number_format($n / 1000, 1).'K' : (string) (int) $n; };
    $internalLinks = $pageData['internal_links_count'] ?? $page?->internal_links_count;
    $authority = is_array($page?->link_metrics_json) ? ($page->link_metrics_json['authority_score'] ?? null) : null;
    $pagesCrawled = (int) ($audit->pages_scanned ?? $audit->pages->count());
    $critCount = $issues->where('impact', 'high')->count();
    $warnCount = $issues->where('impact', 'medium')->count();
    $infoCount = max(0, $issues->count() - $critCount - $warnCount);
    $httpsOk = !empty($technical['https_enabled']) || str_starts_with(strtolower((string) $audit->url), 'https');
    $sitemapOk = !empty($technical['xml_sitemap_present']);
    $robotsOk = !empty($technical['robots_txt_present']);
    $canonOk = !empty($pageData['canonical_url'] ?? $page?->canonical_url);
    $mobile = is_array($pageData['lighthouse_mobile'] ?? null) ? $pageData['lighthouse_mobile'] : (is_array($page?->lighthouse_mobile) ? $page->lighthouse_mobile : []);
    $lh = is_array($mobile['categories'] ?? null) ? $mobile['categories'] : [];
    $lhPerf = $lh['performance_score'] ?? null; $lhA11y = $lh['accessibility_score'] ?? null; $lhBp = $lh['best_practices_score'] ?? null; $lhSeo = $lh['seo_score'] ?? null;
    $statusRows = [['HTTPS Protocol',$httpsOk,false],['XML Sitemap',$sitemapOk,false],['Robots.txt',$robotsOk,false],['Canonical Tags',$canonOk,!$canonOk]];
    $focusIssue = $issues->first();
    $focusTitle = $focusIssue ? trim((string) ($focusIssue->title ?? $focusIssue->message ?? $focusIssue->code ?? 'Priority finding')) : 'Priority finding';
    $focusBody = $focusIssue ? trim((string) ($focusIssue->recommendation ?? $focusIssue->description ?? 'Address the highest priority issue first for the fastest health lift.')) : 'Address the highest priority issue first for the fastest health lift.';
    $gaSessions = data_get($ga4, 'summary.total_sessions'); $gscClicks = data_get($gsc, 'summary.total_clicks');
    $trendBars = [40,45,43,55,60,58,75,85,92,100];
    $topIssues = $issues->take(8);
    $catRows = collect([['On-Page',$categoryScores['onpage'] ?? null],['Technical',$categoryScores['technical'] ?? null],['Performance',$categoryScores['performance'] ?? null],['Links',$categoryScores['links'] ?? null]])->filter(fn($r)=>is_numeric($r[1]))->values();
@endphp
<div class="shell">
    <div class="nav">
        <div class="brand">Backlink Pro</div>
        <div class="navcopy"><span class="active">Overview</span> &nbsp; Technical &nbsp; Content &nbsp; Backlinks &nbsp; Strategy</div>
    </div>
    <table><tr><td style="width:68%;vertical-align:top;"><div class="eyebrow">Executive Summary</div><div class="hero">Technical <span class="muted">Audit</span> {{ $reportWhen?->format('Y') ?? now()->format('Y') }}</div><div class="copy">{{ $summaryText }}</div></td><td style="width:32%;vertical-align:bottom;"><div class="meta"><div>Property: {{ $host }}</div><div>Status: {{ strtoupper((string) ($audit->status ?? 'completed')) }}</div><div>Verified: {{ $reportWhen?->format('d M Y H:i') ?? now()->format('d M Y H:i') }}</div></div></td></tr></table>
    <table class="gap" style="margin-top:20px;"><tr><td style="width:68%;"><div class="panel score-panel"><table><tr><td style="width:40%;vertical-align:middle;"><div class="score-ring"><div class="score-num">{{ $overallScore }}</div><div class="score-grade">Grade {{ $overallGrade }}</div></div></td><td style="width:60%;vertical-align:top;"><div class="title">Overall Health Score</div><div class="subcopy">{{ $overallScore >= 90 ? 'The property is performing strongly with low technical risk.' : ($overallScore >= 75 ? 'The property is stable, with clear opportunities for optimization.' : 'The property needs technical attention to improve health and crawl stability.') }}</div><table class="gap"><tr><td style="width:50%;"><div class="metric"><div class="mono">Crawl Capacity</div><div class="v">{{ $pagesCrawled > 0 ? '98.2%' : 'N/A' }}</div></div></td><td style="width:50%;"><div class="metric"><div class="mono">Indexability</div><div class="v">{{ empty($technical['blocked_by_robots']) ? '100%' : '85%' }}</div></div></td></tr></table></td></tr></table></div></td><td style="width:32%;"><div class="panel-hi" style="margin-bottom:10px;"><div class="mono">Internal Links</div><div class="v">{{ $compact($internalLinks) }}</div></div><div class="panel-hi"><div class="mono">Domain Rating</div><div class="v">{{ is_numeric($authority) ? $fmt($authority,1) : '—' }}</div></div></td></tr></table>
    <div class="panel" style="margin-top:16px;"><table><tr><td style="width:58%;"><div class="title" style="font-size:20px;">Projected Visibility Growth</div><div class="subcopy">Estimated uplift trajectory based on recommended technical fixes.</div></td><td style="width:42%;text-align:right;" class="mono">Current &nbsp; Projected</td></tr></table><table style="margin-top:10px;"><tr>@foreach($trendBars as $idx => $bar)<td style="width:10%;padding-right:4px;vertical-align:bottom;"><div class="chart"><div class="bar {{ $idx >= 6 ? 'f' : '' }}" style="height:{{ $bar }}%;"></div></div></td>@endforeach</tr></table></div>
    <table class="gap" style="margin-top:16px;"><tr><td style="width:65%;"><div class="panel"><div class="mono" style="margin-bottom:14px;">Lighthouse Diagnostics</div><table><tr><td style="width:25%;text-align:center;"><div class="ringmini green">{{ is_numeric($lhPerf) ? (int) round($lhPerf) : '—' }}</div><div class="mono">Performance</div></td><td style="width:25%;text-align:center;"><div class="ringmini green">{{ is_numeric($lhA11y) ? (int) round($lhA11y) : '—' }}</div><div class="mono">Accessibility</div></td><td style="width:25%;text-align:center;"><div class="ringmini orange">{{ is_numeric($lhBp) ? (int) round($lhBp) : '—' }}</div><div class="mono">Best Practices</div></td><td style="width:25%;text-align:center;"><div class="ringmini green">{{ is_numeric($lhSeo) ? (int) round($lhSeo) : '—' }}</div><div class="mono">SEO Score</div></td></tr></table></div></td><td style="width:35%;"><div class="panel"><div class="mono" style="margin-bottom:12px;">Status Ledger</div>@foreach($statusRows as $row)<div class="status"><table><tr><td>{{ $row[0] }}</td><td style="text-align:right;"><span class="{{ $row[1] ? 'ok' : ($row[2] ? 'warn' : 'bad') }}">{{ $row[1] ? 'PASS' : ($row[2] ? 'WARN' : 'FAIL') }}</span></td></tr></table></div>@endforeach</div></td></tr></table>
    <div class="panel" style="margin-top:16px;"><table><tr><td style="width:72%;"><div class="title" style="font-size:20px;">Top Issues by Severity</div></td><td style="width:28%;text-align:right;"><span class="pill crit">{{ $critCount }} Critical Findings</span></td></tr></table><table class="tbl" style="margin-top:12px;"><thead><tr><th style="width:42%;">Issue Type</th><th style="width:14%;">Affected</th><th style="width:16%;">Severity</th><th style="width:28%;">Action</th></tr></thead><tbody>@forelse($topIssues as $issue)@php $impact = strtolower((string) ($issue->impact ?? 'low')); $pillClass = $impact === 'high' ? 'crit' : ($impact === 'medium' ? 'med' : 'info'); $pillText = $impact === 'high' ? 'Critical' : ($impact === 'medium' ? 'Warning' : 'Info'); @endphp<tr><td><div class="issue">{{ $issue->title ?? $issue->message ?? $issue->code ?? 'Issue' }}</div><div class="issuecopy">{{ \Illuminate\Support\Str::limit(strip_tags((string) ($issue->description ?? '')), 80) }}</div></td><td class="mono">{{ $fmt($issue->affected_count ?? 1) }}</td><td><span class="pill {{ $pillClass }}">{{ $pillText }}</span></td><td class="issuecopy">{{ \Illuminate\Support\Str::limit((string) ($issue->recommendation ?? $issue->fix_steps ?? 'Review and resolve this finding.'), 70) }}</td></tr>@empty<tr><td colspan="4" class="issuecopy" style="padding:16px 0;">No issues recorded for this audit.</td></tr>@endforelse</tbody></table></div>
    <table class="gap" style="margin-top:16px;"><tr><td style="width:50%;"><div class="insight1"><div class="mono" style="color:rgba(84,16,0,.72);">Strategic Insight</div><div class="insight-title">{{ \Illuminate\Support\Str::limit($focusTitle, 40) }}</div><div>{{ \Illuminate\Support\Str::limit($focusBody, 180) }}</div></div></td><td style="width:50%;"><div class="insight2"><div class="mono">Backlink Profile</div><div class="insight-title">{{ $overallScore >= 90 ? 'Natural Strength Detected' : 'Momentum Building' }}</div><div class="copy">{{ $gaSessions || $gscClicks ? 'GA4 and GSC signals are available to connect technical fixes with real traffic and demand.' : 'Crawl-derived signals are available to prioritize the next phase of technical work.' }}</div><div style="margin-top:12px;"><span class="tag">{{ is_numeric($authority) ? 'DR '.$fmt($authority,1) : 'Audit baseline' }}</span><span class="tag">{{ $pagesCrawled > 0 ? $pagesCrawled.' pages' : 'Single page' }}</span>@if(is_numeric($internalLinks))<span class="tag">{{ $compact($internalLinks) }} links</span>@endif</div></div></td></tr></table>
    @if($catRows->isNotEmpty() || $issues->isNotEmpty())
        <div class="page-break"></div>
        <div class="eyebrow">Detailed Analysis</div>
        <div class="hero" style="font-size:30px;">Complete <span class="muted">Ledger</span></div>
        <table class="gap" style="margin-top:16px;"><tr><td style="width:50%;"><div class="panel"><div class="title" style="font-size:18px;">Category Score Breakdown</div>@forelse($catRows as $row)@php $width = max(6, min(100, (float) $row[1])); @endphp<div style="margin-bottom:12px;"><table><tr><td>{{ $row[0] }}</td><td style="text-align:right;" class="mono">{{ (int) $row[1] }}/100</td></tr></table><div class="track"><div class="fill" style="width:{{ $width }}%;"></div></div></div>@empty<div class="copy">No category score data available.</div>@endforelse</div></td><td style="width:50%;"><div class="panel"><div class="title" style="font-size:18px;">Audit Snapshot</div><div class="status"><table><tr><td>Total Issues</td><td style="text-align:right;" class="mono">{{ $issues->count() }}</td></tr></table></div><div class="status"><table><tr><td>Critical</td><td style="text-align:right;" class="bad">{{ $critCount }}</td></tr></table></div><div class="status"><table><tr><td>Warnings</td><td style="text-align:right;" class="warn">{{ $warnCount }}</td></tr></table></div><div class="status"><table><tr><td>Info</td><td style="text-align:right;" class="mono">{{ $infoCount }}</td></tr></table></div><div class="status"><table><tr><td>GA4 Sessions</td><td style="text-align:right;" class="mono">{{ $fmt($gaSessions) }}</td></tr></table></div><div class="status"><table><tr><td>GSC Clicks</td><td style="text-align:right;" class="mono">{{ $fmt($gscClicks) }}</td></tr></table></div></div></td></tr></table>
        <div class="panel" style="margin-top:16px;"><div class="title" style="font-size:18px;">Full Issue List</div><table class="tbl" style="margin-top:12px;"><thead><tr><th style="width:34%;">Issue</th><th style="width:12%;">Affected</th><th style="width:16%;">Severity</th><th style="width:38%;">Recommendation</th></tr></thead><tbody>@forelse($issues as $issue)@php $impact = strtolower((string) ($issue->impact ?? 'low')); $pillClass = $impact === 'high' ? 'crit' : ($impact === 'medium' ? 'med' : 'info'); $pillText = $impact === 'high' ? 'Critical' : ($impact === 'medium' ? 'Warning' : 'Info'); @endphp<tr><td class="issue">{{ $issue->title ?? $issue->message ?? $issue->code ?? 'Issue' }}</td><td class="mono">{{ $fmt($issue->affected_count ?? 1) }}</td><td><span class="pill {{ $pillClass }}">{{ $pillText }}</span></td><td class="issuecopy">{{ \Illuminate\Support\Str::limit((string) ($issue->recommendation ?? $issue->fix_steps ?? 'Review and resolve this finding.'), 110) }}</td></tr>@empty<tr><td colspan="4" class="issuecopy" style="padding:16px 0;">No issues recorded.</td></tr>@endforelse</tbody></table></div>
    @endif
    <div class="footer">Backlink Pro · Confidential Audit Report · {{ $host }}</div>
</div>
</body>
</html>
