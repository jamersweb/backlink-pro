import { useState } from 'react';
import { LineChart, Line, BarChart, Bar, PieChart, Pie, Cell, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts';
import AppLayout from '../Components/Layout/AppLayout';
import Card from '../Components/Shared/Card';
import Button from '../Components/Shared/Button';
import { Link } from '@inertiajs/react';

export default function Dashboard({ user, subscription, stats, recentBacklinks, recentCampaigns, dailyBacklinks, backlinksByType }) {
    const [chartPeriod, setChartPeriod] = useState(7); // 7 or 30 days
    const [activeTab, setActiveTab] = useState('overview');

    // Prepare chart data
    const dailyBacklinksData = dailyBacklinks?.map(item => ({
        date: new Date(item.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }),
        count: item.count,
    })) || [];

    const backlinksByTypeData = backlinksByType ? Object.entries(backlinksByType).map(([type, count]) => ({
        name: type.charAt(0).toUpperCase() + type.slice(1),
        value: count,
    })) : [];

    const COLORS = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6'];

    const getStatusBadge = (status) => {
        const colors = {
            active: 'bg-green-100 text-green-800',
            canceled: 'bg-red-100 text-red-800',
            past_due: 'bg-yellow-100 text-yellow-800',
            trialing: 'bg-blue-100 text-blue-800',
        };
        return (
            <span className={`px-2 py-1 text-xs font-semibold rounded-full ${colors[status] || 'bg-gray-100 text-gray-800'}`}>
                {status?.replace('_', ' ').toUpperCase()}
            </span>
        );
    };

    const tabs = [
        { id: 'overview', label: 'Overview', icon: '📊' },
        { id: 'seo-audit', label: 'SEO Audit', icon: '🔍' },
        { id: 'seo-tracking', label: 'SEO Tracking', icon: '📈' },
        { id: 'analytics', label: 'Analytics', icon: '📊' },
        { id: 'automation', label: 'Automation', icon: '⚙️' },
        { id: 'managed-services', label: 'Managed Services', icon: '👥' },
    ];

    const renderTabContent = () => {
        switch (activeTab) {
            case 'overview':
                return renderOverviewTab();
            case 'seo-audit':
                return renderSeoAuditTab();
            case 'seo-tracking':
                return renderSeoTrackingTab();
            case 'analytics':
                return renderAnalyticsTab();
            case 'automation':
                return renderAutomationTab();
            case 'managed-services':
                return renderManagedServicesTab();
            default:
                return renderOverviewTab();
        }
    };

    const renderOverviewTab = () => (
        <div className="space-y-6">
                {/* Subscription Status */}
                {user.plan && (
                    <Card>
                        <div className="flex items-center justify-between">
                            <div>
                                <h3 className="text-lg font-semibold text-gray-900">Current Subscription</h3>
                                <p className="text-sm text-gray-600 mt-1">
                                    {user.plan.name} - ${user.plan.price}/{user.plan.billing_interval}
                                </p>
                                {subscription && (
                                    <p className="text-xs text-gray-500 mt-1">
                                        Status: {getStatusBadge(subscription.status)}
                                        {subscription.current_period_end && (
                                            <span className="ml-2">
                                                • Renews: {new Date(subscription.current_period_end * 1000).toLocaleDateString()}
                                            </span>
                                        )}
                                    </p>
                                )}
                            </div>
                            <Link href="/subscription/manage">
                                <Button variant="outline">Manage Subscription</Button>
                            </Link>
                        </div>
                    </Card>
                )}

                {/* Stats Cards */}
                <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <div className="flex items-center">
                            <div className="flex-shrink-0">
                                <div className="flex items-center justify-center h-12 w-12 rounded-md bg-indigo-500 text-white">
                                    <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLineCap="round" strokeLineJoin="round" strokeWidth={2} d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                    </svg>
                                </div>
                            </div>
                            <div className="ml-5 w-0 flex-1">
                                <dl>
                                    <dt className="text-sm font-medium text-gray-500 truncate">Total Backlinks</dt>
                                    <dd className="text-lg font-medium text-gray-900">{stats?.total_backlinks || 0}</dd>
                                </dl>
                            </div>
                        </div>
                    </Card>

                    <Card>
                        <div className="flex items-center">
                            <div className="flex-shrink-0">
                                <div className="flex items-center justify-center h-12 w-12 rounded-md bg-green-500 text-white">
                                    <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLineCap="round" strokeLineJoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            </div>
                            <div className="ml-5 w-0 flex-1">
                                <dl>
                                    <dt className="text-sm font-medium text-gray-500 truncate">Links Today</dt>
                                    <dd className="text-lg font-medium text-gray-900">
                                        {stats?.links_today || 0} / {stats?.daily_limit || '∞'}
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </Card>

                    <Card>
                        <div className="flex items-center">
                            <div className="flex-shrink-0">
                                <div className="flex items-center justify-center h-12 w-12 rounded-md bg-blue-500 text-white">
                                    <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLineCap="round" strokeLineJoin="round" strokeWidth={2} d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                </div>
                            </div>
                            <div className="ml-5 w-0 flex-1">
                                <dl>
                                    <dt className="text-sm font-medium text-gray-500 truncate">Active Campaigns</dt>
                                    <dd className="text-lg font-medium text-gray-900">{stats?.active_campaigns || 0}</dd>
                                </dl>
                            </div>
                        </div>
                    </Card>

                    <Card>
                        <div className="flex items-center">
                            <div className="flex-shrink-0">
                                <div className="flex items-center justify-center h-12 w-12 rounded-md bg-yellow-500 text-white">
                                    <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLineCap="round" strokeLineJoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            </div>
                            <div className="ml-5 w-0 flex-1">
                                <dl>
                                    <dt className="text-sm font-medium text-gray-500 truncate">Verified Links</dt>
                                    <dd className="text-lg font-medium text-gray-900">{stats?.verified_links || 0}</dd>
                                </dl>
                            </div>
                        </div>
                    </Card>
                </div>

                {/* Charts Section */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Daily Backlinks Chart */}
                    <Card className="bg-white border border-gray-200 shadow-md">
                        <div className="flex items-center justify-between mb-4">
                            <h3 className="text-lg font-bold text-gray-900">Backlinks Created (Last 7 Days)</h3>
                            <div className="flex gap-2">
                                <button
                                    onClick={() => setChartPeriod(7)}
                                    className={`px-3 py-1 text-xs font-medium rounded-md ${
                                        chartPeriod === 7 ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-700'
                                    }`}
                                >
                                    7 Days
                                </button>
                                <button
                                    onClick={() => setChartPeriod(30)}
                                    className={`px-3 py-1 text-xs font-medium rounded-md ${
                                        chartPeriod === 30 ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-700'
                                    }`}
                                >
                                    30 Days
                                </button>
                            </div>
                        </div>
                        {dailyBacklinksData.length > 0 ? (
                            <ResponsiveContainer width="100%" height={250}>
                                <LineChart data={dailyBacklinksData}>
                                    <CartesianGrid strokeDasharray="3 3" />
                                    <XAxis dataKey="date" />
                                    <YAxis />
                                    <Tooltip />
                                    <Line type="monotone" dataKey="count" stroke="#3B82F6" strokeWidth={2} name="Backlinks" />
                                </LineChart>
                            </ResponsiveContainer>
                        ) : (
                            <div className="text-center py-12 text-gray-500">
                                No data available yet. Create campaigns to see your backlink growth!
                            </div>
                        )}
                    </Card>

                    {/* Backlinks by Type Pie Chart */}
                    <Card className="bg-white border border-gray-200 shadow-md">
                        <h3 className="text-lg font-bold text-gray-900 mb-4">Backlinks by Type</h3>
                        {backlinksByTypeData.length > 0 ? (
                            <div>
                                <ResponsiveContainer width="100%" height={250}>
                                    <PieChart>
                                        <Pie
                                            data={backlinksByTypeData}
                                            cx="50%"
                                            cy="50%"
                                            labelLine={false}
                                            label={({ name, percent }) => `${name} ${(percent * 100).toFixed(0)}%`}
                                            outerRadius={70}
                                            fill="#8884d8"
                                            dataKey="value"
                                        >
                                            {backlinksByTypeData.map((entry, index) => (
                                                <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                                            ))}
                                        </Pie>
                                        <Tooltip />
                                    </PieChart>
                                </ResponsiveContainer>
                                <div className="mt-4 space-y-2">
                                    {backlinksByTypeData.map((item, index) => (
                                        <div key={item.name} className="flex items-center justify-between text-sm">
                                            <div className="flex items-center gap-2">
                                                <div className="w-3 h-3 rounded-full" style={{ backgroundColor: COLORS[index % COLORS.length] }}></div>
                                                <span className="text-gray-700">{item.name}</span>
                                            </div>
                                            <span className="font-semibold text-gray-900">{item.value}</span>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        ) : (
                            <div className="text-center py-12 text-gray-500">
                                No data available yet
                            </div>
                        )}
                    </Card>
                </div>

                {/* Quick Actions */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <Card title="Quick Actions">
                        <div className="grid grid-cols-2 gap-4">
                            <Link href="/campaign/create">
                                <Button variant="primary" className="w-full">Create Campaign</Button>
                            </Link>
                            <Link href="/campaign">
                                <Button variant="secondary" className="w-full">View Campaigns</Button>
                            </Link>
                            <Link href="/backlinks">
                                <Button variant="secondary" className="w-full">View Backlinks</Button>
                            </Link>
                            <Link href="/reports">
                                <Button variant="secondary" className="w-full">View Reports</Button>
                            </Link>
                        </div>
                    </Card>

                    <Card title="Quick Links">
                        <div className="grid grid-cols-2 gap-4">
                            <Link href="/domains">
                                <Button variant="secondary" className="w-full">Domains</Button>
                            </Link>
                            <Link href="/site-accounts">
                                <Button variant="secondary" className="w-full">Site Accounts</Button>
                            </Link>
                            <Link href="/gmail">
                                <Button variant="secondary" className="w-full">Gmail Accounts</Button>
                            </Link>
                            <Link href="/settings">
                                <Button variant="secondary" className="w-full">Settings</Button>
                            </Link>
                        </div>
                    </Card>
                </div>

                {/* Recent Campaigns */}
                {recentCampaigns && recentCampaigns.length > 0 && (
                    <Card title="Recent Campaigns">
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Campaign</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Backlinks</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {recentCampaigns.map((campaign) => (
                                        <tr key={campaign.id}>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                {campaign.name}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${
                                                    campaign.status === 'active' ? 'bg-green-100 text-green-800' :
                                                    campaign.status === 'paused' ? 'bg-yellow-100 text-yellow-800' :
                                                    'bg-gray-100 text-gray-800'
                                                }`}>
                                                    {campaign.status}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {campaign.backlinks_count || 0}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {new Date(campaign.created_at).toLocaleDateString()}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm">
                                                <Link href={`/campaign/${campaign.id}`} className="text-indigo-600 hover:text-indigo-900 mr-3">
                                                    View
                                                </Link>
                                                <Link href={`/campaign/${campaign.id}/backlinks`} className="text-indigo-600 hover:text-indigo-900">
                                                    Backlinks
                                                </Link>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <div className="mt-4">
                            <Link href="/campaign">
                                <Button variant="outline" className="w-full">View All Campaigns</Button>
                            </Link>
                        </div>
                    </Card>
                )}

                {/* Recent Backlinks */}
                <Card title="Recent Backlinks">
                    {recentBacklinks && recentBacklinks.length > 0 ? (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Campaign</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {recentBacklinks.map((backlink) => (
                                        <tr key={backlink.id}>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {new Date(backlink.created_at).toLocaleDateString()}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {backlink.campaign?.name || 'N/A'}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 capitalize">
                                                {backlink.type}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${
                                                    backlink.status === 'verified' ? 'bg-green-100 text-green-800' :
                                                    backlink.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                                                    backlink.status === 'submitted' ? 'bg-blue-100 text-blue-800' :
                                                    'bg-red-100 text-red-800'
                                                }`}>
                                                    {backlink.status}
                                                </span>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ) : (
                        <p className="text-gray-500 text-center py-4">No backlinks yet. Create your first campaign to get started!</p>
                    )}
                </Card>
        </div>
    );

    const renderSeoAuditTab = () => (
        <div className="space-y-6">
            <Card>
                <h2 className="text-2xl font-bold text-gray-900 mb-4">SEO Audit</h2>
                <p className="text-gray-600 mb-6">Run comprehensive SEO audits for your websites</p>
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <Link href="/audit" className="block">
                        <Card className="hover:shadow-lg transition-shadow cursor-pointer">
                            <div className="text-center p-6">
                                <div className="text-4xl mb-3">🔍</div>
                                <h3 className="text-lg font-semibold text-gray-900 mb-2">New SEO Audit</h3>
                                <p className="text-sm text-gray-600">Start a new SEO audit for any website</p>
                            </div>
                        </Card>
                    </Link>
                    <Link href="/Backlink/auditreport" className="block">
                        <Card className="hover:shadow-lg transition-shadow cursor-pointer">
                            <div className="text-center p-6">
                                <div className="text-4xl mb-3">📄</div>
                                <h3 className="text-lg font-semibold text-gray-900 mb-2">View Reports</h3>
                                <p className="text-sm text-gray-600">View and export audit reports</p>
                            </div>
                        </Card>
                    </Link>
                    <Link href="/audit" className="block">
                        <Card className="hover:shadow-lg transition-shadow cursor-pointer">
                            <div className="text-center p-6">
                                <div className="text-4xl mb-3">⚡</div>
                                <h3 className="text-lg font-semibold text-gray-900 mb-2">Performance</h3>
                                <p className="text-sm text-gray-600">Check website performance metrics</p>
                            </div>
                        </Card>
                    </Link>
                </div>
            </Card>

            {/* Audit Reports Section */}
            <Card>
                <h3 className="text-xl font-bold text-gray-900 mb-4">Audit Reports & Exports</h3>
                <p className="text-gray-600 mb-6">Export your audit reports in various formats</p>
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div className="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                        <div className="text-3xl mb-2">📄</div>
                        <h4 className="font-semibold text-gray-900 mb-1">PDF Export</h4>
                        <p className="text-sm text-gray-600 mb-3">Export complete audit as PDF</p>
                        <p className="text-xs text-gray-500">Available on audit detail page</p>
                    </div>
                    <div className="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                        <div className="text-3xl mb-2">📊</div>
                        <h4 className="font-semibold text-gray-900 mb-1">Pages CSV</h4>
                        <p className="text-sm text-gray-600 mb-3">Export all crawled pages data</p>
                        <p className="text-xs text-gray-500">/audit/{'{id}'}/export/pages.csv</p>
                    </div>
                    <div className="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                        <div className="text-3xl mb-2">⚠️</div>
                        <h4 className="font-semibold text-gray-900 mb-1">Issues CSV</h4>
                        <p className="text-sm text-gray-600 mb-3">Export all SEO issues found</p>
                        <p className="text-xs text-gray-500">/audit/{'{id}'}/export/issues.csv</p>
                    </div>
                    <div className="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                        <div className="text-3xl mb-2">🔗</div>
                        <h4 className="font-semibold text-gray-900 mb-1">Links CSV</h4>
                        <p className="text-sm text-gray-600 mb-3">Export all links data</p>
                        <p className="text-xs text-gray-500">/audit/{'{id}'}/export/links.csv</p>
                    </div>
                </div>
                <div className="mt-6 p-4 bg-blue-50 rounded-lg">
                    <p className="text-sm text-blue-800">
                        <strong>How to access:</strong> Open the audit report page (e.g., <code className="bg-blue-100 px-1 rounded">/Backlink/auditreport</code>) and you'll find the export options there. For CSV exports, use the export URLs shown above.
                    </p>
                </div>
            </Card>
        </div>
    );

    const renderSeoTrackingTab = () => (
        <div className="space-y-6">
            <Card>
                <h2 className="text-2xl font-bold text-gray-900 mb-4">SEO Tracking</h2>
                <p className="text-gray-600 mb-6">Track keyword rankings, GSC data, and GA4 metrics</p>
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <Link href="/domains" className="block">
                        <Card className="hover:shadow-lg transition-shadow cursor-pointer">
                            <div className="text-center p-6">
                                <div className="text-4xl mb-3">🔑</div>
                                <h3 className="text-lg font-semibold text-gray-900 mb-2">Rank Tracking</h3>
                                <p className="text-sm text-gray-600">Track keyword rankings</p>
                            </div>
                        </Card>
                    </Link>
                    <Link href="/domains" className="block">
                        <Card className="hover:shadow-lg transition-shadow cursor-pointer">
                            <div className="text-center p-6">
                                <div className="text-4xl mb-3">📊</div>
                                <h3 className="text-lg font-semibold text-gray-900 mb-2">Google Search Console</h3>
                                <p className="text-sm text-gray-600">View GSC data and insights</p>
                            </div>
                        </Card>
                    </Link>
                    <Link href="/domains" className="block">
                        <Card className="hover:shadow-lg transition-shadow cursor-pointer">
                            <div className="text-center p-6">
                                <div className="text-4xl mb-3">📈</div>
                                <h3 className="text-lg font-semibold text-gray-900 mb-2">Google Analytics 4</h3>
                                <p className="text-sm text-gray-600">View GA4 metrics and reports</p>
                            </div>
                        </Card>
                    </Link>
                </div>
            </Card>
        </div>
    );

    const renderAnalyticsTab = () => (
        <div className="space-y-6">
            <Card>
                <h2 className="text-2xl font-bold text-gray-900 mb-4">Analytics</h2>
                <p className="text-gray-600 mb-6">Enterprise analytics, cohorts, and data warehouse insights</p>
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <Link href="/reports" className="block">
                        <Card className="hover:shadow-lg transition-shadow cursor-pointer">
                            <div className="text-center p-6">
                                <div className="text-4xl mb-3">📊</div>
                                <h3 className="text-lg font-semibold text-gray-900 mb-2">Data Warehouse</h3>
                                <p className="text-sm text-gray-600">View data warehouse analytics</p>
                            </div>
                        </Card>
                    </Link>
                    <Link href="/reports" className="block">
                        <Card className="hover:shadow-lg transition-shadow cursor-pointer">
                            <div className="text-center p-6">
                                <div className="text-4xl mb-3">👥</div>
                                <h3 className="text-lg font-semibold text-gray-900 mb-2">Cohort Analysis</h3>
                                <p className="text-sm text-gray-600">User activation and retention</p>
                            </div>
                        </Card>
                    </Link>
                    <Link href="/reports" className="block">
                        <Card className="hover:shadow-lg transition-shadow cursor-pointer">
                            <div className="text-center p-6">
                                <div className="text-4xl mb-3">🧪</div>
                                <h3 className="text-lg font-semibold text-gray-900 mb-2">A/B Testing</h3>
                                <p className="text-sm text-gray-600">Run experiments and tests</p>
                            </div>
                        </Card>
                    </Link>
                </div>
            </Card>
        </div>
    );

    const renderAutomationTab = () => (
        <div className="space-y-6">
            <Card>
                <h2 className="text-2xl font-bold text-gray-900 mb-4">Automation</h2>
                <p className="text-gray-600 mb-6">Fix automation, backlink strategy, and monitoring</p>
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <Link href="/domains" className="block">
                        <Card className="hover:shadow-lg transition-shadow cursor-pointer">
                            <div className="text-center p-6">
                                <div className="text-4xl mb-3">🔧</div>
                                <h3 className="text-lg font-semibold text-gray-900 mb-2">Fix Automation</h3>
                                <p className="text-sm text-gray-600">Automated code fixes and patches</p>
                            </div>
                        </Card>
                    </Link>
                    <Link href="/domains" className="block">
                        <Card className="hover:shadow-lg transition-shadow cursor-pointer">
                            <div className="text-center p-6">
                                <div className="text-4xl mb-3">🔗</div>
                                <h3 className="text-lg font-semibold text-gray-900 mb-2">Backlink Strategy</h3>
                                <p className="text-sm text-gray-600">Generate backlink campaigns</p>
                            </div>
                        </Card>
                    </Link>
                    <Link href="/domains" className="block">
                        <Card className="hover:shadow-lg transition-shadow cursor-pointer">
                            <div className="text-center p-6">
                                <div className="text-4xl mb-3">👁️</div>
                                <h3 className="text-lg font-semibold text-gray-900 mb-2">Continuous Monitoring</h3>
                                <p className="text-sm text-gray-600">Monitor and detect changes</p>
                            </div>
                        </Card>
                    </Link>
                </div>
            </Card>
        </div>
    );

    const renderManagedServicesTab = () => (
        <div className="space-y-6">
            <Card>
                <h2 className="text-2xl font-bold text-gray-900 mb-4">Managed Services</h2>
                <p className="text-gray-600 mb-6">Manage clients, projects, and deliverables</p>
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <Link href="/domains" className="block">
                        <Card className="hover:shadow-lg transition-shadow cursor-pointer">
                            <div className="text-center p-6">
                                <div className="text-4xl mb-3">👥</div>
                                <h3 className="text-lg font-semibold text-gray-900 mb-2">Client Portal</h3>
                                <p className="text-sm text-gray-600">Manage client projects</p>
                            </div>
                        </Card>
                    </Link>
                    <Link href="/domains" className="block">
                        <Card className="hover:shadow-lg transition-shadow cursor-pointer">
                            <div className="text-center p-6">
                                <div className="text-4xl mb-3">📋</div>
                                <h3 className="text-lg font-semibold text-gray-900 mb-2">Deliverables</h3>
                                <p className="text-sm text-gray-600">Track deliverables and SLAs</p>
                            </div>
                        </Card>
                    </Link>
                    <Link href="/domains" className="block">
                        <Card className="hover:shadow-lg transition-shadow cursor-pointer">
                            <div className="text-center p-6">
                                <div className="text-4xl mb-3">✅</div>
                                <h3 className="text-lg font-semibold text-gray-900 mb-2">Approval Workflows</h3>
                                <p className="text-sm text-gray-600">Manage approval processes</p>
                            </div>
                        </Card>
                    </Link>
                </div>
            </Card>
        </div>
    );

    return (
        <AppLayout header="Dashboard">
            <div className="space-y-6">
                {/* Tabs Navigation */}
                <Card className="p-0">
                    <div className="border-b border-gray-200">
                        <nav className="-mb-px flex space-x-8 overflow-x-auto" aria-label="Tabs">
                            {tabs.map((tab) => (
                                <button
                                    key={tab.id}
                                    onClick={() => setActiveTab(tab.id)}
                                    className={`
                                        whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2
                                        ${activeTab === tab.id
                                            ? 'border-indigo-500 text-indigo-600'
                                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                        }
                                    `}
                                >
                                    <span className="text-lg">{tab.icon}</span>
                                    <span>{tab.label}</span>
                                </button>
                            ))}
                        </nav>
                    </div>
                </Card>

                {/* Tab Content */}
                {renderTabContent()}
            </div>
        </AppLayout>
    );
}
