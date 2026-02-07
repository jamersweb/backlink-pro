import { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import { LineChart, Line, AreaChart, Area, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts';
import AppLayout from '@/Components/Layout/AppLayout';
import Card from '@/Components/Shared/Card';
import Button from '@/Components/Shared/Button';

export default function SeoDashboard({ organization, gscMetrics, ga4Metrics, alerts, rankings, dateRange }) {
    const [selectedRange, setSelectedRange] = useState(dateRange || '30');

    const handleRangeChange = (range) => {
        setSelectedRange(range);
        router.get(route('seo.dashboard', { organization: organization.id }), {
            date_range: range,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    // Calculate KPIs
    const totalClicks = gscMetrics?.reduce((sum, m) => sum + (m.clicks || 0), 0) || 0;
    const totalImpressions = gscMetrics?.reduce((sum, m) => sum + (m.impressions || 0), 0) || 0;
    const avgCTR = gscMetrics?.length > 0 
        ? (gscMetrics.reduce((sum, m) => sum + (m.ctr || 0), 0) / gscMetrics.length) * 100 
        : 0;
    const avgPosition = gscMetrics?.length > 0
        ? gscMetrics.reduce((sum, m) => sum + (m.position || 0), 0) / gscMetrics.length
        : 0;

    const totalSessions = ga4Metrics?.reduce((sum, m) => sum + (m.sessions || 0), 0) || 0;
    const totalUsers = ga4Metrics?.reduce((sum, m) => sum + (m.users || 0), 0) || 0;
    const totalConversions = ga4Metrics?.reduce((sum, m) => sum + (m.conversions || 0), 0) || 0;

    // Prepare chart data
    const gscChartData = gscMetrics?.map(m => ({
        date: m.date,
        clicks: m.clicks,
        impressions: m.impressions,
        ctr: m.ctr ? (m.ctr * 100).toFixed(2) : 0,
        position: m.position?.toFixed(1) || 0,
    })) || [];

    const ga4ChartData = ga4Metrics?.map(m => ({
        date: m.date,
        sessions: m.sessions,
        users: m.users,
        conversions: m.conversions || 0,
    })) || [];

    const getSeverityBadge = (severity) => {
        const colors = {
            info: 'bg-blue-100 text-blue-800',
            warning: 'bg-yellow-100 text-yellow-800',
            critical: 'bg-red-100 text-red-800',
        };
        return (
            <span className={`px-2 py-1 text-xs font-semibold rounded-full ${colors[severity] || colors.info}`}>
                {severity.toUpperCase()}
            </span>
        );
    };

    return (
        <AppLayout header={`SEO Dashboard - ${organization.name}`}>
            <div className="space-y-6">
                {/* Header */}
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">SEO Dashboard</h1>
                        <p className="text-sm text-gray-500 mt-1">Track your SEO performance, rankings, and traffic</p>
                    </div>
                    <div className="flex gap-2">
                        <select
                            value={selectedRange}
                            onChange={(e) => handleRangeChange(e.target.value)}
                            className="px-3 py-2 border border-gray-300 rounded-md text-sm"
                        >
                            <option value="7">Last 7 days</option>
                            <option value="30">Last 30 days</option>
                            <option value="90">Last 90 days</option>
                        </select>
                        <Link href={route('integrations.google', { organization: organization.id })}>
                            <Button variant="outline">⚙️ Integrations</Button>
                        </Link>
                    </div>
                </div>

                {/* KPI Cards */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <Card>
                        <div className="p-4">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-600">GSC Clicks</p>
                                    <p className="text-2xl font-bold text-gray-900 mt-1">{totalClicks.toLocaleString()}</p>
                                </div>
                                <div className="text-3xl">👆</div>
                            </div>
                        </div>
                    </Card>

                    <Card>
                        <div className="p-4">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-600">GSC Impressions</p>
                                    <p className="text-2xl font-bold text-gray-900 mt-1">{totalImpressions.toLocaleString()}</p>
                                </div>
                                <div className="text-3xl">👁️</div>
                            </div>
                        </div>
                    </Card>

                    <Card>
                        <div className="p-4">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-600">Avg CTR</p>
                                    <p className="text-2xl font-bold text-gray-900 mt-1">{avgCTR.toFixed(2)}%</p>
                                </div>
                                <div className="text-3xl">📊</div>
                            </div>
                        </div>
                    </Card>

                    <Card>
                        <div className="p-4">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-600">Avg Position</p>
                                    <p className="text-2xl font-bold text-gray-900 mt-1">{avgPosition.toFixed(1)}</p>
                                </div>
                                <div className="text-3xl">📍</div>
                            </div>
                        </div>
                    </Card>
                </div>

                {/* Charts */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* GSC Chart */}
                    {gscChartData.length > 0 && (
                        <Card>
                            <div className="p-6">
                                <h3 className="text-lg font-semibold text-gray-900 mb-4">Search Console Performance</h3>
                                <ResponsiveContainer width="100%" height={300}>
                                    <AreaChart data={gscChartData}>
                                        <CartesianGrid strokeDasharray="3 3" />
                                        <XAxis 
                                            dataKey="date" 
                                            tickFormatter={(value) => new Date(value).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}
                                        />
                                        <YAxis yAxisId="left" />
                                        <YAxis yAxisId="right" orientation="right" />
                                        <Tooltip />
                                        <Legend />
                                        <Area yAxisId="left" type="monotone" dataKey="clicks" stroke="#3b82f6" fill="#3b82f6" fillOpacity={0.3} name="Clicks" />
                                        <Area yAxisId="right" type="monotone" dataKey="impressions" stroke="#10b981" fill="#10b981" fillOpacity={0.3} name="Impressions" />
                                    </AreaChart>
                                </ResponsiveContainer>
                            </div>
                        </Card>
                    )}

                    {/* GA4 Chart */}
                    {ga4ChartData.length > 0 && (
                        <Card>
                            <div className="p-6">
                                <h3 className="text-lg font-semibold text-gray-900 mb-4">GA4 Sessions & Users</h3>
                                <ResponsiveContainer width="100%" height={300}>
                                    <LineChart data={ga4ChartData}>
                                        <CartesianGrid strokeDasharray="3 3" />
                                        <XAxis 
                                            dataKey="date"
                                            tickFormatter={(value) => new Date(value).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}
                                        />
                                        <YAxis />
                                        <Tooltip />
                                        <Legend />
                                        <Line type="monotone" dataKey="sessions" stroke="#8b5cf6" name="Sessions" />
                                        <Line type="monotone" dataKey="users" stroke="#f59e0b" name="Users" />
                                    </LineChart>
                                </ResponsiveContainer>
                            </div>
                        </Card>
                    )}
                </div>

                {/* Recent Alerts */}
                {alerts && alerts.length > 0 && (
                    <Card>
                        <div className="p-6">
                            <div className="flex justify-between items-center mb-4">
                                <h3 className="text-lg font-semibold text-gray-900">Recent Alerts</h3>
                                <Link href={route('seo.alerts.index', { organization: organization.id })}>
                                    <Button variant="outline" size="sm">View All</Button>
                                </Link>
                            </div>
                            <div className="space-y-3">
                                {alerts.slice(0, 5).map((alert) => (
                                    <div key={alert.id} className="flex items-start justify-between p-3 bg-gray-50 rounded-lg">
                                        <div className="flex-1">
                                            <div className="flex items-center gap-2 mb-1">
                                                {getSeverityBadge(alert.severity)}
                                                <span className="font-medium text-gray-900">{alert.title}</span>
                                            </div>
                                            <p className="text-sm text-gray-600">{alert.message}</p>
                                            <p className="text-xs text-gray-500 mt-1">
                                                {new Date(alert.created_at).toLocaleString()}
                                            </p>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </Card>
                )}

                {/* Rankings Summary */}
                {rankings && rankings.length > 0 && (
                    <Card>
                        <div className="p-6">
                            <div className="flex justify-between items-center mb-4">
                                <h3 className="text-lg font-semibold text-gray-900">Top Rankings</h3>
                                <Link href={route('seo.rankings.index', { organization: organization.id })}>
                                    <Button variant="outline" size="sm">View All</Button>
                                </Link>
                            </div>
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Keyword</th>
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Position</th>
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Change</th>
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">URL</th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                        {rankings.slice(0, 10).map((rank, idx) => (
                                            <tr key={idx} className="hover:bg-gray-50">
                                                <td className="px-4 py-3 text-sm font-medium text-gray-900">{rank.keyword}</td>
                                                <td className="px-4 py-3 text-sm text-gray-900">
                                                    {rank.current_position ? `#${rank.current_position}` : 'N/A'}
                                                </td>
                                                <td className="px-4 py-3 text-sm">
                                                    {rank.change !== null && (
                                                        <span className={rank.change > 0 ? 'text-green-600' : rank.change < 0 ? 'text-red-600' : 'text-gray-600'}>
                                                            {rank.change > 0 ? '↑' : rank.change < 0 ? '↓' : '→'} {Math.abs(rank.change || 0)}
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="px-4 py-3 text-sm">
                                                    {rank.url ? (
                                                        <a href={rank.url} target="_blank" rel="noopener noreferrer" className="text-blue-600 hover:underline truncate block max-w-xs">
                                                            {rank.url}
                                                        </a>
                                                    ) : (
                                                        <span className="text-gray-400">—</span>
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </Card>
                )}

                {/* Empty State */}
                {(!gscMetrics || gscMetrics.length === 0) && (!ga4Metrics || ga4Metrics.length === 0) && (
                    <Card>
                        <div className="text-center py-12">
                            <div className="text-4xl mb-4">📊</div>
                            <h3 className="text-lg font-medium text-gray-900 mb-2">No SEO Data Yet</h3>
                            <p className="text-sm text-gray-500 mb-6">
                                Connect your Google Search Console and GA4 accounts to start tracking your SEO performance.
                            </p>
                            <Link href={route('integrations.google', { organization: organization.id })}>
                                <Button variant="primary">Connect Google Accounts</Button>
                            </Link>
                        </div>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
