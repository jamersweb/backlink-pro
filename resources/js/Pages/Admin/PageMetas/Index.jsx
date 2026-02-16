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
                        <h2 className="text-lg font-semibold text-[var(--admin-text)]">Manage Page Metadata</h2>
                        <p className="text-sm text-[var(--admin-text-muted)]">Edit meta titles, descriptions, keywords, and schema markup for all pages.</p>
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
                <Card variant="elevated">
                    <form onSubmit={handleSearch} className="flex gap-4">
                        <div className="flex-1">
                            <input
                                type="text"
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                                placeholder="Search by page name, key, URL, or title..."
                                className="admin-input w-full px-3 py-2 rounded-lg"
                            />
                        </div>
                        <select
                            value={statusFilter}
                            onChange={(e) => setStatusFilter(e.target.value)}
                            className="admin-select px-3 py-2 rounded-lg"
                        >
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        <Button type="submit">Search</Button>
                    </form>
                </Card>

                {/* Pages Table */}
                <Card variant="elevated">
                    {pages?.data && pages.data.length > 0 ? (
                        <div className="overflow-x-auto">
                            <table className="admin-table min-w-full">
                                <thead>
                                    <tr>
                                        <th>Page</th>
                                        <th>Meta Title</th>
                                        <th>Status</th>
                                        <th>SEO</th>
                                        <th>Updated</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {pages.data.map((page) => (
                                        <tr key={page.id}>
                                            <td className="px-6 py-4">
                                                <div>
                                                    <div className="text-sm font-medium text-[var(--admin-text)]">{page.page_name}</div>
                                                    <div className="text-xs text-[var(--admin-text-muted)]">{page.url_path}</div>
                                                    <div className="text-xs admin-link font-mono">{page.page_key}</div>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="max-w-xs">
                                                    <div className="text-sm text-[var(--admin-text)] truncate" title={page.meta_title}>
                                                        {page.meta_title || <span className="text-[var(--admin-text-dim)] italic">Not set</span>}
                                                    </div>
                                                    {page.meta_title && (
                                                        <div className={`text-xs ${page.meta_title.length > 60 ? 'admin-text-warning' : 'admin-text-success'}`}>
                                                            {page.meta_title.length}/60 chars
                                                        </div>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <button
                                                    onClick={() => handleToggleStatus(page.id)}
                                                    className={`admin-badge cursor-pointer ${page.is_active ? 'admin-badge-success' : 'admin-badge-neutral'}`}
                                                >
                                                    {page.is_active ? 'Active' : 'Inactive'}
                                                </button>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="flex gap-1">
                                                    <span className={`admin-badge ${page.is_indexable ? 'admin-badge-success' : 'admin-badge-danger'}`}>
                                                        {page.is_indexable ? 'Index' : 'NoIndex'}
                                                    </span>
                                                    <span className={`admin-badge ${page.is_followable ? 'admin-badge-success' : 'admin-badge-danger'}`}>
                                                        {page.is_followable ? 'Follow' : 'NoFollow'}
                                                    </span>
                                                    {page.schema_json && Object.keys(page.schema_json).length > 0 && (
                                                        <span className="admin-badge admin-badge-primary">Schema</span>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-[var(--admin-text-muted)]">
                                                {page.updated_at ? new Date(page.updated_at).toLocaleDateString() : 'N/A'}
                                                {page.updated_by_user && (
                                                    <div className="text-xs text-[var(--admin-text-dim)]">by {page.updated_by_user.name}</div>
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
                            <div className="inline-block p-6 rounded-full mb-4 bg-[var(--admin-hover-bg)]">
                                <span className="text-5xl">📄</span>
                            </div>
                            <p className="text-[var(--admin-text)] font-medium text-lg">No pages found</p>
                            <p className="text-[var(--admin-text-muted)] text-sm mt-2">Click "Import Defaults" to add marketing pages or "Add New Page" to create custom pages.</p>
                        </div>
                    )}

                    {/* Pagination */}
                    {pages?.links && pages.links.length > 3 && (
                        <div className="px-6 py-4 border-t border-[var(--admin-border)] bg-[var(--admin-surface-2)]">
                            <div className="flex flex-col sm:flex-row items-center justify-between gap-4">
                                <div className="text-sm text-[var(--admin-text-muted)]">
                                    Showing <span className="font-medium text-[var(--admin-text)]">{pages.from || 0}</span> to <span className="font-medium text-[var(--admin-text)]">{pages.to || 0}</span> of <span className="font-medium text-[var(--admin-text)]">{pages.total || 0}</span> results
                                </div>
                                <div className="flex flex-wrap gap-2">
                                    {pages.links.map((link, index) => (
                                        <Link
                                            key={index}
                                            href={link.url || '#'}
                                            className={`px-3 py-2 text-sm font-medium rounded-lg transition-colors ${
                                                link.active ? 'bg-[var(--admin-primary)] text-white' : 'bg-[var(--admin-surface)] text-[var(--admin-text)] hover:bg-[var(--admin-hover-bg)] border border-[var(--admin-border)]'
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
                <Card className="p-6 bg-[#0EA5E9]/10 border border-[#0EA5E9]/30">
                    <div className="flex items-start gap-4">
                        <div className="p-2 rounded-lg bg-[#0EA5E9]/20">
                            <svg className="w-6 h-6 text-[#0EA5E9]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h4 className="font-semibold text-[#0EA5E9]">SEO Best Practices</h4>
                            <ul className="text-sm text-[var(--admin-text-muted)] mt-2 space-y-1">
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
