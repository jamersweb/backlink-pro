import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Components/Layout/AppLayout';
import Card from '@/Components/Shared/Card';
import Button from '@/Components/Shared/Button';
import AuditSummaryCards from './Partials/AuditSummaryCards';
import IssuesTable from './Partials/IssuesTable';
import PagesTable from './Partials/PagesTable';

export default function AuditsShow({ domain, audit, pages, issues, issueTypes, filters }) {
    const [activeTab, setActiveTab] = useState(filters.tab || 'issues');

    const handleTabChange = (tab) => {
        setActiveTab(tab);
        router.get(`/domains/${domain.id}/audits/${audit.id}`, { tab }, { preserveState: true, replace: true });
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
        <AppLayout header="Audit Details">
            <div className="space-y-6">
                {/* Breadcrumb */}
                <div className="flex items-center gap-2 text-sm text-gray-600">
                    <Link href="/domains" className="hover:text-gray-900">Domains</Link>
                    <span>/</span>
                    <Link href={`/domains/${domain.id}`} className="hover:text-gray-900">{domain.name}</Link>
                    <span>/</span>
                    <Link href={`/domains/${domain.id}/audits`} className="hover:text-gray-900">Audits</Link>
                    <span>/</span>
                    <span className="text-gray-900">#{audit.id}</span>
                </div>

                {/* Header */}
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">Audit #{audit.id}</h1>
                        <div className="flex items-center gap-2 mt-2">
                            {getStatusBadge(audit.status)}
                            <span className="text-sm text-gray-500">
                                Started: {audit.started_at ? new Date(audit.started_at).toLocaleString() : '-'}
                            </span>
                            {audit.finished_at && (
                                <>
                                    <span className="text-gray-400">•</span>
                                    <span className="text-sm text-gray-500">
                                        Duration: {formatDuration(audit.started_at, audit.finished_at)}
                                    </span>
                                </>
                            )}
                        </div>
                    </div>
                    <div className="flex gap-2">
                        <Link href={`/domains/${domain.id}/audits/${audit.id}/export`}>
                            <Button variant="outline">Export CSV</Button>
                        </Link>
                        <Link href={`/domains/${domain.id}/audits`}>
                            <Button variant="outline">Back to Audits</Button>
                        </Link>
                    </div>
                </div>

                {/* Summary Cards */}
                {audit.status === 'completed' && <AuditSummaryCards audit={audit} />}

                {/* Tabs */}
                <div className="border-b border-gray-200">
                    <nav className="-mb-px flex space-x-8 overflow-x-auto" aria-label="Tabs">
                        {[
                            { id: 'issues', label: 'Issues' },
                            { id: 'pages', label: 'Pages' },
                            { id: 'performance', label: 'Performance' },
                        ].map((tab) => (
                            <button
                                key={tab.id}
                                onClick={() => handleTabChange(tab.id)}
                                className={`
                                    whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm
                                    ${activeTab === tab.id
                                        ? 'border-gray-900 text-gray-900'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                    }
                                `}
                            >
                                {tab.label}
                            </button>
                        ))}
                    </nav>
                </div>

                {/* Tab Content */}
                <div className="mt-6">
                    {activeTab === 'issues' && (
                        <IssuesTable
                            audit={audit}
                            issues={issues}
                            issueTypes={issueTypes}
                            filters={filters}
                        />
                    )}

                    {activeTab === 'pages' && (
                        <PagesTable
                            audit={audit}
                            pages={pages}
                            filters={filters}
                        />
                    )}

                    {activeTab === 'performance' && (
                        <PerformanceTab audit={audit} />
                    )}
                </div>
            </div>
        </AppLayout>
    );
}

function PerformanceTab({ audit }) {
    const hasApiKey = audit.metrics && audit.metrics.length > 0;
    const metrics = audit.metrics || [];

    return (
        <Card>
            {!hasApiKey && (
                <div className="p-4 bg-yellow-50 border border-yellow-200 rounded-md mb-6">
                    <p className="text-sm text-yellow-800">
                        <strong>Note:</strong> Core Web Vitals require PAGESPEED_API_KEY to be configured in your .env file.
                        {audit.settings_json?.include_cwv && (
                            <span className="block mt-1">This audit was configured to include CWV, but no API key was found.</span>
                        )}
                    </p>
                </div>
            )}

            {hasApiKey && metrics.length > 0 ? (
                <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">URL</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Strategy</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Score</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">LCP</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">CLS</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">INP</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">FCP</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">TTFB</th>
                            </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-gray-200">
                            {metrics.map((metric) => (
                                <tr key={metric.id} className="hover:bg-gray-50">
                                    <td className="px-6 py-4">
                                        <a href={metric.url} target="_blank" rel="noopener noreferrer" className="text-blue-600 hover:underline text-sm truncate block max-w-md">
                                            {metric.url}
                                        </a>
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 capitalize">
                                        {metric.strategy}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        {metric.performance_score !== null ? (
                                            <span className={`text-lg font-bold ${
                                                metric.performance_score >= 90 ? 'text-green-600' :
                                                metric.performance_score >= 50 ? 'text-yellow-600' :
                                                'text-red-600'
                                            }`}>
                                                {metric.performance_score}
                                            </span>
                                        ) : (
                                            <span className="text-gray-400">-</span>
                                        )}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {metric.lcp_ms ? `${(metric.lcp_ms / 1000).toFixed(2)}s` : '-'}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {metric.cls_x1000 ? (metric.cls_x1000 / 1000).toFixed(3) : '-'}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {metric.inp_ms ? `${metric.inp_ms}ms` : '-'}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {metric.fcp_ms ? `${(metric.fcp_ms / 1000).toFixed(2)}s` : '-'}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {metric.ttfb_ms ? `${metric.ttfb_ms}ms` : '-'}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            ) : hasApiKey ? (
                <div className="text-center py-12 text-gray-500">
                    No performance metrics available yet. Metrics are fetched after the audit completes.
                </div>
            ) : null}
        </Card>
    );
}


