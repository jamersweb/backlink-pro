import AdminLayout from '@/Components/Layout/AdminLayout';
import Card from '@/Components/Shared/Card';
import { Link } from '@inertiajs/react';
import { useState } from 'react';

export default function PurchaseUsers({ users, total }) {
    const [searchQuery, setSearchQuery] = useState('');
    const [filterStatus, setFilterStatus] = useState('all');

    // Filter users based on search and status filter
    const filteredUsers = users?.data?.filter(user => {
        const matchesSearch = !searchQuery || 
            user.name?.toLowerCase().includes(searchQuery.toLowerCase()) ||
            user.email?.toLowerCase().includes(searchQuery.toLowerCase());
        
        const matchesStatus = filterStatus === 'all' || 
            (filterStatus === 'active' && user.subscription_status === 'active') ||
            (filterStatus === 'inactive' && user.subscription_status !== 'active');
        
        return matchesSearch && matchesStatus;
    }) || [];

    const copyToClipboard = (text) => {
        navigator.clipboard.writeText(text);
    };

    const getStatusColor = (status) => {
        switch(status?.toLowerCase()) {
            case 'active':
                return 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-500/20';
            case 'canceled':
            case 'cancelled':
                return 'bg-red-500/10 text-red-700 dark:text-red-400 border-red-500/20';
            case 'trialing':
                return 'bg-blue-500/10 text-blue-700 dark:text-blue-400 border-blue-500/20';
            default:
                return 'bg-gray-500/10 text-gray-700 dark:text-gray-400 border-gray-500/20';
        }
    };

    return (
        <AdminLayout header="Purchase Users">
            <div className="space-y-6">
                {/* Stats Card - Premium Design */}
                <Card className="relative overflow-hidden">
                    <div className="p-6">
                        <div className="flex items-center justify-between">
                            <div className="flex items-start gap-4">
                                {/* Icon Chip */}
                                <div className="flex-shrink-0">
                                    <div className="w-14 h-14 rounded-xl bg-gradient-to-br from-blue-500/10 to-blue-600/10 border border-blue-500/20 flex items-center justify-center">
                                        <svg className="w-7 h-7 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                        </svg>
                                    </div>
                                </div>
                                
                                <div>
                                    <p className="text-sm font-medium text-[var(--admin-text-muted)] uppercase tracking-wide mb-1">
                                        Total Purchase Users
                                    </p>
                                    <p className="text-5xl font-bold text-[var(--admin-text)] mb-2">
                                        {total || 0}
                                    </p>
                                    <p className="text-sm text-[var(--admin-text-muted)] flex items-center gap-2">
                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                        </svg>
                                        <span>Users with active subscriptions</span>
                                    </p>
                                </div>
                            </div>

                            {/* Decorative Element */}
                            <div className="hidden sm:block">
                                <div className="w-32 h-32 rounded-2xl bg-gradient-to-br from-blue-500/5 to-blue-600/5 border border-blue-500/10 flex items-center justify-center">
                                    <svg className="w-16 h-16 text-blue-500/20" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </Card>

                {/* Users Table Card - Premium Design */}
                <Card className="overflow-hidden">
                    {/* Table Header with Tools */}
                    <div className="px-6 py-5 border-b border-[var(--admin-border)] bg-[var(--admin-hover-bg)]">
                        <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                            <div>
                                <h2 className="text-lg font-semibold text-[var(--admin-text)]">Purchase Users</h2>
                                <p className="text-sm text-[var(--admin-text-muted)] mt-0.5">
                                    Users with active subscriptions and payments
                                </p>
                            </div>
                            
                            {/* Tools Row */}
                            <div className="flex flex-wrap items-center gap-3">
                                {/* Search Input */}
                                <div className="relative flex-1 lg:flex-initial">
                                    <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg className="h-4 w-4 text-[var(--admin-text-muted)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                    </div>
                                    <input
                                        type="text"
                                        placeholder="Search by name or email..."
                                        value={searchQuery}
                                        onChange={(e) => setSearchQuery(e.target.value)}
                                        className="pl-10 pr-4 py-2 w-full lg:w-64 text-sm bg-[var(--admin-surface)] border border-[var(--admin-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 text-[var(--admin-text)] placeholder-[var(--admin-text-muted)] transition-all"
                                    />
                                </div>

                                {/* Filter Dropdown */}
                                <select
                                    value={filterStatus}
                                    onChange={(e) => setFilterStatus(e.target.value)}
                                    className="px-4 py-2 text-sm bg-[var(--admin-surface)] border border-[var(--admin-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 text-[var(--admin-text)] transition-all"
                                >
                                    <option value="all">All Status</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>

                                {/* Export Button */}
                                <button className="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-[var(--admin-text)] bg-[var(--admin-surface)] border border-[var(--admin-border)] rounded-lg hover:bg-[var(--admin-hover-bg)] hover:border-blue-500/50 transition-all">
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    Export CSV
                                </button>
                            </div>
                        </div>
                    </div>

                    {/* Table Content */}
                    {filteredUsers.length > 0 ? (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-[var(--admin-border)]">
                                <thead>
                                    <tr className="bg-[var(--admin-hover-bg)]">
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-[var(--admin-text-muted)] uppercase tracking-wider">
                                            Name
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-[var(--admin-text-muted)] uppercase tracking-wider">
                                            Email
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-[var(--admin-text-muted)] uppercase tracking-wider">
                                            Plan
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-[var(--admin-text-muted)] uppercase tracking-wider">
                                            Subscription
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-[var(--admin-text-muted)] uppercase tracking-wider">
                                            Verified
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-[var(--admin-border)]">
                                    {filteredUsers.map((user) => (
                                        <tr 
                                            key={user.id} 
                                            className="group hover:bg-[var(--admin-hover-bg)] transition-all duration-150"
                                        >
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="flex items-center gap-3">
                                                    <div className="flex-shrink-0 w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white font-semibold text-sm shadow-sm">
                                                        {user.name?.charAt(0).toUpperCase() || 'U'}
                                                    </div>
                                                    <div>
                                                        <div className="text-sm font-medium text-[var(--admin-text)]">
                                                            {user.name}
                                                        </div>
                                                        <div className="text-xs text-[var(--admin-text-muted)]">
                                                            Paying customer
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="flex items-center gap-2 group/email">
                                                    <span className="text-sm text-[var(--admin-text)]">{user.email}</span>
                                                    <button
                                                        onClick={() => copyToClipboard(user.email)}
                                                        className="opacity-0 group-hover/email:opacity-100 p-1 rounded hover:bg-[var(--admin-surface)] transition-all"
                                                        title="Copy email"
                                                    >
                                                        <svg className="w-4 h-4 text-[var(--admin-text-muted)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                {user.plan ? (
                                                    <div className="flex flex-col gap-1">
                                                        <span className="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-full bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20 w-fit">
                                                            <svg className="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                                                            </svg>
                                                            {user.plan.name}
                                                        </span>
                                                        {user.plan.price && (
                                                            <span className="text-xs text-[var(--admin-text-muted)] font-medium">
                                                                ${user.plan.price}/{user.plan.billing_interval || 'mo'}
                                                            </span>
                                                        )}
                                                    </div>
                                                ) : (
                                                    <span className="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-full bg-gray-500/10 text-gray-700 dark:text-gray-400 border border-gray-500/20">
                                                        No Plan
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <span className={`inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-full border ${getStatusColor(user.subscription_status)}`}>
                                                    {user.subscription_status === 'active' && (
                                                        <svg className="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                                                        </svg>
                                                    )}
                                                    {user.subscription_status || 'N/A'}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                {user.email_verified_at ? (
                                                    <span className="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-full bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20">
                                                        <svg className="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                                                        </svg>
                                                        Yes
                                                    </span>
                                                ) : (
                                                    <span className="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-full bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/20">
                                                        <svg className="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                                                        </svg>
                                                        No
                                                    </span>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ) : (
                        <div className="text-center py-16">
                            <div className="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-[var(--admin-hover-bg)] border border-[var(--admin-border)] mb-4">
                                <svg className="w-8 h-8 text-[var(--admin-text-muted)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                </svg>
                            </div>
                            <p className="text-[var(--admin-text)] font-medium text-lg">No purchase users found</p>
                            <p className="text-[var(--admin-text-muted)] text-sm mt-2">
                                {searchQuery || filterStatus !== 'all' 
                                    ? 'Try adjusting your search or filters'
                                    : 'Users with active subscriptions will appear here'
                                }
                            </p>
                        </div>
                    )}

                    {/* Pagination */}
                    {users?.links && users.links.length > 3 && filteredUsers.length > 0 && (
                        <div className="px-6 py-4 border-t border-[var(--admin-border)] bg-[var(--admin-hover-bg)]">
                            <div className="flex flex-col sm:flex-row items-center justify-between gap-4">
                                <div className="text-sm text-[var(--admin-text-muted)]">
                                    Showing <span className="font-medium text-[var(--admin-text)]">{users.from || 0}</span> to <span className="font-medium text-[var(--admin-text)]">{users.to || 0}</span> of <span className="font-medium text-[var(--admin-text)]">{users.total || 0}</span> results
                                </div>
                                <div className="flex flex-wrap gap-2">
                                    {users.links.map((link, index) => (
                                        <Link
                                            key={index}
                                            href={link.url || '#'}
                                            className={`px-3 py-2 text-sm font-medium rounded-lg transition-all duration-150 ${
                                                link.active
                                                    ? 'bg-blue-500 text-white shadow-sm'
                                                    : 'bg-[var(--admin-surface)] text-[var(--admin-text)] hover:bg-[var(--admin-hover-bg)] border border-[var(--admin-border)] hover:border-blue-500/50'
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
