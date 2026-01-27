import AdminLayout from '@/Components/Layout/AdminLayout';
import Card from '@/Components/Shared/Card';
import Button from '@/Components/Shared/Button';
import { router } from '@inertiajs/react';
import { useState } from 'react';

export default function SystemConfigIndex({ system, services, cache, queue, logs }) {
    const [loading, setLoading] = useState({});

    const handleAction = (action) => {
        setLoading(prev => ({ ...prev, [action]: true }));
        router.post(`/admin/system-config/${action}`, {}, {
            preserveScroll: true,
            onFinish: () => setLoading(prev => ({ ...prev, [action]: false })),
        });
    };

    const handleClearCache = (type) => {
        setLoading(prev => ({ ...prev, [`cache-${type}`]: true }));
        router.post('/admin/system-config/clear-cache', { type }, {
            preserveScroll: true,
            onFinish: () => setLoading(prev => ({ ...prev, [`cache-${type}`]: false })),
        });
    };

    const getStatusColor = (status) => {
        switch (status) {
            case 'healthy': return 'bg-green-100 text-green-800';
            case 'warning': return 'bg-yellow-100 text-yellow-800';
            case 'error': return 'bg-red-100 text-red-800';
            default: return 'bg-gray-100 text-gray-800';
        }
    };

    const getLogLevelColor = (level) => {
        switch (level?.toLowerCase()) {
            case 'error': return 'text-red-600 bg-red-50';
            case 'warning': return 'text-yellow-600 bg-yellow-50';
            case 'info': return 'text-blue-600 bg-blue-50';
            case 'debug': return 'text-gray-600 bg-gray-50';
            default: return 'text-gray-600 bg-gray-50';
        }
    };

    return (
        <AdminLayout header="System Configuration">
            <div className="space-y-6">
                {/* Quick Actions */}
                <Card>
                    <h3 className="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
                    <div className="flex flex-wrap gap-3">
                        <Button
                            size="sm"
                            variant="secondary"
                            onClick={() => handleClearCache('all')}
                            disabled={loading['cache-all']}
                        >
                            {loading['cache-all'] ? 'Clearing...' : 'Clear All Cache'}
                        </Button>
                        <Button
                            size="sm"
                            variant="secondary"
                            onClick={() => handleAction('optimize')}
                            disabled={loading.optimize}
                        >
                            {loading.optimize ? 'Optimizing...' : 'Optimize App'}
                        </Button>
                        <Button
                            size="sm"
                            variant="secondary"
                            onClick={() => handleAction('retry-failed-jobs')}
                            disabled={loading['retry-failed-jobs']}
                        >
                            {loading['retry-failed-jobs'] ? 'Retrying...' : 'Retry Failed Jobs'}
                        </Button>
                        <Button
                            size="sm"
                            variant="danger"
                            onClick={() => handleAction('flush-failed-jobs')}
                            disabled={loading['flush-failed-jobs']}
                        >
                            {loading['flush-failed-jobs'] ? 'Flushing...' : 'Flush Failed Jobs'}
                        </Button>
                    </div>
                </Card>

                {/* System Info & Services Grid */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* System Information */}
                    <Card>
                        <h3 className="text-lg font-semibold text-gray-900 mb-4">System Information</h3>
                        <div className="space-y-3">
                            <InfoRow label="PHP Version" value={system?.php_version} />
                            <InfoRow label="Laravel Version" value={system?.laravel_version} />
                            <InfoRow label="Environment" value={system?.environment} badge />
                            <InfoRow label="Debug Mode" value={system?.debug_mode ? 'Enabled' : 'Disabled'} badgeColor={system?.debug_mode ? 'warning' : 'success'} badge />
                            <InfoRow label="Timezone" value={system?.timezone} />
                            <InfoRow label="Memory Limit" value={system?.server?.memory_limit} />
                            <InfoRow label="Max Execution Time" value={`${system?.server?.max_execution_time}s`} />
                        </div>
                        
                        {/* Storage */}
                        <div className="mt-6 pt-4 border-t border-gray-200">
                            <h4 className="font-medium text-gray-700 mb-3">Storage</h4>
                            <div className="mb-2 flex justify-between text-sm">
                                <span className="text-gray-600">{system?.storage?.disk_free} free of {system?.storage?.disk_total}</span>
                                <span className="font-medium">{system?.storage?.disk_usage_percent}%</span>
                            </div>
                            <div className="w-full bg-gray-200 rounded-full h-2.5">
                                <div 
                                    className={`h-2.5 rounded-full ${
                                        system?.storage?.disk_usage_percent > 90 ? 'bg-red-500' :
                                        system?.storage?.disk_usage_percent > 70 ? 'bg-yellow-500' : 'bg-green-500'
                                    }`}
                                    style={{ width: `${system?.storage?.disk_usage_percent}%` }}
                                ></div>
                            </div>
                        </div>
                    </Card>

                    {/* Service Status */}
                    <Card>
                        <h3 className="text-lg font-semibold text-gray-900 mb-4">Service Status</h3>
                        <div className="space-y-3">
                            {Object.entries(services || {}).map(([name, service]) => (
                                <div key={name} className="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                                    <div>
                                        <span className="font-medium text-gray-900 capitalize">{name}</span>
                                        <p className="text-xs text-gray-500">{service.driver || service.host}</p>
                                    </div>
                                    <span className={`px-2.5 py-1 text-xs font-medium rounded-full ${getStatusColor(service.status)}`}>
                                        {service.status}
                                    </span>
                                </div>
                            ))}
                        </div>
                    </Card>
                </div>

                {/* Queue & Cache Stats */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Queue Information */}
                    <Card>
                        <h3 className="text-lg font-semibold text-gray-900 mb-4">Queue Status</h3>
                        <div className="grid grid-cols-3 gap-4">
                            <StatBox label="Pending Jobs" value={queue?.pending_jobs || 0} color="blue" />
                            <StatBox label="Failed Jobs" value={queue?.failed_jobs || 0} color={queue?.failed_jobs > 0 ? 'red' : 'green'} />
                            <StatBox label="Batches" value={queue?.batches || 0} color="purple" />
                        </div>
                        <p className="mt-4 text-sm text-gray-500">
                            Queue Connection: <span className="font-medium">{queue?.connection}</span>
                        </p>
                    </Card>

                    {/* Cache Information */}
                    <Card>
                        <h3 className="text-lg font-semibold text-gray-900 mb-4">Cache Status (Redis)</h3>
                        {cache?.error ? (
                            <p className="text-red-600">{cache.error}</p>
                        ) : (
                            <>
                                <div className="grid grid-cols-2 gap-4">
                                    <StatBox label="Memory Used" value={cache?.used_memory || 'N/A'} color="blue" />
                                    <StatBox label="Cached Keys" value={cache?.keys || 0} color="purple" />
                                </div>
                                <div className="mt-4 flex gap-2">
                                    <Button size="xs" variant="secondary" onClick={() => handleClearCache('application')}>
                                        Clear App Cache
                                    </Button>
                                    <Button size="xs" variant="secondary" onClick={() => handleClearCache('view')}>
                                        Clear Views
                                    </Button>
                                    <Button size="xs" variant="secondary" onClick={() => handleClearCache('config')}>
                                        Clear Config
                                    </Button>
                                </div>
                            </>
                        )}
                    </Card>
                </div>

                {/* Recent Logs */}
                <Card>
                    <h3 className="text-lg font-semibold text-gray-900 mb-4">Recent Logs</h3>
                    {logs && logs.length > 0 ? (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-4 py-2 text-left text-xs font-medium text-gray-500">Time</th>
                                        <th className="px-4 py-2 text-left text-xs font-medium text-gray-500">Level</th>
                                        <th className="px-4 py-2 text-left text-xs font-medium text-gray-500">Message</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {logs.map((log, index) => (
                                        <tr key={index} className="hover:bg-gray-50">
                                            <td className="px-4 py-2 whitespace-nowrap text-xs text-gray-500">
                                                {log.timestamp}
                                            </td>
                                            <td className="px-4 py-2 whitespace-nowrap">
                                                <span className={`px-2 py-0.5 text-xs font-medium rounded ${getLogLevelColor(log.level)}`}>
                                                    {log.level}
                                                </span>
                                            </td>
                                            <td className="px-4 py-2 text-xs text-gray-700 max-w-md truncate">
                                                {log.message}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ) : (
                        <p className="text-gray-500 text-center py-8">No recent logs found</p>
                    )}
                </Card>
            </div>
        </AdminLayout>
    );
}

function InfoRow({ label, value, badge = false, badgeColor = 'default' }) {
    const badgeColors = {
        default: 'bg-gray-100 text-gray-800',
        success: 'bg-green-100 text-green-800',
        warning: 'bg-yellow-100 text-yellow-800',
        danger: 'bg-red-100 text-red-800',
    };

    return (
        <div className="flex justify-between items-center py-1">
            <span className="text-gray-600 text-sm">{label}</span>
            {badge ? (
                <span className={`px-2 py-0.5 text-xs font-medium rounded ${badgeColors[badgeColor]}`}>
                    {value}
                </span>
            ) : (
                <span className="font-medium text-gray-900 text-sm">{value}</span>
            )}
        </div>
    );
}

function StatBox({ label, value, color = 'blue' }) {
    const colors = {
        blue: 'bg-blue-50 text-blue-700',
        green: 'bg-green-50 text-green-700',
        red: 'bg-red-50 text-red-700',
        purple: 'bg-purple-50 text-purple-700',
        yellow: 'bg-yellow-50 text-yellow-700',
    };

    return (
        <div className={`rounded-lg p-4 text-center ${colors[color]}`}>
            <p className="text-2xl font-bold">{value}</p>
            <p className="text-xs mt-1">{label}</p>
        </div>
    );
}
