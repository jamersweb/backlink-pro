import { Link } from '@inertiajs/react';
import AppLayout from '@/Components/Layout/AppLayout';
import Card from '@/Components/Shared/Card';

export default function CompetitorsShow({ organization, audit, run, summary }) {
    const snapshots = run?.snapshots ?? [];

    return (
        <AppLayout header={`Competitor Run - ${audit?.url || 'Audit'}`}>
            <div className="space-y-6">
                <div className="flex items-center gap-2 text-sm text-gray-600">
                    <Link href="/dashboard" className="hover:text-gray-900">Dashboard</Link>
                    <span>/</span>
                    <span className="text-gray-900">Org: {organization?.name}</span>
                    <span>/</span>
                    <Link
                        href={route('orgs.audits.ai.competitors.index', { organization: organization.id, audit: audit.id })}
                        className="hover:text-gray-900"
                    >
                        Competitors
                    </Link>
                    <span>/</span>
                    <span className="text-gray-900">Run #{run?.id}</span>
                </div>

                <div>
                    <h1 className="text-2xl font-bold text-gray-900">Competitor run #{run?.id}</h1>
                    <p className="text-sm text-gray-500 mt-1">Status: {run?.status} · {snapshots.length} snapshot(s)</p>
                </div>

                <Card>
                    <div className="p-6">
                        <h2 className="text-lg font-semibold text-gray-900 mb-4">Competitor snapshots</h2>
                        {snapshots.length > 0 ? (
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Keyword</th>
                                            <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">URL</th>
                                            <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                                            <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Meta description</th>
                                            <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Word count</th>
                                            <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Page size</th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                        {snapshots.map((s) => (
                                            <tr key={s.id} className="hover:bg-gray-50">
                                                <td className="px-4 py-3 text-sm text-gray-700">{s.keyword || '-'}</td>
                                                <td className="px-4 py-3 text-sm">
                                                    <a href={s.competitor_url} target="_blank" rel="noopener noreferrer" className="text-indigo-600 truncate max-w-xs block">
                                                        {s.competitor_url}
                                                    </a>
                                                </td>
                                                <td className="px-4 py-3 text-sm text-gray-700 max-w-xs truncate">{s.title || '-'}</td>
                                                <td className="px-4 py-3 text-sm text-gray-600 max-w-xs truncate">{s.meta_description || '-'}</td>
                                                <td className="px-4 py-3 text-sm text-gray-600">{s.word_count ?? '-'}</td>
                                                <td className="px-4 py-3 text-sm text-gray-600">{s.page_weight_bytes != null ? `${(s.page_weight_bytes / 1024).toFixed(1)} KB` : '-'}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        ) : (
                            <p className="text-gray-500 text-sm">No snapshots yet or run still in progress.</p>
                        )}
                    </div>
                </Card>

                {summary && (
                    <Card>
                        <div className="p-6">
                            <h2 className="text-lg font-semibold text-gray-900 mb-4">Gap analysis summary</h2>
                            <pre className="text-sm text-gray-700 whitespace-pre-wrap bg-gray-50 p-4 rounded">
                                {typeof summary === 'object' ? JSON.stringify(summary, null, 2) : String(summary)}
                            </pre>
                        </div>
                    </Card>
                )}

                {(!summary || Object.keys(summary || {}).length === 0) && snapshots.length > 0 && (
                    <Card>
                        <div className="p-6">
                            <h2 className="text-lg font-semibold text-gray-900 mb-2">Gaps (placeholder)</h2>
                            <p className="text-sm text-gray-500">Missing keywords/topics vs competitors will appear here when AI summary is enabled.</p>
                        </div>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
