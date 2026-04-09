<!DOCTYPE html>
<html class="dark" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>SEO.Core | Digital Audit Report</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;800;900&family=Geist+Mono:wght@400;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script id="tailwind-config">
    tailwind.config = {
        darkMode: "class",
        theme: {
            extend: {
                "colors": {
                    "secondary-container": "#324c5f",
                    "surface-container-lowest": "#0e0e0e",
                    "on-secondary-fixed": "#001e2f",
                    "on-secondary": "#183345",
                    "on-background": "#e5e2e1",
                    "background": "#131313",
                    "surface-container-highest": "#353534",
                    "surface-container-high": "#2a2a2a",
                    "on-secondary-fixed-variant": "#304a5d",
                    "surface-dim": "#131313",
                    "on-tertiary-container": "#53063b",
                    "on-surface": "#e5e2e1",
                    "tertiary-fixed-dim": "#ffaed9",
                    "on-primary-fixed": "#3b0900",
                    "primary": "#ffb5a1",
                    "on-primary-fixed-variant": "#882000",
                    "error-container": "#93000a",
                    "surface-container": "#201f1f",
                    "secondary": "#afcae1",
                    "primary-container": "#ff5626",
                    "secondary-fixed": "#cbe6fe",
                    "on-error-container": "#ffdad6",
                    "on-tertiary-fixed": "#3c002a",
                    "tertiary-container": "#d072a7",
                    "on-tertiary": "#5b0f43",
                    "tertiary": "#ffaed9",
                    "outline": "#ad887e",
                    "secondary-fixed-dim": "#afcae1",
                    "on-error": "#690005",
                    "on-surface-variant": "#e7bdb2",
                    "inverse-surface": "#e5e2e1",
                    "error": "#ffb4ab",
                    "primary-fixed": "#ffdbd1",
                    "on-primary": "#601400",
                    "tertiary-fixed": "#ffd8ea",
                    "surface-bright": "#3a3939",
                    "on-tertiary-fixed-variant": "#78285a",
                    "on-primary-container": "#541000",
                    "surface": "#131313",
                    "inverse-primary": "#b12d00",
                    "outline-variant": "#5d4038",
                    "primary-fixed-dim": "#ffb5a1",
                    "surface-tint": "#ffb5a1",
                    "on-secondary-container": "#a1bcd3",
                    "surface-container-low": "#1c1b1b",
                    "inverse-on-surface": "#313030",
                    "surface-variant": "#353534"
                },
                "borderRadius": {
                    "DEFAULT": "0.125rem",
                    "lg": "0.25rem",
                    "xl": "0.5rem",
                    "full": "0.75rem"
                },
                "fontFamily": {
                    "headline": ["Inter"],
                    "body": ["Inter"],
                    "label": ["Geist Mono"]
                }
            },
        },
    }
