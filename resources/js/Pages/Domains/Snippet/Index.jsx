import { router } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Components/Layout/AppLayout';
import Card from '@/Components/Shared/Card';
import Button from '@/Components/Shared/Button';

export default function SnippetIndex({ domain, installation, topPages, chartData, performance }) {
    const [settings, setSettings] = useState({
        tracking: installation?.settings_json?.tracking ?? true,
        performance: installation?.settings_json?.performance ?? false,
    });

    const handleSettingsUpdate = () => {
        router.post(`/domains/${domain.id}/snippet/settings`, settings);
    };

    const handleVerify = () => {
        router.post(`/domains/${domain.id}/snippet/verify`);
    };

    const handleRefresh = () => {
        router.post(`/domains/${domain.id}/snippet/refresh`);
    };

    const copySnippet = () => {
        const snippet = `<script src="${window.location.origin}/snippet/${domain.meta_snippet_key}.js" async></script>`;
        navigator.clipboard.writeText(snippet);
        alert('Snippet code copied to clipboard!');
    };

    return (
        <AppLayout header="Snippet Agent">
            <div className="space-y-6">
                {/* Installation Status */}
                <Card>
                    <div className="p-6">
                        <h3 className="text-lg font-semibold mb-4">Installation Status</h3>
                        <div className="space-y-3">
                            <div className="flex justify-between">
                                <span className="text-gray-600">Status:</span>
                                <span className={`px-2 py-1 text-xs font-semibold rounded-full ${
                                    installation?.status === 'verified' ? 'bg-green-100 text-green-800' :
                                    installation?.status === 'error' ? 'bg-red-100 text-red-800' :
                                    'bg-gray-100 text-gray-800'
                                }`}>
                                    {installation?.status || 'Unknown'}
                                </span>
                            </div>
                            {installation?.first_seen_at && (
                                <div className="flex justify-between">
                                    <span className="text-gray-600">First Seen:</span>
                                    <span className="text-gray-900">{new Date(installation.first_seen_at).toLocaleString()}</span>
                                </div>
                            )}
                            {installation?.last_seen_at && (
                                <div className="flex justify-between">
                                    <span className="text-gray-600">Last Seen:</span>
                                    <span className="text-gray-900">{new Date(installation.last_seen_at).toLocaleString()}</span>
                                </div>
                            )}
                            {installation?.last_origin_host && (
                                <div className="flex justify-between">
                                    <span className="text-gray-600">Last Origin:</span>
                                    <span className="text-gray-900 font-mono text-sm">{installation.last_origin_host}</span>
                                </div>
                            )}
                            {installation?.agent_version && (
                                <div className="flex justify-between">
                                    <span className="text-gray-600">Agent Version:</span>
                                    <span className="text-gray-900">{installation.agent_version}</span>
                                </div>
                            )}
                        </div>
                        <div className="mt-4 flex gap-3">
                            <Button variant="outline" onClick={handleVerify}>Send Verify Command</Button>
                            <Button variant="outline" onClick={handleRefresh}>Refresh Meta Now</Button>
                        </div>
                    </div>
                </Card>

                {/* Snippet Code */}
                <Card>
                    <div className="p-6">
                        <h3 className="text-lg font-semibold mb-4">Installation Code</h3>
                        <div className="bg-gray-50 p-4 rounded border font-mono text-sm">
                            {`<script src="${window.location.origin}/snippet/${domain.meta_snippet_key}.js" async></script>`}
                        </div>
                        <Button variant="outline" onClick={copySnippet} className="mt-3">Copy Code</Button>
                    </div>
                </Card>

                {/* Settings */}
                <Card>
                    <div className="p-6">
                        <h3 className="text-lg font-semibold mb-4">Settings</h3>
                        <div className="space-y-3">
                            <label className="flex items-center">
                                <input
                                    type="checkbox"
                                    checked={settings.tracking}
                                    onChange={(e) => setSettings({...settings, tracking: e.target.checked})}
                                    className="mr-2"
                                />
                                <span className="text-sm text-gray-700">Enable pageview tracking</span>
                            </label>
                            <label className="flex items-center">
                                <input
                                    type="checkbox"
                                    checked={settings.performance}
                                    onChange={(e) => setSettings({...settings, performance: e.target.checked})}
                                    className="mr-2"
                                />
                                <span className="text-sm text-gray-700">Enable performance metrics</span>
                            </label>
                        </div>
                        <Button variant="primary" onClick={handleSettingsUpdate} className="mt-4">Save Settings</Button>
                    </div>
                </Card>

                {/* Top Pages */}
                <Card>
                    <div className="p-6">
                        <h3 className="text-lg font-semibold mb-4">Top Pages (Last 7 Days)</h3>
                        {topPages && topPages.length > 0 ? (
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Path</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Views</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Uniques</th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                        {topPages.map((page, idx) => (
                                            <tr key={idx}>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm font-mono">{page.path}</td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm">{page.total_views}</td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm">{page.total_uniques}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        ) : (
                            <p className="text-sm text-gray-500">No pageview data yet</p>
                        )}
                    </div>
                </Card>

                {/* Performance */}
                {settings.performance && performance && performance.length > 0 && (
                    <Card>
                        <div className="p-6">
                            <h3 className="text-lg font-semibold mb-4">Performance Metrics (Last 7 Days)</h3>
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Path</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Avg Load (ms)</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Avg TTFB (ms)</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Samples</th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                        {performance.map((perf, idx) => (
                                            <tr key={idx}>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm font-mono">{perf.path}</td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm">{perf.avg_load ? Math.round(perf.avg_load) : '-'}</td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm">{perf.avg_ttfb ? Math.round(perf.avg_ttfb) : '-'}</td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm">{perf.samples}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}


