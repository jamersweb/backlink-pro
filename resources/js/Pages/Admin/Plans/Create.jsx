import { useState } from 'react';
import AdminLayout from '@/Components/Layout/AdminLayout';
import Card from '@/Components/Shared/Card';
import Button from '@/Components/Shared/Button';
import Input from '@/Components/Shared/Input';
import { Link, router, usePage } from '@inertiajs/react';

export default function AdminPlansCreate() {
    const { flash, errors } = usePage().props;
    const [formData, setFormData] = useState({
        name: '',
        slug: '',
        description: '',
        price: '',
        billing_interval: 'monthly',
        max_domains: 1,
        max_campaigns: 1,
        daily_backlink_limit: 10,
        backlink_types: [],
        features: [],
        is_active: true,
        sort_order: 0,
    });

    const [featureInput, setFeatureInput] = useState('');

    const handleSubmit = (e) => {
        e.preventDefault();
        
        // Filter out empty features
        const features = formData.features.filter(f => f.trim() !== '');
        
        router.post('/admin/plans', {
            ...formData,
            features: features.length > 0 ? features : null,
            backlink_types: formData.backlink_types.length > 0 ? formData.backlink_types : null,
        });
    };

    const handleAddFeature = () => {
        if (featureInput.trim()) {
            setFormData({
                ...formData,
                features: [...formData.features, featureInput.trim()],
            });
            setFeatureInput('');
        }
    };

    const handleRemoveFeature = (index) => {
        setFormData({
            ...formData,
            features: formData.features.filter((_, i) => i !== index),
        });
    };

    const toggleBacklinkType = (type) => {
        setFormData({
            ...formData,
            backlink_types: formData.backlink_types.includes(type)
                ? formData.backlink_types.filter(t => t !== type)
                : [...formData.backlink_types, type],
        });
    };

    const backlinkTypeIcons = {
        comment: 'bi-chat-text',
        profile: 'bi-person-circle',
        forum: 'bi-chat-square-quote',
        guestposting: 'bi-file-earmark-text',
    };

    const backlinkTypeLabels = {
        comment: 'Comment',
        profile: 'Profile',
        forum: 'Forum',
        guestposting: 'Guest Posting',
    };

    return (
        <AdminLayout header="Create New Plan">
            <div className="max-w-5xl mx-auto">
                {/* Page Header */}
                <div className="mb-6">
                    <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h1 className="text-2xl font-bold text-[var(--admin-text)] mb-1">Create New Plan</h1>
                            <p className="text-sm text-[var(--admin-text-muted)]">Define pricing, limits and features for your subscription plan</p>
                        </div>
                        <div className="flex items-center gap-3">
                            <Link href="/admin/plans">
                                <button
                                    type="button"
                                    className="px-4 py-2.5 text-sm font-medium text-[var(--admin-text)] bg-[var(--admin-surface)] border border-[var(--admin-border)] rounded-lg hover:bg-[var(--admin-hover-bg)] transition-all"
                                >
                                    Cancel
                                </button>
                            </Link>
                            <button
                                type="submit"
                                form="plan-form"
                                className="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-[#2F6BFF] to-[#2457D6] hover:from-[#2457D6] hover:to-[#1E4BB8] text-white rounded-lg font-medium transition-all duration-200 shadow-lg shadow-[#2F6BFF]/20"
                            >
                                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                                </svg>
                                Save Plan
                            </button>
                        </div>
                    </div>
                </div>

                {/* Success/Error Messages */}
                {flash?.success && (
                    <div className="mb-6 p-4 bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 rounded-lg">
                        <p className="text-sm text-emerald-800 dark:text-emerald-400">{flash.success}</p>
                    </div>
                )}
                {flash?.error && (
                    <div className="mb-6 p-4 bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 rounded-lg">
                        <p className="text-sm text-red-800 dark:text-red-400">{flash.error}</p>
                    </div>
                )}

                <form id="plan-form" onSubmit={handleSubmit} className="space-y-6">
                    {/* Basic Information */}
                    <Card className="overflow-hidden">
                        <div className="px-6 py-5 border-b border-[var(--admin-border)] bg-[var(--admin-hover-bg)]">
                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 rounded-lg bg-gradient-to-br from-indigo-500/10 to-indigo-600/10 border border-indigo-500/20 flex items-center justify-center flex-shrink-0">
                                    <svg className="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 className="text-base font-semibold text-[var(--admin-text)]">Basic Information</h3>
                                    <p className="text-xs text-[var(--admin-text-muted)]">Plan name, slug and description</p>
                                </div>
                            </div>
                        </div>
                        <div className="p-6">
                            <div className="space-y-5">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                                    <div>
                                        <label className="block text-sm font-semibold text-[var(--admin-text)] mb-2">
                                            Plan Name <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            value={formData.name}
                                            onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                            required
                                            placeholder="e.g., Professional"
                                            className={`w-full px-4 py-2.5 text-sm bg-[var(--admin-surface)] border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 text-[var(--admin-text)] placeholder-[var(--admin-text-muted)] transition-all ${
                                                errors?.name ? 'border-red-500' : 'border-[var(--admin-border)]'
                                            }`}
                                        />
                                        {errors?.name && <p className="mt-1.5 text-xs text-red-600 dark:text-red-400">{errors.name}</p>}
                                        <p className="mt-1.5 text-xs text-[var(--admin-text-muted)]">Display name shown to users</p>
                                    </div>
                                    <div>
                                        <label className="block text-sm font-semibold text-[var(--admin-text)] mb-2">
                                            Slug <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            value={formData.slug}
                                            onChange={(e) => setFormData({ ...formData, slug: e.target.value.toLowerCase().replace(/\s+/g, '-') })}
                                            placeholder="e.g., professional-plan"
                                            required
                                            className={`w-full px-4 py-2.5 text-sm bg-[var(--admin-surface)] border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 text-[var(--admin-text)] placeholder-[var(--admin-text-muted)] transition-all ${
                                                errors?.slug ? 'border-red-500' : 'border-[var(--admin-border)]'
                                            }`}
                                        />
                                        {errors?.slug && <p className="mt-1.5 text-xs text-red-600 dark:text-red-400">{errors.slug}</p>}
                                        <p className="mt-1.5 text-xs text-[var(--admin-text-muted)]">URL-friendly identifier (auto-formatted)</p>
                                    </div>
                                </div>
                                <div>
                                    <label className="block text-sm font-semibold text-[var(--admin-text)] mb-2">
                                        Description
                                    </label>
                                    <textarea
                                        value={formData.description}
                                        onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                                        rows="4"
                                        placeholder="Brief description of what this plan includes..."
                                        className="w-full px-4 py-2.5 text-sm bg-[var(--admin-surface)] border border-[var(--admin-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 text-[var(--admin-text)] placeholder-[var(--admin-text-muted)] transition-all resize-none"
                                    />
                                    <p className="mt-1.5 text-xs text-[var(--admin-text-muted)]">Optional: shown on pricing page</p>
                                </div>
                            </div>
                        </div>
                    </Card>

                    {/* Pricing */}
                    <Card className="overflow-hidden">
                        <div className="px-6 py-5 border-b border-[var(--admin-border)] bg-[var(--admin-hover-bg)]">
                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 rounded-lg bg-gradient-to-br from-emerald-500/10 to-emerald-600/10 border border-emerald-500/20 flex items-center justify-center flex-shrink-0">
                                    <svg className="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 className="text-base font-semibold text-[var(--admin-text)]">Pricing</h3>
                                    <p className="text-xs text-[var(--admin-text-muted)]">Set plan price and billing cycle</p>
                                </div>
                            </div>
                        </div>
                        <div className="p-6">
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-5">
                                <div>
                                    <label className="block text-sm font-semibold text-[var(--admin-text)] mb-2">
                                        Price <span className="text-red-500">*</span>
                                    </label>
                                    <div className="relative">
                                        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <span className="text-[var(--admin-text-muted)]">$</span>
                                        </div>
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={formData.price}
                                            onChange={(e) => setFormData({ ...formData, price: e.target.value })}
                                            required
                                            placeholder="29.99"
                                            className={`w-full pl-8 pr-4 py-2.5 text-sm bg-[var(--admin-surface)] border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 text-[var(--admin-text)] placeholder-[var(--admin-text-muted)] transition-all ${
                                                errors?.price ? 'border-red-500' : 'border-[var(--admin-border)]'
                                            }`}
                                        />
                                    </div>
                                    {errors?.price && <p className="mt-1.5 text-xs text-red-600 dark:text-red-400">{errors.price}</p>}
                                    <p className="mt-1.5 text-xs text-[var(--admin-text-muted)]">Monthly or annual price</p>
                                </div>
                                <div>
                                    <label className="block text-sm font-semibold text-[var(--admin-text)] mb-2">
                                        Billing Interval <span className="text-red-500">*</span>
                                    </label>
                                    <select
                                        value={formData.billing_interval}
                                        onChange={(e) => setFormData({ ...formData, billing_interval: e.target.value })}
                                        className="w-full px-4 py-2.5 text-sm bg-[var(--admin-surface)] border border-[var(--admin-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 text-[var(--admin-text)] transition-all"
                                        required
                                    >
                                        <option value="monthly">Monthly</option>
                                        <option value="yearly">Yearly</option>
                                    </select>
                                    <p className="mt-1.5 text-xs text-[var(--admin-text-muted)]">How often to bill</p>
                                </div>
                                <div>
                                    <label className="block text-sm font-semibold text-[var(--admin-text)] mb-2">
                                        Sort Order
                                    </label>
                                    <input
                                        type="number"
                                        value={formData.sort_order}
                                        onChange={(e) => setFormData({ ...formData, sort_order: parseInt(e.target.value) || 0 })}
                                        placeholder="0"
                                        className="w-full px-4 py-2.5 text-sm bg-[var(--admin-surface)] border border-[var(--admin-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 text-[var(--admin-text)] placeholder-[var(--admin-text-muted)] transition-all"
                                    />
                                    <p className="mt-1.5 text-xs text-[var(--admin-text-muted)]">Display order on pricing page</p>
                                </div>
                            </div>
                        </div>
                    </Card>

                    {/* Limits */}
                    <Card className="overflow-hidden">
                        <div className="px-6 py-5 border-b border-[var(--admin-border)] bg-[var(--admin-hover-bg)]">
                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-500/10 to-blue-600/10 border border-blue-500/20 flex items-center justify-center flex-shrink-0">
                                    <svg className="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 className="text-base font-semibold text-[var(--admin-text)]">Limits</h3>
                                    <p className="text-xs text-[var(--admin-text-muted)]">Set usage limits for this plan</p>
                                </div>
                            </div>
                        </div>
                        <div className="p-6">
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-5">
                                <div>
                                    <label className="block text-sm font-semibold text-[var(--admin-text)] mb-2">
                                        Max Domains <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="number"
                                        min="-1"
                                        value={formData.max_domains}
                                        onChange={(e) => setFormData({ ...formData, max_domains: parseInt(e.target.value) || -1 })}
                                        placeholder="-1"
                                        required
                                        className="w-full px-4 py-2.5 text-sm bg-[var(--admin-surface)] border border-[var(--admin-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 text-[var(--admin-text)] placeholder-[var(--admin-text-muted)] transition-all"
                                    />
                                    <p className="mt-1.5 text-xs text-[var(--admin-text-muted)]">Use -1 for unlimited</p>
                                </div>
                                <div>
                                    <label className="block text-sm font-semibold text-[var(--admin-text)] mb-2">
                                        Max Campaigns <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="number"
                                        min="-1"
                                        value={formData.max_campaigns}
                                        onChange={(e) => setFormData({ ...formData, max_campaigns: parseInt(e.target.value) || -1 })}
                                        placeholder="-1"
                                        required
                                        className="w-full px-4 py-2.5 text-sm bg-[var(--admin-surface)] border border-[var(--admin-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 text-[var(--admin-text)] placeholder-[var(--admin-text-muted)] transition-all"
                                    />
                                    <p className="mt-1.5 text-xs text-[var(--admin-text-muted)]">Use -1 for unlimited</p>
                                </div>
                                <div>
                                    <label className="block text-sm font-semibold text-[var(--admin-text)] mb-2">
                                        Daily Backlink Limit <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="number"
                                        min="-1"
                                        value={formData.daily_backlink_limit}
                                        onChange={(e) => setFormData({ ...formData, daily_backlink_limit: parseInt(e.target.value) || -1 })}
                                        placeholder="-1"
                                        required
                                        className="w-full px-4 py-2.5 text-sm bg-[var(--admin-surface)] border border-[var(--admin-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 text-[var(--admin-text)] placeholder-[var(--admin-text-muted)] transition-all"
                                    />
                                    <p className="mt-1.5 text-xs text-[var(--admin-text-muted)]">Use -1 for unlimited</p>
                                </div>
                            </div>
                        </div>
                    </Card>

                    {/* Allowed Backlink Types */}
                    <Card className="overflow-hidden">
                        <div className="px-6 py-5 border-b border-[var(--admin-border)] bg-[var(--admin-hover-bg)]">
                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 rounded-lg bg-gradient-to-br from-purple-500/10 to-purple-600/10 border border-purple-500/20 flex items-center justify-center flex-shrink-0">
                                    <svg className="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 className="text-base font-semibold text-[var(--admin-text)]">Allowed Backlink Types</h3>
                                    <p className="text-xs text-[var(--admin-text-muted)]">Select which backlink types are available</p>
                                </div>
                            </div>
                        </div>
                        <div className="p-6">
                            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                                {['comment', 'profile', 'forum', 'guestposting'].map((type) => (
                                    <button
                                        key={type}
                                        type="button"
                                        onClick={() => toggleBacklinkType(type)}
                                        className={`relative flex items-center gap-3 px-4 py-3 rounded-lg border-2 transition-all duration-150 ${
                                            formData.backlink_types.includes(type)
                                                ? 'bg-indigo-500/10 border-indigo-500/50 shadow-sm'
                                                : 'bg-[var(--admin-surface)] border-[var(--admin-border)] hover:border-indigo-500/30 hover:bg-[var(--admin-hover-bg)]'
                                        }`}
                                    >
                                        <div className={`flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center transition-colors ${
                                            formData.backlink_types.includes(type)
                                                ? 'bg-indigo-500 text-white'
                                                : 'bg-[var(--admin-hover-bg)] text-[var(--admin-text-muted)]'
                                        }`}>
                                            <i className={`bi ${backlinkTypeIcons[type]} text-sm`}></i>
                                        </div>
                                        <div className="flex-1 text-left">
                                            <div className={`text-sm font-medium transition-colors ${
                                                formData.backlink_types.includes(type)
                                                    ? 'text-indigo-700 dark:text-indigo-400'
                                                    : 'text-[var(--admin-text)]'
                                            }`}>
                                                {backlinkTypeLabels[type]}
                                            </div>
                                        </div>
                                        {formData.backlink_types.includes(type) && (
                                            <div className="absolute top-2 right-2">
                                                <svg className="w-4 h-4 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                                                </svg>
                                            </div>
                                        )}
                                    </button>
                                ))}
                            </div>
                            <p className="mt-3 text-xs text-[var(--admin-text-muted)]">Leave empty to allow all types</p>
                        </div>
                    </Card>

                    {/* Features */}
                    <Card className="overflow-hidden">
                        <div className="px-6 py-5 border-b border-[var(--admin-border)] bg-[var(--admin-hover-bg)]">
                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 rounded-lg bg-gradient-to-br from-amber-500/10 to-amber-600/10 border border-amber-500/20 flex items-center justify-center flex-shrink-0">
                                    <svg className="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <div className="flex-1">
                                    <h3 className="text-base font-semibold text-[var(--admin-text)]">Features</h3>
                                    <p className="text-xs text-[var(--admin-text-muted)]">List key features included in this plan</p>
                                </div>
                            </div>
                        </div>
                        <div className="p-6">
                            {/* Add Feature Input */}
                            <div className="flex items-center gap-3 mb-4">
                                <div className="relative flex-1">
                                    <input
                                        type="text"
                                        value={featureInput}
                                        onChange={(e) => setFeatureInput(e.target.value)}
                                        onKeyPress={(e) => e.key === 'Enter' && (e.preventDefault(), handleAddFeature())}
                                        placeholder="Add a feature (e.g., 'Unlimited domains')"
                                        className="w-full px-4 py-2.5 text-sm bg-[var(--admin-surface)] border border-[var(--admin-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 text-[var(--admin-text)] placeholder-[var(--admin-text-muted)] transition-all"
                                    />
                                </div>
                                <button
                                    type="button"
                                    onClick={handleAddFeature}
                                    disabled={!featureInput.trim()}
                                    className="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-500 hover:bg-indigo-600 disabled:bg-[var(--admin-border)] disabled:cursor-not-allowed text-white rounded-lg font-medium transition-all text-sm"
                                >
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
                                    </svg>
                                    Add
                                </button>
                            </div>

                            {/* Features List as Pills */}
                            {formData.features.length > 0 && (
                                <div className="space-y-3">
                                    <p className="text-xs font-medium text-[var(--admin-text-muted)] uppercase tracking-wide">Added Features ({formData.features.length})</p>
                                    <div className="flex flex-wrap gap-2">
                                        {formData.features.map((feature, index) => (
                                            <div
                                                key={index}
                                                className="inline-flex items-center gap-2 pl-4 pr-2 py-2 bg-[var(--admin-hover-bg)] border border-[var(--admin-border)] rounded-lg text-sm text-[var(--admin-text)] group hover:border-indigo-500/50 transition-all"
                                            >
                                                <svg className="w-3.5 h-3.5 text-emerald-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                                                </svg>
                                                <span className="flex-1">{feature}</span>
                                                <button
                                                    type="button"
                                                    onClick={() => handleRemoveFeature(index)}
                                                    className="p-1 rounded hover:bg-red-500/10 text-[var(--admin-text-muted)] hover:text-red-600 transition-colors"
                                                    title="Remove feature"
                                                >
                                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}

                            {formData.features.length === 0 && (
                                <div className="text-center py-6 px-4 rounded-lg bg-[var(--admin-hover-bg)] border border-dashed border-[var(--admin-border)]">
                                    <svg className="w-8 h-8 text-[var(--admin-text-muted)] mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                    <p className="text-sm text-[var(--admin-text-muted)]">No features added yet</p>
                                    <p className="text-xs text-[var(--admin-text-muted)] mt-1">Add features above to list plan benefits</p>
                                </div>
                            )}
                        </div>
                    </Card>

                    {/* Status & Settings */}
                    <Card className="overflow-hidden">
                        <div className="px-6 py-5 border-b border-[var(--admin-border)] bg-[var(--admin-hover-bg)]">
                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 rounded-lg bg-gradient-to-br from-rose-500/10 to-rose-600/10 border border-rose-500/20 flex items-center justify-center flex-shrink-0">
                                    <svg className="w-5 h-5 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 className="text-base font-semibold text-[var(--admin-text)]">Settings</h3>
                                    <p className="text-xs text-[var(--admin-text-muted)]">Plan availability and status</p>
                                </div>
                            </div>
                        </div>
                        <div className="p-6">
                            <label className="relative inline-flex items-center cursor-pointer group">
                                <input
                                    type="checkbox"
                                    id="is_active"
                                    checked={formData.is_active}
                                    onChange={(e) => setFormData({ ...formData, is_active: e.target.checked })}
                                    className="sr-only peer"
                                />
                                <div className="w-11 h-6 bg-gray-200 dark:bg-gray-700 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                <span className="ml-3 text-sm font-medium text-[var(--admin-text)]">
                                    Plan is active (visible to users)
                                </span>
                            </label>
                            <p className="mt-2 text-xs text-[var(--admin-text-muted)] ml-14">Inactive plans are hidden from the pricing page</p>
                        </div>
                    </Card>

                    {/* Bottom Action Bar (Sticky) */}
                    <div className="sticky bottom-0 left-0 right-0 bg-[var(--admin-surface)]/95 backdrop-blur-sm border-t border-[var(--admin-border)] px-6 py-4 -mx-6 shadow-lg">
                        <div className="flex items-center justify-between gap-4">
                            <div className="text-sm text-[var(--admin-text-muted)]">
                                <span className="font-medium text-[var(--admin-text)]">Ready to create?</span> Review your plan details above
                            </div>
                            <div className="flex items-center gap-3">
                                <Link href="/admin/plans">
                                    <button
                                        type="button"
                                        className="px-5 py-2.5 text-sm font-medium text-[var(--admin-text)] bg-[var(--admin-surface)] border border-[var(--admin-border)] rounded-lg hover:bg-[var(--admin-hover-bg)] transition-all"
                                    >
                                        Cancel
                                    </button>
                                </Link>
                                <button
                                    type="submit"
                                    className="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-[#2F6BFF] to-[#2457D6] hover:from-[#2457D6] hover:to-[#1E4BB8] text-white rounded-lg font-medium transition-all duration-200 shadow-lg shadow-[#2F6BFF]/20 text-sm"
                                >
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                                    </svg>
                                    Create Plan
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}