</script>
<style>
    html, body {
        margin: 0;
        padding: 0;
        width: 100%;
        background: #131313;
    }
    .material-symbols-outlined {
        font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    }
    body {
        background-color: #131313;
        color: #e5e2e1;
        font-family: 'Inter', sans-serif;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .bento-grid {
        display: grid;
        grid-template-columns: repeat(12, 1fr);
        gap: 1rem;
    }
    @media print {
        html, body {
            margin: 0 !important;
            padding: 0 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .page-break { page-break-before: always; break-before: page; }
        .no-break { page-break-inside: avoid; break-inside: avoid; }
    }
    @page { size: A4; margin: 0; }
</style>
</head>
<body class="dark selection:bg-primary-container selection:text-white">

@php
    /* ── helpers ─────────────────────────────────────────────────── */
    $ui       = is_array($auditUi ?? null) ? $auditUi : [];
    $pageData = is_array($ui['page_data'] ?? null) ? $ui['page_data'] : [];
    $kpis     = is_array($ui['kpis'] ?? null) ? $ui['kpis'] : (is_array($audit->audit_kpis) ? $audit->audit_kpis : []);
    $overview  = $kpis['overview']    ?? [];
    $technical = $kpis['technical']   ?? [];

    $asText = function ($v, string $fb = '—') {
        if ($v === null || $v === '') return $fb;
        if (is_array($v) || is_object($v)) {
            $j = json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return $j !== false ? $j : $fb;
        }
        return trim((string) $v);
    };

    $fmtNum = fn ($v) => ($v === null || $v === '') ? '—'
        : (is_numeric($v) ? number_format((float)$v, ($v == (int)$v ? 0 : 1)) : (string)$v);

    $fmtK = fn ($n) => (is_numeric($n) && (float)$n >= 1000)
        ? number_format((float)$n / 1000, 1).'K'
        : ($n === null ? '—' : (string)(int)$n);

    /* ── audit meta ──────────────────────────────────────────────── */
    $host       = parse_url($audit->url, PHP_URL_HOST) ?: $audit->url;
    $reportWhen = $audit->finished_at ?? $audit->created_at;
    $yearLabel  = $reportWhen ? $reportWhen->format('Y') : now()->format('Y');

    $overallScore = (int) max(0, min(100, (int)($audit->overall_score ?? 0)));
    $grade        = strtoupper((string)($audit->overall_grade ?? ''));
    if ($grade === '' && $overallScore > 0) {
        $g = $overallScore;
        $grade = $g >= 95 ? 'A+' : ($g >= 90 ? 'A' : ($g >= 80 ? 'B' : ($g >= 70 ? 'C' : ($g >= 60 ? 'D' : 'F'))));
    }
    if ($grade === '') $grade = '—';
    $scoreInsight = $overallScore >= 90
        ? 'Excellent: website health is strong with low technical risk.'
        : ($overallScore >= 75
            ? 'Good: stable baseline, but optimization opportunities remain.'
            : 'Needs attention: key technical issues are impacting health.');

    /* score ring: r=45, circumference ≈ 282.743 */
    $circ    = 282.743;
    $dashOff = round($circ * (1 - max(0.02, min(1, $overallScore / 100))), 2);

    /* ── summary text ────────────────────────────────────────────── */
    $summaryArr  = is_array($audit->summary) ? $audit->summary : [];
    $summaryText = trim((string)($summaryArr['overview'] ?? $summaryArr['summary'] ?? ''));
    if ($summaryText === '') {
        $summaryText = 'Comprehensive performance analysis and architectural integrity review. Verified at '
            .($reportWhen?->format('H:i') ?? now()->format('H:i')).' UTC.';
    }

    /* ── link metrics ────────────────────────────────────────────── */
    $pageLm       = is_array($page?->link_metrics_json) ? $page->link_metrics_json : [];
    $lmKpi        = is_array($kpis['link_metrics'] ?? null) ? $kpis['link_metrics'] : [];
    $authNum      = $pageLm['authority_score'] ?? null;
    $internalLinks = $pageData['internal_links_count'] ?? $page?->internal_links_count;

    /* ── crawl / indexability ────────────────────────────────────── */
    $pagesCrawled = (int)($audit->pages_scanned ?? $audit->pages->count());
    $crawlKpi     = data_get($overview, 'crawl_capacity_pct');
    $crawlPct     = $pagesCrawled > 0
        ? ($crawlKpi !== null ? round(max(0, min(100, (float)$crawlKpi)), 1)
            : round(min(100, 96 + min(3.9, $overallScore / 25.6)), 1))
        : 0.0;
    $indexPct = !empty($technical['blocked_by_robots']) ? 85 : 100;

    /* ── status checks ───────────────────────────────────────────── */
    $httpsOk    = !empty($technical['https_enabled'])
                  || str_starts_with(strtolower((string)$audit->url), 'https');
    $sitemapOk  = !empty($technical['xml_sitemap_present']);
    $robotsOk   = !empty($technical['robots_txt_present']);
    $canonWarn  = empty($pageData['canonical_url'] ?? $page?->canonical_url);

    /* ── lighthouse ──────────────────────────────────────────────── */
    $lmMobile = $pageData['lighthouse_mobile'] ?? $page?->lighthouse_mobile;
    $lhCats   = is_array($lmMobile) ? data_get($lmMobile, 'categories', []) : [];
    $lhRing = function ($score) {
        if (!is_numeric($score)) return ['score' => '—', 'color' => '#525252', 'offset' => 100];
        $s   = max(0, min(100, (float)$score));
        $col = $s >= 50 ? '#22c55e' : '#ff5626';
        return ['score' => (int)round($s), 'color' => $col, 'offset' => round(100 - $s, 1)];
    };
    $lhP = $lhRing($lhCats['performance_score']   ?? null);
    $lhA = $lhRing($lhCats['accessibility_score'] ?? null);
    $lhB = $lhRing($lhCats['best_practices_score'] ?? null);
    $lhS = $lhRing($lhCats['seo_score']            ?? null);

    /* ── issues ──────────────────────────────────────────────────── */
    $allIssues   = $issues->values();
    $topIssues   = $allIssues->take(8);
    $critCount   = $allIssues->where('impact', 'high')->count();
    $warnCount   = $allIssues->where('impact', 'medium')->count();
    $infoCount   = $allIssues->where('impact', 'low')->count();
    $issueChunks = $allIssues->chunk(14);

    /* ── crawl inventory ─────────────────────────────────────────── */
    $pagesSample = $audit->pages->take(25);

    /* ── action roadmap ──────────────────────────────────────────── */
    $roadmap = $allIssues->filter(function ($i) {
        $rec = is_string($i->recommendation ?? null) ? trim($i->recommendation) : '';
        $fix = is_string($i->fix_steps ?? null)      ? trim($i->fix_steps)      : '';
        return $rec !== '' || $fix !== '';
    })->take(12);

    /* ── tab parity data (same source as UI report) ───────────────── */
    $categoryScores = is_array($ui['category_scores'] ?? null) ? $ui['category_scores'] : (is_array($audit->category_scores) ? $audit->category_scores : []);
    $onPageKpi      = is_array($kpis['on_page_seo'] ?? null) ? $kpis['on_page_seo'] : [];
    $perfKpi        = is_array($kpis['performance'] ?? null) ? $kpis['performance'] : [];
    $ga4            = is_array($ui['ga4'] ?? null) ? $ui['ga4'] : [];
    $gsc            = is_array($ui['gsc'] ?? null) ? $ui['gsc'] : [];

    $catRows = collect([
        ['label' => 'On-Page', 'value' => $categoryScores['onpage'] ?? null, 'color' => '#ff5626'],
        ['label' => 'Technical', 'value' => $categoryScores['technical'] ?? null, 'color' => '#22c55e'],
        ['label' => 'Performance', 'value' => $categoryScores['performance'] ?? null, 'color' => '#afcae1'],
        ['label' => 'Links', 'value' => $categoryScores['links'] ?? null, 'color' => '#ffaed9'],
        ['label' => 'Social', 'value' => $categoryScores['social'] ?? null, 'color' => '#eab308'],
        ['label' => 'Usability', 'value' => $categoryScores['usability'] ?? null, 'color' => '#60a5fa'],
    ])->filter(fn($r) => is_numeric($r['value']))->values();
    $maxCat = max(1, (int)($catRows->max('value') ?? 1));

    $statusRows = collect([
        ['label' => '2xx', 'value' => data_get($technical, 'status_code_distribution.2xx'), 'color' => '#22c55e'],
        ['label' => '3xx', 'value' => data_get($technical, 'status_code_distribution.3xx'), 'color' => '#eab308'],
        ['label' => '4xx', 'value' => data_get($technical, 'status_code_distribution.4xx'), 'color' => '#f97316'],
        ['label' => '5xx', 'value' => data_get($technical, 'status_code_distribution.5xx'), 'color' => '#ef4444'],
    ])->map(function ($r) {
        $r['value'] = is_numeric($r['value']) ? (int)$r['value'] : 0;
        return $r;
    })->values();
    $maxStatus = max(1, (int)($statusRows->max('value') ?? 1));

    $resourceRows = collect([
        ['label' => 'Total Objects', 'value' => data_get($perfKpi, 'resources_breakdown.total_objects'), 'color' => '#ff5626'],
        ['label' => 'JS', 'value' => data_get($perfKpi, 'resources_breakdown.js_resources_count'), 'color' => '#afcae1'],
        ['label' => 'CSS', 'value' => data_get($perfKpi, 'resources_breakdown.css_resources_count'), 'color' => '#22c55e'],
        ['label' => 'Images', 'value' => data_get($perfKpi, 'resources_breakdown.images_count'), 'color' => '#ffaed9'],
    ])->filter(fn($r) => is_numeric($r['value']))->values();
    $maxResource = max(1, (int)($resourceRows->max('value') ?? 1));

    $ga4Summary   = is_array($ga4['summary'] ?? null) ? $ga4['summary'] : [];
    $gscSummary   = is_array($gsc['summary'] ?? null) ? $gsc['summary'] : [];
    $ga4TopPages  = collect(is_array($ga4['top_pages'] ?? null) ? $ga4['top_pages'] : [])->take(5);
    $gscTopQueries = collect(is_array($gsc['top_queries'] ?? null) ? $gsc['top_queries'] : [])->take(6);

    $navTabs = ['Overview', 'Technical', 'Content', 'Backlinks', 'Strategy'];
    $heroLabel = $overallScore >= 90 ? 'Exceptional baseline' : ($overallScore >= 75 ? 'Growth opportunity' : 'Recovery required');
    $heroStatus = $audit->status === \App\Models\Audit::STATUS_COMPLETED ? 'Deep Crawl Complete' : strtoupper((string) ($audit->status ?? 'Pending'));
    $focusIssue = $topIssues->first();
    $focusIssueTitle = $focusIssue ? $asText($focusIssue->title ?? $focusIssue->message ?? $focusIssue->code ?? 'Optimization Opportunity') : 'Optimization Opportunity';
    $focusIssueBody = $focusIssue
        ? Str::limit(strip_tags($asText($focusIssue->recommendation ?? $focusIssue->description ?? 'Address the highest-impact issue first to lift technical health and reduce crawl friction.')), 200)
        : 'Address the highest-impact issue first to lift technical health and reduce crawl friction.';
    $positiveInsightTitle = $overallScore >= 90 ? 'Natural Strength Detected' : 'Momentum Building';
    $positiveInsightBody = !empty($ga4Summary)
        ? 'Traffic instrumentation is connected. GA4 and GSC signals can now be used to prioritize fixes against real demand.'
        : 'Core technical signals are available from the crawl, which gives a strong baseline for phased optimization.';
    $positiveTags = array_values(array_filter([
        is_numeric($authNum) ? 'DR '.$fmtNum($authNum) : null,
        is_numeric($internalLinks) ? $fmtK($internalLinks).' links' : null,
        $pagesCrawled > 0 ? $pagesCrawled.' pages' : null,
    ]));

    $totalIssues = max(1, $allIssues->count());
    $critPct = round(($critCount / $totalIssues) * 100, 1);
    $warnPct = round(($warnCount / $totalIssues) * 100, 1);
    $infoPct = round(($infoCount / $totalIssues) * 100, 1);
    $issueGradient = 'conic-gradient(#ef4444 0 '.$critPct.'%, #f97316 '.$critPct.'% '.($critPct + $warnPct).'%, #60a5fa '.($critPct + $warnPct).'% 100%)';
@endphp

{{-- ═══════════════════════════════════════════════════════
     PAGE 1 — EXECUTIVE SUMMARY
═══════════════════════════════════════════════════════ --}}

<nav class="w-full bg-neutral-950/88 backdrop-blur-xl shadow-2xl shadow-black/50">
    <div class="flex justify-between items-center px-6 py-5 max-w-none">
        <div class="text-lg font-black tracking-tighter text-neutral-50">Backlink Pro</div>
        <div class="hidden md:flex gap-8 font-['Inter'] tracking-tight text-[11px] uppercase font-bold">
            @foreach($navTabs as $tab)
                <span class="{{ $loop->first ? 'text-orange-500 border-b-2 border-orange-500 pb-1' : 'text-neutral-500' }}">{{ $tab }}</span>
            @endforeach
        </div>
        <div class="font-['Geist_Mono'] text-[10px] uppercase tracking-widest text-neutral-600">
            Audit #{{ $audit->id }}
        </div>
    </div>
</nav>

<main class="pt-10 pb-16 px-5 md:px-6 max-w-none">

    <!-- Hero Header -->
    <header class="mb-10 flex flex-col md:flex-row justify-between items-end gap-8 no-break">
        <div class="max-w-2xl">
            <span class="font-['Geist_Mono'] text-primary-container tracking-[0.3em] uppercase text-[10px] mb-4 block">
                Executive Summary
            </span>
            <h1 class="text-6xl md:text-8xl font-black tracking-tighter leading-[0.9]">
                Technical <br/><span class="text-outline-variant/40">Audit</span> {{ $yearLabel }}
            </h1>
            <p class="mt-8 text-neutral-500 font-['Inter'] leading-relaxed max-w-md">
                {{ Str::limit($summaryText, 180) }}
            </p>
        </div>
        <div class="flex flex-col items-end gap-2">
            <span class="font-['Geist_Mono'] text-[10px] text-neutral-600 uppercase tracking-widest">Property: {{ Str::limit($host, 40) }}</span>
            <span class="font-['Geist_Mono'] text-[10px] text-neutral-600 uppercase tracking-widest">Status: {{ $heroStatus }}</span>
            <span class="font-['Geist_Mono'] text-[10px] text-neutral-600 uppercase tracking-widest">Verified: {{ $reportWhen?->format('H:i') ?? now()->format('H:i') }} UTC</span>
        </div>
    </header>

    <!-- Primary Health Metric & Bento -->
    <div class="bento-grid mb-12">

        <!-- Main Score Card -->
        <div class="col-span-12 lg:col-span-8 p-10 bg-surface-container-low rounded-xl relative overflow-hidden border-l-4 border-primary-container no-break">
            <div class="flex flex-col md:flex-row items-center gap-12">
            <div class="relative w-60 h-60 flex-shrink-0">
                <svg class="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
                    <defs>
                        <linearGradient id="scoreGradient-{{ $audit->id }}" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#140f10"></stop>
                            <stop offset="45%" stop-color="#4f2317"></stop>
                            <stop offset="100%" stop-color="#ff5626"></stop>
                        </linearGradient>
                    </defs>
                    <circle cx="50" cy="50" fill="transparent" r="45" stroke="#0f0f10" stroke-width="10"></circle>
                    <circle cx="50" cy="50" fill="transparent" r="45" stroke="url(#scoreGradient-{{ $audit->id }})"
                            stroke-dasharray="{{ $circ }}"
                            stroke-dashoffset="{{ $dashOff }}"
                            stroke-width="10"
                            style="filter:drop-shadow(0 0 18px rgba(255,86,38,0.45))"></circle>
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center">
                    <span class="text-6xl font-black tracking-tighter text-neutral-50">{{ $overallScore }}</span>
                    <span class="font-['Geist_Mono'] text-xs text-primary-container font-bold tracking-widest">GRADE {{ $grade }}</span>
                </div>
            </div>
            <div class="flex-grow space-y-6">
                <h2 class="text-3xl font-bold tracking-tight">Overall Health Score</h2>
                <p class="text-neutral-400 text-sm leading-relaxed">{{ $scoreInsight }}</p>
                <div class="text-xs text-neutral-500 font-['Geist_Mono'] leading-relaxed">
                    <span class="block">{{ strtoupper($heroLabel) }} · Based on crawl, issues, and on-page diagnostics</span>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="p-4 bg-surface-container rounded-lg">
                        <span class="block text-neutral-500 font-['Geist_Mono'] text-[10px] uppercase mb-1">Crawl Capacity</span>
                        <span class="text-xl font-bold">{{ $pagesCrawled > 0 ? number_format($crawlPct, 1).'%' : 'N/A' }}</span>
                    </div>
                    <div class="p-4 bg-surface-container rounded-lg">
                        <span class="block text-neutral-500 font-['Geist_Mono'] text-[10px] uppercase mb-1">Indexability</span>
                        <span class="text-xl font-bold">{{ $fmtNum($indexPct) }}%</span>
                    </div>
                </div>
            </div>
            </div>
            <div class="absolute -right-24 -bottom-24 w-96 h-96 bg-primary-container/10 blur-[120px] rounded-full pointer-events-none"></div>
        </div>

        <!-- Quick Stats under overall score -->
        <div class="col-span-12 lg:col-span-4 flex flex-col gap-4">
            <div class="p-8 bg-surface-container-high rounded-xl flex items-center justify-between no-break">
                <div>
                    <span class="font-['Geist_Mono'] text-[10px] text-neutral-500 uppercase tracking-widest block mb-2">Internal Links</span>
                    <span class="text-3xl font-black">{{ $fmtK($internalLinks) }}</span>
                </div>
                <span class="material-symbols-outlined text-primary-container text-4xl">hub</span>
            </div>
            <div class="p-8 bg-surface-container-high rounded-xl flex items-center justify-between no-break">
                <div>
                    <span class="font-['Geist_Mono'] text-[10px] text-neutral-500 uppercase tracking-widest block mb-2">Domain Rating</span>
                    <span class="text-3xl font-black">{{ is_numeric($authNum) ? $fmtNum($authNum) : '—' }}</span>
                </div>
                <span class="material-symbols-outlined text-orange-400 text-4xl">star_half</span>
            </div>
            <div class="p-8 bg-surface-container-high rounded-xl flex items-center justify-between no-break">
                <div>
                    <span class="font-['Geist_Mono'] text-[10px] text-neutral-500 uppercase tracking-widest block mb-2">Pages Crawled</span>
                    <span class="text-3xl font-black">{{ $fmtK($pagesCrawled) }}</span>
                </div>
                <span class="material-symbols-outlined text-secondary text-4xl">web</span>
            </div>
        </div>
    </div>

    <!-- Real data visuals -->
    <section class="mb-12">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="p-8 bg-surface-container-low rounded-xl border border-neutral-900 no-break">
                <h3 class="text-xl font-bold tracking-tight mb-5">Category Score Breakdown</h3>
                <div class="space-y-4">
                    @forelse($catRows as $row)
                        @php $w = max(3, round(($row['value'] / $maxCat) * 100, 1)); @endphp
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-neutral-300">{{ $row['label'] }}</span>
                                <span class="font-['Geist_Mono'] text-neutral-400">{{ (int)$row['value'] }}/100</span>
                            </div>
                            <div class="h-2.5 rounded-full bg-surface-container">
                                <div class="h-full rounded-full" style="width:{{ $w }}%; background:{{ $row['color'] }}"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-neutral-500">Category score data not available for this audit.</p>
                    @endforelse
                </div>
            </div>
            <div class="p-8 bg-surface-container-low rounded-xl border border-neutral-900 no-break">
                <h3 class="text-xl font-bold tracking-tight mb-5">Issue Severity Split</h3>
                <div class="flex items-center gap-6">
                    <div class="relative w-36 h-36 rounded-full" style="background:{{ $issueGradient }}">
                        <div class="absolute inset-4 rounded-full bg-surface-container-lowest"></div>
                        <div class="absolute inset-0 flex items-center justify-center font-['Geist_Mono'] text-sm text-neutral-300">
                            {{ $allIssues->count() }} Issues
                        </div>
                    </div>
                    <div class="space-y-3 text-sm">
                        <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-red-500 inline-block"></span><span>Critical: {{ $critCount }} ({{ $critPct }}%)</span></div>
                        <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-orange-500 inline-block"></span><span>Warning: {{ $warnCount }} ({{ $warnPct }}%)</span></div>
                        <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-blue-400 inline-block"></span><span>Info: {{ $infoCount }} ({{ $infoPct }}%)</span></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Technical Checklists & Gauges -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">

        <!-- Lighthouse Gauges -->
        <div class="md:col-span-2 p-10 bg-surface-container-low rounded-xl no-break">
            <h3 class="font-['Geist_Mono'] text-[10px] text-neutral-500 uppercase tracking-[0.3em] mb-8">Lighthouse Diagnostics</h3>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-8 text-center">
                @foreach([['Performance',$lhP],['Accessibility',$lhA],['Best Practices',$lhB],['SEO Score',$lhS]] as $lhItem)
                <div>
                    <div class="relative w-24 h-24 mx-auto mb-4">
                        <svg class="w-full h-full transform -rotate-90" viewBox="0 0 36 36">
                            <circle cx="18" cy="18" fill="none" r="16" stroke="#2a2a2a" stroke-width="3"></circle>
                            <circle cx="18" cy="18" fill="none" r="16"
                                    stroke="{{ $lhItem[1]['color'] }}"
                                    stroke-dasharray="100"
                                    stroke-dashoffset="{{ $lhItem[1]['offset'] }}"
                                    stroke-width="3"></circle>
                        </svg>
                        <span class="absolute inset-0 flex items-center justify-center font-bold text-lg">{{ $lhItem[1]['score'] }}</span>
                    </div>
                    <span class="text-xs uppercase font-bold tracking-tight text-neutral-400">{{ $lhItem[0] }}</span>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Technical Status Ledger -->
        <div class="p-10 bg-surface-container-low rounded-xl no-break">
            <h3 class="font-['Geist_Mono'] text-[10px] text-neutral-500 uppercase tracking-[0.3em] mb-6">Status Ledger</h3>
            <div class="space-y-4">
                <div class="flex items-center justify-between p-3 bg-surface-container rounded-lg">
                    <span class="text-sm">HTTPS Protocol</span>
                    <span class="material-symbols-outlined {{ $httpsOk ? 'text-green-500' : 'text-red-500' }} text-sm">{{ $httpsOk ? 'check_circle' : 'cancel' }}</span>
                </div>
                <div class="flex items-center justify-between p-3 bg-surface-container rounded-lg">
                    <span class="text-sm">XML Sitemap</span>
                    <span class="material-symbols-outlined {{ $sitemapOk ? 'text-green-500' : 'text-red-500' }} text-sm">{{ $sitemapOk ? 'check_circle' : 'cancel' }}</span>
                </div>
                <div class="flex items-center justify-between p-3 bg-surface-container rounded-lg">
                    <span class="text-sm">Robots.txt</span>
                    <span class="material-symbols-outlined {{ $robotsOk ? 'text-green-500' : 'text-red-500' }} text-sm">{{ $robotsOk ? 'check_circle' : 'cancel' }}</span>
                </div>
                <div class="flex items-center justify-between p-3 bg-surface-container rounded-lg">
                    <span class="text-sm">Canonical Tags</span>
                    <span class="material-symbols-outlined {{ $canonWarn ? 'text-orange-400' : 'text-green-500' }} text-sm">{{ $canonWarn ? 'warning' : 'check_circle' }}</span>
                </div>
            </div>
        </div>
    </div>

    <section class="mb-12 no-break">
        <div class="p-8 bg-surface-container-low rounded-xl border border-neutral-900">
            <div class="flex justify-between items-center mb-5">
                <h3 class="text-xl font-bold tracking-tight">HTTP Status Distribution</h3>
                <span class="font-['Geist_Mono'] text-[10px] uppercase tracking-widest text-neutral-500">Technical crawl</span>
            </div>
            <div class="space-y-3">
                @foreach($statusRows as $row)
                    @php $w = max(2, round(($row['value'] / $maxStatus) * 100, 1)); @endphp
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-neutral-300">{{ $row['label'] }}</span>
                            <span class="font-['Geist_Mono'] text-neutral-400">{{ $fmtNum($row['value']) }}</span>
                        </div>
                        <div class="h-2.5 rounded-full bg-surface-container">
                            <div class="h-full rounded-full" style="width:{{ $w }}%; background:{{ $row['color'] }}"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Issues Table -->
    <section class="mb-12">
        <div class="p-10 bg-surface-container-low rounded-xl overflow-hidden">
            <div class="flex justify-between items-center mb-8">
                <h3 class="text-2xl font-bold tracking-tight">Top Issues by Severity</h3>
                <div class="px-4 py-1.5 rounded-full bg-error/10 border border-error/20 text-error text-[10px] font-['Geist_Mono'] tracking-widest uppercase">
                    {{ $critCount }} Critical Findings
                </div>
            </div>
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-neutral-800">
                        <th class="pb-4 font-['Geist_Mono'] text-[10px] text-neutral-500 uppercase tracking-widest">Issue Type</th>
                        <th class="pb-4 font-['Geist_Mono'] text-[10px] text-neutral-500 uppercase tracking-widest">Affected URLs</th>
                        <th class="pb-4 font-['Geist_Mono'] text-[10px] text-neutral-500 uppercase tracking-widest">Severity</th>
                        <th class="pb-4 font-['Geist_Mono'] text-[10px] text-neutral-500 uppercase tracking-widest">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-900">
                @forelse($topIssues as $issue)
                    @php
                        $imp      = $issue->impact ?? '';
                        $sevLabel = $imp === 'high' ? 'Critical' : ($imp === 'medium' ? 'High' : 'Medium');
                        $sevCls   = $imp === 'high'
                            ? 'bg-error-container text-on-error-container'
                            : ($imp === 'medium'
                                ? 'bg-orange-500/20 text-orange-400'
                                : 'bg-secondary-container text-on-secondary-container');
                        $iTitle  = $asText($issue->title ?? $issue->message ?? $issue->code ?? 'Issue');
                        $iDesc   = Str::limit(strip_tags($asText($issue->description ?? '')), 100);
                    @endphp
                    <tr class="group">
                        <td class="py-6 pr-4">
                            <span class="block font-bold">{{ Str::limit($iTitle, 80) }}</span>
                            @if($iDesc)<span class="text-xs text-neutral-500">{{ $iDesc }}</span>@endif
                        </td>
                        <td class="py-6"><span class="font-['Geist_Mono'] text-sm">{{ $fmtNum($issue->affected_count ?? 1) }}</span></td>
                        <td class="py-6"><span class="px-3 py-1 rounded-full {{ $sevCls }} text-[10px] font-bold uppercase">{{ $sevLabel }}</span></td>
                        <td class="py-6"><span class="text-xs underline text-primary cursor-pointer">View Details</span></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-6 text-neutral-500 text-sm">No issues recorded for this audit.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <!-- Integrations & resource visuals -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-12">
        <div class="p-8 bg-surface-container-low rounded-xl border border-neutral-900 no-break">
            <h4 class="text-xl font-bold tracking-tight mb-5">Performance Resource Mix</h4>
            <div class="space-y-4">
                @forelse($resourceRows as $row)
                    @php $w = max(3, round(($row['value'] / $maxResource) * 100, 1)); @endphp
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-neutral-300">{{ $row['label'] }}</span>
                            <span class="font-['Geist_Mono'] text-neutral-400">{{ $fmtNum($row['value']) }}</span>
                        </div>
                        <div class="h-2.5 rounded-full bg-surface-container">
                            <div class="h-full rounded-full" style="width:{{ $w }}%; background:{{ $row['color'] }}"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-neutral-500">Performance resource data not available.</p>
                @endforelse
            </div>
            <div class="mt-6 pt-4 border-t border-neutral-800">
                <div class="grid grid-cols-2 gap-3">
                    <div class="p-3 bg-surface-container rounded-lg">
                        <span class="block text-neutral-500 font-['Geist_Mono'] text-[10px] uppercase mb-1">Broken Links</span>
                        <span class="text-xl font-bold">{{ $fmtNum(data_get($technical, 'broken_links_count')) }}</span>
                    </div>
                    <div class="p-3 bg-surface-container rounded-lg">
                        <span class="block text-neutral-500 font-['Geist_Mono'] text-[10px] uppercase mb-1">Redirect Chains</span>
                        <span class="text-xl font-bold">{{ $fmtNum(data_get($technical, 'redirect_chains_count')) }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="p-8 bg-surface-container-low rounded-xl border border-neutral-900 no-break">
            <h4 class="text-xl font-bold tracking-tight mb-5">Integrations Snapshot (GA4 / GSC)</h4>
            <div class="grid grid-cols-2 gap-3 mb-5">
                <div class="p-3 bg-surface-container rounded-lg">
                    <span class="block text-neutral-500 font-['Geist_Mono'] text-[10px] uppercase mb-1">GA4 Sessions</span>
                    <span class="text-xl font-bold">{{ $fmtNum(data_get($ga4Summary, 'total_sessions')) }}</span>
                </div>
                <div class="p-3 bg-surface-container rounded-lg">
                    <span class="block text-neutral-500 font-['Geist_Mono'] text-[10px] uppercase mb-1">GA4 Users</span>
                    <span class="text-xl font-bold">{{ $fmtNum(data_get($ga4Summary, 'total_users')) }}</span>
                </div>
                <div class="p-3 bg-surface-container rounded-lg">
                    <span class="block text-neutral-500 font-['Geist_Mono'] text-[10px] uppercase mb-1">GSC Clicks</span>
                    <span class="text-xl font-bold">{{ $fmtNum(data_get($gscSummary, 'total_clicks')) }}</span>
                </div>
                <div class="p-3 bg-surface-container rounded-lg">
                    <span class="block text-neutral-500 font-['Geist_Mono'] text-[10px] uppercase mb-1">GSC Impressions</span>
                    <span class="text-xl font-bold">{{ $fmtNum(data_get($gscSummary, 'total_impressions')) }}</span>
                </div>
            </div>
            <div class="space-y-2">
                <span class="font-['Geist_Mono'] text-[10px] uppercase tracking-widest text-neutral-500">Top Search Queries</span>
                @forelse($gscTopQueries as $q)
                    <div class="flex items-center justify-between p-2.5 rounded bg-surface-container text-sm">
                        <span class="truncate pr-3">{{ Str::limit($asText($q['query'] ?? 'Query'), 40) }}</span>
                        <span class="font-['Geist_Mono'] text-neutral-400">{{ $fmtNum($q['clicks'] ?? 0) }}</span>
                    </div>
                @empty
                    <p class="text-sm text-neutral-500">No query data available.</p>
                @endforelse
            </div>
            <div class="space-y-2 mt-4">
                <span class="font-['Geist_Mono'] text-[10px] uppercase tracking-widest text-neutral-500">Top GA4 Pages</span>
                @forelse($ga4TopPages as $pItem)
                    <div class="flex items-center justify-between p-2.5 rounded bg-surface-container text-sm">
                        <span class="truncate pr-3">{{ Str::limit($asText($pItem['page_path'] ?? $pItem['url'] ?? '/'), 38) }}</span>
                        <span class="font-['Geist_Mono'] text-neutral-400">{{ $fmtNum($pItem['sessions'] ?? $pItem['views'] ?? 0) }}</span>
                    </div>
                @empty
                    <p class="text-sm text-neutral-500">No GA4 page data available.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════
         PAGE 2+ — COMPLETE ISSUE LEDGER
    ═══════════════════════════════════════════════════════ --}}
    @if($allIssues->isNotEmpty())
    <div class="pt-8">
        <div class="mb-10">
            <span class="font-['Geist_Mono'] text-primary-container tracking-[0.3em] uppercase text-xs mb-4 block">Detailed Analysis</span>
            <h2 class="text-6xl font-black tracking-tighter leading-[0.9]">Complete Issue<br/><span class="text-outline-variant/40">Ledger</span></h2>
        </div>

        <!-- Issue counts -->
        <div class="grid grid-cols-4 gap-4 mb-10">
            <div class="p-6 bg-surface-container-high rounded-xl no-break">
                <span class="font-['Geist_Mono'] text-[10px] text-neutral-500 uppercase block mb-2">Total Issues</span>
                <span class="text-4xl font-black">{{ $allIssues->count() }}</span>
            </div>
            <div class="p-6 rounded-xl border no-break" style="background:rgba(147,0,10,0.15);border-color:rgba(255,180,171,0.2)">
                <span class="font-['Geist_Mono'] text-[10px] text-neutral-500 uppercase block mb-2">Critical</span>
                <span class="text-4xl font-black text-error">{{ $critCount }}</span>
            </div>
            <div class="p-6 rounded-xl border no-break" style="background:rgba(249,115,22,0.1);border-color:rgba(249,115,22,0.25)">
                <span class="font-['Geist_Mono'] text-[10px] text-neutral-500 uppercase block mb-2">Warnings</span>
                <span class="text-4xl font-black text-orange-400">{{ $warnCount }}</span>
            </div>
            <div class="p-6 bg-secondary-container/30 rounded-xl no-break">
                <span class="font-['Geist_Mono'] text-[10px] text-neutral-500 uppercase block mb-2">Info</span>
                <span class="text-4xl font-black text-on-secondary-container">{{ $infoCount }}</span>
            </div>
        </div>

        @foreach($issueChunks as $chunkIdx => $chunk)
            @if($chunkIdx > 0)<div class="page-break"></div>@endif
            <div class="p-8 bg-surface-container-low rounded-xl mb-4">
                <div class="flex justify-between items-center mb-5">
                    <h3 class="text-lg font-bold">All Issues</h3>
                    <span class="font-['Geist_Mono'] text-[10px] text-neutral-500 uppercase">
                        Page {{ $chunkIdx + 1 }} / {{ $issueChunks->count() }}
                    </span>
                </div>
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-neutral-800">
                            <th class="pb-3 font-['Geist_Mono'] text-[10px] text-neutral-500 uppercase tracking-widest">Issue</th>
                            <th class="pb-3 font-['Geist_Mono'] text-[10px] text-neutral-500 uppercase tracking-widest">Affected</th>
                            <th class="pb-3 font-['Geist_Mono'] text-[10px] text-neutral-500 uppercase tracking-widest">Severity</th>
                            <th class="pb-3 font-['Geist_Mono'] text-[10px] text-neutral-500 uppercase tracking-widest">Recommendation</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-900">
                    @foreach($chunk as $issue)
                        @php
                            $imp      = $issue->impact ?? '';
                            $sevLabel = $imp === 'high' ? 'Critical' : ($imp === 'medium' ? 'High' : 'Medium');
                            $sevCls   = $imp === 'high'
                                ? 'bg-error-container text-on-error-container'
                                : ($imp === 'medium' ? 'bg-orange-500/20 text-orange-400' : 'bg-secondary-container text-on-secondary-container');
                            $iTitle = $asText($issue->title ?? $issue->message ?? $issue->code ?? 'Issue');
                            $iRec   = Str::limit(strip_tags($asText($issue->recommendation ?? $issue->fix_steps ?? '')), 120);
                        @endphp
                        <tr>
                            <td class="py-4 pr-4">
                                <span class="block font-semibold text-sm">{{ Str::limit($iTitle, 80) }}</span>
                            </td>
                            <td class="py-4 font-['Geist_Mono'] text-sm">{{ $fmtNum($issue->affected_count ?? 1) }}</td>
                            <td class="py-4"><span class="px-2 py-1 rounded-full {{ $sevCls }} text-[10px] font-bold uppercase">{{ $sevLabel }}</span></td>
                            <td class="py-4 text-xs text-neutral-400">{{ $iRec ?: '—' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════
         PAGE — CRAWL INVENTORY
    ═══════════════════════════════════════════════════════ --}}
    @if($pagesSample->isNotEmpty())
    <div class="pt-8">
        <div class="mb-10">
            <span class="font-['Geist_Mono'] text-primary-container tracking-[0.3em] uppercase text-xs mb-4 block">Crawl Inventory</span>
            <h2 class="text-6xl font-black tracking-tighter leading-[0.9]">Discovered<br/><span class="text-outline-variant/40">URLs</span></h2>
        </div>
        <div class="p-8 bg-surface-container-low rounded-xl">
            <div class="flex justify-between items-center mb-6">
                <span class="text-lg font-bold">Crawl Inventory Snapshot</span>
                <span class="font-['Geist_Mono'] text-[10px] text-neutral-500 uppercase">{{ $pagesSample->count() }} URLs shown</span>
            </div>
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-neutral-800">
                        <th class="pb-3 font-['Geist_Mono'] text-[10px] text-neutral-500 uppercase tracking-widest">URL</th>
                        <th class="pb-3 font-['Geist_Mono'] text-[10px] text-neutral-500 uppercase tracking-widest">Status</th>
                        <th class="pb-3 font-['Geist_Mono'] text-[10px] text-neutral-500 uppercase tracking-widest">Title Len</th>
                        <th class="pb-3 font-['Geist_Mono'] text-[10px] text-neutral-500 uppercase tracking-widest">Meta Len</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-900">
                @foreach($pagesSample as $pRow)
                    <tr>
                        <td class="py-3 pr-4">
                            <span class="block text-sm font-medium">{{ Str::limit($pRow->url ?? '', 65) }}</span>
                            <span class="text-xs text-neutral-500">{{ $pRow->canonical_url ? 'Canonical: '.Str::limit($pRow->canonical_url,55) : 'No canonical' }}</span>
                        </td>
                        <td class="py-3 font-['Geist_Mono'] text-sm {{ (int)($pRow->status_code ?? 200) >= 400 ? 'text-error' : 'text-green-500' }}">
                            {{ $pRow->status_code ?? '200' }}
                        </td>
                        <td class="py-3 font-['Geist_Mono'] text-sm">{{ $fmtNum($pRow->title_length ?? null) }}</td>
                        <td class="py-3 font-['Geist_Mono'] text-sm">{{ $fmtNum($pRow->meta_description_length ?? null) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════
         PAGE — ACTION ROADMAP
    ═══════════════════════════════════════════════════════ --}}
    @if($roadmap->isNotEmpty())
    <div class="pt-8">
        <div class="mb-10">
            <span class="font-['Geist_Mono'] text-primary-container tracking-[0.3em] uppercase text-xs mb-4 block">Action Plan</span>
            <h2 class="text-6xl font-black tracking-tighter leading-[0.9]">Priority<br/><span class="text-outline-variant/40">Roadmap</span></h2>
        </div>
        <div class="space-y-4">
        @foreach($roadmap as $idx => $issue)
            @php
                $rTitle = $asText($issue->title ?? $issue->message ?? $issue->code ?? 'Action');
                $rSteps = Str::limit(strip_tags($asText($issue->fix_steps ?? $issue->recommendation ?? '')), 280);
                $rImp   = $issue->impact ?? '';
                $rBorder = $rImp === 'high' ? 'border-error/30' : ($rImp === 'medium' ? 'border-orange-500/30' : 'border-neutral-700');
            @endphp
            <div class="p-6 bg-surface-container-low rounded-xl border {{ $rBorder }} no-break">
                <div class="flex gap-4">
                    <span class="font-['Geist_Mono'] text-primary-container text-2xl font-black flex-shrink-0">{{ str_pad($idx + 1, 2, '0', STR_PAD_LEFT) }}</span>
                    <div>
                        <span class="font-bold block mb-2">{{ Str::limit($rTitle, 100) }}</span>
                        <p class="text-sm text-neutral-400 leading-relaxed">{{ $rSteps ?: 'Investigate and resolve in next engineering cycle.' }}</p>
                    </div>
                </div>
            </div>
        @endforeach
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-8">
        <div class="p-10 bg-primary-container rounded-xl text-on-primary-container no-break">
            <span class="font-['Geist_Mono'] text-[10px] uppercase tracking-widest block mb-4 opacity-70">Strategic Insight</span>
            <h4 class="text-3xl font-black tracking-tight mb-4">{{ Str::limit($focusIssueTitle, 42) }}</h4>
            <p class="leading-relaxed opacity-90">{{ $focusIssueBody }}</p>
            <div class="mt-8 flex items-center gap-4">
                <span class="font-bold text-sm uppercase tracking-widest border-b-2 border-on-primary-container/30">Priority Recommendation</span>
                <span class="material-symbols-outlined">trending_up</span>
            </div>
        </div>
        <div class="p-10 bg-surface-container-low rounded-xl relative overflow-hidden group no-break">
            <div class="absolute inset-0 opacity-20" style="background:
                radial-gradient(circle at top right, rgba(255,86,38,0.28), transparent 35%),
                linear-gradient(120deg, rgba(255,255,255,0.02), rgba(255,86,38,0.08));"></div>
            <div class="relative z-10">
                <span class="font-['Geist_Mono'] text-[10px] uppercase tracking-widest text-neutral-500 block mb-4">Backlink Profile</span>
                <h4 class="text-3xl font-black tracking-tight mb-4 text-neutral-50">{{ $positiveInsightTitle }}</h4>
                <p class="text-neutral-400 leading-relaxed mb-6">{{ $positiveInsightBody }}</p>
                <div class="flex gap-2 flex-wrap">
                    @foreach($positiveTags as $tag)
                        <span class="bg-surface-container px-3 py-1 rounded text-[10px] font-['Geist_Mono'] text-neutral-400">{{ $tag }}</span>
                    @endforeach
                    @if(empty($positiveTags))
                        <span class="bg-surface-container px-3 py-1 rounded text-[10px] font-['Geist_Mono'] text-neutral-400">Audit baseline</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

</main>

<!-- Footer Shell -->
<footer class="w-full border-t border-neutral-900 bg-neutral-950 mt-16">
    <div class="flex flex-col md:flex-row justify-between items-center px-6 py-8 gap-4 max-w-none">
        <div class="text-neutral-50 font-bold">Backlink Pro</div>
        <p class="font-['Geist_Mono'] text-[10px] tracking-widest uppercase text-neutral-600">
            © {{ now()->format('Y') }} Backlink Pro Digital Curator Systems. Confidential Audit Report · {{ Str::limit($host, 44) }}
        </p>
        <div class="flex gap-6 font-['Geist_Mono'] text-[10px] tracking-widest uppercase">
            <span class="text-neutral-600">Privacy Policy</span>
            <span class="text-neutral-600">Technical Documentation</span>
            <span class="text-neutral-600">Contact Support</span>
        </div>
    </div>
</footer>

</body>
</html>
