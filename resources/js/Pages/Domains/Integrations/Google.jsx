import { Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts';
import AppLayout from '@/Components/Layout/AppLayout';
import Card from '@/Components/Shared/Card';
import Button from '@/Components/Shared/Button';
import Select from '@/Components/Shared/Select';

export default function GoogleIntegrations({ domain, integration, selectable, metrics, top }) {
    const { data, setData, post, processing } = useForm({
        gsc_property: integration?.gsc_property || '',
        ga4_property_id: integration?.ga4_property_id || '',
    });

    const handleSave = (e) => {
        e.preventDefault();
        post(`/domains/${domain.id}/integrations/google/save`, {
            preserveScroll: true,
        });
    };

    const handleSyncNow = () => {
        router.post(`/domains/${domain.id}/integrations/google/sync-now`, {}, {
            preserveScroll: true,
        });
    };

    const handleDisconnect = () => {
        if (confirm('Are you sure you want to disconnect this integration?')) {
            router.post(`/domains/${domain.id}/integrations/google/disconnect`, {}, {
                preserveScroll: true,
            });
        }
    };

    const getStatusBadge = (status) => {
        const colors = {
            connected: 'bg-green-100 text-green-800',
            error: 'bg-red-100 text-red-800',
            disconnected: 'bg-gray-100 text-gray-800',
        };
        return (
            <span className={`px-2 py-1 text-xs font-semibold rounded-full ${colors[status] || colors.disconnected}`}>
                {status}
            </span>
        );
    };

    // Prepare chart data
    const gscChartData = metrics?.gscDaily?.map(item => ({
        date: new Date(item.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }),
        clicks: item.clicks,
        impressions: item.impressions,
    })) || [];

    const ga4ChartData = metrics?.ga4Daily?.map(item => ({
        date: new Date(item.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }),
        sessions: item.sessions,
    })) || [];

    return (
        <AppLayout header="Google Integrations">
            <div className="space-y-6">
                {/* Breadcrumb */}
                <div className="flex items-center gap-2 text-sm text-gray-600">
                    <Link href="/domains" className="hover:text-gray-900">Domains</Link>
                    <span>/</span>
                    <Link href={`/domains/${domain.id}`} className="hover:text-gray-900">{domain.name}</Link>
                    <span>/</span>
                    <span className="text-gray-900">Integrations</span>
                </div>

                {/* Header */}
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">Google Integrations</h1>
                        <p className="text-sm text-gray-500 mt-1">Connect Google Search Console and GA4 for {domain.host || domain.name}</p>
                    </div>
                    {integration && integration.status === 'connected' && (
                        <div className="flex gap-2">
                            <Button variant="primary" onClick={handleSyncNow}>
                                🔄 Sync Now
                            </Button>
                            <Button variant="outline" onClick={handleDisconnect}>
                                Disconnect
                            </Button>
                        </div>
                    )}
                </div>

                {/* Connection Status */}
                {!integration ? (
                    <Card>
                        <div className="text-center py-12">
                            <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLineCap="round" strokeLineJoin="round" strokeWidth={2} d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 011-1h1a2 2 0 100-4H7a1 1 0 01-1-1V7a1 1 0 011-1h3a1 1 0 001-1V4z" />
                            </svg>
                            <h3 className="mt-2 text-sm font-medium text-gray-900">Not Connected</h3>
                            <p className="mt-1 text-sm text-gray-500">Connect your Google account to sync Search Console and GA4 data.</p>
                            <div className="mt-6">
                                <Link href={`/domains/${domain.id}/integrations/google/connect`}>
                                    <Button variant="primary">Connect Google (SEO)</Button>
                                </Link>
                            </div>
                        </div>
                    </Card>
                ) : (
                    <>
                        {/* Connection Info */}
                        <Card>
                            <div className="flex items-center justify-between mb-4">
                                <div>
                                    <h3 className="text-lg font-semibold text-gray-900">Connection Status</h3>
                                    <p className="text-sm text-gray-500 mt-1">
                                        Connected as: {integration.account_email}
                                    </p>
                                </div>
                                <div className="flex items-center gap-2">
                                    {getStatusBadge(integration.status)}
                                    {integration.last_synced_at && (
                                        <span className="text-sm text-gray-500">
                                            Last synced: {new Date(integration.last_synced_at).toLocaleString()}
                                        </span>
                                    )}
                                </div>
                            </div>
                        </Card>

                        {/* Property Selection */}
                        <Card>
                            <h3 className="text-lg font-semibold text-gray-900 mb-4">Property Selection</h3>
                            <form onSubmit={handleSave} className="space-y-4">
                                <Select
                                    label="Google Search Console Property"
                                    name="gsc_property"
                                    value={data.gsc_property}
                                    onChange={(e) => setData('gsc_property', e.target.value)}
                                >
                                    <option value="">Select a property...</option>
                                    {selectable?.gscSites?.map((site) => (
                                        <option key={site.siteUrl} value={site.siteUrl}>
                                            {site.siteUrl} ({site.permissionLevel})
                                        </option>
                                    ))}
                                </Select>

                                <Select
                                    label="GA4 Property"
                                    name="ga4_property_id"
                                    value={data.ga4_property_id}
                                    onChange={(e) => setData('ga4_property_id', e.target.value)}
                                >
                                    <option value="">Select a property...</option>
                                    {selectable?.ga4Properties?.map((prop) => (
                                        <option key={prop.propertyName} value={prop.propertyName}>
                                            {prop.displayName} ({prop.propertyName})
                                        </option>
                                    ))}
                                </Select>

                                <Button type="submit" variant="primary" disabled={processing}>
                                    {processing ? 'Saving...' : 'Save Properties'}
                                </Button>
                            </form>
                        </Card>

                        {/* Charts */}
                        {integration.gsc_property && gscChartData.length > 0 && (
                            <Card>
                                <h3 className="text-lg font-semibold text-gray-900 mb-4">Search Console - Last 28 Days</h3>
                                <ResponsiveContainer width="100%" height={300}>
                                    <LineChart data={gscChartData}>
                                        <CartesianGrid strokeDasharray="3 3" />
                                        <XAxis dataKey="date" />
                                        <YAxis yAxisId="left" />
                                        <YAxis yAxisId="right" orientation="right" />
                                        <Tooltip />
                                        <Legend />
                                        <Line yAxisId="left" type="monotone" dataKey="clicks" stroke="#3b82f6" name="Clicks" />
                                        <Line yAxisId="right" type="monotone" dataKey="impressions" stroke="#10b981" name="Impressions" />
                                    </LineChart>
                                </ResponsiveContainer>
                            </Card>
                        )}

                        {integration.ga4_property_id && ga4ChartData.length > 0 && (
                            <Card>
                                <h3 className="text-lg font-semibold text-gray-900 mb-4">GA4 Sessions - Last 28 Days</h3>
                                <ResponsiveContainer width="100%" height={300}>
                                    <LineChart data={ga4ChartData}>
                                        <CartesianGrid strokeDasharray="3 3" />
                                        <XAxis dataKey="date" />
                                        <YAxis />
                                        <Tooltip />
                                        <Legend />
                                        <Line type="monotone" dataKey="sessions" stroke="#8b5cf6" name="Sessions" />
                                    </LineChart>
                                </ResponsiveContainer>
                            </Card>
                        )}

                        {/* Top Pages */}
                        {top?.pages && top.pages.length > 0 && (
                            <Card>
                                <h3 className="text-lg font-semibold text-gray-900 mb-4">Top Pages (Latest Snapshot)</h3>
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200">
                                        <thead className="bg-gray-50">
                                            <tr>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Page</th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Clicks</th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Impressions</th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">CTR</th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Position</th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-white divide-y divide-gray-200">
                                            {top.pages.map((page, index) => (
                                                <tr key={index} className="hover:bg-gray-50">
                                                    <td className="px-6 py-4 text-sm">
                                                        <a href={page.page} target="_blank" rel="noopener noreferrer" className="text-blue-600 hover:underline truncate block max-w-md">
                                                            {page.page}
                                                        </a>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{page.clicks}</td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{page.impressions}</td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{(page.ctr * 100).toFixed(2)}%</td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{page.position?.toFixed(1)}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </Card>
                        )}

                        {/* Top Queries */}
                        {top?.queries && top.queries.length > 0 && (
                            <Card>
                                <h3 className="text-lg font-semibold text-gray-900 mb-4">Top Queries (Latest Snapshot)</h3>
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200">
                                        <thead className="bg-gray-50">
                                            <tr>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Query</th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Clicks</th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Impressions</th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">CTR</th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Position</th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-white divide-y divide-gray-200">
                                            {top.queries.map((query, index) => (
                                                <tr key={index} className="hover:bg-gray-50">
                                                    <td className="px-6 py-4 text-sm font-medium text-gray-900">{query.query}</td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{query.clicks}</td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{query.impressions}</td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{(query.ctr * 100).toFixed(2)}%</td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{query.position?.toFixed(1)}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </Card>
                        )}
                    </>
                )}
            </div>
        </AppLayout>
    );
}


