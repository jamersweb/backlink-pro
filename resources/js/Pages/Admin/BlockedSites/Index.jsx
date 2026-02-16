import { useState } from 'react';
import { router, usePage, useForm } from '@inertiajs/react';
import AdminLayout from '@/Components/Layout/AdminLayout';
import Card from '@/Components/Shared/Card';
import Button from '@/Components/Shared/Button';
import Input from '@/Components/Shared/Input';

export default function AdminBlockedSitesIndex({ blockedSites, stats, filters }) {
    const { flash } = usePage().props;
    const [showAddForm, setShowAddForm] = useState(false);
    const [editingId, setEditingId] = useState(null);
    const [localFilters, setLocalFilters] = useState(filters || {
        is_active: '',
        search: '',
    });

    const { data: formData, setData: setFormData, post, processing, errors, reset } = useForm({
        domain: '',
        reason: '',
        blocked_by: '',
    });

    const handleFilterChange = (key, value) => {
        const newFilters = { ...localFilters, [key]: value };
        setLocalFilters(newFilters);
        router.get('/admin/blocked-sites', newFilters, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/admin/blocked-sites', {
            onSuccess: () => {
                reset();
                setShowAddForm(false);
            },
        });
    };

    const handleToggle = (id) => {
        router.post(`/admin/blocked-sites/${id}/toggle`);
    };

    const handleDelete = (id) => {
        if (confirm('Are you sure you want to remove this blocked site?')) {
            router.delete(`/admin/blocked-sites/${id}`);
        }
    };

    return (
        <AdminLayout header="Blocked Sites">
            <div className="space-y-6">
                {/* Flash Messages */}
                {flash?.success && (
                    <div className="p-4 rounded-lg bg-[#12B76A]/10 border border-[#12B76A]/30">
                        <p className="text-sm text-[#12B76A] font-medium">{flash.success}</p>
                    </div>
                )}

                {/* Stats */}
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <Card variant="elevated">
                        <div className="p-4">
                            <p className="text-[var(--admin-text-muted)] text-xs font-medium mb-1">Total Blocked</p>
                            <p className="text-2xl font-bold text-[var(--admin-text)]">{stats?.total || 0}</p>
                        </div>
                    </Card>
                    <Card variant="elevated">
                        <div className="p-4">
                            <p className="text-red-600 dark:text-red-400 text-xs font-medium mb-1">Active Blocks</p>
                            <p className="text-2xl font-bold text-red-600 dark:text-red-400">{stats?.active || 0}</p>
                        </div>
                    </Card>
                    <Card variant="elevated">
                        <div className="p-4">
                            <p className="text-[var(--admin-text-muted)] text-xs font-medium mb-1">Inactive</p>
                            <p className="text-2xl font-bold text-[var(--admin-text-muted)]">{stats?.inactive || 0}</p>
                        </div>
                    </Card>
                </div>

                {/* Filters and Add Button */}
                <Card variant="elevated">
                    <div className="flex flex-col md:flex-row gap-4 items-start md:items-center justify-between">
                        <div className="flex-1 grid grid-cols-1 md:grid-cols-3 gap-4">
                            <Input
                                label="Search"
                                type="text"
                                value={localFilters.search || ''}
                                onChange={(e) => handleFilterChange('search', e.target.value)}
                                placeholder="Search domain, reason..."
                            />
                            <div>
                                <label className="block text-sm font-medium text-[var(--admin-text)] mb-1">Status</label>
                                <select
                                    value={localFilters.is_active || ''}
                                    onChange={(e) => handleFilterChange('is_active', e.target.value)}
                                    className="admin-select w-full px-3 py-2 rounded-lg"
                                >
                                    <option value="">All</option>
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                            <div className="flex items-end">
                                <Button
                                    variant="secondary"
                                    onClick={() => {
                                        const emptyFilters = { is_active: '', search: '' };
                                        setLocalFilters(emptyFilters);
                                        router.get('/admin/blocked-sites', emptyFilters);
                                    }}
                                >
                                    Clear Filters
                                </Button>
                            </div>
                        </div>
                        <Button
                            variant="primary"
                            onClick={() => setShowAddForm(!showAddForm)}
                        >
                            {showAddForm ? 'Cancel' : '+ Add Blocked Site'}
                        </Button>
                    </div>

                    {/* Add Form */}
                    {showAddForm && (
                        <form onSubmit={handleSubmit} className="mt-6 pt-6 border-t border-[var(--admin-border)] space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <Input
                                    label="Domain"
                                    type="text"
                                    value={formData.domain}
                                    onChange={(e) => setFormData('domain', e.target.value)}
                                    error={errors.domain}
                                    placeholder="example.com"
                                    required
                                />
                                <Input
                                    label="Reason"
                                    type="text"
                                    value={formData.reason}
                                    onChange={(e) => setFormData('reason', e.target.value)}
                                    error={errors.reason}
                                    placeholder="Opt-out request, complaint, etc."
                                />
                                <Input
                                    label="Blocked By"
                                    type="text"
                                    value={formData.blocked_by}
                                    onChange={(e) => setFormData('blocked_by', e.target.value)}
                                    error={errors.blocked_by}
                                    placeholder="Admin email or system"
                                />
                            </div>
                            <div className="flex gap-2">
                                <Button type="submit" variant="primary" disabled={processing}>
                                    {processing ? 'Adding...' : 'Add Blocked Site'}
                                </Button>
                                <Button type="button" variant="secondary" onClick={() => {
                                    reset();
                                    setShowAddForm(false);
                                }}>
                                    Cancel
                                </Button>
                            </div>
                        </form>
                    )}
                </Card>

                {/* Blocked Sites Table */}
                <Card variant="elevated">
                    <h3 className="text-lg font-bold text-[var(--admin-text)] mb-4">Blocked Sites</h3>
                    {blockedSites?.data && blockedSites.data.length > 0 ? (
                        <div className="overflow-x-auto">
                            <table className="admin-table min-w-full">
                                <thead>
                                    <tr>
                                        <th>Domain</th>
                                        <th>Reason</th>
                                        <th>Blocked By</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {blockedSites.data.map((site) => (
                                        <tr key={site.id} className={!site.is_active ? 'opacity-60' : ''}>
                                            <td className="text-sm font-medium text-[var(--admin-text)]">{site.domain}</td>
                                            <td className="text-sm text-[var(--admin-text-muted)]">{site.reason || 'N/A'}</td>
                                            <td className="text-sm text-[var(--admin-text-muted)]">{site.blocked_by || 'System'}</td>
                                            <td className="text-sm">
                                                <span className={`admin-badge ${site.is_active ? 'admin-badge-danger' : 'admin-badge-neutral'}`}>
                                                    {site.is_active ? 'Active' : 'Inactive'}
                                                </span>
                                            </td>
                                            <td className="text-sm text-[var(--admin-text-muted)]">
                                                {new Date(site.created_at).toLocaleDateString()}
                                            </td>
                                            <td className="text-sm">
                                                <div className="flex gap-2">
                                                    <button
                                                        onClick={() => handleToggle(site.id)}
                                                        className={`admin-badge cursor-pointer ${site.is_active ? 'admin-badge-warning' : 'admin-badge-success'}`}
                                                    >
                                                        {site.is_active ? 'Deactivate' : 'Activate'}
                                                    </button>
                                                    <button
                                                        onClick={() => handleDelete(site.id)}
                                                        className="admin-badge admin-badge-danger cursor-pointer"
                                                    >
                                                        Delete
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ) : (
                        <p className="text-[var(--admin-text-muted)] text-center py-8">No blocked sites found.</p>
                    )}

                    {/* Pagination */}
                    {blockedSites?.links && blockedSites.links.length > 3 && (
                        <div className="mt-6 pt-4 border-t border-[var(--admin-border)]">
                            <div className="flex items-center justify-between">
                                <div className="text-sm text-[var(--admin-text-muted)]">
                                    Showing {blockedSites.from} to {blockedSites.to} of {blockedSites.total} results
                                </div>
                                <div className="flex gap-2">
                                    {blockedSites.links.map((link, index) => (
                                        <button
                                            key={index}
                                            onClick={() => link.url && router.get(link.url)}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                            className={`px-3 py-1 text-sm rounded-lg transition-colors ${
                                                link.active 
                                                    ? 'bg-[var(--admin-primary)] text-white' 
                                                    : 'bg-[var(--admin-surface)] text-[var(--admin-text)] hover:bg-[var(--admin-hover-bg)] border border-[var(--admin-border)]'
                                            } ${!link.url ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'}`}
                                            disabled={!link.url}
                                        />
                                    ))}
                                </div>
                            </div>
                        </div>
                    )}
                </Card>
            </div>
        </AdminLayout>
    );
}

