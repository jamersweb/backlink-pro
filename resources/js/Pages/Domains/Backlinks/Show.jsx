import { Link, router } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import AppLayout from '@/Components/Layout/AppLayout';
import Card from '@/Components/Shared/Card';
import Button from '@/Components/Shared/Button';
import SummaryCards from './Partials/SummaryCards';
import BacklinksTable from './Partials/BacklinksTable';
import RefDomainsTable from './Partials/RefDomainsTable';
import AnchorsTable from './Partials/AnchorsTable';
import DeltasPanel from './Partials/DeltasPanel';
import FiltersBar from './Partials/FiltersBar';

export default function BacklinksShow({ domain, run, backlinks, refDomains, anchors, uniqueTlds, filters }) {
    const [activeTab, setActiveTab] = useState(filters.tab || 'backlinks');

    // Auto-refresh if running/queued
    useEffect(() => {
        if (run.status === 'running' || run.status === 'queued') {
            const interval = setInterval(() => {
                router.reload({ only: ['run'] });
            }, 8000);

            return () => clearInterval(interval);
        }
    }, [run.status]);

    const handleTabChange = (tab) => {
        setActiveTab(tab);
        router.get(
            `/domains/${domain.id}/backlinks/${run.id}`,
            { ...filters, tab },
            { preserveState: true, replace: true }
        );
    };

    const getStatusBadge = (status) => {
        const colors = {
            queued: 'bg-gray-100 text-gray-800',
            running: 'bg-blue-100 text-blue-800',
            completed: 'bg-green-100 text-green-800',
            failed: 'bg-red-100 text-red-800',
        };
        return (
            <span className={`px-3 py-1 text-sm font-semibold rounded-full ${colors[status] || colors.queued}`}>
                {status}
            </span>
        );
    };

    return (
        <AppLayout header="Backlink Report">
            <div className="space-y-6">
                {/* Breadcrumb */}
                <div className="flex items-center gap-2 text-sm text-gray-600">
                    <Link href="/domains" className="hover:text-gray-900">Domains</Link>
                    <span>/</span>
                    <Link href={`/domains/${domain.id}`} className="hover:text-gray-900">{domain.name}</Link>
                    <span>/</span>
                    <Link href={`/domains/${domain.id}/backlinks`} className="hover:text-gray-900">Backlinks</Link>
                    <span>/</span>
                    <span className="text-gray-900">Run #{run.id}</span>
                </div>

                {/* Header */}
                <div className="flex justify-between items-center">
                    <div>
                        <div className="flex items-center gap-3">
                            <h1 className="text-2xl font-bold text-gray-900">Backlink Report</h1>
                            {getStatusBadge(run.status)}
                        </div>
                        <p className="text-sm text-gray-500 mt-1">
                            Started: {run.started_at ? new Date(run.started_at).toLocaleString() : 'N/A'}
                            {run.finished_at && ` • Finished: ${new Date(run.finished_at).toLocaleString()}`}
                            {run.duration && ` • Duration: ${Math.round(run.duration / 60)}m ${run.duration % 60}s`}
                        </p>
                    </div>
                    {run.status === 'completed' && (
                        <Link href={`/domains/${domain.id}/backlinks/${run.id}/export`}>
                            <Button variant="outline">📥 Export CSV</Button>
                        </Link>
                    )}
                </div>

                {/* Error Message */}
                {run.status === 'failed' && run.error_message && (
                    <Card>
                        <div className="p-4 bg-red-50 border border-red-200 rounded-md">
                            <p className="text-sm text-red-800">
                                <strong>Error:</strong> {run.error_message}
                            </p>
                        </div>
                    </Card>
                )}

                {/* Summary Cards */}
                {run.status === 'completed' && run.summary_json && (
                    <SummaryCards summary={run.summary_json} />
                )}

                {/* Deltas Panel */}
                {run.status === 'completed' && run.delta && (
                    <DeltasPanel delta={run.delta} />
                )}

                {/* Tabs */}
                {run.status === 'completed' && (
                    <Card>
                        <div className="border-b border-gray-200">
                            <nav className="flex -mb-px overflow-x-auto">
                                {[
                                    { id: 'backlinks', label: 'Backlinks' },
                                    { id: 'refdomains', label: 'Ref Domains' },
                                    { id: 'anchors', label: 'Anchors' },
                                    { id: 'deltas', label: 'New/Lost' },
                                ].map((tab) => (
                                    <button
                                        key={tab.id}
                                        onClick={() => handleTabChange(tab.id)}
                                        className={`px-6 py-3 text-sm font-medium border-b-2 whitespace-nowrap ${
                                            activeTab === tab.id
                                                ? 'border-gray-900 text-gray-900'
                                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                        }`}
                                    >
                                        {tab.label}
                                    </button>
                                ))}
                            </nav>
                        </div>

                        {/* Filters */}
                        <div className="p-4 border-b border-gray-200">
                            <FiltersBar
                                filters={filters}
                                uniqueTlds={uniqueTlds}
                                onFilterChange={(newFilters) => {
                                    router.get(
                                        `/domains/${domain.id}/backlinks/${run.id}`,
                                        { ...filters, ...newFilters, tab: activeTab },
                                        { preserveState: true, replace: true }
                                    );
                                }}
                            />
                        </div>

                        {/* Tab Content */}
                        <div className="p-4">
                            {activeTab === 'backlinks' && (
                                <BacklinksTable backlinks={backlinks} />
                            )}
                            {activeTab === 'refdomains' && (
                                <RefDomainsTable refDomains={refDomains} />
                            )}
                            {activeTab === 'anchors' && (
                                <AnchorsTable anchors={anchors} />
                            )}
                            {activeTab === 'deltas' && run.delta && (
                                <DeltasPanel delta={run.delta} detailed />
                            )}
                        </div>
                    </Card>
                )}

                {/* Loading State */}
                {(run.status === 'queued' || run.status === 'running') && (
                    <Card>
                        <div className="text-center py-12">
                            <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900 mb-4"></div>
                            <p className="text-gray-600">
                                {run.status === 'queued' ? 'Queued for processing...' : 'Fetching backlinks...'}
                            </p>
                            <p className="text-sm text-gray-500 mt-2">This page will auto-refresh when complete.</p>
                        </div>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}


