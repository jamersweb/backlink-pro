import { router } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Components/Layout/AppLayout';
import Card from '@/Components/Shared/Card';
import Button from '@/Components/Shared/Button';
import Input from '@/Components/Shared/Input';

export default function MetaConnector({ domain, connector }) {
    const [connectorType, setConnectorType] = useState(connector?.type || '');
    const [formData, setFormData] = useState({
        // WordPress
        wp_base_url: connector?.settings_json?.wp_base_url || '',
        wp_username: connector?.credentials_json?.username || '',
        wp_app_password: '',
        
        // Shopify
        shopify_shop: connector?.settings_json?.shop || '',
        shopify_access_token: '',
        shopify_api_version: connector?.settings_json?.api_version || '2024-01',
        
        // Generic REST
        generic_base_url: connector?.settings_json?.base_url || '',
        generic_auth_type: connector?.settings_json?.auth_type || 'bearer',
        generic_token: '',
        generic_username: '',
        generic_password: '',
        generic_api_key: '',
        generic_publish_endpoint: connector?.settings_json?.publish_endpoint || '/meta/update',
    });

    const [testing, setTesting] = useState(false);
    const [saving, setSaving] = useState(false);

    const handleTypeChange = (type) => {
        setConnectorType(type);
    };

    const handleFieldChange = (field, value) => {
        setFormData(prev => ({ ...prev, [field]: value }));
    };

    const handleSave = () => {
        setSaving(true);
        
        let credentials = {};
        let settings = {};

        switch (connectorType) {
            case 'wp':
                credentials = {
                    username: formData.wp_username,
                    app_password: formData.wp_app_password || connector?.credentials_json?.app_password,
                };
                settings = {
                    wp_base_url: formData.wp_base_url,
                };
                break;

            case 'shopify':
                credentials = {
                    access_token: formData.shopify_access_token || connector?.credentials_json?.access_token,
                };
                settings = {
                    shop: formData.shopify_shop,
                    api_version: formData.shopify_api_version,
                };
                break;

            case 'generic':
                credentials = {
                    token: formData.generic_token || connector?.credentials_json?.token,
                    username: formData.generic_username || connector?.credentials_json?.username,
                    password: formData.generic_password || connector?.credentials_json?.password,
                    api_key: formData.generic_api_key || connector?.credentials_json?.api_key,
                };
                settings = {
                    base_url: formData.generic_base_url,
                    auth_type: formData.generic_auth_type,
                    publish_endpoint: formData.generic_publish_endpoint,
                };
                break;

            case 'custom_js':
                credentials = {};
                settings = {};
                break;
        }

        router.post(`/domains/${domain.id}/meta/connector`, {
            type: connectorType,
            credentials,
            settings,
        }, {
            onFinish: () => setSaving(false),
        });
    };

    const handleTest = () => {
        setTesting(true);
        router.post(`/domains/${domain.id}/meta/connector/test`, {}, {
            onFinish: () => setTesting(false),
        });
    };

    const handleDisconnect = () => {
        if (confirm('Are you sure you want to disconnect this connector?')) {
            router.delete(`/domains/${domain.id}/meta/connector`);
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

    return (
        <AppLayout header="Meta Connector">
            <div className="space-y-6">
                {/* Status Card */}
                {connector && (
                    <Card>
                        <div className="p-4 flex justify-between items-center">
                            <div>
                                <h3 className="text-lg font-semibold text-gray-900">Current Connector</h3>
                                <div className="mt-2 flex items-center gap-3">
                                    {getStatusBadge(connector.status)}
                                    <span className="text-sm text-gray-600 capitalize">{connector.type}</span>
                                    {connector.last_tested_at && (
                                        <span className="text-xs text-gray-500">
                                            Last tested: {new Date(connector.last_tested_at).toLocaleString()}
                                        </span>
                                    )}
                                </div>
                                {connector.last_error_message && (
                                    <div className="mt-2 text-sm text-red-600">
                                        Error: {connector.last_error_message}
                                    </div>
                                )}
                            </div>
                            <div className="flex gap-2">
                                <Button variant="outline" onClick={handleTest} disabled={testing}>
                                    {testing ? 'Testing...' : 'Test Connection'}
                                </Button>
                                <Button variant="outline" onClick={handleDisconnect}>
                                    Disconnect
                                </Button>
                            </div>
                        </div>
                    </Card>
                )}

                {/* Connector Type Selection */}
                <Card>
                    <div className="p-6">
                        <h2 className="text-xl font-bold text-gray-900 mb-4">Select Connector Type</h2>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {['wp', 'shopify', 'generic', 'custom_js'].map((type) => (
                                <button
                                    key={type}
                                    onClick={() => handleTypeChange(type)}
                                    className={`p-4 border-2 rounded-lg text-left transition ${
                                        connectorType === type
                                            ? 'border-blue-500 bg-blue-50'
                                            : 'border-gray-200 hover:border-gray-300'
                                    }`}
                                >
                                    <div className="font-semibold text-gray-900 capitalize">{type === 'wp' ? 'WordPress' : type === 'custom_js' ? 'Custom JS (Snippet)' : type}</div>
                                    <div className="text-sm text-gray-600 mt-1">
                                        {type === 'wp' && 'WordPress REST API with Application Passwords'}
                                        {type === 'shopify' && 'Shopify Admin API with GraphQL'}
                                        {type === 'generic' && 'Generic REST API connector'}
                                        {type === 'custom_js' && 'JavaScript snippet (client-side)'}
                                    </div>
                                </button>
                            ))}
                        </div>
                    </div>
                </Card>

                {/* WordPress Form */}
                {connectorType === 'wp' && (
                    <Card>
                        <div className="p-6">
                            <h2 className="text-xl font-bold text-gray-900 mb-4">WordPress Configuration</h2>
                            <div className="space-y-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        WordPress Base URL
                                    </label>
                                    <Input
                                        type="url"
                                        value={formData.wp_base_url}
                                        onChange={(e) => handleFieldChange('wp_base_url', e.target.value)}
                                        placeholder="https://example.com"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Username
                                    </label>
                                    <Input
                                        type="text"
                                        value={formData.wp_username}
                                        onChange={(e) => handleFieldChange('wp_username', e.target.value)}
                                        placeholder="wordpress_username"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Application Password
                                    </label>
                                    <Input
                                        type="password"
                                        value={formData.wp_app_password}
                                        onChange={(e) => handleFieldChange('wp_app_password', e.target.value)}
                                        placeholder={connector?.credentials_json?.app_password ? '••••••••' : 'Enter application password'}
                                    />
                                    <p className="text-xs text-gray-500 mt-1">
                                        Create in WordPress: Users → Your Profile → Application Passwords
                                    </p>
                                </div>
                                <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                    <h4 className="font-semibold text-blue-900 mb-2">WordPress Plugin Required</h4>
                                    <p className="text-sm text-blue-800 mb-2">
                                        You need to install the "BacklinkPro Meta Bridge" plugin on your WordPress site.
                                    </p>
                                    <a
                                        href="/wordpress-plugin/backlinkpro-meta-bridge.zip"
                                        download
                                        className="text-sm text-blue-600 hover:text-blue-800 underline"
                                    >
                                        Download Plugin ZIP
                                    </a>
                                </div>
                                <Button variant="primary" onClick={handleSave} disabled={saving}>
                                    {saving ? 'Saving...' : 'Save Configuration'}
                                </Button>
                            </div>
                        </div>
                    </Card>
                )}

                {/* Shopify Form */}
                {connectorType === 'shopify' && (
                    <Card>
                        <div className="p-6">
                            <h2 className="text-xl font-bold text-gray-900 mb-4">Shopify Configuration</h2>
                            <div className="space-y-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Shop Domain
                                    </label>
                                    <Input
                                        type="text"
                                        value={formData.shopify_shop}
                                        onChange={(e) => handleFieldChange('shopify_shop', e.target.value)}
                                        placeholder="myshop.myshopify.com"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Access Token
                                    </label>
                                    <Input
                                        type="password"
                                        value={formData.shopify_access_token}
                                        onChange={(e) => handleFieldChange('shopify_access_token', e.target.value)}
                                        placeholder={connector?.credentials_json?.access_token ? '••••••••' : 'Enter access token'}
                                    />
                                    <p className="text-xs text-gray-500 mt-1">
                                        Create in Shopify: Apps → Develop apps → Private app → Admin API access token
                                    </p>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        API Version
                                    </label>
                                    <select
                                        value={formData.shopify_api_version}
                                        onChange={(e) => handleFieldChange('shopify_api_version', e.target.value)}
                                        className="w-full rounded-md border-gray-300"
                                    >
                                        <option value="2024-01">2024-01</option>
                                        <option value="2024-04">2024-04</option>
                                        <option value="2024-07">2024-07</option>
                                        <option value="2024-10">2024-10</option>
                                        <option value="2025-01">2025-01</option>
                                    </select>
                                </div>
                                <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                    <p className="text-sm text-yellow-800">
                                        <strong>Note:</strong> Supports Pages and Blog Articles. Uses GraphQL SEO fields for updates.
                                    </p>
                                </div>
                                <Button variant="primary" onClick={handleSave} disabled={saving}>
                                    {saving ? 'Saving...' : 'Save Configuration'}
                                </Button>
                            </div>
                        </div>
                    </Card>
                )}

                {/* Generic REST Form */}
                {connectorType === 'generic' && (
                    <Card>
                        <div className="p-6">
                            <h2 className="text-xl font-bold text-gray-900 mb-4">Generic REST API Configuration</h2>
                            <div className="space-y-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Base URL
                                    </label>
                                    <Input
                                        type="url"
                                        value={formData.generic_base_url}
                                        onChange={(e) => handleFieldChange('generic_base_url', e.target.value)}
                                        placeholder="https://api.example.com"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Authentication Type
                                    </label>
                                    <select
                                        value={formData.generic_auth_type}
                                        onChange={(e) => handleFieldChange('generic_auth_type', e.target.value)}
                                        className="w-full rounded-md border-gray-300"
                                    >
                                        <option value="bearer">Bearer Token</option>
                                        <option value="basic">Basic Auth</option>
                                        <option value="api_key">API Key</option>
                                    </select>
                                </div>
                                {formData.generic_auth_type === 'bearer' && (
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Bearer Token
                                        </label>
                                        <Input
                                            type="password"
                                            value={formData.generic_token}
                                            onChange={(e) => handleFieldChange('generic_token', e.target.value)}
                                            placeholder="Enter bearer token"
                                        />
                                    </div>
                                )}
                                {formData.generic_auth_type === 'basic' && (
                                    <>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                Username
                                            </label>
                                            <Input
                                                type="text"
                                                value={formData.generic_username}
                                                onChange={(e) => handleFieldChange('generic_username', e.target.value)}
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                Password
                                            </label>
                                            <Input
                                                type="password"
                                                value={formData.generic_password}
                                                onChange={(e) => handleFieldChange('generic_password', e.target.value)}
                                            />
                                        </div>
                                    </>
                                )}
                                {formData.generic_auth_type === 'api_key' && (
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            API Key
                                        </label>
                                        <Input
                                            type="password"
                                            value={formData.generic_api_key}
                                            onChange={(e) => handleFieldChange('generic_api_key', e.target.value)}
                                        />
                                    </div>
                                )}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Publish Endpoint
                                    </label>
                                    <Input
                                        type="text"
                                        value={formData.generic_publish_endpoint}
                                        onChange={(e) => handleFieldChange('generic_publish_endpoint', e.target.value)}
                                        placeholder="/meta/update"
                                    />
                                    <p className="text-xs text-gray-500 mt-1">
                                        Expected payload: {`{ url, title, description, canonical }`}
                                    </p>
                                </div>
                                <Button variant="primary" onClick={handleSave} disabled={saving}>
                                    {saving ? 'Saving...' : 'Save Configuration'}
                                </Button>
                            </div>
                        </div>
                    </Card>
                )}

                {/* Custom JS Info */}
                {connectorType === 'custom_js' && (
                    <Card>
                        <div className="p-6">
                            <h2 className="text-xl font-bold text-gray-900 mb-4">Custom JS (Snippet Agent)</h2>
                            <div className="space-y-4">
                                <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                    <p className="text-sm text-blue-800">
                                        Custom JS connector uses the JavaScript snippet agent for client-side meta updates.
                                        No additional configuration needed here.
                                    </p>
                                </div>
                                <Button variant="primary" onClick={handleSave} disabled={saving}>
                                    {saving ? 'Saving...' : 'Save Configuration'}
                                </Button>
                            </div>
                        </div>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}


