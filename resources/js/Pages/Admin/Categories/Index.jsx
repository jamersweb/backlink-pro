import AdminLayout from '@/Components/Layout/AdminLayout';
import Card from '@/Components/Shared/Card';
import { Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function CategoriesIndex({ categories, parentCategories, subcategories, filters }) {
    const { flash } = usePage().props;
    const [statusFilter, setStatusFilter] = useState(filters.status || 'all');
    const [typeFilter, setTypeFilter] = useState(filters.type || 'all');

    // Calculate stats
    const stats = {
        total: categories?.length || 0,
        active: categories?.filter(c => c.status === 'active').length || 0,
        inactive: categories?.filter(c => c.status === 'inactive').length || 0,
        parent: parentCategories?.length || 0,
    };

    const handleFilter = () => {
        router.get('/admin/categories', {
            status: statusFilter !== 'all' ? statusFilter : undefined,
            type: typeFilter !== 'all' ? typeFilter : undefined,
        }, {
            preserveState: true,
            replace: true,
        });
    };

    const handleDelete = (id) => {
        if (confirm('Are you sure you want to delete this category?')) {
            router.delete(`/admin/categories/${id}`, {
                preserveScroll: true,
            });
        }
    };

    return (
        <AdminLayout header="Backlink Categories">
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

                {/* Page Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-2xl font-bold text-[var(--admin-text)]">Backlink Categories</h2>
                        <p className="text-[var(--admin-text-muted)] mt-1">Organize backlink opportunities by categories</p>
                    </div>
                    <Link
                        href="/admin/categories/create"
                        className="px-4 py-2.5 bg-gradient-to-r from-[#2F6BFF] to-[#2457D6] hover:from-[#2457D6] hover:to-[#1E4BBD] text-white rounded-lg font-medium transition-all duration-200 flex items-center gap-2 shadow-lg shadow-[#2F6BFF]/20"
                    >
                        <i className="bi bi-plus-lg"></i>
                        Create Category
                    </Link>
                </div>

                {/* KPI Stats Cards - Dashboard Style */}
                <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    {/* Total */}
                    <div className="group relative overflow-hidden rounded-xl bg-[var(--admin-surface)] border border-[var(--admin-border)] p-6 hover:border-[var(--admin-border-hover)] transition-all duration-300 shadow-[var(--admin-shadow-sm)]">
                        <div className="absolute top-0 right-0 w-24 h-24 bg-slate-500/10 rounded-full blur-2xl -mr-8 -mt-8 group-hover:bg-slate-500/20 transition-colors dark:opacity-100 opacity-0"></div>
                        <div className="relative flex items-center justify-between">
                            <div>
                                <p className="text-[var(--admin-text-muted)] text-sm font-medium mb-1">Total</p>
                                <p className="text-3xl font-bold text-[var(--admin-text)]">{stats.total}</p>
                                <p className="text-[var(--admin-text-dim)] text-xs mt-2">All categories</p>
                            </div>
                            <div className="flex items-center justify-center h-14 w-14 rounded-xl bg-slate-500/10">
                                <svg className="h-7 w-7 text-slate-600 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
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
                                <p className="text-3xl font-bold text-[var(--admin-text)]">{stats.active}</p>
                                <p className="text-[var(--admin-text-dim)] text-xs mt-2">In use</p>
                            </div>
                            <div className="flex items-center justify-center h-14 w-14 rounded-xl bg-[#12B76A]/15">
                                <svg className="h-7 w-7 text-[#12B76A]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    {/* Inactive */}
                    <div className="group relative overflow-hidden rounded-xl bg-[var(--admin-surface)] border border-[var(--admin-border)] p-6 hover:border-[#F79009]/50 transition-all duration-300 shadow-[var(--admin-shadow-sm)]">
                        <div className="absolute top-0 right-0 w-24 h-24 bg-[#F79009]/10 rounded-full blur-2xl -mr-8 -mt-8 group-hover:bg-[#F79009]/20 transition-colors dark:opacity-100 opacity-0"></div>
                        <div className="relative flex items-center justify-between">
                            <div>
                                <p className="text-[var(--admin-text-muted)] text-sm font-medium mb-1">Inactive</p>
                                <p className="text-3xl font-bold text-[var(--admin-text)]">{stats.inactive}</p>
                                <p className="text-[var(--admin-text-dim)] text-xs mt-2">Not in use</p>
                            </div>
                            <div className="flex items-center justify-center h-14 w-14 rounded-xl bg-[#F79009]/15">
                                <svg className="h-7 w-7 text-[#F79009]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    {/* Parent Categories */}
                    <div className="group relative overflow-hidden rounded-xl bg-[var(--admin-surface)] border border-[var(--admin-border)] p-6 hover:border-[#7F56D9]/50 transition-all duration-300 shadow-[var(--admin-shadow-sm)]">
                        <div className="absolute top-0 right-0 w-24 h-24 bg-[#7F56D9]/10 rounded-full blur-2xl -mr-8 -mt-8 group-hover:bg-[#7F56D9]/20 transition-colors dark:opacity-100 opacity-0"></div>
                        <div className="relative flex items-center justify-between">
                            <div>
                                <p className="text-[var(--admin-text-muted)] text-sm font-medium mb-1">Parents</p>
                                <p className="text-3xl font-bold text-[var(--admin-text)]">{stats.parent}</p>
                                <p className="text-[var(--admin-text-dim)] text-xs mt-2">Top-level</p>
                            </div>
                            <div className="flex items-center justify-center h-14 w-14 rounded-xl bg-[#7F56D9]/15">
                                <svg className="h-7 w-7 text-[#7F56D9]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Filters Toolbar - Dashboard Style */}
                <Card variant="elevated">
                    <div className="p-5">
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <select
                                    value={statusFilter}
                                    onChange={(e) => setStatusFilter(e.target.value)}
                                    className="w-full px-4 py-2.5 bg-[var(--admin-hover-bg)] border border-[var(--admin-border)] rounded-lg text-[var(--admin-text)] focus:outline-none focus:ring-2 focus:ring-[#2F6BFF]/50 focus:border-[#2F6BFF] transition-all"
                                >
                                    <option value="all">All Status</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <div>
                                <select
                                    value={typeFilter}
                                    onChange={(e) => setTypeFilter(e.target.value)}
                                    className="w-full px-4 py-2.5 bg-[var(--admin-hover-bg)] border border-[var(--admin-border)] rounded-lg text-[var(--admin-text)] focus:outline-none focus:ring-2 focus:ring-[#2F6BFF]/50 focus:border-[#2F6BFF] transition-all"
                                >
                                    <option value="all">All Types</option>
                                    <option value="parent">Parent Categories</option>
                                    <option value="subcategory">Subcategories</option>
                                </select>
                            </div>
                            <div>
                                <button
                                    onClick={handleFilter}
                                    className="w-full px-4 py-2.5 bg-gradient-to-r from-[#2F6BFF] to-[#2457D6] hover:from-[#2457D6] hover:to-[#1E4BBD] text-white rounded-lg font-medium transition-all duration-200 flex items-center justify-center gap-2"
                                >
                                    <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                    </svg>
                                    Filter
                                </button>
                            </div>
                        </div>
                    </div>
                </Card>

                {/* Categories Tree - Dashboard Style */}
                <Card variant="elevated">
                    {parentCategories && parentCategories.length > 0 ? (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-[var(--admin-border)]">
                                <thead className="bg-[var(--admin-surface-2)]">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-[var(--admin-text-muted)] uppercase tracking-wider">Name</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-[var(--admin-text-muted)] uppercase tracking-wider">Type</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-[var(--admin-text-muted)] uppercase tracking-wider">Subcategories</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-[var(--admin-text-muted)] uppercase tracking-wider">Status</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-[var(--admin-text-muted)] uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-[var(--admin-border)]">
                                    {parentCategories.map((category) => {
                                        const categorySubcategories = subcategories?.[category.id] || [];
                                        return (
                                            <>
                                                <tr key={category.id} className="hover:bg-[var(--admin-hover-bg)] transition-colors">
                                                    <td className="px-6 py-4">
                                                        <div className="flex items-center gap-3">
                                                            <div className="flex items-center justify-center h-10 w-10 rounded-lg bg-gradient-to-br from-[#7F56D9]/20 to-[#7F56D9]/5 border border-[#7F56D9]/20">
                                                                <svg className="h-5 w-5 text-[#7F56D9]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                                                </svg>
                                                            </div>
                                                            <div>
                                                                <div className="text-sm font-medium text-[var(--admin-text)]">{category.name}</div>
                                                                {category.description && (
                                                                    <div className="text-xs text-[var(--admin-text-dim)] mt-1">{category.description}</div>
                                                                )}
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <span className="px-3 py-1 text-xs font-semibold rounded-full bg-[#7F56D9]/15 text-[#7F56D9] border border-[#7F56D9]/30">
                                                            Parent
                                                        </span>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-[var(--admin-text-muted)]">
                                                        <span className="font-medium text-[var(--admin-text)]">{categorySubcategories.length}</span> subcategory(ies)
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <span className={`px-3 py-1 text-xs font-semibold rounded-full ${
                                                            category.status === 'active' 
                                                                ? 'bg-[#12B76A]/15 text-[#12B76A] border border-[#12B76A]/30' 
                                                                : 'bg-[var(--admin-surface-2)] text-[var(--admin-text-muted)] border border-[var(--admin-border)]'
                                                        }`}>
                                                            {category.status}
                                                        </span>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm">
                                                        <div className="flex items-center gap-2">
                                                            <Link 
                                                                href={`/admin/categories/${category.id}/edit`} 
                                                                className="p-1.5 rounded-lg hover:bg-[var(--admin-surface-2)] text-[var(--admin-text-muted)] hover:text-[#2F6BFF] transition-colors"
                                                                title="Edit"
                                                            >
                                                                <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                                </svg>
                                                            </Link>
                                                            <button
                                                                onClick={() => handleDelete(category.id)}
                                                                className="p-1.5 rounded-lg hover:bg-[var(--admin-surface-2)] text-[var(--admin-text-muted)] hover:text-[#F04438] transition-colors"
                                                                title="Delete"
                                                            >
                                                                <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                                </svg>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                                {categorySubcategories.map((subcat) => (
                                                    <tr key={subcat.id} className="hover:bg-[var(--admin-hover-bg)] transition-colors bg-[var(--admin-surface-2)]/50">
                                                        <td className="px-6 py-4">
                                                            <div className="flex items-center gap-3 pl-8">
                                                                <div className="flex items-center justify-center h-8 w-8 rounded-lg bg-gradient-to-br from-[#2F6BFF]/20 to-[#2F6BFF]/5 border border-[#2F6BFF]/20">
                                                                    <svg className="h-4 w-4 text-[#5B8AFF]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                                                    </svg>
                                                                </div>
                                                                <div>
                                                                    <div className="text-sm font-medium text-[var(--admin-text)]">
                                                                        {subcat.name}
                                                                    </div>
                                                                    {subcat.description && (
                                                                        <div className="text-xs text-[var(--admin-text-dim)] mt-1">{subcat.description}</div>
                                                                    )}
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap">
                                                            <span className="px-3 py-1 text-xs font-semibold rounded-full bg-[#2F6BFF]/15 text-[#5B8AFF] border border-[#2F6BFF]/30">
                                                                Subcategory
                                                            </span>
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-[var(--admin-text-dim)]">-</td>
                                                        <td className="px-6 py-4 whitespace-nowrap">
                                                            <span className={`px-3 py-1 text-xs font-semibold rounded-full ${
                                                                subcat.status === 'active' 
                                                                    ? 'bg-[#12B76A]/15 text-[#12B76A] border border-[#12B76A]/30' 
                                                                    : 'bg-[var(--admin-surface-2)] text-[var(--admin-text-muted)] border border-[var(--admin-border)]'
                                                            }`}>
                                                                {subcat.status}
                                                            </span>
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-sm">
                                                            <div className="flex items-center gap-2">
                                                                <Link 
                                                                    href={`/admin/categories/${subcat.id}/edit`} 
                                                                    className="p-1.5 rounded-lg hover:bg-[var(--admin-surface-2)] text-[var(--admin-text-muted)] hover:text-[#2F6BFF] transition-colors"
                                                                    title="Edit"
                                                                >
                                                                    <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                                    </svg>
                                                                </Link>
                                                                <button
                                                                    onClick={() => handleDelete(subcat.id)}
                                                                    className="p-1.5 rounded-lg hover:bg-[var(--admin-surface-2)] text-[var(--admin-text-muted)] hover:text-[#F04438] transition-colors"
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
                                            </>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    ) : (
                        <div className="text-center py-16">
                            <div className="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-[var(--admin-surface-2)] mb-4">
                                <svg className="h-8 w-8 text-[var(--admin-text-dim)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                </svg>
                            </div>
                            <p className="text-[var(--admin-text-muted)] font-medium">No categories found</p>
                            <p className="text-[var(--admin-text-dim)] text-sm mt-1">Create your first category to get started</p>
                            <Link
                                href="/admin/categories/create"
                                className="inline-flex items-center gap-2 mt-4 px-4 py-2 bg-gradient-to-r from-[#2F6BFF] to-[#2457D6] hover:from-[#2457D6] hover:to-[#1E4BBD] text-white rounded-lg text-sm font-medium transition-colors shadow-lg shadow-[#2F6BFF]/20"
                            >
                                <i className="bi bi-plus-lg"></i>
                                Create Category
                            </Link>
                        </div>
                    )}
                </Card>
            </div>
        </AdminLayout>
    );
}

