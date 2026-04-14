import AppLayout from '../Components/Layout/AppLayout';
import Card from '../Components/Shared/Card';
import { useState, useMemo, useEffect } from 'react';
import { router } from '@inertiajs/react';
import BrandedAuditReportView from './WhiteLabelReport/BrandedAuditReportView';

function isPdfArrayBuffer(buf) {
    if (!buf || buf.byteLength < 5) return false;
    const u = new Uint8Array(buf);
    return u[0] === 0x25 && u[1] === 0x50 && u[2] === 0x44 && u[3] === 0x46;
}

function parseDownloadFilename(contentDisposition, fallback) {
    if (!contentDisposition || typeof contentDisposition !== 'string') return fallback;
    const star = contentDisposition.match(/filename\*=UTF-8''([^;\n]+)/i);
    if (star) {
        try {
            return decodeURIComponent(star[1].trim());
        } catch {
            /* ignore */
        }
    }
    const quoted = contentDisposition.match(/filename\s*=\s*"([^"]+)"/i);
    if (quoted) return quoted[1];
    const bare = contentDisposition.match(/filename\s*=\s*([^;\n]+)/i);
    if (bare) return bare[1].replace(/^["']|["']$/g, '').trim();
    return fallback;
}

function triggerPdfDownload(arrayBuffer, filename) {
    const blob = new Blob([arrayBuffer], { type: 'application/pdf' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename.endsWith('.pdf') ? filename : `${filename}.pdf`;
    a.rel = 'noopener';
    a.style.display = 'none';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    setTimeout(() => URL.revokeObjectURL(url), 2500);
}

function useExportPdf(auditId) {
    const [exporting, setExporting] = useState(false);

    const exportPdf = async () => {
        if (!auditId) return;

        setExporting(true);
        try {
            const defaultName = `seo-audit-${auditId}-${new Date().toISOString().slice(0, 10)}.pdf`;
            const res = await fetch(`/audit-report/${auditId}/export-pdf`, {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/pdf',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const buf = await res.arrayBuffer();
            const disposition = res.headers.get('Content-Disposition');
            const filename = parseDownloadFilename(disposition, defaultName);

            if (!res.ok) {
                throw new Error(`Export failed (${res.status})`);
            }

            if (isPdfArrayBuffer(buf)) {
                triggerPdfDownload(buf, filename);
                return;
            }

            throw new Error('Server did not return a valid PDF file.');
        } catch (e) {
            console.error(e);
            window.alert(e instanceof Error ? e.message : 'PDF export failed. Please try again.');
        } finally {
            setExporting(false);
        }
    };

    return [exporting, exportPdf];
}

const tabs = [
    { id: 'overview', label: 'Overview', icon: 'bi-speedometer2' },
    { id: 'onpage', label: 'On-Page SEO', icon: 'bi-file-text' },
    { id: 'technical', label: 'Technical', icon: 'bi-gear' },
    { id: 'performance', label: 'Performance', icon: 'bi-lightning' },
    { id: 'integrations', label: 'Integrations', icon: 'bi-plug' },
];

/** Core report modules: data already shown in the five tabs above (not duplicated as “advanced” sections). */
const CORE_MODULE_KEYS_DUPLICATING_TABS = new Set(['overview', 'on_page_seo', 'technical', 'performance', 'integrations']);

/** Where each optional crawl module appears after its core tab content (`onpage` = On-Page SEO tab id). */
const EXTENDED_MODULE_TAB = {
    segmentation: 'overview',
    near_duplicate_content: 'onpage',
    spelling_grammar: 'onpage',
    js_rendering: 'technical',
    site_visualisations: 'technical',
    custom_source_search: 'technical',
    custom_extraction: 'technical',
    forms_auth_summary: 'technical',
    link_metrics: 'integrations',
};

const SEVERITY_CHART_COLORS = {
    critical: '#F04438',
    warning: '#F79009',
    info: '#2F6BFF',
    likely_authenticated: '#12B76A',
    http_blocked: '#F04438',
    login_redirect_suspected: '#F79009',
};
const BAR_PALETTE = ['#2F6BFF', '#2457D6', '#12B76A', '#7C3AED', '#F79009', '#06B6D4'];

const formatDate = (value) => (value ? new Date(value).toLocaleString() : 'N/A');
const formatNumber = (value) => (value === null || value === undefined || value === '' ? 'N/A' : Intl.NumberFormat('en-US').format(value));
const formatBool = (value) => (value === null || value === undefined ? 'N/A' : value ? 'Yes' : 'No');
const formatMetricSeconds = (value) => (value === null || value === undefined || Number.isNaN(value) ? 'N/A' : `${Number(value).toFixed(2)}s`);
const formatMegabytes = (value) => (value === null || value === undefined ? 'N/A' : `${Number(value).toFixed(2)} MB`);
const asArray = (value) => (Array.isArray(value) ? value.filter(Boolean) : []);
const splitFixSteps = (value) => {
    if (!value) return [];
    if (Array.isArray(value)) return value.filter(Boolean).map((step) => String(step).trim()).filter(Boolean);
    if (typeof value !== 'string') return [];
    return value
        .split(/\r?\n|;|•/g)
        .map((step) => step.trim())
        .filter(Boolean);
};
const normalizePsiMetrics = (run, fallback = {}) => {
    const metrics = run?.kpis || {};
    const source = Object.keys(metrics).length ? metrics : (fallback || {});

    return {
        score: source.score ?? source.categories?.performance_score ?? null,
        lcp: source.lcp ?? source.lcp_ms ?? source.lab_metrics?.lcp_ms ?? null,
        fcp: source.fcp ?? source.fcp_ms ?? source.lab_metrics?.fcp_ms ?? null,
        cls: source.cls ?? source.lab_metrics?.cls ?? null,
        tti: source.tti ?? source.tti_ms ?? source.lab_metrics?.tti_ms ?? null,
        tbt: source.tbt ?? source.tbt_ms ?? source.lab_metrics?.tbt_ms ?? null,
    };
};

function getScoreTone(score) {
    if (score >= 90) return { bg: 'bg-[#12B76A]/10', border: 'border-[#12B76A]/30', text: 'text-[#12B76A]' };
    if (score >= 70) return { bg: 'bg-[#F79009]/10', border: 'border-[#F79009]/30', text: 'text-[#F79009]' };
    return { bg: 'bg-[#F04438]/10', border: 'border-[#F04438]/30', text: 'text-[#F04438]' };
}

function MetricCard({ label, value, hint = null }) {
    return (
        <div className="rounded-xl border border-[var(--admin-border)] bg-[var(--admin-surface-2)] p-4">
            <p className="text-sm text-[var(--admin-text-muted)]">{label}</p>
            <p className="mt-2 text-2xl font-semibold text-[var(--admin-text)]">{value}</p>
            {hint ? <p className="mt-1 text-xs text-[var(--admin-text-dim)]">{hint}</p> : null}
        </div>
    );
}

function IssueBadge({ severity }) {
    const tone = severity === 'critical'
        ? 'bg-[#F04438]/10 text-[#F04438]'
        : severity === 'warning'
            ? 'bg-[#F79009]/10 text-[#F79009]'
            : 'bg-[#2F6BFF]/10 text-[#2F6BFF]';

    return <span className={`rounded-md px-2 py-1 text-xs font-semibold uppercase ${tone}`}>{severity}</span>;
}

function SectionMessage({ children }) {
    return (
        <div className="rounded-xl border border-dashed border-[var(--admin-border)] bg-[var(--admin-surface-2)] p-5 text-sm text-[var(--admin-text-dim)]">
            {children}
        </div>
    );
}

function DataTable({ title, columns, rows, emptyText = 'No data available.' }) {
    return (
        <div className="space-y-3">
            <h3 className="text-base font-semibold text-[var(--admin-text)]">{title}</h3>
            <div className="overflow-x-auto rounded-xl border border-[var(--admin-border)]">
                <table className="min-w-full text-sm">
                    <thead className="bg-[var(--admin-surface-2)] text-left text-[var(--admin-text-muted)]">
                        <tr>
                            {columns.map((column) => (
                                <th key={column.key} className="px-4 py-3 font-medium">
                                    {column.label}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {rows.length > 0 ? rows.map((row, rowIndex) => (
                            <tr key={row.id || row.url || row.asset_url || row.query || row.page_path || rowIndex} className="border-t border-[var(--admin-border)]">
                                {columns.map((column) => (
                                    <td key={column.key} className="px-4 py-3 align-top text-[var(--admin-text)]">
                                        {column.render ? column.render(row[column.key], row) : (row[column.key] ?? 'N/A')}
                                    </td>
                                ))}
                            </tr>
                        )) : (
                            <tr>
                                <td colSpan={columns.length} className="px-4 py-6 text-center text-[var(--admin-text-dim)]">
                                    {emptyText}
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
        </div>
    );
}

function chartEntriesFromDataset(dataset) {
    if (!dataset || typeof dataset !== 'object' || Array.isArray(dataset)) {
        return [];
    }
    return Object.entries(dataset).map(([k, v]) => ({
        rawKey: k,
        label: String(k).replaceAll('_', ' '),
        raw: v,
        num: typeof v === 'number' && !Number.isNaN(v) ? v : Number(v),
    }));
}

function ModuleChartCard({ chart }) {
    const entries = useMemo(() => chartEntriesFromDataset(chart?.dataset), [chart?.dataset]);
    const title = chart?.title || 'Chart';
    const type = chart?.type || 'bar';

    if (!entries.length) {
        return (
            <Card variant="elevated">
                <h3 className="text-lg font-semibold text-[var(--admin-text)]">{title}</h3>
                <SectionMessage>No data for this visualization.</SectionMessage>
            </Card>
        );
    }

    if (type === 'pie') {
        const numeric = entries.map((e) => ({
            ...e,
            n: Number.isFinite(e.num) && e.num > 0 ? e.num : 0,
        }));
        const total = numeric.reduce((s, e) => s + e.n, 0);
        if (total <= 0) {
            return (
                <Card variant="elevated">
                    <h3 className="text-lg font-semibold text-[var(--admin-text)]">{title}</h3>
                    <SectionMessage>All values are zero — nothing to chart.</SectionMessage>
                </Card>
            );
        }
        let acc = 0;
        const slices = numeric
            .filter((e) => e.n > 0)
            .map((e) => {
                const pct = (e.n / total) * 100;
                const start = acc;
                acc += pct;
                const color = SEVERITY_CHART_COLORS[e.rawKey] || BAR_PALETTE[Math.abs(String(e.rawKey).length) % BAR_PALETTE.length];
                return { ...e, color, start, end: acc };
            });
        const gradient = slices.map((s) => `${s.color} ${s.start.toFixed(2)}% ${s.end.toFixed(2)}%`).join(', ');

        return (
            <Card variant="elevated">
                <h3 className="text-lg font-semibold text-[var(--admin-text)]">{title}</h3>
                <div className="mt-4 flex flex-col items-center gap-6 sm:flex-row sm:items-start">
                    <div
                        className="h-40 w-40 shrink-0 rounded-full border-4 border-[var(--admin-border)] shadow-inner"
                        style={{ background: `conic-gradient(${gradient})` }}
                        aria-hidden
                    />
                    <ul className="min-w-0 flex-1 space-y-2 text-sm">
                        {slices.map((s) => (
                            <li key={s.rawKey} className="flex items-center justify-between gap-3">
                                <span className="flex min-w-0 items-center gap-2">
                                    <span className="h-3 w-3 shrink-0 rounded-sm" style={{ backgroundColor: s.color }} />
                                    <span className="capitalize text-[var(--admin-text)]">{s.label}</span>
                                </span>
                                <span className="shrink-0 font-medium tabular-nums text-[var(--admin-text-muted)]">
                                    {formatNumber(s.n)}
                                    <span className="text-[var(--admin-text-dim)]"> ({((s.n / total) * 100).toFixed(1)}%)</span>
                                </span>
                            </li>
                        ))}
                    </ul>
                </div>
            </Card>
        );
    }

    const numericForBar = entries.map((e, i) => {
        const n = Number.isFinite(e.num) ? e.num : null;
        return { ...e, n, display: n === null || Number.isNaN(n) ? (e.raw === null || e.raw === undefined ? '—' : String(e.raw)) : formatNumber(n), color: BAR_PALETTE[i % BAR_PALETTE.length] };
    });
    const maxBar = Math.max(...numericForBar.map((e) => (e.n !== null && !Number.isNaN(e.n) ? Math.abs(e.n) : 0)), 1);

    return (
        <Card variant="elevated">
            <h3 className="text-lg font-semibold text-[var(--admin-text)]">{title}</h3>
            <ul className="mt-4 space-y-3">
                {numericForBar.map((e) => {
                    const pct = e.n !== null && !Number.isNaN(e.n) ? Math.min(100, (Math.abs(e.n) / maxBar) * 100) : 0;
                    return (
                        <li key={e.rawKey}>
                            <div className="mb-1 flex items-center justify-between gap-2 text-xs text-[var(--admin-text-muted)]">
                                <span className="min-w-0 truncate capitalize">{e.label}</span>
                                <span className="shrink-0 font-semibold tabular-nums text-[var(--admin-text)]">{e.display}</span>
                            </div>
                            <div className="h-2.5 overflow-hidden rounded-full bg-[var(--admin-surface-2)]">
                                <div
                                    className="h-full rounded-full transition-all"
                                    style={{ width: `${pct}%`, backgroundColor: e.color }}
                                />
                            </div>
                        </li>
                    );
                })}
            </ul>
        </Card>
    );
}

function ExtendedModulesInTab({ audit, modules }) {
    if (!modules?.length) {
        return null;
    }
    return (
        <div className="mt-10 space-y-10 border-t border-[var(--admin-border)] pt-8">
            <div>
                <p className="text-xs font-semibold uppercase tracking-wide text-[var(--admin-text-muted)]">Advanced crawl modules</p>
                <p className="mt-1 text-sm text-[var(--admin-text-dim)]">Optional features enabled for this audit appear below.</p>
            </div>
            {modules.map((m) => (
                <AuditExtendedModuleSection key={m.module_key} audit={audit} module={m} />
            ))}
        </div>
    );
}

function AuditExtendedModuleSection({ audit, module: mod }) {
    const [jsRenderPreset, setJsRenderPreset] = useState('all');
    const [segmentFilter, setSegmentFilter] = useState('all');
    const [spellingGrammarPreset, setSpellingGrammarPreset] = useState('all');
    const [customRuleKeyFilter, setCustomRuleKeyFilter] = useState('all');
    const [extractionPreset, setExtractionPreset] = useState('all');
    const [linkMetricsPreset, setLinkMetricsPreset] = useState('all');
    const [linkMetricsIssueType, setLinkMetricsIssueType] = useState('all');
    const [linkMetricsMinRd, setLinkMetricsMinRd] = useState(0);
    const [linkMetricsMinBl, setLinkMetricsMinBl] = useState(0);

    useEffect(() => {
        setJsRenderPreset('all');
        setSegmentFilter('all');
        setSpellingGrammarPreset('all');
        setCustomRuleKeyFilter('all');
        setExtractionPreset('all');
        setLinkMetricsPreset('all');
        setLinkMetricsIssueType('all');
        setLinkMetricsMinRd(0);
        setLinkMetricsMinBl(0);
    }, [mod.module_key]);

    const filteredModuleIssues = useMemo(() => {
        const list = mod?.issues || [];
        let out = list;
        if (segmentFilter !== 'all') {
            out = out.filter((issue) => {
                const issueSegment = issue.segment || issue.details_json?.segment || 'other';
                return issueSegment === segmentFilter;
            });
        }
        if (mod?.module_key === 'spelling_grammar' && spellingGrammarPreset !== 'all') {
            out = out.filter((issue) => {
                if (spellingGrammarPreset === 'high_confidence') {
                    return (issue.details_json?.filter_tags || []).includes('high_confidence');
                }
                return issue.details_json?.issue_kind === spellingGrammarPreset;
            });
        }
        if (mod?.module_key === 'js_rendering' && jsRenderPreset !== 'all') {
            out = out.filter((issue) => {
                const tags = issue.details_json?.filter_tags || [];
                return tags.includes(jsRenderPreset);
            });
        }
        if (mod?.module_key === 'custom_source_search' && customRuleKeyFilter !== 'all') {
            out = out.filter((issue) => (issue.details_json?.rule_key || '') === customRuleKeyFilter);
        }
        if (mod?.module_key === 'link_metrics') {
            const minRd = Number(linkMetricsMinRd) || 0;
            const minBl = Number(linkMetricsMinBl) || 0;
            out = out.filter((issue) => {
                const eq = issue.details_json?.link_equity;
                if (!eq) return false;
                const rd = Number(eq.referring_domains ?? 0);
                const bl = Number(eq.backlinks ?? 0);
                if (rd < minRd || bl < minBl) return false;
                if (linkMetricsIssueType !== 'all' && issue.issue_type !== linkMetricsIssueType) return false;
                if (linkMetricsPreset === 'high_tier_only' && eq.tier !== 'high') return false;
                if (linkMetricsPreset === 'medium_or_high' && eq.tier !== 'high' && eq.tier !== 'medium') return false;
                return true;
            });
        }
        return out;
    }, [mod, jsRenderPreset, segmentFilter, spellingGrammarPreset, customRuleKeyFilter, linkMetricsPreset, linkMetricsIssueType, linkMetricsMinRd, linkMetricsMinBl]);

    const filteredModuleTables = useMemo(() => {
        const tables = mod?.tables || [];
        if (!mod) {
            return tables;
        }
        return tables.map((table) => {
            let rows = Array.isArray(table.rows) ? [...table.rows] : [];
            if (segmentFilter !== 'all') {
                rows = rows.filter((r) => (r.segment || 'other') === segmentFilter);
            }
            if (mod.module_key === 'custom_source_search' && customRuleKeyFilter !== 'all') {
                if (table.key === 'custom_search_results' || table.key === 'custom_search_rule_summaries') {
                    rows = rows.filter((r) => r.rule_key === customRuleKeyFilter);
                }
            }
            if (mod.module_key === 'custom_extraction') {
                if (customRuleKeyFilter !== 'all') {
                    if (
                        table.key === 'custom_extraction_per_url' ||
                        table.key === 'custom_extraction_rules' ||
                        table.key === 'custom_extraction_duplicates'
                    ) {
                        rows = rows.filter((r) => r.rule_key === customRuleKeyFilter);
                    }
                }
                if (extractionPreset === 'missing_only' && table.key === 'custom_extraction_per_url') {
                    rows = rows.filter((r) => r.missing);
                }
            }
            if (mod.module_key === 'link_metrics') {
                const minRd = Number(linkMetricsMinRd) || 0;
                const minBl = Number(linkMetricsMinBl) || 0;
                rows = rows.filter((r) => {
                    const rd = Number(r.referring_domains ?? 0);
                    const bl = Number(r.backlinks ?? 0);
                    if (rd < minRd || bl < minBl) return false;
                    if (linkMetricsPreset === 'high_tier_only' && r.equity_tier !== 'high') return false;
                    if (linkMetricsPreset === 'medium_or_high' && r.equity_tier !== 'high' && r.equity_tier !== 'medium') {
                        return false;
                    }
                    return true;
                });
            }
            return { ...table, rows };
        });
    }, [mod, segmentFilter, customRuleKeyFilter, extractionPreset, linkMetricsPreset, linkMetricsMinRd, linkMetricsMinBl]);

    return (
        <div className="space-y-6 rounded-2xl border border-[var(--admin-border)] bg-[var(--admin-surface-2)]/30 p-6">
            <h3 className="text-lg font-semibold text-[var(--admin-text)]">{mod.module_title}</h3>

            {mod.filters?.segments?.length ? (
                <div className="flex flex-wrap items-center gap-2">
                    <span className="text-sm text-[var(--admin-text-muted)]">Segment:</span>
                    <select
                        value={segmentFilter}
                        onChange={(e) => setSegmentFilter(e.target.value)}
                        className="rounded-lg border border-[var(--admin-border)] bg-[var(--admin-surface)] px-3 py-1.5 text-sm text-[var(--admin-text)]"
                    >
                        <option value="all">All</option>
                        {mod.filters.segments.map((segment) => (
                            <option key={segment} value={segment}>
                                {segment}
                            </option>
                        ))}
                    </select>
                </div>
            ) : null}

            {mod.module_key === 'js_rendering' && mod.filters?.presets?.length ? (
                <div className="flex flex-wrap items-center gap-2">
                    <span className="text-sm text-[var(--admin-text-muted)]">Filter:</span>
                    <button
                        type="button"
                        onClick={() => setJsRenderPreset('all')}
                        className={`rounded-lg px-3 py-1.5 text-sm ${
                            jsRenderPreset === 'all' ? 'bg-[#2F6BFF] text-white' : 'bg-[var(--admin-surface-2)] text-[var(--admin-text-muted)]'
                        }`}
                    >
                        All
                    </button>
                    {mod.filters.presets.map((preset) => (
                        <button
                            type="button"
                            key={preset.key}
                            onClick={() => setJsRenderPreset(preset.key)}
                            className={`rounded-lg px-3 py-1.5 text-sm ${
                                jsRenderPreset === preset.key ? 'bg-[#2F6BFF] text-white' : 'bg-[var(--admin-surface-2)] text-[var(--admin-text-muted)]'
                            }`}
                        >
                            {preset.label}
                        </button>
                    ))}
                </div>
            ) : null}
            {mod.module_key === 'spelling_grammar' && mod.filters?.presets?.length ? (
                <div className="flex flex-wrap items-center gap-2">
                    <span className="text-sm text-[var(--admin-text-muted)]">Filter:</span>
                    <button
                        type="button"
                        onClick={() => setSpellingGrammarPreset('all')}
                        className={`rounded-lg px-3 py-1.5 text-sm ${
                            spellingGrammarPreset === 'all' ? 'bg-[#2F6BFF] text-white' : 'bg-[var(--admin-surface-2)] text-[var(--admin-text-muted)]'
                        }`}
                    >
                        All
                    </button>
                    {mod.filters.presets.map((preset) => (
                        <button
                            type="button"
                            key={preset.key}
                            onClick={() => setSpellingGrammarPreset(preset.key)}
                            className={`rounded-lg px-3 py-1.5 text-sm ${
                                spellingGrammarPreset === preset.key ? 'bg-[#2F6BFF] text-white' : 'bg-[var(--admin-surface-2)] text-[var(--admin-text-muted)]'
                            }`}
                        >
                            {preset.label}
                        </button>
                    ))}
                </div>
            ) : null}
            {(mod.module_key === 'custom_source_search' || mod.module_key === 'custom_extraction') && mod.filters?.rule_keys?.length ? (
                <div className="flex flex-wrap items-center gap-2">
                    <span className="text-sm text-[var(--admin-text-muted)]">Rule:</span>
                    <select
                        value={customRuleKeyFilter}
                        onChange={(e) => setCustomRuleKeyFilter(e.target.value)}
                        className="rounded-lg border border-[var(--admin-border)] bg-[var(--admin-surface)] px-3 py-1.5 text-sm text-[var(--admin-text)]"
                    >
                        <option value="all">All rules</option>
                        {mod.filters.rule_keys.map((key) => (
                            <option key={key} value={key}>
                                {key}
                            </option>
                        ))}
                    </select>
                </div>
            ) : null}
            {mod.module_key === 'custom_extraction' && mod.filters?.presets?.length ? (
                <div className="flex flex-wrap items-center gap-2">
                    <span className="text-sm text-[var(--admin-text-muted)]">Rows:</span>
                    <button
                        type="button"
                        onClick={() => setExtractionPreset('all')}
                        className={`rounded-lg px-3 py-1.5 text-sm ${
                            extractionPreset === 'all' ? 'bg-[#2F6BFF] text-white' : 'bg-[var(--admin-surface-2)] text-[var(--admin-text-muted)]'
                        }`}
                    >
                        All
                    </button>
                    {mod.filters.presets.map((preset) => (
                        <button
                            type="button"
                            key={preset.key}
                            onClick={() => setExtractionPreset(preset.key)}
                            className={`rounded-lg px-3 py-1.5 text-sm ${
                                extractionPreset === preset.key ? 'bg-[#2F6BFF] text-white' : 'bg-[var(--admin-surface-2)] text-[var(--admin-text-muted)]'
                            }`}
                        >
                            {preset.label}
                        </button>
                    ))}
                </div>
            ) : null}
            {mod.module_key === 'link_metrics' ? (
                <div className="flex flex-col gap-3 rounded-xl border border-[var(--admin-border)] bg-[var(--admin-surface)] p-4">
                    <div className="flex flex-wrap items-center gap-2">
                        <span className="text-sm text-[var(--admin-text-muted)]">Equity:</span>
                        {(mod.filters?.presets || []).map((preset) => (
                            <button
                                type="button"
                                key={preset.key}
                                onClick={() => setLinkMetricsPreset(preset.key)}
                                className={`rounded-lg px-3 py-1.5 text-sm ${
                                    linkMetricsPreset === preset.key ? 'bg-[#2F6BFF] text-white' : 'bg-[var(--admin-surface-2)] text-[var(--admin-text-muted)]'
                                }`}
                            >
                                {preset.label}
                            </button>
                        ))}
                    </div>
                    <div className="flex flex-wrap items-center gap-3">
                        <label className="flex items-center gap-2 text-sm text-[var(--admin-text-muted)]">
                            Min referring domains
                            <input
                                type="number"
                                min={0}
                                className="w-24 rounded-lg border border-[var(--admin-border)] bg-[var(--admin-surface)] px-2 py-1 text-[var(--admin-text)]"
                                value={linkMetricsMinRd}
                                onChange={(e) => setLinkMetricsMinRd(e.target.value)}
                            />
                        </label>
                        <label className="flex items-center gap-2 text-sm text-[var(--admin-text-muted)]">
                            Min backlinks
                            <input
                                type="number"
                                min={0}
                                className="w-24 rounded-lg border border-[var(--admin-border)] bg-[var(--admin-surface)] px-2 py-1 text-[var(--admin-text)]"
                                value={linkMetricsMinBl}
                                onChange={(e) => setLinkMetricsMinBl(e.target.value)}
                            />
                        </label>
                        {mod.filters?.issue_types?.length ? (
                            <label className="flex items-center gap-2 text-sm text-[var(--admin-text-muted)]">
                                Issue type
                                <select
                                    value={linkMetricsIssueType}
                                    onChange={(e) => setLinkMetricsIssueType(e.target.value)}
                                    className="rounded-lg border border-[var(--admin-border)] bg-[var(--admin-surface)] px-2 py-1 text-[var(--admin-text)]"
                                >
                                    <option value="all">All</option>
                                    {mod.filters.issue_types.map((t) => (
                                        <option key={t} value={t}>
                                            {t}
                                        </option>
                                    ))}
                                </select>
                            </label>
                        ) : null}
                    </div>
                </div>
            ) : null}

            {mod.module_key === 'spelling_grammar' ? (
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
                    <MetricCard label="Pages with issues" value={formatNumber(mod.card?.affected_urls)} />
                    <MetricCard label="Total issues" value={formatNumber(mod.card?.overview_count)} />
                    <MetricCard label="Highest-confidence issues" value={formatNumber(mod.card?.high_confidence_issues)} />
                    <MetricCard label="Warning" value={formatNumber(mod.severity_counts?.warning)} />
                    <MetricCard label="Info" value={formatNumber(mod.severity_counts?.info)} />
                </div>
            ) : (
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
                    <MetricCard label="Overview Count" value={formatNumber(mod.card?.overview_count)} />
                    <MetricCard label="Affected URLs" value={formatNumber(mod.card?.affected_urls)} />
                    <MetricCard label="Critical" value={formatNumber(mod.severity_counts?.critical)} />
                    <MetricCard label="Warning" value={formatNumber(mod.severity_counts?.warning)} />
                    <MetricCard label="Info" value={formatNumber(mod.severity_counts?.info)} />
                </div>
            )}

            <DataTable
                title="Grouped Issues"
                rows={filteredModuleIssues}
                columns={
                    mod.module_key === 'link_metrics'
                        ? [
                              { key: 'severity', label: 'Severity', render: (v) => <IssueBadge severity={v || 'info'} /> },
                              {
                                  key: 'details_json',
                                  label: 'Equity',
                                  render: (_v, row) => {
                                      const eq = row.details_json?.link_equity || {};
                                      return (
                                          <span className="text-xs">
                                              <span className="font-semibold uppercase text-[var(--admin-text-muted)]">{eq.tier || '—'}</span>
                                              {' · RD '}
                                              {formatNumber(eq.referring_domains ?? 0)}
                                              {' · BL '}
                                              {formatNumber(eq.backlinks ?? 0)}
                                          </span>
                                      );
                                  },
                              },
                              { key: 'module_key', label: 'Module' },
                              { key: 'issue_type', label: 'Issue type' },
                              { key: 'url', label: 'URL' },
                              { key: 'score_penalty', label: 'Penalty', render: (v) => formatNumber(v) },
                              { key: 'message', label: 'Message' },
                          ]
                        : mod.module_key === 'js_rendering'
                          ? [
                                { key: 'severity', label: 'Severity', render: (v) => <IssueBadge severity={v || 'info'} /> },
                                {
                                    key: 'details_json',
                                    label: 'Diff type',
                                    render: (_v, row) => row.details_json?.diff_type || row.issue_type || '—',
                                },
                                { key: 'url', label: 'URL' },
                                { key: 'message', label: 'Message' },
                                {
                                    key: 'details_json',
                                    label: 'Raw vs rendered',
                                    render: (_v, row) => {
                                        const d = row.details_json || {};
                                        return (
                                            <span className="text-xs text-[var(--admin-text-muted)]">
                                                {d.raw != null && d.rendered != null ? 'See CSV/JSON export for full payload' : '—'}
                                            </span>
                                        );
                                    },
                                },
                            ]
                          : mod.module_key === 'spelling_grammar'
                            ? [
                                  { key: 'severity', label: 'Severity', render: (v) => <IssueBadge severity={v || 'info'} /> },
                                  {
                                      key: 'details_json',
                                      label: 'Kind',
                                      render: (_v, row) => row.details_json?.issue_kind || row.issue_type || '—',
                                  },
                                  {
                                      key: 'details_json',
                                      label: 'Text / fix',
                                      render: (_v, row) => {
                                          const d = row.details_json || {};
                                          const fix = d.suggested_correction;
                                          return (
                                              <span className="text-sm">
                                                  <span className="font-medium">{d.issue_text || '—'}</span>
                                                  {fix ? <span className="text-[var(--admin-text-muted)]"> → {fix}</span> : null}
                                              </span>
                                          );
                                      },
                                  },
                                  {
                                      key: 'details_json',
                                      label: 'Confidence',
                                      render: (_v, row) => (row.details_json?.confidence != null ? `${row.details_json.confidence}` : '—'),
                                  },
                                  {
                                      key: 'details_json',
                                      label: 'Context',
                                      render: (_v, row) => (
                                          <span className="text-xs text-[var(--admin-text-muted)]">{row.details_json?.context_snippet || '—'}</span>
                                      ),
                                  },
                                  { key: 'url', label: 'URL' },
                              ]
                            : [
                                  { key: 'severity', label: 'Severity', render: (value) => <IssueBadge severity={value || 'info'} /> },
                                  { key: 'issue_type', label: 'Issue Type' },
                                  { key: 'status', label: 'Status' },
                                  { key: 'url', label: 'URL' },
                                  { key: 'message', label: 'Message' },
                              ]
                }
                emptyText="No issues detected for this module."
            />

            {filteredModuleTables.map((table) => (
                <DataTable
                    key={table.key}
                    title={table.title || 'Table'}
                    rows={Array.isArray(table.rows) ? table.rows : []}
                    columns={
                        Array.isArray(table.rows) && table.rows.length
                            ? Object.keys(table.rows[0]).slice(0, 4).map((key) => ({ key, label: key.replaceAll('_', ' ') }))
                            : [{ key: 'value', label: 'Value' }]
                    }
                />
            ))}

            <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                {(mod.charts || []).map((chart) => (
                    <ModuleChartCard key={chart.key || chart.title} chart={chart} />
                ))}
            </div>

            <Card variant="elevated">
                <h3 className="text-lg font-semibold text-[var(--admin-text)]">Recommended Actions</h3>
                <ul className="mt-3 list-disc space-y-1 pl-5 text-sm text-[var(--admin-text)]">
                    {(mod.card?.recommended_actions || []).map((item, idx) => (
                        <li key={`${mod.module_key}-rec-${idx}`}>{item}</li>
                    ))}
                </ul>
            </Card>

            <div className="flex flex-wrap gap-2">
                <a
                    href={`/audit/${audit.id}/export/modules.csv?module_key=${encodeURIComponent(mod.module_key)}`}
                    className="rounded-lg border border-[var(--admin-border)] px-3 py-2 text-sm text-[var(--admin-text)] hover:bg-[var(--admin-surface-2)]"
                >
                    Export module CSV
                </a>
                <a
                    href={`/audit/${audit.id}/export/modules.json?module_key=${encodeURIComponent(mod.module_key)}`}
                    className="rounded-lg border border-[var(--admin-border)] px-3 py-2 text-sm text-[var(--admin-text)] hover:bg-[var(--admin-surface-2)]"
                >
                    Export module JSON
                </a>
            </div>
        </div>
    );
}

export default function AuditReportView({ audit }) {
    const [activeTab, setActiveTab] = useState('overview');
    const [exportingPdf, exportPdf] = useExportPdf(audit?.id);
    const [cruxData, setCruxData] = useState(audit?.crux || audit?.kpis?.google?.crux || null);
    const [cruxLoading, setCruxLoading] = useState(false);
    const [cruxError, setCruxError] = useState(null);
    const [gscData, setGscData] = useState(audit?.gsc || null);
    const [gscProperties, setGscProperties] = useState([]);
    const [selectedGscSite, setSelectedGscSite] = useState(audit?.gsc?.selected_site_url || audit?.gsc?.site_url || '');
    const [gscLoading, setGscLoading] = useState(false);
    const [gscPropertiesLoading, setGscPropertiesLoading] = useState(false);
    const [gscError, setGscError] = useState(null);
    const [ga4Data, setGa4Data] = useState(audit?.ga4 || null);
    const [ga4Properties, setGa4Properties] = useState([]);
    const [selectedGa4Property, setSelectedGa4Property] = useState(audit?.ga4?.selected_property_id || audit?.ga4?.property_id || '');
    const [ga4Loading, setGa4Loading] = useState(false);
    const [ga4PropertiesLoading, setGa4PropertiesLoading] = useState(false);
    const [ga4Error, setGa4Error] = useState(null);

    if (audit?.branding?.enabled) {
        return <BrandedAuditReportView report={audit?.white_label_report} audit={audit} exportingPdf={exportingPdf} onExportPdf={exportPdf} />;
    }

    const overallScore = audit?.overall_score ?? 0;
    const overallGrade = audit?.overall_grade || 'N/A';
    const scoreTone = getScoreTone(overallScore);
    const issues = audit?.issues || [];
    const criticalIssues = issues.filter((issue) => issue.severity === 'critical');
    const warningIssues = issues.filter((issue) => issue.severity === 'warning');
    const infoIssues = issues.filter((issue) => issue.severity === 'info');

    const kpis = audit?.kpis || {};
    const overview = kpis.overview || {};
    const onPage = kpis.on_page_seo || {};
    const technical = kpis.technical || {};
    const performance = kpis.performance || {};
    const usability = kpis.usability || {};
    const social = kpis.social || {};
    const localSeo = kpis.local_seo || {};
    const techEmail = kpis.tech_email || {};
    const pageData = audit?.page_data || {};
    const psi = audit?.psi || null;
    const ga4 = ga4Data || null;
    const gsc = gscData || null;
    const categoryScores = audit?.category_scores || {};
    const reportModules = audit?.report_modules?.modules || [];
    const moduleOrder = audit?.report_modules?.module_order || [];
    const orderedModules = moduleOrder.length
        ? moduleOrder
            .map((key) => reportModules.find((module) => module.module_key === key))
            .filter(Boolean)
        : reportModules;
    const extendedModulesByTab = useMemo(() => {
        const buckets = { overview: [], onpage: [], technical: [], performance: [], integrations: [] };
        const extended = orderedModules.filter((m) => !CORE_MODULE_KEYS_DUPLICATING_TABS.has(m.module_key));
        for (const m of extended) {
            const tabId = EXTENDED_MODULE_TAB[m.module_key] || 'technical';
            if (buckets[tabId]) {
                buckets[tabId].push(m);
            }
        }
        return buckets;
    }, [orderedModules]);

    const duplicateTitles = asArray(onPage.duplicate_titles_table);
    const missingMeta = asArray(onPage.missing_meta_table);
    const missingH1 = asArray(onPage.missing_h1_table);
    const brokenLinks = asArray(technical.broken_links_examples);
    const redirectChains = asArray(technical.redirect_chains_examples);
    const non200Pages = asArray(technical.non_200_pages);
    const heavyAssets = asArray(performance.heavy_assets);
    const topKeywords = asArray(onPage.top_keywords);
    const mobileOpportunities = asArray(usability.mobile_opportunities);
    const desktopOpportunities = asArray(usability.desktop_opportunities);
    const securityHeaders = asArray(technical.security_headers_list);
    const gscDaily = asArray(gsc?.daily);
    const ga4Daily = asArray(ga4?.daily);
    const gscTrend = gscDaily.map((row, index) => ({
        id: `gsc-trend-${index}`,
        date: row.date || 'N/A',
        clicks: row.clicks ?? 0,
        impressions: row.impressions ?? 0,
    }));
    const ga4Trend = ga4Daily.map((row, index) => ({
        id: `ga4-trend-${index}`,
        date: row.date || 'N/A',
        sessions: row.sessions ?? 0,
        users: row.total_users ?? row.users ?? 0,
        engagement_rate: row.engagement_rate ?? 0,
    }));
    const gscIndexCoverage = gsc?.index_coverage || {};
    const gscIndexCoverageSummary = gscIndexCoverage.summary || {};
    const gscIndexCoverageIssues = asArray(gscIndexCoverage.issues);
    const cruxEntries = ['mobile', 'desktop'].map((mode) => ({ mode, payload: cruxData?.[mode] || null }));
    const priorityFixes = useMemo(() => {
        const severityRank = { critical: 0, warning: 1, info: 2 };
        const effortRank = { low: 0, medium: 1, high: 2 };
        return [...issues]
            .sort((a, b) => {
                const aSeverity = severityRank[a.severity] ?? 3;
                const bSeverity = severityRank[b.severity] ?? 3;
                if (aSeverity !== bSeverity) return aSeverity - bSeverity;
                const aPenalty = Number(a.score_penalty ?? 0);
                const bPenalty = Number(b.score_penalty ?? 0);
                if (aPenalty !== bPenalty) return bPenalty - aPenalty;
                const aAffected = Number(a.affected_count ?? a.sample_urls?.length ?? 0);
                const bAffected = Number(b.affected_count ?? b.sample_urls?.length ?? 0);
                if (aAffected !== bAffected) return bAffected - aAffected;
                const aEffort = effortRank[a.effort] ?? 3;
                const bEffort = effortRank[b.effort] ?? 3;
                return aEffort - bEffort;
            })
            .slice(0, 6);
    }, [issues]);

    const fetchCrux = async () => {
        if (!audit?.id) return;
        setCruxLoading(true);
        setCruxError(null);
        try {
            const res = await fetch(`/audit/${audit.id}/crux`, {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const payload = await res.json();
            if (!res.ok) {
                throw new Error(payload?.error || `Failed to fetch Core Web Vitals (${res.status})`);
            }
            setCruxData(payload?.crux || null);
        } catch (error) {
            setCruxError(error instanceof Error ? error.message : 'Unable to fetch Core Web Vitals data.');
        } finally {
            setCruxLoading(false);
        }
    };

    const runCrux = async () => {
        if (!audit?.id) return;
        setCruxLoading(true);
        setCruxError(null);
        try {
            const res = await fetch(`/audit/${audit.id}/crux/run`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const payload = await res.json();
            if (!res.ok) {
                throw new Error(payload?.error || `Core Web Vitals run failed (${res.status})`);
            }
            setCruxData(payload?.crux || null);
        } catch (error) {
            setCruxError(error instanceof Error ? error.message : 'Unable to run Core Web Vitals report.');
        } finally {
            setCruxLoading(false);
        }
    };

    const fetchGscProperties = async () => {
        if (!audit?.id) return;
        setGscPropertiesLoading(true);
        setGscError(null);
        try {
            const res = await fetch(`/audit-report/${audit.id}/gsc/properties`, {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const payload = await res.json();
            if (!res.ok) {
                throw new Error(payload?.error || `Unable to load Search Console properties (${res.status})`);
            }
            setGscProperties(asArray(payload?.sites));
            if (payload?.selected_site_url) {
                setSelectedGscSite(payload.selected_site_url);
            }
            if (payload?.connected === false) {
                setGscError(payload?.message || 'Search Console is not connected.');
            }
        } catch (error) {
            setGscError(error instanceof Error ? error.message : 'Unable to load Search Console properties.');
        } finally {
            setGscPropertiesLoading(false);
        }
    };

    const saveSelectedGscProperty = async () => {
        if (!audit?.id || !selectedGscSite) return;
        setGscLoading(true);
        setGscError(null);
        try {
            const res = await fetch(`/audit-report/${audit.id}/gsc/property`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ site_url: selectedGscSite }),
            });
            const payload = await res.json();
            if (!res.ok) {
                throw new Error(payload?.error || `Unable to select Search Console property (${res.status})`);
            }
        } catch (error) {
            setGscError(error instanceof Error ? error.message : 'Unable to select Search Console property.');
        } finally {
            setGscLoading(false);
        }
    };

    const syncGscData = async () => {
        if (!audit?.id) return;
        setGscLoading(true);
        setGscError(null);
        try {
            const res = await fetch(`/audit-report/${audit.id}/gsc/sync`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ site_url: selectedGscSite || null }),
            });
            const payload = await res.json();
            if (!res.ok) {
                throw new Error(payload?.error || `Unable to sync Search Console data (${res.status})`);
            }
            setGscData(payload?.gsc || null);
            if (payload?.gsc?.selected_site_url) {
                setSelectedGscSite(payload.gsc.selected_site_url);
            }
        } catch (error) {
            setGscError(error instanceof Error ? error.message : 'Unable to sync Search Console data.');
        } finally {
            setGscLoading(false);
        }
    };

    const fetchGa4Properties = async () => {
        if (!audit?.id) return;
        setGa4PropertiesLoading(true);
        setGa4Error(null);
        try {
            const res = await fetch(`/audit-report/${audit.id}/ga4/properties`, {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const payload = await res.json();
            if (!res.ok) {
                throw new Error(payload?.error || `Unable to load GA4 properties (${res.status})`);
            }
            setGa4Properties(asArray(payload?.properties));
            if (payload?.selected_property_id) {
                setSelectedGa4Property(payload.selected_property_id);
            }
            if (payload?.connected === false) {
                setGa4Error(payload?.message || 'Google Analytics is not connected.');
            }
        } catch (error) {
            setGa4Error(error instanceof Error ? error.message : 'Unable to load GA4 properties.');
        } finally {
            setGa4PropertiesLoading(false);
        }
    };

    const saveSelectedGa4Property = async () => {
        if (!audit?.id || !selectedGa4Property) return;
        setGa4Loading(true);
        setGa4Error(null);
        try {
            const res = await fetch(`/audit-report/${audit.id}/ga4/property`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ property_id: selectedGa4Property }),
            });
            const payload = await res.json();
            if (!res.ok) {
                throw new Error(payload?.error || `Unable to select GA4 property (${res.status})`);
            }
        } catch (error) {
            setGa4Error(error instanceof Error ? error.message : 'Unable to select GA4 property.');
        } finally {
            setGa4Loading(false);
        }
    };

    const syncGa4Data = async () => {
        if (!audit?.id) return;
        setGa4Loading(true);
        setGa4Error(null);
        try {
            const res = await fetch(`/audit-report/${audit.id}/ga4/sync`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ property_id: selectedGa4Property || null }),
            });
            const payload = await res.json();
            if (!res.ok) {
                throw new Error(payload?.error || `Unable to sync GA4 data (${res.status})`);
            }
            setGa4Data(payload?.ga4 || null);
            if (payload?.ga4?.selected_property_id) {
                setSelectedGa4Property(payload.ga4.selected_property_id);
            }
        } catch (error) {
            setGa4Error(error instanceof Error ? error.message : 'Unable to sync GA4 data.');
        } finally {
            setGa4Loading(false);
        }
    };

    useEffect(() => {
        setCruxData(audit?.crux || audit?.kpis?.google?.crux || null);
    }, [audit?.crux, audit?.kpis]);

    useEffect(() => {
        setGscData(audit?.gsc || null);
        setSelectedGscSite(audit?.gsc?.selected_site_url || audit?.gsc?.site_url || '');
    }, [audit?.gsc]);

    useEffect(() => {
        setGa4Data(audit?.ga4 || null);
        setSelectedGa4Property(audit?.ga4?.selected_property_id || audit?.ga4?.property_id || '');
    }, [audit?.ga4]);

    useEffect(() => {
        if (activeTab === 'integrations' && gsc?.connected !== false) {
            fetchGscProperties();
        }
        if (activeTab === 'integrations' && ga4?.connected !== false) {
            fetchGa4Properties();
        }
    }, [activeTab]);

    return (
        <AppLayout header="Audit Report">
            <div className="mx-auto max-w-7xl space-y-6">
                {audit.status === 'failed' && audit.error ? (
                    <div className="flex items-start gap-3 rounded-2xl border border-[#F04438]/30 bg-[#F04438]/10 p-4">
                        <i className="bi bi-x-circle-fill text-xl text-[#F04438]"></i>
                        <div>
                            <p className="font-medium text-[#F04438]">Audit failed</p>
                            <p className="mt-1 text-sm text-[var(--admin-text-muted)]">{audit.error}</p>
                        </div>
                    </div>
                ) : null}

                <div className="rounded-2xl border border-[var(--admin-border)] bg-[var(--admin-surface)] p-8 shadow-[var(--admin-shadow-md)]">
                    <div className="grid grid-cols-1 gap-8 lg:grid-cols-3">
                        <div className="lg:col-span-2">
                            <div className="flex items-start gap-4">
                                <div className="flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-[#2F6BFF] to-[#2457D6] text-white shadow-lg shadow-[#2F6BFF]/30">
                                    <i className="bi bi-globe text-2xl"></i>
                                </div>
                                <div className="min-w-0 flex-1">
                                    <h2 className="break-all text-2xl font-bold text-[var(--admin-text)]">{audit.url}</h2>
                                    <div className="mt-3 flex flex-wrap items-center gap-4 text-sm text-[var(--admin-text-muted)]">
                                        <span className="flex items-center gap-1.5">
                                            <i className="bi bi-calendar3"></i>
                                            {formatDate(audit.created_at)}
                                        </span>
                                        <span className="flex items-center gap-1.5">
                                            <i className="bi bi-clock"></i>
                                            {audit.finished_at && audit.started_at
                                                ? `Completed in ${Math.max(0, Math.round((new Date(audit.finished_at) - new Date(audit.started_at)) / 1000))}s`
                                                : 'Processing'}
                                        </span>
                                        <span className={`rounded-full px-3 py-1 text-xs font-medium ${
                                            audit.status === 'completed'
                                                ? 'border border-[#12B76A]/30 bg-[#12B76A]/10 text-[#12B76A]'
                                                : audit.status === 'failed'
                                                    ? 'border border-[#F04438]/30 bg-[#F04438]/10 text-[#F04438]'
                                                    : 'border border-[#F79009]/30 bg-[#F79009]/10 text-[#F79009]'
                                        }`}>
                                            {audit.status}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="flex justify-center lg:justify-end">
                            <div className={`flex h-40 w-40 flex-col items-center justify-center rounded-full border-4 ${scoreTone.bg} ${scoreTone.border}`}>
                                <div className={`text-4xl font-bold ${scoreTone.text}`}>{overallScore}</div>
                                <div className="mt-1 text-xs text-[var(--admin-text-dim)]">Overall Score</div>
                                <div className={`mt-1 text-2xl font-bold ${scoreTone.text}`}>{overallGrade}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <MetricCard label="SEO Health" value={`${formatNumber(categoryScores.onpage ?? 0)}/100`} hint="Rules engine" />
                    <MetricCard label="Performance" value={`${formatNumber(categoryScores.performance ?? 0)}/100`} hint="Lab plus asset signals" />
                    <MetricCard label="Technical" value={`${formatNumber(categoryScores.technical ?? 0)}/100`} hint="Crawlability" />
                    <MetricCard label="Issues Found" value={formatNumber(issues.length)} hint={`${criticalIssues.length} critical`} />
                </div>

                <Card variant="elevated">
                    <div className="border-b border-[var(--admin-border)] px-6 pt-6">
                        <div className="flex gap-2 overflow-x-auto pb-4">
                            {tabs.map((tab) => (
                                <button
                                    key={tab.id}
                                    onClick={() => setActiveTab(tab.id)}
                                    className={`flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-medium transition-all ${
                                        activeTab === tab.id
                                            ? 'bg-gradient-to-r from-[#2F6BFF] to-[#2457D6] text-white shadow-md shadow-[#2F6BFF]/20'
                                            : 'bg-[var(--admin-surface-2)] text-[var(--admin-text-muted)] hover:bg-[var(--admin-hover-bg)] hover:text-[var(--admin-text)]'
                                    }`}
                                >
                                    <i className={`bi ${tab.icon}`}></i>
                                    {tab.label}
                                </button>
                            ))}
                        </div>
                    </div>

                    <div className="space-y-6 p-6">
                        {activeTab === 'overview' ? (
                            <>
                                <div className="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                                    <MetricCard label="Pages Crawled" value={formatNumber(overview.pages_crawled_count ?? audit.summary?.pages_scanned)} />
                                    <MetricCard label="Recommendations" value={formatNumber(overview.recommendations_count ?? audit.summary?.total_issues)} />
                                    <MetricCard label="Warnings" value={formatNumber(overview.warnings_count ?? audit.summary?.medium_impact_issues)} />
                                    <MetricCard label="Passed Checks" value={formatNumber(overview.passed_checks)} />
                                </div>

                                <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                                    <MetricCard label="Critical Issues" value={formatNumber(criticalIssues.length)} />
                                    <MetricCard label="Warnings" value={formatNumber(warningIssues.length)} />
                                    <MetricCard label="Opportunities" value={formatNumber(infoIssues.length)} />
                                </div>

                                <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                                    <Card variant="elevated">
                                        <h3 className="text-lg font-semibold text-[var(--admin-text)]">Category Grades</h3>
                                        <div className="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                            {Object.entries(overview.category_grades || {}).map(([key, value]) => (
                                                <div key={key} className="rounded-xl border border-[var(--admin-border)] bg-[var(--admin-surface-2)] p-4">
                                                    <p className="text-sm capitalize text-[var(--admin-text-muted)]">{key.replaceAll('_', ' ')}</p>
                                                    <p className="mt-2 text-2xl font-semibold text-[var(--admin-text)]">{value || 'N/A'}</p>
                                                </div>
                                            ))}
                                        </div>
                                    </Card>

                                    <Card variant="elevated">
                                        <h3 className="text-lg font-semibold text-[var(--admin-text)]">Homepage Snapshot</h3>
                                        <div className="mt-4 space-y-3 text-sm">
                                            <div className="rounded-xl border border-[var(--admin-border)] bg-[var(--admin-surface-2)] p-4">
                                                <p className="text-[var(--admin-text-muted)]">Title</p>
                                                <p className="mt-1 font-medium text-[var(--admin-text)]">{pageData.title || 'Missing'}</p>
                                            </div>
                                            <div className="rounded-xl border border-[var(--admin-border)] bg-[var(--admin-surface-2)] p-4">
                                                <p className="text-[var(--admin-text-muted)]">Meta Description</p>
                                                <p className="mt-1 font-medium text-[var(--admin-text)]">{pageData.meta_description || 'Missing'}</p>
                                            </div>
                                            <div className="grid grid-cols-2 gap-3">
                                                <MetricCard label="Status Code" value={formatNumber(pageData.status_code)} />
                                                <MetricCard label="Word Count" value={formatNumber(pageData.word_count)} />
                                            </div>
                                        </div>
                                    </Card>
                                </div>

                                <DataTable
                                    title="Top Issues"
                                    rows={issues.slice(0, 10)}
                                    columns={[
                                        { key: 'severity', label: 'Severity', render: (value) => <IssueBadge severity={value} /> },
                                        { key: 'title', label: 'Issue' },
                                        { key: 'affected_count', label: 'Affected', render: (value) => formatNumber(value) },
                                        { key: 'recommendation', label: 'Recommendation', render: (value, row) => value || row.description || 'N/A' },
                                    ]}
                                    emptyText="No issues were stored for this audit."
                                />
                                <Card variant="elevated">
                                    <div className="flex items-center justify-between gap-3">
                                        <h3 className="text-lg font-semibold text-[var(--admin-text)]">Priority Fixes</h3>
                                        <span className="text-xs text-[var(--admin-text-dim)]">Sorted by impact and score penalty</span>
                                    </div>
                                    {priorityFixes.length ? (
                                        <div className="mt-4 space-y-3">
                                            {priorityFixes.map((item) => {
                                                const fixSteps = splitFixSteps(item.fix_steps);
                                                return (
                                                    <div key={item.id} className="rounded-xl border border-[var(--admin-border)] bg-[var(--admin-surface-2)] p-4">
                                                        <div className="flex flex-wrap items-center gap-2">
                                                            <IssueBadge severity={item.severity || 'info'} />
                                                            <p className="text-sm font-semibold text-[var(--admin-text)]">{item.title || item.message || item.issue_type || 'SEO Issue'}</p>
                                                        </div>
                                                        <p className="mt-2 text-sm text-[var(--admin-text-muted)]">
                                                            {item.recommendation || item.description || item.message || 'No recommendation was stored for this issue.'}
                                                        </p>
                                                        <div className="mt-3 flex flex-wrap items-center gap-3 text-xs text-[var(--admin-text-dim)]">
                                                            <span>Penalty: {formatNumber(item.score_penalty)}</span>
                                                            <span>Affected: {formatNumber(item.affected_count)}</span>
                                                            <span>Effort: {item.effort || 'N/A'}</span>
                                                        </div>
                                                        {fixSteps.length ? (
                                                            <ul className="mt-3 list-disc space-y-1 pl-5 text-sm text-[var(--admin-text)]">
                                                                {fixSteps.slice(0, 3).map((step, idx) => (
                                                                    <li key={`${item.id}-step-${idx}`}>{step}</li>
                                                                ))}
                                                            </ul>
                                                        ) : null}
                                                    </div>
                                                );
                                            })}
                                        </div>
                                    ) : (
                                        <SectionMessage>No priority fixes generated because no actionable issues were found.</SectionMessage>
                                    )}
                                </Card>
                                <ExtendedModulesInTab audit={audit} modules={extendedModulesByTab.overview} />
                            </>
                        ) : null}

                        {activeTab === 'onpage' ? (
                            <>
                                <div className="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                                    <MetricCard label="Title Length" value={formatNumber(onPage.title_length ?? pageData.title_len)} />
                                    <MetricCard label="Meta Length" value={formatNumber(onPage.meta_description_length ?? pageData.meta_len)} />
                                    <MetricCard label="H1 Present" value={formatBool(onPage.h1_present)} />
                                    <MetricCard label="Missing Alt Images" value={formatNumber(onPage.images_missing_alt_total ?? pageData.images_missing_alt)} />
                                    <MetricCard label="Thin Pages" value={formatNumber(onPage.thin_pages_count)} />
                                    <MetricCard label="Title Duplicates" value={formatNumber(onPage.title_duplicate_count)} />
                                    <MetricCard label="Meta Duplicates" value={formatNumber(onPage.meta_duplicate_count)} />
                                    <MetricCard label="Keyword Consistency" value={formatBool(onPage.keyword_consistency_flag)} />
                                </div>

                                <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                                    <Card variant="elevated">
                                        <h3 className="text-lg font-semibold text-[var(--admin-text)]">Content Signals</h3>
                                        <div className="mt-4 space-y-3 text-sm text-[var(--admin-text)]">
                                            <p><span className="text-[var(--admin-text-muted)]">Canonical:</span> {onPage.canonical_url || pageData.canonical_url || 'Missing'}</p>
                                            <p><span className="text-[var(--admin-text-muted)]">Language:</span> {onPage.lang_declared || 'N/A'}</p>
                                            <p><span className="text-[var(--admin-text-muted)]">Analytics Tool:</span> {onPage.analytics_tool_name || 'Not detected'}</p>
                                            <p><span className="text-[var(--admin-text-muted)]">Schema:</span> {asArray(onPage.schema_types).length ? asArray(onPage.schema_types).join(', ') : 'Not detected'}</p>
                                        </div>
                                    </Card>

                                    <Card variant="elevated">
                                        <h3 className="text-lg font-semibold text-[var(--admin-text)]">Top Keywords</h3>
                                        <div className="mt-4 flex flex-wrap gap-2">
                                            {topKeywords.length ? topKeywords.map((item) => (
                                                <span key={item.keyword} className="rounded-full bg-[#2F6BFF]/10 px-3 py-1.5 text-sm text-[#2F6BFF]">
                                                    {item.keyword}{item.count ? ` (${item.count})` : ''}
                                                </span>
                                            )) : <p className="text-sm text-[var(--admin-text-dim)]">No keyword extraction data stored.</p>}
                                        </div>
                                    </Card>
                                </div>

                                <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                                    <DataTable title="Duplicate Titles" rows={duplicateTitles} columns={[{ key: 'url', label: 'URL' }, { key: 'title', label: 'Title' }]} />
                                    <DataTable title="Missing Meta Descriptions" rows={missingMeta} columns={[{ key: 'url', label: 'URL' }]} />
                                    <DataTable title="Pages Missing H1" rows={missingH1} columns={[{ key: 'url', label: 'URL' }]} />
                                </div>
                                <ExtendedModulesInTab audit={audit} modules={extendedModulesByTab.onpage} />
                            </>
                        ) : null}

                        {activeTab === 'technical' ? (
                            <>
                                <div className="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                                    <MetricCard label="HTTPS Enabled" value={formatBool(technical.https_enabled)} />
                                    <MetricCard label="HTTPS Redirect" value={formatBool(technical.https_redirect_ok)} />
                                    <MetricCard label="robots.txt" value={formatBool(technical.robots_txt_present)} />
                                    <MetricCard label="XML Sitemap" value={formatBool(technical.xml_sitemap_present)} />
                                    <MetricCard label="Broken Links" value={formatNumber(technical.broken_links_count)} />
                                    <MetricCard label="Redirect Chains" value={formatNumber(technical.redirect_chains_count)} />
                                    <MetricCard label="Indexability Issues" value={formatNumber(technical.indexability_issues_count)} />
                                    <MetricCard label="Canonical Present" value={formatNumber(technical.canonical_present_count)} />
                                </div>

                                <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                                    <Card variant="elevated">
                                        <h3 className="text-lg font-semibold text-[var(--admin-text)]">Discovery</h3>
                                        <div className="mt-4 space-y-3 text-sm text-[var(--admin-text)]">
                                            <p><span className="text-[var(--admin-text-muted)]">robots.txt URL:</span> {technical.robots_txt_url || 'Not found'}</p>
                                            <p><span className="text-[var(--admin-text-muted)]">Sitemap URL:</span> {technical.sitemap_url || 'Not found'}</p>
                                            <p><span className="text-[var(--admin-text-muted)]">Blocked by robots:</span> {formatBool(technical.blocked_by_robots)}</p>
                                        </div>
                                    </Card>

                                    <Card variant="elevated">
                                        <h3 className="text-lg font-semibold text-[var(--admin-text)]">Status Distribution</h3>
                                        <div className="mt-4 grid grid-cols-2 gap-3">
                                            <MetricCard label="2xx" value={formatNumber(technical.status_code_distribution?.['2xx'])} />
                                            <MetricCard label="3xx" value={formatNumber(technical.status_code_distribution?.['3xx'])} />
                                            <MetricCard label="4xx" value={formatNumber(technical.status_code_distribution?.['4xx'])} />
                                            <MetricCard label="5xx" value={formatNumber(technical.status_code_distribution?.['5xx'])} />
                                        </div>
                                    </Card>
                                </div>
                                <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                                    <DataTable title="Broken Links" rows={brokenLinks} columns={[{ key: 'from_url', label: 'From' }, { key: 'to_url', label: 'To' }, { key: 'status_code', label: 'Status' }]} />
                                    <DataTable title="Redirect Chains" rows={redirectChains} columns={[{ key: 'from_url', label: 'From' }, { key: 'to_url', label: 'To' }, { key: 'redirect_hops', label: 'Hops' }]} />
                                    <DataTable title="Non-200 Pages" rows={non200Pages} columns={[{ key: 'url', label: 'URL' }, { key: 'status_code', label: 'Status' }]} />
                                </div>

                                <DataTable
                                    title="Security Headers"
                                    rows={securityHeaders}
                                    columns={[
                                        { key: 'header', label: 'Header' },
                                        { key: 'pages_with_header', label: 'Pages With Header' },
                                        { key: 'total_pages', label: 'Total Pages' },
                                    ]}
                                    emptyText="Security header coverage was not stored for this audit."
                                />
                                <ExtendedModulesInTab audit={audit} modules={extendedModulesByTab.technical} />
                            </>
                        ) : null}

                        {activeTab === 'performance' ? (
                            <>
                                <Card variant="elevated">
                                    <div className="flex flex-wrap items-center justify-between gap-3">
                                        <h3 className="text-lg font-semibold text-[var(--admin-text)]">Core Web Vitals (CrUX)</h3>
                                        <div className="flex gap-2">
                                            <button
                                                type="button"
                                                onClick={fetchCrux}
                                                disabled={cruxLoading}
                                                className="rounded-lg border border-[var(--admin-border)] px-3 py-2 text-xs font-medium text-[var(--admin-text)] hover:bg-[var(--admin-surface-2)] disabled:opacity-60"
                                            >
                                                {cruxLoading ? 'Refreshing...' : 'Refresh'}
                                            </button>
                                            <button
                                                type="button"
                                                onClick={runCrux}
                                                disabled={cruxLoading}
                                                className="rounded-lg bg-gradient-to-r from-[#2F6BFF] to-[#2457D6] px-3 py-2 text-xs font-medium text-white disabled:opacity-60"
                                            >
                                                {cruxLoading ? 'Running...' : 'Run CrUX'}
                                            </button>
                                        </div>
                                    </div>
                                    {cruxError ? <SectionMessage>{cruxError}</SectionMessage> : null}
                                    {cruxEntries.some((entry) => entry.payload) ? (
                                        <div className="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
                                            {cruxEntries.map(({ mode, payload }) => {
                                                const kpis = payload?.kpis || {};
                                                return (
                                                    <div key={mode} className="rounded-xl border border-[var(--admin-border)] bg-[var(--admin-surface-2)] p-4">
                                                        <div className="mb-3 flex items-center justify-between gap-2">
                                                            <p className="text-sm font-semibold capitalize text-[var(--admin-text)]">{mode}</p>
                                                            <span className="text-xs text-[var(--admin-text-dim)]">
                                                                {payload?.target_type ? `${payload.target_type} level` : 'No target level'}
                                                            </span>
                                                        </div>
                                                        {payload?.status === 'failed' ? (
                                                            <SectionMessage>{payload?.error || 'CrUX failed for this device profile.'}</SectionMessage>
                                                        ) : payload?.status === 'no_data' ? (
                                                            <SectionMessage>No field data available yet for this profile.</SectionMessage>
                                                        ) : (
                                                            <div className="grid grid-cols-2 gap-3">
                                                                <MetricCard label="LCP (p75)" value={formatMetricSeconds((kpis.lcp_p75_ms ?? NaN) / 1000)} />
                                                                <MetricCard label="INP (p75)" value={formatMetricSeconds((kpis.inp_p75_ms ?? NaN) / 1000)} />
                                                                <MetricCard label="CLS (p75)" value={kpis.cls_p75 ?? 'N/A'} />
                                                                <MetricCard label="TTFB (p75)" value={formatMetricSeconds((kpis.ttfb_p75_ms ?? NaN) / 1000)} />
                                                            </div>
                                                        )}
                                                    </div>
                                                );
                                            })}
                                        </div>
                                    ) : (
                                        <SectionMessage>
                                            Core Web Vitals field data is not available yet. Use Run CrUX after API key setup to fetch real-user metrics.
                                        </SectionMessage>
                                    )}
                                </Card>
                                {psi ? (
                                    <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                                        {['mobile', 'desktop'].map((mode) => {
                                            const run = psi?.[mode];
                                            const fallback = mode === 'mobile' ? pageData?.lighthouse_mobile : pageData?.lighthouse_desktop;
                                            const metrics = normalizePsiMetrics(run, fallback);

                                            return (
                                                <Card key={mode} variant="elevated">
                                                    <h3 className="text-lg font-semibold capitalize text-[var(--admin-text)]">{mode} PageSpeed</h3>
                                                    {run?.status === 'failed' && metrics.score === null ? (
                                                        <SectionMessage>{run.error || 'PageSpeed run failed.'}</SectionMessage>
                                                    ) : (
                                                        <div className="mt-4 grid grid-cols-2 gap-3">
                                                            <MetricCard label="Score" value={formatNumber(metrics.score)} />
                                                            <MetricCard label="LCP" value={formatMetricSeconds(metrics.lcp / 1000)} />
                                                            <MetricCard label="FCP" value={formatMetricSeconds(metrics.fcp / 1000)} />
                                                            <MetricCard label="CLS" value={metrics.cls ?? 'N/A'} />
                                                            <MetricCard label="TBT" value={formatMetricSeconds(metrics.tbt / 1000)} />
                                                            <MetricCard label="TTI" value={formatMetricSeconds(metrics.tti / 1000)} />
                                                        </div>
                                                    )}
                                                </Card>
                                            );
                                        })}
                                    </div>
                                ) : (
                                    <SectionMessage>PageSpeed data was not available for this audit.</SectionMessage>
                                )}

                                <div className="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                                    <MetricCard label="Total Download Size" value={formatMegabytes(performance.total_download_size_mb)} />
                                    <MetricCard label="Total Objects" value={formatNumber(performance.resources_breakdown?.total_objects)} />
                                    <MetricCard label="JS Resources" value={formatNumber(performance.resources_breakdown?.js_resources_count)} />
                                    <MetricCard label="Images" value={formatNumber(performance.resources_breakdown?.images_count)} />
                                </div>

                                <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                                    <DataTable
                                        title="Heavy Assets"
                                        rows={heavyAssets}
                                        columns={[
                                            { key: 'asset_url', label: 'Asset' },
                                            { key: 'type', label: 'Type' },
                                            { key: 'size_kb', label: 'KB' },
                                        ]}
                                    />

                                    <DataTable
                                        title="Optimization Opportunities"
                                        rows={[...mobileOpportunities, ...desktopOpportunities].map((row, index) => ({ ...row, id: `${row.name}-${index}` }))}
                                        columns={[
                                            { key: 'name', label: 'Opportunity' },
                                            { key: 'estimated_savings_sec', label: 'Estimated Savings', render: (value) => formatMetricSeconds(value) },
                                        ]}
                                    />
                                </div>
                                <ExtendedModulesInTab audit={audit} modules={extendedModulesByTab.performance} />
                            </>
                        ) : null}

                        {activeTab === 'integrations' ? (
                            <div className="space-y-6">
                                <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                                    <Card variant="elevated">
                                        <h3 className="text-lg font-semibold text-[var(--admin-text)]">Google Analytics 4</h3>
                                        {ga4?.connected === false ? (
                                            <SectionMessage>{ga4.message || 'GA4 is not connected for this audit.'}</SectionMessage>
                                        ) : (
                                            <div className="mt-4 space-y-4">
                                                <div className="flex flex-wrap items-end gap-3">
                                                    <label className="min-w-[240px] flex-1">
                                                        <span className="mb-1 block text-xs font-medium uppercase tracking-wide text-[var(--admin-text-dim)]">Property</span>
                                                        <select
                                                            value={selectedGa4Property}
                                                            onChange={(e) => setSelectedGa4Property(e.target.value)}
                                                            className="w-full rounded-lg border border-[var(--admin-border)] bg-[var(--admin-surface)] px-3 py-2 text-sm text-[var(--admin-text)]"
                                                        >
                                                            <option value="">{ga4PropertiesLoading ? 'Loading properties...' : 'Select property'}</option>
                                                            {ga4Properties.map((property) => (
                                                                <option key={property.propertyName} value={property.propertyName}>
                                                                    {property.displayName || property.propertyName}
                                                                </option>
                                                            ))}
                                                        </select>
                                                    </label>
                                                    <button
                                                        type="button"
                                                        onClick={saveSelectedGa4Property}
                                                        disabled={!selectedGa4Property || ga4Loading}
                                                        className="rounded-lg border border-[var(--admin-border)] px-3 py-2 text-xs font-medium text-[var(--admin-text)] hover:bg-[var(--admin-surface-2)] disabled:opacity-60"
                                                    >
                                                        Save Property
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={syncGa4Data}
                                                        disabled={ga4Loading}
                                                        className="rounded-lg bg-gradient-to-r from-[#2F6BFF] to-[#2457D6] px-3 py-2 text-xs font-medium text-white disabled:opacity-60"
                                                    >
                                                        {ga4Loading ? 'Syncing...' : 'Sync GA4'}
                                                    </button>
                                                </div>
                                                {ga4Error ? <SectionMessage>{ga4Error}</SectionMessage> : null}
                                                {ga4?.summary ? (
                                                    <div className="grid grid-cols-2 gap-3">
                                                        <MetricCard label="Sessions" value={formatNumber(ga4.summary.total_sessions)} />
                                                        <MetricCard label="Users" value={formatNumber(ga4.summary.total_users)} />
                                                        <MetricCard label="Engagement Rate" value={ga4.summary.avg_engagement_rate !== undefined ? `${ga4.summary.avg_engagement_rate}%` : 'N/A'} />
                                                        <MetricCard label="Period" value={ga4.period || 'N/A'} />
                                                    </div>
                                                ) : (
                                                    <SectionMessage>GA4 data was not captured for this audit.</SectionMessage>
                                                )}
                                            </div>
                                        )}
                                    </Card>

                                    <Card variant="elevated">
                                        <h3 className="text-lg font-semibold text-[var(--admin-text)]">Google Search Console</h3>
                                        {gsc?.connected === false ? (
                                            <SectionMessage>{gsc.message || 'GSC is not connected for this audit.'}</SectionMessage>
                                        ) : (
                                            <div className="mt-4 space-y-4">
                                                <div className="flex flex-wrap items-end gap-3">
                                                    <label className="min-w-[240px] flex-1">
                                                        <span className="mb-1 block text-xs font-medium uppercase tracking-wide text-[var(--admin-text-dim)]">Property</span>
                                                        <select
                                                            value={selectedGscSite}
                                                            onChange={(e) => setSelectedGscSite(e.target.value)}
                                                            className="w-full rounded-lg border border-[var(--admin-border)] bg-[var(--admin-surface)] px-3 py-2 text-sm text-[var(--admin-text)]"
                                                        >
                                                            <option value="">{gscPropertiesLoading ? 'Loading properties...' : 'Select property'}</option>
                                                            {gscProperties.map((site) => (
                                                                <option key={site.siteUrl} value={site.siteUrl}>
                                                                    {site.siteUrl}
                                                                </option>
                                                            ))}
                                                        </select>
                                                    </label>
                                                    <button
                                                        type="button"
                                                        onClick={saveSelectedGscProperty}
                                                        disabled={!selectedGscSite || gscLoading}
                                                        className="rounded-lg border border-[var(--admin-border)] px-3 py-2 text-xs font-medium text-[var(--admin-text)] hover:bg-[var(--admin-surface-2)] disabled:opacity-60"
                                                    >
                                                        Save Property
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={syncGscData}
                                                        disabled={gscLoading}
                                                        className="rounded-lg bg-gradient-to-r from-[#2F6BFF] to-[#2457D6] px-3 py-2 text-xs font-medium text-white disabled:opacity-60"
                                                    >
                                                        {gscLoading ? 'Syncing...' : 'Sync GSC'}
                                                    </button>
                                                </div>
                                                {gscError ? <SectionMessage>{gscError}</SectionMessage> : null}
                                                {gsc?.summary ? (
                                                    <div className="grid grid-cols-2 gap-3">
                                                        <MetricCard label="Clicks" value={formatNumber(gsc.summary.total_clicks)} />
                                                        <MetricCard label="Impressions" value={formatNumber(gsc.summary.total_impressions)} />
                                                        <MetricCard label="CTR" value={gsc.summary.avg_ctr !== undefined ? `${gsc.summary.avg_ctr}%` : 'N/A'} />
                                                        <MetricCard label="Avg Position" value={formatNumber(gsc.summary.avg_position)} />
                                                    </div>
                                                ) : (
                                                    <SectionMessage>Search Console data was not captured for this audit.</SectionMessage>
                                                )}
                                            </div>
                                        )}
                                    </Card>
                                </div>
                                <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                                    <DataTable
                                        title="Top GA4 Pages"
                                        rows={asArray(ga4?.top_pages)}
                                        columns={[
                                            { key: 'page_path', label: 'Page', render: (value, row) => value ?? row.landing_page ?? 'N/A' },
                                            { key: 'sessions', label: 'Sessions', render: (value, row) => formatNumber(value ?? row.views) },
                                            { key: 'total_users', label: 'Users', render: (value, row) => formatNumber(value ?? row.active_users) },
                                        ]}
                                    />

                                    <DataTable
                                        title="Top GSC Queries"
                                        rows={asArray(gsc?.top_queries)}
                                        columns={[
                                            { key: 'query', label: 'Query' },
                                            { key: 'clicks', label: 'Clicks', render: (value) => formatNumber(value) },
                                            { key: 'impressions', label: 'Impressions', render: (value) => formatNumber(value) },
                                            { key: 'position', label: 'Position', render: (value) => formatNumber(value) },
                                        ]}
                                    />
                                </div>
                                <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                                    <DataTable
                                        title="Top GA4 Sources"
                                        rows={asArray(ga4?.top_sources)}
                                        columns={[
                                            { key: 'source_medium', label: 'Source / Medium' },
                                            { key: 'sessions', label: 'Sessions', render: (value) => formatNumber(value) },
                                            { key: 'active_users', label: 'Users', render: (value) => formatNumber(value) },
                                        ]}
                                        emptyText="No source data captured for this audit."
                                    />
                                    <DataTable
                                        title="GA4 Sessions, Users & Engagement Trend"
                                        rows={ga4Trend}
                                        columns={[
                                            { key: 'date', label: 'Date' },
                                            { key: 'sessions', label: 'Sessions', render: (value) => formatNumber(value) },
                                            { key: 'users', label: 'Users', render: (value) => formatNumber(value) },
                                            { key: 'engagement_rate', label: 'Engagement', render: (value) => `${((Number(value) || 0) * 100).toFixed(1)}%` },
                                        ]}
                                        emptyText="No GA4 trend data is available for this period."
                                    />
                                </div>
                                <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                                    <DataTable
                                        title="Top GSC Pages"
                                        rows={asArray(gsc?.top_pages)}
                                        columns={[
                                            { key: 'page', label: 'Page URL' },
                                            { key: 'clicks', label: 'Clicks', render: (value) => formatNumber(value) },
                                            { key: 'impressions', label: 'Impressions', render: (value) => formatNumber(value) },
                                            { key: 'position', label: 'Position', render: (value) => formatNumber(value) },
                                        ]}
                                    />
                                    <DataTable
                                        title="GSC Clicks & Impressions Trend"
                                        rows={gscTrend}
                                        columns={[
                                            { key: 'date', label: 'Date' },
                                            { key: 'clicks', label: 'Clicks', render: (value) => formatNumber(value) },
                                            { key: 'impressions', label: 'Impressions', render: (value) => formatNumber(value) },
                                        ]}
                                        emptyText="No trend data is available for this period."
                                    />
                                </div>
                                <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                                    <Card variant="elevated">
                                        <h3 className="text-lg font-semibold text-[var(--admin-text)]">Index Coverage Summary</h3>
                                        <div className="mt-4 grid grid-cols-2 gap-3">
                                            <MetricCard label="Inspected URLs" value={formatNumber(gscIndexCoverageSummary.inspected_urls)} />
                                            <MetricCard label="Indexed" value={formatNumber(gscIndexCoverageSummary.indexed)} />
                                            <MetricCard label="Not Indexed" value={formatNumber(gscIndexCoverageSummary.not_indexed)} />
                                            <MetricCard label="Errors" value={formatNumber(gscIndexCoverageSummary.errors)} />
                                        </div>
                                    </Card>
                                    <DataTable
                                        title="Index Coverage & Errors"
                                        rows={gscIndexCoverageIssues}
                                        columns={[
                                            { key: 'url', label: 'URL' },
                                            { key: 'severity', label: 'Severity' },
                                            { key: 'coverage_state', label: 'Coverage' },
                                            { key: 'indexing_state', label: 'Indexing State' },
                                            { key: 'message', label: 'Status Message' },
                                        ]}
                                        emptyText="No index coverage inspection results found. Run a GSC sync."
                                    />
                                </div>

                                <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                                    <Card variant="elevated">
                                        <h3 className="text-lg font-semibold text-[var(--admin-text)]">Social Signals</h3>
                                        <div className="mt-4 grid grid-cols-2 gap-3">
                                            <MetricCard label="Open Graph" value={formatBool(social.open_graph_tags_present)} />
                                            <MetricCard label="X Cards" value={formatBool(social.x_cards_present)} />
                                            <MetricCard label="Facebook" value={formatBool(social.facebook_page_linked)} />
                                            <MetricCard label="Instagram" value={formatBool(social.instagram_linked)} />
                                            <MetricCard label="LinkedIn" value={formatBool(social.linkedin_linked)} />
                                            <MetricCard label="YouTube" value={formatBool(social.youtube_channel_linked)} />
                                        </div>
                                    </Card>

                                    <Card variant="elevated">
                                        <h3 className="text-lg font-semibold text-[var(--admin-text)]">Local and Email Signals</h3>
                                        <div className="mt-4 grid grid-cols-2 gap-3">
                                            <MetricCard label="Address Found" value={formatBool(localSeo.address_found)} />
                                            <MetricCard label="Phone Found" value={formatBool(localSeo.phone_found)} />
                                            <MetricCard label="Local Schema" value={formatBool(localSeo.local_business_schema_present)} />
                                            <MetricCard label="SPF" value={formatBool(techEmail.spf_present)} />
                                            <MetricCard label="DMARC" value={formatBool(techEmail.dmarc_present)} />
                                            <MetricCard label="Server" value={techEmail.web_server || 'N/A'} />
                                        </div>
                                    </Card>
                                </div>
                                <ExtendedModulesInTab audit={audit} modules={extendedModulesByTab.integrations} />
                            </div>
                        ) : null}

                    </div>
                </Card>

                <div className="flex gap-3">
                    <button
                        onClick={() => router.visit('/audit-report')}
                        className="flex items-center gap-2 rounded-lg border border-[var(--admin-border)] bg-[var(--admin-surface)] px-6 py-2.5 font-medium text-[var(--admin-text)] transition-colors hover:bg-[var(--admin-surface-2)]"
                    >
                        <i className="bi bi-arrow-left"></i>
                        Back to Audits
                    </button>
                    <button
                        onClick={() => exportPdf()}
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
