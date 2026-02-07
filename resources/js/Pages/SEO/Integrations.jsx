import { useState } from 'react';
import { Link, router, useForm } from '@inertiajs/react';
import AppLayout from '@/Components/Layout/AppLayout';
import Card from '@/Components/Shared/Card';
import Button from '@/Components/Shared/Button';

export default function SeoIntegrations({ organization, connection, gscSites, ga4Properties }) {
    const { data, setData, post, processing } = useForm({
        site_url: gscSites?.find(s => s.is_active)?.site_url || '',
        property_id: ga4Properties?.find(p => p.is_active)?.property_id || '',
    });

    const handleSelectGsc = (siteUrl) => {
        router.post(route('integrations.google.gsc-site', { organization: organization.id }), {
            site_url: siteUrl,
        }, {
            preserveScroll: true,
        });
    };

    const handleSelectGa4 = (propertyId) => {
        router.post(route('integrations.google.ga4-property', { organization: organization.id }), {
            property_id: propertyId,
        }, {
            preserveScroll: true,
        });
    };

    const handleConnect = () => {
        router.visit(route('integrations.google.connect', { organization: organization.id }));
    };

    const handleDisconnect = () => {
        if (confirm('Are you sure you want to disconnect your Google account?')) {
            router.post(route('integrations.google.disconnect', { organization: organization.id }), {}, {
                preserveScroll: true,
            });
        }
    };

    const getStatusBadge = (status) => {
        const colors = {
            active: 'bg-green-100 text-green-800',
            revoked: 'bg-red-100 text-red-800',
            error: 'bg-yellow-100 text-yellow-800',
        };
        return (
            <span className={`px-2 py-1 text-xs font-semibold rounded-full ${colors[status] || 'bg-gray-100 text-gray-800'}`}>
                {status?.toUpperCase()}
            </span>
        );
    };

    return (
        <AppLayout header={`Integrations - ${organization.name}`}>
            <div className="space-y-6">
                {/* Header */}
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">Google Integrations</h1>
                        <p className="text-sm text-gray-500 mt-1">Connect Google Search Console and GA4 to track SEO performance</p>
                    </div>
                    <Link href={route('seo.dashboard', { organization: organization.id })}>
                        <Button variant="outline">← Back to Dashboard</Button>
                    </Link>
                </div>

                <Card>
                    <div className="p-6 flex items-center justify-between">
                        <div>
                            <h3 className="text-lg font-semibold text-gray-900">PageSpeed API Key</h3>
                            <p className="text-sm text-gray-500 mt-1">Use shared key or bring your own for higher limits.</p>
                        </div>
                        <Link href={route('orgs.settings.pagespeed', { organization: organization.id })}>
                            <Button variant="primary">Manage PageSpeed</Button>
                        </Link>
                    </div>
                </Card>

                {/* Connection Status */}
                {!connection ? (
                    <Card>
                        <div className="text-center py-12">
                            <div className="text-5xl mb-4">🔗</div>
                            <h3 className="text-lg font-medium text-gray-900 mb-2">Not Connected</h3>
                            <p className="text-sm text-gray-500 mb-6">
                                Connect your Google account to sync Search Console and GA4 data automatically.
                            </p>
                            <Button variant="primary" onClick={handleConnect}>
                                Connect Google Account
                            </Button>
                        </div>
                    </Card>
                ) : (
                    <>
                        {/* Connection Info */}
                        <Card>
                            <div className="p-6">
                                <div className="flex items-center justify-between mb-4">
                                    <div>
                                        <h3 className="text-lg font-semibold text-gray-900">Connection Status</h3>
                                        <p className="text-sm text-gray-500 mt-1">
                                            Connected as: <strong>{connection.account_email}</strong>
                                        </p>
                                    </div>
                                    <div className="flex items-center gap-3">
                                        {getStatusBadge(connection.status)}
                                        {connection.status === 'error' && connection.last_error && (
                                            <span className="text-sm text-red-600">{connection.last_error}</span>
                                        )}
                                    </div>
                                </div>
                                {connection.status === 'active' && (
                                    <Button variant="outline" onClick={handleDisconnect}>
                                        Disconnect
                                    </Button>
                                )}
                            </div>
                        </Card>

                        {/* GSC Site Selection */}
                        <Card>
                            <div className="p-6">
                                <h3 className="text-lg font-semibold text-gray-900 mb-4">Google Search Console</h3>
                                {gscSites && gscSites.length > 0 ? (
                                    <div className="space-y-3">
                                        {gscSites.map((site) => (
                                            <div
                                                key={site.id}
                                                className={`p-4 border-2 rounded-lg cursor-pointer transition-colors ${
                                                    site.is_active
                                                        ? 'border-blue-500 bg-blue-50'
                                                        : 'border-gray-200 hover:border-gray-300'
                                                }`}
                                                onClick={() => handleSelectGsc(site.site_url)}
                                            >
                                                <div className="flex items-center justify-between">
                                                    <div>
                                                        <p className="font-medium text-gray-900">{site.site_url}</p>
                                                        {site.permission_level && (
                                                            <p className="text-sm text-gray-500 mt-1">
                                                                Permission: {site.permission_level}
                                                            </p>
                                                        )}
                                                    </div>
                                                    {site.is_active && (
                                                        <span className="px-2 py-1 text-xs font-semibold bg-blue-100 text-blue-800 rounded-full">
                                                            Active
                                                        </span>
                                                    )}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <p className="text-sm text-gray-500">No GSC sites available. Please connect your Google account.</p>
                                )}
                            </div>
                        </Card>

                        {/* GA4 Property Selection */}
                        <Card>
                            <div className="p-6">
                                <h3 className="text-lg font-semibold text-gray-900 mb-4">Google Analytics 4</h3>
                                {ga4Properties && ga4Properties.length > 0 ? (
                                    <div className="space-y-3">
                                        {ga4Properties.map((property) => (
                                            <div
                                                key={property.id}
                                                className={`p-4 border-2 rounded-lg cursor-pointer transition-colors ${
                                                    property.is_active
                                                        ? 'border-blue-500 bg-blue-50'
                                                        : 'border-gray-200 hover:border-gray-300'
                                                }`}
                                                onClick={() => handleSelectGa4(property.property_id)}
                                            >
                                                <div className="flex items-center justify-between">
                                                    <div>
                                                        <p className="font-medium text-gray-900">{property.display_name}</p>
                                                        <p className="text-sm text-gray-500 mt-1">ID: {property.property_id}</p>
                                                    </div>
                                                    {property.is_active && (
                                                        <span className="px-2 py-1 text-xs font-semibold bg-blue-100 text-blue-800 rounded-full">
                                                            Active
                                                        </span>
                                                    )}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <p className="text-sm text-gray-500">No GA4 properties available. Please connect your Google account.</p>
                                )}
                            </div>
                        </Card>

                        {/* Info Box */}
                        <Card>
                            <div className="p-6 bg-blue-50 border-l-4 border-blue-500">
                                <h4 className="font-semibold text-gray-900 mb-2">💡 How It Works</h4>
                                <ul className="text-sm text-gray-700 space-y-1 list-disc list-inside">
                                    <li>Data syncs automatically every day at 2:00 AM</li>
                                    <li>Historical data is retained based on your plan (Free: 90 days, Pro: 365 days, Agency: 730 days)</li>
                                    <li>You can manually trigger a sync from the dashboard</li>
                                    <li>Alerts are sent when significant changes are detected</li>
                                </ul>
                            </div>
                        </Card>
                    </>
                )}
            </div>
        </AppLayout>
    );
}
