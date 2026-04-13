import AppLayout from '../../Components/Layout/AppLayout';
import { router } from '@inertiajs/react';

const clamp = (value, min = 0, max = 100) => Math.min(max, Math.max(min, Number(value) || 0));
const formatNumber = (value, fallback = 'N/A') => {
    if (value === null || value === undefined || value === '') return fallback;
    const numeric = Number(value);
    return Number.isFinite(numeric) ? Intl.NumberFormat('en-US').format(numeric) : value;
};
const formatSeconds = (value, fallback = 'N/A') => {
    if (value === null || value === undefined || value === '') return fallback;
    const numeric = Number(value);
    return Number.isFinite(numeric) ? `${numeric.toFixed(1)}s` : fallback;
};

const normalizePsiMetrics = (run, fallback = {}) => {
    const metrics = run?.kpis || {};
    const source = Object.keys(metrics).length ? metrics : (fallback || {});

    return {
        score: source.score ?? source.categories?.performance_score ?? null,
        lcp: source.lcp ?? source.lcp_ms ?? source.lab_metrics?.lcp_ms ?? null,
        cls: source.cls ?? source.lab_metrics?.cls ?? null,
    };
};

const SECTION_GROUPS = [
    {
        key: 'on_page',
        title: 'On Page SEO',
        items: [
            {
                key: 'title_optimization',
                label: 'Title Optimization',
                getMetric: ({ onPage, pageData }) => ({
                    value: `${formatNumber(onPage.title_length ?? pageData.title_len, 'N/A')} chars`,
                    note: pageData.title || 'Homepage title not available.',
                    percent: clamp((onPage.title_length ?? pageData.title_len ?? 0) / 70 * 100),
                }),
            },
            {
                key: 'meta_descriptions',
                label: 'Meta Descriptions',
                getMetric: ({ onPage, pageData }) => ({
                    value: `${formatNumber(onPage.meta_description_length ?? pageData.meta_len, 'N/A')} chars`,
                    note: pageData.meta_description || 'Meta description not captured.',
                    percent: clamp((onPage.meta_description_length ?? pageData.meta_len ?? 0) / 160 * 100),
                }),
            },
            {
                key: 'heading_structure',
                label: 'Heading Structure',
                getMetric: ({ pageData }) => ({
                    value: `${formatNumber(pageData.h1_count ?? 0)} H1 / ${formatNumber(pageData.h2_count ?? 0)} H2`,
                    note: 'Heading hierarchy detected on the audited page.',
                    percent: clamp(((pageData.h1_count ?? 0) > 0 ? 60 : 20) + Math.min((pageData.h2_count ?? 0) * 8, 40)),
                }),
            },
            {
                key: 'content_quality',
                label: 'Content Quality',
                getMetric: ({ pageData }) => ({
                    value: `${formatNumber(pageData.word_count)} words`,
                    note: 'Word count from the crawled homepage snapshot.',
                    percent: clamp((pageData.word_count ?? 0) / 1500 * 100),
                }),
            },
            {
                key: 'internal_linking',
                label: 'Internal Linking',
                getMetric: ({ pageData }) => ({
                    value: formatNumber(pageData.internal_links_count),
                    note: 'Internal links discovered during crawl.',
                    percent: clamp((pageData.internal_links_count ?? 0) / 150 * 100),
                }),
            },
        ],
    },
    {
        key: 'off_page',
        title: 'Off Page SEO',
        items: [
            {
                key: 'backlink_quality',
                label: 'Backlink Quality',
                getMetric: ({ linkMetrics }) => ({
                    value: formatNumber(linkMetrics.authority_score),
                    note: 'Authority score from available link metrics.',
                    percent: clamp(linkMetrics.authority_score),
                }),
            },
            {
                key: 'referring_domains',
                label: 'Referring Domains',
                getMetric: ({ linkMetrics }) => ({
                    value: formatNumber(linkMetrics.referring_domains),
                    note: 'Referring domains captured in the current backlink snapshot.',
                    percent: clamp((linkMetrics.referring_domains ?? 0) / 250 * 100),
                }),
            },
            {
                key: 'anchor_text_profile',
                label: 'Anchor Text Profile',
                getMetric: ({ issues }) => {
                    const anchorIssues = issues.filter((issue) => String(issue.issue_type || issue.title || '').toLowerCase().includes('anchor'));
                    return {
                        value: anchorIssues.length ? `${anchorIssues.length} issues` : 'No issues found',
                        note: 'Anchor-related findings from this audit dataset.',
                        percent: anchorIssues.length ? clamp(100 - anchorIssues.length * 15) : 92,
                    };
                },
            },
            {
                key: 'link_velocity',
                label: 'Link Velocity',
                getMetric: ({ linkMetrics }) => ({
                    value: formatNumber(linkMetrics.backlinks),
                    note: 'Current backlink count used as live backlink footprint.',
                    percent: clamp((linkMetrics.backlinks ?? 0) / 500 * 100),
                }),
            },
        ],
    },
    {
        key: 'technical_seo',
        title: 'Technical SEO',
        items: [
            {
                key: 'crawlability',
                label: 'Crawlability',
                getMetric: ({ overview, technical }) => ({
                    value: `${formatNumber(overview.pages_crawled_count ?? 0)} pages`,
                    note: technical.robots_txt_present ? 'robots.txt found during audit crawl.' : 'robots.txt not detected during crawl.',
                    percent: technical.robots_txt_present ? 92 : 58,
                }),
            },
            {
                key: 'indexability',
                label: 'Indexability',
                getMetric: ({ technical }) => ({
                    value: `${formatNumber(technical.indexability_issues_count ?? 0)} issues`,
                    note: 'Indexability issue count from the technical audit.',
                    percent: clamp(100 - ((technical.indexability_issues_count ?? 0) * 12)),
                }),
            },
            {
                key: 'pagespeed',
                label: 'PageSpeed',
                getMetric: ({ mobileMetrics, desktopMetrics }) => ({
                    value: `${formatNumber(mobileMetrics.score)} / ${formatNumber(desktopMetrics.score)}`,
                    note: 'Mobile and desktop performance scores from PageSpeed data.',
                    percent: clamp(((mobileMetrics.score ?? 0) + (desktopMetrics.score ?? 0)) / 2),
                }),
            },
            {
                key: 'structured_data',
                label: 'Structured Data',
                getMetric: ({ onPage, pageData }) => {
                    const types = onPage.schema_types || pageData.schema_types || [];
                    return {
                        value: Array.isArray(types) && types.length ? `${types.length} types` : 'Not detected',
                        note: Array.isArray(types) && types.length ? types.join(', ') : 'No schema types were captured.',
                        percent: Array.isArray(types) && types.length ? clamp(types.length * 24) : 18,
                    };
                },
            },
            {
                key: 'mobile_usability',
                label: 'Mobile Usability',
                getMetric: ({ usability }) => ({
                    value: `${formatNumber((usability.mobile_opportunities || []).length)} opportunities`,
                    note: 'Mobile UX opportunities discovered by the audit engine.',
                    percent: clamp(100 - ((usability.mobile_opportunities || []).length * 10)),
                }),
            },
        ],
    },
];

