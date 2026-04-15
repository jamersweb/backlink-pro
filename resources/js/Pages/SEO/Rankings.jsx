import { useForm, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import AppLayout from '@/Components/Layout/AppLayout';
import Card from '@/Components/Shared/Card';
import Button from '@/Components/Shared/Button';
import { COUNTRY_OPTIONS, LANGUAGE_OPTIONS } from './keywordOptions';

const PAGE_SIZE = 20;
const keywordResearchStorePath = '/keyword-research';

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

const formatKeywordTraffic = (value) => {
    if (value === null || value === undefined || Number.isNaN(Number(value))) {
        return '—';
    }

    return Number(value).toLocaleString();
};

export default function SeoRankings({
    organization,
    rankProjects: _rankProjects = [],
    recentRuns: _recentRuns = [],
    selectedRun = null,
    storageReady = true,
}) {
    const { flash } = usePage().props;
    const [keywordSearch, setKeywordSearch] = useState('');
    const [intentFilter, setIntentFilter] = useState('all');
    const [sortBy, setSortBy] = useState('keyword');
    const [sortDirection, setSortDirection] = useState('desc');
    const [currentPage, setCurrentPage] = useState(1);

    const form = useForm({
        input_type: 'keyword',
        input_text: '',
        page_url: '',
        locale_country: 'Pakistan',
        locale_language: 'en',
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
            if (sortBy === 'keyword_traffic') {
                return direction * ((left.keyword_traffic ?? -1) - (right.keyword_traffic ?? -1));
            }

            return direction * left.keyword.localeCompare(right.keyword);
        });

        return sorted;
    }, [selectedRun, keywordSearch, intentFilter, sortBy, sortDirection]);

    useEffect(() => {
        setCurrentPage(1);
    }, [keywordSearch, intentFilter, sortBy, sortDirection, selectedRun?.id]);

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
            'Keyword Traffic',
        ];
        const rows = processedItems.map((item) => ([
            item.keyword,
            item.intent || 'unknown',
            contentTypeForDisplay(item),
            item.keyword_density_pct ?? '',
            item.keyword_traffic ?? '',
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
                            <p className="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--admin-primary-light)]/80">AI Keyword Lab</p>
                            <h1 className="mt-2 text-3xl font-semibold text-[#fff7f2]">Keyword Research</h1>
                            <p className="mt-2 text-sm text-[rgba(255,240,232,0.72)]">
                                This section is fully separate from Projects. Add a keyword, product brief, or page context and generate ideas.
                            </p>
                        </div>
                        <div className="inline-flex items-center rounded-full border border-[rgba(255,110,64,0.28)] bg-[rgba(255,110,64,0.12)] px-4 py-2 text-xs font-semibold text-[rgba(255,240,232,0.9)]">
                            <i className="bi bi-stars mr-2"></i>
                            AI Assisted
                        </div>
                    </div>
                </div>

                {flash?.success && (
                    <div className="rounded-2xl border border-emerald-400/25 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">
                        {flash.success}
                    </div>
                )}
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
                        <div className="mb-5 flex items-center justify-between">
                            <h2 className="text-lg font-semibold text-[#fff7f2]">Generate New Keyword Set</h2>
                            <span className="text-xs uppercase tracking-[0.2em] text-[rgba(255,240,232,0.58)]">Independent Flow</span>
                        </div>

                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div>
                                <label className="mb-1 block text-xs font-semibold uppercase tracking-[0.18em] text-[rgba(255,240,232,0.62)]">Input Type</label>
                                <select
                                    value={form.data.input_type}
                                    onChange={(event) => form.setData('input_type', event.target.value)}
                                    className="w-full rounded-xl border border-[rgba(255,110,64,0.2)] bg-black/30 px-3 py-2 text-sm text-[#fff7f2]"
                                >
                                    <option value="keyword">Single Keyword</option>
                                    <option value="product">Product / Service</option>
                                    <option value="page">Page Detail / URL Context</option>
                                </select>
                            </div>

                                <div>
                                    <label className="mb-1 block text-xs font-semibold uppercase tracking-[0.18em] text-[rgba(255,240,232,0.62)]">{inputTypeLabel}</label>
                                    <textarea
                                        rows={4}
                                        value={form.data.input_text}
                                        onChange={(event) => form.setData('input_text', event.target.value)}
                                        placeholder={inputPlaceholder}
                                        className="w-full rounded-xl border border-[rgba(255,110,64,0.2)] bg-black/30 px-3 py-2 text-sm text-[#fff7f2] placeholder:text-[rgba(255,240,232,0.45)]"
                                    />
                                    {form.errors.input_text && <p className="mt-1 text-xs text-rose-300">{form.errors.input_text}</p>}
                                </div>

                                {form.data.input_type === 'page' && (
                                    <div>
                                        <label className="mb-1 block text-xs font-semibold uppercase tracking-[0.18em] text-[rgba(255,240,232,0.62)]">Page URL (Optional)</label>
                                        <input
                                            type="url"
                                            value={form.data.page_url}
                                            onChange={(event) => form.setData('page_url', event.target.value)}
                                            placeholder="https://example.com/page"
                                            className="w-full rounded-xl border border-[rgba(255,110,64,0.2)] bg-black/30 px-3 py-2 text-sm text-[#fff7f2] placeholder:text-[rgba(255,240,232,0.45)]"
                                        />
                                        {form.errors.page_url && <p className="mt-1 text-xs text-rose-300">{form.errors.page_url}</p>}
                                    </div>
                                )}

                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label className="mb-1 block text-xs font-semibold uppercase tracking-[0.18em] text-[rgba(255,240,232,0.62)]">Country</label>
                                        <select
                                            value={form.data.locale_country}
                                            onChange={(event) => form.setData('locale_country', event.target.value)}
                                            className="w-full rounded-xl border border-[rgba(255,110,64,0.2)] bg-black/30 px-3 py-2 text-sm text-[#fff7f2]"
                                        >
                                            {COUNTRY_OPTIONS.map((country) => (
                                                <option key={country} value={country}>
                                                    {country}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                    <div>
                                        <label className="mb-1 block text-xs font-semibold uppercase tracking-[0.18em] text-[rgba(255,240,232,0.62)]">Language</label>
                                        <select
                                            value={form.data.locale_language}
                                            onChange={(event) => form.setData('locale_language', event.target.value)}
                                            className="w-full rounded-xl border border-[rgba(255,110,64,0.2)] bg-black/30 px-3 py-2 text-sm text-[#fff7f2]"
                                        >
                                            {LANGUAGE_OPTIONS.map((language) => (
                                                <option key={language.code} value={language.code}>
                                                    {language.label}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                </div>
                            <div className="flex justify-end pt-2">
                                <Button type="submit" variant="primary" disabled={form.processing}>
                                    {form.processing ? 'Generating Keywords...' : 'Generate Keywords'}
                                </Button>
                            </div>
                        </form>
                    </div>
                </Card>

                <Card className="border border-[rgba(255,110,64,0.18)] bg-[linear-gradient(180deg,rgba(22,18,18,0.96),rgba(10,10,10,0.98))]">
                    <div className="p-6">
                        {!selectedRun ? (
                            <p className="text-sm text-[rgba(255,240,232,0.62)]">Generate a keyword run to view results.</p>
                        ) : (
                            <div className="space-y-4">
                                <div className="flex flex-wrap items-start justify-between gap-4">
                                    <div>
                                        <h2 className="text-lg font-semibold text-[#fff7f2]">Run Results</h2>
                                        <p className="mt-1 text-sm text-[rgba(255,240,232,0.7)]">{selectedRun.summary_text || 'No summary available.'}</p>
                                    </div>
                                    <span className="inline-flex items-center rounded-full border border-[rgba(255,110,64,0.28)] bg-[rgba(255,110,64,0.12)] px-3 py-1 text-xs font-semibold text-[rgba(255,240,232,0.9)]">
                                        {selectedRun.result_count} Keywords
                                    </span>
                                </div>

                                <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                    {[
                                        ['Total', selectedRun.stats?.total_keywords ?? 0],
                                        ['Informational', selectedRun.stats?.informational_keywords ?? 0],
                                        ['Commercial/Txn', selectedRun.stats?.commercial_transactional_keywords ?? 0],
                                    ].map(([label, value]) => (
                                        <div key={label} className="rounded-xl border border-[rgba(255,110,64,0.2)] bg-black/25 p-3">
                                            <div className="text-[11px] uppercase tracking-[0.18em] text-[rgba(255,240,232,0.62)]">{label}</div>
                                            <div className="mt-1 text-xl font-semibold text-[#fff7f2]">{value}</div>
                                        </div>
                                    ))}
                                </div>

                                <div className="overflow-auto rounded-xl border border-[rgba(255,110,64,0.2)] max-h-[70vh]">
                                    <div className="flex flex-wrap items-center justify-between gap-3 border-b border-[rgba(255,110,64,0.2)] bg-black/30 px-3 py-3">
                                        <div className="flex flex-1 flex-wrap gap-3">
                                            <input
                                                type="text"
                                                value={keywordSearch}
                                                onChange={(event) => setKeywordSearch(event.target.value)}
                                                placeholder="Search keyword or cluster..."
                                                className="min-w-[220px] flex-1 rounded-lg border border-[rgba(255,110,64,0.2)] bg-black/30 px-3 py-2 text-sm text-[#fff7f2] placeholder:text-[rgba(255,240,232,0.45)]"
                                            />
                                            <select
                                                value={intentFilter}
                                                onChange={(event) => setIntentFilter(event.target.value)}
                                                className="rounded-lg border border-[rgba(255,110,64,0.2)] bg-black/30 px-3 py-2 text-sm text-[#fff7f2]"
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
                                                className="rounded-lg border border-[rgba(255,110,64,0.2)] bg-black/30 px-3 py-2 text-sm text-[#fff7f2]"
                                            >
                                                <option value="keyword">Sort: Keyword</option>
                                                <option value="intent">Sort: Intent</option>
                                                <option value="content">Sort: Content Type</option>
                                                <option value="keyword_density_pct">Sort: Density %</option>
                                                <option value="keyword_traffic">Sort: Traffic</option>
                                            </select>
                                            <select
                                                value={sortDirection}
                                                onChange={(event) => setSortDirection(event.target.value)}
                                                className="rounded-lg border border-[rgba(255,110,64,0.2)] bg-black/30 px-3 py-2 text-sm text-[#fff7f2]"
                                            >
                                                <option value="desc">Order: Desc</option>
                                                <option value="asc">Order: Asc</option>
                                            </select>
                                        </div>
                                        <Button type="button" variant="secondary" onClick={exportFilteredCsv} disabled={processedItems.length === 0}>
                                            Export CSV
                                        </Button>
                                    </div>
                                    <div className="border-b border-[rgba(255,110,64,0.2)] bg-black/90 px-3 py-2 text-xs text-[rgba(255,240,232,0.72)]">
                                        Showing {processedItems.length === 0 ? 0 : pageStart + 1}-{Math.min(pageEnd, processedItems.length)} of {processedItems.length}
                                    </div>
                                    <table className="min-w-full divide-y divide-[rgba(255,110,64,0.2)]">
                                        <thead className="bg-[rgba(255,110,64,0.1)]">
                                            <tr>
                                                <th className="sticky top-0 z-20 bg-[rgba(255,110,64,0.95)] px-3 py-2 text-left text-xs font-semibold text-[rgba(255,240,232,0.95)]">Keyword</th>
                                                <th className="sticky top-0 z-20 bg-[rgba(255,110,64,0.95)] px-3 py-2 text-left text-xs font-semibold text-[rgba(255,240,232,0.95)]">Intent</th>
                                                <th className="sticky top-0 z-20 bg-[rgba(255,110,64,0.95)] px-3 py-2 text-left text-xs font-semibold text-[rgba(255,240,232,0.95)]">Content Type</th>
                                                <th className="sticky top-0 z-20 bg-[rgba(255,110,64,0.95)] px-3 py-2 text-left text-xs font-semibold text-[rgba(255,240,232,0.95)]">Keyword Density %</th>
                                                <th className="sticky top-0 z-20 bg-[rgba(255,110,64,0.95)] px-3 py-2 text-left text-xs font-semibold text-[rgba(255,240,232,0.95)]">Keyword Traffic</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-[rgba(255,110,64,0.14)] bg-black/20">
                                            {paginatedItems.map((item) => (
                                                <tr key={item.id}>
                                                    <td className="px-3 py-2 text-sm font-medium text-[#fff7f2]">{item.keyword}</td>
                                                    <td className="px-3 py-2 text-sm text-[rgba(255,240,232,0.82)]">{item.intent || 'unknown'}</td>
                                                    <td className="px-3 py-2 text-sm text-[rgba(255,240,232,0.82)]">{contentTypeForDisplay(item)}</td>
                                                    <td className="px-3 py-2 text-sm text-[rgba(255,240,232,0.82)]">{formatKeywordDensity(item.keyword_density_pct)}</td>
                                                    <td className="px-3 py-2 text-sm text-[rgba(255,240,232,0.82)]">{formatKeywordTraffic(item.keyword_traffic)}</td>
                                                </tr>
                                            ))}
                                            {processedItems.length === 0 && (
                                                <tr>
                                                    <td colSpan={5} className="px-3 py-6 text-center text-sm text-[rgba(255,240,232,0.62)]">
                                                        No keywords match current filters.
                                                    </td>
                                                </tr>
                                            )}
                                        </tbody>
                                    </table>
                                    {processedItems.length > 0 && (
                                        <div className="flex items-center justify-between border-t border-[rgba(255,110,64,0.2)] bg-black/20 px-3 py-3">
                                            <span className="text-xs text-[rgba(255,240,232,0.72)]">
                                                Page {safeCurrentPage} of {totalPages}
                                            </span>
                                            <div className="flex gap-2">
                                                <button
                                                    type="button"
                                                    onClick={() => setCurrentPage((previous) => Math.max(previous - 1, 1))}
                                                    disabled={safeCurrentPage <= 1}
                                                    className="rounded border border-[rgba(255,110,64,0.2)] px-3 py-1 text-xs text-[rgba(255,240,232,0.9)] disabled:opacity-50"
                                                >
                                                    Prev
                                                </button>
                                                <button
                                                    type="button"
                                                    onClick={() => setCurrentPage((previous) => Math.min(previous + 1, totalPages))}
                                                    disabled={safeCurrentPage >= totalPages}
                                                    className="rounded border border-[rgba(255,110,64,0.2)] px-3 py-1 text-xs text-[rgba(255,240,232,0.9)] disabled:opacity-50"
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
        </AppLayout>
    );
}
