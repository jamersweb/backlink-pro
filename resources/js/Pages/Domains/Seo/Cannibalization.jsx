import { Link, router, usePage } from '@inertiajs/react';
import AppLayout from '@/Components/Layout/AppLayout';
import Card from '@/Components/Shared/Card';
import Button from '@/Components/Shared/Button';

export default function Cannibalization({ domain, candidates, hasGsc }) {
    const { props } = usePage();
    const flash = props.flash || {};

    const runScan = () => {
        router.post(route('domains.seo.cannibalization.scan', domain.id), {}, { preserveScroll: true });
    };

    return (
        <AppLayout header={`Cannibalization - ${domain?.name || domain?.host || 'Domain'}`}>
            <div className="space-y-6">
                <div className="flex items-center gap-2 text-sm text-gray-600">
                    <Link href={route('domains.show', domain.id)} className="hover:text-gray-900">{domain?.name || domain?.host}</Link>
                    <span>/</span>
                    <span className="text-gray-900">SEO</span>
                    <span>/</span>
                    <span className="text-gray-900">Cannibalization</span>
                </div>

                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">Keyword cannibalization</h1>
                        <p className="text-sm text-gray-500 mt-1">Queries where multiple pages compete for the same search term.</p>
                    </div>
                    {hasGsc && (
                        <Button variant="primary" onClick={runScan}>
                            Run scan
                        </Button>
                    )}
                </div>

                {!hasGsc && (
                    <div className="rounded-md bg-amber-50 p-4 text-sm text-amber-800">
                        Connect Google Search Console for this domain to run a cannibalization scan.
                    </div>
                )}

                {flash.success && (
                    <div className="rounded-md bg-green-50 p-4 text-sm text-green-800">
                        {flash.success}
                    </div>
                )}

                <Card>
                    <div className="p-6">
                        <h2 className="text-lg font-semibold text-gray-900 mb-4">Candidates</h2>
                        {candidates && candidates.length > 0 ? (
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Query</th>
                                            <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Pages</th>
                                            <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Impressions</th>
                                            <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Clicks</th>
                                            <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Suggestion</th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                        {candidates.map((row, i) => (
                                            <tr key={i} className="hover:bg-gray-50">
                                                <td className="px-4 py-3 text-sm font-medium text-gray-900">{row.query}</td>
                                                <td className="px-4 py-3 text-sm text-gray-600">
                                                    <ul className="list-disc list-inside space-y-0.5">
                                                        {(row.pages || []).map((p, j) => (
                                                            <li key={j}>
                                                                <span className="truncate max-w-md inline-block" title={p.page_url}>{p.page_url}</span>
                                                                <span className="text-gray-400 ml-1">({p.impressions} imp, {p.clicks} cl)</span>
                                                            </li>
                                                        ))}
                                                    </ul>
                                                </td>
                                                <td className="px-4 py-3 text-sm text-gray-600">{row.total_impressions ?? 0}</td>
                                                <td className="px-4 py-3 text-sm text-gray-600">{row.total_clicks ?? 0}</td>
                                                <td className="px-4 py-3 text-sm text-gray-600 max-w-xs">{row.suggestion ?? '-'}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        ) : (
                            <p className="text-gray-500 text-sm">
                                {hasGsc ? 'No cannibalization candidates found, or run a scan first.' : 'Connect GSC and run a scan to see candidates.'}
                            </p>
                        )}
                    </div>
                </Card>
            </div>
        </AppLayout>
    );
}
