import { Link, useForm } from '@inertiajs/react';
import Card from '@/Components/Shared/Card';
import Button from '@/Components/Shared/Button';

export default function PagesTable({ audit, pages, issueTypes = [], selectedPage, filters }) {
    const { data, setData, get } = useForm({
        status_code: filters.status_code || '',
        indexable: filters.indexable ?? '',
        search: filters.search || '',
        missing_title: Boolean(filters.missing_title),
        missing_meta: Boolean(filters.missing_meta),
        canonical_issue: Boolean(filters.canonical_issue),
        noindex: Boolean(filters.noindex),
        redirected: Boolean(filters.redirected),
        status_4xx: Boolean(filters.status_4xx),
        status_5xx: Boolean(filters.status_5xx),
        has_issues: Boolean(filters.has_issues),
        issue_type: filters.issue_type || '',
        min_issues: filters.min_issues || '',
        sort: filters.sort || 'updated_at',
        direction: filters.direction || 'desc',
    });

    const applyFilters = () => {
        get(`/domains/${audit.domain_id}/audits/${audit.id}`, {
            data: {
                ...filters,
                tab: 'pages',
                page_id: filters.page_id || undefined,
                status_code: data.status_code || undefined,
                indexable: data.indexable === '' ? undefined : data.indexable,
                search: data.search || undefined,
                missing_title: data.missing_title ? 1 : undefined,
                missing_meta: data.missing_meta ? 1 : undefined,
                canonical_issue: data.canonical_issue ? 1 : undefined,
                noindex: data.noindex ? 1 : undefined,
                redirected: data.redirected ? 1 : undefined,
                status_4xx: data.status_4xx ? 1 : undefined,
                status_5xx: data.status_5xx ? 1 : undefined,
                has_issues: data.has_issues ? 1 : undefined,
                issue_type: data.issue_type || undefined,
                min_issues: data.min_issues || undefined,
                sort: data.sort,
                direction: data.direction,
            },
            preserveState: true,
            replace: true,
        });
    };

    const resetFilters = () => {
        get(`/domains/${audit.domain_id}/audits/${audit.id}`, {
            data: { tab: 'pages' },
            preserveState: true,
            replace: true,
        });
    };

    const statusBadge = (statusCode) => {
        if (!statusCode) return <span className="text-[rgba(255,255,255,0.45)]">-</span>;
        const tone = statusCode >= 500
            ? 'bg-red-500/20 text-red-300'
            : statusCode >= 400
            ? 'bg-orange-500/20 text-orange-200'
            : statusCode >= 300
            ? 'bg-blue-500/20 text-blue-200'
            : 'bg-emerald-500/20 text-emerald-300';

        return <span className={`px-2 py-1 text-xs rounded-full ${tone}`}>{statusCode}</span>;
    };

    const openPageDetails = (pageId) => {
        get(`/domains/${audit.domain_id}/audits/${audit.id}`, {
            data: { ...filters, ...data, tab: 'pages', page_id: pageId },
            preserveState: true,
            replace: true,
        });
    };

    return (
        <div className="space-y-4">
            <Card className="border border-[rgba(255,255,255,0.08)] bg-[linear-gradient(180deg,rgba(22,22,22,0.96),rgba(10,10,10,1))]">
                <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-6 gap-3">
                    <SelectField label="Status Code" value={data.status_code} onChange={(e) => setData('status_code', e.target.value)}>
                        <option value="">All</option>
                        <option value="200">200</option>
                        <option value="301">301</option>
                        <option value="302">302</option>
                        <option value="404">404</option>
                        <option value="500">500</option>
                    </SelectField>
                    <SelectField label="Indexability" value={data.indexable} onChange={(e) => setData('indexable', e.target.value)}>
                        <option value="">All</option>
                        <option value="1">Indexable</option>
                        <option value="0">Non-Indexable</option>
                    </SelectField>
                    <SelectField label="Issue Type" value={data.issue_type} onChange={(e) => setData('issue_type', e.target.value)}>
                        <option value="">All</option>
                        {issueTypes.map((type) => (
                            <option key={type} value={type}>{type.replace(/_/g, ' ')}</option>
                        ))}
                    </SelectField>
                    <SelectField label="Sort By" value={data.sort} onChange={(e) => setData('sort', e.target.value)}>
                        <option value="updated_at">Updated</option>
                        <option value="status_code">Status Code</option>
                        <option value="issues_count">Issue Count</option>
                        <option value="response_time_ms">Response Time</option>
                        <option value="word_count">Word Count</option>
                        <option value="title">Title</option>
                        <option value="url">URL</option>
                    </SelectField>
                    <SelectField label="Direction" value={data.direction} onChange={(e) => setData('direction', e.target.value)}>
                        <option value="desc">Desc</option>
                        <option value="asc">Asc</option>
                    </SelectField>
                    <div>
                        <label className="block text-xs font-medium text-[rgba(255,255,255,0.62)] mb-1">Min Issues</label>
                        <input
                            className="w-full border border-[rgba(255,255,255,0.14)] bg-[rgba(255,255,255,0.03)] text-white rounded-lg px-3 py-2 text-sm"
                            value={data.min_issues}
                            onChange={(e) => setData('min_issues', e.target.value)}
                            type="number"
                            min={0}
                        />
                    </div>
                </div>

                <div className="mt-3">
                    <label className="block text-xs font-medium text-[rgba(255,255,255,0.62)] mb-1">Search URL / Title</label>
                    <input
                        className="w-full border border-[rgba(255,255,255,0.14)] bg-[rgba(255,255,255,0.03)] text-white rounded-lg px-3 py-2 text-sm"
                        value={data.search}
                        onChange={(e) => setData('search', e.target.value)}
                        placeholder="Search by URL, title, final URL..."
                    />
                </div>

                <div className="mt-3 grid grid-cols-2 md:grid-cols-4 xl:grid-cols-8 gap-2 text-xs">
                    <Check label="Missing Title" checked={data.missing_title} onChange={(v) => setData('missing_title', v)} />
                    <Check label="Missing Meta" checked={data.missing_meta} onChange={(v) => setData('missing_meta', v)} />
                    <Check label="Canonical Issues" checked={data.canonical_issue} onChange={(v) => setData('canonical_issue', v)} />
                    <Check label="Noindex" checked={data.noindex} onChange={(v) => setData('noindex', v)} />
                    <Check label="Redirects" checked={data.redirected} onChange={(v) => setData('redirected', v)} />
                    <Check label="4xx" checked={data.status_4xx} onChange={(v) => setData('status_4xx', v)} />
                    <Check label="5xx" checked={data.status_5xx} onChange={(v) => setData('status_5xx', v)} />
                    <Check label="Has Issues" checked={data.has_issues} onChange={(v) => setData('has_issues', v)} />
                </div>

                <div className="flex flex-wrap gap-2 mt-4">
                    <Button variant="primary" onClick={applyFilters}>Apply Filters</Button>
                    <Button variant="outline" onClick={resetFilters}>Reset</Button>
                </div>
            </Card>

            <Card className="border border-[rgba(255,255,255,0.08)] bg-[linear-gradient(180deg,rgba(22,22,22,0.96),rgba(10,10,10,1))]">
                {pages?.data?.length ? (
                    <>
                        <div className="overflow-x-auto">
                            <table className="min-w-[1600px] w-full divide-y divide-[rgba(255,255,255,0.08)]">
                                <thead>
                                    <tr className="text-xs uppercase text-[rgba(255,255,255,0.54)]">
                                        <th className="px-4 py-3 text-left">URL</th>
                                        <th className="px-4 py-3 text-left">Final URL</th>
                                        <th className="px-4 py-3 text-left">Status</th>
                                        <th className="px-4 py-3 text-left">Indexable</th>
                                        <th className="px-4 py-3 text-left">Title</th>
                                        <th className="px-4 py-3 text-left">Title Len</th>
                                        <th className="px-4 py-3 text-left">Meta Len</th>
                                        <th className="px-4 py-3 text-left">H1</th>
                                        <th className="px-4 py-3 text-left">Words</th>
                                        <th className="px-4 py-3 text-left">Canonical</th>
                                        <th className="px-4 py-3 text-left">Robots</th>
                                        <th className="px-4 py-3 text-left">Type</th>
                                        <th className="px-4 py-3 text-left">Resp. Time</th>
                                        <th className="px-4 py-3 text-left">Issues</th>
                                        <th className="px-4 py-3 text-left">Action</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-[rgba(255,255,255,0.06)]">
                                    {pages.data.map((page) => (
                                        <tr key={page.id} className="hover:bg-[rgba(255,255,255,0.03)]">
                                            <td className="px-4 py-3 text-sm">
                                                <a href={page.url} target="_blank" rel="noreferrer" className="text-sky-300 hover:underline break-all">{page.url}</a>
                                            </td>
                                            <td className="px-4 py-3 text-sm text-[rgba(255,255,255,0.7)] max-w-xs truncate">{page.final_url || '-'}</td>
                                            <td className="px-4 py-3">{statusBadge(page.status_code)}</td>
                                            <td className="px-4 py-3 text-sm">{page.is_indexable ? <span className="text-emerald-300">Yes</span> : <span className="text-red-300">No</span>}</td>
                                            <td className="px-4 py-3 text-sm text-white max-w-xs truncate">{page.title || '-'}</td>
                                            <td className="px-4 py-3 text-sm text-[rgba(255,255,255,0.74)]">{page.title_length ?? 0}</td>
                                            <td className="px-4 py-3 text-sm text-[rgba(255,255,255,0.74)]">{page.meta_description_length ?? 0}</td>
                                            <td className="px-4 py-3 text-sm text-[rgba(255,255,255,0.74)]">{page.h1_count ?? 0}</td>
                                            <td className="px-4 py-3 text-sm text-[rgba(255,255,255,0.74)]">{page.word_count ?? 0}</td>
                                            <td className="px-4 py-3 text-sm">
                                                <span className={`${page.canonical_status === 'self' ? 'text-emerald-300' : page.canonical_status === 'mismatch' ? 'text-yellow-200' : 'text-red-300'}`}>
                                                    {page.canonical_status}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 text-sm text-[rgba(255,255,255,0.74)] max-w-[220px] truncate">{page.robots_meta || '-'}</td>
                                            <td className="px-4 py-3 text-sm text-[rgba(255,255,255,0.74)]">{page.content_type || '-'}</td>
                                            <td className="px-4 py-3 text-sm text-[rgba(255,255,255,0.74)]">{page.response_time_ms ? `${page.response_time_ms}ms` : '-'}</td>
                                            <td className="px-4 py-3 text-sm text-white font-medium">{page.issues_count ?? 0}</td>
                                            <td className="px-4 py-3 text-sm">
                                                <button onClick={() => openPageDetails(page.id)} className="text-sky-300 hover:text-sky-200">
                                                    View Page
                                                </button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {pages.links && pages.links.length > 3 && (
                            <div className="mt-4 flex items-center justify-center gap-2 flex-wrap">
                                {pages.links.map((link, idx) => (
                                    <Link
                                        key={idx}
                                        href={link.url || '#'}
                                        className={`px-3 py-1.5 rounded-lg text-sm ${
                                            link.active
                                                ? 'bg-white text-black'
                                                : link.url
                                                ? 'bg-[rgba(255,255,255,0.06)] text-white border border-[rgba(255,255,255,0.15)]'
                                                : 'bg-[rgba(255,255,255,0.05)] text-[rgba(255,255,255,0.4)] cursor-not-allowed'
                                        }`}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ))}
                            </div>
                        )}
                    </>
                ) : (
                    <p className="text-center text-sm text-[rgba(255,255,255,0.62)] py-10">No pages found for selected filters.</p>
                )}
            </Card>

            {selectedPage && (
                <Card className="border border-[rgba(255,255,255,0.08)] bg-[linear-gradient(180deg,rgba(22,22,22,0.96),rgba(10,10,10,1))]">
                    <div className="flex items-center justify-between gap-3">
                        <h4 className="text-lg font-semibold text-white">Page Details</h4>
                        <Link
                            href={`/domains/${audit.domain_id}/audits/${audit.id}?tab=pages`}
                            className="text-sm text-[rgba(255,255,255,0.62)] hover:text-white"
                        >
                            Close
                        </Link>
                    </div>

                    <div className="mt-4 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3 text-sm">
                        <Info label="URL" value={selectedPage.url} />
                        <Info label="Final URL" value={selectedPage.final_url || '-'} />
                        <Info label="Status Code" value={selectedPage.status_code || '-'} />
                        <Info label="Indexability" value={selectedPage.is_indexable ? 'Indexable' : 'Non-Indexable'} />
                        <Info label="Canonical" value={selectedPage.canonical || '-'} />
                        <Info label="Robots" value={selectedPage.robots_meta || '-'} />
                        <Info label="Title" value={selectedPage.title || '-'} />
                        <Info label="Title Length" value={selectedPage.title_length ?? 0} />
                        <Info label="Meta Description Length" value={selectedPage.meta_description_length ?? 0} />
                        <Info label="H1 Count" value={selectedPage.h1_count ?? 0} />
                        <Info label="Word Count" value={selectedPage.word_count ?? 0} />
                        <Info label="Response Time" value={selectedPage.response_time_ms ? `${selectedPage.response_time_ms}ms` : '-'} />
                    </div>

                    <div className="mt-5">
                        <p className="text-sm font-semibold text-white mb-2">Page Issues ({selectedPage.issues?.length || 0})</p>
                        {selectedPage.issues?.length ? (
                            <div className="space-y-2">
                                {selectedPage.issues.map((issue) => (
                                    <div key={issue.id} className="rounded-lg border border-[rgba(255,255,255,0.1)] bg-[rgba(255,255,255,0.03)] p-3">
                                        <div className="flex items-center justify-between gap-3">
                                            <p className="text-sm font-medium text-white">{issue.label}</p>
                                            <span className={`px-2 py-1 text-xs rounded-full ${
                                                issue.severity === 'critical' ? 'bg-red-500/20 text-red-300' :
                                                issue.severity === 'warning' ? 'bg-yellow-500/20 text-yellow-200' :
                                                'bg-blue-500/20 text-blue-200'
                                            }`}>
                                                {issue.severity}
                                            </span>
                                        </div>
                                        <p className="text-xs text-[rgba(255,255,255,0.66)] mt-1">{issue.message}</p>
                                        <p className="text-xs text-[rgba(255,255,255,0.52)] mt-1">Fix: {issue.recommendation}</p>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <p className="text-sm text-[rgba(255,255,255,0.62)]">No specific issues found for this page.</p>
                        )}
                    </div>
                </Card>
            )}
        </div>
    );
}

function SelectField({ label, children, ...props }) {
    return (
        <div>
            <label className="block text-xs font-medium text-[rgba(255,255,255,0.62)] mb-1">{label}</label>
            <select
                {...props}
                className="w-full border border-[rgba(255,255,255,0.14)] bg-[rgba(255,255,255,0.03)] text-white rounded-lg px-3 py-2 text-sm"
            >
                {children}
            </select>
        </div>
    );
}

function Check({ label, checked, onChange }) {
    return (
        <label className="inline-flex items-center gap-2 text-[rgba(255,255,255,0.72)]">
            <input type="checkbox" checked={checked} onChange={(e) => onChange(e.target.checked)} />
            <span>{label}</span>
        </label>
    );
}

function Info({ label, value }) {
    return (
        <div className="rounded-lg border border-[rgba(255,255,255,0.08)] bg-[rgba(255,255,255,0.02)] p-3">
            <p className="text-[11px] uppercase tracking-[0.1em] text-[rgba(255,255,255,0.5)]">{label}</p>
            <p className="text-sm text-white mt-1 break-all">{value}</p>
        </div>
    );
}
