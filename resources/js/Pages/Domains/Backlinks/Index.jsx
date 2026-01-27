import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Components/Layout/AppLayout';
import Card from '@/Components/Shared/Card';
import Button from '@/Components/Shared/Button';
import FetchBacklinksModal from './Partials/FetchBacklinksModal';

export default function BacklinksIndex({ domain, runs, latestSummary }) {
    const [showModal, setShowModal] = useState(false);

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
        <AppLayout header="Backlinks">
            <div className="space-y-6">
                {/* Breadcrumb */}
                <div className="flex items-center gap-2 text-sm text-gray-600">
                    <Link href="/domains" className="hover:text-gray-900">Domains</Link>
                    <span>/</span>
                    <Link href={`/domains/${domain.id}`} className="hover:text-gray-900">{domain.name}</Link>
                    <span>/</span>
                    <span className="text-gray-900">Backlinks</span>
                </div>

                {/* Header */}
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">Backlinks</h1>
                        <p className="text-sm text-gray-500 mt-1">Monitor and analyze backlinks for {domain.host || domain.name}</p>
                    </div>
                    <Button variant="primary" onClick={() => setShowModal(true)}>
                        🔍 Fetch Backlinks
                    </Button>
                </div>

                {/* Latest Summary Card */}
                {latestSummary && (
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <Card>
                            <div className="p-4">
                                <p className="text-gray-600 text-xs font-medium mb-1">Total Backlinks</p>
                                <p className="text-2xl font-bold text-gray-900">{latestSummary.total_backlinks || 0}</p>
                            </div>
                        </Card>
                        <Card>
                            <div className="p-4">
                                <p className="text-gray-600 text-xs font-medium mb-1">Ref Domains</p>
                                <p className="text-2xl font-bold text-gray-900">{latestSummary.ref_domains || 0}</p>
                            </div>
                        </Card>
                        <Card>
                            <div className="p-4">
                                <p className="text-gray-600 text-xs font-medium mb-1">Follow / Nofollow</p>
                                <p className="text-2xl font-bold text-gray-900">
                                    {latestSummary.follow || 0} / {latestSummary.nofollow || 0}
                                </p>
                            </div>
                        </Card>
                        <Card>
                            <div className="p-4">
                                <p className="text-gray-600 text-xs font-medium mb-1">Risk Score</p>
                                <div className="flex items-center gap-2">
                                    <span className={`text-2xl font-bold ${
                                        latestSummary.risk_score >= 80 ? 'text-green-600' :
                                        latestSummary.risk_score >= 60 ? 'text-yellow-600' :
                                        'text-red-600'
                                    }`}>
                                        {latestSummary.risk_score || 0}
                                    </span>
                                    <span className="text-xs text-gray-500">(heuristic)</span>
                                </div>
                            </div>
                        </Card>
                    </div>
                )}

                {/* Runs Table */}
                {runs && runs.data && runs.data.length > 0 ? (
                    <>
                        <Card>
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Backlinks</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ref Domains</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">New/Lost</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Provider</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                        {runs.data.map((run) => {
                                            const summary = run.summary_json || {};
                                            return (
                                                <tr key={run.id} className="hover:bg-gray-50">
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        {new Date(run.created_at).toLocaleString()}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        {getStatusBadge(run.status)}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        {summary.total_backlinks || 0}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        {summary.ref_domains || 0}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        <div className="flex gap-2">
                                                            {summary.new_links > 0 && (
                                                                <span className="text-green-600 font-semibold">+{summary.new_links}</span>
                                                            )}
                                                            {summary.lost_links > 0 && (
                                                                <span className="text-red-600 font-semibold">-{summary.lost_links}</span>
                                                            )}
                                                            {!summary.new_links && !summary.lost_links && (
                                                                <span className="text-gray-400">-</span>
                                                            )}
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 capitalize">
                                                        {run.provider}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm">
                                                        <Link href={`/domains/${domain.id}/backlinks/${run.id}`}>
                                                            <Button variant="outline" className="text-xs">View</Button>
                                                        </Link>
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                        </Card>

                        {/* Pagination */}
                        {runs.links && runs.links.length > 3 && (
                            <div className="flex items-center justify-center gap-2">
                                {runs.links.map((link, index) => (
                                    <Link
                                        key={index}
                                        href={link.url || '#'}
                                        className={`px-4 py-2 rounded-lg text-sm font-medium ${
                                            link.active
                                                ? 'bg-gray-900 text-white'
                                                : link.url
                                                ? 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'
                                                : 'bg-gray-100 text-gray-400 cursor-not-allowed'
                                        }`}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ))}
                            </div>
                        )}
                    </>
                ) : (
                    <Card>
                        <div className="text-center py-12">
                            <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLineCap="round" strokeLineJoin="round" strokeWidth={2} d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                            </svg>
                            <h3 className="mt-2 text-sm font-medium text-gray-900">No backlink runs yet</h3>
                            <p className="mt-1 text-sm text-gray-500">Get started by fetching backlinks for this domain.</p>
                            <div className="mt-6">
                                <Button variant="primary" onClick={() => setShowModal(true)}>
                                    Fetch Backlinks
                                </Button>
                            </div>
                        </div>
                    </Card>
                )}

                {/* Fetch Modal */}
                {showModal && (
                    <FetchBacklinksModal
                        domain={domain}
                        onClose={() => setShowModal(false)}
                    />
                )}
            </div>
        </AppLayout>
    );
}


