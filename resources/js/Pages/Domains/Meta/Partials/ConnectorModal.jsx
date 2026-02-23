import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import { usePage, router } from '@inertiajs/react';
import Button from '@/Components/Shared/Button';
import Input from '@/Components/Shared/Input';
import Select from '@/Components/Shared/Select';

const APP_URL = typeof window !== 'undefined' ? window.location.origin : '';

export default function ConnectorModal({ domain, connector, onClose }) {
    const { props } = usePage();
    const features = props.features || {};
    const [connectorType, setConnectorType] = useState(connector?.type || 'wordpress');
    const { data, setData, post, processing } = useForm({
        type: connectorType,
        base_url: connector?.base_url || '',
        api_token: '',
        shop_domain: '',
        admin_access_token: '',
        api_version: '2024-01',
        cache_ttl: connector?.auth_json?.cache_ttl ?? 300,
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post(`/domains/${domain.id}/meta/connect`, {
            onSuccess: () => { onClose(); },
        });
    };

    const copyToken = () => {
        const t = connector?.auth_json?.edge_token;
        if (t) navigator.clipboard.writeText(t);
    };

    const rotateToken = () => {
        router.post(`/domains/${domain.id}/meta/edge-proxy/rotate`, {}, { preserveScroll: true, onSuccess: () => onClose(); });
    };

    const verifyEdge = () => {
        router.post(`/domains/${domain.id}/meta/test`, {}, { preserveScroll: true, onSuccess: () => onClose(); });
    };

    const edgeToken = connector?.auth_json?.edge_token ?? '';
    const tokenMasked = edgeToken ? (edgeToken.slice(0, 8) + '…' + edgeToken.slice(-4)) : '';
    const workerScript = `// BacklinkPro Edge/Proxy Worker - deploy to Cloudflare Workers
const BACKEND = "${APP_URL}";
const TOKEN = "${edgeToken || 'YOUR_TOKEN'}";
export default {
  async fetch(req) {
    const url = new URL(req.url);
    const res = await fetch(BACKEND + "/edge/meta?host=" + encodeURIComponent(url.hostname) + "&path=" + encodeURIComponent(url.pathname), {
      headers: { "Authorization": "Bearer " + TOKEN }
    });
    const { meta } = await res.json();
    // Inject meta into HTML and return (implement your HTML rewrite here)
    return new Response("<!DOCTYPE html><html><head><title>" + (meta?.title || "") + "</title></head><body>Edge meta loaded</body></html>", {
      headers: { "Content-Type": "text/html" }
    });
  }
};`;

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
                            {features.edge_proxy && <option value="edge_proxy">Edge/Proxy (SEO-safe)</option>}
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
                                {features.shopify_oauth && (
                                    <div className="p-4 bg-green-50 border border-green-200 rounded-md space-y-3">
                                        <p className="text-sm text-green-800 font-medium">Connect with Shopify (OAuth)</p>
                                        <div className="flex gap-2 items-end">
                                            <Input
                                                label="Shop domain"
                                                value={data.shop_domain}
                                                onChange={(e) => setData('shop_domain', e.target.value)}
                                                placeholder="myshop or myshop.myshopify.com"
                                            />
                                            <Button
                                                type="button"
                                                variant="primary"
                                                onClick={() => {
                                                    const shop = (data.shop_domain || '').trim();
                                                    if (shop) {
                                                        const url = `/domains/${domain.id}/shopify/install?shop=${encodeURIComponent(shop)}`;
                                                        window.location.href = url;
                                                    }
                                                }}
                                            >
                                                Connect Shopify
                                            </Button>
                                        </div>
                                        <p className="text-xs text-green-700">Or use manual token below.</p>
                                    </div>
                                )}
                                <Input
                                    label="Shop Domain"
                                    value={data.shop_domain}
                                    onChange={(e) => setData('shop_domain', e.target.value)}
                                    placeholder="myshop.myshopify.com"
                                    required={!features.shopify_oauth}
                                />
                                <Input
                                    label="Admin API Access Token"
                                    type="password"
                                    value={data.admin_access_token}
                                    onChange={(e) => setData('admin_access_token', e.target.value)}
                                    placeholder="Token from Shopify Custom App"
                                    required={!features.shopify_oauth}
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

                        {features.edge_proxy && connectorType === 'edge_proxy' && (
                            <div className="space-y-4 p-4 bg-amber-50 border border-amber-200 rounded-md">
                                <p className="text-sm text-amber-800">
                                    <strong>Edge/Proxy (SEO-safe):</strong> Meta tags at the edge (e.g. Cloudflare Worker). Save to generate token, then deploy the Worker script.
                                </p>
                                <Input
                                    label="Cache TTL (seconds)"
                                    type="number"
                                    value={data.cache_ttl ?? 300}
                                    onChange={(e) => setData('cache_ttl', parseInt(e.target.value, 10) || 300)}
                                    min={0}
                                    max={86400}
                                />
                                {edgeToken && (
                                    <>
                                        <div className="flex items-center gap-2">
                                            <span className="text-sm font-medium text-gray-700">Token:</span>
                                            <code className="text-xs bg-white px-2 py-1 rounded flex-1 truncate">{tokenMasked}</code>
                                            <Button type="button" variant="outline" size="sm" onClick={copyToken}>Copy</Button>
                                        </div>
                                        <div>
                                            <p className="text-xs font-medium text-gray-600 mb-1">Cloudflare Worker script:</p>
                                            <pre className="text-xs bg-white p-2 rounded border overflow-x-auto max-h-40 overflow-y-auto whitespace-pre-wrap">{workerScript}</pre>
                                        </div>
                                        <div className="flex gap-2">
                                            <Button type="button" variant="outline" size="sm" onClick={rotateToken}>Rotate token</Button>
                                            <Button type="button" variant="outline" size="sm" onClick={verifyEdge}>Verify</Button>
                                            {connector?.status === 'connected' && (
                                                <span className="text-xs text-green-600 self-center">Connected</span>
                                            )}
                                        </div>
                                    </>
                                )}
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


