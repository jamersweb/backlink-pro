import { useState } from 'react';
import AdminLayout from '@/Components/Layout/AdminLayout';
import Card from '@/Components/Shared/Card';
import { Link, router, usePage } from '@inertiajs/react';

export default function AdminCampaignsCreate({ users, domains, categories }) {
    const { flash, errors } = usePage().props;
    const [formData, setFormData] = useState({
        name: '',
        user_id: '',
        domain_id: '',
        web_name: '',
        web_url: '',
        web_keyword: '',
        web_about: '',
        status: 'inactive',
        daily_limit: '',
        total_limit: '',
        start_date: '',
        end_date: '',
        category_id: '',
        subcategory_id: '',
        settings: {},
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        router.post('/admin/campaigns', {
            ...formData,
            settings: Object.keys(formData.settings).length > 0 ? formData.settings : null,
        });
    };

    const handleChange = (field, value) => {
        setFormData(prev => ({ ...prev, [field]: value }));
    };

    // Get subcategories for selected category
    const subcategories = formData.category_id 
        ? categories?.find(c => c.id === parseInt(formData.category_id))?.children || []
        : [];

    return (
        <AdminLayout header="Create Campaign">
            <div className="max-w-5xl mx-auto">
                {/* Page Header */}
                <div className="mb-6">
                    <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h1 className="text-2xl font-bold text-[var(--admin-text)] mb-1">Create New Campaign</h1>
                            <p className="text-sm text-[var(--admin-text-muted)]">Set up a new backlink building campaign for a user</p>
                        </div>
                        <div className="flex items-center gap-3">
                            <Link href="/admin/campaigns">
                                <button
                                    type="button"
                                    className="px-4 py-2.5 text-sm font-medium text-[var(--admin-text)] bg-[var(--admin-surface)] border border-[var(--admin-border)] rounded-lg hover:bg-[var(--admin-hover-bg)] transition-all"
                                >
                                    Cancel
                                </button>
                            </Link>
                            <button
                                type="submit"
                                form="campaign-form"
                                className="px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-[#2F6BFF] to-[#2457D6] hover:from-[#2457D6] hover:to-[#1E4BBD] rounded-lg shadow-lg shadow-[#2F6BFF]/20 transition-all"
                            >
                                Save Campaign
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
                <form id="campaign-form" onSubmit={handleSubmit} className="space-y-6">
                    {/* Basic Information */}
                    <Card variant="elevated">
                        <div className="p-6">
                            <div className="flex items-center gap-3 mb-6">
                                <div className="flex items-center justify-center w-10 h-10 rounded-xl bg-[#2F6BFF]/15">
                                    <svg className="h-5 w-5 text-[#5B8AFF]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 className="text-lg font-semibold text-[var(--admin-text)]">Basic Information</h3>
                                    <p className="text-sm text-[var(--admin-text-muted)]">Campaign identification and ownership details</p>
                                </div>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label className="block text-sm font-semibold text-[var(--admin-text)] mb-2">
                                        Campaign Name <span className="text-[#F04438]">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        value={formData.name}
                                        onChange={(e) => handleChange('name', e.target.value)}
                                        className="w-full px-4 py-2.5 bg-[var(--admin-hover-bg)] border border-[var(--admin-border)] rounded-lg text-[var(--admin-text)] placeholder-[var(--admin-text-muted)] focus:outline-none focus:ring-2 focus:ring-[#2F6BFF]/50 focus:border-[#2F6BFF] transition-all"
                                        placeholder="e.g., SEO Campaign 2026"
                                        required
                                    />
                                    {errors?.name && <p className="mt-1 text-sm text-[#F04438]">{errors.name}</p>}
                                    <p className="mt-1 text-xs text-[var(--admin-text-dim)]">Internal name for this campaign</p>
                                </div>

                                <div>
                                    <label className="block text-sm font-semibold text-[var(--admin-text)] mb-2">
                                        User <span className="text-[#F04438]">*</span>
                                    </label>
                                    <select
                                        value={formData.user_id}
                                        onChange={(e) => handleChange('user_id', e.target.value)}
                                        className="w-full px-4 py-2.5 bg-[var(--admin-hover-bg)] border border-[var(--admin-border)] rounded-lg text-[var(--admin-text)] focus:outline-none focus:ring-2 focus:ring-[#2F6BFF]/50 focus:border-[#2F6BFF] transition-all"
                                        required
                                    >
                                        <option value="">Select User</option>
                                        {users?.map(user => (
                                            <option key={user.id} value={user.id}>
                                                {user.name} ({user.email})
                                            </option>
                                        ))}
                                    </select>
                                    {errors?.user_id && <p className="mt-1 text-sm text-[#F04438]">{errors.user_id}</p>}
                                    <p className="mt-1 text-xs text-[var(--admin-text-dim)]">Campaign owner</p>
                                </div>

                                <div>
                                    <label className="block text-sm font-semibold text-[var(--admin-text)] mb-2">
                                        Domain (Optional)
                                    </label>
                                    <select
                                        value={formData.domain_id}
                                        onChange={(e) => handleChange('domain_id', e.target.value)}
                                        className="w-full px-4 py-2.5 bg-[var(--admin-hover-bg)] border border-[var(--admin-border)] rounded-lg text-[var(--admin-text)] focus:outline-none focus:ring-2 focus:ring-[#2F6BFF]/50 focus:border-[#2F6BFF] transition-all"
                                    >
                                        <option value="">No Domain</option>
                                        {domains?.map(domain => (
                                            <option key={domain.id} value={domain.id}>
                                                {domain.name}
                                            </option>
                                        ))}
                                    </select>
                                    {errors?.domain_id && <p className="mt-1 text-sm text-[#F04438]">{errors.domain_id}</p>}
                                    <p className="mt-1 text-xs text-[var(--admin-text-dim)]">Associated domain</p>
                                </div>

                                <div>
                                    <label className="block text-sm font-semibold text-[var(--admin-text)] mb-2">
                                        Status <span className="text-[#F04438]">*</span>
                                    </label>
                                    <select
                                        value={formData.status}
                                        onChange={(e) => handleChange('status', e.target.value)}
                                        className="w-full px-4 py-2.5 bg-[var(--admin-hover-bg)] border border-[var(--admin-border)] rounded-lg text-[var(--admin-text)] focus:outline-none focus:ring-2 focus:ring-[#2F6BFF]/50 focus:border-[#2F6BFF] transition-all"
                                        required
                                    >
                                        <option value="inactive">Inactive</option>
                                        <option value="active">Active</option>
                                        <option value="paused">Paused</option>
                                        <option value="completed">Completed</option>
                                    </select>
                                    {errors?.status && <p className="mt-1 text-sm text-[#F04438]">{errors.status}</p>}
                                    <p className="mt-1 text-xs text-[var(--admin-text-dim)]">Campaign execution status</p>
                                </div>
                            </div>
                        </div>
                    </Card>

                    {/* Website Information */}
                    <Card variant="elevated">
                        <div className="p-6">
                            <div className="flex items-center gap-3 mb-6">
                                <div className="flex items-center justify-center w-10 h-10 rounded-xl bg-[#12B76A]/15">
                                    <svg className="h-5 w-5 text-[#12B76A]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 className="text-lg font-semibold text-[var(--admin-text)]">Website Information</h3>
                                    <p className="text-sm text-[var(--admin-text-muted)]">Target website details for backlink campaigns</p>
                                </div>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label className="block text-sm font-semibold text-[var(--admin-text)] mb-2">
                                        Website Name
                                    </label>
                                    <input
                                        type="text"
                                        value={formData.web_name}
                                        onChange={(e) => handleChange('web_name', e.target.value)}
                                        className="w-full px-4 py-2.5 bg-[var(--admin-hover-bg)] border border-[var(--admin-border)] rounded-lg text-[var(--admin-text)] placeholder-[var(--admin-text-muted)] focus:outline-none focus:ring-2 focus:ring-[#2F6BFF]/50 focus:border-[#2F6BFF] transition-all"
                                        placeholder="e.g., My Website"
                                    />
                                    {errors?.web_name && <p className="mt-1 text-sm text-[#F04438]">{errors.web_name}</p>}
                                </div>

                                <div>
                                    <label className="block text-sm font-semibold text-[var(--admin-text)] mb-2">
                                        Website URL
                                    </label>
                                    <input
                                        type="url"
                                        value={formData.web_url}
                                        onChange={(e) => handleChange('web_url', e.target.value)}
                                        className="w-full px-4 py-2.5 bg-[var(--admin-hover-bg)] border border-[var(--admin-border)] rounded-lg text-[var(--admin-text)] placeholder-[var(--admin-text-muted)] focus:outline-none focus:ring-2 focus:ring-[#2F6BFF]/50 focus:border-[#2F6BFF] transition-all"
                                        placeholder="https://example.com"
                                    />
                                    {errors?.web_url && <p className="mt-1 text-sm text-[#F04438]">{errors.web_url}</p>}
                                </div>

                                <div className="md:col-span-2">
                                    <label className="block text-sm font-semibold text-[var(--admin-text)] mb-2">
                                        Keywords
                                    </label>
                                    <input
                                        type="text"
                                        value={formData.web_keyword}
                                        onChange={(e) => handleChange('web_keyword', e.target.value)}
                                        className="w-full px-4 py-2.5 bg-[var(--admin-hover-bg)] border border-[var(--admin-border)] rounded-lg text-[var(--admin-text)] placeholder-[var(--admin-text-muted)] focus:outline-none focus:ring-2 focus:ring-[#2F6BFF]/50 focus:border-[#2F6BFF] transition-all"
                                        placeholder="SEO, backlinks, marketing (comma-separated)"
                                    />
                                    {errors?.web_keyword && <p className="mt-1 text-sm text-[#F04438]">{errors.web_keyword}</p>}
                                    <p className="mt-1 text-xs text-[var(--admin-text-dim)]">Comma-separated keywords for anchor text</p>
                                </div>

                                <div className="md:col-span-2">
                                    <label className="block text-sm font-semibold text-[var(--admin-text)] mb-2">
                                        About Website
                                    </label>
                                    <textarea
                                        value={formData.web_about}
                                        onChange={(e) => handleChange('web_about', e.target.value)}
                                        rows={4}
                                        className="w-full px-4 py-2.5 bg-[var(--admin-hover-bg)] border border-[var(--admin-border)] rounded-lg text-[var(--admin-text)] placeholder-[var(--admin-text-muted)] focus:outline-none focus:ring-2 focus:ring-[#2F6BFF]/50 focus:border-[#2F6BFF] transition-all resize-none"
                                        placeholder="Brief description of the website for content generation..."
                                    />
                                    {errors?.web_about && <p className="mt-1 text-sm text-[#F04438]">{errors.web_about}</p>}
                                </div>
                            </div>
                        </div>
                    </Card>

                    {/* Limits & Schedule */}
                    <Card variant="elevated">
                        <div className="p-6">
                            <div className="flex items-center gap-3 mb-6">
                                <div className="flex items-center justify-center w-10 h-10 rounded-xl bg-[#F79009]/15">
                                    <svg className="h-5 w-5 text-[#F79009]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 className="text-lg font-semibold text-[var(--admin-text)]">Limits & Schedule</h3>
                                    <p className="text-sm text-[var(--admin-text-muted)]">Campaign execution limits and timing</p>
                                </div>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label className="block text-sm font-semibold text-[var(--admin-text)] mb-2">
                                        Daily Limit
                                    </label>
                                    <input
                                        type="number"
                                        value={formData.daily_limit}
                                        onChange={(e) => handleChange('daily_limit', e.target.value)}
                                        min="1"
                                        max="1000"
                                        className="w-full px-4 py-2.5 bg-[var(--admin-hover-bg)] border border-[var(--admin-border)] rounded-lg text-[var(--admin-text)] placeholder-[var(--admin-text-muted)] focus:outline-none focus:ring-2 focus:ring-[#2F6BFF]/50 focus:border-[#2F6BFF] transition-all"
                                        placeholder="10"
                                    />
                                    {errors?.daily_limit && <p className="mt-1 text-sm text-[#F04438]">{errors.daily_limit}</p>}
                                    <p className="mt-1 text-xs text-[var(--admin-text-dim)]">Max backlinks per day</p>
                                </div>

                                <div>
                                    <label className="block text-sm font-semibold text-[var(--admin-text)] mb-2">
                                        Total Limit
                                    </label>
                                    <input
                                        type="number"
                                        value={formData.total_limit}
                                        onChange={(e) => handleChange('total_limit', e.target.value)}
                                        min="1"
                                        className="w-full px-4 py-2.5 bg-[var(--admin-hover-bg)] border border-[var(--admin-border)] rounded-lg text-[var(--admin-text)] placeholder-[var(--admin-text-muted)] focus:outline-none focus:ring-2 focus:ring-[#2F6BFF]/50 focus:border-[#2F6BFF] transition-all"
                                        placeholder="300"
                                    />
                                    {errors?.total_limit && <p className="mt-1 text-sm text-[#F04438]">{errors.total_limit}</p>}
                                    <p className="mt-1 text-xs text-[var(--admin-text-dim)]">Total campaign limit</p>
                                </div>

                                <div>
                                    <label className="block text-sm font-semibold text-[var(--admin-text)] mb-2">
                                        Start Date
                                    </label>
                                    <input
                                        type="datetime-local"
                                        value={formData.start_date}
                                        onChange={(e) => handleChange('start_date', e.target.value)}
                                        className="w-full px-4 py-2.5 bg-[var(--admin-hover-bg)] border border-[var(--admin-border)] rounded-lg text-[var(--admin-text)] focus:outline-none focus:ring-2 focus:ring-[#2F6BFF]/50 focus:border-[#2F6BFF] transition-all"
                                    />
                                    {errors?.start_date && <p className="mt-1 text-sm text-[#F04438]">{errors.start_date}</p>}
                                </div>

                                <div>
                                    <label className="block text-sm font-semibold text-[var(--admin-text)] mb-2">
                                        End Date
                                    </label>
                                    <input
                                        type="datetime-local"
                                        value={formData.end_date}
                                        onChange={(e) => handleChange('end_date', e.target.value)}
                                        className="w-full px-4 py-2.5 bg-[var(--admin-hover-bg)] border border-[var(--admin-border)] rounded-lg text-[var(--admin-text)] focus:outline-none focus:ring-2 focus:ring-[#2F6BFF]/50 focus:border-[#2F6BFF] transition-all"
                                    />
                                    {errors?.end_date && <p className="mt-1 text-sm text-[#F04438]">{errors.end_date}</p>}
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
                                    <p className="text-sm text-[var(--admin-text-muted)]">Target opportunity categories for this campaign</p>
                                </div>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label className="block text-sm font-semibold text-[var(--admin-text)] mb-2">
                                        Parent Category
                                    </label>
                                    <select
                                        value={formData.category_id}
                                        onChange={(e) => {
                                            handleChange('category_id', e.target.value);
                                            handleChange('subcategory_id', ''); // Reset subcategory
                                        }}
                                        className="w-full px-4 py-2.5 bg-[var(--admin-hover-bg)] border border-[var(--admin-border)] rounded-lg text-[var(--admin-text)] focus:outline-none focus:ring-2 focus:ring-[#2F6BFF]/50 focus:border-[#2F6BFF] transition-all"
                                    >
                                        <option value="">No Category</option>
                                        {categories?.map(category => (
                                            <option key={category.id} value={category.id}>
                                                {category.name}
                                            </option>
                                        ))}
                                    </select>
                                    {errors?.category_id && <p className="mt-1 text-sm text-[#F04438]">{errors.category_id}</p>}
                                </div>

                                <div>
                                    <label className="block text-sm font-semibold text-[var(--admin-text)] mb-2">
                                        Subcategory
                                    </label>
                                    <select
                                        value={formData.subcategory_id}
                                        onChange={(e) => handleChange('subcategory_id', e.target.value)}
                                        disabled={!formData.category_id || subcategories.length === 0}
                                        className="w-full px-4 py-2.5 bg-[var(--admin-hover-bg)] border border-[var(--admin-border)] rounded-lg text-[var(--admin-text)] focus:outline-none focus:ring-2 focus:ring-[#2F6BFF]/50 focus:border-[#2F6BFF] transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        <option value="">No Subcategory</option>
                                        {subcategories.map(subcat => (
                                            <option key={subcat.id} value={subcat.id}>
                                                {subcat.name}
                                            </option>
                                        ))}
                                    </select>
                                    {errors?.subcategory_id && <p className="mt-1 text-sm text-[#F04438]">{errors.subcategory_id}</p>}
                                </div>
                            </div>
                        </div>
                    </Card>

                    {/* Sticky Bottom Actions (mobile) */}
                    <div className="sticky bottom-0 left-0 right-0 p-4 bg-[var(--admin-surface)] border-t border-[var(--admin-border)] flex items-center justify-end gap-3 md:hidden">
                        <Link href="/admin/campaigns">
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
                            Save Campaign
                        </button>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}
