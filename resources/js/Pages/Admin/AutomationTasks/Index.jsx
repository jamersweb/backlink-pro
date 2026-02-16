import { Fragment, useState } from 'react';
import AdminLayout from '@/Components/Layout/AdminLayout';
import Card from '@/Components/Shared/Card';
import Button from '@/Components/Shared/Button';
import Input from '@/Components/Shared/Input';
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
                    <div className="p-4 rounded-lg bg-[#12B76A]/10 border border-[#12B76A]/30">
                        <p className="text-sm text-[#12B76A] font-medium">{flash.success}</p>
                    </div>
                )}
                {flash?.error && (
                    <div className="p-4 rounded-lg bg-[#F04438]/10 border border-[#F04438]/30">
                        <p className="text-sm text-[#F04438] font-medium">{flash.error}</p>
                    </div>
                )}

                {/* Stats Cards - Dashboard-like compact 4×2 (7 cards) */}
                <div className="stats-grid stats-grid-tasks">
                    <div className="stat-card">
                        <div>
                            <p className="stat-title">Total</p>
                            <p className="stat-value">{stats?.total || 0}</p>
                        </div>
                        <div className="stat-iconWrap stat-iconWrap-neutral">
                            <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                        </div>
                    </div>
                    <div className="stat-card">
                        <div>
                            <p className="stat-title">Pending</p>
                            <p className="stat-value stat-value-warning">{stats?.pending || 0}</p>
                        </div>
                        <div className="stat-iconWrap stat-iconWrap-warning">
                            <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                    </div>
                    <div className="stat-card">
                        <div>
                            <p className="stat-title">Running</p>
                            <p className="stat-value stat-value-info">{stats?.running || 0}</p>
                        </div>
                        <div className="stat-iconWrap stat-iconWrap-info">
                            <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                        </div>
                    </div>
                    <div className="stat-card">
                        <div>
                            <p className="stat-title">Success</p>
                            <p className="stat-value stat-value-success">{stats?.success || 0}</p>
                        </div>
                        <div className="stat-iconWrap stat-iconWrap-success">
                            <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                    </div>
                    <div className="stat-card">
                        <div>
                            <p className="stat-title">Failed</p>
                            <p className="stat-value stat-value-danger">{stats?.failed || 0}</p>
                        </div>
                        <div className="stat-iconWrap stat-iconWrap-danger">
                            <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                    </div>
                    <div className="stat-card">
                        <div>
                            <p className="stat-title">Today</p>
                            <p className="stat-value">{stats?.today || 0}</p>
                        </div>
                        <div className="stat-iconWrap stat-iconWrap-neutral">
                            <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        </div>
                    </div>
                    <div className="stat-card">
                        <div>
                            <p className="stat-title">This Week</p>
                            <p className="stat-value">{stats?.this_week || 0}</p>
                        </div>
                        <div className="stat-iconWrap stat-iconWrap-neutral">
                            <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                        </div>
                    </div>
                </div>

                {/* Filters */}
                <Card variant="elevated">
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
                                    className="admin-select w-full px-3 py-2 rounded-lg"
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
                                    className="admin-select w-full px-3 py-2 rounded-lg"
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
                                    className="admin-select w-full px-3 py-2 rounded-lg"
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
                                    className="admin-select w-full px-3 py-2 rounded-lg"
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
                <Card variant="elevated">
                    {tasks?.data && tasks.data.length > 0 ? (
                        <div className="overflow-x-auto">
                            <table className="admin-table min-w-full">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Campaign</th>
                                        <th>User</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Worker</th>
                                        <th>Retries</th>
                                        <th>Error</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {sortedGroups.map((group) => (
                                        <Fragment key={`group-${group.campaignId || 'none'}`}>
                                            <tr className="bg-[var(--admin-surface-2)]">
                                                <td colSpan={10} className="px-4 py-3">
                                                    <div className="flex flex-wrap items-center gap-3 justify-between">
                                                        <div className="flex items-center gap-3">
                                                            <span className="text-sm font-semibold text-[var(--admin-text)]">{group.label}</span>
                                                            <span className="text-xs text-[var(--admin-text-muted)]">User: {group.userName}</span>
                                                        </div>
                                                        <div className="flex items-center gap-2 text-xs">
                                                            <span className="admin-badge admin-badge-neutral">Total {group.counts.total}</span>
                                                            {group.counts.pending > 0 && (
                                                                <span className="admin-badge admin-badge-warning">Pending {group.counts.pending}</span>
                                                            )}
                                                            {group.counts.running > 0 && (
                                                                <span className="admin-badge admin-badge-info">Running {group.counts.running}</span>
                                                            )}
                                                            {group.counts.success > 0 && (
                                                                <span className="admin-badge admin-badge-success">Success {group.counts.success}</span>
                                                            )}
                                                            {group.counts.failed > 0 && (
                                                                <span className="admin-badge admin-badge-danger">Failed {group.counts.failed}</span>
                                                            )}
                                                            {group.counts.cancelled > 0 && (
                                                                <span className="admin-badge admin-badge-neutral">Cancelled {group.counts.cancelled}</span>
                                                            )}
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            {group.items.map((task) => (
                                                <tr key={task.id}>
                                                    <td className="whitespace-nowrap text-sm">
                                                        <Link href={`/admin/automation-tasks/${task.id}`} className="admin-link font-medium">
                                                            #{task.id}
                                                        </Link>
                                                    </td>
                                                    <td className="whitespace-nowrap">
                                                        <Link href={`/admin/campaigns/${task.campaign_id}`} className="admin-link text-sm">
                                                            {task.campaign?.name || 'N/A'}
                                                        </Link>
                                                    </td>
                                                    <td className="whitespace-nowrap text-sm text-[var(--admin-text)]">
                                                        {task.campaign?.user?.name || 'N/A'}
                                                    </td>
                                                    <td className="whitespace-nowrap text-sm text-[var(--admin-text-muted)] capitalize">{task.type}</td>
                                                    <td className="whitespace-nowrap">
                                                        <span className={`admin-badge ${
                                                            task.status === 'success' ? 'admin-badge-success' :
                                                            task.status === 'running' ? 'admin-badge-info' :
                                                            task.status === 'pending' ? 'admin-badge-warning' :
                                                            task.status === 'failed' ? 'admin-badge-danger' :
                                                            'admin-badge-neutral'
                                                        }`}>
                                                            {task.status}
                                                        </span>
                                                    </td>
                                                    <td className="whitespace-nowrap text-sm text-[var(--admin-text-muted)]">
                                                        {task.locked_by || '-'}
                                                    </td>
                                                    <td className="whitespace-nowrap text-sm text-[var(--admin-text-muted)]">
                                                        {task.retry_count || 0} / {task.max_retries || 3}
                                                    </td>
                                                    <td className="text-sm max-w-xs">
                                                        {task.error_message ? (
                                                            <div className="group relative">
                                                                <span className="admin-text-danger cursor-help underline decoration-dotted">
                                                                    {task.error_message.substring(0, 50)}...
                                                                </span>
                                                                <div className="hidden group-hover:block absolute z-50 w-96 p-3 mt-2 bg-[var(--admin-surface-3)] border border-[var(--admin-border)] text-[var(--admin-text)] text-xs rounded-lg shadow-[var(--admin-shadow-lg)] pointer-events-none">
                                                                    <div className="font-semibold mb-1">Error Details:</div>
                                                                    <pre className="whitespace-pre-wrap break-words max-h-64 overflow-y-auto text-[var(--admin-text-muted)]">
                                                                        {task.error_message}
                                                                    </pre>
                                                                    {task.retry_count > 0 && (
                                                                        <div className="mt-2 pt-2 border-t border-[var(--admin-border)]">
                                                                            Retries: {task.retry_count}/{task.max_retries}
                                                                        </div>
                                                                    )}
                                                                </div>
                                                            </div>
                                                        ) : '-'}
                                                    </td>
                                                    <td className="whitespace-nowrap text-sm text-[var(--admin-text-muted)]">
                                                        {new Date(task.created_at).toLocaleDateString()}
                                                    </td>
                                                    <td className="whitespace-nowrap text-sm">
                                                        <div className="flex items-center gap-2">
                                                            {task.status === 'failed' && (
                                                                <button
                                                                    onClick={() => handleRetry(task.id)}
                                                                    className="admin-text-success hover:opacity-80"
                                                                    title="Retry"
                                                                >
                                                                    🔄
                                                                </button>
                                                            )}
                                                            {(task.status === 'pending' || task.status === 'running') && (
                                                                <button
                                                                    onClick={() => handleCancel(task.id)}
                                                                    className="admin-text-danger hover:opacity-80"
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
                            <div className="inline-block p-6 rounded-full mb-4 bg-[var(--admin-hover-bg)]">
                                <span className="text-5xl">⚙️</span>
                            </div>
                            <p className="text-[var(--admin-text)] font-medium">No automation tasks found</p>
                            <p className="text-[var(--admin-text-muted)] text-sm mt-2">Tasks will appear here once campaigns are active</p>
                        </div>
                    )}

                    {/* Pagination */}
                    {tasks?.links && tasks.links.length > 3 && (
                        <div className="px-6 py-4 border-t border-[var(--admin-border)] bg-[var(--admin-surface-2)]">
                            <div className="flex flex-col sm:flex-row items-center justify-between gap-4">
                                <div className="text-sm text-[var(--admin-text-muted)]">
                                    Showing <span className="font-medium text-[var(--admin-text)]">{tasks.from || 0}</span> to <span className="font-medium text-[var(--admin-text)]">{tasks.to || 0}</span> of <span className="font-medium text-[var(--admin-text)]">{tasks.total || 0}</span> results
                                </div>
                                <div className="flex flex-wrap gap-2">
                                    {tasks.links.map((link, index) => (
                                        <Link
                                            key={index}
                                            href={link.url || '#'}
                                            className={`px-3 py-2 text-sm font-medium rounded-lg transition-colors ${
                                                link.active
                                                    ? 'bg-[var(--admin-primary)] text-white'
                                                    : 'bg-[var(--admin-surface)] text-[var(--admin-text)] hover:bg-[var(--admin-hover-bg)] border border-[var(--admin-border)]'
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

