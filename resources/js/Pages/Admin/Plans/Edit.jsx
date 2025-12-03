import { useState, useEffect } from 'react';
import AdminLayout from '../../../Components/Layout/AdminLayout';
import Card from '../../../Components/Shared/Card';
import Button from '../../../Components/Shared/Button';
import Input from '../../../Components/Shared/Input';
import { Link, router, usePage } from '@inertiajs/react';

export default function AdminPlansEdit({ plan }) {
    const { flash, errors } = usePage().props;
    const [formData, setFormData] = useState({
        name: plan.name || '',
        slug: plan.slug || '',
        description: plan.description || '',
        price: plan.price || '',
        billing_interval: plan.billing_interval || 'monthly',
        max_domains: plan.max_domains ?? 1,
        max_campaigns: plan.max_campaigns ?? 1,
        daily_backlink_limit: plan.daily_backlink_limit ?? 10,
        backlink_types: plan.backlink_types || [],
        features: plan.features && plan.features.length > 0 ? plan.features : [''],
        is_active: plan.is_active ?? true,
        sort_order: plan.sort_order ?? 0,
    });

    const [featureInput, setFeatureInput] = useState('');

    const handleSubmit = (e) => {
        e.preventDefault();
        
        // Filter out empty features
        const features = formData.features.filter(f => f.trim() !== '');
        
        router.put(`/admin/plans/${plan.id}`, {
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

    return (
        <AdminLayout header={`Edit Plan: ${plan.name}`}>
            <div className="space-y-6">
                {/* Success/Error Messages */}
                {flash?.success && (
                    <div className="p-4 bg-green-50 border border-green-200 rounded-md">
                        <p className="text-sm text-green-800">{flash.success}</p>
                    </div>
                )}
                {flash?.error && (
                    <div className="p-4 bg-red-50 border border-red-200 rounded-md">
                        <p className="text-sm text-red-800">{flash.error}</p>
                    </div>
                )}

                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Basic Information */}
                    <Card className="bg-white border border-gray-200 shadow-md">
                        <h3 className="text-lg font-bold text-gray-900 mb-4">Basic Information</h3>
                        <div className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Plan Name *</label>
                                    <Input
                                        type="text"
                                        value={formData.name}
                                        onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                        required
                                        className={errors?.name ? 'border-red-500' : ''}
                                    />
                                    {errors?.name && <p className="mt-1 text-sm text-red-600">{errors.name}</p>}
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Slug *</label>
                                    <Input
                                        type="text"
                                        value={formData.slug}
                                        onChange={(e) => setFormData({ ...formData, slug: e.target.value.toLowerCase().replace(/\s+/g, '-') })}
                                        required
                                        className={errors?.slug ? 'border-red-500' : ''}
                                    />
                                    {errors?.slug && <p className="mt-1 text-sm text-red-600">{errors.slug}</p>}
                                </div>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                <textarea
                                    value={formData.description}
                                    onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                                    rows="3"
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500"
                                />
                            </div>
                        </div>
                    </Card>

                    {/* Pricing */}
                    <Card className="bg-white border border-gray-200 shadow-md">
                        <h3 className="text-lg font-bold text-gray-900 mb-4">Pricing</h3>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Price *</label>
                                <Input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    value={formData.price}
                                    onChange={(e) => setFormData({ ...formData, price: e.target.value })}
                                    required
                                    className={errors?.price ? 'border-red-500' : ''}
                                />
                                {errors?.price && <p className="mt-1 text-sm text-red-600">{errors.price}</p>}
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Billing Interval *</label>
                                <select
                                    value={formData.billing_interval}
                                    onChange={(e) => setFormData({ ...formData, billing_interval: e.target.value })}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500"
                                    required
                                >
                                    <option value="monthly">Monthly</option>
                                    <option value="yearly">Yearly</option>
                                </select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Sort Order</label>
                                <Input
                                    type="number"
                                    value={formData.sort_order}
                                    onChange={(e) => setFormData({ ...formData, sort_order: parseInt(e.target.value) || 0 })}
                                />
                            </div>
                        </div>
                    </Card>

                    {/* Limits */}
                    <Card className="bg-white border border-gray-200 shadow-md">
                        <h3 className="text-lg font-bold text-gray-900 mb-4">Limits</h3>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Max Domains *</label>
                                <Input
                                    type="number"
                                    min="-1"
                                    value={formData.max_domains}
                                    onChange={(e) => setFormData({ ...formData, max_domains: parseInt(e.target.value) || -1 })}
                                    required
                                />
                                <p className="mt-1 text-xs text-gray-500">Use -1 for unlimited</p>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Max Campaigns *</label>
                                <Input
                                    type="number"
                                    min="-1"
                                    value={formData.max_campaigns}
                                    onChange={(e) => setFormData({ ...formData, max_campaigns: parseInt(e.target.value) || -1 })}
                                    required
                                />
                                <p className="mt-1 text-xs text-gray-500">Use -1 for unlimited</p>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Daily Backlink Limit *</label>
                                <Input
                                    type="number"
                                    min="-1"
                                    value={formData.daily_backlink_limit}
                                    onChange={(e) => setFormData({ ...formData, daily_backlink_limit: parseInt(e.target.value) || -1 })}
                                    required
                                />
                                <p className="mt-1 text-xs text-gray-500">Use -1 for unlimited</p>
                            </div>
                        </div>
                    </Card>

                    {/* Backlink Types */}
                    <Card className="bg-white border border-gray-200 shadow-md">
                        <h3 className="text-lg font-bold text-gray-900 mb-4">Allowed Backlink Types</h3>
                        <div className="flex flex-wrap gap-3">
                            {['comment', 'profile', 'forum', 'guestposting'].map((type) => (
                                <label key={type} className="flex items-center cursor-pointer">
                                    <input
                                        type="checkbox"
                                        checked={formData.backlink_types.includes(type)}
                                        onChange={() => toggleBacklinkType(type)}
                                        className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                    />
                                    <span className="ml-2 text-sm text-gray-700 capitalize">{type}</span>
                                </label>
                            ))}
                        </div>
                    </Card>

                    {/* Features */}
                    <Card className="bg-white border border-gray-200 shadow-md">
                        <h3 className="text-lg font-bold text-gray-900 mb-4">Features</h3>
                        <div className="space-y-3">
                            {formData.features.map((feature, index) => (
                                <div key={index} className="flex items-center gap-2">
                                    <Input
                                        type="text"
                                        value={feature}
                                        onChange={(e) => {
                                            const newFeatures = [...formData.features];
                                            newFeatures[index] = e.target.value;
                                            setFormData({ ...formData, features: newFeatures });
                                        }}
                                        placeholder="Enter feature"
                                    />
                                    <button
                                        type="button"
                                        onClick={() => handleRemoveFeature(index)}
                                        className="text-red-600 hover:text-red-900"
                                    >
                                        🗑️
                                    </button>
                                </div>
                            ))}
                            <div className="flex items-center gap-2">
                                <Input
                                    type="text"
                                    value={featureInput}
                                    onChange={(e) => setFeatureInput(e.target.value)}
                                    onKeyPress={(e) => e.key === 'Enter' && (e.preventDefault(), handleAddFeature())}
                                    placeholder="Add new feature"
                                />
                                <Button type="button" variant="secondary" onClick={handleAddFeature}>
                                    ➕ Add
                                </Button>
                            </div>
                        </div>
                    </Card>

                    {/* Status */}
                    <Card className="bg-white border border-gray-200 shadow-md">
                        <div className="flex items-center">
                            <input
                                type="checkbox"
                                id="is_active"
                                checked={formData.is_active}
                                onChange={(e) => setFormData({ ...formData, is_active: e.target.checked })}
                                className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            />
                            <label htmlFor="is_active" className="ml-2 text-sm text-gray-700">
                                Plan is active (visible to users)
                            </label>
                        </div>
                    </Card>

                    {/* Action Buttons */}
                    <div className="flex items-center gap-4">
                        <Button type="submit" variant="primary">
                            💾 Update Plan
                        </Button>
                        <Link href={`/admin/plans/${plan.id}`}>
                            <Button type="button" variant="secondary">
                                Cancel
                            </Button>
                        </Link>
                        <Link href="/admin/plans">
                            <Button type="button" variant="secondary">
                                ← Back to Plans
                            </Button>
                        </Link>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}

