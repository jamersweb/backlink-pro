import AdminLayout from '../../../Components/Layout/AdminLayout';
import Card from '../../../Components/Shared/Card';
import Button from '../../../Components/Shared/Button';
import { Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function CategoriesIndex({ categories, parentCategories, subcategories, filters }) {
    const { flash } = usePage().props;
    const [statusFilter, setStatusFilter] = useState(filters.status || 'all');
    const [typeFilter, setTypeFilter] = useState(filters.type || 'all');

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
                    <div className="p-4 bg-green-50 border border-green-200 rounded-md">
                        <p className="text-sm text-green-800">{flash.success}</p>
                    </div>
                )}
                {flash?.error && (
                    <div className="p-4 bg-red-50 border border-red-200 rounded-md">
                        <p className="text-sm text-red-800">{flash.error}</p>
                    </div>
                )}

                {/* Action Bar */}
                <div className="flex justify-between items-center">
                    <h2 className="text-xl font-semibold text-gray-900">Categories</h2>
                    <Link href="/admin/categories/create">
                        <Button variant="primary">➕ Add Category</Button>
                    </Link>
                </div>

                {/* Filters */}
                <Card className="bg-white border border-gray-200 shadow-md">
                    <div className="p-4">
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select
                                    value={statusFilter}
                                    onChange={(e) => setStatusFilter(e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-900"
                                >
                                    <option value="all">All Status</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Type</label>
                                <select
                                    value={typeFilter}
                                    onChange={(e) => setTypeFilter(e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-900"
                                >
                                    <option value="all">All Types</option>
                                    <option value="parent">Parent Categories</option>
                                    <option value="subcategory">Subcategories</option>
                                </select>
                            </div>
                            <div className="flex items-end">
                                <Button variant="primary" onClick={handleFilter} className="w-full">
                                    🔍 Filter
                                </Button>
                            </div>
                        </div>
                    </div>
                </Card>

                {/* Categories Tree */}
                <Card className="bg-white border border-gray-200 shadow-md">
                    {parentCategories && parentCategories.length > 0 ? (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Name</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Type</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Subcategories</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Status</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {parentCategories.map((category) => {
                                        const categorySubcategories = subcategories?.[category.id] || [];
                                        return (
                                            <>
                                                <tr key={category.id} className="hover:bg-gray-50 transition-colors">
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className="text-sm font-medium text-gray-900">{category.name}</div>
                                                        {category.description && (
                                                            <div className="text-xs text-gray-500 mt-1">{category.description}</div>
                                                        )}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <span className="px-2 py-1 text-xs font-medium rounded bg-gray-100 text-gray-800">
                                                            Parent
                                                        </span>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                        {categorySubcategories.length} subcategory(ies)
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <span className={`px-2 py-1 text-xs font-medium rounded ${
                                                            category.status === 'active' 
                                                                ? 'bg-green-100 text-green-800' 
                                                                : 'bg-gray-100 text-gray-800'
                                                        }`}>
                                                            {category.status}
                                                        </span>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm">
                                                        <div className="flex gap-2">
                                                            <Link href={`/admin/categories/${category.id}/edit`} className="text-gray-900 hover:text-gray-700">
                                                                ✏️ Edit
                                                            </Link>
                                                            <button
                                                                onClick={() => handleDelete(category.id)}
                                                                className="text-red-600 hover:text-red-900"
                                                            >
                                                                🗑️ Delete
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                                {categorySubcategories.map((subcat) => (
                                                    <tr key={subcat.id} className="hover:bg-gray-50 transition-colors bg-gray-50">
                                                        <td className="px-6 py-4 whitespace-nowrap pl-12">
                                                            <div className="text-sm font-medium text-gray-700">
                                                                └─ {subcat.name}
                                                            </div>
                                                            {subcat.description && (
                                                                <div className="text-xs text-gray-500 mt-1">{subcat.description}</div>
                                                            )}
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap">
                                                            <span className="px-2 py-1 text-xs font-medium rounded bg-blue-100 text-blue-800">
                                                                Subcategory
                                                            </span>
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">-</td>
                                                        <td className="px-6 py-4 whitespace-nowrap">
                                                            <span className={`px-2 py-1 text-xs font-medium rounded ${
                                                                subcat.status === 'active' 
                                                                    ? 'bg-green-100 text-green-800' 
                                                                    : 'bg-gray-100 text-gray-800'
                                                            }`}>
                                                                {subcat.status}
                                                            </span>
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-sm">
                                                            <div className="flex gap-2">
                                                                <Link href={`/admin/categories/${subcat.id}/edit`} className="text-gray-900 hover:text-gray-700">
                                                                    ✏️ Edit
                                                                </Link>
                                                                <button
                                                                    onClick={() => handleDelete(subcat.id)}
                                                                    className="text-red-600 hover:text-red-900"
                                                                >
                                                                    🗑️ Delete
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
                            <div className="inline-block p-6 bg-gray-100 rounded-full mb-4">
                                <span className="text-5xl">📁</span>
                            </div>
                            <p className="text-gray-500 font-medium">No categories found</p>
                            <p className="text-gray-400 text-sm mt-2">Create your first category to get started</p>
                        </div>
                    )}
                </Card>
            </div>
        </AdminLayout>
    );
}

