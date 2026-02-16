import AdminLayout from '@/Components/Layout/AdminLayout';
import Card from '@/Components/Shared/Card';
import { Link, router } from '@inertiajs/react';
import { useState } from 'react';

export default function SubscriptionsIndex({ subscriptions, stats, filters }) {
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [statusFilter, setStatusFilter] = useState(filters.status || 'all');
    const [firstTimeFilter, setFirstTimeFilter] = useState(filters.first_time === 'true');

    const handleFilterChange = (newStatus, newFirstTime) => {
        const params = {
            status: newStatus,
            first_time: newFirstTime ? 'true' : 'false',
            search: searchTerm,
        };
        router.get('/admin/subscriptions', params, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleSearch = (e) => {
        e.preventDefault();
        handleFilterChange(statusFilter, firstTimeFilter);
    };

    const getStatusBadge = (status) => {
        const badges = {
            active: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-500/20',
            canceled: 'bg-red-500/10 text-red-700 dark:text-red-400 border-red-500/20',
            pending: 'bg-amber-500/10 text-amber-700 dark:text-amber-400 border-amber-500/20',
            incomplete: 'bg-amber-500/10 text-amber-700 dark:text-amber-400 border-amber-500/20',
            trialing: 'bg-blue-500/10 text-blue-700 dark:text-blue-400 border-blue-500/20',
            past_due: 'bg-orange-500/10 text-orange-700 dark:text-orange-400 border-orange-500/20',
        };
        return badges[status] || 'bg-[var(--admin-hover-bg)] text-[var(--admin-text)] border-[var(--admin-border)]';
    };

    const getStatusLabel = (status) => {
        const labels = {
            active: 'Active',
            canceled: 'Canceled',
            incomplete: 'Pending',
            trialing: 'Trialing',
            past_due: 'Past Due',
        };
        return labels[status] || status || 'N/A';
    };

    return (
        <AdminLayout header="Subscriptions Management">
            <div className="space-y-6">
                {/* Page Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-[var(--admin-text)]">Subscriptions</h1>
                        <p className="text-sm text-[var(--admin-text-muted)] mt-1">Manage user subscriptions and billing status</p>
                    </div>
                    <button className="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-[var(--admin-text)] bg-[var(--admin-surface)] border border-[var(--admin-border)] rounded-lg hover:bg-[var(--admin-hover-bg)] transition-all">
                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Export CSV
                    </button>
                </div>

                {/* KPI Stats - Compact Dashboard Style */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    {/* Total */}
                    <div className="group relative overflow-hidden rounded-xl bg-[var(--admin-surface)] border border-[var(--admin-border)] p-4 min-h-[100px] hover:border-[var(--admin-border-hover)] transition-all duration-300 shadow-[var(--admin-shadow-sm)]">
                        <div className="flex items-center justify-between gap-3">
                            <div className="space-y-0.5">
                                <p className="text-sm font-medium text-[var(--admin-text-muted)]">Total</p>
                                <p className="text-2xl font-semibold text-[var(--admin-text)]">{stats.total || 0}</p>
                            </div>
                            <div className="flex items-center justify-center h-10 w-10 rounded-lg bg-[var(--admin-hover-bg)] flex-shrink-0">
                                <svg className="h-5 w-5 text-[var(--admin-text-muted)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    {/* Active */}
                    <div className="group relative overflow-hidden rounded-xl bg-[var(--admin-surface)] border border-[var(--admin-border)] p-4 min-h-[100px] hover:border-[var(--admin-border-hover)] transition-all duration-300 shadow-[var(--admin-shadow-sm)]">
                        <div className="flex items-center justify-between gap-3">
                            <div className="space-y-0.5">
                                <p className="text-sm font-medium text-[var(--admin-text-muted)]">Active</p>
                                <p className="text-2xl font-semibold text-emerald-600 dark:text-emerald-400">{stats.active || 0}</p>
                            </div>
                            <div className="flex items-center justify-center h-10 w-10 rounded-lg bg-emerald-500/10 flex-shrink-0">
                                <svg className="h-5 w-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    {/* Pending */}
                    <div className="group relative overflow-hidden rounded-xl bg-[var(--admin-surface)] border border-[var(--admin-border)] p-4 min-h-[100px] hover:border-[var(--admin-border-hover)] transition-all duration-300 shadow-[var(--admin-shadow-sm)]">
                        <div className="flex items-center justify-between gap-3">
                            <div className="space-y-0.5">
                                <p className="text-sm font-medium text-[var(--admin-text-muted)]">Pending</p>
                                <p className="text-2xl font-semibold text-amber-600 dark:text-amber-400">{stats.pending || 0}</p>
                            </div>
                            <div className="flex items-center justify-center h-10 w-10 rounded-lg bg-amber-500/10 flex-shrink-0">
                                <svg className="h-5 w-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    {/* Canceled */}
                    <div className="group relative overflow-hidden rounded-xl bg-[var(--admin-surface)] border border-[var(--admin-border)] p-4 min-h-[100px] hover:border-[var(--admin-border-hover)] transition-all duration-300 shadow-[var(--admin-shadow-sm)]">
                        <div className="flex items-center justify-between gap-3">
                            <div className="space-y-0.5">
                                <p className="text-sm font-medium text-[var(--admin-text-muted)]">Canceled</p>
                                <p className="text-2xl font-semibold text-red-600 dark:text-red-400">{stats.canceled || 0}</p>
                            </div>
                            <div className="flex items-center justify-center h-10 w-10 rounded-lg bg-red-500/10 flex-shrink-0">
                                <svg className="h-5 w-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    {/* Past Due */}
                    <div className="group relative overflow-hidden rounded-xl bg-[var(--admin-surface)] border border-[var(--admin-border)] p-4 min-h-[100px] hover:border-[var(--admin-border-hover)] transition-all duration-300 shadow-[var(--admin-shadow-sm)]">
                        <div className="flex items-center justify-between gap-3">
                            <div className="space-y-0.5">
                                <p className="text-sm font-medium text-[var(--admin-text-muted)]">Past Due</p>
                                <p className="text-2xl font-semibold text-orange-600 dark:text-orange-400">{stats.past_due || 0}</p>
                            </div>
                            <div className="flex items-center justify-center h-10 w-10 rounded-lg bg-orange-500/10 flex-shrink-0">
                                <svg className="h-5 w-5 text-orange-600 dark:text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    {/* First Time */}
                    <div className="group relative overflow-hidden rounded-xl bg-[var(--admin-surface)] border border-[var(--admin-border)] p-4 min-h-[100px] hover:border-[var(--admin-border-hover)] transition-all duration-300 shadow-[var(--admin-shadow-sm)]">
                        <div className="flex items-center justify-between gap-3">
                            <div className="space-y-0.5">
                                <p className="text-sm font-medium text-[var(--admin-text-muted)]">First Time</p>
                                <p className="text-2xl font-semibold text-purple-600 dark:text-purple-400">{stats.first_time || 0}</p>
                            </div>
                            <div className="flex items-center justify-center h-10 w-10 rounded-lg bg-purple-500/10 flex-shrink-0">
                                <svg className="h-5 w-5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Filters Toolbar */}
                <Card>
                    <div className="p-5">
                        <div className="flex flex-col lg:flex-row items-stretch lg:items-center gap-3">
                            {/* Search */}
                            <form onSubmit={handleSearch} className="flex-1">
                                <div className="relative">
                                    <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg className="w-4 h-4 text-[var(--admin-text-muted)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                    </div>
                                    <input
                                        type="text"
                                        value={searchTerm}
                                        onChange={(e) => setSearchTerm(e.target.value)}
                                        placeholder="Search by name or email..."
                                        className="w-full pl-9 pr-20 py-2 text-sm bg-[var(--admin-surface)] border border-[var(--admin-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#2F6BFF]/20 focus:border-[#2F6BFF] text-[var(--admin-text)] placeholder-[var(--admin-text-muted)] transition-all"
                                    />
                                    <button
                                        type="submit"
                                        className="absolute right-1.5 top-1/2 -translate-y-1/2 px-3 py-1 bg-[#2F6BFF] hover:bg-[#2457D6] text-white text-xs font-medium rounded transition-all"
                                    >
                                        Search
                                    </button>
                                </div>
                            </form>

                            {/* Filters */}
                            <div className="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                                {/* Status */}
                                <select
                                    value={statusFilter}
                                    onChange={(e) => {
                                        setStatusFilter(e.target.value);
                                        handleFilterChange(e.target.value, firstTimeFilter);
                                    }}
                                    className="px-3 py-2 text-sm bg-[var(--admin-surface)] border border-[var(--admin-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#2F6BFF]/20 focus:border-[#2F6BFF] text-[var(--admin-text)] transition-all"
                                >
                                    <option value="all">All Status</option>
                                    <option value="active">Active</option>
                                    <option value="pending">Pending</option>
                                    <option value="canceled">Canceled</option>
                                    <option value="past_due">Past Due</option>
                                </select>

                                {/* Toggle */}
                                <button
                                    onClick={() => {
                                        const newFirstTime = !firstTimeFilter;
                                        setFirstTimeFilter(newFirstTime);
                                        handleFilterChange(statusFilter, newFirstTime);
                                    }}
                                    className={`inline-flex items-center gap-2 px-3 py-2 text-sm font-medium rounded-lg transition-all ${
                                        firstTimeFilter
                                            ? 'bg-[#2F6BFF] text-white'
                                            : 'bg-[var(--admin-surface)] text-[var(--admin-text)] border border-[var(--admin-border)] hover:bg-[var(--admin-hover-bg)]'
                                    }`}
                                >
                                    <svg className="w-4 h-4" fill={firstTimeFilter ? "currentColor" : "none"} stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                    </svg>
                                    First Time
                                </button>
                            </div>
                        </div>
                    </div>
                </Card>

                {/* Data Table Card */}
                <Card>
                    {subscriptions?.data && subscriptions.data.length > 0 ? (
                        <>
                            {/* Table */}
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-[var(--admin-border)]">
                                    <thead className="bg-[var(--admin-hover-bg)]">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-semibold text-[var(--admin-text-muted)] uppercase tracking-wider">Customer</th>
                                            <th className="px-6 py-3 text-left text-xs font-semibold text-[var(--admin-text-muted)] uppercase tracking-wider">Plan</th>
                                            <th className="px-6 py-3 text-left text-xs font-semibold text-[var(--admin-text-muted)] uppercase tracking-wider">Status</th>
                                            <th className="px-6 py-3 text-left text-xs font-semibold text-[var(--admin-text-muted)] uppercase tracking-wider">Tags</th>
                                            <th className="px-6 py-3 text-left text-xs font-semibold text-[var(--admin-text-muted)] uppercase tracking-wider">Subscription ID</th>
                                            <th className="px-6 py-3 text-left text-xs font-semibold text-[var(--admin-text-muted)] uppercase tracking-wider">Created</th>
                                            <th className="px-6 py-3 text-left text-xs font-semibold text-[var(--admin-text-muted)] uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-[var(--admin-border)]">
                                        {subscriptions.data.map((subscription) => (
                                            <tr key={subscription.id} className="hover:bg-[var(--admin-hover-bg)] transition-colors">
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <div className="flex items-center">
                                                        <div className="h-10 w-10 rounded-full bg-[var(--admin-hover-bg)] flex items-center justify-center text-[var(--admin-text)] font-semibold text-sm">
                                                            {subscription.name?.charAt(0).toUpperCase() || 'U'}
                                                        </div>
                                                        <div className="ml-3">
                                                            <div className="text-sm font-medium text-[var(--admin-text)]">{subscription.name}</div>
                                                            <div className="text-xs text-[var(--admin-text-muted)]">{subscription.email}</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    {subscription.plan ? (
                                                        <div>
                                                            <div className="text-sm font-medium text-[var(--admin-text)]">{subscription.plan.name}</div>
                                                            <div className="text-xs text-[var(--admin-text-muted)]">
                                                                ${subscription.plan.price}/{subscription.plan.billing_interval}
                                                            </div>
                                                        </div>
                                                    ) : (
                                                        <span className="text-sm text-[var(--admin-text-muted)]">No Plan</span>
                                                    )}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <span className={`inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-lg border ${getStatusBadge(subscription.subscription_status)}`}>
                                                        {subscription.subscription_status === 'active' && (
                                                            <svg className="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                                                            </svg>
                                                        )}
                                                        {getStatusLabel(subscription.subscription_status)}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <div className="flex flex-wrap gap-1.5">
                                                        {subscription.is_first_time && (
                                                            <span className="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-md bg-purple-500/10 text-purple-700 dark:text-purple-400 border border-purple-500/20">
                                                                <svg className="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                                </svg>
                                                                First
                                                            </span>
                                                        )}
                                                        {subscription.stripe_customer_id && (
                                                            <span className="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-md bg-indigo-500/10 text-indigo-700 dark:text-indigo-400 border border-indigo-500/20" title={`Customer ID: ${subscription.stripe_customer_id}`}>
                                                                <svg className="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                                                </svg>
                                                                Stripe
                                                            </span>
                                                        )}
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <div className="text-xs text-[var(--admin-text-muted)] font-mono">
                                                        {subscription.stripe_subscription_id ? (
                                                            <span title={subscription.stripe_subscription_id}>
                                                                {subscription.stripe_subscription_id.substring(0, 20)}...
                                                            </span>
                                                        ) : (
                                                            <span>N/A</span>
                                                        )}
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-[var(--admin-text-muted)]">
                                                    {subscription.created_at ? new Date(subscription.created_at).toLocaleDateString() : 'N/A'}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm">
                                                    <Link
                                                        href={`/admin/subscriptions/${subscription.id}`}
                                                        className="inline-flex items-center gap-1.5 text-[#2F6BFF] hover:text-[#2457D6] font-medium transition-colors"
                                                    >
                                                        View
                                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                                                        </svg>
                                                    </Link>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            {/* Pagination */}
                            {subscriptions?.links && subscriptions.links.length > 3 && (
                                <div className="px-6 py-4 border-t border-[var(--admin-border)] bg-[var(--admin-hover-bg)]">
                                    <div className="flex flex-col sm:flex-row items-center justify-between gap-4">
                                        <div className="text-sm text-[var(--admin-text-muted)]">
                                            Showing <span className="font-medium text-[var(--admin-text)]">{subscriptions.from || 0}</span> to <span className="font-medium text-[var(--admin-text)]">{subscriptions.to || 0}</span> of <span className="font-medium text-[var(--admin-text)]">{subscriptions.total || 0}</span> results
                                        </div>
                                        <div className="flex flex-wrap gap-2">
                                            {subscriptions.links.map((link, index) => (
                                                <Link
                                                    key={index}
                                                    href={link.url || '#'}
                                                    className={`px-3 py-2 text-sm font-medium rounded-lg transition-all ${
                                                        link.active
                                                            ? 'bg-[#2F6BFF] text-white'
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
                        /* Empty State */
                        <div className="py-16 px-6">
                            <div className="flex flex-col items-center justify-center max-w-md mx-auto text-center">
                                <div className="w-16 h-16 rounded-2xl bg-[var(--admin-hover-bg)] flex items-center justify-center mb-4">
                                    <svg className="w-8 h-8 text-[var(--admin-text-muted)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                    </svg>
                                </div>
                                <h3 className="text-lg font-semibold text-[var(--admin-text)] mb-2">No subscriptions found</h3>
                                <p className="text-sm text-[var(--admin-text-muted)] leading-relaxed mb-6">
                                    {searchTerm || statusFilter !== 'all' || firstTimeFilter
                                        ? "Try adjusting your filters or search terms to find what you're looking for."
                                        : "There are no active subscriptions yet. Subscriptions will appear here once users subscribe to a plan."}
                                </p>
                                {(searchTerm || statusFilter !== 'all' || firstTimeFilter) && (
                                    <button
                                        onClick={() => {
                                            setSearchTerm('');
                                            setStatusFilter('all');
                                            setFirstTimeFilter(false);
                                            router.get('/admin/subscriptions');
                                        }}
                                        className="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-[var(--admin-text)] bg-[var(--admin-surface)] border border-[var(--admin-border)] rounded-lg hover:bg-[var(--admin-hover-bg)] transition-all"
                                    >
                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                        Clear Filters
                                    </button>
                                )}
                            </div>
                        </div>
                    )}
                </Card>
            </div>
        </AdminLayout>
    );
}
