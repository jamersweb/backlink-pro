import AdminLayout from '@/Components/Layout/AdminLayout';
import Card from '@/Components/Shared/Card';
import { Link } from '@inertiajs/react';

export default function SubscriptionShow({ subscription, campaigns, campaigns_count }) {
    const getStatusBadge = (status) => {
        const badges = {
            active: 'bg-green-100 text-green-800 border-green-200',
            canceled: 'bg-red-100 text-red-800 border-red-200',
            incomplete: 'bg-yellow-100 text-yellow-800 border-yellow-200',
            trialing: 'bg-gray-900 text-white border-gray-900',
            past_due: 'bg-orange-100 text-orange-800 border-orange-200',
        };
        return badges[status] || 'bg-gray-100 text-gray-800 border-gray-200';
    };

    const getStatusLabel = (status) => {
        const labels = {
            active: 'Active',
            canceled: 'Canceled',
            incomplete: 'Pending',
            trialing: 'Trialing',
            past_due: 'Past Due',
        };
        return labels[status] || status || 'N/A';
    };

    return (
        <AdminLayout header="Subscription Details">
            <div className="space-y-6">
                {/* Back Button */}
                <Link
                    href="/admin/subscriptions"
                    className="inline-flex items-center text-gray-900 hover:text-gray-700 font-medium"
                >
                    ← Back to Subscriptions
                </Link>

                {/* Customer Info Card */}
                <Card className="bg-white border border-gray-200 shadow-md">
                    <div className="p-6">
                        <div className="flex items-start justify-between mb-6">
                            <div className="flex items-center">
                                <div className="h-16 w-16 rounded-full bg-gray-200 flex items-center justify-center text-gray-700 font-bold text-2xl">
                                    {subscription.name?.charAt(0).toUpperCase() || 'U'}
                                </div>
                                <div className="ml-4">
                                    <h2 className="text-2xl font-bold text-gray-900">{subscription.name}</h2>
                                    <p className="text-gray-600">{subscription.email}</p>
                                </div>
                            </div>
                            <div className="flex flex-col items-end gap-2">
                                <span className={`px-3 py-1 text-sm font-medium rounded border ${getStatusBadge(subscription.subscription_status)}`}>
                                    {getStatusLabel(subscription.subscription_status)}
                                </span>
                                {subscription.is_first_time && (
                                    <span className="px-3 py-1 text-xs font-medium rounded bg-gray-900 text-white border border-gray-900">
                                        🆕 First Time Purchase
                                    </span>
                                )}
                            </div>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6 pt-6 border-t border-gray-200">
                            <div>
                                <h3 className="text-sm font-semibold text-gray-700 mb-4">Subscription Details</h3>
                                <dl className="space-y-3">
                                    <div>
                                        <dt className="text-sm text-gray-500">Plan</dt>
                                        <dd className="text-sm font-medium text-gray-900">
                                            {subscription.plan?.name || 'No Plan'}
                                        </dd>
                                    </div>
                                    {subscription.plan && (
                                        <>
                                            <div>
                                                <dt className="text-sm text-gray-500">Price</dt>
                                                <dd className="text-sm font-medium text-gray-900">
                                                    ${subscription.plan.price}/{subscription.plan.billing_interval}
                                                </dd>
                                            </div>
                                            {subscription.plan.description && (
                                                <div>
                                                    <dt className="text-sm text-gray-500">Description</dt>
                                                    <dd className="text-sm text-gray-900">{subscription.plan.description}</dd>
                                                </div>
                                            )}
                                        </>
                                    )}
                                </dl>
                            </div>

                            <div>
                                <h3 className="text-sm font-semibold text-gray-700 mb-4">Stripe Information</h3>
                                <dl className="space-y-3">
                                    {subscription.stripe_customer_id && (
                                        <div>
                                            <dt className="text-sm text-gray-500">Customer ID</dt>
                                            <dd className="text-sm font-mono text-gray-900 break-all">
                                                {subscription.stripe_customer_id}
                                            </dd>
                                        </div>
                                    )}
                                    {subscription.stripe_subscription_id && (
                                        <div>
                                            <dt className="text-sm text-gray-500">Subscription ID</dt>
                                            <dd className="text-sm font-mono text-gray-900 break-all">
                                                {subscription.stripe_subscription_id}
                                            </dd>
                                        </div>
                                    )}
                                    {subscription.trial_ends_at && (
                                        <div>
                                            <dt className="text-sm text-gray-500">Trial Ends</dt>
                                            <dd className="text-sm text-gray-900">
                                                {new Date(subscription.trial_ends_at).toLocaleString()}
                                            </dd>
                                        </div>
                                    )}
                                </dl>
                            </div>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6 pt-6 border-t border-gray-200">
                            <div>
                                <h3 className="text-sm font-semibold text-gray-700 mb-4">Account Information</h3>
                                <dl className="space-y-3">
                                    <div>
                                        <dt className="text-sm text-gray-500">Email Verified</dt>
                                        <dd className="text-sm font-medium text-gray-900">
                                            {subscription.email_verified_at ? (
                                                <span className="text-green-600">✅ Verified</span>
                                            ) : (
                                                <span className="text-red-600">❌ Not Verified</span>
                                            )}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-sm text-gray-500">Created</dt>
                                        <dd className="text-sm text-gray-900">
                                            {subscription.created_at ? new Date(subscription.created_at).toLocaleString() : 'N/A'}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-sm text-gray-500">Last Updated</dt>
                                        <dd className="text-sm text-gray-900">
                                            {subscription.updated_at ? new Date(subscription.updated_at).toLocaleString() : 'N/A'}
                                        </dd>
                                    </div>
                                </dl>
                            </div>

                            <div>
                                <h3 className="text-sm font-semibold text-gray-700 mb-4">Usage Statistics</h3>
                                <dl className="space-y-3">
                                    <div>
                                        <dt className="text-sm text-gray-500">Total Campaigns</dt>
                                        <dd className="text-sm font-medium text-gray-900">{campaigns_count || 0}</dd>
                                    </div>
                                </dl>
                            </div>
                        </div>
                    </div>
                </Card>

                {/* Recent Campaigns */}
                {campaigns && campaigns.length > 0 && (
                    <Card className="bg-white border border-gray-200 shadow-md">
                        <div className="p-6">
                            <h3 className="text-lg font-semibold text-gray-900 mb-4">Recent Campaigns</h3>
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Campaign Name</th>
                                            <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Status</th>
                                            <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Created</th>
                                            <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                        {campaigns.map((campaign) => (
                                            <tr key={campaign.id} className="hover:bg-gray-50">
                                                <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                    {campaign.name}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <span className={`px-2 py-1 text-xs font-medium rounded ${
                                                        campaign.status === 'active' ? 'bg-green-100 text-green-800' :
                                                        campaign.status === 'paused' ? 'bg-yellow-100 text-yellow-800' :
                                                        'bg-gray-100 text-gray-800'
                                                    }`}>
                                                        {campaign.status}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                    {campaign.created_at ? new Date(campaign.created_at).toLocaleDateString() : 'N/A'}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm">
                                                    <Link
                                                        href={`/admin/campaigns/${campaign.id}`}
                                                        className="text-gray-900 hover:text-gray-700"
                                                    >
                                                        View →
                                                    </Link>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </Card>
                )}

                {/* View User Profile Link */}
                <div className="flex justify-end">
                    <Link
                        href={`/admin/users/${subscription.id}`}
                        className="px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 transition-colors"
                    >
                        View Full User Profile →
                    </Link>
                </div>
            </div>
        </AdminLayout>
    );
}

