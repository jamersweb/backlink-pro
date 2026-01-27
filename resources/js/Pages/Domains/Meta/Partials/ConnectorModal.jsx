import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import Button from '@/Components/Shared/Button';
import Input from '@/Components/Shared/Input';
import Select from '@/Components/Shared/Select';

export default function ConnectorModal({ domain, connector, onClose }) {
    const [connectorType, setConnectorType] = useState(connector?.type || 'wordpress');
    const { data, setData, post, processing } = useForm({
        type: connectorType,
        base_url: connector?.base_url || '',
        api_token: '',
        shop_domain: '',
        admin_access_token: '',
        api_version: '2024-01',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post(`/domains/${domain.id}/meta/connect`, {
            onSuccess: () => {
                onClose();
            },
        });
    };

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div className="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div className="p-6">
                    <div className="flex justify-between items-center mb-6">
                        <h2 className="text-2xl font-bold text-gray-900">Connect Meta Connector</h2>
                        <button onClick={onClose} className="text-gray-400 hover:text-gray-600">
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLineCap="round" strokeLineJoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form onSubmit={handleSubmit} className="space-y-6">
                        <Select
                            label="Connector Type"
                            value={connectorType}
                            onChange={(e) => {
                                setConnectorType(e.target.value);
                                setData('type', e.target.value);
                            }}
                            required
                        >
                            <option value="wordpress">WordPress</option>
                            <option value="shopify">Shopify</option>
                            <option value="custom_js">Custom JS Snippet</option>
                        </Select>

                        {connectorType === 'wordpress' && (
                            <>
                                <Input
                                    label="WordPress Base URL"
                                    type="url"
                                    value={data.base_url}
                                    onChange={(e) => setData('base_url', e.target.value)}
                                    placeholder="https://example.com"
                                    required
                                />
                                <Input
                                    label="API Token"
                                    type="password"
                                    value={data.api_token}
                                    onChange={(e) => setData('api_token', e.target.value)}
                                    placeholder="Token from BacklinkPro WordPress plugin"
                                    required
                                />
                                <div className="p-4 bg-blue-50 border border-blue-200 rounded-md">
                                    <p className="text-sm text-blue-800">
                                        <strong>Note:</strong> Install the BacklinkPro WordPress plugin and configure the API token in plugin settings.
                                    </p>
                                </div>
                            </>
                        )}

                        {connectorType === 'shopify' && (
                            <>
                                <Input
                                    label="Shop Domain"
                                    value={data.shop_domain}
                                    onChange={(e) => setData('shop_domain', e.target.value)}
                                    placeholder="myshop.myshopify.com"
                                    required
                                />
                                <Input
                                    label="Admin API Access Token"
                                    type="password"
                                    value={data.admin_access_token}
                                    onChange={(e) => setData('admin_access_token', e.target.value)}
                                    placeholder="Token from Shopify Custom App"
                                    required
                                />
                                <Input
                                    label="API Version"
                                    value={data.api_version}
                                    onChange={(e) => setData('api_version', e.target.value)}
                                    placeholder="2024-01"
                                />
                                <div className="p-4 bg-blue-50 border border-blue-200 rounded-md">
                                    <p className="text-sm text-blue-800">
                                        <strong>Note:</strong> Create a Custom App in Shopify Admin and grant Admin API access. Use the Admin API access token.
                                    </p>
                                </div>
                            </>
                        )}

                        {connectorType === 'custom_js' && (
                            <div className="space-y-4">
                                <div className="p-4 bg-blue-50 border border-blue-200 rounded-md">
                                    <p className="text-sm text-blue-800 mb-4">
                                        <strong>Custom JS Snippet:</strong> Add this snippet to your website's HTML before the closing &lt;/head&gt; tag:
                                    </p>
                                    <code className="block p-3 bg-white border rounded text-xs break-all">
                                        {`<script src="${window.location.origin}/snippet/${domain.meta_snippet_key || 'YOUR_KEY'}.js" async></script>`}
                                    </code>
                                    <p className="text-xs text-blue-700 mt-2">
                                        This snippet will inject meta tags client-side. Works best for static sites or sites where you can't modify server-side code.
                                    </p>
                                </div>
                            </div>
                        )}

                        <div className="flex gap-4 pt-4">
                            <Button type="submit" variant="primary" disabled={processing}>
                                {processing ? 'Saving...' : 'Save'}
                            </Button>
                            <Button type="button" variant="outline" onClick={onClose}>
                                Cancel
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    );
}


