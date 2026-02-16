import { useState } from 'react';
import AdminLayout from '@/Components/Layout/AdminLayout';
import Card from '@/Components/Shared/Card';
import Button from '@/Components/Shared/Button';
import Input from '@/Components/Shared/Input';
import { router, usePage } from '@inertiajs/react';

export default function AdminCaptchaLogsIndex({ logs, stats, campaigns, users, filters = {} }) {
    const { flash } = usePage().props;
    const [search, setSearch] = useState(filters.search || '');
    const [statusFilter, setStatusFilter] = useState(filters.status || '');
    const [typeFilter, setTypeFilter] = useState(filters.captcha_type || '');
    const [serviceFilter, setServiceFilter] = useState(filters.service || '');
    const [campaignFilter, setCampaignFilter] = useState(filters.campaign_id || '');
    const [userFilter, setUserFilter] = useState(filters.user_id || '');
    const [dateFrom, setDateFrom] = useState(filters.date_from || '');
    const [dateTo, setDateTo] = useState(filters.date_to || '');

    const handleFilter = () => {
        router.get('/admin/captcha-logs', {
            search: search || undefined,
            status: statusFilter || undefined,
            captcha_type: typeFilter || undefined,
            service: serviceFilter || undefined,
            campaign_id: campaignFilter || undefined,
            user_id: userFilter || undefined,
            date_from: dateFrom || undefined,
            date_to: dateTo || undefined,
        }, {
            preserveState: true,
            replace: true,
        });
    };

    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
            minimumFractionDigits: 4,
        }).format(amount || 0);
    };

    return (
        <AdminLayout header="Captcha Usage Dashboard">
            <div className="space-y-6">
                {/* Success Message */}
                {flash?.success && (
                    <div className="p-4 rounded-lg bg-[#12B76A]/10 border border-[#12B76A]/30">
                        <p className="text-sm text-[#12B76A] font-medium">{flash.success}</p>
                    </div>
                )}

                {/* Stats Cards - Dashboard-like 4×2 grid */}
                <div className="stats-grid stats-grid-captcha">
                    <div className="stat-card">
                        <div>
                            <p className="stat-title">Total</p>
                            <p className="stat-value">{stats?.total || 0}</p>
                        </div>
                        <div className="stat-iconWrap stat-iconWrap-neutral">
                            <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                        </div>
                    </div>
                    <div className="stat-card">
                        <div>
                            <p className="stat-title">Solved</p>
                            <p className="stat-value stat-value-success">{stats?.solved || 0}</p>
                        </div>
                        <div className="stat-iconWrap stat-iconWrap-success">
                            <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                    </div>
                    <div className="stat-card">
                        <div>
                            <p className="stat-title">Failed</p>
                            <p className="stat-value stat-value-danger">{stats?.failed || 0}</p>
                        </div>
                        <div className="stat-iconWrap stat-iconWrap-danger">
                            <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                    </div>
                    <div className="stat-card">
                        <div>
                            <p className="stat-title">Pending</p>
                            <p className="stat-value stat-value-warning">{stats?.pending || 0}</p>
                        </div>
                        <div className="stat-iconWrap stat-iconWrap-warning">
                            <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                    </div>
                    <div className="stat-card">
                        <div>
                            <p className="stat-title">Total Cost</p>
                            <p className="stat-value stat-value-info text-xl">{formatCurrency(stats?.total_cost || 0)}</p>
                        </div>
                        <div className="stat-iconWrap stat-iconWrap-info">
                            <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                    </div>
                    <div className="stat-card">
                        <div>
                            <p className="stat-title">Today</p>
                            <p className="stat-value text-xl">{formatCurrency(stats?.today_cost || 0)}</p>
                        </div>
                        <div className="stat-iconWrap stat-iconWrap-neutral">
                            <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        </div>
                    </div>
                    <div className="stat-card">
                        <div>
                            <p className="stat-title">This Week</p>
                            <p className="stat-value text-xl">{formatCurrency(stats?.this_week_cost || 0)}</p>
                        </div>
                        <div className="stat-iconWrap stat-iconWrap-neutral">
                            <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                        </div>
                    </div>
                    <div className="stat-card">
                        <div>
                            <p className="stat-title">This Month</p>
                            <p className="stat-value text-xl">{formatCurrency(stats?.this_month_cost || 0)}</p>
                        </div>
                        <div className="stat-iconWrap stat-iconWrap-neutral">
                            <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        </div>
                    </div>
                </div>

                {/* Filters */}
                <Card variant="elevated">
                    <div className="p-4">
                        <div className="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-8 gap-4">
                            <div className="md:col-span-2">
                                <Input
                                    type="text"
                                    placeholder="Search domain, order ID..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    onKeyPress={(e) => e.key === 'Enter' && handleFilter()}
                                />
                            </div>
                            <div>
                                <select
                                    value={statusFilter}
                                    onChange={(e) => setStatusFilter(e.target.value)}
                                    className="admin-select w-full px-3 py-2 rounded-lg"
                                >
                                    <option value="">All Statuses</option>
                                    <option value="pending">Pending</option>
                                    <option value="solved">Solved</option>
                                    <option value="failed">Failed</option>
                                </select>
                            </div>
                            <div>
                                <select
                                    value={typeFilter}
                                    onChange={(e) => setTypeFilter(e.target.value)}
                                    className="admin-select w-full px-3 py-2 rounded-lg"
                                >
                                    <option value="">All Types</option>
                                    <option value="image">Image</option>
                                    <option value="recaptcha_v2">reCAPTCHA v2</option>
                                    <option value="recaptcha_invisible">reCAPTCHA Invisible</option>
                                    <option value="hcaptcha">hCaptcha</option>
                                </select>
                            </div>
                            <div>
                                <select
                                    value={serviceFilter}
                                    onChange={(e) => setServiceFilter(e.target.value)}
                                    className="admin-select w-full px-3 py-2 rounded-lg"
                                >
                                    <option value="">All Services</option>
                                    <option value="2captcha">2Captcha</option>
                                    <option value="anticaptcha">AntiCaptcha</option>
                                </select>
                            </div>
                            <div>
                                <select
                                    value={campaignFilter}
                                    onChange={(e) => setCampaignFilter(e.target.value)}
                                    className="admin-select w-full px-3 py-2 rounded-lg"
                                >
                                    <option value="">All Campaigns</option>
                                    {campaigns?.map((campaign) => (
                                        <option key={campaign.id} value={campaign.id}>
                                            {campaign.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <Input
                                    type="date"
                                    placeholder="From Date"
                                    value={dateFrom}
                                    onChange={(e) => setDateFrom(e.target.value)}
                                />
                            </div>
                            <div>
                                <Input
                                    type="date"
                                    placeholder="To Date"
                                    value={dateTo}
                                    onChange={(e) => setDateTo(e.target.value)}
                                />
                            </div>
                        </div>
                        <div className="mt-4">
                            <Button variant="primary" onClick={handleFilter} className="w-full md:w-auto">
                                🔍 Filter
                            </Button>
                        </div>
                    </div>
                </Card>

                {/* Logs Table */}
                <Card variant="elevated">
                    {logs?.data && logs.data.length > 0 ? (
                        <div className="overflow-x-auto">
                            <table className="admin-table min-w-full">
                                <thead>
                                    <tr>
                                        <th>Campaign</th>
                                        <th>User</th>
                                        <th>Site Domain</th>
                                        <th>Type</th>
                                        <th>Service</th>
                                        <th>Status</th>
                                        <th>Cost</th>
                                        <th>Order ID</th>
                                        <th>Created</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {logs.data.map((log) => (
                                        <tr key={log.id}>
                                            <td className="whitespace-nowrap">
                                                <a href={`/admin/campaigns/${log.campaign_id}`} className="admin-link text-sm">
                                                    {log.campaign?.name || 'N/A'}
                                                </a>
                                            </td>
                                            <td className="whitespace-nowrap text-sm text-[var(--admin-text)]">
                                                {log.campaign?.user?.name || 'N/A'}
                                            </td>
                                            <td className="whitespace-nowrap text-sm text-[var(--admin-text-muted)]">{log.site_domain}</td>
                                            <td className="whitespace-nowrap text-sm text-[var(--admin-text-muted)] capitalize">{log.captcha_type}</td>
                                            <td className="whitespace-nowrap text-sm text-[var(--admin-text-muted)]">{log.service}</td>
                                            <td className="whitespace-nowrap">
                                                <span className={`admin-badge ${
                                                    log.status === 'solved' ? 'admin-badge-success' :
                                                    log.status === 'failed' ? 'admin-badge-danger' :
                                                    'admin-badge-warning'
                                                }`}>
                                                    {log.status}
                                                </span>
                                            </td>
                                            <td className="whitespace-nowrap text-sm font-semibold text-[var(--admin-text)]">
                                                {formatCurrency(log.estimated_cost || 0)}
                                            </td>
                                            <td className="whitespace-nowrap text-sm text-[var(--admin-text-muted)]">{log.order_id || '-'}</td>
                                            <td className="whitespace-nowrap text-sm text-[var(--admin-text-muted)]">
                                                {new Date(log.created_at).toLocaleDateString()}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ) : (
                        <div className="text-center py-16">
                            <div className="inline-block p-6 rounded-full mb-4 bg-[var(--admin-hover-bg)]">
                                <span className="text-5xl">🧩</span>
                            </div>
                            <p className="text-[var(--admin-text)] font-medium">No captcha logs found</p>
                            <p className="text-[var(--admin-text-muted)] text-sm mt-2">Captcha logs will appear here once captchas are solved</p>
                        </div>
                    )}

                    {/* Pagination */}
                    {logs?.links && logs.links.length > 3 && (
                        <div className="px-6 py-4 border-t border-[var(--admin-border)] bg-[var(--admin-surface-2)]">
                            <div className="flex flex-col sm:flex-row items-center justify-between gap-4">
                                <div className="text-sm text-[var(--admin-text-muted)]">
                                    Showing <span className="font-medium text-[var(--admin-text)]">{logs.from || 0}</span> to <span className="font-medium text-[var(--admin-text)]">{logs.to || 0}</span> of <span className="font-medium text-[var(--admin-text)]">{logs.total || 0}</span> results
                                </div>
                                <div className="flex flex-wrap gap-2">
                                    {logs.links.map((link, index) => (
                                        <a
                                            key={index}
                                            href={link.url || '#'}
                                            className={`px-3 py-2 text-sm font-medium rounded-lg transition-colors ${
                                                link.active ? 'bg-[var(--admin-primary)] text-white' : 'bg-[var(--admin-surface)] text-[var(--admin-text)] hover:bg-[var(--admin-hover-bg)] border border-[var(--admin-border)]'
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

