import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '../../Components/Layout/AppLayout';
import Card from '../../Components/Shared/Card';
import Button from '../../Components/Shared/Button';

export default function SiteAccountsIndex({ siteAccounts, campaigns, filters }) {
    const [campaignFilter, setCampaignFilter] = useState(filters?.campaign_id || '');
    const [statusFilter, setStatusFilter] = useState(filters?.status || '');

    const handleFilter = () => {
        router.get('/site-accounts', {
            campaign_id: campaignFilter || undefined,
            status: statusFilter || undefined,
        }, {
            preserveState: true,
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
            <span className={`px-2 py-1 text-xs font-semibold rounded-full ${colors[status] || colors.created}`}>
                {status?.replace('_', ' ')}
            </span>
        );
    };

    return (
        <AppLayout header="Site Account Management">
            <div className="space-y-6">
                <div className="flex justify-between items-center">
                    <h1 className="text-2xl font-bold text-gray-900">Site Accounts</h1>
                    <Link href="/site-accounts/create">
                        <Button variant="primary">Add Site Account</Button>
                    </Link>
                </div>

                {/* Filters */}
                <Card title="Filters">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Campaign</label>
                            <select
                                value={campaignFilter}
                                onChange={(e) => setCampaignFilter(e.target.value)}
                                className="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            >
                                <option value="">All Campaigns</option>
                                {campaigns?.map((campaign) => (
                                    <option key={campaign.id} value={campaign.id}>
                                        {campaign.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select
                                value={statusFilter}
                                onChange={(e) => setStatusFilter(e.target.value)}
                                className="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            >
                                <option value="">All Statuses</option>
                                <option value="created">Created</option>
                                <option value="waiting_email">Waiting Email</option>
                                <option value="verified">Verified</option>
                                <option value="failed">Failed</option>
                            </select>
                        </div>
                        <div className="flex items-end">
                            <Button variant="primary" onClick={handleFilter} className="w-full">
                                Apply Filters
                            </Button>
                        </div>
                    </div>
                </Card>

                {/* Site Accounts List */}
                {siteAccounts.data && siteAccounts.data.length > 0 ? (
                    <div className="grid grid-cols-1 gap-6">
                        {siteAccounts.data.map((account) => (
                            <Card key={account.id}>
                                <div className="flex items-center justify-between">
                                    <div className="flex-1">
                                        <div className="flex items-center gap-3 mb-2">
                                            <h3 className="text-lg font-semibold text-gray-900">{account.site_domain}</h3>
                                            {getStatusBadge(account.status)}
                                        </div>
                                        <div className="text-sm text-gray-600 space-y-1">
                                            <p><strong>Email:</strong> {account.login_email}</p>
                                            <p><strong>Username:</strong> {account.username || 'N/A'}</p>
                                            <p><strong>Campaign:</strong> {account.campaign?.name || 'N/A'}</p>
                                            <p><strong>Backlinks:</strong> {account.backlinks_count || 0}</p>
                                        </div>
                                    </div>
                                    <div className="flex gap-2">
                                        <Link href={`/site-accounts/${account.id}/edit`}>
                                            <Button variant="outline">Edit</Button>
                                        </Link>
                                        <Button variant="outline" onClick={() => handleDelete(account.id)}>
                                            Delete
                                        </Button>
                                    </div>
                                </div>
                            </Card>
                        ))}
                    </div>
                ) : (
                    <Card>
                        <div className="text-center py-12">
                            <p className="text-gray-500">No site accounts found.</p>
                        </div>
                    </Card>
                )}

                {/* Pagination */}
                {siteAccounts.links && siteAccounts.links.length > 3 && (
                    <div className="flex justify-center">
                        <div className="flex gap-2">
                            {siteAccounts.links.map((link, index) => (
                                <Link
                                    key={index}
                                    href={link.url || '#'}
                                    className={`px-3 py-2 rounded-md text-sm ${
                                        link.active
                                            ? 'bg-indigo-600 text-white'
                                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                    } ${!link.url ? 'opacity-50 cursor-not-allowed' : ''}`}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

