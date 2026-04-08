import { useState } from 'react';
import { Link, router, useForm } from '@inertiajs/react';
import { usePage } from '@inertiajs/react';
import AppLayout from '@/Components/Layout/AppLayout';
import Card from '@/Components/Shared/Card';
import Button from '@/Components/Shared/Button';

const MAX_COMPETITOR_URLS = 5;
const MIN_KEYWORDS = 1;
const MAX_KEYWORDS = 10;

export default function AuditsCompetitors({ organization, audit, runs }) {
    const { props } = usePage();
    const flash = props.flash || {};
    const [keywordInput, setKeywordInput] = useState('');
    const [competitorUrlInputs, setCompetitorUrlInputs] = useState(['', '', '', '', '']);

    const form = useForm({
        keywords: [],
        country: 'us',
        competitor_urls: [],
    });

    const addKeyword = () => {
        const k = (keywordInput || '').trim();
        if (!k || form.data.keywords.length >= MAX_KEYWORDS) return;
        if (form.data.keywords.includes(k)) return;
        form.setData('keywords', [...form.data.keywords, k]);
        setKeywordInput('');
    };

    const removeKeyword = (index) => {
        form.setData('keywords', form.data.keywords.filter((_, i) => i !== index));
    };

    const setCompetitorUrl = (index, value) => {
        const next = [...competitorUrlInputs];
        next[index] = value;
        setCompetitorUrlInputs(next);
    };

    const competitorUrlsToSubmit = competitorUrlInputs
        .map((u) => (u || '').trim())
        .filter(Boolean);

    const handleSubmit = (e) => {
        e.preventDefault();
        form.setData('competitor_urls', competitorUrlsToSubmit);
        form.post(route('orgs.audits.ai.competitors.store', {
            organization: organization.id,
            audit: audit.id,
        }), {
            preserveScroll: true,
            onSuccess: () => {
                setCompetitorUrlInputs(['', '', '', '', '']);
            },
        });
    };

    const getStatusBadge = (status) => {
        const colors = {
            queued: 'bg-gray-100 text-gray-800',
            running: 'bg-blue-100 text-blue-800',
            completed: 'bg-green-100 text-green-800',
            failed: 'bg-red-100 text-red-800',
        };
        return (
            <span className={`px-2 py-1 text-xs font-semibold rounded-full ${colors[status] || colors.queued}`}>
                {status}
            </span>
        );
    };

    return (
        <AppLayout header={`Competitor Benchmark - ${audit?.url || 'Audit'}`}>
            <div className="space-y-6">
                {/* Breadcrumb */}
                <div className="flex items-center gap-2 text-sm text-gray-600">
                    <Link href="/dashboard" className="hover:text-gray-900">Dashboard</Link>
                    <span>/</span>
                    <span className="text-gray-900">Org: {organization?.name}</span>
                    <span>/</span>
                    <span className="text-gray-900">Competitors</span>
                </div>

                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">Competitor Gap Analysis</h1>
                        <p className="text-sm text-gray-500 mt-1">Audit URL: {audit?.url}</p>
                    </div>
                </div>

                {flash.success && (
                    <div className="rounded-md bg-green-50 p-4 text-sm text-green-800">
                        {flash.success}
                    </div>
                )}

                {/* New run form */}
                <Card>
                    <div className="p-6">
                        <h2 className="text-lg font-semibold text-gray-900 mb-4">New competitor run</h2>
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Keywords (1–10)</label>
                                <div className="flex gap-2 flex-wrap items-center">
                                    {form.data.keywords.map((k, i) => (
                                        <span
                                            key={i}
                                            className="inline-flex items-center gap-1 px-2 py-1 rounded bg-gray-100 text-sm"
                                        >
                                            {k}
                                            <button
                                                type="button"
                                                onClick={() => removeKeyword(i)}
                                                className="text-gray-500 hover:text-red-600"
                                                aria-label="Remove"
                                            >
                                                ×
                                            </button>
                                        </span>
                                    ))}
                                    {form.data.keywords.length < MAX_KEYWORDS && (
                                        <>
                                            <input
                                                type="text"
                                                value={keywordInput}
                                                onChange={(e) => setKeywordInput(e.target.value)}
                                                onKeyDown={(e) => e.key === 'Enter' && (e.preventDefault(), addKeyword())}
                                                placeholder="Add keyword"
                                                className="rounded border border-gray-300 px-2 py-1 text-sm w-40"
                                            />
                                            <Button type="button" variant="outline" onClick={addKeyword}>
                                                Add
                                            </Button>
                                        </>
                                    )}
                                </div>
                                {form.errors.keywords && (
                                    <p className="mt-1 text-sm text-red-600">{form.errors.keywords}</p>
                                )}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Competitor URLs (1–5, optional)
                                </label>
                                <p className="text-xs text-gray-500 mb-2">
                                    Enter full URLs to compare title, meta, and content.
                                </p>
                                <div className="space-y-2">
                                    {[0, 1, 2, 3, 4].map((i) => (
                                        <input
                                            key={i}
                                            type="url"
                                            value={competitorUrlInputs[i] || ''}
                                            onChange={(e) => setCompetitorUrl(i, e.target.value)}
                                            placeholder={`Competitor URL ${i + 1}`}
                                            className="w-full rounded border border-gray-300 px-3 py-2 text-sm"
                                        />
                                    ))}
                                </div>
                                {form.errors.competitor_urls && (
                                    <p className="mt-1 text-sm text-red-600">{form.errors.competitor_urls}</p>
                                )}
                            </div>

                            <div className="flex gap-2">
                                <Button type="submit" variant="primary" disabled={form.processing || form.data.keywords.length < MIN_KEYWORDS}>
                                    {form.processing ? 'Starting…' : 'Start benchmark'}
                                </Button>
                            </div>
                        </form>
                    </div>
                </Card>

                {/* Runs list */}
                <Card>
                    <div className="p-6">
                        <h2 className="text-lg font-semibold text-gray-900 mb-4">Runs</h2>
                        {runs && runs.length > 0 ? (
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                            <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Keywords</th>
                                            <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Competitor URLs</th>
                                            <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                            <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Snapshots</th>
                                            <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                        {runs.map((run) => (
                                            <tr key={run.id} className="hover:bg-gray-50">
                                                <td className="px-4 py-3 text-sm text-gray-900">
                                                    {run.created_at ? new Date(run.created_at).toLocaleString() : '-'}
                                                </td>
                                                <td className="px-4 py-3 text-sm text-gray-700">
                                                    {(run.keywords || []).slice(0, 3).join(', ')}
                                                    {(run.keywords || []).length > 3 ? '…' : ''}
                                                </td>
                                                <td className="px-4 py-3 text-sm text-gray-600">
                                                    {(run.competitor_urls || []).length} URL(s)
                                                </td>
                                                <td className="px-4 py-3">{getStatusBadge(run.status)}</td>
                                                <td className="px-4 py-3 text-sm text-gray-600">{run.snapshots_count ?? 0}</td>
                                                <td className="px-4 py-3">
                                                    <Link
                                                        href={route('orgs.audits.ai.competitors.show', {
                                                            organization: organization.id,
                                                            audit: audit.id,
                                                            competitorRun: run.id,
                                                        })}
                                                        className="text-indigo-600 hover:text-indigo-800 text-sm font-medium"
                                                    >
                                                        View
                                                    </Link>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        ) : (
                            <p className="text-gray-500 text-sm">No runs yet. Start a benchmark above.</p>
                        )}
                    </div>
                </Card>
            </div>
        </AppLayout>
    );
}
