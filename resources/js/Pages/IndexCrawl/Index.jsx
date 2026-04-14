import { Link, router, useForm, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import AppLayout from '@/Components/Layout/AppLayout';
import Card from '@/Components/Shared/Card';
import Button from '@/Components/Shared/Button';

export default function IndexCrawlPage({
    domains = [],
    selectedDomain,
    latestAudit,
    auditHistory = [],
    counts,
    filters = {},
}) {
    const { flash } = usePage().props;
    const [showAddDomain, setShowAddDomain] = useState(false);
    const form = useForm({
        crawl_limit: selectedDomain?.default_settings?.crawl_limit || 100,
        max_depth: selectedDomain?.default_settings?.max_depth || 3,
        include_sitemap: selectedDomain?.default_settings?.include_sitemap ?? true,
        include_cwv: false,
        return_to: 'index-crawl',
    });
    const addDomainForm = useForm({
        url: '',
        name: '',
    });

    const queryParams = useMemo(() => ({
        domain_id: selectedDomain?.id,
        audit_id: latestAudit?.id,
    }), [selectedDomain?.id, latestAudit?.id]);
    const fullReportHref = useMemo(() => {
        if (!selectedDomain?.id || !latestAudit?.id) return null;
        return `/domains/${selectedDomain.id}/audits/${latestAudit.id}`;
    }, [selectedDomain?.id, latestAudit?.id]);

    useEffect(() => {
        if (!latestAudit || !['queued', 'running'].includes(latestAudit.status)) return undefined;
        const timer = setTimeout(() => {
            router.reload({ only: ['latestAudit', 'auditHistory', 'counts'] });
        }, 5000);
        return () => clearTimeout(timer);
    }, [latestAudit?.id, latestAudit?.status]);

    const onDomainChange = (domainId) => {
        router.get('/index-crawl', { domain_id: domainId || null, audit_id: null }, { preserveState: true, replace: true, preserveScroll: true });
    };

    const startCrawl = (e) => {
        e.preventDefault();
        if (!selectedDomain) return;
        form.post(`/domains/${selectedDomain.id}/audits`, {
            preserveScroll: true,
            data: {
                crawl_limit: Number(form.data.crawl_limit),
                max_depth: Number(form.data.max_depth),
                include_sitemap: Boolean(form.data.include_sitemap),
                include_cwv: Boolean(form.data.include_cwv),
                return_to: 'index-crawl',
            },
        });
    };

    const submitNewDomain = (action = 'save') => {
        addDomainForm.post('/index-crawl/domains', {
            preserveScroll: true,
            data: {
                url: addDomainForm.data.url,
                name: addDomainForm.data.name || null,
                action,
                crawl_limit: Number(form.data.crawl_limit),
                max_depth: Number(form.data.max_depth),
                include_sitemap: Boolean(form.data.include_sitemap),
                include_cwv: Boolean(form.data.include_cwv),
            },
            onSuccess: () => {
                addDomainForm.reset();
                setShowAddDomain(false);
            },
        });
    };

    const statusBadge = (status) => {
        const map = {
            queued: 'bg-gray-100 text-gray-800',
            running: 'bg-blue-100 text-blue-800',
            completed: 'bg-green-100 text-green-800',
            failed: 'bg-red-100 text-red-800',
        };
        return (
            <span className={`px-2 py-1 rounded-full text-xs font-semibold ${map[status] || map.queued}`}>
                {status || 'queued'}
            </span>
        );
    };

    const isActive = ['queued', 'running'].includes(latestAudit?.status);

    const summaryCard = (label, value) => (
        <Card className="h-full">
            <p className="text-xs text-gray-500 uppercase tracking-wide">{label}</p>
            <p className="mt-2 text-2xl font-bold text-gray-900">{value ?? 0}</p>
        </Card>
    );

    return (
        <AppLayout header="Index & Crawl" subtitle="Run and monitor crawl status for your domains">
            <div className="space-y-6">
                {flash?.success && (
                    <div className="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{flash.success}</div>
                )}
                {flash?.warning && (
                    <div className="rounded-xl border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-800">{flash.warning}</div>
                )}
                {flash?.error && (
                    <div className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{flash.error}</div>
                )}

                <div className="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div className="flex flex-wrap items-center gap-3">
                        <select
                            className="border border-gray-300 rounded-lg px-3 py-2 text-sm min-w-[280px]"
                            value={selectedDomain?.id || ''}
                            onChange={(e) => onDomainChange(e.target.value)}
                        >
                            <option value="">Select domain</option>
                            {domains.map((domain) => (
                                <option key={domain.id} value={domain.id}>
                                    {domain.display_label || domain.name || domain.host}
                                </option>
                            ))}
                        </select>

                        {selectedDomain && auditHistory.length > 0 && (
                            <select
                                className="border border-gray-300 rounded-lg px-3 py-2 text-sm"
                                value={latestAudit?.id || ''}
                                onChange={(e) => router.get('/index-crawl', { ...queryParams, audit_id: e.target.value || null }, { preserveState: true, replace: true, preserveScroll: true })}
                            >
                                {auditHistory.map((audit) => (
                                    <option key={audit.id} value={audit.id}>
                                        Audit #{audit.id} - {new Date(audit.created_at).toLocaleString()}
                                    </option>
                                ))}
                            </select>
                        )}

                        <Button variant="outline" onClick={() => setShowAddDomain((prev) => !prev)}>
                            <i className="bi bi-plus-lg"></i> {showAddDomain ? 'Close Add URL' : 'Add New URL'}
                        </Button>
                        {fullReportHref && (
                            <Link href={fullReportHref}>
                                <Button variant="outline">
                                    <i className="bi bi-file-earmark-text"></i> View Full Crawl Report
                                </Button>
                            </Link>
                        )}
                    </div>

                    <Button
                        variant="primary"
                        onClick={startCrawl}
                        disabled={!selectedDomain || form.processing || isActive}
                    >
                        <i className="bi bi-play-fill"></i> {form.processing ? 'Starting...' : isActive ? 'Crawl In Progress' : (latestAudit ? 'Run Crawl Again' : 'Start First Crawl')}
                    </Button>
                </div>

                {showAddDomain && (
                    <Card title="Add New URL / Domain">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label className="block text-xs font-medium text-gray-600 mb-1">Website URL *</label>
                                <input
                                    type="text"
                                    className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                                    placeholder="example.com or https://example.com"
                                    value={addDomainForm.data.url}
                                    onChange={(e) => addDomainForm.setData('url', e.target.value)}
                                />
                                {(addDomainForm.errors.url || addDomainForm.errors.new_domain_url) && (
                                    <p className="mt-1 text-xs text-red-600">{addDomainForm.errors.url || addDomainForm.errors.new_domain_url}</p>
                                )}
                                <p className="mt-1 text-xs text-gray-500">
                                    We auto-normalize to root domain (for example: <code>https://example.com</code>).
                                </p>
                            </div>
                            <div>
                                <label className="block text-xs font-medium text-gray-600 mb-1">Project / Domain Name (optional)</label>
                                <input
                                    type="text"
                                    className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                                    placeholder="My Website"
                                    value={addDomainForm.data.name}
                                    onChange={(e) => addDomainForm.setData('name', e.target.value)}
                                />
                                <p className="mt-1 text-xs text-gray-500">If left empty, domain host name will be used automatically.</p>
                            </div>
                        </div>

                        <div className="mt-4 flex flex-wrap gap-2">
                            <Button
                                variant="secondary"
                                onClick={() => submitNewDomain('save')}
                                disabled={addDomainForm.processing || !addDomainForm.data.url.trim()}
                            >
                                <i className="bi bi-check2"></i> {addDomainForm.processing ? 'Saving...' : 'Save Domain'}
                            </Button>
                            <Button
                                variant="primary"
                                onClick={() => submitNewDomain('save_and_run')}
                                disabled={addDomainForm.processing || !addDomainForm.data.url.trim()}
                            >
                                <i className="bi bi-play-fill"></i> {addDomainForm.processing ? 'Processing...' : 'Save & Run Crawl'}
                            </Button>
                        </div>
                    </Card>
                )}

                {selectedDomain && (
                    <Card title="Crawl Settings">
                        <form onSubmit={startCrawl} className="grid grid-cols-1 md:grid-cols-4 gap-3">
                            <div>
                                <label className="block text-xs font-medium text-gray-600 mb-1">Crawl Limit</label>
                                <input
                                    type="number"
                                    min={1}
                                    max={1000}
                                    className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                                    value={form.data.crawl_limit}
                                    onChange={(e) => form.setData('crawl_limit', Number(e.target.value))}
                                />
                            </div>
                            <div>
                                <label className="block text-xs font-medium text-gray-600 mb-1">Max Depth</label>
                                <input
                                    type="number"
                                    min={0}
                                    max={5}
                                    className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                                    value={form.data.max_depth}
                                    onChange={(e) => form.setData('max_depth', Number(e.target.value))}
                                />
                            </div>
                            <label className="flex items-center gap-2 text-sm text-gray-700 pt-7">
                                <input
                                    type="checkbox"
                                    checked={form.data.include_sitemap}
                                    onChange={(e) => form.setData('include_sitemap', e.target.checked)}
                                />
                                Include sitemap
                            </label>
                            <label className="flex items-center gap-2 text-sm text-gray-700 pt-7">
                                <input
                                    type="checkbox"
                                    checked={form.data.include_cwv}
                                    onChange={(e) => form.setData('include_cwv', e.target.checked)}
                                />
                                Include CWV
                            </label>
                        </form>
                    </Card>
                )}

                {domains.length === 0 && (
                    <Card>
                        <div className="text-center py-10">
                            <p className="text-gray-600 mb-4">No domains found. Add a domain to start crawling.</p>
                            <Link href="/domains">
                                <Button variant="primary"><i className="bi bi-plus-lg"></i> Go to Domains</Button>
                            </Link>
                        </div>
                    </Card>
                )}

                {domains.length > 0 && !selectedDomain && (
                    <Card>
                        <div className="text-center py-10 text-gray-500">Select a domain to load Index & Crawl data.</div>
                    </Card>
                )}

                {selectedDomain && !latestAudit && (
                    <Card>
                        <div className="text-center py-10">
                            <p className="text-gray-600 mb-4">No crawl has been run for this domain yet.</p>
                            <Button variant="primary" onClick={startCrawl} disabled={form.processing}>
                                <i className="bi bi-play-fill"></i> {form.processing ? 'Starting...' : 'Start First Crawl'}
                            </Button>
                        </div>
                    </Card>
                )}

                {selectedDomain && latestAudit && (
                    <>
                        <Card title="Latest Crawl Status">
                            <div className="grid grid-cols-1 md:grid-cols-5 gap-4 text-sm">
                                <div>
                                    <p className="text-gray-500">Status</p>
                                    <div className="mt-1">{statusBadge(latestAudit.status)}</div>
                                </div>
                                <div>
                                    <p className="text-gray-500">Started</p>
                                    <p className="mt-1 text-gray-900">{latestAudit.started_at ? new Date(latestAudit.started_at).toLocaleString() : '-'}</p>
                                </div>
                                <div>
                                    <p className="text-gray-500">Finished</p>
                                    <p className="mt-1 text-gray-900">{latestAudit.finished_at ? new Date(latestAudit.finished_at).toLocaleString() : '-'}</p>
                                </div>
                                <div>
                                    <p className="text-gray-500">Health Score</p>
                                    <p className="mt-1 text-gray-900 font-semibold">{latestAudit.health_score ?? '-'}</p>
                                </div>
                                <div>
                                    <p className="text-gray-500">Audit ID</p>
                                    <p className="mt-1 text-gray-900">#{latestAudit.id}</p>
                                </div>
                            </div>
                            {fullReportHref && (
                                <div className="mt-4 flex flex-wrap items-center gap-2">
                                    <Link href={fullReportHref}>
                                        <Button variant="secondary">
                                            <i className="bi bi-box-arrow-up-right"></i> Open Detailed Audit
                                        </Button>
                                    </Link>
                                    <p className="text-xs text-gray-500">
                                        Use the full report for deeper Issues, Pages, and Performance analysis.
                                    </p>
                                </div>
                            )}
                            {isActive && (
                                <p className="mt-4 text-sm text-blue-700">Crawl is in progress. This panel refreshes automatically.</p>
                            )}
                            {latestAudit.status === 'failed' && (
                                <div className="mt-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                                    <p className="text-sm text-red-700">Crawl failed: {latestAudit.error_message || 'Unexpected error'}</p>
                                    <Button variant="primary" onClick={startCrawl} disabled={form.processing}>
                                        <i className="bi bi-arrow-repeat"></i> {form.processing ? 'Retrying...' : 'Retry Crawl'}
                                    </Button>
                                </div>
                            )}
                        </Card>

                        {latestAudit.status === 'completed' && (
                            <>
                                <Card>
                                    <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                                        <div>
                                            <p className="text-sm font-semibold text-gray-900">Crawl completed. Detailed report is ready.</p>
                                            <p className="text-xs text-gray-500 mt-1">
                                                Review full Issues, Pages, and Performance tabs in the detailed audit view.
                                            </p>
                                        </div>
                                        {fullReportHref && (
                                            <Link href={fullReportHref}>
                                                <Button variant="primary">
                                                    <i className="bi bi-bar-chart-line"></i> View Full Crawl Report
                                                </Button>
                                            </Link>
                                        )}
                                    </div>
                                </Card>
                                <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4">
                                    {summaryCard('Total URLs Discovered', counts?.total_urls_discovered)}
                                    {summaryCard('Total URLs Crawled', counts?.total_urls_crawled)}
                                    {summaryCard('Indexable URLs', counts?.indexable_urls)}
                                    {summaryCard('Non-Indexable URLs', counts?.non_indexable_urls)}
                                    {summaryCard('Blocked By Robots', counts?.blocked_by_robots_urls)}
                                    {summaryCard('Noindex URLs', counts?.noindex_urls)}
                                    {summaryCard('Redirect URLs', counts?.redirected_urls)}
                                    {summaryCard('404 URLs', counts?.status_404_urls)}
                                    {summaryCard('5xx URLs', counts?.status_5xx_urls)}
                                    {summaryCard('Score', latestAudit.health_score)}
                                </div>
                            </>
                        )}
                    </>
                )}
            </div>
        </AppLayout>
    );
}
