import { useState } from 'react';
import { LineChart, Line, PieChart, Pie, Cell, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';
import AppLayout from '../Components/Layout/AppLayout';
import Card from '../Components/Shared/Card';
import { Link } from '@inertiajs/react';

export default function Dashboard({ user, subscription, stats, recentBacklinks, recentCampaigns, dailyBacklinks, backlinksByType }) {
    const [chartPeriod, setChartPeriod] = useState(7);
    const [activeTab, setActiveTab] = useState('overview');

    const dailyBacklinksData = dailyBacklinks?.map(item => ({
        date: new Date(item.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }),
        count: item.count,
    })) || [];

    const backlinksByTypeData = backlinksByType ? Object.entries(backlinksByType).map(([type, count]) => ({
        name: type.charAt(0).toUpperCase() + type.slice(1),
        value: count,
    })) : [];

    const COLORS = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6'];

    const tooltipStyle = {
        contentStyle: { background: '#1e293b', border: '1px solid rgba(255,255,255,0.1)', borderRadius: 8, color: '#e5e7eb' },
        labelStyle: { color: '#94a3b8' },
    };

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

    /*
     * ICON MAPPING — tabs use Bootstrap Icons (consistent with sidebar)
     * OLD (emoji) → NEW (bi-*)
     * 📊 Overview   → bi-grid-1x2
     * 🔍 SEO Audit  → bi-search
     * 📈 SEO Track  → bi-graph-up
     * 📊 Analytics  → bi-bar-chart-line
     * ⚙️ Automation → bi-gear
     * 👥 Managed    → bi-people
     */
    const tabs = [
        { id: 'overview', label: 'Overview', icon: 'bi-grid-1x2' },
        { id: 'seo-audit', label: 'SEO Audit', icon: 'bi-search' },
        { id: 'seo-tracking', label: 'SEO Tracking', icon: 'bi-graph-up' },
        { id: 'analytics', label: 'Analytics', icon: 'bi-bar-chart-line' },
        { id: 'automation', label: 'Automation', icon: 'bi-gear' },
        { id: 'managed-services', label: 'Managed Services', icon: 'bi-people' },
    ];

    const renderTabContent = () => {
        switch (activeTab) {
            case 'overview': return renderOverviewTab();
            case 'seo-audit': return renderSeoAuditTab();
            case 'seo-tracking': return renderSeoTrackingTab();
            case 'analytics': return renderAnalyticsTab();
            case 'automation': return renderAutomationTab();
            case 'managed-services': return renderManagedServicesTab();
            default: return renderOverviewTab();
        }
    };

    const dashboardActions = (
        <>
            <Link href="/notifications" className="bp-topbar-btn-secondary">
                <i className="bi bi-bell"></i>
                <span>Notifications</span>
            </Link>
            <Link href="/campaign/create" className="bp-topbar-btn-primary">
                <i className="bi bi-plus-lg"></i>
                <span>New Campaign</span>
            </Link>
        </>
    );

    const renderOverviewTab = () => (
        <div>
            {user.plan && (
                <div className="bp-subscription-bar">
                    <div>
                        <h3>Current Subscription</h3>
                        <p>{user.plan.name} - ${user.plan.price}/{user.plan.billing_interval}</p>
                        {subscription && (
                            <p style={{ fontSize: 12, marginTop: 4 }}>
                                Status: {getStatusBadge(subscription.status)}
                                {subscription.current_period_end && (
                                    <span style={{ marginLeft: 8 }}>
                                        Renews: {new Date(subscription.current_period_end * 1000).toLocaleDateString()}
                                    </span>
                                )}
                            </p>
                        )}
                    </div>
                    <Link href="/subscription/manage" className="bp-topbar-btn-secondary">
                        Manage Subscription
                    </Link>
                </div>
            )}

            <div className="bp-stat-grid">
                <div className="bp-stat-card">
                    <div className="bp-stat-icon indigo"><i className="bi bi-link-45deg"></i></div>
                    <div className="bp-stat-label">Total Backlinks</div>
                    <div className="bp-stat-value">{stats?.total_backlinks || 0}</div>
                </div>
                <div className="bp-stat-card">
                    <div className="bp-stat-icon emerald"><i className="bi bi-graph-up-arrow"></i></div>
                    <div className="bp-stat-label">Links Today</div>
                    <div className="bp-stat-value">{stats?.links_today || 0} / {stats?.daily_limit || '∞'}</div>
                </div>
                <div className="bp-stat-card">
                    <div className="bp-stat-icon sky"><i className="bi bi-journal-check"></i></div>
                    <div className="bp-stat-label">Active Campaigns</div>
                    <div className="bp-stat-value">{stats?.active_campaigns || 0}</div>
                </div>
                <div className="bp-stat-card">
                    <div className="bp-stat-icon violet"><i className="bi bi-shield-check"></i></div>
                    <div className="bp-stat-label">Verified Links</div>
                    <div className="bp-stat-value">{stats?.verified_links || 0}</div>
                </div>
            </div>

            <div className="bp-chart-grid">
                <div className="bp-chart-card">
                    <div className="bp-chart-header">
                        <div className="bp-chart-header-left">
                            <div className="bp-chart-header-icon blue"><i className="bi bi-graph-up"></i></div>
                            <div>
                                <h3 className="bp-chart-title">Backlinks Created (Last 7 Days)</h3>
                                <p className="bp-chart-subtitle">Backlink creation over time</p>
                            </div>
                        </div>
                        <div className="bp-pill-group">
                            <button onClick={() => setChartPeriod(7)} className={`bp-pill ${chartPeriod === 7 ? 'active' : ''}`}>7D</button>
                            <button onClick={() => setChartPeriod(30)} className={`bp-pill ${chartPeriod === 30 ? 'active' : ''}`}>30D</button>
                        </div>
                    </div>
                    <div className="bp-chart-body">
                        {dailyBacklinksData.length > 0 ? (
                            <ResponsiveContainer width="100%" height={250}>
                                <LineChart data={dailyBacklinksData}>
                                    <CartesianGrid strokeDasharray="3 3" stroke="rgba(255,255,255,0.06)" />
                                    <XAxis dataKey="date" stroke="#64748b" tick={{ fill: '#64748b', fontSize: 12 }} />
                                    <YAxis stroke="#64748b" tick={{ fill: '#64748b', fontSize: 12 }} />
                                    <Tooltip {...tooltipStyle} />
                                    <Line type="monotone" dataKey="count" stroke="#3B82F6" strokeWidth={2} dot={{ fill: '#3B82F6', r: 4 }} name="Backlinks" />
                                </LineChart>
                            </ResponsiveContainer>
                        ) : (
                            <div className="bp-empty-state">
                                <div className="bp-empty-icon"><i className="bi bi-graph-up"></i></div>
                                <p>No data available yet. Create campaigns to see your backlink growth!</p>
                            </div>
                        )}
                    </div>
                </div>

                <div className="bp-chart-card">
                    <div className="bp-chart-header">
                        <div className="bp-chart-header-left">
                            <div className="bp-chart-header-icon purple"><i className="bi bi-pie-chart"></i></div>
                            <div>
                                <h3 className="bp-chart-title">Backlinks by Type</h3>
                                <p className="bp-chart-subtitle">Distribution by category</p>
                            </div>
                        </div>
                    </div>
                    <div className="bp-chart-body">
                        {backlinksByTypeData.length > 0 ? (
                            <div>
                                <ResponsiveContainer width="100%" height={200}>
                                    <PieChart>
                                        <Pie data={backlinksByTypeData} cx="50%" cy="50%" innerRadius={50} outerRadius={80} labelLine={false} dataKey="value">
                                            {backlinksByTypeData.map((entry, index) => (
                                                <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                                            ))}
                                        </Pie>
                                        <Tooltip {...tooltipStyle} />
                                    </PieChart>
                                </ResponsiveContainer>
                                <div style={{ marginTop: 16, display: 'flex', flexDirection: 'column', gap: 8 }}>
                                    {backlinksByTypeData.map((item, index) => (
                                        <div key={item.name} style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', fontSize: 13 }}>
                                            <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                                                <div style={{ width: 10, height: 10, borderRadius: '50%', background: COLORS[index % COLORS.length] }} />
                                                <span style={{ color: '#94a3b8' }}>{item.name}</span>
                                            </div>
                                            <span style={{ fontWeight: 600, color: '#e5e7eb' }}>{item.value}</span>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        ) : (
                            <div className="bp-empty-state">
                                <div className="bp-empty-icon"><i className="bi bi-pie-chart"></i></div>
                                <p>No data available yet</p>
                            </div>
                        )}
                    </div>
                </div>
            </div>

            <div className="bp-lower-grid">
                <div className="bp-section-card">
                    <div className="bp-section-header"><h3 className="bp-section-title">Quick Actions</h3></div>
                    <div className="bp-action-grid">
                        <Link href="/campaign/create" className="bp-action-card">
                            <div className="bp-action-icon blue"><i className="bi bi-plus-lg"></i></div>
                            <span className="bp-action-label">Create Campaign</span>
                        </Link>
                        <Link href="/campaign" className="bp-action-card">
                            <div className="bp-action-icon purple"><i className="bi bi-megaphone"></i></div>
                            <span className="bp-action-label">View Campaigns</span>
                        </Link>
                        <Link href="/backlinks" className="bp-action-card">
                            <div className="bp-action-icon green"><i className="bi bi-link-45deg"></i></div>
                            <span className="bp-action-label">View Backlinks</span>
                        </Link>
                        <Link href="/reports" className="bp-action-card">
                            <div className="bp-action-icon amber"><i className="bi bi-bar-chart-line"></i></div>
                            <span className="bp-action-label">View Reports</span>
                        </Link>
                    </div>
                </div>
                <div className="bp-section-card">
                    <div className="bp-section-header"><h3 className="bp-section-title">Quick Links</h3></div>
                    <div className="bp-links-list">
                        <Link href="/domains" className="bp-link-item">
                            <div className="bp-link-icon blue"><i className="bi bi-globe2"></i></div>
                            <span className="bp-link-label">Domains</span>
                            <i className="bi bi-chevron-right bp-link-chevron"></i>
                        </Link>
                        <Link href="/site-accounts" className="bp-link-item">
                            <div className="bp-link-icon purple"><i className="bi bi-person-badge"></i></div>
                            <span className="bp-link-label">Site Accounts</span>
                            <i className="bi bi-chevron-right bp-link-chevron"></i>
                        </Link>
                        <Link href="/gmail" className="bp-link-item">
                            <div className="bp-link-icon green"><i className="bi bi-envelope"></i></div>
                            <span className="bp-link-label">Gmail Accounts</span>
                            <i className="bi bi-chevron-right bp-link-chevron"></i>
                        </Link>
                        <Link href="/settings" className="bp-link-item">
                            <div className="bp-link-icon amber"><i className="bi bi-gear"></i></div>
                            <span className="bp-link-label">Settings</span>
                            <i className="bi bi-chevron-right bp-link-chevron"></i>
                        </Link>
                    </div>
                </div>
            </div>

            {recentCampaigns && recentCampaigns.length > 0 && (
                <div className="bp-table-card">
                    <div className="bp-table-header">
                        <div><h3 className="bp-table-title">Recent Campaigns</h3></div>
                        <Link href="/campaign" className="bp-table-link">View All</Link>
                    </div>
                    <div style={{ overflowX: 'auto' }}>
                        <table className="bp-table">
                            <thead><tr><th>Campaign</th><th>Status</th><th>Backlinks</th><th>Created</th><th>Actions</th></tr></thead>
                            <tbody>
                                {recentCampaigns.map((campaign) => (
                                    <tr key={campaign.id}>
                                        <td style={{ fontWeight: 500 }}>{campaign.name}</td>
                                        <td><span className={`bp-badge ${campaign.status === 'active' ? 'bp-badge-active' : campaign.status === 'paused' ? 'bp-badge-paused' : 'bp-badge-pending'}`}>{campaign.status}</span></td>
                                        <td>{campaign.backlinks_count || 0}</td>
                                        <td style={{ color: 'var(--bp-text-muted)' }}>{new Date(campaign.created_at).toLocaleDateString()}</td>
                                        <td>
                                            <Link href={`/campaign/${campaign.id}`} className="bp-table-link" style={{ marginRight: 12 }}>View</Link>
                                            <Link href={`/campaign/${campaign.id}/backlinks`} className="bp-table-link">Backlinks</Link>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}

            <div className="bp-table-card">
                <div className="bp-table-header">
                    <div><h3 className="bp-table-title">Recent Backlinks</h3><p className="bp-table-subtitle">Latest verified backlinks</p></div>
                </div>
                {recentBacklinks && recentBacklinks.length > 0 ? (
                    <div style={{ overflowX: 'auto' }}>
                        <table className="bp-table">
                            <thead><tr><th>Date</th><th>Campaign</th><th>Type</th><th>Status</th></tr></thead>
                            <tbody>
                                {recentBacklinks.map((backlink) => (
                                    <tr key={backlink.id}>
                                        <td style={{ color: 'var(--bp-text-muted)' }}>{new Date(backlink.created_at).toLocaleDateString()}</td>
                                        <td>{backlink.campaign?.name || 'N/A'}</td>
                                        <td style={{ textTransform: 'capitalize' }}>{backlink.type}</td>
                                        <td><span className={`bp-badge ${backlink.status === 'verified' ? 'bp-badge-verified' : backlink.status === 'pending' ? 'bp-badge-pending' : backlink.status === 'submitted' ? 'bp-badge-submitted' : 'bp-badge-failed'}`}>{backlink.status}</span></td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                ) : (
                    <div className="bp-empty-state">
                        <div className="bp-empty-icon"><i className="bi bi-link-45deg"></i></div>
                        <p>No backlinks yet. Create your first campaign to get started!</p>
                    </div>
                )}
            </div>
        </div>
    );

    /*
     * INNER TAB ICON MAPPING (emoji → bi-*)
     * 🔍 New SEO Audit  → bi-search
     * 📄 View Reports   → bi-file-earmark-text
     * ⚡ Performance     → bi-speedometer2
     * 📄 PDF Export      → bi-file-earmark-pdf
     * 📊 Pages CSV       → bi-table
     * ⚠️ Issues CSV      → bi-exclamation-triangle
     * 🔗 Links CSV       → bi-link-45deg
     * 🔑 Rank Tracking   → bi-key
     * 📊 Search Console  → bi-search
     * 📈 Analytics 4     → bi-graph-up-arrow
     * 📊 Data Warehouse  → bi-database
     * 👥 Cohort          → bi-people
     * 🧪 A/B Testing     → bi-sliders
     * 🔧 Fix Automation  → bi-wrench
     * 🔗 Backlink Strat  → bi-diagram-3
     * 👁️ Monitoring      → bi-eye
     * 👥 Client Portal   → bi-people
     * 📋 Deliverables    → bi-clipboard-check
     * ✅ Approvals       → bi-check-circle
     */

    const renderSeoAuditTab = () => (
        <div className="space-y-6">
            <Card>
                <h2 className="text-2xl font-bold text-gray-900 mb-4">SEO Audit</h2>
                <p className="text-gray-600 mb-6">Run comprehensive SEO audits for your websites</p>
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <Link href="/audit" className="block">
                        <Card className="hover:shadow-lg transition-shadow cursor-pointer">
                            <div className="text-center p-6">
                                <div className="bp-feature-icon blue"><i className="bi bi-search"></i></div>
                                <h3 className="text-lg font-semibold text-gray-900 mb-2">New SEO Audit</h3>
                                <p className="text-sm text-gray-600">Start a new SEO audit for any website</p>
                            </div>
                        </Card>
                    </Link>
                    <Link href="/Backlink/auditreport" className="block">
                        <Card className="hover:shadow-lg transition-shadow cursor-pointer">
                            <div className="text-center p-6">
                                <div className="bp-feature-icon purple"><i className="bi bi-file-earmark-text"></i></div>
                                <h3 className="text-lg font-semibold text-gray-900 mb-2">View Reports</h3>
                                <p className="text-sm text-gray-600">View and export audit reports</p>
                            </div>
                        </Card>
                    </Link>
                    <Link href="/audit" className="block">
                        <Card className="hover:shadow-lg transition-shadow cursor-pointer">
                            <div className="text-center p-6">
                                <div className="bp-feature-icon green"><i className="bi bi-speedometer2"></i></div>
                                <h3 className="text-lg font-semibold text-gray-900 mb-2">Performance</h3>
                                <p className="text-sm text-gray-600">Check website performance metrics</p>
                            </div>
                        </Card>
                    </Link>
                </div>
            </Card>
            <Card>
                <h3 className="text-xl font-bold text-gray-900 mb-4">Audit Reports & Exports</h3>
                <p className="text-gray-600 mb-6">Export your audit reports in various formats</p>
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div className="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                        <div className="bp-feature-icon-sm rose"><i className="bi bi-file-earmark-pdf"></i></div>
                        <h4 className="font-semibold text-gray-900 mb-1">PDF Export</h4>
                        <p className="text-sm text-gray-600 mb-3">Export complete audit as PDF</p>
                        <p className="text-xs text-gray-500">Available on audit detail page</p>
                    </div>
                    <div className="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                        <div className="bp-feature-icon-sm blue"><i className="bi bi-table"></i></div>
                        <h4 className="font-semibold text-gray-900 mb-1">Pages CSV</h4>
                        <p className="text-sm text-gray-600 mb-3">Export all crawled pages data</p>
                        <p className="text-xs text-gray-500">/audit/{'{id}'}/export/pages.csv</p>
                    </div>
                    <div className="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                        <div className="bp-feature-icon-sm amber"><i className="bi bi-exclamation-triangle"></i></div>
                        <h4 className="font-semibold text-gray-900 mb-1">Issues CSV</h4>
                        <p className="text-sm text-gray-600 mb-3">Export all SEO issues found</p>
                        <p className="text-xs text-gray-500">/audit/{'{id}'}/export/issues.csv</p>
                    </div>
                    <div className="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                        <div className="bp-feature-icon-sm green"><i className="bi bi-link-45deg"></i></div>
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
                                <div className="bp-feature-icon amber"><i className="bi bi-key"></i></div>
                                <h3 className="text-lg font-semibold text-gray-900 mb-2">Rank Tracking</h3>
                                <p className="text-sm text-gray-600">Track keyword rankings</p>
                            </div>
                        </Card>
                    </Link>
                    <Link href="/domains" className="block">
                        <Card className="hover:shadow-lg transition-shadow cursor-pointer">
                            <div className="text-center p-6">
                                <div className="bp-feature-icon blue"><i className="bi bi-search"></i></div>
                                <h3 className="text-lg font-semibold text-gray-900 mb-2">Google Search Console</h3>
                                <p className="text-sm text-gray-600">View GSC data and insights</p>
                            </div>
                        </Card>
                    </Link>
                    <Link href="/domains" className="block">
                        <Card className="hover:shadow-lg transition-shadow cursor-pointer">
                            <div className="text-center p-6">
                                <div className="bp-feature-icon green"><i className="bi bi-graph-up-arrow"></i></div>
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
                                <div className="bp-feature-icon purple"><i className="bi bi-database"></i></div>
                                <h3 className="text-lg font-semibold text-gray-900 mb-2">Data Warehouse</h3>
                                <p className="text-sm text-gray-600">View data warehouse analytics</p>
                            </div>
                        </Card>
                    </Link>
                    <Link href="/reports" className="block">
                        <Card className="hover:shadow-lg transition-shadow cursor-pointer">
                            <div className="text-center p-6">
                                <div className="bp-feature-icon blue"><i className="bi bi-people"></i></div>
                                <h3 className="text-lg font-semibold text-gray-900 mb-2">Cohort Analysis</h3>
                                <p className="text-sm text-gray-600">User activation and retention</p>
                            </div>
                        </Card>
                    </Link>
                    <Link href="/reports" className="block">
                        <Card className="hover:shadow-lg transition-shadow cursor-pointer">
                            <div className="text-center p-6">
                                <div className="bp-feature-icon green"><i className="bi bi-sliders"></i></div>
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
                                <div className="bp-feature-icon amber"><i className="bi bi-wrench"></i></div>
                                <h3 className="text-lg font-semibold text-gray-900 mb-2">Fix Automation</h3>
                                <p className="text-sm text-gray-600">Automated code fixes and patches</p>
                            </div>
                        </Card>
                    </Link>
                    <Link href="/domains" className="block">
                        <Card className="hover:shadow-lg transition-shadow cursor-pointer">
                            <div className="text-center p-6">
                                <div className="bp-feature-icon purple"><i className="bi bi-diagram-3"></i></div>
                                <h3 className="text-lg font-semibold text-gray-900 mb-2">Backlink Strategy</h3>
                                <p className="text-sm text-gray-600">Generate backlink campaigns</p>
                            </div>
                        </Card>
                    </Link>
                    <Link href="/domains" className="block">
                        <Card className="hover:shadow-lg transition-shadow cursor-pointer">
                            <div className="text-center p-6">
                                <div className="bp-feature-icon blue"><i className="bi bi-eye"></i></div>
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
                                <div className="bp-feature-icon blue"><i className="bi bi-people"></i></div>
                                <h3 className="text-lg font-semibold text-gray-900 mb-2">Client Portal</h3>
                                <p className="text-sm text-gray-600">Manage client projects</p>
                            </div>
                        </Card>
                    </Link>
                    <Link href="/domains" className="block">
                        <Card className="hover:shadow-lg transition-shadow cursor-pointer">
                            <div className="text-center p-6">
                                <div className="bp-feature-icon green"><i className="bi bi-clipboard-check"></i></div>
                                <h3 className="text-lg font-semibold text-gray-900 mb-2">Deliverables</h3>
                                <p className="text-sm text-gray-600">Track deliverables and SLAs</p>
                            </div>
                        </Card>
                    </Link>
                    <Link href="/domains" className="block">
                        <Card className="hover:shadow-lg transition-shadow cursor-pointer">
                            <div className="text-center p-6">
                                <div className="bp-feature-icon purple"><i className="bi bi-check-circle"></i></div>
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
        <AppLayout
            header="Dashboard Overview"
            subtitle="Monitor your backlink performance and campaigns"
            actions={dashboardActions}
        >
            <div className="bp-dash-tabs">
                {tabs.map((tab) => (
                    <button
                        key={tab.id}
                        onClick={() => setActiveTab(tab.id)}
                        className={`bp-dash-tab ${activeTab === tab.id ? 'active' : ''}`}
                    >
                        <i className={`bi ${tab.icon} bp-tab-icon`}></i>
                        <span>{tab.label}</span>
                    </button>
                ))}
            </div>
            {renderTabContent()}
        </AppLayout>
    );
}
