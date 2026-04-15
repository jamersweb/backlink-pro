import { Link, router, useForm, usePage } from '@inertiajs/react';
import AppLayout from '@/Components/Layout/AppLayout';
import Card from '@/Components/Shared/Card';
import Button from '@/Components/Shared/Button';

export default function SeoRankings({
    organization,
    projects = [],
    recentRuns = [],
    selectedRun = null,
    initialProjectId = null,
}) {
    const { flash } = usePage().props;
    const resolvedInitialProjectId = initialProjectId ?? selectedRun?.project_id ?? '';

    const form = useForm({
        input_type: 'keyword',
        input_text: '',
        page_url: '',
        locale_country: '',
        locale_language: '',
        project_id: resolvedInitialProjectId ? String(resolvedInitialProjectId) : '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        form.post(route('orgs.seo.rankings.store', { organization: organization.id }), {
            preserveScroll: true,
        });
    };

    return (
        <AppLayout header={`Keyword Research - ${organization.name}`}>
            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">Keyword Research</h1>
                    <p className="text-sm text-gray-500 mt-1">
                        Generate keyword ideas from a seed topic, product description, or page context.
                    </p>
                </div>

                {flash?.success && (
                    <div className="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                        {flash.success}
                    </div>
                )}

                {flash?.error && (
                    <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        {flash.error}
                    </div>
                )}

                <Card>
                    <div className="p-6">
                        <h2 className="text-lg font-semibold text-gray-900 mb-4">Generate Keywords</h2>
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Input Type</label>
                                    <select
                                        value={form.data.input_type}
                                        onChange={(e) => form.setData('input_type', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-md"
                                    >
                                        <option value="keyword">Keyword</option>
                                        <option value="product">Product / Service</option>
                                        <option value="page">Page</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Project (optional)</label>
                                    <select
                                        value={form.data.project_id}
                                        onChange={(e) => form.setData('project_id', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-md"
                                    >
                                        <option value="">Select project</option>
                                        {projects.map((project) => (
                                            <option key={project.id} value={project.id}>{project.name}</option>
                                        ))}
                                    </select>
                                    {form.errors.project_id && <p className="mt-1 text-sm text-red-600">{form.errors.project_id}</p>}
                                </div>
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    {form.data.input_type === 'keyword' ? 'Seed Keyword' : 'Description'}
                                </label>
                                <textarea
                                    value={form.data.input_text}
                                    onChange={(e) => form.setData('input_text', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md"
                                    rows={4}
                                    placeholder={
                                        form.data.input_type === 'keyword'
                                            ? 'e.g., gym management software'
                                            : form.data.input_type === 'product'
                                                ? 'Describe your product or service...'
                                                : 'Describe the page topic/context...'
                                    }
                                />
                                {form.errors.input_text && <p className="mt-1 text-sm text-red-600">{form.errors.input_text}</p>}
                            </div>

                            {form.data.input_type === 'page' && (
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Page URL (optional if description provided)</label>
                                    <input
                                        type="url"
                                        value={form.data.page_url}
                                        onChange={(e) => form.setData('page_url', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-md"
                                        placeholder="https://example.com/page"
                                    />
                                    {form.errors.page_url && <p className="mt-1 text-sm text-red-600">{form.errors.page_url}</p>}
                                </div>
                            )}

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Country (optional)</label>
                                    <input
                                        type="text"
                                        value={form.data.locale_country}
                                        onChange={(e) => form.setData('locale_country', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-md"
                                        placeholder="PK"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Language (optional)</label>
                                    <input
                                        type="text"
                                        value={form.data.locale_language}
                                        onChange={(e) => form.setData('locale_language', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-md"
                                        placeholder="en"
                                    />
                                </div>
                            </div>

                            <div className="flex justify-end">
                                <Button type="submit" variant="primary" disabled={form.processing}>
                                    {form.processing ? 'Generating...' : 'Generate Keywords'}
                                </Button>
                            </div>
                        </form>
                    </div>
                </Card>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div>
                        <Card>
                            <div className="p-6">
                                <h2 className="text-lg font-semibold text-gray-900 mb-4">Recent Research Runs</h2>
                                {recentRuns.length === 0 ? (
                                    <p className="text-sm text-gray-500">No research runs yet.</p>
                                ) : (
                                    <div className="space-y-3">
                                        {recentRuns.map((run) => (
                                            <Link
                                                key={run.id}
                                                href={route('orgs.seo.rankings.index', { organization: organization.id, run: run.id })}
                                                className={`block rounded-lg border px-3 py-3 transition ${
                                                    selectedRun?.id === run.id
                                                        ? 'border-gray-900 bg-gray-50'
                                                        : 'border-gray-200 hover:border-gray-300'
                                                }`}
                                            >
                                                <div className="text-xs uppercase text-gray-500">{run.input_type}</div>
                                                <div className="text-sm font-medium text-gray-900 mt-1">{run.seed_preview || '—'}</div>
                                                <div className="text-xs text-gray-500 mt-1">
                                                    {new Date(run.created_at).toLocaleString()} • {run.result_count} keywords
                                                </div>
                                            </Link>
                                        ))}
                                    </div>
                                )}
                            </div>
                        </Card>
                    </div>

                    <div className="lg:col-span-2">
                        <Card>
                            <div className="p-6 space-y-4">
                                {!selectedRun ? (
                                    <p className="text-sm text-gray-500">Select a research run to view saved results.</p>
                                ) : (
                                    <>
                                        <div>
                                            <h2 className="text-lg font-semibold text-gray-900">Research Results</h2>
                                            <p className="text-sm text-gray-600 mt-1">{selectedRun.summary_text || 'No summary available.'}</p>
                                        </div>

                                        <div className="grid grid-cols-2 md:grid-cols-5 gap-3">
                                            {[
                                                ['Total', selectedRun.stats?.total_keywords ?? 0],
                                                ['Clusters', selectedRun.stats?.unique_clusters ?? 0],
                                                ['Informational', selectedRun.stats?.informational_keywords ?? 0],
                                                ['Commercial/Txn', selectedRun.stats?.commercial_transactional_keywords ?? 0],
                                                ['Saved', selectedRun.stats?.saved_keywords ?? 0],
                                            ].map(([label, value]) => (
                                                <div key={label} className="rounded-lg border border-gray-200 p-3">
                                                    <div className="text-xs text-gray-500">{label}</div>
                                                    <div className="text-lg font-semibold text-gray-900">{value}</div>
                                                </div>
                                            ))}
                                        </div>

                                        <div className="overflow-x-auto">
                                            <table className="min-w-full divide-y divide-gray-200">
                                                <thead className="bg-gray-50">
                                                    <tr>
                                                        <th className="px-3 py-2 text-left text-xs font-semibold text-gray-600">Keyword</th>
                                                        <th className="px-3 py-2 text-left text-xs font-semibold text-gray-600">Intent</th>
                                                        <th className="px-3 py-2 text-left text-xs font-semibold text-gray-600">Funnel</th>
                                                        <th className="px-3 py-2 text-left text-xs font-semibold text-gray-600">Cluster</th>
                                                        <th className="px-3 py-2 text-left text-xs font-semibold text-gray-600">Content Type</th>
                                                        <th className="px-3 py-2 text-left text-xs font-semibold text-gray-600">Confidence</th>
                                                        <th className="px-3 py-2 text-left text-xs font-semibold text-gray-600">Business</th>
                                                        <th className="px-3 py-2 text-left text-xs font-semibold text-gray-600">Reason</th>
                                                        <th className="px-3 py-2 text-left text-xs font-semibold text-gray-600">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody className="divide-y divide-gray-100 bg-white">
                                                    {(selectedRun.items || []).map((item) => (
                                                        <tr key={item.id}>
                                                            <td className="px-3 py-2 text-sm font-medium text-gray-900">{item.keyword}</td>
                                                            <td className="px-3 py-2 text-sm text-gray-700">{item.intent || 'unknown'}</td>
                                                            <td className="px-3 py-2 text-sm text-gray-700">{item.funnel_stage || 'unknown'}</td>
                                                            <td className="px-3 py-2 text-sm text-gray-700">{item.cluster_name || '—'}</td>
                                                            <td className="px-3 py-2 text-sm text-gray-700">{item.recommended_content_type || 'unknown'}</td>
                                                            <td className="px-3 py-2 text-sm text-gray-700">{item.confidence_score ?? '—'}</td>
                                                            <td className="px-3 py-2 text-sm text-gray-700">{item.business_relevance_score ?? '—'}</td>
                                                            <td className="px-3 py-2 text-sm text-gray-700 max-w-[250px]">{item.ai_reason || '—'}</td>
                                                            <td className="px-3 py-2 text-sm">
                                                                <button
                                                                    type="button"
                                                                    onClick={() => router.post(
                                                                        route('orgs.seo.rankings.items.toggle-save', { organization: organization.id, item: item.id }),
                                                                        {},
                                                                        { preserveScroll: true }
                                                                    )}
                                                                    className={`px-2 py-1 rounded text-xs font-medium ${
                                                                        item.is_saved
                                                                            ? 'bg-green-100 text-green-800'
                                                                            : 'bg-gray-100 text-gray-700'
                                                                    }`}
                                                                >
                                                                    {item.is_saved ? 'Saved' : 'Save'}
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </div>
                                    </>
                                )}
                            </div>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
