import { Link, router } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import AppLayout from '@/Components/Layout/AppLayout';
import Card from '@/Components/Shared/Card';
import Button from '@/Components/Shared/Button';
import AuditSummaryCards from './Partials/AuditSummaryCards';
import IssuesTable from './Partials/IssuesTable';
import PagesTable from './Partials/PagesTable';
import PerformanceTab from './Partials/PerformanceTab';

const TABS = [
    { id: 'issues', label: 'Issues' },
    { id: 'pages', label: 'Pages' },
    { id: 'performance', label: 'Performance' },
];

export default function AuditsShow({
    domain,
    audit,
    pages,
    issues,
    issueTypes = [],
    issueGroups = [],
    stats = {},
    topFixes = [],
    selectedPage = null,
    performanceSummary = {},
    hasPageSpeedApiKey = false,
    filters,
}) {
    const [activeTab, setActiveTab] = useState(filters.tab || 'issues');

    const tabCounts = useMemo(() => ({
        issues: issues?.total ?? issueGroups.reduce((sum, item) => sum + (item.total || 0), 0),
        pages: pages?.total ?? stats.total_urls_crawled ?? 0,
        performance: performanceSummary.total_rows ?? (audit.metrics?.length || 0),
    }), [issues?.total, issueGroups, pages?.total, stats.total_urls_crawled, performanceSummary.total_rows, audit.metrics]);

    const handleTabChange = (tab) => {
        setActiveTab(tab);
        router.get(`/domains/${domain.id}/audits/${audit.id}`, { ...filters, tab }, { preserveState: true, replace: true });
    };

    const statusBadge = (status) => {
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

    const formatDuration = (seconds) => {
        if (!seconds && seconds !== 0) return '-';
        if (seconds < 60) return `${seconds}s`;
        const minutes = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return `${minutes}m ${secs}s`;
    };

    return (
        <AppLayout header="Audit Details" subtitle="Technical SEO crawl workspace">
            <div className="space-y-6">
                <div className="flex items-center gap-2 text-sm text-gray-600">
                    <Link href="/domains" className="hover:text-gray-900">Domains</Link>
                    <span>/</span>
                    <Link href={`/domains/${domain.id}`} className="hover:text-gray-900">{domain.name}</Link>
                    <span>/</span>
                    <Link href={`/domains/${domain.id}/audits`} className="hover:text-gray-900">Audits</Link>
                    <span>/</span>
                    <span className="text-gray-900">#{audit.id}</span>
                </div>

                <Card className="border border-[rgba(255,255,255,0.08)] bg-[linear-gradient(180deg,rgba(20,20,20,0.98),rgba(8,8,8,1))]">
                    <div className="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6">
                        <div>
                            <div className="flex items-center gap-3 flex-wrap">
                                <h1 className="text-2xl font-bold text-white">Audit #{audit.id}</h1>
                                {statusBadge(audit.status)}
                            </div>
                            <p className="text-sm text-[rgba(255,255,255,0.65)] mt-2">
                                Domain: <span className="text-white font-medium">{audit.domain_host || domain.host || domain.name}</span>
                            </p>
                            <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3 mt-4 text-xs text-[rgba(255,255,255,0.72)]">
                                <div>Started: <span className="text-white">{audit.started_at ? new Date(audit.started_at).toLocaleString() : '-'}</span></div>
                                <div>Finished: <span className="text-white">{audit.finished_at ? new Date(audit.finished_at).toLocaleString() : '-'}</span></div>
                                <div>Duration: <span className="text-white">{formatDuration(audit.duration_seconds)}</span></div>
                                <div>Health Score: <span className="text-white">{audit.health_score ?? '-'}</span></div>
                                <div>Crawl Limit: <span className="text-white">{audit.settings_json?.crawl_limit ?? '-'}</span></div>
                                <div>Max Depth: <span className="text-white">{audit.settings_json?.max_depth ?? '-'}</span></div>
                                <div>Include Sitemap: <span className="text-white">{audit.settings_json?.include_sitemap ? 'Yes' : 'No'}</span></div>
                                <div>Include CWV: <span className="text-white">{audit.settings_json?.include_cwv ? 'Yes' : 'No'}</span></div>
                            </div>
                            {audit.status === 'failed' && (
                                <div className="mt-4 rounded-lg border border-red-400/30 bg-red-500/10 px-4 py-3 text-sm text-red-200">
                                    Crawl failed: {audit.error_message || 'Unexpected failure while processing this audit.'}
                                </div>
                            )}
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Link href={`/domains/${domain.id}/audits/${audit.id}/export`}>
                                <Button variant="outline" className="bp-btn-secondary"><i className="bi bi-download"></i> Export CSV</Button>
                            </Link>
                            <Link href={`/domains/${domain.id}/audits`}>
                                <Button variant="outline">Back to Audits</Button>
                            </Link>
                        </div>
                    </div>
                </Card>

                {audit.status === 'completed' && (
                    <AuditSummaryCards
                        audit={audit}
                        stats={stats}
                        topFixes={topFixes}
                    />
                )}

                <div className="border-b border-gray-200">
                    <nav className="-mb-px flex gap-5 overflow-x-auto" aria-label="Tabs">
                        {TABS.map((tab) => (
                            <button
                                key={tab.id}
                                onClick={() => handleTabChange(tab.id)}
                                className={`whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm ${
                                    activeTab === tab.id
                                        ? 'border-gray-900 text-gray-900'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                }`}
                            >
                                {tab.label} <span className="text-xs text-gray-400">({tabCounts[tab.id] ?? 0})</span>
                            </button>
                        ))}
                    </nav>
                </div>

                {activeTab === 'issues' && (
                    <IssuesTable
                        audit={audit}
                        issues={issues}
                        issueTypes={issueTypes}
                        issueGroups={issueGroups}
                        filters={filters}
                    />
                )}

                {activeTab === 'pages' && (
                    <PagesTable
                        audit={audit}
                        pages={pages}
                        issueTypes={issueTypes}
                        selectedPage={selectedPage}
                        filters={filters}
                    />
                )}

                {activeTab === 'performance' && (
                    <PerformanceTab
                        audit={audit}
                        performanceSummary={performanceSummary}
                        hasPageSpeedApiKey={hasPageSpeedApiKey}
                    />
                )}
            </div>
        </AppLayout>
    );
}
