import { useState } from 'react';
import AdminLayout from '../../../Components/Layout/AdminLayout';
import Card from '../../../Components/Shared/Card';
import Button from '../../../Components/Shared/Button';
import Input from '../../../Components/Shared/Input';
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
                    <div className="p-4 bg-green-50 border border-green-200 rounded-md">
                        <p className="text-sm text-green-800">{flash.success}</p>
                    </div>
                )}

                {/* Stats Cards */}
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-8">
                    <Card className="bg-white border border-gray-200 shadow-md">
                        <div className="p-4">
                            <p className="text-gray-600 text-xs font-medium mb-1">Total</p>
                            <p className="text-2xl font-bold text-gray-900">{stats?.total || 0}</p>
                        </div>
                    </Card>
                    <Card className="bg-white border border-green-200 shadow-md">
                        <div className="p-4">
                            <p className="text-green-600 text-xs font-medium mb-1">Solved</p>
                            <p className="text-2xl font-bold text-green-900">{stats?.solved || 0}</p>
                        </div>
                    </Card>
                    <Card className="bg-white border border-red-200 shadow-md">
                        <div className="p-4">
                            <p className="text-red-600 text-xs font-medium mb-1">Failed</p>
                            <p className="text-2xl font-bold text-red-900">{stats?.failed || 0}</p>
                        </div>
                    </Card>
                    <Card className="bg-white border border-yellow-200 shadow-md">
                        <div className="p-4">
                            <p className="text-yellow-600 text-xs font-medium mb-1">Pending</p>
                            <p className="text-2xl font-bold text-yellow-900">{stats?.pending || 0}</p>
                        </div>
                    </Card>
                    <Card className="bg-white border border-blue-200 shadow-md">
                        <div className="p-4">
                            <p className="text-blue-600 text-xs font-medium mb-1">Total Cost</p>
                            <p className="text-xl font-bold text-blue-900">{formatCurrency(stats?.total_cost || 0)}</p>
                        </div>
                    </Card>
                    <Card className="bg-white border border-gray-200 shadow-md">
                        <div className="p-4">
                            <p className="text-gray-600 text-xs font-medium mb-1">Today</p>
                            <p className="text-xl font-bold text-gray-900">{formatCurrency(stats?.today_cost || 0)}</p>
                        </div>
                    </Card>
                    <Card className="bg-white border border-gray-200 shadow-md">
                        <div className="p-4">
                            <p className="text-gray-600 text-xs font-medium mb-1">This Week</p>
                            <p className="text-xl font-bold text-gray-900">{formatCurrency(stats?.this_week_cost || 0)}</p>
                        </div>
                    </Card>
                    <Card className="bg-white border border-gray-200 shadow-md">
                        <div className="p-4">
                            <p className="text-gray-600 text-xs font-medium mb-1">This Month</p>
                            <p className="text-xl font-bold text-gray-900">{formatCurrency(stats?.this_month_cost || 0)}</p>
                        </div>
                    </Card>
                </div>

                {/* Filters */}
                <Card className="bg-white border border-gray-200 shadow-md">
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
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500"
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
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500"
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
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500"
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
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500"
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
                <Card className="bg-white border border-gray-200 shadow-md">
                    {logs?.data && logs.data.length > 0 ? (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Campaign</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">User</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Site Domain</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Type</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Service</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Status</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Cost</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Order ID</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Created</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {logs.data.map((log) => (
                                        <tr key={log.id} className="hover:bg-gray-50 transition-colors">
                                            <td className="px-4 py-3 whitespace-nowrap">
                                                <a href={`/admin/campaigns/${log.campaign_id}`} className="text-sm text-blue-600 hover:text-blue-900">
                                                    {log.campaign?.name || 'N/A'}
                                                </a>
                                            </td>
                                            <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                                {log.campaign?.user?.name || 'N/A'}
                                            </td>
                                            <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-600">{log.site_domain}</td>
                                            <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-600 capitalize">{log.captcha_type}</td>
                                            <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-600">{log.service}</td>
                                            <td className="px-4 py-3 whitespace-nowrap">
                                                <span className={`px-2 py-1 text-xs font-medium rounded-full ${
                                                    log.status === 'solved' ? 'bg-green-100 text-green-800' :
                                                    log.status === 'failed' ? 'bg-red-100 text-red-800' :
                                                    'bg-yellow-100 text-yellow-800'
                                                }`}>
                                                    {log.status}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 whitespace-nowrap text-sm font-semibold text-gray-900">
                                                {formatCurrency(log.estimated_cost || 0)}
                                            </td>
                                            <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-600">{log.order_id || '-'}</td>
                                            <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-600">
                                                {new Date(log.created_at).toLocaleDateString()}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ) : (
                        <div className="text-center py-16">
                            <div className="inline-block p-6 bg-gray-100 rounded-full mb-4">
                                <span className="text-5xl">🧩</span>
                            </div>
                            <p className="text-gray-500 font-medium">No captcha logs found</p>
                            <p className="text-gray-400 text-sm mt-2">Captcha logs will appear here once captchas are solved</p>
                        </div>
                    )}

                    {/* Pagination */}
                    {logs?.links && logs.links.length > 3 && (
                        <div className="px-6 py-4 border-t border-gray-200 bg-gray-50">
                            <div className="flex flex-col sm:flex-row items-center justify-between gap-4">
                                <div className="text-sm text-gray-700">
                                    Showing <span className="font-medium">{logs.from || 0}</span> to <span className="font-medium">{logs.to || 0}</span> of <span className="font-medium">{logs.total || 0}</span> results
                                </div>
                                <div className="flex flex-wrap gap-2">
                                    {logs.links.map((link, index) => (
                                        <a
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

