import { useState } from 'react';
import AdminLayout from '@/Components/Layout/AdminLayout';
import Card from '@/Components/Shared/Card';
import { Link, router, usePage } from '@inertiajs/react';

export default function AdminCampaignsIndex({ campaigns, stats, users, filters = {} }) {
    const { flash } = usePage().props;
    const [deletingId, setDeletingId] = useState(null);
    const [search, setSearch] = useState(filters.search || '');
    const [statusFilter, setStatusFilter] = useState(filters.status || '');
    const [userFilter, setUserFilter] = useState(filters.user_id || '');

    const handleDelete = (campaignId, campaignName) => {
        if (window.confirm(`Are you sure you want to delete "${campaignName}"? This action cannot be undone.`)) {
            setDeletingId(campaignId);
            router.delete(`/admin/campaigns/${campaignId}`, {
                onFinish: () => setDeletingId(null),
            });
        }
    };

    const handleFilter = () => {
        router.get('/admin/campaigns', {
            search: search || undefined,
            status: statusFilter || undefined,
            user_id: userFilter || undefined,
        }, {
            preserveState: true,
            replace: true,
        });
    };

    const handlePause = (campaignId) => {
        router.post(`/admin/campaigns/${campaignId}/pause`, {}, {
            preserveScroll: true,
        });
    };

    const handleResume = (campaignId) => {
        router.post(`/admin/campaigns/${campaignId}/resume`, {}, {
            preserveScroll: true,
        });
    };

    return (
        <AdminLayout header="Campaigns Management">
            <div className="space-y-6">
                {/* Success Message */}
                {flash?.success && (
                    <div className="p-4 rounded-lg bg-[#12B76A]/10 border border-[#12B76A]/30">
                        <p className="text-sm text-[#12B76A] font-medium">{flash.success}</p>
                    </div>
                )}

                {/* Page Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-2xl font-bold text-[var(--admin-text)]">Campaigns</h2>
                        <p className="text-[var(--admin-text-muted)] mt-1">Manage and monitor all user campaigns</p>
                    </div>
                    <Link
                        href="/admin/campaigns/create"
                        className="px-4 py-2.5 bg-gradient-to-r from-[#2F6BFF] to-[#2457D6] hover:from-[#2457D6] hover:to-[#1E4BBD] text-white rounded-lg font-medium transition-all duration-200 flex items-center gap-2 shadow-lg shadow-[#2F6BFF]/20"
                    >
                        <i className="bi bi-plus-lg"></i>
                        New Campaign
                    </Link>
                </div>

                {/* KPI Stats Cards - Dashboard Style */}
                <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-5">
                    {/* Total */}
                    <div className="group relative overflow-hidden rounded-xl bg-[var(--admin-surface)] border border-[var(--admin-border)] p-6 hover:border-[var(--admin-border-hover)] transition-all duration-300 shadow-[var(--admin-shadow-sm)]">
                        <div className="absolute top-0 right-0 w-24 h-24 bg-slate-500/10 rounded-full blur-2xl -mr-8 -mt-8 group-hover:bg-slate-500/20 transition-colors dark:opacity-100 opacity-0"></div>
                        <div className="relative flex items-center justify-between">
                            <div>
                                <p className="text-[var(--admin-text-muted)] text-sm font-medium mb-1">Total</p>
                                <p className="text-3xl font-bold text-[var(--admin-text)]">{stats?.total || 0}</p>
                                <p className="text-[var(--admin-text-dim)] text-xs mt-2">All campaigns</p>
                            </div>
                            <div className="flex items-center justify-center h-14 w-14 rounded-xl bg-slate-500/10">
                                <svg className="h-7 w-7 text-slate-600 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    {/* Active */}
                    <div className="group relative overflow-hidden rounded-xl bg-[var(--admin-surface)] border border-[var(--admin-border)] p-6 hover:border-[#12B76A]/50 transition-all duration-300 shadow-[var(--admin-shadow-sm)]">
                        <div className="absolute top-0 right-0 w-24 h-24 bg-[#12B76A]/10 rounded-full blur-2xl -mr-8 -mt-8 group-hover:bg-[#12B76A]/20 transition-colors dark:opacity-100 opacity-0"></div>
                        <div className="relative flex items-center justify-between">
                            <div>
                                <p className="text-[var(--admin-text-muted)] text-sm font-medium mb-1">Active</p>
                                <p className="text-3xl font-bold text-[var(--admin-text)]">{stats?.active || 0}</p>
                                <p className="text-[var(--admin-text-dim)] text-xs mt-2">Currently running</p>
                            </div>
                            <div className="flex items-center justify-center h-14 w-14 rounded-xl bg-[#12B76A]/15">
                                <svg className="h-7 w-7 text-[#12B76A]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    {/* Paused */}
                    <div className="group relative overflow-hidden rounded-xl bg-[var(--admin-surface)] border border-[var(--admin-border)] p-6 hover:border-[#F79009]/50 transition-all duration-300 shadow-[var(--admin-shadow-sm)]">
                        <div className="absolute top-0 right-0 w-24 h-24 bg-[#F79009]/10 rounded-full blur-2xl -mr-8 -mt-8 group-hover:bg-[#F79009]/20 transition-colors dark:opacity-100 opacity-0"></div>
                        <div className="relative flex items-center justify-between">
                            <div>
                                <p className="text-[var(--admin-text-muted)] text-sm font-medium mb-1">Paused</p>
                                <p className="text-3xl font-bold text-[var(--admin-text)]">{stats?.paused || 0}</p>
                                <p className="text-[var(--admin-text-dim)] text-xs mt-2">On hold</p>
                            </div>
                            <div className="flex items-center justify-center h-14 w-14 rounded-xl bg-[#F79009]/15">
                                <svg className="h-7 w-7 text-[#F79009]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    {/* Completed */}
                    <div className="group relative overflow-hidden rounded-xl bg-[var(--admin-surface)] border border-[var(--admin-border)] p-6 hover:border-[#2F6BFF]/50 transition-all duration-300 shadow-[var(--admin-shadow-sm)]">
                        <div className="absolute top-0 right-0 w-24 h-24 bg-[#2F6BFF]/10 rounded-full blur-2xl -mr-8 -mt-8 group-hover:bg-[#2F6BFF]/20 transition-colors dark:opacity-100 opacity-0"></div>
                        <div className="relative flex items-center justify-between">
                            <div>
                                <p className="text-[var(--admin-text-muted)] text-sm font-medium mb-1">Completed</p>
                                <p className="text-3xl font-bold text-[var(--admin-text)]">{stats?.completed || 0}</p>
                                <p className="text-[var(--admin-text-dim)] text-xs mt-2">Finished</p>
                            </div>
                            <div className="flex items-center justify-center h-14 w-14 rounded-xl bg-[#2F6BFF]/15">
                                <svg className="h-7 w-7 text-[#5B8AFF]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    {/* Error */}
                    <div className="group relative overflow-hidden rounded-xl bg-[var(--admin-surface)] border border-[var(--admin-border)] p-6 hover:border-[#F04438]/50 transition-all duration-300 shadow-[var(--admin-shadow-sm)]">
                        <div className="absolute top-0 right-0 w-24 h-24 bg-[#F04438]/10 rounded-full blur-2xl -mr-8 -mt-8 group-hover:bg-[#F04438]/20 transition-colors dark:opacity-100 opacity-0"></div>
                        <div className="relative flex items-center justify-between">
                            <div>
                                <p className="text-[var(--admin-text-muted)] text-sm font-medium mb-1">Error</p>
                                <p className="text-3xl font-bold text-[var(--admin-text)]">{stats?.error || 0}</p>
                                <p className="text-[var(--admin-text-dim)] text-xs mt-2">Failed campaigns</p>
                            </div>
                            <div className="flex items-center justify-center h-14 w-14 rounded-xl bg-[#F04438]/15">
                                <svg className="h-7 w-7 text-[#F04438]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Filters Toolbar - Dashboard Style */}
                <Card variant="elevated">
                    <div className="p-5">
                        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div className="relative">
                                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg className="h-5 w-5 text-[var(--admin-text-muted)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                <input
                                    type="text"
                                    placeholder="Search campaigns..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    onKeyPress={(e) => e.key === 'Enter' && handleFilter()}
                                    className="w-full pl-10 pr-4 py-2.5 bg-[var(--admin-hover-bg)] border border-[var(--admin-border)] rounded-lg text-[var(--admin-text)] placeholder-[var(--admin-text-muted)] focus:outline-none focus:ring-2 focus:ring-[#2F6BFF]/50 focus:border-[#2F6BFF] transition-all"
                                />
                            </div>
                            <div>
                                <select
                                    value={statusFilter}
                                    onChange={(e) => setStatusFilter(e.target.value)}
                                    className="w-full px-4 py-2.5 bg-[var(--admin-hover-bg)] border border-[var(--admin-border)] rounded-lg text-[var(--admin-text)] focus:outline-none focus:ring-2 focus:ring-[#2F6BFF]/50 focus:border-[#2F6BFF] transition-all"
                                >
                                    <option value="">All Statuses</option>
                                    <option value="active">Active</option>
                                    <option value="paused">Paused</option>
                                    <option value="completed">Completed</option>
                                    <option value="error">Error</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <div>
                                <select
                                    value={userFilter}
                                    onChange={(e) => setUserFilter(e.target.value)}
                                    className="w-full px-4 py-2.5 bg-[var(--admin-hover-bg)] border border-[var(--admin-border)] rounded-lg text-[var(--admin-text)] focus:outline-none focus:ring-2 focus:ring-[#2F6BFF]/50 focus:border-[#2F6BFF] transition-all"
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
                                <button
                                    onClick={handleFilter}
                                    className="w-full px-4 py-2.5 bg-gradient-to-r from-[#2F6BFF] to-[#2457D6] hover:from-[#2457D6] hover:to-[#1E4BBD] text-white rounded-lg font-medium transition-all duration-200 flex items-center justify-center gap-2"
                                >
                                    <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    Search
                                </button>
                            </div>
                        </div>
                    </div>
                </Card>

                {/* Campaigns Table - Dashboard Style */}
                <Card variant="elevated">
                    {campaigns?.data && campaigns.data.length > 0 ? (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-[var(--admin-border)]">
                                <thead className="bg-[var(--admin-surface-2)]">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-[var(--admin-text-muted)] uppercase tracking-wider">Campaign</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-[var(--admin-text-muted)] uppercase tracking-wider">User</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-[var(--admin-text-muted)] uppercase tracking-wider">Domain</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-[var(--admin-text-muted)] uppercase tracking-wider">Status</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-[var(--admin-text-muted)] uppercase tracking-wider">Backlinks</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-[var(--admin-text-muted)] uppercase tracking-wider">Tasks</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-[var(--admin-text-muted)] uppercase tracking-wider">Created</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-[var(--admin-text-muted)] uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-[var(--admin-border)]">
                                    {campaigns.data.map((campaign) => (
                                        <tr key={campaign.id} className="hover:bg-[var(--admin-hover-bg)] transition-colors">
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="flex items-center">
                                                    <div className="h-10 w-10 rounded-lg bg-gradient-to-br from-[#2F6BFF]/20 to-[#2F6BFF]/5 flex items-center justify-center text-[#5B8AFF] font-bold text-sm mr-3 border border-[#2F6BFF]/20">
                                                        {campaign.name?.charAt(0).toUpperCase() || 'C'}
                                                    </div>
                                                    <div>
                                                        <div className="text-sm font-medium text-[var(--admin-text)]">{campaign.name || 'Untitled'}</div>
                                                        <div className="text-xs text-[var(--admin-text-dim)] truncate max-w-[200px]">{campaign.web_url || 'N/A'}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="text-sm text-[var(--admin-text)]">{campaign.user?.name || 'N/A'}</div>
                                                <div className="text-xs text-[var(--admin-text-dim)]">{campaign.user?.email || ''}</div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="text-sm text-[var(--admin-text)]">{campaign.domain?.name || 'N/A'}</div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <span className={`px-3 py-1 text-xs font-semibold rounded-full ${
                                                    campaign.status === 'active' 
                                                        ? 'bg-[#12B76A]/15 text-[#12B76A] border border-[#12B76A]/30' 
                                                        : campaign.status === 'paused' 
                                                            ? 'bg-[#F79009]/15 text-[#F79009] border border-[#F79009]/30' 
                                                            : campaign.status === 'completed'
                                                                ? 'bg-[#2F6BFF]/15 text-[#5B8AFF] border border-[#2F6BFF]/30'
                                                                : campaign.status === 'error'
                                                                    ? 'bg-[#F04438]/15 text-[#F04438] border border-[#F04438]/30'
                                                                    : 'bg-[var(--admin-surface-2)] text-[var(--admin-text-muted)] border border-[var(--admin-border)]'
                                                }`}>
                                                    {campaign.status}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="text-sm font-semibold text-[var(--admin-text)]">{campaign.backlinks_count || 0}</div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="text-sm font-semibold text-[var(--admin-text)]">{campaign.automation_tasks_count || 0}</div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-[var(--admin-text-muted)]">
                                                {new Date(campaign.created_at).toLocaleDateString()}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm">
                                                <div className="flex items-center gap-2">
                                                    <Link 
                                                        href={`/admin/campaigns/${campaign.id}`} 
                                                        className="p-1.5 rounded-lg hover:bg-[var(--admin-surface-2)] text-[var(--admin-text-muted)] hover:text-[#2F6BFF] transition-colors"
                                                        title="View"
                                                    >
                                                        <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                        </svg>
                                                    </Link>
                                                    <Link 
                                                        href={`/admin/campaigns/${campaign.id}/edit`} 
                                                        className="p-1.5 rounded-lg hover:bg-[var(--admin-surface-2)] text-[var(--admin-text-muted)] hover:text-[#2F6BFF] transition-colors"
                                                        title="Edit"
                                                    >
                                                        <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                        </svg>
                                                    </Link>
                                                    {campaign.status === 'active' ? (
                                                        <button
                                                            onClick={() => handlePause(campaign.id)}
                                                            className="p-1.5 rounded-lg hover:bg-[var(--admin-surface-2)] text-[var(--admin-text-muted)] hover:text-[#F79009] transition-colors"
                                                            title="Pause"
                                                        >
                                                            <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                            </svg>
                                                        </button>
                                                    ) : campaign.status === 'paused' ? (
                                                        <button
                                                            onClick={() => handleResume(campaign.id)}
                                                            className="p-1.5 rounded-lg hover:bg-[var(--admin-surface-2)] text-[var(--admin-text-muted)] hover:text-[#12B76A] transition-colors"
                                                            title="Resume"
                                                        >
                                                            <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                            </svg>
                                                        </button>
                                                    ) : null}
                                                    <button
                                                        onClick={() => handleDelete(campaign.id, campaign.name)}
                                                        disabled={deletingId === campaign.id}
                                                        className="p-1.5 rounded-lg hover:bg-[var(--admin-surface-2)] text-[var(--admin-text-muted)] hover:text-[#F04438] disabled:opacity-50 transition-colors"
                                                        title="Delete"
                                                    >
                                                        <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ) : (
                        <div className="text-center py-16">
                            <div className="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-[var(--admin-surface-2)] mb-4">
                                <svg className="h-8 w-8 text-[var(--admin-text-dim)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                            <p className="text-[var(--admin-text-muted)] font-medium">No campaigns found</p>
                            <p className="text-[var(--admin-text-dim)] text-sm mt-1">Campaigns will appear here once created</p>
                            <Link
                                href="/admin/campaigns/create"
                                className="inline-flex items-center gap-2 mt-4 px-4 py-2 bg-gradient-to-r from-[#2F6BFF] to-[#2457D6] hover:from-[#2457D6] hover:to-[#1E4BBD] text-white rounded-lg text-sm font-medium transition-colors shadow-lg shadow-[#2F6BFF]/20"
                            >
                                <i className="bi bi-plus-lg"></i>
                                Create Campaign
                            </Link>
                        </div>
                    )}

                    {/* Pagination */}
                    {campaigns?.links && campaigns.links.length > 3 && (
                        <div className="px-6 py-4 border-t border-[var(--admin-border)] bg-[var(--admin-surface-2)]">
                            <div className="flex flex-col sm:flex-row items-center justify-between gap-4">
                                <div className="text-sm text-[var(--admin-text-muted)]">
                                    Showing <span className="font-medium text-[var(--admin-text)]">{campaigns.from || 0}</span> to <span className="font-medium text-[var(--admin-text)]">{campaigns.to || 0}</span> of <span className="font-medium text-[var(--admin-text)]">{campaigns.total || 0}</span> results
                                </div>
                                <div className="flex flex-wrap gap-2">
                                    {campaigns.links.map((link, index) => (
                                        <Link
                                            key={index}
                                            href={link.url || '#'}
                                            className={`px-3 py-2 text-sm font-medium rounded-lg transition-all ${
                                                link.active
                                                    ? 'bg-gradient-to-r from-[#2F6BFF] to-[#2457D6] text-white shadow-lg shadow-[#2F6BFF]/20'
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

