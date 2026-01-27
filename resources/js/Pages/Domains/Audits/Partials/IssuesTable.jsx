import { Link, router, useForm } from '@inertiajs/react';
import Card from '@/Components/Shared/Card';
import Button from '@/Components/Shared/Button';
import Input from '@/Components/Shared/Input';
import Select from '@/Components/Shared/Select';

export default function IssuesTable({ audit, issues, issueTypes, filters }) {
    const { data, setData, get } = useForm({
        severity: filters.severity || '',
        type: filters.type || '',
        search: filters.search || '',
    });

    const handleFilter = () => {
        get(`/domains/${audit.domain_id}/audits/${audit.id}`, {
            data: {
                tab: 'issues',
                severity: data.severity || undefined,
                type: data.type || undefined,
                search: data.search || undefined,
            },
            preserveState: true,
        });
    };

    const getSeverityBadge = (severity) => {
        const colors = {
            critical: 'bg-red-100 text-red-800',
            warning: 'bg-yellow-100 text-yellow-800',
            info: 'bg-blue-100 text-blue-800',
        };
        return (
            <span className={`px-2 py-1 text-xs font-semibold rounded-full ${colors[severity] || colors.info}`}>
                {severity}
            </span>
        );
    };

    return (
        <Card>
            <div className="mb-4">
                <h3 className="text-lg font-semibold text-gray-900 mb-4">Issues</h3>
                
                {/* Filters */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                    <Select
                        label="Severity"
                        name="severity"
                        value={data.severity}
                        onChange={(e) => setData('severity', e.target.value)}
                    >
                        <option value="">All</option>
                        <option value="critical">Critical</option>
                        <option value="warning">Warning</option>
                        <option value="info">Info</option>
                    </Select>

                    <Select
                        label="Type"
                        name="type"
                        value={data.type}
                        onChange={(e) => setData('type', e.target.value)}
                    >
                        <option value="">All</option>
                        {issueTypes.map((type) => (
                            <option key={type} value={type}>{type.replace(/_/g, ' ')}</option>
                        ))}
                    </Select>

                    <div>
                        <Input
                            label="Search URL"
                            name="search"
                            value={data.search}
                            onChange={(e) => setData('search', e.target.value)}
                            placeholder="Filter by URL..."
                            className="mb-0"
                        />
                    </div>

                    <div className="flex items-end">
                        <Button variant="primary" onClick={handleFilter} className="w-full">
                            Filter
                        </Button>
                    </div>
                </div>
            </div>

            {issues && issues.data && issues.data.length > 0 ? (
                <>
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Severity</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Message</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">URL</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                                {issues.data.map((issue) => (
                                    <tr key={issue.id} className="hover:bg-gray-50">
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            {getSeverityBadge(issue.severity)}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {issue.type.replace(/_/g, ' ')}
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-900">
                                            {issue.message}
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-500">
                                            {issue.page ? (
                                                <a href={issue.page.url} target="_blank" rel="noopener noreferrer" className="text-blue-600 hover:underline truncate block max-w-xs">
                                                    {issue.page.url}
                                                </a>
                                            ) : (
                                                <span className="text-gray-400">-</span>
                                            )}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm">
                                            {issue.page && (
                                                <Link href={`/domains/${audit.domain_id}/audits/${audit.id}?tab=pages&search=${encodeURIComponent(issue.page.url)}`}>
                                                    <Button variant="outline" className="text-xs">View Page</Button>
                                                </Link>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {issues.links && issues.links.length > 3 && (
                        <div className="mt-4 flex items-center justify-center gap-2">
                            {issues.links.map((link, index) => (
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
                <div className="text-center py-12 text-gray-500">
                    No issues found
                </div>
            )}
        </Card>
    );
}

