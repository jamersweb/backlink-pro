import Card from '@/Components/Shared/Card';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts';

export default function GoogleSection({ data }) {
    if (!data) return null;

    const gscTrend = data.gsc?.trend || [];
    const ga4Trend = data.ga4?.trend || [];

    return (
        <Card>
            <div className="p-6">
                <h2 className="text-xl font-bold text-gray-900 mb-4">Google Performance</h2>
                
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    {/* GSC Metrics */}
                    <div>
                        <h3 className="text-lg font-semibold text-gray-900 mb-3">Search Console</h3>
                        <div className="space-y-2">
                            <div className="flex justify-between">
                                <span className="text-gray-600">Clicks (28d)</span>
                                <span className="font-semibold">{data.gsc?.clicks?.toLocaleString() || 0}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-600">Impressions (28d)</span>
                                <span className="font-semibold">{data.gsc?.impressions?.toLocaleString() || 0}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-600">Average CTR</span>
                                <span className="font-semibold">{((data.gsc?.ctr || 0) * 100).toFixed(2)}%</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-600">Average Position</span>
                                <span className="font-semibold">{data.gsc?.position?.toFixed(1) || 'N/A'}</span>
                            </div>
                        </div>
                    </div>

                    {/* GA4 Metrics */}
                    <div>
                        <h3 className="text-lg font-semibold text-gray-900 mb-3">Analytics</h3>
                        <div className="space-y-2">
                            <div className="flex justify-between">
                                <span className="text-gray-600">Sessions (28d)</span>
                                <span className="font-semibold">{data.ga4?.sessions?.toLocaleString() || 0}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-600">Users (28d)</span>
                                <span className="font-semibold">{data.ga4?.users?.toLocaleString() || 0}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-600">Engagement Rate</span>
                                <span className="font-semibold">{((data.ga4?.engagement_rate || 0) * 100).toFixed(2)}%</span>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Charts */}
                {(gscTrend.length > 0 || ga4Trend.length > 0) && (
                    <div className="mt-6">
                        <h3 className="text-lg font-semibold text-gray-900 mb-4">Trends (Last 28 Days)</h3>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {gscTrend.length > 0 && (
                                <div>
                                    <h4 className="text-sm font-medium text-gray-700 mb-2">GSC Clicks</h4>
                                    <ResponsiveContainer width="100%" height={200}>
                                        <LineChart data={gscTrend}>
                                            <CartesianGrid strokeDasharray="3 3" />
                                            <XAxis dataKey="date" />
                                            <YAxis />
                                            <Tooltip />
                                            <Line type="monotone" dataKey="clicks" stroke="#3b82f6" strokeWidth={2} />
                                        </LineChart>
                                    </ResponsiveContainer>
                                </div>
                            )}
                            {ga4Trend.length > 0 && (
                                <div>
                                    <h4 className="text-sm font-medium text-gray-700 mb-2">GA4 Sessions</h4>
                                    <ResponsiveContainer width="100%" height={200}>
                                        <LineChart data={ga4Trend}>
                                            <CartesianGrid strokeDasharray="3 3" />
                                            <XAxis dataKey="date" />
                                            <YAxis />
                                            <Tooltip />
                                            <Line type="monotone" dataKey="sessions" stroke="#8b5cf6" strokeWidth={2} />
                                        </LineChart>
                                    </ResponsiveContainer>
                                </div>
                            )}
                        </div>
                    </div>
                )}
            </div>
        </Card>
    );
}


