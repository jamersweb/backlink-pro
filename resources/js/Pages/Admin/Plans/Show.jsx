import AdminLayout from '@/Components/Layout/AdminLayout';
import Card from '@/Components/Shared/Card';
import Button from '@/Components/Shared/Button';
import { Link, router } from '@inertiajs/react';

export default function AdminPlansShow({ plan, stats }) {
    const handleDelete = () => {
        if (window.confirm(`Are you sure you want to delete "${plan.name}"? This action cannot be undone.`)) {
            router.delete(`/admin/plans/${plan.id}`);
        }
    };

    return (
        <AdminLayout header={`Plan: ${plan.name}`}>
            <div className="space-y-6">
                {/* Action Buttons */}
                <div className="flex items-center gap-4">
                    <Link href="/admin/plans">
                        <Button variant="secondary">← Back to Plans</Button>
                    </Link>
                    <Link href={`/admin/plans/${plan.id}/edit`}>
                        <Button variant="primary">✏️ Edit Plan</Button>
                    </Link>
                    <Button variant="danger" onClick={handleDelete}>🗑️ Delete Plan</Button>
                </div>

                {/* Plan Info */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <Card className="bg-white border border-gray-200 shadow-md">
                        <h3 className="text-lg font-bold text-gray-900 mb-4">Plan Details</h3>
                        <div className="space-y-3">
                            <div>
                                <p className="text-sm text-gray-600">Name</p>
                                <p className="text-base font-semibold text-gray-900">{plan.name}</p>
                            </div>
                            <div>
                                <p className="text-sm text-gray-600">Slug</p>
                                <p className="text-base font-semibold text-gray-900 font-mono">{plan.slug}</p>
                            </div>
                            <div>
                                <p className="text-sm text-gray-600">Description</p>
                                <p className="text-base font-semibold text-gray-900">{plan.description || 'No description'}</p>
                            </div>
                            <div>
                                <p className="text-sm text-gray-600">Price</p>
                                <p className="text-base font-semibold text-gray-900">
                                    ${plan.price} / {plan.billing_interval}
                                </p>
                            </div>
                            <div>
                                <p className="text-sm text-gray-600">Status</p>
                                <span className={`inline-block px-3 py-1 text-sm font-medium rounded-full ${
                                    plan.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                                }`}>
                                    {plan.is_active ? 'Active' : 'Inactive'}
                                </span>
                            </div>
                        </div>
                    </Card>

                    <Card className="bg-white border border-gray-200 shadow-md">
                        <h3 className="text-lg font-bold text-gray-900 mb-4">Limits</h3>
                        <div className="space-y-3">
                            <div>
                                <p className="text-sm text-gray-600">Max Domains</p>
                                <p className="text-base font-semibold text-gray-900">
                                    {plan.max_domains === -1 ? 'Unlimited' : plan.max_domains}
                                </p>
                            </div>
                            <div>
                                <p className="text-sm text-gray-600">Max Campaigns</p>
                                <p className="text-base font-semibold text-gray-900">
                                    {plan.max_campaigns === -1 ? 'Unlimited' : plan.max_campaigns}
                                </p>
                            </div>
                            <div>
                                <p className="text-sm text-gray-600">Daily Backlink Limit</p>
                                <p className="text-base font-semibold text-gray-900">
                                    {plan.daily_backlink_limit === -1 ? 'Unlimited' : plan.daily_backlink_limit}
                                </p>
                            </div>
                            <div>
                                <p className="text-sm text-gray-600">Backlink Types</p>
                                <div className="flex flex-wrap gap-2 mt-1">
                                    {plan.backlink_types && plan.backlink_types.length > 0 ? (
                                        plan.backlink_types.map((type) => (
                                            <span key={type} className="px-2 py-1 text-xs font-medium rounded bg-blue-100 text-blue-800 capitalize">
                                                {type}
                                            </span>
                                        ))
                                    ) : (
                                        <span className="text-sm text-gray-500">None</span>
                                    )}
                                </div>
                            </div>
                        </div>
                    </Card>
                </div>

                {/* Statistics */}
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Card className="bg-white border border-gray-200 shadow-md">
                        <div className="p-4">
                            <p className="text-gray-600 text-xs font-medium mb-1">Total Subscribers</p>
                            <p className="text-3xl font-bold text-gray-900">{stats?.total_subscribers || 0}</p>
                        </div>
                    </Card>
                    <Card className="bg-white border border-green-200 shadow-md">
                        <div className="p-4">
                            <p className="text-green-600 text-xs font-medium mb-1">Active Subscribers</p>
                            <p className="text-3xl font-bold text-green-900">{stats?.active_subscribers || 0}</p>
                        </div>
                    </Card>
                    <Card className="bg-white border border-gray-200 shadow-md">
                        <div className="p-4">
                            <p className="text-gray-600 text-xs font-medium mb-1">Total Campaigns</p>
                            <p className="text-3xl font-bold text-gray-900">{stats?.total_campaigns || 0}</p>
                        </div>
                    </Card>
                    <Card className="bg-white border border-gray-200 shadow-md">
                        <div className="p-4">
                            <p className="text-gray-600 text-xs font-medium mb-1">Total Backlinks</p>
                            <p className="text-3xl font-bold text-gray-900">{stats?.total_backlinks || 0}</p>
                        </div>
                    </Card>
                </div>

                {/* Features */}
                {plan.features && plan.features.length > 0 && (
                    <Card className="bg-white border border-gray-200 shadow-md">
                        <h3 className="text-lg font-bold text-gray-900 mb-4">Features</h3>
                        <ul className="space-y-2">
                            {plan.features.map((feature, index) => (
                                <li key={index} className="flex items-start gap-2">
                                    <span className="text-green-500 mt-0.5">✓</span>
                                    <span className="text-sm text-gray-700">{feature}</span>
                                </li>
                            ))}
                        </ul>
                    </Card>
                )}

                {/* Subscribers */}
                {plan.users && plan.users.length > 0 && (
                    <Card className="bg-white border border-gray-200 shadow-md">
                        <div className="flex items-center justify-between mb-4">
                            <h3 className="text-lg font-bold text-gray-900">Recent Subscribers</h3>
                            <Link href={`/admin/users?plan_id=${plan.id}`}>
                                <Button variant="secondary" size="sm">View All</Button>
                            </Link>
                        </div>
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Name</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Email</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Status</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Joined</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {plan.users.map((user) => (
                                        <tr key={user.id}>
                                            <td className="px-4 py-3 text-sm text-gray-900">{user.name}</td>
                                            <td className="px-4 py-3 text-sm text-gray-600">{user.email}</td>
                                            <td className="px-4 py-3 text-sm">
                                                <span className={`px-2 py-1 text-xs font-medium rounded-full ${
                                                    user.subscription_status === 'active' ? 'bg-green-100 text-green-800' :
                                                    user.subscription_status === 'cancelled' ? 'bg-red-100 text-red-800' :
                                                    'bg-gray-100 text-gray-800'
                                                }`}>
                                                    {user.subscription_status || 'None'}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-600">
                                                {new Date(user.created_at).toLocaleDateString()}
                                            </td>
                                            <td className="px-4 py-3 text-sm">
                                                <Link href={`/admin/users/${user.id}`} className="text-blue-600 hover:text-blue-900">
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
            </div>
        </AdminLayout>
    );
}

