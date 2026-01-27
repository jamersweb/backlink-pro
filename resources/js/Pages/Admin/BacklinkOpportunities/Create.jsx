import AdminLayout from '@/Components/Layout/AdminLayout';
import Card from '@/Components/Shared/Card';
import Button from '@/Components/Shared/Button';
import Input from '@/Components/Shared/Input';
import { Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function BacklinkOpportunitiesCreate({ categories }) {
    const { flash } = usePage().props;
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
            <div className="space-y-6">
                {/* Error Messages */}
                {flash?.error && (
                    <div className="p-4 bg-red-50 border border-red-200 rounded-md">
                        <p className="text-sm text-red-800">{flash.error}</p>
                    </div>
                )}

                {/* Back Button */}
                <Link href="/admin/backlink-opportunities" className="inline-flex items-center text-gray-900 hover:text-gray-700 font-medium">
                    ← Back to Opportunities
                </Link>

                {/* Form */}
                <Card className="bg-white border border-gray-200 shadow-md">
                    <form onSubmit={handleSubmit} className="p-6 space-y-6">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                URL <span className="text-red-500">*</span>
                            </label>
                            <Input
                                type="url"
                                required
                                value={form.url}
                                onChange={(e) => setForm({ ...form, url: e.target.value })}
                                placeholder="https://example.com/page"
                            />
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    PA (Page Authority)
                                </label>
                                <Input
                                    type="number"
                                    min="0"
                                    max="100"
                                    value={form.pa}
                                    onChange={(e) => setForm({ ...form, pa: e.target.value })}
                                    placeholder="0-100"
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    DA (Domain Authority)
                                </label>
                                <Input
                                    type="number"
                                    min="0"
                                    max="100"
                                    value={form.da}
                                    onChange={(e) => setForm({ ...form, da: e.target.value })}
                                    placeholder="0-100"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Site Type <span className="text-red-500">*</span>
                                </label>
                                <select
                                    required
                                    value={form.site_type}
                                    onChange={(e) => setForm({ ...form, site_type: e.target.value })}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-900"
                                >
                                    <option value="comment">Comment</option>
                                    <option value="profile">Profile</option>
                                    <option value="forum">Forum</option>
                                    <option value="guestposting">Guest Post</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Status <span className="text-red-500">*</span>
                                </label>
                                <select
                                    required
                                    value={form.status}
                                    onChange={(e) => setForm({ ...form, status: e.target.value })}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-900"
                                >
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="banned">Banned</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Daily Site Limit (Optional)
                            </label>
                            <Input
                                type="number"
                                min="0"
                                value={form.daily_site_limit}
                                onChange={(e) => setForm({ ...form, daily_site_limit: e.target.value })}
                                placeholder="Max links per day for this site"
                            />
                            <p className="text-xs text-gray-500 mt-1">Leave empty for unlimited</p>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Categories <span className="text-red-500">*</span>
                            </label>
                            <div className="border border-gray-300 rounded-md p-4 max-h-64 overflow-y-auto">
                                {parentCategories.map((parent) => (
                                    <div key={parent.id} className="mb-4">
                                        <label className="flex items-center space-x-2 cursor-pointer hover:bg-gray-50 p-2 rounded">
                                            <input
                                                type="checkbox"
                                                checked={form.category_ids.includes(parent.id)}
                                                onChange={() => toggleCategory(parent.id)}
                                                className="rounded border-gray-300 text-gray-900 focus:ring-gray-900"
                                            />
                                            <span className="font-medium text-gray-900">{parent.name}</span>
                                        </label>
                                        {subcategoriesByParent[parent.id]?.map((subcat) => (
                                            <label key={subcat.id} className="flex items-center space-x-2 cursor-pointer hover:bg-gray-50 p-2 rounded ml-6">
                                                <input
                                                    type="checkbox"
                                                    checked={form.category_ids.includes(subcat.id)}
                                                    onChange={() => toggleCategory(subcat.id)}
                                                    className="rounded border-gray-300 text-gray-900 focus:ring-gray-900"
                                                />
                                                <span className="text-sm text-gray-700">└─ {subcat.name}</span>
                                            </label>
                                        ))}
                                    </div>
                                ))}
                            </div>
                            <p className="text-xs text-gray-500 mt-1">Select at least one category</p>
                        </div>

                        <div className="flex gap-3 pt-4">
                            <Button type="submit" variant="primary" className="flex-1">
                                Create Opportunity
                            </Button>
                            <Link href="/admin/backlink-opportunities">
                                <Button type="button" variant="secondary">
                                    Cancel
                                </Button>
                            </Link>
                        </div>
                    </form>
                </Card>
            </div>
        </AdminLayout>
    );
}

