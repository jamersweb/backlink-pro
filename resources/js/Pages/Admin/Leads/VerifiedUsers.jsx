import AdminLayout from '@/Components/Layout/AdminLayout';
import Card from '@/Components/Shared/Card';
import { Link } from '@inertiajs/react';
import { useState } from 'react';

export default function VerifiedUsers({ users, total }) {
    const [searchQuery, setSearchQuery] = useState('');
    const [filterPlan, setFilterPlan] = useState('all');

    // Filter users based on search and plan filter
    const filteredUsers = users?.data?.filter(user => {
        const matchesSearch = !searchQuery || 
            user.name?.toLowerCase().includes(searchQuery.toLowerCase()) ||
            user.email?.toLowerCase().includes(searchQuery.toLowerCase());
        
        const matchesPlan = filterPlan === 'all' || 
            (filterPlan === 'no-plan' && !user.plan) ||
            (filterPlan === 'has-plan' && user.plan);
        
        return matchesSearch && matchesPlan;
    }) || [];

    const formatDate = (dateString) => {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { 
            month: 'short', 
            day: 'numeric', 
            year: 'numeric' 
        });
    };

    const copyToClipboard = (text) => {
        navigator.clipboard.writeText(text);
        // Optional: Add toast notification here
    };

    return (
        <AdminLayout header="Verified Users">
            <div className="space-y-6">
                {/* Stats Card - Premium Design */}
                <Card className="relative overflow-hidden">
                    <div className="p-6">
                        <div className="flex items-center justify-between">
                            <div className="flex items-start gap-4">
                                {/* Icon Chip */}
                                <div className="flex-shrink-0">
                                    <div className="w-14 h-14 rounded-xl bg-gradient-to-br from-emerald-500/10 to-emerald-600/10 border border-emerald-500/20 flex items-center justify-center">
                                        <svg className="w-7 h-7 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                </div>
                                
                                <div>
                                    <p className="text-sm font-medium text-[var(--admin-text-muted)] uppercase tracking-wide mb-1">
                                        Total Verified Users
                                    </p>
                                    <p className="text-5xl font-bold text-[var(--admin-text)] mb-2">
                                        {total || 0}
                                    </p>
                                    <p className="text-sm text-[var(--admin-text-muted)] flex items-center gap-2">
                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                        </svg>
                                        <span>Users who have verified their email</span>
                                    </p>
                                </div>
                            </div>

                            {/* Decorative Element */}
                            <div className="hidden sm:block">
                                <div className="w-32 h-32 rounded-2xl bg-gradient-to-br from-emerald-500/5 to-emerald-600/5 border border-emerald-500/10 flex items-center justify-center">
                                    <svg className="w-16 h-16 text-emerald-500/20" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
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
                                <h2 className="text-lg font-semibold text-[var(--admin-text)]">Verified Users</h2>
                                <p className="text-sm text-[var(--admin-text-muted)] mt-0.5">
                                    Manage and monitor verified user accounts
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
                                        className="pl-10 pr-4 py-2 w-full lg:w-64 text-sm bg-[var(--admin-surface)] border border-[var(--admin-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 text-[var(--admin-text)] placeholder-[var(--admin-text-muted)] transition-all"
                                    />
                                </div>

                                {/* Filter Dropdown */}
                                <select
                                    value={filterPlan}
                                    onChange={(e) => setFilterPlan(e.target.value)}
                                    className="px-4 py-2 text-sm bg-[var(--admin-surface)] border border-[var(--admin-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 text-[var(--admin-text)] transition-all"
                                >
                                    <option value="all">All Plans</option>
                                    <option value="has-plan">Has Plan</option>
                                    <option value="no-plan">No Plan</option>
                                </select>

                                {/* Export Button */}
                                <button className="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-[var(--admin-text)] bg-[var(--admin-surface)] border border-[var(--admin-border)] rounded-lg hover:bg-[var(--admin-hover-bg)] hover:border-emerald-500/50 transition-all">
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
                                            Verified At
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-[var(--admin-text-muted)] uppercase tracking-wider">
                                            Status
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
                                                    <div className="flex-shrink-0 w-10 h-10 rounded-full bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center text-white font-semibold text-sm shadow-sm">
                                                        {user.name?.charAt(0).toUpperCase() || 'U'}
                                                    </div>
                                                    <div>
                                                        <div className="text-sm font-medium text-[var(--admin-text)]">
                                                            {user.name}
                                                        </div>
                                                        <div className="text-xs text-[var(--admin-text-muted)]">
                                                            Verified user
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
                                                    <span className="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-full bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20">
                                                        <svg className="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                                                        </svg>
                                                        {user.plan.name}
                                                    </span>
                                                ) : (
                                                    <span className="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-full bg-gray-500/10 text-gray-700 dark:text-gray-400 border border-gray-500/20">
                                                        <svg className="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
                                                        </svg>
                                                        No Plan
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="text-sm text-[var(--admin-text)]">
                                                    {formatDate(user.email_verified_at)}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <span className="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-full bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20">
                                                    <svg className="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                                                    </svg>
                                                    Verified
                                                </span>
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
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <p className="text-[var(--admin-text)] font-medium text-lg">No verified users found</p>
                            <p className="text-[var(--admin-text-muted)] text-sm mt-2">
                                {searchQuery || filterPlan !== 'all' 
                                    ? 'Try adjusting your search or filters'
                                    : 'Verified users will appear here'
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
                                                    ? 'bg-emerald-500 text-white shadow-sm'
                                                    : 'bg-[var(--admin-surface)] text-[var(--admin-text)] hover:bg-[var(--admin-hover-bg)] border border-[var(--admin-border)] hover:border-emerald-500/50'
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

