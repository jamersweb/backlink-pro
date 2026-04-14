import { Link, useForm } from '@inertiajs/react';
import Card from '@/Components/Shared/Card';
import Button from '@/Components/Shared/Button';

export default function IssuesTable({ audit, issues, issueTypes, issueGroups, filters }) {
    const { data, setData, get } = useForm({
        severity: filters.severity || '',
        type: filters.type || '',
        issue_status_code: filters.issue_status_code || '',
        issue_indexable: filters.issue_indexable ?? '',
        search: filters.search || '',
    });

    const applyFilters = () => {
        get(`/domains/${audit.domain_id}/audits/${audit.id}`, {
            data: {
                ...filters,
                tab: 'issues',
                severity: data.severity || undefined,
                type: data.type || undefined,
                issue_status_code: data.issue_status_code || undefined,
                issue_indexable: data.issue_indexable === '' ? undefined : data.issue_indexable,
                search: data.search || undefined,
            },
            preserveState: true,
            replace: true,
        });
    };

    const resetFilters = () => {
        get(`/domains/${audit.domain_id}/audits/${audit.id}`, {
            data: { tab: 'issues' },
            preserveState: true,
            replace: true,
        });
    };

    const severityBadge = (severity) => {
        const colors = {
            critical: 'bg-red-500/15 text-red-300 border border-red-400/30',
            warning: 'bg-yellow-500/15 text-yellow-200 border border-yellow-300/30',
            info: 'bg-blue-500/15 text-blue-200 border border-blue-300/30',
        };
        return (
            <span className={`px-2 py-1 text-xs font-semibold rounded-full ${colors[severity] || colors.info}`}>
                {severity}
            </span>
        );
    };

    return (
        <div className="space-y-4">
            <Card className="border border-[rgba(255,255,255,0.08)] bg-[linear-gradient(180deg,rgba(22,22,22,0.96),rgba(10,10,10,1))]">
                <div className="flex items-center justify-between gap-3 flex-wrap">
                    <h3 className="text-lg font-semibold text-white">Issue Prioritization</h3>
                    <span className="text-sm text-[rgba(255,255,255,0.62)]">{issues?.total ?? 0} total issues</span>
                </div>

                {issueGroups?.length > 0 ? (
                    <div className="mt-4 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                        {issueGroups.slice(0, 6).map((group) => (
                            <div key={`${group.type}-${group.severity}`} className="rounded-xl border border-[rgba(255,255,255,0.09)] bg-[rgba(255,255,255,0.03)] p-4">
                                <div className="flex items-center justify-between gap-2">
                                    <p className="text-sm font-semibold text-white">{group.label}</p>
                                    {severityBadge(group.severity)}
                                </div>
                                <p className="text-xs text-[rgba(255,255,255,0.64)] mt-2">{group.explanation}</p>
                                <div className="mt-3 text-xs text-[rgba(255,255,255,0.58)]">
                                    <span className="mr-4">Affected URLs: <span className="text-white font-medium">{group.affected_urls}</span></span>
                                    <span>Occurrences: <span className="text-white font-medium">{group.total}</span></span>
                                </div>
                            </div>
                        ))}
                    </div>
                ) : (
                    <p className="mt-4 text-sm text-[rgba(255,255,255,0.62)]">No issue groups available for this audit.</p>
                )}
            </Card>

            <Card className="border border-[rgba(255,255,255,0.08)] bg-[linear-gradient(180deg,rgba(22,22,22,0.96),rgba(10,10,10,1))]">
                <div className="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-6 gap-3">
                    <SelectField label="Severity" value={data.severity} onChange={(e) => setData('severity', e.target.value)}>
                        <option value="">All</option>
                        <option value="critical">Critical</option>
                        <option value="warning">Warning</option>
                        <option value="info">Info</option>
                    </SelectField>

                    <SelectField label="Issue Type" value={data.type} onChange={(e) => setData('type', e.target.value)}>
                        <option value="">All</option>
                        {issueTypes.map((type) => (
                            <option key={type} value={type}>{type.replace(/_/g, ' ')}</option>
                        ))}
                    </SelectField>

                    <SelectField label="Status Code" value={data.issue_status_code} onChange={(e) => setData('issue_status_code', e.target.value)}>
                        <option value="">All</option>
                        <option value="200">200</option>
                        <option value="301">301</option>
                        <option value="302">302</option>
                        <option value="404">404</option>
                        <option value="500">500</option>
                    </SelectField>

                    <SelectField label="Indexability" value={data.issue_indexable} onChange={(e) => setData('issue_indexable', e.target.value)}>
                        <option value="">All</option>
                        <option value="1">Indexable</option>
                        <option value="0">Non-Indexable</option>
                    </SelectField>

                    <div className="xl:col-span-2">
                        <label className="block text-xs font-medium text-[rgba(255,255,255,0.62)] mb-1">Search URL / Title / Issue</label>
                        <input
                            className="w-full border border-[rgba(255,255,255,0.14)] bg-[rgba(255,255,255,0.03)] text-white rounded-lg px-3 py-2 text-sm"
                            value={data.search}
                            onChange={(e) => setData('search', e.target.value)}
                            placeholder="e.g. /blog, missing title"
                        />
                    </div>
                </div>

                <div className="flex flex-wrap gap-2 mt-4">
                    <Button variant="primary" onClick={applyFilters}>Apply Filters</Button>
                    <Button variant="outline" onClick={resetFilters}>Reset</Button>
                </div>
            </Card>

            <Card className="border border-[rgba(255,255,255,0.08)] bg-[linear-gradient(180deg,rgba(22,22,22,0.96),rgba(10,10,10,1))]">
                {issues?.data?.length ? (
                    <>
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-[rgba(255,255,255,0.08)]">
                                <thead>
                                    <tr className="text-xs uppercase text-[rgba(255,255,255,0.54)]">
                                        <th className="px-4 py-3 text-left">Severity</th>
                                        <th className="px-4 py-3 text-left">Issue Type</th>
                                        <th className="px-4 py-3 text-left">Explanation</th>
                                        <th className="px-4 py-3 text-left">URL</th>
                                        <th className="px-4 py-3 text-left">Page Title</th>
                                        <th className="px-4 py-3 text-left">Code</th>
                                        <th className="px-4 py-3 text-left">Indexability</th>
                                        <th className="px-4 py-3 text-left">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-[rgba(255,255,255,0.06)]">
                                    {issues.data.map((issue) => (
                                        <tr key={issue.id} className="hover:bg-[rgba(255,255,255,0.03)]">
                                            <td className="px-4 py-3">{severityBadge(issue.severity)}</td>
                                            <td className="px-4 py-3 text-sm text-white">{issue.type_label}</td>
                                            <td className="px-4 py-3 text-sm text-[rgba(255,255,255,0.67)] max-w-sm">
                                                <p>{issue.explanation || issue.message}</p>
                                                {issue.recommendation && (
                                                    <p className="text-xs text-[rgba(255,255,255,0.48)] mt-1">Fix: {issue.recommendation}</p>
                                                )}
                                            </td>
                                            <td className="px-4 py-3 text-sm">
                                                {issue.page?.url ? (
                                                    <a href={issue.page.url} target="_blank" rel="noreferrer" className="text-sky-300 hover:underline break-all">
                                                        {issue.page.url}
                                                    </a>
                                                ) : <span className="text-[rgba(255,255,255,0.45)]">-</span>}
                                            </td>
                                            <td className="px-4 py-3 text-sm text-[rgba(255,255,255,0.72)] max-w-xs truncate">{issue.page?.title || '-'}</td>
                                            <td className="px-4 py-3 text-sm text-[rgba(255,255,255,0.72)]">{issue.page?.status_code || '-'}</td>
                                            <td className="px-4 py-3 text-sm">
                                                {issue.page ? (
                                                    issue.page.is_indexable
                                                        ? <span className="text-emerald-300">Indexable</span>
                                                        : <span className="text-red-300">Non-Indexable</span>
                                                ) : <span className="text-[rgba(255,255,255,0.45)]">-</span>}
                                            </td>
                                            <td className="px-4 py-3 text-sm">
                                                {issue.page?.id ? (
                                                    <Link
                                                        href={`/domains/${audit.domain_id}/audits/${audit.id}?tab=pages&page_id=${issue.page.id}&search=${encodeURIComponent(issue.page.url || '')}`}
                                                        className="text-sky-300 hover:text-sky-200"
                                                    >
                                                        View Page Details
                                                    </Link>
                                                ) : '-'}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {issues.links && issues.links.length > 3 && (
                            <div className="mt-4 flex items-center justify-center gap-2 flex-wrap">
                                {issues.links.map((link, idx) => (
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
                    <p className="text-center text-sm text-[rgba(255,255,255,0.62)] py-10">No issues found for selected filters.</p>
                )}
            </Card>
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
