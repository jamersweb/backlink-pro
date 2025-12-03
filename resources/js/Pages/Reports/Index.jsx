import { useState } from 'react';
import AppLayout from '../../Components/Layout/AppLayout';
import Card from '../../Components/Shared/Card';
import Input from '../../Components/Shared/Input';
import Button from '../../Components/Shared/Button';

export default function ReportsIndex({ overallStats, backlinksByType, backlinksByStatus, dailyBacklinks, campaignPerformance, filters }) {
    const [startDate, setStartDate] = useState(filters?.start_date || '');
    const [endDate, setEndDate] = useState(filters?.end_date || '');

    const handleDateFilter = () => {
        window.location.href = `/reports?start_date=${startDate}&end_date=${endDate}`;
    };

    return (
        <AppLayout header="Reports & Analytics">
            <div className="space-y-6">
                {/* Date Filter */}
                <Card title="Date Range">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
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
                        <div className="flex items-end">
                            <Button variant="primary" onClick={handleDateFilter} className="w-full">
                                Apply Filter
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

                {/* Charts Section */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Backlinks by Type */}
                    <Card title="Backlinks by Type">
                        <div className="space-y-3">
                            {backlinksByType && Object.keys(backlinksByType).length > 0 ? (
                                Object.entries(backlinksByType).map(([type, count]) => (
                                    <div key={type} className="flex items-center justify-between">
                                        <span className="text-sm font-medium text-gray-700 capitalize">{type}</span>
                                        <div className="flex items-center gap-3">
                                            <div className="w-32 bg-gray-200 rounded-full h-2">
                                                <div
                                                    className="bg-indigo-600 h-2 rounded-full"
                                                    style={{ width: `${(count / overallStats.total_backlinks) * 100}%` }}
                                                />
                                            </div>
                                            <span className="text-sm font-semibold text-gray-900 w-8 text-right">{count}</span>
                                        </div>
                                    </div>
                                ))
                            ) : (
                                <p className="text-gray-500 text-center py-4">No data available</p>
                            )}
                        </div>
                    </Card>

                    {/* Backlinks by Status */}
                    <Card title="Backlinks by Status">
                        <div className="space-y-3">
                            {backlinksByStatus && Object.keys(backlinksByStatus).length > 0 ? (
                                Object.entries(backlinksByStatus).map(([status, count]) => (
                                    <div key={status} className="flex items-center justify-between">
                                        <span className="text-sm font-medium text-gray-700 capitalize">{status}</span>
                                        <div className="flex items-center gap-3">
                                            <div className="w-32 bg-gray-200 rounded-full h-2">
                                                <div
                                                    className={`h-2 rounded-full ${
                                                        status === 'verified' ? 'bg-green-600' :
                                                        status === 'pending' ? 'bg-yellow-600' :
                                                        status === 'submitted' ? 'bg-blue-600' :
                                                        'bg-red-600'
                                                    }`}
                                                    style={{ width: `${(count / overallStats.total_backlinks) * 100}%` }}
                                                />
                                            </div>
                                            <span className="text-sm font-semibold text-gray-900 w-8 text-right">{count}</span>
                                        </div>
                                    </div>
                                ))
                            ) : (
                                <p className="text-gray-500 text-center py-4">No data available</p>
                            )}
                        </div>
                    </Card>
                </div>

                {/* Campaign Performance */}
                <Card title="Top Campaigns Performance">
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

