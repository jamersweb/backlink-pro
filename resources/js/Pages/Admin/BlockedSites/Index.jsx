import { useState } from 'react';
import { router, usePage, useForm } from '@inertiajs/react';
import AdminLayout from '../../../Components/Layout/AdminLayout';
import Card from '../../../Components/Shared/Card';
import Button from '../../../Components/Shared/Button';
import Input from '../../../Components/Shared/Input';

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
                    <div className="p-4 bg-green-50 border border-green-200 rounded-md">
                        <p className="text-sm text-green-800">{flash.success}</p>
                    </div>
                )}

                {/* Stats */}
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <Card className="bg-white border border-gray-200 shadow-md">
                        <div className="p-4">
                            <p className="text-gray-600 text-xs font-medium mb-1">Total Blocked</p>
                            <p className="text-2xl font-bold text-gray-900">{stats?.total || 0}</p>
                        </div>
                    </Card>
                    <Card className="bg-white border border-red-200 shadow-md">
                        <div className="p-4">
                            <p className="text-gray-600 text-xs font-medium mb-1">Active Blocks</p>
                            <p className="text-2xl font-bold text-red-600">{stats?.active || 0}</p>
                        </div>
                    </Card>
                    <Card className="bg-white border border-gray-200 shadow-md">
                        <div className="p-4">
                            <p className="text-gray-600 text-xs font-medium mb-1">Inactive</p>
                            <p className="text-2xl font-bold text-gray-600">{stats?.inactive || 0}</p>
                        </div>
                    </Card>
                </div>

                {/* Filters and Add Button */}
                <Card className="bg-white border border-gray-200 shadow-md">
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
                                <label className="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select
                                    value={localFilters.is_active || ''}
                                    onChange={(e) => handleFilterChange('is_active', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500"
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
                        <form onSubmit={handleSubmit} className="mt-6 pt-6 border-t border-gray-200 space-y-4">
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
                <Card className="bg-white border border-gray-200 shadow-md">
                    <h3 className="text-lg font-bold text-gray-900 mb-4">Blocked Sites</h3>
                    {blockedSites?.data && blockedSites.data.length > 0 ? (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Domain</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Reason</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Blocked By</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Status</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Created</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {blockedSites.data.map((site) => (
                                        <tr key={site.id} className={!site.is_active ? 'opacity-60' : ''}>
                                            <td className="px-4 py-3 text-sm font-medium text-gray-900">{site.domain}</td>
                                            <td className="px-4 py-3 text-sm text-gray-600">{site.reason || 'N/A'}</td>
                                            <td className="px-4 py-3 text-sm text-gray-600">{site.blocked_by || 'System'}</td>
                                            <td className="px-4 py-3 text-sm">
                                                <span className={`px-2 py-1 text-xs font-medium rounded-full ${
                                                    site.is_active ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800'
                                                }`}>
                                                    {site.is_active ? 'Active' : 'Inactive'}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-600">
                                                {new Date(site.created_at).toLocaleDateString()}
                                            </td>
                                            <td className="px-4 py-3 text-sm">
                                                <div className="flex gap-2">
                                                    <button
                                                        onClick={() => handleToggle(site.id)}
                                                        className={`px-2 py-1 text-xs rounded ${
                                                            site.is_active 
                                                                ? 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200' 
                                                                : 'bg-green-100 text-green-800 hover:bg-green-200'
                                                        }`}
                                                    >
                                                        {site.is_active ? 'Deactivate' : 'Activate'}
                                                    </button>
                                                    <button
                                                        onClick={() => handleDelete(site.id)}
                                                        className="px-2 py-1 text-xs bg-red-100 text-red-800 rounded hover:bg-red-200"
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
                        <p className="text-gray-500 text-center py-8">No blocked sites found.</p>
                    )}

                    {/* Pagination */}
                    {blockedSites?.links && blockedSites.links.length > 3 && (
                        <div className="mt-6 pt-4 border-t border-gray-200">
                            <div className="flex items-center justify-between">
                                <div className="text-sm text-gray-600">
                                    Showing {blockedSites.from} to {blockedSites.to} of {blockedSites.total} results
                                </div>
                                <div className="flex gap-2">
                                    {blockedSites.links.map((link, index) => (
                                        <button
                                            key={index}
                                            onClick={() => link.url && router.get(link.url)}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                            className={`px-3 py-1 text-sm rounded-md ${
                                                link.active 
                                                    ? 'bg-blue-500 text-white' 
                                                    : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300'
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

