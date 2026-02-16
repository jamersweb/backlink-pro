import AdminLayout from '@/Components/Layout/AdminLayout';
import Card from '@/Components/Shared/Card';
import { Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function CategoriesCreate({ parentCategories }) {
    const { flash, errors } = usePage().props;
    const [form, setForm] = useState({
        name: '',
        parent_id: '',
        status: 'active',
        description: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        router.post('/admin/categories', form, {
            preserveScroll: true,
            onSuccess: () => {
                router.visit('/admin/categories');
            },
        });
    };

    return (
        <AdminLayout header="Create Category">
            <div className="max-w-4xl mx-auto">
                {/* Page Header */}
                <div className="mb-6">
                    <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h1 className="text-2xl font-bold text-[var(--admin-text)] mb-1">Create New Category</h1>
                            <p className="text-sm text-[var(--admin-text-muted)]">Add a new category to organize backlink opportunities</p>
                        </div>
                        <div className="flex items-center gap-3">
                            <Link href="/admin/categories">
                                <button
                                    type="button"
                                    className="px-4 py-2.5 text-sm font-medium text-[var(--admin-text)] bg-[var(--admin-surface)] border border-[var(--admin-border)] rounded-lg hover:bg-[var(--admin-hover-bg)] transition-all"
                                >
                                    Cancel
                                </button>
                            </Link>
                            <button
                                type="submit"
                                form="category-form"
                                className="px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-[#2F6BFF] to-[#2457D6] hover:from-[#2457D6] hover:to-[#1E4BBD] rounded-lg shadow-lg shadow-[#2F6BFF]/20 transition-all"
                            >
                                Save Category
                            </button>
                        </div>
                    </div>
                </div>

                {/* Success/Error Messages */}
                {flash?.success && (
                    <div className="mb-6 p-4 rounded-lg bg-[#12B76A]/10 border border-[#12B76A]/30">
                        <p className="text-sm text-[#12B76A] font-medium">{flash.success}</p>
                    </div>
                )}
                {flash?.error && (
                    <div className="mb-6 p-4 rounded-lg bg-[#F04438]/10 border border-[#F04438]/30">
                        <p className="text-sm text-[#F04438] font-medium">{flash.error}</p>
                    </div>
                )}

                {/* Form */}
                <form id="category-form" onSubmit={handleSubmit} className="space-y-6">
                    {/* Basic Information */}
                    <Card variant="elevated">
                        <div className="p-6">
                            <div className="flex items-center gap-3 mb-6">
                                <div className="flex items-center justify-center w-10 h-10 rounded-xl bg-[#7F56D9]/15">
                                    <svg className="h-5 w-5 text-[#7F56D9]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 className="text-lg font-semibold text-[var(--admin-text)]">Basic Information</h3>
                                    <p className="text-sm text-[var(--admin-text-muted)]">Category name and parent relationship</p>
                                </div>
                            </div>

                            <div className="space-y-6">
                                <div>
                                    <label className="block text-sm font-semibold text-[var(--admin-text)] mb-2">
                                        Category Name <span className="text-[#F04438]">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        required
                                        value={form.name}
                                        onChange={(e) => setForm({ ...form, name: e.target.value })}
                                        className="w-full px-4 py-2.5 bg-[var(--admin-hover-bg)] border border-[var(--admin-border)] rounded-lg text-[var(--admin-text)] placeholder-[var(--admin-text-muted)] focus:outline-none focus:ring-2 focus:ring-[#2F6BFF]/50 focus:border-[#2F6BFF] transition-all"
                                        placeholder="e.g., Business, Technology, Health"
                                    />
                                    {errors?.name && <p className="mt-1 text-sm text-[#F04438]">{errors.name}</p>}
                                    <p className="mt-1 text-xs text-[var(--admin-text-dim)]">Unique name for this category</p>
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label className="block text-sm font-semibold text-[var(--admin-text)] mb-2">
                                            Parent Category
                                        </label>
                                        <select
                                            value={form.parent_id}
                                            onChange={(e) => setForm({ ...form, parent_id: e.target.value })}
                                            className="w-full px-4 py-2.5 bg-[var(--admin-hover-bg)] border border-[var(--admin-border)] rounded-lg text-[var(--admin-text)] focus:outline-none focus:ring-2 focus:ring-[#2F6BFF]/50 focus:border-[#2F6BFF] transition-all"
                                        >
                                            <option value="">None (Top-level Category)</option>
                                            {parentCategories?.map((cat) => (
                                                <option key={cat.id} value={cat.id}>
                                                    {cat.name}
                                                </option>
                                            ))}
                                        </select>
                                        {errors?.parent_id && <p className="mt-1 text-sm text-[#F04438]">{errors.parent_id}</p>}
                                        <p className="mt-1 text-xs text-[var(--admin-text-dim)]">
                                            Leave empty for parent category
                                        </p>
                                    </div>

                                    <div>
                                        <label className="block text-sm font-semibold text-[var(--admin-text)] mb-2">
                                            Status <span className="text-[#F04438]">*</span>
                                        </label>
                                        <select
                                            required
                                            value={form.status}
                                            onChange={(e) => setForm({ ...form, status: e.target.value })}
                                            className="w-full px-4 py-2.5 bg-[var(--admin-hover-bg)] border border-[var(--admin-border)] rounded-lg text-[var(--admin-text)] focus:outline-none focus:ring-2 focus:ring-[#2F6BFF]/50 focus:border-[#2F6BFF] transition-all"
                                        >
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                        </select>
                                        {errors?.status && <p className="mt-1 text-sm text-[#F04438]">{errors.status}</p>}
                                        <p className="mt-1 text-xs text-[var(--admin-text-dim)]">Category availability</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Card>

                    {/* Description */}
                    <Card variant="elevated">
                        <div className="p-6">
                            <div className="flex items-center gap-3 mb-6">
                                <div className="flex items-center justify-center w-10 h-10 rounded-xl bg-[#2F6BFF]/15">
                                    <svg className="h-5 w-5 text-[#5B8AFF]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h7" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 className="text-lg font-semibold text-[var(--admin-text)]">Description</h3>
                                    <p className="text-sm text-[var(--admin-text-muted)]">Provide additional details about this category</p>
                                </div>
                            </div>

                            <div>
                                <label className="block text-sm font-semibold text-[var(--admin-text)] mb-2">
                                    Description (Optional)
                                </label>
                                <textarea
                                    value={form.description}
                                    onChange={(e) => setForm({ ...form, description: e.target.value })}
                                    rows={4}
                                    className="w-full px-4 py-2.5 bg-[var(--admin-hover-bg)] border border-[var(--admin-border)] rounded-lg text-[var(--admin-text)] placeholder-[var(--admin-text-muted)] focus:outline-none focus:ring-2 focus:ring-[#2F6BFF]/50 focus:border-[#2F6BFF] transition-all resize-none"
                                    placeholder="Brief description of this category..."
                                />
                                {errors?.description && <p className="mt-1 text-sm text-[#F04438]">{errors.description}</p>}
                                <p className="mt-1 text-xs text-[var(--admin-text-dim)]">Optional category description</p>
                            </div>
                        </div>
                    </Card>

                    {/* Sticky Bottom Actions (mobile) */}
                    <div className="sticky bottom-0 left-0 right-0 p-4 bg-[var(--admin-surface)] border-t border-[var(--admin-border)] flex items-center justify-end gap-3 md:hidden">
                        <Link href="/admin/categories">
                            <button
                                type="button"
                                className="px-4 py-2.5 text-sm font-medium text-[var(--admin-text)] bg-[var(--admin-surface)] border border-[var(--admin-border)] rounded-lg hover:bg-[var(--admin-hover-bg)] transition-all"
                            >
                                Cancel
                            </button>
                        </Link>
                        <button
                            type="submit"
                            className="px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-[#2F6BFF] to-[#2457D6] hover:from-[#2457D6] hover:to-[#1E4BBD] rounded-lg shadow-lg shadow-[#2F6BFF]/20 transition-all"
                        >
                            Save Category
                        </button>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}

