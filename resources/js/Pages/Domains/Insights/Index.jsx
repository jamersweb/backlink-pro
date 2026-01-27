import { Link, router } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import AppLayout from '@/Components/Layout/AppLayout';
import Card from '@/Components/Shared/Card';
import Button from '@/Components/Shared/Button';
import SummaryCards from './Partials/SummaryCards';
import AlertsPanel from './Partials/AlertsPanel';
import TasksBoard from './Partials/TasksBoard';
import KpiChart from './Partials/KpiChart';

export default function InsightsIndex({ domain, latestRun, summary, openTasks, doingTasks, doneTasks, alerts, kpiData }) {
    const [isRunning, setIsRunning] = useState(false);

    // Auto-refresh if running
    useEffect(() => {
        if (latestRun?.status === 'running' || latestRun?.status === 'queued') {
            setIsRunning(true);
            const interval = setInterval(() => {
                router.reload({ only: ['latestRun'] });
            }, 8000);

            return () => clearInterval(interval);
        } else {
            setIsRunning(false);
        }
    }, [latestRun?.status]);

    const handleRunNow = () => {
        router.post(`/domains/${domain.id}/insights/run`, {}, {
            onStart: () => setIsRunning(true),
        });
    };

    const handleMarkAllRead = () => {
        alerts?.filter(a => !a.is_read).forEach(alert => {
            router.post(`/domains/${domain.id}/alerts/${alert.id}/read`, {}, {
                preserveScroll: true,
            });
        });
    };

    return (
        <AppLayout header="Insights">
            <div className="space-y-6">
                {/* Breadcrumb */}
                <div className="flex items-center gap-2 text-sm text-gray-600">
                    <Link href="/domains" className="hover:text-gray-900">Domains</Link>
                    <span>/</span>
                    <Link href={`/domains/${domain.id}`} className="hover:text-gray-900">{domain.name}</Link>
                    <span>/</span>
                    <span className="text-gray-900">Insights</span>
                </div>

                {/* Header */}
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">Insights & Alerts</h1>
                        <p className="text-sm text-gray-500 mt-1">
                            {latestRun ? (
                                <>
                                    Last run: {new Date(latestRun.finished_at || latestRun.created_at).toLocaleString()}
                                    {isRunning && <span className="ml-2 text-blue-600">Running...</span>}
                                </>
                            ) : (
                                'No insights generated yet'
                            )}
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <Link href={`/domains/${domain.id}/planner`}>
                            <Button variant="outline">
                                Action Planner
                            </Button>
                        </Link>
                        {alerts?.some(a => !a.is_read) && (
                            <Button variant="outline" onClick={handleMarkAllRead}>
                                Mark All Read
                            </Button>
                        )}
                        <Button variant="primary" onClick={handleRunNow} disabled={isRunning}>
                            {isRunning ? 'Running...' : 'Run Now'}
                        </Button>
                    </div>
                </div>

                {/* Summary Cards */}
                <SummaryCards summary={summary} />

                {/* KPI Chart */}
                {kpiData && kpiData.length > 0 && (
                    <Card>
                        <div className="p-6">
                            <h3 className="text-lg font-semibold text-gray-900 mb-4">KPI Trends (Last 30 Days)</h3>
                            <KpiChart data={kpiData} />
                        </div>
                    </Card>
                )}

                {/* Alerts Panel */}
                <AlertsPanel alerts={alerts} domain={domain} />

                {/* Tasks Board */}
                <TasksBoard 
                    openTasks={openTasks}
                    doingTasks={doingTasks}
                    doneTasks={doneTasks}
                    domain={domain}
                />
            </div>
        </AppLayout>
    );
}

