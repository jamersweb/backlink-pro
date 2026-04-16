import { useForm, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useRef, useState } from 'react';
import AppLayout from '@/Components/Layout/AppLayout';
import Card from '@/Components/Shared/Card';
import Button from '@/Components/Shared/Button';
import { COUNTRY_OPTIONS, LANGUAGE_OPTIONS } from './keywordOptions';

const PAGE_SIZE = 20;
const keywordResearchStorePath = '/keyword-research';
const INTENT_OPTIONS = [
    { value: '', label: 'Mixed Intent' },
    { value: 'informational', label: 'Informational' },
    { value: 'commercial', label: 'Commercial' },
    { value: 'transactional', label: 'Transactional' },
    { value: 'local', label: 'Local' },
    { value: 'question_based', label: 'Question Based' },
];
const PATTERN_OPTIONS = [
    { value: '', label: 'Mixed Patterns' },
    { value: 'software_tools', label: 'Software / Tools' },
    { value: 'service_keywords', label: 'Service Keywords' },
    { value: 'pricing_keywords', label: 'Pricing / Cost' },
    { value: 'comparison_keywords', label: 'Comparison / Alternatives' },
    { value: 'local_keywords', label: 'Local Keywords' },
    { value: 'question_keywords', label: 'Questions / Guides' },
];
const MARKET_SCOPE_OPTIONS = [
    { value: '', label: 'Balanced Scope' },
    { value: 'global', label: 'Global Reach' },
    { value: 'country_level', label: 'Country Level' },
    { value: 'city_local', label: 'City / Local' },
    { value: 'smb', label: 'Small Business' },
    { value: 'enterprise', label: 'Enterprise' },
];

const selectShellClasses = 'w-full rounded-2xl border border-[rgba(255,110,64,0.16)] bg-[linear-gradient(180deg,rgba(10,10,10,0.88),rgba(6,6,6,0.96))] px-4 py-3 text-sm text-[#fff7f2] shadow-[inset_0_1px_0_rgba(255,255,255,0.02)] outline-none transition-all focus:border-[rgba(255,110,64,0.38)] focus:ring-2 focus:ring-[rgba(255,110,64,0.14)]';

const contentTypeForDisplay = (item) => {
    const explicitType = String(item.recommended_content_type || '').trim();
    if (explicitType !== '' && explicitType !== 'unknown') {
        return explicitType.replaceAll('_', ' ');
    }

    const intent = String(item.intent || 'unknown');
    if (intent === 'informational') {
        return 'Blog / Guide';
    }
    if (intent === 'commercial') {
        return 'Comparison / Landing Page';
    }
    if (intent === 'transactional') {
        return 'Service / Sales Page';
    }
    if (intent === 'local') {
        return 'Local Landing Page';
    }
    if (intent === 'navigational') {
        return 'Homepage / Feature Page';
    }

    return 'General Content Page';
};

const formatKeywordDensity = (value) => {
    if (value === null || value === undefined || Number.isNaN(Number(value))) {
        return '—';
    }

    return `${Number(value).toFixed(2)}%`;
};

const getSearchVolumeValue = (item) => item.search_volume ?? item.keyword_traffic ?? null;

const formatDensityCell = (item) => {
    if (item.keyword_density_pct !== null && item.keyword_density_pct !== undefined && !Number.isNaN(Number(item.keyword_density_pct))) {
        return formatKeywordDensity(item.keyword_density_pct);
    }

    if (item.density_status === 'failed') {
        return 'Failed';
    }

    return 'Not analyzed';
};

const formatSearchVolumeCell = (item) => {
    const value = getSearchVolumeValue(item);
    if (value !== null && value !== undefined && !Number.isNaN(Number(value))) {
        return Number(value).toLocaleString();
    }

    if (item.metrics_status === 'not_configured') {
        return 'Not configured';
    }
    if (item.metrics_status === 'failed') {
        return 'Failed to fetch';
    }
    if (item.metrics_status === 'pending' || item.metrics_status === 'enriching') {
        return 'Pending';
    }

    return 'N/A';
};

