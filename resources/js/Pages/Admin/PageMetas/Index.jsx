import { useState } from 'react';
import AdminLayout from '@/Components/Layout/AdminLayout';
import Card from '@/Components/Shared/Card';
import Button from '@/Components/Shared/Button';
import { Link, router } from '@inertiajs/react';

export default function PageMetasIndex({ pages, filters }) {
    const [searchTerm, setSearchTerm] = useState(filters?.search || '');
    const [statusFilter, setStatusFilter] = useState(filters?.status || '');

    const handleSearch = (e) => {
        e.preventDefault();
        router.get('/admin/page-metas', {
            search: searchTerm,
            status: statusFilter,
        }, { preserveState: true });
    };

    const handleToggleStatus = (id) => {
        router.post(`/admin/page-metas/${id}/toggle-status`);
    };

    const handleDelete = (id, pageName) => {
        if (confirm(`Are you sure you want to delete "${pageName}"?`)) {
            router.delete(`/admin/page-metas/${id}`);
        }
    };

    const handleDuplicate = (id) => {
        router.post(`/admin/page-metas/${id}/duplicate`);
    };

    const handleImportDefaults = () => {
        if (confirm('This will import all default marketing pages. Existing pages will not be overwritten. Continue?')) {
            router.post('/admin/page-metas/import-defaults');
        }
    };

    return (
        <AdminLayout header="Page SEO Management">
            <div className="space-y-6">
                {/* Header Actions */}
                <div className="flex justify-between items-center">
                    <div>
                        <h2 className="text-lg font-semibold text-gray-900">Manage Page Metadata</h2>
                        <p className="text-sm text-gray-600">Edit meta titles, descriptions, keywords, and schema markup for all pages.</p>
                    </div>
                    <div className="flex gap-3">
                        <Button variant="secondary" onClick={handleImportDefaults}>
                            Import Defaults
                        </Button>
                        <Link href="/admin/page-metas/create">
                            <Button>+ Add New Page</Button>
                        </Link>
                    </div>
                </div>

                {/* Search & Filter */}
                <Card className="p-4 bg-white border border-gray-200">
                    <form onSubmit={handleSearch} className="flex gap-4">
                        <div className="flex-1">
                            <input
                                type="text"
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                                placeholder="Search by page name, key, URL, or title..."
                                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                        </div>
                        <select
                            value={statusFilter}
                            onChange={(e) => setStatusFilter(e.target.value)}
                            className="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        <Button type="submit">Search</Button>
                    </form>
                </Card>

                {/* Pages Table */}
                <Card className="bg-white border border-gray-200 shadow-md">
                    {pages?.data && pages.data.length > 0 ? (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Page</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Meta Title</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Status</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">SEO</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Updated</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {pages.data.map((page) => (
                                        <tr key={page.id} className="hover:bg-gray-50 transition-colors">
                                            <td className="px-6 py-4">
                                                <div>
                                                    <div className="text-sm font-medium text-gray-900">{page.page_name}</div>
                                                    <div className="text-xs text-gray-500">{page.url_path}</div>
                                                    <div className="text-xs text-blue-600 font-mono">{page.page_key}</div>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="max-w-xs">
                                                    <div className="text-sm text-gray-900 truncate" title={page.meta_title}>
                                                        {page.meta_title || <span className="text-gray-400 italic">Not set</span>}
                                                    </div>
                                                    {page.meta_title && (
                                                        <div className={`text-xs ${page.meta_title.length > 60 ? 'text-orange-600' : 'text-green-600'}`}>
                                                            {page.meta_title.length}/60 chars
                                                        </div>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <button
                                                    onClick={() => handleToggleStatus(page.id)}
                                                    className={`px-2 py-1 text-xs font-medium rounded cursor-pointer ${
                                                        page.is_active
                                                            ? 'bg-green-100 text-green-800 hover:bg-green-200'
                                                            : 'bg-gray-100 text-gray-800 hover:bg-gray-200'
                                                    }`}
                                                >
                                                    {page.is_active ? 'Active' : 'Inactive'}
                                                </button>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="flex gap-1">
                                                    <span className={`px-1.5 py-0.5 text-xs rounded ${page.is_indexable ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`}>
                                                        {page.is_indexable ? 'Index' : 'NoIndex'}
                                                    </span>
                                                    <span className={`px-1.5 py-0.5 text-xs rounded ${page.is_followable ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`}>
                                                        {page.is_followable ? 'Follow' : 'NoFollow'}
                                                    </span>
                                                    {page.schema_json && Object.keys(page.schema_json).length > 0 && (
                                                        <span className="px-1.5 py-0.5 text-xs rounded bg-purple-100 text-purple-700">
                                                            Schema
                                                        </span>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                {page.updated_at ? new Date(page.updated_at).toLocaleDateString() : 'N/A'}
                                                {page.updated_by_user && (
                                                    <div className="text-xs text-gray-400">by {page.updated_by_user.name}</div>
                                                )}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="flex gap-2">
                                                    <Link href={`/admin/page-metas/${page.id}/edit`}>
                                                        <Button variant="secondary" size="sm">Edit</Button>
                                                    </Link>
                                                    <Button variant="secondary" size="sm" onClick={() => handleDuplicate(page.id)}>
                                                        Copy
                                                    </Button>
                                                    <Button variant="danger" size="sm" onClick={() => handleDelete(page.id, page.page_name)}>
                                                        Delete
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ) : (
                        <div className="text-center py-16">
                            <div className="inline-block p-6 bg-gray-100 rounded-full mb-4">
                                <span className="text-5xl">📄</span>
                            </div>
                            <p className="text-gray-500 font-medium text-lg">No pages found</p>
                            <p className="text-gray-400 text-sm mt-2">Click "Import Defaults" to add marketing pages or "Add New Page" to create custom pages.</p>
                        </div>
                    )}

                    {/* Pagination */}
                    {pages?.links && pages.links.length > 3 && (
                        <div className="px-6 py-4 border-t border-gray-200 bg-gray-50">
                            <div className="flex flex-col sm:flex-row items-center justify-between gap-4">
                                <div className="text-sm text-gray-700">
                                    Showing <span className="font-medium">{pages.from || 0}</span> to <span className="font-medium">{pages.to || 0}</span> of <span className="font-medium">{pages.total || 0}</span> results
                                </div>
                                <div className="flex flex-wrap gap-2">
                                    {pages.links.map((link, index) => (
                                        <Link
                                            key={index}
                                            href={link.url || '#'}
                                            className={`px-3 py-2 text-sm font-medium rounded-md transition-colors ${
                                                link.active
                                                    ? 'bg-gray-900 text-white'
                                                    : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-300'
                                            }`}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    ))}
                                </div>
                            </div>
                        </div>
                    )}
                </Card>

                {/* Info Card */}
                <Card className="p-6 bg-blue-50 border border-blue-200">
                    <div className="flex items-start gap-4">
                        <div className="p-2 bg-blue-100 rounded-lg">
                            <svg className="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h4 className="font-semibold text-blue-900">SEO Best Practices</h4>
                            <ul className="text-sm text-blue-700 mt-2 space-y-1">
                                <li>• <strong>Meta Title:</strong> Keep under 60 characters for optimal display in search results</li>
                                <li>• <strong>Meta Description:</strong> Keep between 150-160 characters</li>
                                <li>• <strong>Schema Markup:</strong> Add structured data to help search engines understand your content</li>
                                <li>• <strong>Canonical URL:</strong> Set if you have duplicate content across multiple URLs</li>
                            </ul>
                        </div>
                    </div>
                </Card>
            </div>
        </AdminLayout>
    );
}
