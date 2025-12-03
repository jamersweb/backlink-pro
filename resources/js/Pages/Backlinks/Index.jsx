import { useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import AppLayout from '../../Components/Layout/AppLayout';
import Card from '../../Components/Shared/Card';
import Button from '../../Components/Shared/Button';
import Input from '../../Components/Shared/Input';

export default function BacklinksIndex({ backlinks, stats, campaigns, filters }) {
    const { flash } = usePage().props;
    const [localFilters, setLocalFilters] = useState(filters || {
        campaign_id: '',
        status: '',
        type: '',
        date_from: '',
        date_to: '',
        search: '',
    });

    const handleFilterChange = (key, value) => {
        const newFilters = { ...localFilters, [key]: value };
        setLocalFilters(newFilters);
        router.get('/backlinks', newFilters, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleExport = (format) => {
        const params = new URLSearchParams(localFilters);
        params.append('format', format);
        window.location.href = `/backlinks/export?${params.toString()}`;
    };

    const handleRecheck = (id) => {
        router.post(`/backlinks/${id}/recheck`, {}, {
            preserveScroll: true,
        });
    };

    const getStatusBadge = (status) => {
        const colors = {
            verified: 'bg-green-100 text-green-800',
            pending: 'bg-yellow-100 text-yellow-800',
            submitted: 'bg-blue-100 text-blue-800',
            error: 'bg-red-100 text-red-800',
        };
        return (
            <span className={`px-2 py-1 text-xs font-medium rounded-full ${colors[status] || colors.pending}`}>
                {status}
            </span>
        );
    };

    const getTypeBadge = (type) => {
        return (
            <span className="px-2 py-1 text-xs font-medium rounded bg-gray-100 text-gray-800 capitalize">
                {type}
            </span>
        );
    };

    return (
        <AppLayout header="Backlinks & Logs">
            <div className="space-y-6">
                {/* Flash Messages */}
                {flash?.success && (
                    <div className="p-4 bg-green-50 border border-green-200 rounded-md">
                        <p className="text-sm text-green-800">{flash.success}</p>
                    </div>
                )}

                {/* Statistics */}
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    <Card className="bg-white border border-gray-200 shadow-md">
                        <div className="p-4">
                            <p className="text-gray-600 text-xs font-medium mb-1">Total</p>
                            <p className="text-2xl font-bold text-gray-900">{stats?.total || 0}</p>
                        </div>
                    </Card>
                    <Card className="bg-white border border-green-200 shadow-md">
                        <div className="p-4">
                            <p className="text-green-600 text-xs font-medium mb-1">Verified</p>
                            <p className="text-2xl font-bold text-green-900">{stats?.verified || 0}</p>
                        </div>
                    </Card>
                    <Card className="bg-white border border-yellow-200 shadow-md">
                        <div className="p-4">
                            <p className="text-yellow-600 text-xs font-medium mb-1">Pending</p>
                            <p className="text-2xl font-bold text-yellow-900">{stats?.pending || 0}</p>
                        </div>
                    </Card>
                    <Card className="bg-white border border-blue-200 shadow-md">
                        <div className="p-4">
                            <p className="text-blue-600 text-xs font-medium mb-1">Submitted</p>
                            <p className="text-2xl font-bold text-blue-900">{stats?.submitted || 0}</p>
                        </div>
                    </Card>
                    <Card className="bg-white border border-red-200 shadow-md">
                        <div className="p-4">
                            <p className="text-red-600 text-xs font-medium mb-1">Errors</p>
                            <p className="text-2xl font-bold text-red-900">{stats?.error || 0}</p>
                        </div>
                    </Card>
                </div>

                {/* Filters */}
                <Card className="bg-white border border-gray-200 shadow-md">
                    <h3 className="text-lg font-bold text-gray-900 mb-4">Filters</h3>
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Search</label>
                            <Input
                                type="text"
                                value={localFilters.search || ''}
                                onChange={(e) => handleFilterChange('search', e.target.value)}
                                placeholder="URL, keyword, anchor..."
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Campaign</label>
                            <select
                                value={localFilters.campaign_id || ''}
                                onChange={(e) => handleFilterChange('campaign_id', e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500"
                            >
                                <option value="">All Campaigns</option>
                                {campaigns?.map((campaign) => (
                                    <option key={campaign.id} value={campaign.id}>{campaign.name}</option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select
                                value={localFilters.status || ''}
                                onChange={(e) => handleFilterChange('status', e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500"
                            >
                                <option value="">All Statuses</option>
                                <option value="verified">Verified</option>
                                <option value="pending">Pending</option>
                                <option value="submitted">Submitted</option>
                                <option value="error">Error</option>
                            </select>
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Type</label>
                            <select
                                value={localFilters.type || ''}
                                onChange={(e) => handleFilterChange('type', e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500"
                            >
                                <option value="">All Types</option>
                                <option value="comment">Comment</option>
                                <option value="profile">Profile</option>
                                <option value="forum">Forum</option>
                                <option value="guestposting">Guest Posting</option>
                            </select>
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                            <Input
                                type="date"
                                value={localFilters.date_from || ''}
                                onChange={(e) => handleFilterChange('date_from', e.target.value)}
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                            <Input
                                type="date"
                                value={localFilters.date_to || ''}
                                onChange={(e) => handleFilterChange('date_to', e.target.value)}
                            />
                        </div>
                    </div>
                    <div className="mt-4 flex gap-2">
                        <Button variant="secondary" onClick={() => {
                            const emptyFilters = {
                                campaign_id: '',
                                status: '',
                                type: '',
                                date_from: '',
                                date_to: '',
                                search: '',
                            };
                            setLocalFilters(emptyFilters);
                            router.get('/backlinks', emptyFilters);
                        }}>
                            Clear Filters
                        </Button>
                        <Button variant="secondary" onClick={() => handleExport('csv')}>
                            📥 Export CSV
                        </Button>
                        <Button variant="secondary" onClick={() => handleExport('json')}>
                            📥 Export JSON
                        </Button>
                    </div>
                </Card>

                {/* Backlinks Table */}
                <Card className="bg-white border border-gray-200 shadow-md">
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">URL</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Campaign</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Type</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Keyword</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Status</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Created</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                                {backlinks?.data && backlinks.data.length > 0 ? (
                                    backlinks.data.map((backlink) => (
                                        <tr key={backlink.id}>
                                            <td className="px-4 py-3 text-sm">
                                                <a 
                                                    href={backlink.url} 
                                                    target="_blank" 
                                                    rel="noopener noreferrer" 
                                                    className="text-blue-600 hover:text-blue-900 break-all max-w-xs truncate block"
                                                    title={backlink.url}
                                                >
                                                    {backlink.url}
                                                </a>
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-900">
                                                <Link href={`/campaign/${backlink.campaign?.id}`} className="text-blue-600 hover:text-blue-900">
                                                    {backlink.campaign?.name || 'N/A'}
                                                </Link>
                                            </td>
                                            <td className="px-4 py-3 text-sm">
                                                {getTypeBadge(backlink.type)}
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-600">
                                                {backlink.keyword || '-'}
                                            </td>
                                            <td className="px-4 py-3 text-sm">
                                                {getStatusBadge(backlink.status)}
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-600">
                                                {new Date(backlink.created_at).toLocaleDateString()}
                                            </td>
                                            <td className="px-4 py-3 text-sm">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => handleRecheck(backlink.id)}
                                                >
                                                    🔄 Re-check
                                                </Button>
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan="7" className="px-4 py-8 text-center text-gray-500">
                                            No backlinks found. {localFilters.search || Object.values(localFilters).some(f => f) ? 'Try adjusting your filters.' : 'Create a campaign to start building backlinks.'}
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {backlinks?.links && backlinks.links.length > 3 && (
                        <div className="px-4 py-3 border-t border-gray-200">
                            <div className="flex items-center justify-between">
                                <div className="text-sm text-gray-600">
                                    Showing {backlinks.from} to {backlinks.to} of {backlinks.total} results
                                </div>
                                <div className="flex gap-2">
                                    {backlinks.links.map((link, index) => (
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
        </AppLayout>
    );
}


