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
            case 'healthy': return 'admin-badge-success';
            case 'warning': return 'admin-badge-warning';
            case 'error': return 'admin-badge-danger';
            default: return 'admin-badge-neutral';
        }
    };

    const getLogLevelColor = (level) => {
        switch (level?.toLowerCase()) {
            case 'error': return 'admin-badge admin-badge-danger';
            case 'warning': return 'admin-badge admin-badge-warning';
            case 'info': return 'admin-badge admin-badge-info';
            case 'debug': return 'admin-badge admin-badge-neutral';
            default: return 'admin-badge admin-badge-neutral';
        }
    };

    return (
        <AdminLayout header="System Configuration">
            <div className="space-y-6">
                {/* Quick Actions */}
                <Card variant="elevated">
                    <h3 className="text-lg font-semibold text-[var(--admin-text)] mb-4">Quick Actions</h3>
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
                    <Card variant="elevated">
                        <h3 className="text-lg font-semibold text-[var(--admin-text)] mb-4">System Information</h3>
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
                        <div className="mt-6 pt-4 border-t border-[var(--admin-border)]">
                            <h4 className="font-medium text-[var(--admin-text)] mb-3">Storage</h4>
                            <div className="mb-2 flex justify-between text-sm">
                                <span className="text-[var(--admin-text-muted)]">{system?.storage?.disk_free} free of {system?.storage?.disk_total}</span>
                                <span className="font-medium text-[var(--admin-text)]">{system?.storage?.disk_usage_percent}%</span>
                            </div>
                            <div className="w-full bg-[var(--admin-surface-3)] rounded-full h-2.5">
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
                    <Card variant="elevated">
                        <h3 className="text-lg font-semibold text-[var(--admin-text)] mb-4">Service Status</h3>
                        <div className="space-y-3">
                            {Object.entries(services || {}).map(([name, service]) => (
                                <div key={name} className="flex items-center justify-between py-2 border-b border-[var(--admin-border-light)] last:border-0">
                                    <div>
                                        <span className="font-medium text-[var(--admin-text)] capitalize">{name}</span>
                                        <p className="text-xs text-[var(--admin-text-muted)]">{service.driver || service.host}</p>
                                    </div>
                                    <span className={`admin-badge ${getStatusColor(service.status)}`}>
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
                    <Card variant="elevated">
                        <h3 className="text-lg font-semibold text-[var(--admin-text)] mb-4">Queue Status</h3>
                        <div className="grid grid-cols-3 gap-4">
                            <StatBox label="Pending Jobs" value={queue?.pending_jobs || 0} color="blue" />
                            <StatBox label="Failed Jobs" value={queue?.failed_jobs || 0} color={queue?.failed_jobs > 0 ? 'red' : 'green'} />
                            <StatBox label="Batches" value={queue?.batches || 0} color="purple" />
                        </div>
                        <p className="mt-4 text-sm text-[var(--admin-text-muted)]">
                            Queue Connection: <span className="font-medium text-[var(--admin-text)]">{queue?.connection}</span>
                        </p>
                    </Card>

                    {/* Cache Information */}
                    <Card variant="elevated">
                        <h3 className="text-lg font-semibold text-[var(--admin-text)] mb-4">Cache Status (Redis)</h3>
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
                <Card variant="elevated">
                    <h3 className="text-lg font-semibold text-[var(--admin-text)] mb-4">Recent Logs</h3>
                    {logs && logs.length > 0 ? (
                        <div className="overflow-x-auto">
                            <table className="admin-table min-w-full">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>Level</th>
                                        <th>Message</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {logs.map((log, index) => (
                                        <tr key={index}>
                                            <td className="whitespace-nowrap text-xs text-[var(--admin-text-muted)]">
                                                {log.timestamp}
                                            </td>
                                            <td className="whitespace-nowrap">
                                                <span className={`admin-badge ${getLogLevelColor(log.level)}`}>
                                                    {log.level}
                                                </span>
                                            </td>
                                            <td className="text-xs text-[var(--admin-text)] max-w-md truncate">
                                                {log.message}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ) : (
                        <p className="text-[var(--admin-text-muted)] text-center py-8">No recent logs found</p>
                    )}
                </Card>
            </div>
        </AdminLayout>
    );
}

function InfoRow({ label, value, badge = false, badgeColor = 'default' }) {
    const badgeClasses = {
        default: 'admin-badge-neutral',
        success: 'admin-badge-success',
        warning: 'admin-badge-warning',
        danger: 'admin-badge-danger',
    };

    return (
        <div className="flex justify-between items-center py-1">
            <span className="text-[var(--admin-text-muted)] text-sm">{label}</span>
            {badge ? (
                <span className={`admin-badge ${badgeClasses[badgeColor]}`}>
                    {value}
                </span>
            ) : (
                <span className="font-medium text-[var(--admin-text)] text-sm">{value}</span>
            )}
        </div>
    );
}

function StatBox({ label, value, color = 'blue' }) {
    const colorClasses = {
        blue: 'bg-blue-500/10 text-blue-600 dark:text-blue-400',
        green: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
        red: 'bg-red-500/10 text-red-600 dark:text-red-400',
        purple: 'bg-purple-500/10 text-purple-600 dark:text-purple-400',
        yellow: 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
    };

    return (
        <div className={`rounded-lg p-4 text-center ${colorClasses[color] || colorClasses.blue}`}>
            <p className="text-2xl font-bold">{value}</p>
            <p className="text-xs mt-1 opacity-90">{label}</p>
        </div>
    );
}
