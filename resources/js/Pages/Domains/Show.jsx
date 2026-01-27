import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '../../Components/Layout/AppLayout';
import Card from '../../Components/Shared/Card';
import Button from '../../Components/Shared/Button';

export default function DomainsShow({ domain, tab, platforms, activityLogs }) {
    const [copiedToken, setCopiedToken] = useState(false);

    const tabs = [
        { id: 'overview', label: 'Overview' },
        { id: 'analyzer', label: 'Website Analyzer' },
        { id: 'integrations', label: 'Integrations' },
        { id: 'backlinks', label: 'Backlinks' },
        { id: 'meta', label: 'Meta Editor' },
        { id: 'content', label: 'Content' },
        { id: 'insights', label: 'Insights' },
        { id: 'automation', label: 'Automation' },
        { id: 'reports', label: 'Reports' },
    ];

    const handleTabChange = (tabId) => {
        router.get(`/domains/${domain.id}`, { tab: tabId }, { preserveState: true, replace: true });
    };

    const copyToClipboard = (text) => {
        navigator.clipboard.writeText(text);
        setCopiedToken(true);
        setTimeout(() => setCopiedToken(false), 2000);
    };

    const getStatusBadge = (status) => {
        return (
            <span className={`px-2 py-1 text-xs font-semibold rounded-full ${
                status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
            }`}>
                {status}
            </span>
        );
    };

    const getPlatformBadge = (platform) => {
        if (!platform) return null;
        const colors = {
            wordpress: 'bg-blue-100 text-blue-800',
            shopify: 'bg-green-100 text-green-800',
            custom: 'bg-gray-100 text-gray-800',
            webflow: 'bg-purple-100 text-purple-800',
            wix: 'bg-pink-100 text-pink-800',
            squarespace: 'bg-indigo-100 text-indigo-800',
            other: 'bg-yellow-100 text-yellow-800',
        };
        return (
            <span className={`px-2 py-1 text-xs font-semibold rounded-full ${colors[platform] || colors.other}`}>
                {platforms?.[platform] || platform}
            </span>
        );
    };

    const getVerificationBadge = (status) => {
        const colors = {
            verified: 'bg-green-100 text-green-800',
            pending: 'bg-yellow-100 text-yellow-800',
            unverified: 'bg-gray-100 text-gray-800',
        };
        return (
            <span className={`px-2 py-1 text-xs font-semibold rounded-full ${colors[status] || colors.unverified}`}>
                {status}
            </span>
        );
    };

    const renderTabContent = () => {
        switch (tab) {
            case 'overview':
                return (
                    <div className="space-y-6">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <Card>
                                <h3 className="text-lg font-semibold text-gray-900 mb-4">Domain Statistics</h3>
                                <div className="space-y-3">
                                    <div className="flex justify-between">
                                        <span className="text-gray-600">Campaigns</span>
                                        <span className="font-semibold text-gray-900">{domain.campaigns_count || 0}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-600">Total Backlinks</span>
                                        <span className="font-semibold text-gray-900">{domain.total_backlinks || 0}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-600">Status</span>
                                        {getStatusBadge(domain.status)}
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-600">Verification</span>
                                        {getVerificationBadge(domain.verification_status)}
                                    </div>
                                </div>
                            </Card>

                            <Card>
                                <h3 className="text-lg font-semibold text-gray-900 mb-4">Recent Activity</h3>
                                {activityLogs && activityLogs.length > 0 ? (
                                    <div className="space-y-2 max-h-64 overflow-y-auto">
                                        {activityLogs.map((log) => (
                                            <div key={log.id} className="text-sm border-b border-gray-100 pb-2 last:border-0">
                                                <div className="flex items-start justify-between">
                                                    <div className="flex-1">
                                                        <p className="text-gray-900">{log.message}</p>
                                                        <p className="text-xs text-gray-500 mt-1">
                                                            {new Date(log.created_at).toLocaleString()}
                                                        </p>
                                                    </div>
                                                    <span className={`px-2 py-1 text-xs rounded-full ml-2 ${
                                                        log.status === 'success' ? 'bg-green-100 text-green-800' :
                                                        log.status === 'error' ? 'bg-red-100 text-red-800' :
                                                        log.status === 'warning' ? 'bg-yellow-100 text-yellow-800' :
                                                        'bg-blue-100 text-blue-800'
                                                    }`}>
                                                        {log.status}
                                                    </span>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <div className="text-gray-500 text-sm">
                                        <p>No recent activity</p>
                                    </div>
                                )}
                            </Card>
                        </div>

                        <Card>
                            <h3 className="text-lg font-semibold text-gray-900 mb-4">Domain Information</h3>
                            <div className="space-y-3">
                                <div>
                                    <span className="text-gray-600">Website URL:</span>
                                    <a href={domain.url} target="_blank" rel="noopener noreferrer" className="ml-2 text-blue-600 hover:underline">
                                        {domain.url}
                                    </a>
                                </div>
                                <div>
                                    <span className="text-gray-600">Host:</span>
                                    <span className="ml-2 font-mono text-gray-900">{domain.host}</span>
                                </div>
                                <div>
                                    <span className="text-gray-600">Platform:</span>
                                    <span className="ml-2">{getPlatformBadge(domain.platform)}</span>
                                </div>
                                <div>
                                    <span className="text-gray-600">Created:</span>
                                    <span className="ml-2 text-gray-900">{new Date(domain.created_at).toLocaleString()}</span>
                                </div>
                                {domain.verified_at && (
                                    <div>
                                        <span className="text-gray-600">Verified:</span>
                                        <span className="ml-2 text-gray-900">{new Date(domain.verified_at).toLocaleString()}</span>
                                    </div>
                                )}
                            </div>
                        </Card>
                    </div>
                );

            case 'analyzer':
                return (
                    <Card>
                        <div className="text-center py-12">
                            <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLineCap="round" strokeLineJoin="round" strokeWidth={2} d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            <h3 className="mt-2 text-sm font-medium text-gray-900">Website Analyzer</h3>
                            <p className="mt-1 text-sm text-gray-500 mb-6">Run SEO audits and analyze your website's structure, performance, and issues.</p>
                            <Link href={`/domains/${domain.id}/audits`}>
                                <Button variant="primary">View Audits</Button>
                            </Link>
                        </div>
                    </Card>
                );

            case 'integrations':
                return (
                    <Card>
                        <div className="text-center py-12">
                            <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLineCap="round" strokeLineJoin="round" strokeWidth={2} d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 011-1h1a2 2 0 100-4H7a1 1 0 01-1-1V7a1 1 0 011-1h3a1 1 0 001-1V4z" />
                            </svg>
                            <h3 className="mt-2 text-sm font-medium text-gray-900">Integrations</h3>
                            <p className="mt-1 text-sm text-gray-500 mb-6">Connect Google Search Console and GA4 to sync SEO data and analytics.</p>
                            <Link href={`/domains/${domain.id}/integrations/google`}>
                                <Button variant="primary">Manage Integrations</Button>
                            </Link>
                        </div>
                    </Card>
                );

            case 'backlinks':
                return (
                    <Card>
                        <div className="text-center py-12">
                            <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLineCap="round" strokeLineJoin="round" strokeWidth={2} d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                            </svg>
                            <h3 className="mt-2 text-sm font-medium text-gray-900">Backlinks</h3>
                            <p className="mt-1 text-sm text-gray-500 mb-6">Monitor and analyze backlinks for this domain.</p>
                            <Link href={`/domains/${domain.id}/backlinks`}>
                                <Button variant="primary">View Backlinks</Button>
                            </Link>
                        </div>
                    </Card>
                );
            case 'meta':
                return (
                    <Card>
                        <div className="text-center py-12">
                            <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLineCap="round" strokeLineJoin="round" strokeWidth={2} d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            <h3 className="mt-2 text-sm font-medium text-gray-900">Meta Editor</h3>
                            <p className="mt-1 text-sm text-gray-500 mb-6">Edit SEO meta tags for your website pages.</p>
                            <Link href={`/domains/${domain.id}/meta`}>
                                <Button variant="primary">Open Meta Editor</Button>
                            </Link>
                        </div>
                    </Card>
                );

            default:
                return null;
        }
    };

    return (
        <AppLayout header="Domain Details">
            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">{domain.name}</h1>
                        {domain.host && (
                            <p className="text-sm text-gray-500 mt-1 font-mono">{domain.host}</p>
                        )}
                    </div>
                    <div className="flex gap-2">
                        <Link href={`/domains/${domain.id}/edit`}>
                            <Button variant="outline">Edit</Button>
                        </Link>
                        <Link href="/domains">
                            <Button variant="outline">Back to List</Button>
                        </Link>
                    </div>
                </div>

                {/* Top Cards Row */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {/* Website Info Card */}
                    <Card>
                        <h3 className="text-lg font-semibold text-gray-900 mb-4">Website Information</h3>
                        <div className="space-y-3">
                            <div>
                                <span className="text-gray-600 text-sm">URL:</span>
                                <a href={domain.url} target="_blank" rel="noopener noreferrer" className="ml-2 text-blue-600 hover:underline text-sm break-all">
                                    {domain.url}
                                </a>
                            </div>
                            <div>
                                <span className="text-gray-600 text-sm">Platform:</span>
                                <span className="ml-2">{getPlatformBadge(domain.platform)}</span>
                            </div>
                            <div>
                                <span className="text-gray-600 text-sm">Status:</span>
                                <span className="ml-2">{getStatusBadge(domain.status)}</span>
                            </div>
                        </div>
                    </Card>

                    {/* Verification Card */}
                    <Card>
                        <h3 className="text-lg font-semibold text-gray-900 mb-4">Verification</h3>
                        <div className="space-y-4">
                            <div>
                                <span className="text-gray-600 text-sm">Status:</span>
                                <span className="ml-2">{getVerificationBadge(domain.verification_status)}</span>
                            </div>

                            {domain.verification_token && (
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">Verification Token</label>
                                    <div className="flex gap-2">
                                        <input
                                            type="text"
                                            readOnly
                                            value={domain.verification_token}
                                            className="flex-1 px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-sm font-mono"
                                        />
                                        <Button
                                            variant="outline"
                                            onClick={() => copyToClipboard(domain.verification_token)}
                                            className="whitespace-nowrap"
                                        >
                                            {copiedToken ? 'Copied!' : 'Copy'}
                                        </Button>
                                    </div>
                                </div>
                            )}

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Verification Method</label>
                                <div className="space-y-2">
                                    <label className="flex items-center">
                                        <input
                                            type="radio"
                                            name="verification_method"
                                            value="dns_txt"
                                            checked={domain.verification_method === 'dns_txt'}
                                            disabled
                                            className="mr-2"
                                        />
                                        <span className="text-sm text-gray-700">DNS TXT Record</span>
                                    </label>
                                    <label className="flex items-center">
                                        <input
                                            type="radio"
                                            name="verification_method"
                                            value="html_file"
                                            checked={domain.verification_method === 'html_file'}
                                            disabled
                                            className="mr-2"
                                        />
                                        <span className="text-sm text-gray-700">HTML File Upload</span>
                                    </label>
                                    <label className="flex items-center">
                                        <input
                                            type="radio"
                                            name="verification_method"
                                            value="meta_tag"
                                            checked={domain.verification_method === 'meta_tag'}
                                            disabled
                                            className="mr-2"
                                        />
                                        <span className="text-sm text-gray-700">Meta Tag</span>
                                    </label>
                                </div>
                                <p className="mt-2 text-xs text-gray-500">
                                    Verification functionality will be available in a future update.
                                </p>
                            </div>

                            {domain.verification_method === 'dns_txt' && domain.verification_token && (
                                <div className="p-3 bg-blue-50 border border-blue-200 rounded-md">
                                    <p className="text-xs text-blue-800">
                                        <strong>DNS TXT Instructions:</strong> Add a TXT record to your domain's DNS settings with the name <code className="bg-blue-100 px-1 rounded">@</code> and value <code className="bg-blue-100 px-1 rounded">{domain.verification_token}</code>
                                    </p>
                                </div>
                            )}

                            {domain.verification_method === 'html_file' && domain.verification_token && (
                                <div className="p-3 bg-blue-50 border border-blue-200 rounded-md">
                                    <p className="text-xs text-blue-800">
                                        <strong>HTML File Instructions:</strong> Upload a file named <code className="bg-blue-100 px-1 rounded">{domain.verification_token}.html</code> to your website's root directory.
                                    </p>
                                </div>
                            )}

                            {domain.verification_method === 'meta_tag' && domain.verification_token && (
                                <div className="p-3 bg-blue-50 border border-blue-200 rounded-md">
                                    <p className="text-xs text-blue-800">
                                        <strong>Meta Tag Instructions:</strong> Add this meta tag to your website's <code className="bg-blue-100 px-1 rounded">&lt;head&gt;</code> section:
                                        <br />
                                        <code className="bg-blue-100 px-1 rounded mt-1 block">&lt;meta name="backlink-pro-verification" content="{domain.verification_token}" /&gt;</code>
                                    </p>
                                </div>
                            )}
                        </div>
                    </Card>
                </div>

                {/* Tabs */}
                <div className="border-b border-gray-200">
                    <nav className="-mb-px flex space-x-8 overflow-x-auto" aria-label="Tabs">
                        {tabs.map((tabItem) => {
                            // Special handling for tabs that link to separate pages
                            if (tabItem.id === 'analyzer') {
                                return (
                                    <Link
                                        key={tabItem.id}
                                        href={`/domains/${domain.id}/audits`}
                                        className={`
                                            whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm
                                            ${tab === tabItem.id
                                                ? 'border-gray-900 text-gray-900'
                                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                            }
                                        `}
                                    >
                                        {tabItem.label}
                                    </Link>
                                );
                            }
                            if (tabItem.id === 'integrations') {
                                return (
                                    <Link
                                        key={tabItem.id}
                                        href={`/domains/${domain.id}/integrations/google`}
                                        className={`
                                            whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm
                                            ${tab === tabItem.id
                                                ? 'border-gray-900 text-gray-900'
                                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                            }
                                        `}
                                    >
                                        {tabItem.label}
                                    </Link>
                                );
                            }
                            if (tabItem.id === 'backlinks') {
                                return (
                                    <Link
                                        key={tabItem.id}
                                        href={`/domains/${domain.id}/backlinks`}
                                        className={`
                                            whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm
                                            ${tab === tabItem.id
                                                ? 'border-gray-900 text-gray-900'
                                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                            }
                                        `}
                                    >
                                        {tabItem.label}
                                    </Link>
                                );
                            }
                            if (tabItem.id === 'meta') {
                                return (
                                    <Link
                                        key={tabItem.id}
                                        href={`/domains/${domain.id}/meta`}
                                        className={`
                                            whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm
                                            ${tab === tabItem.id
                                                ? 'border-gray-900 text-gray-900'
                                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                            }
                                        `}
                                    >
                                        {tabItem.label}
                                    </Link>
                                );
                            }
                            if (tabItem.id === 'insights') {
                                return (
                                    <Link
                                        key={tabItem.id}
                                        href={`/domains/${domain.id}/insights`}
                                        className={`
                                            whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm
                                            ${tab === tabItem.id
                                                ? 'border-gray-900 text-gray-900'
                                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                            }
                                        `}
                                    >
                                        {tabItem.label}
                                    </Link>
                                );
                            }
                            if (tabItem.id === 'automation') {
                                return (
                                    <Link
                                        key={tabItem.id}
                                        href={`/domains/${domain.id}/automation`}
                                        className={`
                                            whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm
                                            ${tab === tabItem.id
                                                ? 'border-gray-900 text-gray-900'
                                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                            }
                                        `}
                                    >
                                        {tabItem.label}
                                    </Link>
                                );
                            }
                            if (tabItem.id === 'content') {
                                return (
                                    <Link
                                        key={tabItem.id}
                                        href={`/domains/${domain.id}/content`}
                                        className={`
                                            whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm
                                            ${tab === tabItem.id
                                                ? 'border-gray-900 text-gray-900'
                                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                            }
                                        `}
                                    >
                                        {tabItem.label}
                                    </Link>
                                );
                            }
                            return (
                                <button
                                    key={tabItem.id}
                                    onClick={() => handleTabChange(tabItem.id)}
                                    className={`
                                        whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm
                                        ${tab === tabItem.id
                                            ? 'border-gray-900 text-gray-900'
                                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                        }
                                    `}
                                >
                                    {tabItem.label}
                                </button>
                            );
                        })}
                    </nav>
                </div>

                {/* Tab Content */}
                <div className="mt-6">
                    {renderTabContent()}
                </div>
            </div>
        </AppLayout>
    );
}

