import AdminLayout from '../../../Components/Layout/AdminLayout';
import Card from '../../../Components/Shared/Card';
import { Link } from '@inertiajs/react';

export default function UsersIndex({ users, total }) {
    return (
        <AdminLayout header="All Users">
            <div className="space-y-6">
                {/* Stats Card */}
                <Card className="p-6 bg-white border border-gray-200 shadow-md">
                    <div className="flex items-center justify-between">
                        <div>
                            <p className="text-gray-600 text-sm font-medium mb-2 flex items-center gap-2">
                                <span className="text-xl">👥</span>
                                Total Users
                            </p>
                            <p className="text-4xl font-bold text-gray-900 mt-2">{total || 0}</p>
                            <p className="text-gray-500 text-xs mt-2">All registered users in the system</p>
                        </div>
                        <div className="h-20 w-20 rounded-lg bg-gray-100 flex items-center justify-center">
                            <svg className="h-10 w-10 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                    </div>
                </Card>

                {/* Users Table */}
                <Card className="bg-white border border-gray-200 shadow-md">
                    {users?.data && users.data.length > 0 ? (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">👤 Name</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">📧 Email</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">💳 Plan</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">✅ Verified</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">📅 Joined</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {users.data.map((user) => (
                                        <tr key={user.id} className="hover:bg-gray-50 transition-colors">
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="flex items-center">
                                                    <div className="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-700 font-bold text-sm">
                                                        {user.name?.charAt(0).toUpperCase() || 'U'}
                                                    </div>
                                                    <div className="ml-3">
                                                        <div className="text-sm font-medium text-gray-900">{user.name}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="text-sm text-gray-900">{user.email}</div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <span className={`px-2 py-1 text-xs font-medium rounded ${
                                                    user.plan ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                                                }`}>
                                                    {user.plan?.name || 'No Plan'}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <span className={`px-2 py-1 text-xs font-medium rounded ${
                                                    user.email_verified_at ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                                                }`}>
                                                    {user.email_verified_at ? '✅ Yes' : '❌ No'}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                {user.created_at ? new Date(user.created_at).toLocaleDateString() : 'N/A'}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm">
                                                <Link href={`/admin/users/${user.id}`} className="text-gray-600 hover:text-gray-900">
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
                                <span className="text-5xl">👥</span>
                            </div>
                            <p className="text-gray-500 font-medium text-lg">No users found</p>
                        </div>
                    )}

                    {/* Pagination */}
                    {users?.links && users.links.length > 3 && (
                        <div className="px-6 py-4 border-t border-gray-200 bg-gray-50">
                            <div className="flex flex-col sm:flex-row items-center justify-between gap-4">
                                <div className="text-sm text-gray-700">
                                    Showing <span className="font-medium">{users.from || 0}</span> to <span className="font-medium">{users.to || 0}</span> of <span className="font-medium">{users.total || 0}</span> results
                                </div>
                                <div className="flex flex-wrap gap-2">
                                    {users.links.map((link, index) => (
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


