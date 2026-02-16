import { useState } from 'react';
import AdminLayout from '@/Components/Layout/AdminLayout';
import Card from '@/Components/Shared/Card';
import { Link, router, usePage } from '@inertiajs/react';

export default function AdminBacklinksIndex({ backlinks, stats, campaigns, users, filters = {} }) {
    const { flash } = usePage().props;
    const [search, setSearch] = useState(filters.search || '');
    const [statusFilter, setStatusFilter] = useState(filters.status || '');
    const [typeFilter, setTypeFilter] = useState(filters.type || '');
    const [campaignFilter, setCampaignFilter] = useState(filters.campaign_id || '');
    const [userFilter, setUserFilter] = useState(filters.user_id || '');
    const [dateFrom, setDateFrom] = useState(filters.date_from || '');
    const [dateTo, setDateTo] = useState(filters.date_to || '');

    const handleFilter = () => {
        router.get('/admin/backlinks', {
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

    const handleReset = () => {
        setSearch('');
        setStatusFilter('');
        setTypeFilter('');
        setCampaignFilter('');
        setUserFilter('');
        setDateFrom('');
        setDateTo('');
        router.get('/admin/backlinks', {}, {
            preserveState: true,
            replace: true,
        });
    };

    const handleExport = () => {
        const params = new URLSearchParams({
            ...(statusFilter && { status: statusFilter }),
            ...(typeFilter && { type: typeFilter }),
            ...(campaignFilter && { campaign_id: campaignFilter }),
            ...(userFilter && { user_id: userFilter }),
        });
        window.open(`/admin/backlinks/export?${params.toString()}`, '_blank');
    };

    return (
        <AdminLayout header="Backlinks Management">
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
                {flash?.import_errors && flash.import_errors.length > 0 && (
                    <div className="p-4 rounded-lg bg-[#F79009]/10 border border-[#F79009]/30">
                        <p className="text-sm font-semibold text-[#F79009] mb-2">Import Warnings:</p>
                        <ul className="list-disc list-inside text-sm text-[#F79009]/90 space-y-1">
                            {flash.import_errors.slice(0, 10).map((error, index) => (
                                <li key={index}>{error}</li>
                            ))}
                            {flash.import_errors.length > 10 && (
                                <li>... and {flash.import_errors.length - 10} more</li>
                            )}
                        </ul>
                    </div>
                )}

                {/* Page Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-[var(--admin-text)]">Backlinks</h1>
                        <p className="text-sm text-[var(--admin-text-muted)] mt-1">Manage and track backlink status</p>
                    </div>
                    <Link href="/admin/backlinks/create">
                        <button className="px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-[#2F6BFF] to-[#2457D6] hover:from-[#2457D6] hover:to-[#1E4BBD] rounded-lg shadow-lg shadow-[#2F6BFF]/20 transition-all">
                            <svg className="inline-block w-4 h-4 mr-2 -mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
                            </svg>
                            Add Link
                        </button>
                    </Link>
                </div>

                {/* KPI Stats Cards */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    {/* Total */}
                    <div className="group relative overflow-hidden rounded-xl bg-[var(--admin-surface)] border border-[var(--admin-border)] p-4 min-h-[100px] hover:border-[var(--admin-border-hover)] transition-all duration-300 shadow-[var(--admin-shadow-sm)]">
                        <div className="flex items-center justify-between gap-3">
                            <div className="space-y-0.5">
                                <p className="text-sm font-medium text-[var(--admin-text-muted)]">Total</p>
                                <p className="text-2xl font-semibold text-[var(--admin-text)]">{stats?.total || 0}</p>
                            </div>
                            <div className="flex items-center justify-center h-10 w-10 rounded-lg bg-[#6366F1]/15 flex-shrink-0">
                                <svg className="h-5 w-5 text-[#6366F1]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    {/* Verified */}
                    <div className="group relative overflow-hidden rounded-xl bg-[var(--admin-surface)] border border-[var(--admin-border)] p-4 min-h-[100px] hover:border-[var(--admin-border-hover)] transition-all duration-300 shadow-[var(--admin-shadow-sm)]">
                        <div className="flex items-center justify-between gap-3">
                            <div className="space-y-0.5">
                                <p className="text-sm font-medium text-[var(--admin-text-muted)]">Verified</p>
                                <p className="text-2xl font-semibold text-[#12B76A]">{stats?.verified || 0}</p>
                            </div>
                            <div className="flex items-center justify-center h-10 w-10 rounded-lg bg-[#12B76A]/15 flex-shrink-0">
                                <svg className="h-5 w-5 text-[#12B76A]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    {/* Pending */}
                    <div className="group relative overflow-hidden rounded-xl bg-[var(--admin-surface)] border border-[var(--admin-border)] p-4 min-h-[100px] hover:border-[var(--admin-border-hover)] transition-all duration-300 shadow-[var(--admin-shadow-sm)]">
                        <div className="flex items-center justify-between gap-3">
                            <div className="space-y-0.5">
                                <p className="text-sm font-medium text-[var(--admin-text-muted)]">Pending</p>
                                <p className="text-2xl font-semibold text-[#F79009]">{stats?.pending || 0}</p>
                            </div>
                            <div className="flex items-center justify-center h-10 w-10 rounded-lg bg-[#F79009]/15 flex-shrink-0">
                                <svg className="h-5 w-5 text-[#F79009]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    {/* Submitted */}
                    <div className="group relative overflow-hidden rounded-xl bg-[var(--admin-surface)] border border-[var(--admin-border)] p-4 min-h-[100px] hover:border-[var(--admin-border-hover)] transition-all duration-300 shadow-[var(--admin-shadow-sm)]">
                        <div className="flex items-center justify-between gap-3">
                            <div className="space-y-0.5">
                                <p className="text-sm font-medium text-[var(--admin-text-muted)]">Submitted</p>
                                <p className="text-2xl font-semibold text-[#5B8AFF]">{stats?.submitted || 0}</p>
                            </div>
                            <div className="flex items-center justify-center h-10 w-10 rounded-lg bg-[#5B8AFF]/15 flex-shrink-0">
                                <svg className="h-5 w-5 text-[#5B8AFF]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    {/* Error */}
                    <div className="group relative overflow-hidden rounded-xl bg-[var(--admin-surface)] border border-[var(--admin-border)] p-4 min-h-[100px] hover:border-[var(--admin-border-hover)] transition-all duration-300 shadow-[var(--admin-shadow-sm)]">
                        <div className="flex items-center justify-between gap-3">
                            <div className="space-y-0.5">
                                <p className="text-sm font-medium text-[var(--admin-text-muted)]">Error</p>
                                <p className="text-2xl font-semibold text-[#F04438]">{stats?.error || 0}</p>
                            </div>
                            <div className="flex items-center justify-center h-10 w-10 rounded-lg bg-[#F04438]/15 flex-shrink-0">
                                <svg className="h-5 w-5 text-[#F04438]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    {/* Today */}
                    <div className="group relative overflow-hidden rounded-xl bg-[var(--admin-surface)] border border-[var(--admin-border)] p-4 min-h-[100px] hover:border-[var(--admin-border-hover)] transition-all duration-300 shadow-[var(--admin-shadow-sm)]">
                        <div className="flex items-center justify-between gap-3">
                            <div className="space-y-0.5">
                                <p className="text-sm font-medium text-[var(--admin-text-muted)]">Today</p>
                                <p className="text-2xl font-semibold text-[var(--admin-text)]">{stats?.today || 0}</p>
                            </div>
                            <div className="flex items-center justify-center h-10 w-10 rounded-lg bg-[#7F56D9]/15 flex-shrink-0">
                                <svg className="h-5 w-5 text-[#7F56D9]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    {/* This Week */}
                    <div className="group relative overflow-hidden rounded-xl bg-[var(--admin-surface)] border border-[var(--admin-border)] p-4 min-h-[100px] hover:border-[var(--admin-border-hover)] transition-all duration-300 shadow-[var(--admin-shadow-sm)]">
                        <div className="flex items-center justify-between gap-3">
                            <div className="space-y-0.5">
                                <p className="text-sm font-medium text-[var(--admin-text-muted)]">This Week</p>
                                <p className="text-2xl font-semibold text-[var(--admin-text)]">{stats?.this_week || 0}</p>
                            </div>
                            <div className="flex items-center justify-center h-10 w-10 rounded-lg bg-[#10B981]/15 flex-shrink-0">
                                <svg className="h-5 w-5 text-[#10B981]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    {/* This Month */}
                    <div className="group relative overflow-hidden rounded-xl bg-[var(--admin-surface)] border border-[var(--admin-border)] p-4 min-h-[100px] hover:border-[var(--admin-border-hover)] transition-all duration-300 shadow-[var(--admin-shadow-sm)]">
                        <div className="flex items-center justify-between gap-3">
                            <div className="space-y-0.5">
                                <p className="text-sm font-medium text-[var(--admin-text-muted)]">This Month</p>
                                <p className="text-2xl font-semibold text-[var(--admin-text)]">{stats?.this_month || 0}</p>
                            </div>
                            <div className="flex items-center justify-center h-10 w-10 rounded-lg bg-[#F59E0B]/15 flex-shrink-0">
                                <svg className="h-5 w-5 text-[#F59E0B]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Filters Toolbar */}
                <Card variant="elevated">
                    <div className="p-6">
                        <div className="space-y-4">
                            {/* Row 1: Search and Main Filters */}
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
                                <div className="lg:col-span-2">
                                    <div className="relative">
                                        <svg className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-[var(--admin-text-dim)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                        <input
                                            type="text"
                                            placeholder="Search URLs, keywords..."
                                            value={search}
                                            onChange={(e) => setSearch(e.target.value)}
                                            onKeyPress={(e) => e.key === 'Enter' && handleFilter()}
                                            className="w-full pl-10 pr-4 py-2.5 bg-[var(--admin-hover-bg)] border border-[var(--admin-border)] rounded-lg text-[var(--admin-text)] placeholder-[var(--admin-text-muted)] focus:outline-none focus:ring-2 focus:ring-[#2F6BFF]/50 focus:border-[#2F6BFF] transition-all"
                                        />
                                    </div>
                                </div>
                                <div>
                                    <select
                                        value={statusFilter}
                                        onChange={(e) => setStatusFilter(e.target.value)}
                                        className="w-full px-4 py-2.5 bg-[var(--admin-hover-bg)] border border-[var(--admin-border)] rounded-lg text-[var(--admin-text)] focus:outline-none focus:ring-2 focus:ring-[#2F6BFF]/50 focus:border-[#2F6BFF] transition-all"
                                    >
                                        <option value="">All Statuses</option>
                                        <option value="pending">Pending</option>
                                        <option value="submitted">Submitted</option>
                                        <option value="verified">Verified</option>
                                        <option value="error">Error</option>
                                    </select>
                                </div>
                                <div>
                                    <select
                                        value={typeFilter}
                                        onChange={(e) => setTypeFilter(e.target.value)}
                                        className="w-full px-4 py-2.5 bg-[var(--admin-hover-bg)] border border-[var(--admin-border)] rounded-lg text-[var(--admin-text)] focus:outline-none focus:ring-2 focus:ring-[#2F6BFF]/50 focus:border-[#2F6BFF] transition-all"
                                    >
                                        <option value="">All Types</option>
                                        <option value="comment">Comment</option>
                                        <option value="profile">Profile</option>
                                        <option value="forum">Forum</option>
                                        <option value="guestposting">Guest Post</option>
                                    </select>
                                </div>
                                <div>
                                    <select
                                        value={campaignFilter}
                                        onChange={(e) => setCampaignFilter(e.target.value)}
                                        className="w-full px-4 py-2.5 bg-[var(--admin-hover-bg)] border border-[var(--admin-border)] rounded-lg text-[var(--admin-text)] focus:outline-none focus:ring-2 focus:ring-[#2F6BFF]/50 focus:border-[#2F6BFF] transition-all"
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
                            </div>

                            {/* Row 2: Date Range and Actions */}
                            <div className="flex flex-col sm:flex-row items-center gap-4">
                                <div className="flex items-center gap-2 flex-1">
                                    <input
                                        type="date"
                                        placeholder="From Date"
                                        value={dateFrom}
                                        onChange={(e) => setDateFrom(e.target.value)}
                                        className="flex-1 px-4 py-2.5 bg-[var(--admin-hover-bg)] border border-[var(--admin-border)] rounded-lg text-[var(--admin-text)] focus:outline-none focus:ring-2 focus:ring-[#2F6BFF]/50 focus:border-[#2F6BFF] transition-all"
                                    />
                                    <span className="text-[var(--admin-text-muted)]">to</span>
                                    <input
                                        type="date"
                                        placeholder="To Date"
                                        value={dateTo}
                                        onChange={(e) => setDateTo(e.target.value)}
                                        className="flex-1 px-4 py-2.5 bg-[var(--admin-hover-bg)] border border-[var(--admin-border)] rounded-lg text-[var(--admin-text)] focus:outline-none focus:ring-2 focus:ring-[#2F6BFF]/50 focus:border-[#2F6BFF] transition-all"
                                    />
                                </div>
                                <div className="flex gap-3 w-full sm:w-auto">
                                    <button
                                        onClick={handleReset}
                                        className="flex-1 sm:flex-none px-4 py-2.5 text-sm font-medium text-[var(--admin-text)] bg-[var(--admin-surface)] border border-[var(--admin-border)] rounded-lg hover:bg-[var(--admin-hover-bg)] transition-all"
                                    >
                                        Reset
                                    </button>
                                    <button
                                        onClick={handleExport}
                                        className="flex-1 sm:flex-none px-4 py-2.5 text-sm font-medium text-[var(--admin-text)] bg-[var(--admin-surface)] border border-[var(--admin-border)] rounded-lg hover:bg-[var(--admin-hover-bg)] transition-all"
                                    >
                                        <svg className="inline-block w-4 h-4 mr-2 -mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                        </svg>
                                        Export CSV
                                    </button>
                                    <button
                                        onClick={handleFilter}
                                        className="flex-1 sm:flex-none px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-[#2F6BFF] to-[#2457D6] hover:from-[#2457D6] hover:to-[#1E4BBD] rounded-lg shadow-lg shadow-[#2F6BFF]/20 transition-all"
                                    >
                                        <svg className="inline-block w-4 h-4 mr-2 -mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                        Apply Filters
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </Card>

                {/* Backlinks Table */}
                <Card variant="elevated">
                    {backlinks?.data && backlinks.data.length > 0 ? (
                        <>
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-[var(--admin-border)]">
                                    <thead className="bg-[var(--admin-surface-2)]">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-semibold text-[var(--admin-text)] uppercase tracking-wider">ID</th>
                                            <th className="px-6 py-3 text-left text-xs font-semibold text-[var(--admin-text)] uppercase tracking-wider">Campaign</th>
                                            <th className="px-6 py-3 text-left text-xs font-semibold text-[var(--admin-text)] uppercase tracking-wider">User</th>
                                            <th className="px-6 py-3 text-left text-xs font-semibold text-[var(--admin-text)] uppercase tracking-wider">URL</th>
                                            <th className="px-6 py-3 text-left text-xs font-semibold text-[var(--admin-text)] uppercase tracking-wider">Type</th>
                                            <th className="px-6 py-3 text-left text-xs font-semibold text-[var(--admin-text)] uppercase tracking-wider">Keyword</th>
                                            <th className="px-6 py-3 text-left text-xs font-semibold text-[var(--admin-text)] uppercase tracking-wider">PA</th>
                                            <th className="px-6 py-3 text-left text-xs font-semibold text-[var(--admin-text)] uppercase tracking-wider">DA</th>
                                            <th className="px-6 py-3 text-left text-xs font-semibold text-[var(--admin-text)] uppercase tracking-wider">Status</th>
                                            <th className="px-6 py-3 text-left text-xs font-semibold text-[var(--admin-text)] uppercase tracking-wider">Created</th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-[var(--admin-surface)] divide-y divide-[var(--admin-border)]">
                                        {backlinks.data.map((backlink) => (
                                            <tr key={backlink.id} className="hover:bg-[var(--admin-hover-bg)] transition-colors">
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-[var(--admin-text-muted)]">#{backlink.id}</td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <Link href={`/admin/campaigns/${backlink.campaign_id}`} className="text-sm text-[#5B8AFF] hover:text-[#2F6BFF] font-medium transition-colors">
                                                        {backlink.campaign?.name || 'N/A'}
                                                    </Link>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-[var(--admin-text)]">
                                                    {backlink.campaign?.user?.name || 'N/A'}
                                                </td>
                                                <td className="px-6 py-4 text-sm max-w-xs">
                                                    <a href={backlink.url} target="_blank" rel="noopener noreferrer" className="text-[#5B8AFF] hover:text-[#2F6BFF] break-all truncate block transition-colors">
                                                        {backlink.url}
                                                    </a>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-[var(--admin-text)] capitalize">{backlink.type}</td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-[var(--admin-text)]">{backlink.keyword || 'N/A'}</td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-[var(--admin-text)]">{backlink.pa !== null ? backlink.pa : 'N/A'}</td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-[var(--admin-text)]">{backlink.da !== null ? backlink.da : 'N/A'}</td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <span className={`px-2.5 py-1 text-xs font-semibold rounded-full ${
                                                        backlink.status === 'verified' 
                                                            ? 'bg-[#12B76A]/15 text-[#12B76A]' 
                                                            : backlink.status === 'pending' 
                                                                ? 'bg-[#F79009]/15 text-[#F79009]' 
                                                                : backlink.status === 'submitted' 
                                                                    ? 'bg-[#5B8AFF]/15 text-[#5B8AFF]' 
                                                                    : 'bg-[#F04438]/15 text-[#F04438]'
                                                    }`}>
                                                        {backlink.status}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-[var(--admin-text-muted)]">
                                                    {new Date(backlink.created_at).toLocaleDateString()}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            {/* Pagination */}
                            {backlinks.links && backlinks.links.length > 3 && (
                                <div className="px-6 py-4 border-t border-[var(--admin-border)] bg-[var(--admin-surface-2)]">
                                    <div className="flex flex-col sm:flex-row items-center justify-between gap-4">
                                        <div className="text-sm text-[var(--admin-text)]">
                                            Showing <span className="font-semibold">{backlinks.from || 0}</span> to <span className="font-semibold">{backlinks.to || 0}</span> of <span className="font-semibold">{backlinks.total || 0}</span> results
                                        </div>
                                        <div className="flex flex-wrap gap-2">
                                            {backlinks.links.map((link, index) => (
                                                <Link
                                                    key={index}
                                                    href={link.url || '#'}
                                                    className={`px-3.5 py-2 text-sm font-medium rounded-lg transition-all ${
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
                        </>
                    ) : (
                        <div className="text-center py-20">
                            <div className="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-[var(--admin-surface-2)] mb-6">
                                <svg className="h-10 w-10 text-[var(--admin-text-dim)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                </svg>
                            </div>
                            <p className="text-[var(--admin-text)] font-semibold text-lg">No backlinks found</p>
                            <p className="text-[var(--admin-text-muted)] text-sm mt-2 mb-6">Backlinks will appear here once created</p>
                            <Link href="/admin/backlinks/create">
                                <button className="px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-[#2F6BFF] to-[#2457D6] hover:from-[#2457D6] hover:to-[#1E4BBD] rounded-lg shadow-lg shadow-[#2F6BFF]/20 transition-all">
                                    <svg className="inline-block w-4 h-4 mr-2 -mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
                                    </svg>
                                    Add Your First Backlink
                                </button>
                            </Link>
                        </div>
                    )}
                </Card>
            </div>

        </AdminLayout>
    );
}

