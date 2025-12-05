import { Fragment, useState } from 'react';
import AdminLayout from '../../../Components/Layout/AdminLayout';
import Card from '../../../Components/Shared/Card';
import Button from '../../../Components/Shared/Button';
import Input from '../../../Components/Shared/Input';
import { Link, router, usePage } from '@inertiajs/react';

export default function AdminAutomationTasksIndex({ tasks, stats, campaigns, users, filters = {} }) {
    const { flash } = usePage().props;
    const [search, setSearch] = useState(filters.search || '');
    const [statusFilter, setStatusFilter] = useState(filters.status || '');
    const [typeFilter, setTypeFilter] = useState(filters.type || '');
    const [campaignFilter, setCampaignFilter] = useState(filters.campaign_id || '');
    const [userFilter, setUserFilter] = useState(filters.user_id || '');
    const [dateFrom, setDateFrom] = useState(filters.date_from || '');
    const [dateTo, setDateTo] = useState(filters.date_to || '');

    const handleFilter = () => {
        router.get('/admin/automation-tasks', {
            search: search || undefined,
            status: statusFilter || undefined,
            type: typeFilter || undefined,
            campaign_id: campaignFilter || undefined,
            user_id: userFilter || undefined,
            date_from: dateFrom || undefined,
            date_to: dateTo || undefined,
        }, {
            preserveState: true,
            replace: true,
        });
    };

    const handleRetry = (taskId) => {
        router.post(`/admin/automation-tasks/${taskId}/retry`, {}, {
            preserveScroll: true,
        });
    };

    const handleCancel = (taskId) => {
        if (window.confirm('Are you sure you want to cancel this task?')) {
            router.post(`/admin/automation-tasks/${taskId}/cancel`, {}, {
                preserveScroll: true,
            });
        }
    };

    const groupedTasks = tasks?.data?.reduce((groups, task) => {
        const key = task.campaign_id || 'uncategorized';
        const label = task.campaign?.name || 'No Campaign';

        if (!groups[key]) {
            groups[key] = {
                label,
                campaignId: task.campaign_id,
                userName: task.campaign?.user?.name || 'N/A',
                counts: {
                    total: 0,
                    pending: 0,
                    running: 0,
                    success: 0,
                    failed: 0,
                    cancelled: 0,
                },
                items: [],
            };
        }

        groups[key].counts.total += 1;
        const status = task.status || 'pending';
        if (groups[key].counts[status] !== undefined) {
            groups[key].counts[status] += 1;
        }
        groups[key].items.push(task);

        return groups;
    }, {});

    // Sort groups by campaign name, then by total count (descending)
    const sortedGroups = groupedTasks ? Object.values(groupedTasks).sort((a, b) => {
        if (a.label !== b.label) {
            return a.label.localeCompare(b.label);
        }
        return b.counts.total - a.counts.total;
    }) : [];

    return (
        <AdminLayout header="Automation Tasks Management">
            <div className="space-y-6">
                {/* Success/Error Messages */}
                {flash?.success && (
                    <div className="p-4 bg-green-50 border border-green-200 rounded-md">
                        <p className="text-sm text-green-800">{flash.success}</p>
                    </div>
                )}
                {flash?.error && (
                    <div className="p-4 bg-red-50 border border-red-200 rounded-md">
                        <p className="text-sm text-red-800">{flash.error}</p>
                    </div>
                )}

                {/* Stats Cards */}
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7">
                    <Card className="bg-white border border-gray-200 shadow-md">
                        <div className="p-4">
                            <p className="text-gray-600 text-xs font-medium mb-1">Total</p>
                            <p className="text-2xl font-bold text-gray-900">{stats?.total || 0}</p>
                        </div>
                    </Card>
                    <Card className="bg-white border border-yellow-200 shadow-md">
                        <div className="p-4">
                            <p className="text-yellow-600 text-xs font-medium mb-1">Pending</p>
                            <p className="text-2xl font-bold text-yellow-900">{stats?.pending || 0}</p>
                        </div>
                    </Card>
                    <Card className="bg-white border border-blue-200 shadow-md">
                        <div className="p-4">
                            <p className="text-blue-600 text-xs font-medium mb-1">Running</p>
                            <p className="text-2xl font-bold text-blue-900">{stats?.running || 0}</p>
                        </div>
                    </Card>
                    <Card className="bg-white border border-green-200 shadow-md">
                        <div className="p-4">
                            <p className="text-green-600 text-xs font-medium mb-1">Success</p>
                            <p className="text-2xl font-bold text-green-900">{stats?.success || 0}</p>
                        </div>
                    </Card>
                    <Card className="bg-white border border-red-200 shadow-md">
                        <div className="p-4">
                            <p className="text-red-600 text-xs font-medium mb-1">Failed</p>
                            <p className="text-2xl font-bold text-red-900">{stats?.failed || 0}</p>
                        </div>
                    </Card>
                    <Card className="bg-white border border-gray-200 shadow-md">
                        <div className="p-4">
                            <p className="text-gray-600 text-xs font-medium mb-1">Today</p>
                            <p className="text-2xl font-bold text-gray-900">{stats?.today || 0}</p>
                        </div>
                    </Card>
                    <Card className="bg-white border border-gray-200 shadow-md">
                        <div className="p-4">
                            <p className="text-gray-600 text-xs font-medium mb-1">This Week</p>
                            <p className="text-2xl font-bold text-gray-900">{stats?.this_week || 0}</p>
                        </div>
                    </Card>
                </div>

                {/* Filters */}
                <Card className="bg-white border border-gray-200 shadow-md">
                    <div className="p-4">
                        <div className="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-8 gap-4">
                            <div className="md:col-span-2">
                                <Input
                                    type="text"
                                    placeholder="Search error messages, worker ID..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    onKeyPress={(e) => e.key === 'Enter' && handleFilter()}
                                />
                            </div>
                            <div>
                                <select
                                    value={statusFilter}
                                    onChange={(e) => setStatusFilter(e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500"
                                >
                                    <option value="">All Statuses</option>
                                    <option value="pending">Pending</option>
                                    <option value="running">Running</option>
                                    <option value="success">Success</option>
                                    <option value="failed">Failed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                            <div>
                                <select
                                    value={typeFilter}
                                    onChange={(e) => setTypeFilter(e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500"
                                >
                                    <option value="">All Types</option>
                                    <option value="comment">Comment</option>
                                    <option value="profile">Profile</option>
                                    <option value="forum">Forum</option>
                                    <option value="guest">Guest Post</option>
                                    <option value="email_confirmation_click">Email Confirmation</option>
                                </select>
                            </div>
                            <div>
                                <select
                                    value={campaignFilter}
                                    onChange={(e) => setCampaignFilter(e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500"
                                >
                                    <option value="">All Campaigns</option>
                                    {campaigns?.map((campaign) => (
                                        <option key={campaign.id} value={campaign.id}>
                                            {campaign.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <select
                                    value={userFilter}
                                    onChange={(e) => setUserFilter(e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500"
                                >
                                    <option value="">All Users</option>
                                    {users?.map((user) => (
                                        <option key={user.id} value={user.id}>
                                            {user.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <Input
                                    type="date"
                                    placeholder="From Date"
                                    value={dateFrom}
                                    onChange={(e) => setDateFrom(e.target.value)}
                                />
                            </div>
                            <div>
                                <Input
                                    type="date"
                                    placeholder="To Date"
                                    value={dateTo}
                                    onChange={(e) => setDateTo(e.target.value)}
                                />
                            </div>
                        </div>
                        <div className="mt-4">
                            <Button variant="primary" onClick={handleFilter} className="w-full md:w-auto">
                                🔍 Filter
                            </Button>
                        </div>
                    </div>
                </Card>

                {/* Tasks Table */}
                <Card className="bg-white border border-gray-200 shadow-md">
                    {tasks?.data && tasks.data.length > 0 ? (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">ID</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Campaign</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">User</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Type</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Status</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Worker</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Retries</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Error</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Created</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {sortedGroups.map((group) => (
                                        <Fragment key={`group-${group.campaignId || 'none'}`}>
                                            <tr className="bg-gray-100">
                                                <td colSpan={10} className="px-4 py-3">
                                                    <div className="flex flex-wrap items-center gap-3 justify-between">
                                                        <div className="flex items-center gap-3">
                                                            <span className="text-sm font-semibold text-gray-800">{group.label}</span>
                                                            <span className="text-xs text-gray-600">User: {group.userName}</span>
                                                        </div>
                                                        <div className="flex items-center gap-2 text-xs">
                                                            <span className="px-2 py-1 rounded-full bg-gray-200 text-gray-800">Total {group.counts.total}</span>
                                                            {group.counts.pending > 0 && (
                                                                <span className="px-2 py-1 rounded-full bg-yellow-100 text-yellow-800">Pending {group.counts.pending}</span>
                                                            )}
                                                            {group.counts.running > 0 && (
                                                                <span className="px-2 py-1 rounded-full bg-blue-100 text-blue-800">Running {group.counts.running}</span>
                                                            )}
                                                            {group.counts.success > 0 && (
                                                                <span className="px-2 py-1 rounded-full bg-green-100 text-green-800">Success {group.counts.success}</span>
                                                            )}
                                                            {group.counts.failed > 0 && (
                                                                <span className="px-2 py-1 rounded-full bg-red-100 text-red-800">Failed {group.counts.failed}</span>
                                                            )}
                                                            {group.counts.cancelled > 0 && (
                                                                <span className="px-2 py-1 rounded-full bg-gray-100 text-gray-800">Cancelled {group.counts.cancelled}</span>
                                                            )}
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            {group.items.map((task) => (
                                                <tr key={task.id} className="hover:bg-gray-50 transition-colors">
                                                    <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-600">
                                                        <Link href={`/admin/automation-tasks/${task.id}`} className="text-gray-900 hover:text-gray-700 font-medium">
                                                            #{task.id}
                                                        </Link>
                                                    </td>
                                                    <td className="px-4 py-3 whitespace-nowrap">
                                                        <Link href={`/admin/campaigns/${task.campaign_id}`} className="text-sm text-blue-600 hover:text-blue-900">
                                                            {task.campaign?.name || 'N/A'}
                                                        </Link>
                                                    </td>
                                                    <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                                        {task.campaign?.user?.name || 'N/A'}
                                                    </td>
                                                    <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-600 capitalize">{task.type}</td>
                                                    <td className="px-4 py-3 whitespace-nowrap">
                                                        <span className={`px-2 py-1 text-xs font-medium rounded-full ${
                                                            task.status === 'success' ? 'bg-green-100 text-green-800' :
                                                            task.status === 'running' ? 'bg-blue-100 text-blue-800' :
                                                            task.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                                                            task.status === 'failed' ? 'bg-red-100 text-red-800' :
                                                            'bg-gray-100 text-gray-800'
                                                        }`}>
                                                            {task.status}
                                                        </span>
                                                    </td>
                                                    <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-600">
                                                        {task.locked_by || '-'}
                                                    </td>
                                                    <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-600">
                                                        {task.retry_count || 0} / {task.max_retries || 3}
                                                    </td>
                                                    <td className="px-4 py-3 text-sm text-gray-600 max-w-xs truncate" title={task.error_message || ''}>
                                                        {task.error_message ? (
                                                            <span className="text-red-600">{task.error_message.substring(0, 50)}...</span>
                                                        ) : '-'}
                                                    </td>
                                                    <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-600">
                                                        {new Date(task.created_at).toLocaleDateString()}
                                                    </td>
                                                    <td className="px-4 py-3 whitespace-nowrap text-sm">
                                                        <div className="flex items-center gap-2">
                                                            {task.status === 'failed' && (
                                                                <button
                                                                    onClick={() => handleRetry(task.id)}
                                                                    className="text-green-600 hover:text-green-900"
                                                                    title="Retry"
                                                                >
                                                                    🔄
                                                                </button>
                                                            )}
                                                            {(task.status === 'pending' || task.status === 'running') && (
                                                                <button
                                                                    onClick={() => handleCancel(task.id)}
                                                                    className="text-red-600 hover:text-red-900"
                                                                    title="Cancel"
                                                                >
                                                                    ❌
                                                                </button>
                                                            )}
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))}
                                        </Fragment>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ) : (
                        <div className="text-center py-16">
                            <div className="inline-block p-6 bg-gray-100 rounded-full mb-4">
                                <span className="text-5xl">⚙️</span>
                            </div>
                            <p className="text-gray-500 font-medium">No automation tasks found</p>
                            <p className="text-gray-400 text-sm mt-2">Tasks will appear here once campaigns are active</p>
                        </div>
                    )}

                    {/* Pagination */}
                    {tasks?.links && tasks.links.length > 3 && (
                        <div className="px-6 py-4 border-t border-gray-200 bg-gray-50">
                            <div className="flex flex-col sm:flex-row items-center justify-between gap-4">
                                <div className="text-sm text-gray-700">
                                    Showing <span className="font-medium">{tasks.from || 0}</span> to <span className="font-medium">{tasks.to || 0}</span> of <span className="font-medium">{tasks.total || 0}</span> results
                                </div>
                                <div className="flex flex-wrap gap-2">
                                    {tasks.links.map((link, index) => (
                                        <Link
                                            key={index}
                                            href={link.url || '#'}
                                            className={`px-3 py-2 text-sm font-medium rounded-md transition-colors ${
                                                link.active
                                                    ? 'bg-gray-900 text-white'
                                                    : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-300'
                                            }`}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    ))}
                                </div>
                            </div>
                        </div>
                    )}
                </Card>
            </div>
        </AdminLayout>
    );
}

