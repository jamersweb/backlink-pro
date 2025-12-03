import { useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import AppLayout from '../../Components/Layout/AppLayout';
import Card from '../../Components/Shared/Card';
import Button from '../../Components/Shared/Button';
import Input from '../../Components/Shared/Input';

export default function SiteAccountsIndex({ siteAccounts, campaigns, filters }) {
    const { flash } = usePage().props;
    const [localFilters, setLocalFilters] = useState(filters || {
        campaign_id: '',
        status: '',
    });

    const handleFilterChange = (key, value) => {
        const newFilters = { ...localFilters, [key]: value };
        setLocalFilters(newFilters);
        router.get('/site-accounts', newFilters, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleDelete = (id) => {
        if (confirm('Are you sure you want to delete this site account?')) {
            router.delete(`/site-accounts/${id}`);
        }
    };

    const getStatusBadge = (status) => {
        const colors = {
            verified: 'bg-green-100 text-green-800',
            created: 'bg-blue-100 text-blue-800',
            waiting_email: 'bg-yellow-100 text-yellow-800',
            failed: 'bg-red-100 text-red-800',
        };
        return (
            <span className={`px-2 py-1 text-xs font-medium rounded-full ${colors[status] || colors.created}`}>
                {status.replace('_', ' ')}
            </span>
        );
    };

    const getEmailStatusBadge = (status) => {
        if (!status) return null;
        const colors = {
            pending: 'bg-yellow-100 text-yellow-800',
            found: 'bg-green-100 text-green-800',
            timeout: 'bg-red-100 text-red-800',
        };
        return (
            <span className={`px-2 py-1 text-xs font-medium rounded-full ${colors[status] || colors.pending}`}>
                Email: {status}
            </span>
        );
    };

    // Calculate statistics
    const stats = {
        total: siteAccounts?.total || 0,
        verified: siteAccounts?.data?.filter(acc => acc.status === 'verified').length || 0,
        waiting_email: siteAccounts?.data?.filter(acc => acc.status === 'waiting_email').length || 0,
        failed: siteAccounts?.data?.filter(acc => acc.status === 'failed').length || 0,
    };

    return (
        <AppLayout header="Site Accounts Management">
            <div className="space-y-6">
                {/* Flash Messages */}
                {flash?.success && (
                    <div className="p-4 bg-green-50 border border-green-200 rounded-md">
                        <p className="text-sm text-green-800">{flash.success}</p>
                    </div>
                )}

                {/* Statistics */}
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Card className="bg-white border border-gray-200 shadow-md">
                        <div className="p-4">
                            <p className="text-gray-600 text-xs font-medium mb-1">Total Accounts</p>
                            <p className="text-2xl font-bold text-gray-900">{stats.total}</p>
                        </div>
                    </Card>
                    <Card className="bg-white border border-green-200 shadow-md">
                        <div className="p-4">
                            <p className="text-green-600 text-xs font-medium mb-1">Verified</p>
                            <p className="text-2xl font-bold text-green-900">{stats.verified}</p>
                        </div>
                    </Card>
                    <Card className="bg-white border border-yellow-200 shadow-md">
                        <div className="p-4">
                            <p className="text-yellow-600 text-xs font-medium mb-1">Waiting Email</p>
                            <p className="text-2xl font-bold text-yellow-900">{stats.waiting_email}</p>
                        </div>
                    </Card>
                    <Card className="bg-white border border-red-200 shadow-md">
                        <div className="p-4">
                            <p className="text-red-600 text-xs font-medium mb-1">Failed</p>
                            <p className="text-2xl font-bold text-red-900">{stats.failed}</p>
                        </div>
                    </Card>
                </div>

                {/* Filters */}
                <Card className="bg-white border border-gray-200 shadow-md">
                    <div className="flex justify-between items-center mb-4">
                        <h3 className="text-lg font-bold text-gray-900">Filters</h3>
                        <Link href="/site-accounts/create">
                            <Button variant="primary">➕ Add Site Account</Button>
                        </Link>
                    </div>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
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
                                <option value="created">Created</option>
                                <option value="waiting_email">Waiting Email</option>
                                <option value="failed">Failed</option>
                            </select>
                        </div>
                        <div className="flex items-end">
                            <Button variant="secondary" onClick={() => {
                                const emptyFilters = { campaign_id: '', status: '' };
                                setLocalFilters(emptyFilters);
                                router.get('/site-accounts', emptyFilters);
                            }}>
                                Clear Filters
                            </Button>
                        </div>
                    </div>
                </Card>

                {/* Site Accounts Table */}
                <Card className="bg-white border border-gray-200 shadow-md">
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Site Domain</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Campaign</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Login Email</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Username</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Status</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Backlinks</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Created</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                                {siteAccounts?.data && siteAccounts.data.length > 0 ? (
                                    siteAccounts.data.map((account) => (
                                        <tr key={account.id}>
                                            <td className="px-4 py-3 text-sm font-medium text-gray-900">
                                                {account.site_domain}
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-600">
                                                <Link href={`/campaign/${account.campaign?.id}`} className="text-blue-600 hover:text-blue-900">
                                                    {account.campaign?.name || 'N/A'}
                                                </Link>
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-600">
                                                {account.login_email}
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-600">
                                                {account.username || '-'}
                                            </td>
                                            <td className="px-4 py-3 text-sm">
                                                <div className="flex flex-col gap-1">
                                                    {getStatusBadge(account.status)}
                                                    {getEmailStatusBadge(account.email_verification_status)}
                                                </div>
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-600">
                                                {account.backlinks_count || 0}
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-600">
                                                {new Date(account.created_at).toLocaleDateString()}
                                            </td>
                                            <td className="px-4 py-3 text-sm">
                                                <div className="flex gap-2">
                                                    <Link href={`/site-accounts/${account.id}/edit`}>
                                                        <Button variant="outline" size="sm">Edit</Button>
                                                    </Link>
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() => handleDelete(account.id)}
                                                    >
                                                        Delete
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan="8" className="px-4 py-8 text-center text-gray-500">
                                            No site accounts found. {localFilters.campaign_id || localFilters.status ? 'Try adjusting your filters.' : 'Create a site account to get started.'}
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {siteAccounts?.links && siteAccounts.links.length > 3 && (
                        <div className="px-4 py-3 border-t border-gray-200">
                            <div className="flex items-center justify-between">
                                <div className="text-sm text-gray-600">
                                    Showing {siteAccounts.from} to {siteAccounts.to} of {siteAccounts.total} results
                                </div>
                                <div className="flex gap-2">
                                    {siteAccounts.links.map((link, index) => (
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
