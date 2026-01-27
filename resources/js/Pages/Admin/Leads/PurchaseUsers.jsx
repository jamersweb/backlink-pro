import AdminLayout from '@/Components/Layout/AdminLayout';
import Card from '@/Components/Shared/Card';
import { Link } from '@inertiajs/react';

export default function PurchaseUsers({ users, total }) {
    return (
        <AdminLayout header="Purchase Users">
            <div className="space-y-6">
                {/* Stats Card */}
                <Card className="p-6 bg-white border border-gray-200 shadow-md">
                    <div className="flex items-center justify-between">
                        <div>
                            <p className="text-gray-600 text-sm font-medium mb-2 flex items-center gap-2">
                                <span className="text-xl">💳</span>
                                Total Purchase Users
                            </p>
                            <p className="text-4xl font-bold text-gray-900 mt-2">{total || 0}</p>
                            <p className="text-gray-500 text-xs mt-2">Users with active subscriptions</p>
                        </div>
                        <div className="h-20 w-20 rounded-lg bg-gray-100 flex items-center justify-center">
                            <svg className="h-10 w-10 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                        </div>
                    </div>
                </Card>

                {/* Users Table */}
                <Card className="shadow-xl">
                    {users?.data && users.data.length > 0 ? (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gradient-to-r from-red-50 via-green-50 to-red-50">
                                    <tr>
                                        <th className="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">👤 Name</th>
                                        <th className="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">📧 Email</th>
                                        <th className="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">💳 Plan</th>
                                        <th className="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">📊 Subscription Status</th>
                                        <th className="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">✅ Verified</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {users.data.map((user) => (
                                        <tr key={user.id} className="hover:bg-gradient-to-r hover:from-red-50 hover:to-green-50 transition-all duration-200">
                                            <td className="px-6 py-5 whitespace-nowrap">
                                                <div className="flex items-center">
                                                    <div className="h-12 w-12 rounded-full bg-gradient-to-r from-red-500 to-green-500 flex items-center justify-center text-white font-bold shadow-md">
                                                        {user.name?.charAt(0).toUpperCase() || 'U'}
                                                    </div>
                                                    <div className="ml-4">
                                                        <div className="text-sm font-semibold text-gray-900">{user.name}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-6 py-5 whitespace-nowrap">
                                                <div className="text-sm text-gray-900">{user.email}</div>
                                            </td>
                                            <td className="px-6 py-5 whitespace-nowrap">
                                                <div className="flex flex-col gap-1">
                                                    <span className="px-3 py-1.5 text-xs font-semibold rounded-full bg-green-100 text-green-800 border border-green-200 shadow-sm w-fit">
                                                        {user.plan?.name || 'No Plan'}
                                                    </span>
                                                    {user.plan?.price && (
                                                        <span className="text-xs text-gray-600 font-medium">
                                                            ${user.plan.price}/{user.plan.billing_interval || 'mo'}
                                                        </span>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="px-6 py-5 whitespace-nowrap">
                                                <span className={`px-3 py-1.5 text-xs font-semibold rounded-full shadow-sm border ${
                                                    user.subscription_status === 'active' 
                                                        ? 'bg-green-100 text-green-800 border-green-200'
                                                        : user.subscription_status === 'canceled' || user.subscription_status === 'cancelled'
                                                        ? 'bg-red-100 text-red-800 border-red-200'
                                                        : 'bg-yellow-100 text-yellow-800 border-yellow-200'
                                                }`}>
                                                    {user.subscription_status || 'N/A'}
                                                </span>
                                            </td>
                                            <td className="px-6 py-5 whitespace-nowrap">
                                                <span className={`px-3 py-1.5 text-xs font-semibold rounded-full shadow-sm border ${
                                                    user.email_verified_at 
                                                        ? 'bg-green-100 text-green-800 border-green-200' 
                                                        : 'bg-red-100 text-red-800 border-red-200'
                                                }`}>
                                                    {user.email_verified_at ? '✅ Yes' : '❌ No'}
                                                </span>
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
                            <p className="text-gray-500 font-medium text-lg">No purchase users found</p>
                            <p className="text-gray-400 text-sm mt-2">Users with active subscriptions will appear here</p>
                        </div>
                    )}

                    {/* Pagination */}
                    {users?.links && users.links.length > 3 && (
                        <div className="px-6 py-5 border-t-2 border-gray-200 bg-gradient-to-r from-gray-50 to-white">
                            <div className="flex flex-col sm:flex-row items-center justify-between gap-4">
                                <div className="text-sm text-gray-700 font-medium">
                                    Showing <span className="font-bold text-gray-900">{users.from || 0}</span> to <span className="font-bold text-gray-900">{users.to || 0}</span> of <span className="font-bold text-gray-900">{users.total || 0}</span> results
                                </div>
                                <div className="flex flex-wrap gap-2">
                                    {users.links.map((link, index) => (
                                        <Link
                                            key={index}
                                            href={link.url || '#'}
                                            className={`px-4 py-2 text-sm font-semibold rounded-lg shadow-sm transition-all duration-200 ${
                                                link.active
                                                    ? 'bg-gradient-to-r from-red-600 to-green-600 text-white shadow-md scale-105'
                                                    : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200 hover:border-gray-300'
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