function renderLogo(branding, brandName) {
    if (branding?.logo_url) {
        return <img src={branding.logo_url} alt={`${brandName} logo`} className="h-16 max-w-[220px] rounded-2xl object-contain" />;
    }

    return (
        <div className="rounded-2xl border border-[rgba(255,110,64,0.18)] bg-[rgba(255,247,242,0.04)] px-5 py-4 text-lg font-semibold text-[#fff7f2]">
            {brandName}
        </div>
    );
}

export default function BrandedAuditReportView({ audit, exportingPdf, onExportPdf }) {
    const branding = audit?.branding || {};
    const brandName = branding.company_name || 'Your Company';
    const reportPeriodDays = Number(branding.report_period_days) || 30;
    const reportTitle = branding.use_custom_cover_title && branding.custom_cover_title
        ? branding.custom_cover_title
        : `${brandName} SEO Report`;
    const footerText = branding.footer_text || `${brandName} client reporting`;

    const kpis = audit?.kpis || {};
    const overview = kpis.overview || {};
    const onPage = kpis.on_page_seo || {};
    const technical = kpis.technical || {};
    const usability = kpis.usability || {};
    const pageData = audit?.page_data || {};
    const issues = audit?.issues || [];
    const linkMetrics = pageData.link_metrics_json || {};
    const overallScore = clamp(audit?.overall_score || 0);
    const scoreCircumference = 282.7;
    const scoreOffset = scoreCircumference - (scoreCircumference * overallScore) / 100;
    const mobileMetrics = normalizePsiMetrics(audit?.psi?.mobile, pageData.lighthouse_mobile);
    const desktopMetrics = normalizePsiMetrics(audit?.psi?.desktop, pageData.lighthouse_desktop);
    const lighthouseCategories = pageData.lighthouse_mobile?.categories || {};

    const diagnostics = [
        { label: 'Performance', value: mobileMetrics.score ?? null },
        { label: 'Accessibility', value: lighthouseCategories.accessibility_score ?? null },
        { label: 'Best Practices', value: lighthouseCategories.best_practices_score ?? null },
        { label: 'SEO Score', value: lighthouseCategories.seo_score ?? null },
    ];

    const categoryScores = [
        { label: 'On Page', value: audit?.category_scores?.onpage ?? 0 },
        { label: 'Technical', value: audit?.category_scores?.technical ?? 0 },
        { label: 'Performance', value: audit?.category_scores?.performance ?? 0 },
        { label: 'Links', value: audit?.category_scores?.links ?? 0 },
        { label: 'Social', value: audit?.category_scores?.social ?? 0 },
        { label: 'Usability', value: audit?.category_scores?.usability ?? 0 },
    ];

    const selectedGroups = SECTION_GROUPS.map((group) => ({
        ...group,
        items: group.items
            .filter((item) => branding.report_sections?.[group.key]?.[item.key])
            .map((item) => ({
                ...item,
                metric: item.getMetric({ audit, overview, onPage, technical, usability, pageData, issues, linkMetrics, mobileMetrics, desktopMetrics }),
            })),
    })).filter((group) => group.items.length > 0);

    const issueRows = issues.slice(0, 6);
    const statusRows = [
        { label: 'HTTPS Protocol', state: technical.https_enabled ? 'Good' : 'Check', tone: technical.https_enabled ? '#22c55e' : '#f59e0b' },
        { label: 'XML Sitemap', state: technical.xml_sitemap_present ? 'Good' : 'Missing', tone: technical.xml_sitemap_present ? '#22c55e' : '#f59e0b' },
        { label: 'Robots.txt', state: technical.robots_txt_present ? 'Stable' : 'Missing', tone: technical.robots_txt_present ? '#22c55e' : '#f59e0b' },
        { label: 'Canonical Tags', state: pageData.canonical_url ? 'Good' : 'Check', tone: pageData.canonical_url ? '#22c55e' : '#f59e0b' },
    ];

    return (
        <AppLayout header="Audit Report" subtitle="White label report generated from your saved branding settings.">
            <div className="mx-auto max-w-7xl space-y-6">
                <div className="overflow-hidden rounded-[28px] border border-[rgba(255,110,64,0.14)] bg-[#131313] shadow-[0_24px_60px_rgba(0,0,0,0.28)]">
                    <div className="px-6 py-6 sm:px-8 lg:px-10">
                        <div className="mb-6 flex flex-wrap items-center justify-between gap-4 rounded-[24px] border border-[rgba(255,255,255,0.06)] bg-[rgba(255,255,255,0.02)] px-5 py-4">
                            <div className="flex items-center gap-4">
                                {renderLogo(branding, brandName)}
                                <div>
                                    <div className="text-lg font-semibold tracking-[-0.02em] text-[#fff7f2]">{brandName}</div>
                                    <div className="mt-1 font-mono text-[10px] uppercase tracking-[0.24em] text-[rgba(255,240,232,0.34)]">
                                        {branding.website || audit.normalized_url}
                                    </div>
                                </div>
                            </div>
                            <div className="text-right">
                                <div className="font-mono text-[10px] uppercase tracking-[0.28em] text-[#ff8d64]">Client SEO Report</div>
                                <div className="mt-2 text-sm text-[rgba(255,240,232,0.58)]">{reportTitle}</div>
                                <div className="mt-2 font-mono text-[10px] uppercase tracking-[0.22em] text-[rgba(255,240,232,0.34)]">Last {reportPeriodDays} days</div>
                            </div>
                        </div>

                        <div className="grid gap-4 xl:grid-cols-[1.7fr,0.85fr]">
                            <div className="relative overflow-hidden rounded-[26px] bg-[#1c1b1b] p-7">
                                <div className="absolute inset-y-0 left-0 w-1 bg-[#ff5626]"></div>
                                <div className="absolute -bottom-24 -right-16 h-56 w-56 rounded-full bg-[rgba(255,86,38,0.16)] blur-[100px]"></div>
                                <div className="relative flex flex-col gap-8 md:flex-row md:items-center">
                                    <div className="relative h-52 w-52 flex-shrink-0">
                                        <svg className="h-full w-full -rotate-90" viewBox="0 0 100 100">
                                            <circle cx="50" cy="50" r="45" fill="transparent" stroke="#2a2a2a" strokeWidth="8"></circle>
                                            <circle cx="50" cy="50" r="45" fill="transparent" stroke="#ff5626" strokeWidth="8" strokeDasharray={scoreCircumference} strokeDashoffset={scoreOffset} strokeLinecap="round"></circle>
                                        </svg>
                                        <div className="absolute inset-0 flex flex-col items-center justify-center">
                                            <span className="text-5xl font-black tracking-[-0.04em] text-[#fff7f2]">{overallScore}</span>
                                            <span className="font-mono text-[10px] uppercase tracking-[0.28em] text-[#ff8d64]">{audit?.overall_grade || 'N/A'}</span>
                                        </div>
                                    </div>
                                    <div className="relative z-10 flex-1">
                                        <div className="flex flex-wrap items-center gap-4">
                                            {renderLogo(branding, brandName)}
                                            <div>
                                                <div className="text-3xl font-bold tracking-[-0.03em] text-[#fff7f2]">Overall Health Score</div>
                                                <div className="mt-2 text-sm text-[rgba(255,240,232,0.56)]">Real audit signals rendered with your saved white label identity.</div>
                                            </div>
                                        </div>
                                        <p className="mt-6 max-w-lg text-sm leading-7 text-[rgba(255,240,232,0.58)]">
                                            {audit?.summary?.overview || audit?.summary?.summary || 'This branded report combines your client-facing identity with real SEO audit findings from the scanned website.'}
                                        </p>
                                        <div className="mt-6 grid gap-4 sm:grid-cols-3">
                                            <div className="rounded-2xl bg-[#201f1f] p-4">
                                                <span className="block font-mono text-[10px] uppercase tracking-[0.25em] text-[rgba(255,240,232,0.34)]">Pages Crawled</span>
                                                <span className="mt-2 block text-xl font-bold text-[#fff7f2]">{formatNumber(overview.pages_crawled_count ?? audit?.summary?.pages_scanned ?? 0)}</span>
                                            </div>
                                            <div className="rounded-2xl bg-[#201f1f] p-4">
                                                <span className="block font-mono text-[10px] uppercase tracking-[0.25em] text-[rgba(255,240,232,0.34)]">Issues Found</span>
                                                <span className="mt-2 block text-xl font-bold text-[#fff7f2]">{formatNumber(issues.length)}</span>
                                            </div>
                                            <div className="rounded-2xl bg-[#201f1f] p-4">
                                                <span className="block font-mono text-[10px] uppercase tracking-[0.25em] text-[rgba(255,240,232,0.34)]">Reporting Window</span>
                                                <span className="mt-2 block text-xl font-bold text-[#fff7f2]">{reportPeriodDays} days</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div className="grid gap-4">
                                <div className="rounded-[24px] bg-[#2a2a2a] p-6">
                                    <span className="block font-mono text-[10px] uppercase tracking-[0.24em] text-[rgba(255,240,232,0.34)]">Internal Links</span>
                                    <div className="mt-3 flex items-center justify-between">
                                        <span className="text-4xl font-black tracking-[-0.04em] text-[#fff7f2]">{formatNumber(pageData.internal_links_count)}</span>
                                        <i className="bi bi-diagram-3-fill text-xl text-[#ff8d64]"></i>
                                    </div>
                                </div>
                                <div className="rounded-[24px] bg-[#2a2a2a] p-6">
                                    <span className="block font-mono text-[10px] uppercase tracking-[0.24em] text-[rgba(255,240,232,0.34)]">Authority Score</span>
                                    <div className="mt-3 flex items-center justify-between">
                                        <span className="text-4xl font-black tracking-[-0.04em] text-[#fff7f2]">{formatNumber(linkMetrics.authority_score)}</span>
                                        <i className="bi bi-star-fill text-xl text-[#ff8d64]"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="mt-6 rounded-[26px] bg-[#1c1b1b] p-7">
                            <div className="mb-8 flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <h5 className="text-2xl font-bold tracking-[-0.03em] text-[#fff7f2]">SEO Score Breakdown</h5>
                                    <p className="mt-2 text-sm text-[rgba(255,240,232,0.48)]">Live category scores from the real audit results.</p>
                                </div>
                                <div className="rounded-full bg-[rgba(255,86,38,0.10)] px-3 py-1 font-mono text-[10px] uppercase tracking-[0.2em] text-[#ff8d64]">
                                    Website audit data
                                </div>
                            </div>
                            <div className="flex h-64 items-end gap-3">
                                {categoryScores.map((entry) => (
                                    <div key={entry.label} className="flex flex-1 flex-col items-center gap-3">
                                        <div className="flex h-full w-full items-end">
                                            <div className="w-full rounded-t-[8px] bg-[linear-gradient(180deg,rgba(255,181,161,0.18),rgba(255,86,38,0.82))]" style={{ height: `${clamp(entry.value)}%` }}></div>
                                        </div>
                                        <div className="text-center">
                                            <div className="text-sm font-semibold text-[#fff7f2]">{formatNumber(entry.value)}</div>
                                            <div className="font-mono text-[10px] uppercase tracking-[0.2em] text-[rgba(255,240,232,0.34)]">{entry.label}</div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>

                        <div className="mt-6 rounded-[26px] bg-[#1c1b1b] p-7">
                            <div className="mb-8 flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <h5 className="text-2xl font-bold tracking-[-0.03em] text-[#fff7f2]">Mobile vs Desktop</h5>
                                    <p className="mt-2 text-sm text-[rgba(255,240,232,0.48)]">Real performance comparison from PageSpeed and Lighthouse data.</p>
                                </div>
                            </div>
                            <div className="grid gap-4 lg:grid-cols-2">
                                {[
                                    { label: 'Mobile', metrics: mobileMetrics, stability: pageData.performance_metrics?.mobile?.cls ?? mobileMetrics.cls, speed: mobileMetrics.lcp ? mobileMetrics.lcp / 1000 : null, bars: [mobileMetrics.score ?? 0, clamp(100 - ((usability.mobile_opportunities || []).length * 10)), clamp(lighthouseCategories.seo_score ?? 0)], tone: 'bg-[linear-gradient(135deg,#ff5626,#ff764d)]' },
                                    { label: 'Desktop', metrics: desktopMetrics, stability: pageData.performance_metrics?.desktop?.cls ?? desktopMetrics.cls, speed: desktopMetrics.lcp ? desktopMetrics.lcp / 1000 : null, bars: [desktopMetrics.score ?? 0, clamp(100 - ((usability.desktop_opportunities || []).length * 10)), clamp((audit?.category_scores?.performance ?? 0))], tone: 'bg-[linear-gradient(135deg,#242323,#363434)]' },
                                ].map((device) => (
                                    <div key={device.label} className="rounded-[24px] bg-[#151414] p-5">
                                        <div className="flex items-start justify-between gap-4">
                                            <div>
                                                <div className="font-mono text-[10px] uppercase tracking-[0.24em] text-[rgba(255,240,232,0.34)]">{device.label}</div>
                                                <div className="mt-3 text-4xl font-black tracking-[-0.04em] text-[#fff7f2]">{formatNumber(device.metrics.score)}</div>
                                                <div className="mt-1 text-sm text-[rgba(255,240,232,0.52)]">Experience score</div>
                                            </div>
                                            <div className={`rounded-2xl px-4 py-3 text-right ${device.tone}`}>
                                                <div className="font-mono text-[10px] uppercase tracking-[0.22em] text-[rgba(255,244,238,0.78)]">Load Speed</div>
                                                <div className="mt-2 text-2xl font-bold text-[#fff7f2]">{formatSeconds(device.speed)}</div>
                                            </div>
                                        </div>
                                        <div className="mt-5 grid grid-cols-3 gap-3">
                                            {device.bars.map((value, index) => (
                                                <div key={`${device.label}-${index}`} className="rounded-2xl bg-[#201f1f] p-3">
                                                    <div className="font-mono text-[9px] uppercase tracking-[0.2em] text-[rgba(255,240,232,0.32)]">
                                                        {index === 0 ? 'Speed' : index === 1 ? 'UX' : 'SEO'}
                                                    </div>
                                                    <div className="mt-3 h-20 rounded-2xl bg-[rgba(255,255,255,0.04)] p-2">
                                                        <div className="flex h-full items-end">
                                                            <div className="w-full rounded-xl bg-[linear-gradient(180deg,rgba(255,181,161,0.2),rgba(255,86,38,0.88))]" style={{ height: `${clamp(value)}%` }}></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                        <div className="mt-5 flex items-center justify-between rounded-2xl bg-[#201f1f] px-4 py-3">
                                            <span className="text-sm text-[rgba(255,240,232,0.68)]">Layout stability</span>
                                            <span className="text-sm font-semibold text-[#fff7f2]">{device.stability ?? 'N/A'}</span>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>

                        <div className="mt-6 grid gap-4 lg:grid-cols-[1.4fr,0.8fr]">
                            <div className="rounded-[26px] bg-[#1c1b1b] p-7">
                                <div className="mb-8 font-mono text-[10px] uppercase tracking-[0.32em] text-[rgba(255,240,232,0.34)]">Lighthouse Diagnostics</div>
                                <div className="grid grid-cols-2 gap-6 xl:grid-cols-4">
                                    {diagnostics.map((metric) => (
                                        <div key={metric.label} className="text-center">
                                            <div className="relative mx-auto mb-4 h-24 w-24">
                                                <svg className="h-full w-full -rotate-90" viewBox="0 0 36 36">
                                                    <circle cx="18" cy="18" r="16" fill="none" stroke="#2a2a2a" strokeWidth="3"></circle>
                                                    <circle cx="18" cy="18" r="16" fill="none" stroke={(metric.value ?? 0) >= 90 ? '#22c55e' : '#ff5626'} strokeWidth="3" strokeDasharray="100" strokeDashoffset={100 - clamp(metric.value)}></circle>
                                                </svg>
                                                <span className="absolute inset-0 flex items-center justify-center text-lg font-bold text-[#fff7f2]">{formatNumber(metric.value)}</span>
                                            </div>
                                            <div className="text-[11px] font-bold uppercase tracking-[0.14em] text-[rgba(255,240,232,0.55)]">{metric.label}</div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                            <div className="rounded-[26px] bg-[#2a2a2a] p-7">
                                <div className="mb-6 font-mono text-[10px] uppercase tracking-[0.32em] text-[rgba(255,240,232,0.34)]">Status Ledger</div>
                                <div className="space-y-5">
                                    {statusRows.map((item) => (
                                        <div key={item.label} className="flex items-center justify-between gap-3">
                                            <span className="text-sm text-[rgba(255,240,232,0.72)]">{item.label}</span>
                                            <span className="flex items-center gap-2 text-[11px] font-medium uppercase tracking-[0.16em]" style={{ color: item.tone }}>
                                                <span className="h-2 w-2 rounded-full" style={{ backgroundColor: item.tone }}></span>
                                                {item.state}
                                            </span>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>

                        <div className="mt-6 rounded-[26px] bg-[#1c1b1b] p-7">
                            <div className="mb-6 flex items-center justify-between gap-3">
                                <div>
                                    <div className="font-mono text-[10px] uppercase tracking-[0.3em] text-[rgba(255,240,232,0.34)]">Top Issues By Severity</div>
                                    <div className="mt-2 text-2xl font-bold tracking-[-0.03em] text-[#fff7f2]">Live audit issue summary</div>
                                </div>
                                <div className="rounded-full bg-[rgba(255,86,38,0.10)] px-3 py-1 font-mono text-[10px] uppercase tracking-[0.2em] text-[#ff8d64]">
                                    {formatNumber(issues.length)} issues
                                </div>
                            </div>
                            <div className="overflow-hidden rounded-[20px] bg-[#151414]">
                                <div className="grid grid-cols-[1.8fr,0.7fr,0.8fr,0.9fr] gap-3 px-5 py-3 font-mono text-[10px] uppercase tracking-[0.22em] text-[rgba(255,240,232,0.32)]">
                                    <span>Issue</span><span>Affected</span><span>Severity</span><span>Action</span>
                                </div>
                                {issueRows.map((row) => (
                                    <div key={row.id} className="grid grid-cols-[1.8fr,0.7fr,0.8fr,0.9fr] gap-3 border-t border-[rgba(255,255,255,0.04)] px-5 py-4">
                                        <div>
                                            <div className="text-sm font-semibold text-[#fff7f2]">{row.title || row.message || row.issue_type}</div>
                                            <div className="mt-1 text-xs text-[rgba(255,240,232,0.42)]">{row.description || row.message || 'Actionable SEO issue detected in the current audit.'}</div>
                                        </div>
                                        <div className="text-sm text-[rgba(255,240,232,0.72)]">{formatNumber(row.affected_count)}</div>
                                        <div>
                                            <span className={`inline-flex rounded-full px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.15em] ${row.severity === 'critical' ? 'bg-[rgba(239,68,68,0.16)] text-[#f87171]' : row.severity === 'warning' ? 'bg-[rgba(245,158,11,0.16)] text-[#fbbf24]' : 'bg-[rgba(96,165,250,0.16)] text-[#93c5fd]'}`}>
                                                {row.severity}
                                            </span>
                                        </div>
                                        <div className="text-sm text-[#ffb5a1]">{row.recommendation || row.message || 'Review'}</div>
                                    </div>
                                ))}
                            </div>
                        </div>

                        {selectedGroups.length > 0 && (
                            <div className="mt-6 grid gap-4">
                                {selectedGroups.map((group) => (
                                    <div key={group.key} className="rounded-[26px] bg-[#1c1b1b] p-7">
                                        <div className="flex flex-wrap items-start justify-between gap-3">
                                            <div>
                                                <div className="font-mono text-[10px] uppercase tracking-[0.3em] text-[rgba(255,240,232,0.34)]">{group.title}</div>
                                                <div className="mt-2 text-2xl font-bold tracking-[-0.03em] text-[#fff7f2]">Saved report scope with real audit metrics</div>
                                            </div>
                                            <div className="rounded-full bg-[rgba(255,86,38,0.10)] px-3 py-1 font-mono text-[10px] uppercase tracking-[0.2em] text-[#ff8d64]">{group.items.length} items</div>
                                        </div>
                                        <div className="mt-6 grid gap-4 lg:grid-cols-[1.2fr,0.8fr]">
                                            <div className="rounded-[22px] bg-[#201f1f] p-5">
                                                <div className="space-y-3">
                                                    {group.items.map((item) => (
                                                        <div key={item.key} className="rounded-2xl bg-[#161515] px-4 py-3">
                                                            <div className="flex items-center justify-between gap-3">
                                                                <div className="flex items-center gap-3">
                                                                    <i className="bi bi-check-circle-fill text-[#ff8d64]"></i>
                                                                    <span className="text-sm font-medium text-[#fff7f2]">{item.label}</span>
                                                                </div>
                                                                <span className="text-sm font-semibold text-[#fff7f2]">{item.metric.value}</span>
                                                            </div>
                                                            <div className="mt-2 text-xs text-[rgba(255,240,232,0.48)]">{item.metric.note}</div>
                                                            <div className="mt-2 h-2.5 rounded-full bg-[rgba(255,255,255,0.06)]">
                                                                <div className="h-2.5 rounded-full bg-[linear-gradient(90deg,#ff5626,#ff9c7c)]" style={{ width: `${clamp(item.metric.percent)}%` }}></div>
                                                            </div>
                                                        </div>
                                                    ))}
                                                </div>
                                            </div>
                                            <div className="rounded-[22px] bg-[#201f1f] p-5">
                                                <div className="flex h-40 items-end gap-2">
                                                    {group.items.map((item) => (
                                                        <div key={`${group.key}-${item.key}`} className="flex-1 rounded-t-[4px] bg-[linear-gradient(180deg,rgba(255,181,161,0.20),rgba(255,86,38,0.75))]" style={{ height: `${clamp(item.metric.percent)}%` }}></div>
                                                    ))}
                                                </div>
                                                <div className="mt-4 space-y-2">
                                                    {group.items.map((item) => (
                                                        <div key={`${group.key}-${item.key}-label`} className="flex items-center justify-between text-xs text-[rgba(255,240,232,0.48)]">
                                                            <span>{item.label}</span>
                                                            <span>{item.metric.value}</span>
                                                        </div>
                                                    ))}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}

                        <div className="mt-6 rounded-[26px] bg-[#1c1b1b] p-7">
                            <div className="flex flex-wrap items-end justify-between gap-4">
                                <div>
                                    <div className="font-mono text-[10px] uppercase tracking-[0.3em] text-[rgba(255,240,232,0.34)]">Report Closing Note</div>
                                    <div className="mt-2 text-2xl font-bold tracking-[-0.03em] text-[#fff7f2]">Branded ending block with your identity</div>
                                </div>
                                <div className="rounded-full bg-[rgba(255,86,38,0.10)] px-3 py-1 font-mono text-[10px] uppercase tracking-[0.2em] text-[#ff8d64]">
                                    Ready for delivery
                                </div>
                            </div>
                            <div className="mt-6 grid gap-4 lg:grid-cols-[1.2fr,0.8fr]">
                                <div className="rounded-[22px] bg-[#151414] p-5">
                                    <div className="text-sm leading-7 text-[rgba(255,240,232,0.60)]">
                                        This white label report is powered by your saved company profile and live website audit results, so future runs keep the same branded presentation until you change the branding settings again.
                                    </div>
                                    <div className="mt-5 h-px bg-[rgba(255,255,255,0.06)]"></div>
                                    <div className="mt-5 flex flex-wrap items-center justify-between gap-4">
                                        <div>
                                            <div className="text-base font-semibold text-[#fff7f2]">{brandName}</div>
                                            <div className="mt-1 text-sm text-[rgba(255,240,232,0.52)]">{footerText}</div>
                                        </div>
                                        {renderLogo(branding, brandName)}
                                    </div>
                                </div>
                                <div className="rounded-[22px] bg-[#201f1f] p-5">
                                    <div className="font-mono text-[10px] uppercase tracking-[0.24em] text-[rgba(255,240,232,0.34)]">Delivery Modules</div>
                                    <div className="mt-4 space-y-3">
                                        {['Executive Summary', 'Technical Findings', 'Performance Signals', 'SEO Scope'].map((label) => (
                                            <div key={label} className="flex items-center justify-between rounded-2xl bg-[#161515] px-4 py-3">
                                                <span className="text-sm text-[#fff7f2]">{label}</span>
                                                <i className="bi bi-check2-circle text-[#ff8d64]"></i>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="flex gap-3">
                    <button
                        onClick={() => router.visit('/audit-report')}
                        className="flex items-center gap-2 rounded-lg border border-[var(--admin-border)] bg-[var(--admin-surface)] px-6 py-2.5 font-medium text-[var(--admin-text)] transition-colors hover:bg-[var(--admin-surface-2)]"
                    >
                        <i className="bi bi-arrow-left"></i>
                        Back to Audits
                    </button>
                    <button
                        onClick={onExportPdf}
                        disabled={exportingPdf}
                        className="flex items-center gap-2 rounded-lg bg-gradient-to-r from-[#2F6BFF] to-[#2457D6] px-6 py-2.5 font-medium text-white shadow-lg shadow-[#2F6BFF]/20 transition-all disabled:opacity-70"
                    >
                        {exportingPdf ? <i className="bi bi-arrow-repeat animate-spin"></i> : <i className="bi bi-download"></i>}
                        {exportingPdf ? 'Generating PDF...' : 'Export PDF'}
                    </button>
                </div>
            </div>
        </AppLayout>
    );
}
