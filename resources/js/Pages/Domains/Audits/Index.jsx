import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Components/Layout/AppLayout';
import Card from '@/Components/Shared/Card';
import Button from '@/Components/Shared/Button';
import RunAuditModal from './Partials/RunAuditModal';

export default function AuditsIndex({ domain, audits }) {
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

    const formatDuration = (startedAt, finishedAt) => {
        if (!startedAt || !finishedAt) return '-';
        const start = new Date(startedAt);
        const end = new Date(finishedAt);
        const seconds = Math.floor((end - start) / 1000);
        if (seconds < 60) return `${seconds}s`;
        const minutes = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return `${minutes}m ${secs}s`;
    };

    return (
        <AppLayout header="Website Analyzer">
            <div className="space-y-6">
                {/* Breadcrumb */}
                <div className="flex items-center gap-2 text-sm text-gray-600">
                    <Link href="/domains" className="hover:text-gray-900">Domains</Link>
                    <span>/</span>
                    <Link href={`/domains/${domain.id}`} className="hover:text-gray-900">{domain.name}</Link>
                    <span>/</span>
                    <span className="text-gray-900">Audits</span>
                </div>

                {/* Header */}
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">Website Analyzer</h1>
                        <p className="text-sm text-gray-500 mt-1">SEO audits and website analysis for {domain.host || domain.name}</p>
                    </div>
                    <Button variant="primary" onClick={() => setShowModal(true)}>
                        ➕ Run New Audit
                    </Button>
                </div>

                {/* Audit History Table */}
                {audits && audits.data && audits.data.length > 0 ? (
                    <>
                        <Card>
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pages</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Issues</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Health Score</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                        {audits.data.map((audit) => {
                                            const summary = audit.summary_json || {};
                                            return (
                                                <tr key={audit.id} className="hover:bg-gray-50">
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        {new Date(audit.created_at).toLocaleString()}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        {getStatusBadge(audit.status)}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        {summary.pages_crawled || 0}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        <div className="flex gap-2">
                                                            {summary.issues_critical > 0 && (
                                                                <span className="text-red-600 font-semibold">{summary.issues_critical}</span>
                                                            )}
                                                            {summary.issues_warning > 0 && (
                                                                <span className="text-yellow-600 font-semibold">{summary.issues_warning}</span>
                                                            )}
                                                            {summary.issues_info > 0 && (
                                                                <span className="text-blue-600">{summary.issues_info}</span>
                                                            )}
                                                            {!summary.issues_critical && !summary.issues_warning && !summary.issues_info && (
                                                                <span className="text-gray-400">0</span>
                                                            )}
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        {audit.health_score !== null ? (
                                                            <div className="flex items-center gap-2">
                                                                <span className={`text-lg font-bold ${
                                                                    audit.health_score >= 80 ? 'text-green-600' :
                                                                    audit.health_score >= 60 ? 'text-yellow-600' :
                                                                    'text-red-600'
                                                                }`}>
                                                                    {audit.health_score}
                                                                </span>
                                                                <div className="w-16 h-2 bg-gray-200 rounded-full overflow-hidden">
                                                                    <div
                                                                        className={`h-full ${
                                                                            audit.health_score >= 80 ? 'bg-green-600' :
                                                                            audit.health_score >= 60 ? 'bg-yellow-600' :
                                                                            'bg-red-600'
                                                                        }`}
                                                                        style={{ width: `${audit.health_score}%` }}
                                                                    />
                                                                </div>
                                                            </div>
                                                        ) : (
                                                            <span className="text-gray-400">-</span>
                                                        )}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        {formatDuration(audit.started_at, audit.finished_at)}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm">
                                                        <Link href={`/domains/${domain.id}/audits/${audit.id}`}>
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
                        {audits.links && audits.links.length > 3 && (
                            <div className="flex items-center justify-center gap-2">
                                {audits.links.map((link, index) => (
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
                                <path strokeLineCap="round" strokeLineJoin="round" strokeWidth={2} d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            <h3 className="mt-2 text-sm font-medium text-gray-900">No audits yet</h3>
                            <p className="mt-1 text-sm text-gray-500">Get started by running your first SEO audit.</p>
                            <div className="mt-6">
                                <Button variant="primary" onClick={() => setShowModal(true)}>
                                    Run First Audit
                                </Button>
                            </div>
                        </div>
                    </Card>
                )}

                {/* Run Audit Modal */}
                {showModal && (
                    <RunAuditModal
                        domain={domain}
                        onClose={() => setShowModal(false)}
                    />
                )}
            </div>
        </AppLayout>
    );
}


