import AdminLayout from '../../../Components/Layout/AdminLayout';
import Card from '../../../Components/Shared/Card';
import { Link, router } from '@inertiajs/react';
import { useState } from 'react';

export default function SubscriptionsIndex({ subscriptions, stats, filters }) {
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [statusFilter, setStatusFilter] = useState(filters.status || 'all');
    const [firstTimeFilter, setFirstTimeFilter] = useState(filters.first_time === 'true');

    const handleFilterChange = (newStatus, newFirstTime) => {
        const params = {
            status: newStatus,
            first_time: newFirstTime ? 'true' : 'false',
            search: searchTerm,
        };
        router.get('/admin/subscriptions', params, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleSearch = (e) => {
        e.preventDefault();
        handleFilterChange(statusFilter, firstTimeFilter);
    };

    const getStatusBadge = (status) => {
        const badges = {
            active: 'bg-green-100 text-green-800 border-green-200',
            canceled: 'bg-red-100 text-red-800 border-red-200',
            pending: 'bg-yellow-100 text-yellow-800 border-yellow-200',
            incomplete: 'bg-yellow-100 text-yellow-800 border-yellow-200',
            trialing: 'bg-blue-100 text-blue-800 border-blue-200',
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
        <AdminLayout header="Subscriptions Management">
            <div className="space-y-6">
                {/* Stats Cards */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
                    <Card className="p-4 bg-white border border-gray-200 shadow-md">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-gray-600 text-xs font-medium mb-1">Total</p>
                                <p className="text-2xl font-bold text-gray-900">{stats.total || 0}</p>
                            </div>
                            <span className="text-2xl">📊</span>
                        </div>
                    </Card>
                    <Card className="p-4 bg-white border border-gray-200 shadow-md">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-gray-600 text-xs font-medium mb-1">Active</p>
                                <p className="text-2xl font-bold text-green-600">{stats.active || 0}</p>
                            </div>
                            <span className="text-2xl">✅</span>
                        </div>
                    </Card>
                    <Card className="p-4 bg-white border border-gray-200 shadow-md">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-gray-600 text-xs font-medium mb-1">Pending</p>
                                <p className="text-2xl font-bold text-yellow-600">{stats.pending || 0}</p>
                            </div>
                            <span className="text-2xl">⏳</span>
                        </div>
                    </Card>
                    <Card className="p-4 bg-white border border-gray-200 shadow-md">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-gray-600 text-xs font-medium mb-1">Canceled</p>
                                <p className="text-2xl font-bold text-red-600">{stats.canceled || 0}</p>
                            </div>
                            <span className="text-2xl">❌</span>
                        </div>
                    </Card>
                    <Card className="p-4 bg-white border border-gray-200 shadow-md">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-gray-600 text-xs font-medium mb-1">Past Due</p>
                                <p className="text-2xl font-bold text-orange-600">{stats.past_due || 0}</p>
                            </div>
                            <span className="text-2xl">⚠️</span>
                        </div>
                    </Card>
                    <Card className="p-4 bg-white border border-gray-200 shadow-md">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-gray-600 text-xs font-medium mb-1">First Time</p>
                                <p className="text-2xl font-bold text-gray-900">{stats.first_time || 0}</p>
                            </div>
                            <span className="text-2xl">🆕</span>
                        </div>
                    </Card>
                </div>

                {/* Filters */}
                <Card className="bg-white border border-gray-200 shadow-md p-6">
                    <div className="flex flex-col md:flex-row gap-4">
                        {/* Search */}
                        <form onSubmit={handleSearch} className="flex-1">
                            <div className="relative">
                                <input
                                    type="text"
                                    value={searchTerm}
                                    onChange={(e) => setSearchTerm(e.target.value)}
                                    placeholder="Search by name or email..."
                                    className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-gray-900"
                                />
                                <span className="absolute left-3 top-2.5 text-gray-400">🔍</span>
                                <button
                                    type="submit"
                                    className="absolute right-2 top-1.5 px-3 py-1 bg-gray-900 text-white text-sm rounded hover:bg-gray-800"
                                >
                                    Search
                                </button>
                            </div>
                        </form>

                        {/* Status Filter */}
                        <div className="flex gap-2">
                            <select
                                value={statusFilter}
                                onChange={(e) => {
                                    setStatusFilter(e.target.value);
                                    handleFilterChange(e.target.value, firstTimeFilter);
                                }}
                                className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-gray-900"
                            >
                                <option value="all">All Status</option>
                                <option value="active">Active</option>
                                <option value="pending">Pending</option>
                                <option value="canceled">Canceled</option>
                                <option value="past_due">Past Due</option>
                            </select>

                            {/* First Time Filter */}
                            <button
                                onClick={() => {
                                    const newFirstTime = !firstTimeFilter;
                                    setFirstTimeFilter(newFirstTime);
                                    handleFilterChange(statusFilter, newFirstTime);
                                }}
                                className={`px-4 py-2 border rounded-lg transition-colors ${
                                    firstTimeFilter
                                        ? 'bg-gray-900 text-white border-gray-900'
                                        : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                                }`}
                            >
                                🆕 First Time Only
                            </button>
                        </div>
                    </div>
                </Card>

                {/* Subscriptions Table */}
                <Card className="bg-white border border-gray-200 shadow-md">
                    {subscriptions?.data && subscriptions.data.length > 0 ? (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Customer</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Plan</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Status</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Tags</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Subscription ID</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Created</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {subscriptions.data.map((subscription) => (
                                        <tr key={subscription.id} className="hover:bg-gray-50 transition-colors">
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="flex items-center">
                                                    <div className="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-700 font-bold text-sm">
                                                        {subscription.name?.charAt(0).toUpperCase() || 'U'}
                                                    </div>
                                                    <div className="ml-3">
                                                        <div className="text-sm font-medium text-gray-900">{subscription.name}</div>
                                                        <div className="text-sm text-gray-500">{subscription.email}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                {subscription.plan ? (
                                                    <div>
                                                        <div className="text-sm font-medium text-gray-900">{subscription.plan.name}</div>
                                                        <div className="text-xs text-gray-500">
                                                            ${subscription.plan.price}/{subscription.plan.billing_interval}
                                                        </div>
                                                    </div>
                                                ) : (
                                                    <span className="text-sm text-gray-400">No Plan</span>
                                                )}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <span className={`px-2 py-1 text-xs font-medium rounded border ${getStatusBadge(subscription.subscription_status)}`}>
                                                    {getStatusLabel(subscription.subscription_status)}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="flex flex-wrap gap-1">
                                                    {subscription.is_first_time && (
                                                        <span className="px-2 py-1 text-xs font-medium rounded bg-gray-900 text-white border border-gray-900">
                                                            🆕 First Time
                                                        </span>
                                                    )}
                                                    {subscription.stripe_customer_id && (
                                                        <span className="px-2 py-1 text-xs font-medium rounded bg-purple-100 text-purple-800 border border-purple-200" title={`Customer ID: ${subscription.stripe_customer_id}`}>
                                                            💳 Stripe
                                                        </span>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="text-xs text-gray-600 font-mono">
                                                    {subscription.stripe_subscription_id ? (
                                                        <span title={subscription.stripe_subscription_id}>
                                                            {subscription.stripe_subscription_id.substring(0, 20)}...
                                                        </span>
                                                    ) : (
                                                        <span className="text-gray-400">N/A</span>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                {subscription.created_at ? new Date(subscription.created_at).toLocaleDateString() : 'N/A'}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm">
                                                <Link
                                                    href={`/admin/subscriptions/${subscription.id}`}
                                                    className="text-gray-900 hover:text-gray-700 font-medium"
                                                >
                                                    View →
                                                </Link>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ) : (
                        <div className="text-center py-16">
                            <div className="inline-block p-6 bg-gray-100 rounded-full mb-4">
                                <span className="text-5xl">💳</span>
                            </div>
                            <p className="text-gray-500 font-medium text-lg">No subscriptions found</p>
                            <p className="text-gray-400 text-sm mt-2">Try adjusting your filters</p>
                        </div>
                    )}

                    {/* Pagination */}
                    {subscriptions?.links && subscriptions.links.length > 3 && (
                        <div className="px-6 py-4 border-t border-gray-200 bg-gray-50">
                            <div className="flex flex-col sm:flex-row items-center justify-between gap-4">
                                <div className="text-sm text-gray-700">
                                    Showing <span className="font-medium">{subscriptions.from || 0}</span> to <span className="font-medium">{subscriptions.to || 0}</span> of <span className="font-medium">{subscriptions.total || 0}</span> results
                                </div>
                                <div className="flex flex-wrap gap-2">
                                    {subscriptions.links.map((link, index) => (
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

