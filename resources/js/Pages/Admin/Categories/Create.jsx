import AdminLayout from '@/Components/Layout/AdminLayout';
import Card from '@/Components/Shared/Card';
import Button from '@/Components/Shared/Button';
import Input from '@/Components/Shared/Input';
import { Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function CategoriesCreate({ parentCategories }) {
    const { flash } = usePage().props;
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
            <div className="space-y-6">
                {/* Error Messages */}
                {flash?.error && (
                    <div className="p-4 bg-red-50 border border-red-200 rounded-md">
                        <p className="text-sm text-red-800">{flash.error}</p>
                    </div>
                )}

                {/* Back Button */}
                <Link href="/admin/categories" className="inline-flex items-center text-gray-900 hover:text-gray-700 font-medium">
                    ← Back to Categories
                </Link>

                {/* Form */}
                <Card className="bg-white border border-gray-200 shadow-md">
                    <form onSubmit={handleSubmit} className="p-6 space-y-6">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Category Name <span className="text-red-500">*</span>
                            </label>
                            <Input
                                type="text"
                                required
                                value={form.name}
                                onChange={(e) => setForm({ ...form, name: e.target.value })}
                                placeholder="e.g., Business, Technology, Health"
                            />
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Parent Category (Optional)
                            </label>
                            <select
                                value={form.parent_id}
                                onChange={(e) => setForm({ ...form, parent_id: e.target.value })}
                                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-900"
                            >
                                <option value="">None (Top-level Category)</option>
                                {parentCategories?.map((cat) => (
                                    <option key={cat.id} value={cat.id}>
                                        {cat.name}
                                    </option>
                                ))}
                            </select>
                            <p className="text-xs text-gray-500 mt-1">
                                Leave empty to create a parent category, or select a parent to create a subcategory
                            </p>
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
                            </select>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Description (Optional)
                            </label>
                            <textarea
                                value={form.description}
                                onChange={(e) => setForm({ ...form, description: e.target.value })}
                                rows={3}
                                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-900"
                                placeholder="Brief description of this category"
                            />
                        </div>

                        <div className="flex gap-3 pt-4">
                            <Button type="submit" variant="primary" className="flex-1">
                                Create Category
                            </Button>
                            <Link href="/admin/categories">
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

