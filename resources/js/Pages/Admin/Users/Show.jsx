import AdminLayout from '@/Components/Layout/AdminLayout';
import Card from '@/Components/Shared/Card';
import Button from '@/Components/Shared/Button';
import { Link } from '@inertiajs/react';

export default function AdminUsersShow({ user, stats, recentBacklinks }) {
    return (
        <AdminLayout header={`User: ${user.name}`}>
            <div className="space-y-6">
                {/* Action Buttons */}
                <div className="flex items-center gap-4">
                    <Link href="/admin/users">
                        <Button variant="secondary">← Back to Users</Button>
                    </Link>
                    <Link href={`/admin/users/${user.id}/edit`}>
                        <Button variant="primary">✏️ Edit User</Button>
                    </Link>
                </div>

                {/* User Info */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <Card className="bg-white border border-gray-200 shadow-md">
                        <h3 className="text-lg font-bold text-gray-900 mb-4">User Details</h3>
                        <div className="space-y-3">
                            <div>
                                <p className="text-sm text-gray-600">Name</p>
                                <p className="text-base font-semibold text-gray-900">{user.name}</p>
                            </div>
                            <div>
                                <p className="text-sm text-gray-600">Email</p>
                                <p className="text-base font-semibold text-gray-900">{user.email}</p>
                            </div>
                            <div>
                                <p className="text-sm text-gray-600">Email Verified</p>
                                <span className={`inline-block px-3 py-1 text-sm font-medium rounded-full ${
                                    user.email_verified_at ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                                }`}>
                                    {user.email_verified_at ? '✅ Verified' : '❌ Not Verified'}
                                </span>
                            </div>
                            <div>
                                <p className="text-sm text-gray-600">Role</p>
                                <span className="inline-block px-3 py-1 text-sm font-medium rounded-full bg-blue-100 text-blue-800">
                                    {user.roles?.[0]?.name || 'User'}
                                </span>
                            </div>
                            <div>
                                <p className="text-sm text-gray-600">Joined</p>
                                <p className="text-base font-semibold text-gray-900">
                                    {new Date(user.created_at).toLocaleDateString()}
                                </p>
                            </div>
                        </div>
                    </Card>

                    <Card className="bg-white border border-gray-200 shadow-md">
                        <h3 className="text-lg font-bold text-gray-900 mb-4">Subscription</h3>
                        <div className="space-y-3">
                            <div>
                                <p className="text-sm text-gray-600">Plan</p>
                                <p className="text-base font-semibold text-gray-900">
                                    {user.plan ? (
                                        <Link href={`/admin/plans/${user.plan.id}`} className="text-blue-600 hover:text-blue-900">
                                            {user.plan.name} (${user.plan.price}/{user.plan.billing_interval})
                                        </Link>
                                    ) : 'No Plan'}
                                </p>
                            </div>
                            <div>
                                <p className="text-sm text-gray-600">Subscription Status</p>
                                <span className={`inline-block px-3 py-1 text-sm font-medium rounded-full ${
                                    user.subscription_status === 'active' ? 'bg-green-100 text-green-800' :
                                    user.subscription_status === 'cancelled' ? 'bg-red-100 text-red-800' :
                                    'bg-gray-100 text-gray-800'
                                }`}>
                                    {user.subscription_status || 'None'}
                                </span>
                            </div>
                            {user.stripe_customer_id && (
                                <div>
                                    <p className="text-sm text-gray-600">Stripe Customer ID</p>
                                    <p className="text-base font-semibold text-gray-900 font-mono text-sm">{user.stripe_customer_id}</p>
                                </div>
                            )}
                            {user.trial_ends_at && (
                                <div>
                                    <p className="text-sm text-gray-600">Trial Ends</p>
                                    <p className="text-base font-semibold text-gray-900">
                                        {new Date(user.trial_ends_at).toLocaleDateString()}
                                    </p>
                                </div>
                            )}
                        </div>
                    </Card>
                </div>

                {/* Statistics */}
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-6">
                    <Card className="bg-white border border-gray-200 shadow-md">
                        <div className="p-4">
                            <p className="text-gray-600 text-xs font-medium mb-1">Campaigns</p>
                            <p className="text-2xl font-bold text-gray-900">{stats?.total_campaigns || 0}</p>
                            <p className="text-gray-500 text-xs mt-1">{stats?.active_campaigns || 0} active</p>
                        </div>
                    </Card>
                    <Card className="bg-white border border-gray-200 shadow-md">
                        <div className="p-4">
                            <p className="text-gray-600 text-xs font-medium mb-1">Backlinks</p>
                            <p className="text-2xl font-bold text-gray-900">{stats?.total_backlinks || 0}</p>
                            <p className="text-green-600 text-xs mt-1">{stats?.verified_backlinks || 0} verified</p>
                        </div>
                    </Card>
                    <Card className="bg-white border border-gray-200 shadow-md">
                        <div className="p-4">
                            <p className="text-gray-600 text-xs font-medium mb-1">Domains</p>
                            <p className="text-2xl font-bold text-gray-900">{stats?.total_domains || 0}</p>
                        </div>
                    </Card>
                    <Card className="bg-white border border-gray-200 shadow-md">
                        <div className="p-4">
                            <p className="text-gray-600 text-xs font-medium mb-1">Gmail Accounts</p>
                            <p className="text-2xl font-bold text-gray-900">{stats?.connected_gmail_accounts || 0}</p>
                        </div>
                    </Card>
                </div>

                {/* Recent Campaigns */}
                {user.campaigns && user.campaigns.length > 0 && (
                    <Card className="bg-white border border-gray-200 shadow-md">
                        <div className="flex items-center justify-between mb-4">
                            <h3 className="text-lg font-bold text-gray-900">Recent Campaigns</h3>
                            <Link href={`/admin/campaigns?user_id=${user.id}`}>
                                <Button variant="secondary" size="sm">View All</Button>
                            </Link>
                        </div>
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Name</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Status</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Created</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {user.campaigns.map((campaign) => (
                                        <tr key={campaign.id}>
                                            <td className="px-4 py-3 text-sm text-gray-900">{campaign.name || 'Untitled'}</td>
                                            <td className="px-4 py-3 text-sm">
                                                <span className={`px-2 py-1 text-xs font-medium rounded-full ${
                                                    campaign.status === 'active' ? 'bg-green-100 text-green-800' :
                                                    campaign.status === 'paused' ? 'bg-yellow-100 text-yellow-800' :
                                                    'bg-gray-100 text-gray-800'
                                                }`}>
                                                    {campaign.status}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-600">
                                                {new Date(campaign.created_at).toLocaleDateString()}
                                            </td>
                                            <td className="px-4 py-3 text-sm">
                                                <Link href={`/admin/campaigns/${campaign.id}`} className="text-blue-600 hover:text-blue-900">
                                                    View →
                                                </Link>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </Card>
                )}

                {/* Recent Backlinks */}
                {recentBacklinks && recentBacklinks.length > 0 && (
                    <Card className="bg-white border border-gray-200 shadow-md">
                        <div className="flex items-center justify-between mb-4">
                            <h3 className="text-lg font-bold text-gray-900">Recent Backlinks</h3>
                            <Link href={`/admin/backlinks?user_id=${user.id}`}>
                                <Button variant="secondary" size="sm">View All</Button>
                            </Link>
                        </div>
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">URL</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Campaign</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Type</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Status</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Created</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {recentBacklinks.map((backlink) => (
                                        <tr key={backlink.id}>
                                            <td className="px-4 py-3 text-sm">
                                                <a href={backlink.url} target="_blank" rel="noopener noreferrer" className="text-blue-600 hover:text-blue-900 break-all max-w-xs truncate block">
                                                    {backlink.url}
                                                </a>
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-900">{backlink.campaign?.name || 'N/A'}</td>
                                            <td className="px-4 py-3 text-sm text-gray-600 capitalize">{backlink.type}</td>
                                            <td className="px-4 py-3 text-sm">
                                                <span className={`px-2 py-1 text-xs font-medium rounded-full ${
                                                    backlink.status === 'verified' ? 'bg-green-100 text-green-800' :
                                                    backlink.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                                                    'bg-red-100 text-red-800'
                                                }`}>
                                                    {backlink.status}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-600">
                                                {new Date(backlink.created_at).toLocaleDateString()}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </Card>
                )}

                {/* Connected Accounts */}
                {user.connected_accounts && user.connected_accounts.length > 0 && (
                    <Card className="bg-white border border-gray-200 shadow-md">
                        <h3 className="text-lg font-bold text-gray-900 mb-4">Connected Gmail Accounts</h3>
                        <div className="space-y-2">
                            {user.connected_accounts.map((account) => (
                                <div key={account.id} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div>
                                        <p className="text-sm font-medium text-gray-900">{account.email}</p>
                                        <p className="text-xs text-gray-500">Status: {account.status}</p>
                                    </div>
                                    <span className={`px-2 py-1 text-xs font-medium rounded-full ${
                                        account.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                                    }`}>
                                        {account.status}
                                    </span>
                                </div>
                            ))}
                        </div>
                    </Card>
                )}
            </div>
        </AdminLayout>
    );
}

