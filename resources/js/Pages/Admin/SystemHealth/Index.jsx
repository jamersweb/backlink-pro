import { Link, router } from '@inertiajs/react';
import AdminLayout from '@/Components/Layout/AdminLayout';
import Card from '@/Components/Shared/Card';
import Button from '@/Components/Shared/Button';
import OverviewCards from './Partials/OverviewCards';
import RecentFailuresTable from './Partials/RecentFailuresTable';
import RunsStatusPanel from './Partials/RunsStatusPanel';

export default function SystemHealthIndex({ overview, recentFailures, runStatus, topErrors }) {
    return (
        <AdminLayout header="System Health">
            <div className="space-y-6">
                {/* Header */}
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">System Health Dashboard</h1>
                        <p className="text-sm text-gray-500 mt-1">Monitor job health, failures, and system activity</p>
                    </div>
                    <div className="flex gap-2">
                        <Link href="/horizon" target="_blank">
                            <Button variant="outline">Open Horizon</Button>
                        </Link>
                        <Link href="/admin/system-health/activity">
                            <Button variant="outline">View Activity Logs</Button>
                        </Link>
                        <Link href="/admin/system-health/failures">
                            <Button variant="outline">View Failures</Button>
                        </Link>
                    </div>
                </div>

                {/* Overview Cards */}
                <OverviewCards overview={overview} />

                {/* Run Status Panel */}
                <RunsStatusPanel runStatus={runStatus} />

                {/* Recent Failures */}
                <Card>
                    <div className="p-6">
                        <h3 className="text-lg font-semibold text-gray-900 mb-4">Recent Failures (Last 24h)</h3>
                        <RecentFailuresTable failures={recentFailures} />
                    </div>
                </Card>

                {/* Top Errors */}
                {topErrors && topErrors.length > 0 && (
                    <Card>
                        <div className="p-6">
                            <h3 className="text-lg font-semibold text-gray-900 mb-4">Top Error Messages</h3>
                            <div className="space-y-2">
                                {topErrors.map((error, idx) => (
                                    <div key={idx} className="flex justify-between items-center p-3 bg-gray-50 rounded">
                                        <p className="text-sm text-gray-700 flex-1 truncate">{error.exception_message}</p>
                                        <span className="text-sm font-semibold text-gray-900 ml-4">{error.count}x</span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </Card>
                )}
            </div>
        </AdminLayout>
    );
}
