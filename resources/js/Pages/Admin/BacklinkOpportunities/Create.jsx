import AdminLayout from '@/Components/Layout/AdminLayout';
import Card from '@/Components/Shared/Card';
import { Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function BacklinkOpportunitiesCreate({ categories }) {
    const { flash, errors } = usePage().props;
    const [form, setForm] = useState({
        url: '',
        pa: '',
        da: '',
        site_type: 'comment',
        status: 'active',
        daily_site_limit: '',
        category_ids: [],
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        if (form.category_ids.length === 0) {
            alert('Please select at least one category');
            return;
        }
        router.post('/admin/backlink-opportunities', form, {
            preserveScroll: true,
            onSuccess: () => {
                router.visit('/admin/backlink-opportunities');
            },
        });
    };

    const toggleCategory = (categoryId) => {
        setForm({
            ...form,
            category_ids: form.category_ids.includes(categoryId)
                ? form.category_ids.filter(id => id !== categoryId)
                : [...form.category_ids, categoryId],
        });
    };

    // Group categories by parent
    const parentCategories = categories?.filter(cat => !cat.parent_id) || [];
    const subcategories = categories?.filter(cat => cat.parent_id) || [];
    const subcategoriesByParent = subcategories.reduce((acc, subcat) => {
        if (!acc[subcat.parent_id]) acc[subcat.parent_id] = [];
        acc[subcat.parent_id].push(subcat);
        return acc;
    }, {});

    return (
        <AdminLayout header="Create Backlink Opportunity">
            <div className="max-w-4xl mx-auto">
                {/* Page Header */}
                <div className="mb-6">
                    <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h1 className="text-2xl font-bold text-[var(--admin-text)] mb-1">Add New Opportunity</h1>
                            <p className="text-sm text-[var(--admin-text-muted)]">Add a new backlink opportunity to the database</p>
                        </div>
                        <div className="flex items-center gap-3">
                            <Link href="/admin/backlink-opportunities">
                                <button
                                    type="button"
                                    className="px-4 py-2.5 text-sm font-medium text-[var(--admin-text)] bg-[var(--admin-surface)] border border-[var(--admin-border)] rounded-lg hover:bg-[var(--admin-hover-bg)] transition-all"
                                >
                                    Cancel
                                </button>
                            </Link>
                            <button
                                type="submit"
                                form="opportunity-form"
                                className="px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-[#2F6BFF] to-[#2457D6] hover:from-[#2457D6] hover:to-[#1E4BBD] rounded-lg shadow-lg shadow-[#2F6BFF]/20 transition-all"
                            >
                                Save Opportunity
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
                <form id="opportunity-form" onSubmit={handleSubmit} className="space-y-6">
                    {/* Website Details */}
                    <Card variant="elevated">
                        <div className="p-6">
                            <div className="flex items-center gap-3 mb-6">
                                <div className="flex items-center justify-center w-10 h-10 rounded-xl bg-[#12B76A]/15">
                                    <svg className="h-5 w-5 text-[#12B76A]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 className="text-lg font-semibold text-[var(--admin-text)]">Website Details</h3>
                                    <p className="text-sm text-[var(--admin-text-muted)]">Opportunity URL and authority metrics</p>
                                </div>
                            </div>

                            <div className="space-y-6">
                                <div>
                                    <label className="block text-sm font-semibold text-[var(--admin-text)] mb-2">
                                        URL <span className="text-[#F04438]">*</span>
                                    </label>
                                    <input
                                        type="url"
                                        required
                                        value={form.url}
                                        onChange={(e) => setForm({ ...form, url: e.target.value })}
                                        className="w-full px-4 py-2.5 bg-[var(--admin-hover-bg)] border border-[var(--admin-border)] rounded-lg text-[var(--admin-text)] placeholder-[var(--admin-text-muted)] focus:outline-none focus:ring-2 focus:ring-[#2F6BFF]/50 focus:border-[#2F6BFF] transition-all"
                                        placeholder="https://example.com/page"
                                    />
                                    {errors?.url && <p className="mt-1 text-sm text-[#F04438]">{errors.url}</p>}
                                    <p className="mt-1 text-xs text-[var(--admin-text-dim)]">Full URL of the backlink opportunity</p>
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label className="block text-sm font-semibold text-[var(--admin-text)] mb-2">
                                            PA (Page Authority)
                                        </label>
                                        <input
                                            type="number"
                                            min="0"
                                            max="100"
                                            value={form.pa}
                                            onChange={(e) => setForm({ ...form, pa: e.target.value })}
                                            className="w-full px-4 py-2.5 bg-[var(--admin-hover-bg)] border border-[var(--admin-border)] rounded-lg text-[var(--admin-text)] placeholder-[var(--admin-text-muted)] focus:outline-none focus:ring-2 focus:ring-[#2F6BFF]/50 focus:border-[#2F6BFF] transition-all"
                                            placeholder="0-100"
                                        />
                                        {errors?.pa && <p className="mt-1 text-sm text-[#F04438]">{errors.pa}</p>}
                                        <p className="mt-1 text-xs text-[var(--admin-text-dim)]">Page authority score</p>
                                    </div>
                                    <div>
                                        <label className="block text-sm font-semibold text-[var(--admin-text)] mb-2">
                                            DA (Domain Authority)
                                        </label>
                                        <input
                                            type="number"
                                            min="0"
                                            max="100"
                                            value={form.da}
                                            onChange={(e) => setForm({ ...form, da: e.target.value })}
                                            className="w-full px-4 py-2.5 bg-[var(--admin-hover-bg)] border border-[var(--admin-border)] rounded-lg text-[var(--admin-text)] placeholder-[var(--admin-text-muted)] focus:outline-none focus:ring-2 focus:ring-[#2F6BFF]/50 focus:border-[#2F6BFF] transition-all"
                                            placeholder="0-100"
                                        />
                                        {errors?.da && <p className="mt-1 text-sm text-[#F04438]">{errors.da}</p>}
                                        <p className="mt-1 text-xs text-[var(--admin-text-dim)]">Domain authority score</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Card>

                    {/* Settings */}
                    <Card variant="elevated">
                        <div className="p-6">
                            <div className="flex items-center gap-3 mb-6">
                                <div className="flex items-center justify-center w-10 h-10 rounded-xl bg-[#F79009]/15">
                                    <svg className="h-5 w-5 text-[#F79009]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 className="text-lg font-semibold text-[var(--admin-text)]">Settings</h3>
                                    <p className="text-sm text-[var(--admin-text-muted)]">Site type, status, and limits</p>
                                </div>
                            </div>

                            <div className="space-y-6">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label className="block text-sm font-semibold text-[var(--admin-text)] mb-2">
                                            Site Type <span className="text-[#F04438]">*</span>
                                        </label>
                                        <select
                                            required
                                            value={form.site_type}
                                            onChange={(e) => setForm({ ...form, site_type: e.target.value })}
                                            className="w-full px-4 py-2.5 bg-[var(--admin-hover-bg)] border border-[var(--admin-border)] rounded-lg text-[var(--admin-text)] focus:outline-none focus:ring-2 focus:ring-[#2F6BFF]/50 focus:border-[#2F6BFF] transition-all"
                                        >
                                            <option value="comment">Comment</option>
                                            <option value="profile">Profile</option>
                                            <option value="forum">Forum</option>
                                            <option value="guestposting">Guest Post</option>
                                            <option value="other">Other</option>
                                        </select>
                                        {errors?.site_type && <p className="mt-1 text-sm text-[#F04438]">{errors.site_type}</p>}
                                        <p className="mt-1 text-xs text-[var(--admin-text-dim)]">Type of backlink opportunity</p>
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
                                            <option value="banned">Banned</option>
                                        </select>
                                        {errors?.status && <p className="mt-1 text-sm text-[#F04438]">{errors.status}</p>}
                                        <p className="mt-1 text-xs text-[var(--admin-text-dim)]">Opportunity availability</p>
                                    </div>
                                </div>

                                <div>
                                    <label className="block text-sm font-semibold text-[var(--admin-text)] mb-2">
                                        Daily Site Limit (Optional)
                                    </label>
                                    <input
                                        type="number"
                                        min="0"
                                        value={form.daily_site_limit}
                                        onChange={(e) => setForm({ ...form, daily_site_limit: e.target.value })}
                                        className="w-full px-4 py-2.5 bg-[var(--admin-hover-bg)] border border-[var(--admin-border)] rounded-lg text-[var(--admin-text)] placeholder-[var(--admin-text-muted)] focus:outline-none focus:ring-2 focus:ring-[#2F6BFF]/50 focus:border-[#2F6BFF] transition-all"
                                        placeholder="Max links per day for this site"
                                    />
                                    {errors?.daily_site_limit && <p className="mt-1 text-sm text-[#F04438]">{errors.daily_site_limit}</p>}
                                    <p className="mt-1 text-xs text-[var(--admin-text-dim)]">Leave empty for unlimited</p>
                                </div>
                            </div>
                        </div>
                    </Card>

                    {/* Categories */}
                    <Card variant="elevated">
                        <div className="p-6">
                            <div className="flex items-center gap-3 mb-6">
                                <div className="flex items-center justify-center w-10 h-10 rounded-xl bg-[#7F56D9]/15">
                                    <svg className="h-5 w-5 text-[#7F56D9]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 className="text-lg font-semibold text-[var(--admin-text)]">Categories</h3>
                                    <p className="text-sm text-[var(--admin-text-muted)]">Assign categories to this opportunity</p>
                                </div>
                            </div>

                            <div>
                                <label className="block text-sm font-semibold text-[var(--admin-text)] mb-2">
                                    Select Categories <span className="text-[#F04438]">*</span>
                                </label>
                                <div className="border border-[var(--admin-border)] rounded-lg p-4 max-h-64 overflow-y-auto bg-[var(--admin-hover-bg)]">
                                    {parentCategories.map((parent) => (
                                        <div key={parent.id} className="mb-3 last:mb-0">
                                            <label className="flex items-center space-x-3 cursor-pointer hover:bg-[var(--admin-surface-2)] p-2.5 rounded-lg transition-colors">
                                                <input
                                                    type="checkbox"
                                                    checked={form.category_ids.includes(parent.id)}
                                                    onChange={() => toggleCategory(parent.id)}
                                                    className="rounded border-[var(--admin-border)] text-[#2F6BFF] focus:ring-[#2F6BFF]/50 focus:ring-offset-0"
                                                />
                                                <span className="font-medium text-[var(--admin-text)]">{parent.name}</span>
                                            </label>
                                            {subcategoriesByParent[parent.id]?.map((subcat) => (
                                                <label key={subcat.id} className="flex items-center space-x-3 cursor-pointer hover:bg-[var(--admin-surface-2)] p-2.5 rounded-lg ml-6 transition-colors">
                                                    <input
                                                        type="checkbox"
                                                        checked={form.category_ids.includes(subcat.id)}
                                                        onChange={() => toggleCategory(subcat.id)}
                                                        className="rounded border-[var(--admin-border)] text-[#2F6BFF] focus:ring-[#2F6BFF]/50 focus:ring-offset-0"
                                                    />
                                                    <span className="text-sm text-[var(--admin-text-muted)]">└─ {subcat.name}</span>
                                                </label>
                                            ))}
                                        </div>
                                    ))}
                                </div>
                                {errors?.category_ids && <p className="mt-1 text-sm text-[#F04438]">{errors.category_ids}</p>}
                                <p className="mt-1 text-xs text-[var(--admin-text-dim)]">Select at least one category</p>
                            </div>
                        </div>
                    </Card>

                    {/* Sticky Bottom Actions (mobile) */}
                    <div className="sticky bottom-0 left-0 right-0 p-4 bg-[var(--admin-surface)] border-t border-[var(--admin-border)] flex items-center justify-end gap-3 md:hidden">
                        <Link href="/admin/backlink-opportunities">
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
                            Save Opportunity
                        </button>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}

