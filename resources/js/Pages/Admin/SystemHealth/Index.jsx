import AdminLayout from '../../../Components/Layout/AdminLayout';
import Card from '../../../Components/Shared/Card';
import Button from '../../../Components/Shared/Button';
import { router, usePage } from '@inertiajs/react';

export default function AdminSystemHealthIndex({ stats, automationStats, recentFailedJobs }) {
    const { flash } = usePage().props;

    const handleRetryJob = (jobId) => {
        router.post(`/admin/system-health/failed-jobs/${jobId}/retry`, {}, {
            preserveScroll: true,
        });
    };

    const handleFlushJobs = () => {
        if (window.confirm('Are you sure you want to flush all failed jobs? This action cannot be undone.')) {
            router.post('/admin/system-health/failed-jobs/flush', {}, {
                preserveScroll: true,
            });
        }
    };

    return (
        <AdminLayout header="System Health">
            <div className="space-y-6">
                {/* Success/Error Messages */}
                {flash?.success && (
                    <div className="p-4 bg-green-50 border border-green-200 rounded-md">
                        <p className="text-sm text-green-800">{flash.success}</p>
                    </div>
                )}
                {flash?.error && (
                    <div className="p-4 bg-red-50 border border-red-200 rounded-md">
                        <p className="text-sm text-red-800">{flash.error}</p>
                    </div>
                )}

                {/* Connection Status */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <Card className="bg-white border border-gray-200 shadow-md">
                        <h3 className="text-lg font-bold text-gray-900 mb-4">Database Connection</h3>
                        <div className="space-y-3">
                            <div className="flex items-center justify-between">
                                <span className="text-sm text-gray-600">Status</span>
                                <span className={`px-3 py-1 text-sm font-medium rounded-full ${
                                    stats?.db_status === 'connected' 
                                        ? 'bg-green-100 text-green-800' 
                                        : 'bg-red-100 text-red-800'
                                }`}>
                                    {stats?.db_status === 'connected' ? '✅ Connected' : '❌ Disconnected'}
                                </span>
                            </div>
                            {stats?.db_latency && (
                                <div className="flex items-center justify-between">
                                    <span className="text-sm text-gray-600">Latency</span>
                                    <span className="text-sm font-semibold text-gray-900">{stats.db_latency} ms</span>
                                </div>
                            )}
                        </div>
                    </Card>

                    <Card className="bg-white border border-gray-200 shadow-md">
                        <h3 className="text-lg font-bold text-gray-900 mb-4">Redis Connection</h3>
                        <div className="space-y-3">
                            <div className="flex items-center justify-between">
                                <span className="text-sm text-gray-600">Status</span>
                                <span className={`px-3 py-1 text-sm font-medium rounded-full ${
                                    stats?.redis_status === 'connected' 
                                        ? 'bg-green-100 text-green-800' 
                                        : 'bg-red-100 text-red-800'
                                }`}>
                                    {stats?.redis_status === 'connected' ? '✅ Connected' : '❌ Disconnected'}
                                </span>
                            </div>
                            {stats?.redis_latency && (
                                <div className="flex items-center justify-between">
                                    <span className="text-sm text-gray-600">Latency</span>
                                    <span className="text-sm font-semibold text-gray-900">{stats.redis_latency} ms</span>
                                </div>
                            )}
                        </div>
                    </Card>
                </div>

                {/* Queue Sizes */}
                <Card className="bg-white border border-gray-200 shadow-md">
                    <h3 className="text-lg font-bold text-gray-900 mb-4">Queue Sizes</h3>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        {stats?.queue_sizes && typeof stats.queue_sizes === 'object' && !stats.queue_sizes.error ? (
                            Object.entries(stats.queue_sizes).map(([queue, size]) => (
                                <div key={queue} className="p-4 bg-gray-50 rounded-lg">
                                    <p className="text-sm text-gray-600 mb-1 capitalize">{queue} Queue</p>
                                    <p className="text-2xl font-bold text-gray-900">{size || 0}</p>
                                </div>
                            ))
                        ) : (
                            <div className="col-span-3 text-center py-4 text-gray-500">
                                Unable to fetch queue sizes
                            </div>
                        )}
                    </div>
                </Card>

                {/* Automation Tasks Stats */}
                <Card className="bg-white border border-gray-200 shadow-md">
                    <h3 className="text-lg font-bold text-gray-900 mb-4">Automation Tasks</h3>
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div className="p-4 bg-yellow-50 rounded-lg">
                            <p className="text-sm text-yellow-600 mb-1">Pending</p>
                            <p className="text-2xl font-bold text-yellow-900">{automationStats?.pending || 0}</p>
                        </div>
                        <div className="p-4 bg-blue-50 rounded-lg">
                            <p className="text-sm text-blue-600 mb-1">Running</p>
                            <p className="text-2xl font-bold text-blue-900">{automationStats?.running || 0}</p>
                        </div>
                        <div className="p-4 bg-green-50 rounded-lg">
                            <p className="text-sm text-green-600 mb-1">Success</p>
                            <p className="text-2xl font-bold text-green-900">{automationStats?.success || 0}</p>
                        </div>
                        <div className="p-4 bg-red-50 rounded-lg">
                            <p className="text-sm text-red-600 mb-1">Failed</p>
                            <p className="text-2xl font-bold text-red-900">{automationStats?.failed || 0}</p>
                        </div>
                    </div>
                </Card>

                {/* System Information */}
                <Card className="bg-white border border-gray-200 shadow-md">
                    <h3 className="text-lg font-bold text-gray-900 mb-4">System Information</h3>
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <p className="text-sm text-gray-600 mb-1">PHP Version</p>
                            <p className="text-base font-semibold text-gray-900">{stats?.php_version || 'N/A'}</p>
                        </div>
                        <div>
                            <p className="text-sm text-gray-600 mb-1">Laravel Version</p>
                            <p className="text-base font-semibold text-gray-900">{stats?.laravel_version || 'N/A'}</p>
                        </div>
                        <div>
                            <p className="text-sm text-gray-600 mb-1">Memory Usage</p>
                            <p className="text-base font-semibold text-gray-900">{stats?.memory_usage || 'N/A'}</p>
                        </div>
                        <div>
                            <p className="text-sm text-gray-600 mb-1">Peak Memory</p>
                            <p className="text-base font-semibold text-gray-900">{stats?.memory_peak || 'N/A'}</p>
                        </div>
                    </div>
                </Card>

                {/* Failed Jobs */}
                <Card className="bg-white border border-gray-200 shadow-md">
                    <div className="flex items-center justify-between mb-4">
                        <h3 className="text-lg font-bold text-gray-900">Failed Jobs</h3>
                        {stats?.failed_jobs_count > 0 && (
                            <Button variant="danger" onClick={handleFlushJobs}>
                                🗑️ Flush All
                            </Button>
                        )}
                    </div>
                    <div className="mb-4">
                        <p className="text-sm text-gray-600">
                            Total Failed Jobs: <span className="font-semibold text-gray-900">{stats?.failed_jobs_count || 0}</span>
                        </p>
                    </div>
                    {recentFailedJobs && recentFailedJobs.length > 0 ? (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">ID</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Connection</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Queue</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Exception</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Failed At</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {recentFailedJobs.map((job) => (
                                        <tr key={job.id} className="hover:bg-gray-50 transition-colors">
                                            <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-600">#{job.id}</td>
                                            <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-600">{job.connection || '-'}</td>
                                            <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-600">{job.queue || '-'}</td>
                                            <td className="px-4 py-3 text-sm text-gray-600 max-w-xs truncate" title={job.exception || ''}>
                                                {job.exception ? job.exception.substring(0, 50) + '...' : '-'}
                                            </td>
                                            <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-600">
                                                {job.failed_at ? new Date(job.failed_at).toLocaleString() : '-'}
                                            </td>
                                            <td className="px-4 py-3 whitespace-nowrap text-sm">
                                                <button
                                                    onClick={() => handleRetryJob(job.id)}
                                                    className="text-green-600 hover:text-green-900"
                                                    title="Retry"
                                                >
                                                    🔄 Retry
                                                </button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ) : (
                        <div className="text-center py-8">
                            <div className="inline-block p-4 bg-green-100 rounded-full mb-2">
                                <span className="text-3xl">✅</span>
                            </div>
                            <p className="text-gray-500 font-medium">No failed jobs</p>
                            <p className="text-gray-400 text-sm mt-1">All jobs are running successfully</p>
                        </div>
                    )}
                </Card>
            </div>
        </AdminLayout>
    );
}

