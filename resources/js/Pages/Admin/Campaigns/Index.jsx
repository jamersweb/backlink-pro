import { useState } from 'react';
import AdminLayout from '../../../Components/Layout/AdminLayout';
import Card from '../../../Components/Shared/Card';
import Button from '../../../Components/Shared/Button';
import Input from '../../../Components/Shared/Input';
import { Link, router, usePage } from '@inertiajs/react';

export default function AdminCampaignsIndex({ campaigns, stats, users, filters = {} }) {
    const { flash } = usePage().props;
    const [deletingId, setDeletingId] = useState(null);
    const [search, setSearch] = useState(filters.search || '');
    const [statusFilter, setStatusFilter] = useState(filters.status || '');
    const [userFilter, setUserFilter] = useState(filters.user_id || '');

    const handleDelete = (campaignId, campaignName) => {
        if (window.confirm(`Are you sure you want to delete "${campaignName}"? This action cannot be undone.`)) {
            setDeletingId(campaignId);
            router.delete(`/admin/campaigns/${campaignId}`, {
                onFinish: () => setDeletingId(null),
            });
        }
    };

    const handleFilter = () => {
        router.get('/admin/campaigns', {
            search: search || undefined,
            status: statusFilter || undefined,
            user_id: userFilter || undefined,
        }, {
            preserveState: true,
            replace: true,
        });
    };

    const handlePause = (campaignId) => {
        router.post(`/admin/campaigns/${campaignId}/pause`, {}, {
            preserveScroll: true,
        });
    };

    const handleResume = (campaignId) => {
        router.post(`/admin/campaigns/${campaignId}/resume`, {}, {
            preserveScroll: true,
        });
    };

    return (
        <AdminLayout header="Campaigns Management">
            <div className="space-y-6">
                {/* Success Message */}
                {flash?.success && (
                    <div className="p-4 bg-green-50 border border-green-200 rounded-md">
                        <p className="text-sm text-green-800">{flash.success}</p>
                    </div>
                )}

                {/* Stats Cards */}
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    <Card className="bg-white border border-gray-200 shadow-md">
                        <div className="p-4">
                            <p className="text-gray-600 text-sm font-medium mb-1">Total</p>
                            <p className="text-3xl font-bold text-gray-900">{stats?.total || 0}</p>
                        </div>
                    </Card>
                    <Card className="bg-white border border-green-200 shadow-md">
                        <div className="p-4">
                            <p className="text-green-600 text-sm font-medium mb-1">Active</p>
                            <p className="text-3xl font-bold text-green-900">{stats?.active || 0}</p>
                        </div>
                    </Card>
                    <Card className="bg-white border border-yellow-200 shadow-md">
                        <div className="p-4">
                            <p className="text-yellow-600 text-sm font-medium mb-1">Paused</p>
                            <p className="text-3xl font-bold text-yellow-900">{stats?.paused || 0}</p>
                        </div>
                    </Card>
                    <Card className="bg-white border border-blue-200 shadow-md">
                        <div className="p-4">
                            <p className="text-blue-600 text-sm font-medium mb-1">Completed</p>
                            <p className="text-3xl font-bold text-blue-900">{stats?.completed || 0}</p>
                        </div>
                    </Card>
                    <Card className="bg-white border border-red-200 shadow-md">
                        <div className="p-4">
                            <p className="text-red-600 text-sm font-medium mb-1">Error</p>
                            <p className="text-3xl font-bold text-red-900">{stats?.error || 0}</p>
                        </div>
                    </Card>
                </div>

                {/* Filters */}
                <Card className="bg-white border border-gray-200 shadow-md">
                    <div className="p-4">
                        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <Input
                                    type="text"
                                    placeholder="Search campaigns..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    onKeyPress={(e) => e.key === 'Enter' && handleFilter()}
                                />
                            </div>
                            <div>
                                <select
                                    value={statusFilter}
                                    onChange={(e) => setStatusFilter(e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500"
                                >
                                    <option value="">All Statuses</option>
                                    <option value="active">Active</option>
                                    <option value="paused">Paused</option>
                                    <option value="completed">Completed</option>
                                    <option value="error">Error</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <div>
                                <select
                                    value={userFilter}
                                    onChange={(e) => setUserFilter(e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500"
                                >
                                    <option value="">All Users</option>
                                    {users?.map((user) => (
                                        <option key={user.id} value={user.id}>
                                            {user.name} ({user.email})
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <Button variant="primary" onClick={handleFilter} className="w-full">
                                    🔍 Filter
                                </Button>
                            </div>
                        </div>
                    </div>
                </Card>

                {/* Campaigns Table */}
                <Card className="bg-white border border-gray-200 shadow-md">
                    {campaigns?.data && campaigns.data.length > 0 ? (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Campaign</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">User</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Domain</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Status</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Backlinks</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Tasks</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Created</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {campaigns.data.map((campaign) => (
                                        <tr key={campaign.id} className="hover:bg-gray-50 transition-colors">
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="flex items-center">
                                                    <div className="h-10 w-10 rounded-lg bg-gray-200 flex items-center justify-center text-gray-700 font-bold text-sm mr-3">
                                                        {campaign.name?.charAt(0).toUpperCase() || 'C'}
                                                    </div>
                                                    <div>
                                                        <div className="text-sm font-medium text-gray-900">{campaign.name || 'Untitled'}</div>
                                                        <div className="text-xs text-gray-500">{campaign.web_url || 'N/A'}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="text-sm text-gray-900">{campaign.user?.name || 'N/A'}</div>
                                                <div className="text-xs text-gray-500">{campaign.user?.email || ''}</div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="text-sm text-gray-900">{campaign.domain?.name || 'N/A'}</div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <span className={`px-2 py-1 text-xs font-medium rounded-full ${
                                                    campaign.status === 'active' ? 'bg-green-100 text-green-800' :
                                                    campaign.status === 'paused' ? 'bg-yellow-100 text-yellow-800' :
                                                    campaign.status === 'completed' ? 'bg-blue-100 text-blue-800' :
                                                    campaign.status === 'error' ? 'bg-red-100 text-red-800' :
                                                    'bg-gray-100 text-gray-800'
                                                }`}>
                                                    {campaign.status}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="text-sm text-gray-900 font-semibold">{campaign.backlinks_count || 0}</div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="text-sm text-gray-900 font-semibold">{campaign.automation_tasks_count || 0}</div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                {new Date(campaign.created_at).toLocaleDateString()}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm">
                                                <div className="flex items-center gap-2">
                                                    <Link href={`/admin/campaigns/${campaign.id}`} className="text-gray-600 hover:text-gray-900">
                                                        👁️
                                                    </Link>
                                                    <Link href={`/admin/campaigns/${campaign.id}/edit`} className="text-gray-600 hover:text-gray-900">
                                                        ✏️
                                                    </Link>
                                                    {campaign.status === 'active' ? (
                                                        <button
                                                            onClick={() => handlePause(campaign.id)}
                                                            className="text-yellow-600 hover:text-yellow-900"
                                                            title="Pause"
                                                        >
                                                            ⏸️
                                                        </button>
                                                    ) : campaign.status === 'paused' ? (
                                                        <button
                                                            onClick={() => handleResume(campaign.id)}
                                                            className="text-green-600 hover:text-green-900"
                                                            title="Resume"
                                                        >
                                                            ▶️
                                                        </button>
                                                    ) : null}
                                                    <button
                                                        onClick={() => handleDelete(campaign.id, campaign.name)}
                                                        disabled={deletingId === campaign.id}
                                                        className="text-red-600 hover:text-red-900 disabled:opacity-50"
                                                        title="Delete"
                                                    >
                                                        🗑️
                                                    </button>
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
                                <span className="text-5xl">📊</span>
                            </div>
                            <p className="text-gray-500 font-medium">No campaigns found</p>
                            <p className="text-gray-400 text-sm mt-2">Campaigns will appear here once created</p>
                        </div>
                    )}

                    {/* Pagination */}
                    {campaigns?.links && campaigns.links.length > 3 && (
                        <div className="px-6 py-4 border-t border-gray-200 bg-gray-50">
                            <div className="flex flex-col sm:flex-row items-center justify-between gap-4">
                                <div className="text-sm text-gray-700">
                                    Showing <span className="font-medium">{campaigns.from || 0}</span> to <span className="font-medium">{campaigns.to || 0}</span> of <span className="font-medium">{campaigns.total || 0}</span> results
                                </div>
                                <div className="flex flex-wrap gap-2">
                                    {campaigns.links.map((link, index) => (
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
            </div>
        </AdminLayout>
    );
}

