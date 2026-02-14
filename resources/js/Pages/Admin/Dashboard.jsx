import AdminLayout from '../../Components/Layout/AdminLayout';
import Card from '../../Components/Shared/Card';
import { Link } from '@inertiajs/react';

export default function AdminDashboard({ stats = {}, recentCampaigns = [], recentBacklinks = [] }) {
    return (
        <AdminLayout header="Dashboard">
            <div className="space-y-6">
                {/* Welcome Section */}
                <div className="relative overflow-hidden rounded-2xl bg-[var(--admin-surface)] border border-[var(--admin-border)] p-8 shadow-[var(--admin-shadow-md)]">
                    {/* Background decoration - only visible in dark mode */}
                    <div className="absolute top-0 right-0 w-64 h-64 bg-[#2F6BFF]/10 rounded-full blur-3xl -mr-32 -mt-32 dark:opacity-100 opacity-0"></div>
                    <div className="absolute bottom-0 left-0 w-48 h-48 bg-[#B6F400]/10 rounded-full blur-3xl -ml-24 -mb-24 dark:opacity-100 opacity-0"></div>
                    
                    <div className="relative flex items-center justify-between">
                        <div>
                            <h2 className="text-2xl font-bold text-[var(--admin-text)] mb-2">Welcome Back, Admin!</h2>
                            <p className="text-[var(--admin-text-muted)]">Here's what's happening with your platform today.</p>
                        </div>
                        <div className="hidden md:flex items-center gap-3">
                            <Link
                                href="/admin/campaigns"
                                className="px-4 py-2.5 bg-gradient-to-r from-[#2F6BFF] to-[#2457D6] hover:from-[#2457D6] hover:to-[#1E4BBD] text-white rounded-lg font-medium transition-all duration-200 flex items-center gap-2 shadow-lg shadow-[#2F6BFF]/20"
                            >
                                <i className="bi bi-plus-lg"></i>
                                New Campaign
                            </Link>
                        </div>
                    </div>
                </div>

                {/* Stats Grid */}
                <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    {/* Total Users */}
                    <div className="group relative overflow-hidden rounded-xl bg-[var(--admin-surface)] border border-[var(--admin-border)] p-6 hover:border-[#2F6BFF]/50 transition-all duration-300 shadow-[var(--admin-shadow-sm)]">
                        <div className="absolute top-0 right-0 w-24 h-24 bg-[#2F6BFF]/10 rounded-full blur-2xl -mr-8 -mt-8 group-hover:bg-[#2F6BFF]/20 transition-colors dark:opacity-100 opacity-0"></div>
                        <div className="relative flex items-center justify-between">
                            <div>
                                <p className="text-[var(--admin-text-muted)] text-sm font-medium mb-1">Total Users</p>
                                <p className="text-3xl font-bold text-[var(--admin-text)]">{stats?.total_users?.toLocaleString() || 0}</p>
                                <p className="text-[var(--admin-text-dim)] text-xs mt-2">All registered users</p>
                            </div>
                            <div className="flex items-center justify-center h-14 w-14 rounded-xl bg-[#2F6BFF]/15">
                                <svg className="h-7 w-7 text-[#5B8AFF]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    {/* Active Campaigns */}
                    <div className="group relative overflow-hidden rounded-xl bg-[var(--admin-surface)] border border-[var(--admin-border)] p-6 hover:border-[#12B76A]/50 transition-all duration-300 shadow-[var(--admin-shadow-sm)]">
                        <div className="absolute top-0 right-0 w-24 h-24 bg-[#12B76A]/10 rounded-full blur-2xl -mr-8 -mt-8 group-hover:bg-[#12B76A]/20 transition-colors dark:opacity-100 opacity-0"></div>
                        <div className="relative flex items-center justify-between">
                            <div>
                                <p className="text-[var(--admin-text-muted)] text-sm font-medium mb-1">Active Campaigns</p>
                                <p className="text-3xl font-bold text-[var(--admin-text)]">{stats?.active_campaigns?.toLocaleString() || 0}</p>
                                <p className="text-[var(--admin-text-dim)] text-xs mt-2">Currently running</p>
                            </div>
                            <div className="flex items-center justify-center h-14 w-14 rounded-xl bg-[#12B76A]/15">
                                <svg className="h-7 w-7 text-[#12B76A]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    {/* Total Backlinks */}
                    <div className="group relative overflow-hidden rounded-xl bg-[var(--admin-surface)] border border-[var(--admin-border)] p-6 hover:border-[#B6F400]/50 transition-all duration-300 shadow-[var(--admin-shadow-sm)]">
                        <div className="absolute top-0 right-0 w-24 h-24 bg-[#B6F400]/10 rounded-full blur-2xl -mr-8 -mt-8 group-hover:bg-[#B6F400]/20 transition-colors dark:opacity-100 opacity-0"></div>
                        <div className="relative flex items-center justify-between">
                            <div>
                                <p className="text-[var(--admin-text-muted)] text-sm font-medium mb-1">Total Backlinks</p>
                                <p className="text-3xl font-bold text-[var(--admin-text)]">{stats?.total_backlinks?.toLocaleString() || 0}</p>
                                <p className="text-[var(--admin-text-dim)] text-xs mt-2">All time created</p>
                            </div>
                            <div className="flex items-center justify-center h-14 w-14 rounded-xl bg-[#B6F400]/15">
                                <svg className="h-7 w-7 text-[#B6F400]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    {/* Pending Tasks */}
                    <div className="group relative overflow-hidden rounded-xl bg-[var(--admin-surface)] border border-[var(--admin-border)] p-6 hover:border-[#F79009]/50 transition-all duration-300 shadow-[var(--admin-shadow-sm)]">
                        <div className="absolute top-0 right-0 w-24 h-24 bg-[#F79009]/10 rounded-full blur-2xl -mr-8 -mt-8 group-hover:bg-[#F79009]/20 transition-colors dark:opacity-100 opacity-0"></div>
                        <div className="relative flex items-center justify-between">
                            <div>
                                <p className="text-[var(--admin-text-muted)] text-sm font-medium mb-1">Pending Tasks</p>
                                <p className="text-3xl font-bold text-[var(--admin-text)]">{stats?.pending_tasks?.toLocaleString() || 0}</p>
                                <p className="text-[var(--admin-text-dim)] text-xs mt-2">Awaiting action</p>
                            </div>
                            <div className="flex items-center justify-center h-14 w-14 rounded-xl bg-[#F79009]/15">
                                <svg className="h-7 w-7 text-[#F79009]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Recent Activity */}
                <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    {/* Recent Campaigns */}
                    <Card 
                        title={
                            <div className="flex items-center justify-between">
                                <div className="flex items-center gap-3">
                                    <div className="w-8 h-8 rounded-lg bg-[#2F6BFF]/15 flex items-center justify-center">
                                        <i className="bi bi-bullseye text-[#5B8AFF]"></i>
                                    </div>
                                    <span className="text-[var(--admin-text)] font-semibold">Recent Campaigns</span>
                                </div>
                                <Link href="/admin/campaigns" className="text-sm text-[#2F6BFF] hover:text-[#5B8AFF] transition-colors">
                                    View all <i className="bi bi-arrow-right ml-1"></i>
                                </Link>
                            </div>
                        }
                        variant="elevated"
                    >
                        {recentCampaigns && recentCampaigns.length > 0 ? (
                            <div className="space-y-3">
                                {recentCampaigns.map((campaign) => (
                                    <Link 
                                        key={campaign.id} 
                                        href={`/admin/campaigns/${campaign.id}`}
                                        className="flex items-center justify-between p-4 bg-[var(--admin-hover-bg)] hover:bg-[var(--admin-surface-2)] rounded-xl border border-[var(--admin-border)] hover:border-[var(--admin-hover-border)] transition-all duration-200 group"
                                    >
                                        <div className="flex items-center gap-3 flex-1 min-w-0">
                                            <div className="h-10 w-10 rounded-lg bg-gradient-to-br from-[#2F6BFF]/20 to-[#2F6BFF]/5 flex items-center justify-center text-[#5B8AFF] font-bold text-sm border border-[#2F6BFF]/20">
                                                {campaign.name?.charAt(0).toUpperCase() || 'C'}
                                            </div>
                                            <div className="flex-1 min-w-0">
                                                <p className="text-sm font-medium text-[var(--admin-text)] truncate group-hover:text-[var(--admin-primary)] transition-colors">{campaign.name}</p>
                                                <p className="text-xs text-[var(--admin-text-dim)] mt-0.5 flex items-center gap-1">
                                                    <i className="bi bi-person text-[var(--admin-text-muted)]"></i>
                                                    {campaign.user?.name || 'Unknown'}
                                                </p>
                                            </div>
                                        </div>
                                        <span className={`ml-3 px-3 py-1 text-xs font-semibold rounded-full ${
                                            campaign.status === 'active' 
                                                ? 'bg-[#12B76A]/15 text-[#12B76A] border border-[#12B76A]/30' 
                                                : campaign.status === 'paused' 
                                                    ? 'bg-[#F79009]/15 text-[#F79009] border border-[#F79009]/30' 
                                                    : 'bg-[var(--admin-surface-2)] text-[var(--admin-text-muted)] border border-[var(--admin-border)]'
                                        }`}>
                                            {campaign.status}
                                        </span>
                                    </Link>
                                ))}
                            </div>
                        ) : (
                            <div className="text-center py-10">
                                <div className="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-[var(--admin-surface-2)] mb-4">
                                    <i className="bi bi-bullseye text-3xl text-[var(--admin-text-dim)]"></i>
                                </div>
                                <p className="text-[var(--admin-text-muted)] font-medium">No campaigns yet</p>
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
                    </Card>

                    {/* Recent Backlinks */}
                    <Card 
                        title={
                            <div className="flex items-center justify-between">
                                <div className="flex items-center gap-3">
                                    <div className="w-8 h-8 rounded-lg bg-[#B6F400]/15 flex items-center justify-center">
                                        <i className="bi bi-link-45deg text-[#B6F400]"></i>
                                    </div>
                                    <span className="text-[var(--admin-text)] font-semibold">Recent Backlinks</span>
                                </div>
                                <Link href="/admin/backlinks" className="text-sm text-[#2F6BFF] hover:text-[#5B8AFF] transition-colors">
                                    View all <i className="bi bi-arrow-right ml-1"></i>
                                </Link>
                            </div>
                        }
                        variant="elevated"
                    >
                        {recentBacklinks && recentBacklinks.length > 0 ? (
                            <div className="space-y-3">
                                {recentBacklinks.map((backlink) => (
                                    <div 
                                        key={backlink.id} 
                                        className="flex items-center justify-between p-4 bg-[var(--admin-hover-bg)] hover:bg-[var(--admin-surface-2)] rounded-xl border border-[var(--admin-border)] hover:border-[var(--admin-hover-border)] transition-all duration-200 group"
                                    >
                                        <div className="flex items-center gap-3 flex-1 min-w-0">
                                            <div className={`h-10 w-10 rounded-lg flex items-center justify-center text-white font-bold text-sm ${
                                                backlink.status === 'verified' 
                                                    ? 'bg-gradient-to-br from-[#12B76A] to-[#0D9458]' 
                                                    : backlink.status === 'pending' 
                                                        ? 'bg-gradient-to-br from-[#F79009] to-[#D97706]' 
                                                        : 'bg-gradient-to-br from-[#F04438] to-[#DC2626]'
                                            }`}>
                                                {backlink.status === 'verified' ? '✓' : backlink.status === 'pending' ? '⏳' : '✗'}
                                            </div>
                                            <div className="flex-1 min-w-0">
                                                <p className="text-sm font-medium text-[var(--admin-text)] truncate">{backlink.url}</p>
                                                <p className="text-xs text-[var(--admin-text-dim)] mt-0.5 flex items-center gap-1">
                                                    <i className="bi bi-bullseye text-[var(--admin-text-muted)]"></i>
                                                    {backlink.campaign?.name || 'Unknown Campaign'}
                                                </p>
                                            </div>
                                        </div>
                                        <span className={`ml-3 px-3 py-1 text-xs font-semibold rounded-full ${
                                            backlink.status === 'verified' 
                                                ? 'bg-[#12B76A]/15 text-[#12B76A] border border-[#12B76A]/30' 
                                                : backlink.status === 'pending' 
                                                    ? 'bg-[#F79009]/15 text-[#F79009] border border-[#F79009]/30' 
                                                    : 'bg-[#F04438]/15 text-[#F04438] border border-[#F04438]/30'
                                        }`}>
                                            {backlink.status}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="text-center py-10">
                                <div className="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-[var(--admin-surface-2)] mb-4">
                                    <i className="bi bi-link-45deg text-3xl text-[var(--admin-text-dim)]"></i>
                                </div>
                                <p className="text-[var(--admin-text-muted)] font-medium">No backlinks yet</p>
                                <p className="text-[var(--admin-text-dim)] text-sm mt-1">Backlinks will appear here once created</p>
                            </div>
                        )}
                    </Card>
                </div>

                {/* Quick Actions */}
                <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <Link 
                        href="/admin/users"
                        className="group p-5 bg-[var(--admin-surface)] hover:bg-[var(--admin-surface-2)] border border-[var(--admin-border)] hover:border-[#2F6BFF]/50 rounded-xl transition-all duration-300 shadow-[var(--admin-shadow-sm)]"
                    >
                        <div className="w-10 h-10 rounded-lg bg-[#2F6BFF]/15 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                            <i className="bi bi-people text-xl text-[#5B8AFF]"></i>
                        </div>
                        <p className="text-sm font-medium text-[var(--admin-text)]">Manage Users</p>
                        <p className="text-xs text-[var(--admin-text-dim)] mt-1">View all users</p>
                    </Link>
                    
                    <Link 
                        href="/admin/plans"
                        className="group p-5 bg-[var(--admin-surface)] hover:bg-[var(--admin-surface-2)] border border-[var(--admin-border)] hover:border-[#B6F400]/50 rounded-xl transition-all duration-300 shadow-[var(--admin-shadow-sm)]"
                    >
                        <div className="w-10 h-10 rounded-lg bg-[#B6F400]/15 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                            <i className="bi bi-box-seam text-xl text-[#B6F400]"></i>
                        </div>
                        <p className="text-sm font-medium text-[var(--admin-text)]">Pricing Plans</p>
                        <p className="text-xs text-[var(--admin-text-dim)] mt-1">Manage plans</p>
                    </Link>
                    
                    <Link 
                        href="/admin/system-health"
                        className="group p-5 bg-[var(--admin-surface)] hover:bg-[var(--admin-surface-2)] border border-[var(--admin-border)] hover:border-[#12B76A]/50 rounded-xl transition-all duration-300 shadow-[var(--admin-shadow-sm)]"
                    >
                        <div className="w-10 h-10 rounded-lg bg-[#12B76A]/15 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                            <i className="bi bi-heart-pulse text-xl text-[#12B76A]"></i>
                        </div>
                        <p className="text-sm font-medium text-[var(--admin-text)]">System Health</p>
                        <p className="text-xs text-[var(--admin-text-dim)] mt-1">Monitor status</p>
                    </Link>
                    
                    <Link 
                        href="/admin/settings"
                        className="group p-5 bg-[var(--admin-surface)] hover:bg-[var(--admin-surface-2)] border border-[var(--admin-border)] hover:border-[#F79009]/50 rounded-xl transition-all duration-300 shadow-[var(--admin-shadow-sm)]"
                    >
                        <div className="w-10 h-10 rounded-lg bg-[#F79009]/15 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                            <i className="bi bi-sliders text-xl text-[#F79009]"></i>
                        </div>
                        <p className="text-sm font-medium text-[var(--admin-text)]">Settings</p>
                        <p className="text-xs text-[var(--admin-text-dim)] mt-1">Configure app</p>
                    </Link>
                </div>
            </div>
        </AdminLayout>
    );
}