const formatIntentBadge = (intent) => {
    const value = String(intent || 'unknown');

    return {
        informational: 'bg-sky-500/12 text-sky-200 border-sky-400/20',
        commercial: 'bg-violet-500/12 text-violet-200 border-violet-400/20',
        transactional: 'bg-emerald-500/12 text-emerald-200 border-emerald-400/20',
        local: 'bg-amber-500/12 text-amber-100 border-amber-400/20',
        navigational: 'bg-fuchsia-500/12 text-fuchsia-200 border-fuchsia-400/20',
        unknown: 'bg-white/5 text-[rgba(255,240,232,0.72)] border-white/10',
    }[value] || 'bg-white/5 text-[rgba(255,240,232,0.72)] border-white/10';
};

const formatMetricTone = (item) => {
    if (getSearchVolumeValue(item) !== null && getSearchVolumeValue(item) !== undefined) {
        return 'text-emerald-200';
    }
    if (item.metrics_status === 'failed') {
        return 'text-rose-200';
    }
    if (item.metrics_status === 'pending' || item.metrics_status === 'enriching') {
        return 'text-amber-100';
    }

    return 'text-[rgba(255,240,232,0.68)]';
};

export default function SeoRankings({
    organization,
    rankProjects: _rankProjects = [],
    recentRuns: _recentRuns = [],
    selectedRun = null,
    storageReady = true,
}) {
    const { flash } = usePage().props;
    const resultsRef = useRef(null);
    const previousRunIdRef = useRef(selectedRun?.id ?? null);
    const [keywordSearch, setKeywordSearch] = useState('');
    const [intentFilter, setIntentFilter] = useState('all');
    const [sortBy, setSortBy] = useState('relevance');
    const [sortDirection, setSortDirection] = useState('desc');
    const [currentPage, setCurrentPage] = useState(1);

    const form = useForm({
        input_type: 'keyword',
        input_text: '',
        page_url: '',
        locale_country: 'Pakistan',
        locale_language: 'en',
        keyword_focuses: [],
        primary_intent: '',
        keyword_pattern: '',
        market_scope: '',
    });

    const processedItems = useMemo(() => {
        const allItems = selectedRun?.items || [];
        const filtered = allItems.filter((item) => {
            const matchesSearch = keywordSearch.trim() === ''
                || item.keyword.toLowerCase().includes(keywordSearch.trim().toLowerCase())
                || (item.cluster_name || '').toLowerCase().includes(keywordSearch.trim().toLowerCase());
            const matchesIntent = intentFilter === 'all' || (item.intent || 'unknown') === intentFilter;
            return matchesSearch && matchesIntent;
        });

        const sorted = [...filtered].sort((left, right) => {
            const direction = sortDirection === 'asc' ? 1 : -1;

            if (sortBy === 'relevance') {
                const leftScore = ((left.business_relevance_score ?? 0) * 1000) + (left.confidence_score ?? 0);
                const rightScore = ((right.business_relevance_score ?? 0) * 1000) + (right.confidence_score ?? 0);
                return direction * (leftScore - rightScore);
            }
            if (sortBy === 'keyword') {
                return direction * left.keyword.localeCompare(right.keyword);
            }
            if (sortBy === 'intent') {
                return direction * String(left.intent || 'unknown').localeCompare(String(right.intent || 'unknown'));
            }
            if (sortBy === 'content') {
                return direction * String(contentTypeForDisplay(left)).localeCompare(String(contentTypeForDisplay(right)));
            }
            if (sortBy === 'keyword_density_pct') {
                return direction * ((left.keyword_density_pct ?? -1) - (right.keyword_density_pct ?? -1));
            }
            if (sortBy === 'search_volume') {
                return direction * ((getSearchVolumeValue(left) ?? -1) - (getSearchVolumeValue(right) ?? -1));
            }

            return direction * left.keyword.localeCompare(right.keyword);
        });

        return sorted;
    }, [selectedRun, keywordSearch, intentFilter, sortBy, sortDirection]);

    useEffect(() => {
        setCurrentPage(1);
    }, [keywordSearch, intentFilter, sortBy, sortDirection, selectedRun?.id]);

    useEffect(() => {
        if (!selectedRun?.id) {
            previousRunIdRef.current = null;
            return;
        }

        if (previousRunIdRef.current !== selectedRun.id) {
            previousRunIdRef.current = selectedRun.id;
            window.requestAnimationFrame(() => {
                resultsRef.current?.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start',
                });
            });
        }
    }, [selectedRun?.id]);

    const totalPages = Math.max(1, Math.ceil(processedItems.length / PAGE_SIZE));
    const safeCurrentPage = Math.min(currentPage, totalPages);
    const pageStart = (safeCurrentPage - 1) * PAGE_SIZE;
    const pageEnd = pageStart + PAGE_SIZE;
    const paginatedItems = processedItems.slice(pageStart, pageEnd);

    const handleSubmit = (event) => {
        event.preventDefault();
        form.post(keywordResearchStorePath, {
            preserveScroll: true,
        });
    };

    const inputTypeLabel = form.data.input_type === 'keyword'
        ? 'Seed Keyword'
        : form.data.input_type === 'product'
            ? 'Product / Service Details'
            : 'Page Context';

    const inputPlaceholder = form.data.input_type === 'keyword'
        ? 'e.g., gym management software'
        : form.data.input_type === 'product'
            ? 'Describe your product, audience, and value proposition'
            : 'Describe page topic or key points from that page';

    const exportFilteredCsv = () => {
        if (!selectedRun || processedItems.length === 0) {
            return;
        }

        const headers = [
            'Keyword',
            'Intent',
            'Content Type',
            'Keyword Density %',
            'Density Status',
            'Search Volume',
            'Metrics Status',
        ];
        const rows = processedItems.map((item) => ([
            item.keyword,
            item.intent || 'unknown',
            contentTypeForDisplay(item),
            formatDensityCell(item),
            item.density_status || 'not_analyzed',
            formatSearchVolumeCell(item),
            item.metrics_status || 'unavailable',
        ]));

        const csv = [headers, ...rows]
            .map((row) => row.map((cell) => `"${String(cell).replace(/"/g, '""')}"`).join(','))
            .join('\n');

        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `keyword-research-run-${selectedRun.id}.csv`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    };

    return (
        <AppLayout header={`Keyword Research Lab - ${organization?.name || 'Workspace'}`} subtitle="Standalone AI keyword workspace">
            <div className="space-y-6">
                <div className="rounded-3xl border border-[rgba(255,110,64,0.18)] bg-[radial-gradient(circle_at_top_left,rgba(255,110,64,0.14),transparent_48%),linear-gradient(180deg,rgba(22,18,18,0.96),rgba(10,10,10,0.98))] p-6 shadow-[0_22px_55px_rgba(0,0,0,0.45)]">
                    <div className="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <h1 className="text-3xl font-semibold text-[#fff7f2]">Keyword Research</h1>
                            <p className="mt-2 text-sm text-[rgba(255,240,232,0.72)]">
                                Build cleaner keyword sets with better input controls, faster filtering, and a stronger workspace layout.
                            </p>
                        </div>
                    </div>
                </div>

                {flash?.error && (
                    <div className="rounded-2xl border border-rose-400/25 bg-rose-500/10 px-4 py-3 text-sm text-rose-200">
                        {flash.error}
                    </div>
                )}
                {!storageReady && (
                    <div className="rounded-2xl border border-amber-400/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-200">
                        Keyword research tables are not available yet. Run <code>php artisan migrate</code> and reload this page.
                    </div>
                )}

                <Card className="border border-[rgba(255,110,64,0.18)] bg-[linear-gradient(180deg,rgba(22,18,18,0.96),rgba(10,10,10,0.98))]">
                    <div className="p-6">
                        <div className="mb-5">
                            <h2 className="text-lg font-semibold text-[#fff7f2]">Generate New Keyword Set</h2>
                            <p className="mt-2 text-sm text-[rgba(255,240,232,0.62)]">
                                Use compact selectors to shape the keyword direction, then generate a cleaner workspace faster.
                            </p>
                        </div>

                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-6">
                                <div>
                                    <label className="mb-1 block pr-2 text-right text-xs font-semibold uppercase tracking-[0.18em] text-[rgba(255,240,232,0.62)]">Input Type</label>
                                    <select
                                        value={form.data.input_type}
                                        onChange={(event) => form.setData('input_type', event.target.value)}
                                        className={selectShellClasses}
                                    >
                                        <option value="keyword">Single Keyword</option>
                                        <option value="product">Product / Service</option>
                                        <option value="page">Page Detail / URL Context</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="mb-1 block pr-2 text-right text-xs font-semibold uppercase tracking-[0.18em] text-[rgba(255,240,232,0.62)]">Primary Intent</label>
                                    <select
                                        value={form.data.primary_intent}
                                        onChange={(event) => form.setData('primary_intent', event.target.value)}
                                        className={selectShellClasses}
                                    >
                                        {INTENT_OPTIONS.map((option) => (
                                            <option key={option.value || 'mixed'} value={option.value}>
                                                {option.label}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label className="mb-1 block pr-2 text-right text-xs font-semibold uppercase tracking-[0.18em] text-[rgba(255,240,232,0.62)]">Keyword Pattern</label>
                                    <select
                                        value={form.data.keyword_pattern}
                                        onChange={(event) => form.setData('keyword_pattern', event.target.value)}
                                        className={selectShellClasses}
                                    >
                                        {PATTERN_OPTIONS.map((option) => (
                                            <option key={option.value || 'mixed'} value={option.value}>
                                                {option.label}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label className="mb-1 block pr-2 text-right text-xs font-semibold uppercase tracking-[0.18em] text-[rgba(255,240,232,0.62)]">Market Scope</label>
                                    <select
                                        value={form.data.market_scope}
                                        onChange={(event) => form.setData('market_scope', event.target.value)}
                                        className={selectShellClasses}
                                    >
                                        {MARKET_SCOPE_OPTIONS.map((option) => (
                                            <option key={option.value || 'balanced'} value={option.value}>
                                                {option.label}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label className="mb-1 block pr-2 text-right text-xs font-semibold uppercase tracking-[0.18em] text-[rgba(255,240,232,0.62)]">Country</label>
                                    <select
                                        value={form.data.locale_country}
                                        onChange={(event) => form.setData('locale_country', event.target.value)}
                                        className={selectShellClasses}
                                    >
                                        {COUNTRY_OPTIONS.map((country) => (
                                            <option key={country} value={country}>
                                                {country}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label className="mb-1 block pr-2 text-right text-xs font-semibold uppercase tracking-[0.18em] text-[rgba(255,240,232,0.62)]">Language</label>
                                    <select
                                        value={form.data.locale_language}
                                        onChange={(event) => form.setData('locale_language', event.target.value)}
                                        className={selectShellClasses}
                                    >
                                        {LANGUAGE_OPTIONS.map((language) => (
                                            <option key={language.code} value={language.code}>
                                                {language.label}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            </div>

                            <div className="space-y-3">
                                <div>
                                    <div className="mb-1 flex items-center justify-between gap-3">
                                        <label className="block text-xs font-semibold uppercase tracking-[0.18em] text-[rgba(255,240,232,0.62)]">{inputTypeLabel}</label>
                                        <span className="text-[11px] uppercase tracking-[0.16em] text-[rgba(255,240,232,0.42)]">
                                            {form.data.input_text.trim().split(/\s+/).filter(Boolean).length} words
                                        </span>
                                    </div>
                                    <div className="rounded-[24px] border border-[rgba(255,110,64,0.16)] bg-[linear-gradient(180deg,rgba(10,10,10,0.88),rgba(6,6,6,0.96))] p-3 shadow-[inset_0_1px_0_rgba(255,255,255,0.02)]">
                                        <textarea
                                            rows={3}
                                            value={form.data.input_text}
                                            onChange={(event) => form.setData('input_text', event.target.value)}
                                            placeholder={inputPlaceholder}
                                            className="w-full resize-none bg-transparent px-2 py-2 text-sm text-[#fff7f2] outline-none placeholder:text-[rgba(255,240,232,0.42)]"
                                        />
                                    </div>
                                    {form.errors.input_text && <p className="mt-1 text-xs text-rose-300">{form.errors.input_text}</p>}
                                    {form.data.input_type === 'page' && (
                                        <div className="mt-3">
                                            <label className="mb-1 block text-xs font-semibold uppercase tracking-[0.18em] text-[rgba(255,240,232,0.62)]">Page URL (Optional)</label>
                                            <input
                                                type="url"
                                                value={form.data.page_url}
                                                onChange={(event) => form.setData('page_url', event.target.value)}
                                                placeholder="https://example.com/page"
                                                className={selectShellClasses}
                                            />
                                            {form.errors.page_url && <p className="mt-1 text-xs text-rose-300">{form.errors.page_url}</p>}
                                        </div>
                                    )}
                                </div>
                                <div className="rounded-[24px] border border-[rgba(255,110,64,0.16)] bg-[linear-gradient(180deg,rgba(255,255,255,0.03),rgba(255,255,255,0.015))] px-4 py-4 shadow-[inset_0_1px_0_rgba(255,255,255,0.02)]">
                                    <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                        <div className="min-w-0">
                                            <div className="text-[11px] font-semibold uppercase tracking-[0.18em] text-[rgba(255,240,232,0.58)]">Workspace Preset</div>
                                            <div className="mt-2 flex flex-wrap items-center gap-2 text-sm text-[rgba(255,240,232,0.82)]">
                                                {[
                                                    INTENT_OPTIONS.find((option) => option.value === form.data.primary_intent)?.label || 'Mixed Intent',
                                                    PATTERN_OPTIONS.find((option) => option.value === form.data.keyword_pattern)?.label || 'Mixed Patterns',
                                                    MARKET_SCOPE_OPTIONS.find((option) => option.value === form.data.market_scope)?.label || 'Balanced Scope',
                                                ].map((label) => (
                                                    <span
                                                        key={label}
                                                        className="inline-flex items-center rounded-full border border-[rgba(255,255,255,0.08)] bg-black/20 px-3 py-1.5 text-xs font-medium text-[rgba(255,240,232,0.82)]"
                                                    >
                                                        {label}
                                                    </span>
                                                ))}
                                            </div>
                                        </div>

                                        <div className="flex items-center gap-3 lg:min-w-[280px] lg:justify-end">
                                            <div className="hidden h-px flex-1 bg-gradient-to-r from-transparent via-[rgba(255,110,64,0.28)] to-transparent lg:block"></div>
                                            <Button
                                                type="submit"
                                                variant="primary"
                                                size="lg"
                                                disabled={form.processing}
                                                className="w-full rounded-[20px] px-7 py-3 text-base lg:w-auto"
                                            >
                                                {form.processing ? 'Generating...' : 'Generate Keyword'}
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </Card>

                <div ref={resultsRef}>
                <Card className="border border-[rgba(255,110,64,0.18)] bg-[linear-gradient(180deg,rgba(22,18,18,0.96),rgba(10,10,10,0.98))]">
                    <div className="p-6">
                        {!selectedRun ? (
                            <p className="text-sm text-[rgba(255,240,232,0.62)]">Generate a keyword run to view results.</p>
                        ) : (
                            <div className="space-y-4">
                                <div>
                                    <h2 className="text-lg font-semibold text-[#fff7f2]">Run Results</h2>
                                </div>

                                <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
                                    {[
                                        ['Total', selectedRun.stats?.total_keywords ?? 0],
                                        ['Informational', selectedRun.stats?.informational_keywords ?? 0],
                                        ['Commercial', selectedRun.stats?.commercial_keywords ?? 0],
                                        ['Transactional', selectedRun.stats?.transactional_keywords ?? 0],
                                        ['Local', selectedRun.stats?.local_keywords ?? 0],
                                        ['Questions', selectedRun.stats?.question_keywords ?? 0],
                                    ].map(([label, value]) => (
                                        <div key={label} className="rounded-2xl border border-[rgba(255,110,64,0.16)] bg-[rgba(255,255,255,0.02)] p-4 shadow-[inset_0_1px_0_rgba(255,255,255,0.02)]">
                                            <div className="text-[11px] uppercase tracking-[0.18em] text-[rgba(255,240,232,0.62)]">{label}</div>
                                            <div className="mt-2 text-2xl font-semibold text-[#fff7f2]">{value}</div>
                                        </div>
                                    ))}
                                </div>

                                <div className="overflow-hidden rounded-2xl border border-[rgba(255,110,64,0.2)] bg-[rgba(255,255,255,0.02)]">
                                    <div className="flex flex-wrap items-center justify-between gap-3 border-b border-[rgba(255,110,64,0.14)] bg-black/25 px-4 py-4">
                                        <div className="flex flex-1 flex-wrap gap-3">
                                            <input
                                                type="text"
                                                value={keywordSearch}
                                                onChange={(event) => setKeywordSearch(event.target.value)}
                                                placeholder="Search keyword or cluster..."
                                                className="min-w-[220px] flex-1 rounded-xl border border-[rgba(255,110,64,0.18)] bg-black/30 px-4 py-2.5 text-sm text-[#fff7f2] placeholder:text-[rgba(255,240,232,0.45)]"
                                            />
                                            <select
                                                value={intentFilter}
                                                onChange={(event) => setIntentFilter(event.target.value)}
                                                className="rounded-xl border border-[rgba(255,110,64,0.18)] bg-black/30 px-3 py-2.5 text-sm text-[#fff7f2]"
                                            >
                                                <option value="all">All intents</option>
                                                <option value="informational">Informational</option>
                                                <option value="commercial">Commercial</option>
                                                <option value="transactional">Transactional</option>
                                                <option value="navigational">Navigational</option>
                                                <option value="local">Local</option>
                                                <option value="unknown">Unknown</option>
                                            </select>
                                            <select
                                                value={sortBy}
                                                onChange={(event) => setSortBy(event.target.value)}
                                                className="rounded-xl border border-[rgba(255,110,64,0.18)] bg-black/30 px-3 py-2.5 text-sm text-[#fff7f2]"
                                            >
                                                <option value="relevance">Sort: Relevance</option>
                                                <option value="keyword">Sort: Keyword</option>
                                                <option value="intent">Sort: Intent</option>
                                                <option value="content">Sort: Content Type</option>
                                                <option value="keyword_density_pct">Sort: Density %</option>
                                                <option value="search_volume">Sort: Search Volume</option>
                                            </select>
                                            <select
                                                value={sortDirection}
                                                onChange={(event) => setSortDirection(event.target.value)}
                                                className="rounded-xl border border-[rgba(255,110,64,0.18)] bg-black/30 px-3 py-2.5 text-sm text-[#fff7f2]"
                                            >
                                                <option value="desc">Order: Desc</option>
                                                <option value="asc">Order: Asc</option>
                                            </select>
                                        </div>
                                        <Button type="button" variant="secondary" className="rounded-xl" onClick={exportFilteredCsv} disabled={processedItems.length === 0}>
                                            Export CSV
                                        </Button>
                                    </div>
                                    <div className="border-b border-[rgba(255,110,64,0.14)] bg-black/40 px-4 py-3 text-xs text-[rgba(255,240,232,0.72)]">
                                        Showing {processedItems.length === 0 ? 0 : pageStart + 1}-{Math.min(pageEnd, processedItems.length)} of {processedItems.length}
                                    </div>
                                    <div className="max-h-[70vh] overflow-auto">
                                    <table className="min-w-full divide-y divide-[rgba(255,110,64,0.14)]">
                                        <thead className="bg-[rgba(255,110,64,0.08)]">
                                            <tr>
                                                <th className="sticky top-0 z-20 bg-[rgba(18,14,14,0.98)] px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.16em] text-[rgba(255,240,232,0.9)]">Keyword</th>
                                                <th className="sticky top-0 z-20 bg-[rgba(18,14,14,0.98)] px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.16em] text-[rgba(255,240,232,0.9)]">Intent</th>
                                                <th className="sticky top-0 z-20 bg-[rgba(18,14,14,0.98)] px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.16em] text-[rgba(255,240,232,0.9)]">Content Type</th>
                                                <th className="sticky top-0 z-20 bg-[rgba(18,14,14,0.98)] px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.16em] text-[rgba(255,240,232,0.9)]">Density</th>
                                                <th className="sticky top-0 z-20 bg-[rgba(18,14,14,0.98)] px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.16em] text-[rgba(255,240,232,0.9)]">Search Volume</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-[rgba(255,110,64,0.08)] bg-black/10">
                                            {paginatedItems.map((item, index) => (
                                                <tr
                                                    key={item.id}
                                                    className={`${index % 2 === 0 ? 'bg-[rgba(255,255,255,0.01)]' : 'bg-transparent'} transition-colors hover:bg-[rgba(255,110,64,0.06)]`}
                                                >
                                                    <td className="px-4 py-3 text-sm font-medium text-[#fff7f2]">
                                                        <div>{item.keyword}</div>
                                                        <div className="mt-1 text-xs text-[rgba(255,240,232,0.48)]">
                                                            {item.cluster_name || item.pattern_type || 'General cluster'}
                                                        </div>
                                                    </td>
                                                    <td className="px-4 py-3 text-sm">
                                                        <span className={`inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold capitalize ${formatIntentBadge(item.intent)}`}>
                                                            {item.intent || 'unknown'}
                                                        </span>
                                                    </td>
                                                    <td className="px-4 py-3 text-sm text-[rgba(255,240,232,0.82)]">{contentTypeForDisplay(item)}</td>
                                                    <td className="px-4 py-3 text-sm text-[rgba(255,240,232,0.82)]">
                                                        <div>{formatDensityCell(item)}</div>
                                                        <div className="mt-1 text-xs text-[rgba(255,240,232,0.48)]">
                                                            {item.density_status === 'completed' ? 'Page analyzed' : 'Page analysis needed'}
                                                        </div>
                                                    </td>
                                                    <td className={`px-4 py-3 text-sm ${formatMetricTone(item)}`}>
                                                        <div>{formatSearchVolumeCell(item)}</div>
                                                        <div className="mt-1 text-xs text-[rgba(255,240,232,0.48)] capitalize">
                                                            {(item.metrics_status || 'unavailable').replaceAll('_', ' ')}
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))}
                                            {processedItems.length === 0 && (
                                                <tr>
                                                    <td colSpan={5} className="px-4 py-10 text-center text-sm text-[rgba(255,240,232,0.62)]">
                                                        No keywords match current filters.
                                                    </td>
                                                </tr>
                                            )}
                                        </tbody>
                                    </table>
                                    </div>
                                    {processedItems.length > 0 && (
                                        <div className="flex items-center justify-between border-t border-[rgba(255,110,64,0.14)] bg-black/25 px-4 py-3">
                                            <span className="text-xs text-[rgba(255,240,232,0.72)]">
                                                Page {safeCurrentPage} of {totalPages}
                                            </span>
                                            <div className="flex gap-2">
                                                <button
                                                    type="button"
                                                    onClick={() => setCurrentPage((previous) => Math.max(previous - 1, 1))}
                                                    disabled={safeCurrentPage <= 1}
                                                    className="rounded-lg border border-[rgba(255,110,64,0.18)] px-3 py-1.5 text-xs text-[rgba(255,240,232,0.9)] disabled:opacity-50"
                                                >
                                                    Prev
                                                </button>
                                                <button
                                                    type="button"
                                                    onClick={() => setCurrentPage((previous) => Math.min(previous + 1, totalPages))}
                                                    disabled={safeCurrentPage >= totalPages}
                                                    className="rounded-lg border border-[rgba(255,110,64,0.18)] px-3 py-1.5 text-xs text-[rgba(255,240,232,0.9)] disabled:opacity-50"
                                                >
                                                    Next
                                                </button>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </div>
                        )}
                    </div>
                </Card>
                </div>
            </div>
        </AppLayout>
    );
}
