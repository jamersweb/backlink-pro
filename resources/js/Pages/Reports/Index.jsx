import { useState } from 'react';
import { router } from '@inertiajs/react';
import { LineChart, Line, BarChart, Bar, PieChart, Pie, Cell, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts';
import AppLayout from '../../Components/Layout/AppLayout';
import Card from '../../Components/Shared/Card';
import Input from '../../Components/Shared/Input';
import Button from '../../Components/Shared/Button';

export default function ReportsIndex({ overallStats, backlinksByType, backlinksByStatus, dailyBacklinks, campaignPerformance, filters }) {
    const [startDate, setStartDate] = useState(filters?.start_date || '');
    const [endDate, setEndDate] = useState(filters?.end_date || '');

    const handleDateFilter = () => {
        router.get('/reports', { start_date: startDate, end_date: endDate });
    };

    // Prepare chart data
    const dailyBacklinksData = dailyBacklinks?.map(item => ({
        date: new Date(item.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }),
        count: item.count,
    })) || [];

    const backlinksByTypeData = backlinksByType ? Object.entries(backlinksByType).map(([type, count]) => ({
        name: type.charAt(0).toUpperCase() + type.slice(1),
        value: count,
    })) : [];

    const backlinksByStatusData = backlinksByStatus ? Object.entries(backlinksByStatus).map(([status, count]) => ({
        name: status.charAt(0).toUpperCase() + status.slice(1),
        value: count,
    })) : [];

    const COLORS = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6'];
    const STATUS_COLORS = {
        verified: '#10B981',
        pending: '#F59E0B',
        submitted: '#3B82F6',
        error: '#EF4444',
    };

    return (
        <AppLayout header="Reports & Analytics">
            <div className="space-y-6">
                {/* Date Filter */}
                <Card title="Date Range">
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <Input
                            label="Start Date"
                            type="date"
                            value={startDate}
                            onChange={(e) => setStartDate(e.target.value)}
                        />
                        <Input
                            label="End Date"
                            type="date"
                            value={endDate}
                            onChange={(e) => setEndDate(e.target.value)}
                        />
                        <div className="flex items-end gap-2">
                            <Button variant="primary" onClick={handleDateFilter} className="flex-1">
                                Apply Filter
                            </Button>
                            <Button
                                variant="secondary"
                                onClick={() => {
                                    const params = new URLSearchParams({
                                        start_date: startDate,
                                        end_date: endDate,
                                        format: 'csv',
                                    });
                                    window.location.href = `/reports/export?${params.toString()}`;
                                }}
                                className="flex-1"
                            >
                                📥 CSV
                            </Button>
                            <Button
                                variant="secondary"
                                onClick={() => {
                                    const params = new URLSearchParams({
                                        start_date: startDate,
                                        end_date: endDate,
                                        format: 'json',
                                    });
                                    window.location.href = `/reports/export?${params.toString()}`;
                                }}
                                className="flex-1"
                            >
                                📥 JSON
                            </Button>
                        </div>
                    </div>
                </Card>

                {/* Overall Stats */}
                <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-6">
                    <Card>
                        <div className="text-center">
                            <div className="text-2xl font-bold text-gray-900">{overallStats.total_campaigns || 0}</div>
                            <div className="text-sm text-gray-500">Total Campaigns</div>
                        </div>
                    </Card>
                    <Card>
                        <div className="text-center">
                            <div className="text-2xl font-bold text-green-600">{overallStats.active_campaigns || 0}</div>
                            <div className="text-sm text-gray-500">Active</div>
                        </div>
                    </Card>
                    <Card>
                        <div className="text-center">
                            <div className="text-2xl font-bold text-gray-900">{overallStats.total_backlinks || 0}</div>
                            <div className="text-sm text-gray-500">Total Backlinks</div>
                        </div>
                    </Card>
                    <Card>
                        <div className="text-center">
                            <div className="text-2xl font-bold text-green-600">{overallStats.verified_backlinks || 0}</div>
                            <div className="text-sm text-gray-500">Verified</div>
                        </div>
                    </Card>
                    <Card>
                        <div className="text-center">
                            <div className="text-2xl font-bold text-yellow-600">{overallStats.pending_backlinks || 0}</div>
                            <div className="text-sm text-gray-500">Pending</div>
                        </div>
                    </Card>
                    <Card>
                        <div className="text-center">
                            <div className="text-2xl font-bold text-red-600">{overallStats.error_backlinks || 0}</div>
                            <div className="text-sm text-gray-500">Errors</div>
                        </div>
                    </Card>
                </div>

                {/* Daily Backlinks Chart */}
                <Card className="bg-white border border-gray-200 shadow-md">
                    <h3 className="text-lg font-bold text-gray-900 mb-4">Daily Backlinks Created</h3>
                    {dailyBacklinksData.length > 0 ? (
                        <ResponsiveContainer width="100%" height={300}>
                            <LineChart data={dailyBacklinksData}>
                                <CartesianGrid strokeDasharray="3 3" />
                                <XAxis dataKey="date" />
                                <YAxis />
                                <Tooltip />
                                <Legend />
                                <Line type="monotone" dataKey="count" stroke="#3B82F6" strokeWidth={2} name="Backlinks" />
                            </LineChart>
                        </ResponsiveContainer>
                    ) : (
                        <div className="text-center py-12 text-gray-500">
                            No data available for the selected date range
                        </div>
                    )}
                </Card>

                {/* Charts Section */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Backlinks by Type - Pie Chart */}
                    <Card className="bg-white border border-gray-200 shadow-md">
                        <h3 className="text-lg font-bold text-gray-900 mb-4">Backlinks by Type</h3>
                        {backlinksByTypeData.length > 0 ? (
                            <div>
                                <ResponsiveContainer width="100%" height={300}>
                                    <PieChart>
                                        <Pie
                                            data={backlinksByTypeData}
                                            cx="50%"
                                            cy="50%"
                                            labelLine={false}
                                            label={({ name, percent }) => `${name} ${(percent * 100).toFixed(0)}%`}
                                            outerRadius={80}
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
                            <div className="text-center py-12 text-gray-500">No data available</div>
                        )}
                    </Card>

                    {/* Backlinks by Status - Pie Chart */}
                    <Card className="bg-white border border-gray-200 shadow-md">
                        <h3 className="text-lg font-bold text-gray-900 mb-4">Backlinks by Status</h3>
                        {backlinksByStatusData.length > 0 ? (
                            <div>
                                <ResponsiveContainer width="100%" height={300}>
                                    <PieChart>
                                        <Pie
                                            data={backlinksByStatusData}
                                            cx="50%"
                                            cy="50%"
                                            labelLine={false}
                                            label={({ name, percent }) => `${name} ${(percent * 100).toFixed(0)}%`}
                                            outerRadius={80}
                                            fill="#8884d8"
                                            dataKey="value"
                                        >
                                            {backlinksByStatusData.map((entry, index) => (
                                                <Cell key={`cell-${index}`} fill={STATUS_COLORS[entry.name.toLowerCase()] || COLORS[index % COLORS.length]} />
                                            ))}
                                        </Pie>
                                        <Tooltip />
                                    </PieChart>
                                </ResponsiveContainer>
                                <div className="mt-4 space-y-2">
                                    {backlinksByStatusData.map((item, index) => (
                                        <div key={item.name} className="flex items-center justify-between text-sm">
                                            <div className="flex items-center gap-2">
                                                <div 
                                                    className="w-3 h-3 rounded-full" 
                                                    style={{ backgroundColor: STATUS_COLORS[item.name.toLowerCase()] || COLORS[index % COLORS.length] }}
                                                ></div>
                                                <span className="text-gray-700">{item.name}</span>
                                            </div>
                                            <span className="font-semibold text-gray-900">{item.value}</span>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        ) : (
                            <div className="text-center py-12 text-gray-500">No data available</div>
                        )}
                    </Card>
                </div>

                {/* Campaign Performance */}
                <Card className="bg-white border border-gray-200 shadow-md">
                    <h3 className="text-lg font-bold text-gray-900 mb-4">Top Campaigns Performance</h3>
                    {campaignPerformance && campaignPerformance.length > 0 ? (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Campaign</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Verified</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Success Rate</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {campaignPerformance.map((campaign) => (
                                        <tr key={campaign.id}>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                {campaign.name}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <span className={`px-2 py-1 text-xs font-semibold rounded-full ${
                                                    campaign.status === 'active' ? 'bg-green-100 text-green-800' :
                                                    campaign.status === 'paused' ? 'bg-yellow-100 text-yellow-800' :
                                                    'bg-gray-100 text-gray-800'
                                                }`}>
                                                    {campaign.status}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {campaign.total_backlinks}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {campaign.verified_backlinks}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {campaign.success_rate}%
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ) : (
                        <p className="text-gray-500 text-center py-8">No campaign data available</p>
                    )}
                </Card>
            </div>
        </AppLayout>
    );
}

