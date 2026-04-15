import { Link } from '@inertiajs/react';
import AppLayout from '@/Components/Layout/AppLayout';
import Card from '@/Components/Shared/Card';
import Button from '@/Components/Shared/Button';

export default function BacklinksCheckerIndex({ domains, stats }) {
    const statusBadge = (status) => {
        const map = {
            queued: 'bg-gray-100 text-gray-800',
            running: 'bg-blue-100 text-blue-800',
            completed: 'bg-green-100 text-green-800',
            failed: 'bg-red-100 text-red-800',
        };

        return (
            <span className={`inline-flex px-2 py-1 rounded-full text-xs font-semibold ${map[status] || 'bg-gray-100 text-gray-800'}`}>
                {status || 'not-run'}
            </span>
        );
    };

    return (
        <AppLayout header="Backlinks Checker">
            <div className="space-y-6">
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <Card>
                        <div className="p-4">
                            <p className="text-gray-600 text-xs font-medium mb-1">Domains</p>
                            <p className="text-2xl font-bold text-gray-900">{stats?.total_domains || 0}</p>
                        </div>
                    </Card>
                    <Card>
                        <div className="p-4">
                            <p className="text-gray-600 text-xs font-medium mb-1">Completed Runs</p>
                            <p className="text-2xl font-bold text-green-600">{stats?.completed_runs || 0}</p>
                        </div>
                    </Card>
                    <Card>
                        <div className="p-4">
                            <p className="text-gray-600 text-xs font-medium mb-1">In Progress</p>
                            <p className="text-2xl font-bold text-blue-600">{stats?.in_progress_runs || 0}</p>
                        </div>
                    </Card>
                </div>

                <Card>
                    {!domains?.length ? (
                        <div className="p-8 text-center">
                            <p className="text-gray-500 mb-4">No domains found. Add a domain first.</p>
                            <Link href="/domains/create">
                                <Button variant="primary">Add Domain</Button>
                            </Link>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Domain</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Latest Run</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Backlinks</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ref Domains</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Follow/Nofollow</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {domains.map((domain) => {
                                        const summary = domain.latest_summary || {};
                                        return (
                                            <tr key={domain.id} className="hover:bg-gray-50">
                                                <td className="px-6 py-4">
                                                    <div className="text-sm font-medium text-gray-900">{domain.name}</div>
                                                    <div className="text-xs text-gray-500">{domain.host || domain.url}</div>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    {statusBadge(domain.latest_run_status)}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                                    {summary.total_backlinks || 0}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                                    {summary.ref_domains || 0}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                                    {(summary.follow || 0)} / {(summary.nofollow || 0)}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm">
                                                    <div className="flex items-center gap-2">
                                                        <Link href={`/domains/${domain.id}/backlinks`}>
                                                            <Button variant="outline" className="text-xs">Open</Button>
                                                        </Link>
                                                        {domain.latest_run_id && (
                                                            <Link href={`/domains/${domain.id}/backlinks/${domain.latest_run_id}`}>
                                                                <Button variant="secondary" className="text-xs">Latest Report</Button>
                                                            </Link>
                                                        )}
                                                    </div>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    )}
                </Card>
            </div>
        </AppLayout>
    );
}

