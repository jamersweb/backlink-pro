import { useState } from 'react';
import AdminLayout from '@/Components/Layout/AdminLayout';
import Card from '@/Components/Shared/Card';
import Button from '@/Components/Shared/Button';
import { Link, useForm } from '@inertiajs/react';

export default function PageMetasEdit({ page, schemaTemplates }) {
    const [activeTab, setActiveTab] = useState('basic');

    const { data, setData, put, processing, errors } = useForm({
        page_key: page.page_key || '',
        page_name: page.page_name || '',
        route_name: page.route_name || '',
        url_path: page.url_path || '',
        meta_title: page.meta_title || '',
        meta_description: page.meta_description || '',
        meta_keywords: page.meta_keywords || '',
        og_title: page.og_title || '',
        og_description: page.og_description || '',
        og_image: page.og_image || '',
        og_type: page.og_type || 'website',
        twitter_card: page.twitter_card || 'summary_large_image',
        twitter_title: page.twitter_title || '',
        twitter_description: page.twitter_description || '',
        twitter_image: page.twitter_image || '',
        schema_json: page.schema_json || null,
        content_json: page.content_json || null,
        is_active: page.is_active ?? true,
        is_indexable: page.is_indexable ?? true,
        is_followable: page.is_followable ?? true,
        canonical_url: page.canonical_url || '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        put(`/admin/page-metas/${page.id}`);
    };

    const handleApplySchemaTemplate = (templateKey) => {
        if (schemaTemplates[templateKey]) {
            setData('schema_json', schemaTemplates[templateKey]);
        }
    };

    const tabs = [
        { id: 'basic', label: 'Basic Info' },
        { id: 'seo', label: 'SEO Meta' },
        { id: 'social', label: 'Social / OG' },
        { id: 'schema', label: 'Schema' },
        { id: 'settings', label: 'Settings' },
    ];

    return (
        <AdminLayout header={`Edit: ${page.page_name}`}>
            <div className="max-w-4xl mx-auto">
                <div className="mb-6 flex items-center justify-between">
                    <Link href="/admin/page-metas" className="text-gray-600 hover:text-gray-900 flex items-center gap-2">
                        ← Back to Pages
                    </Link>
                    <a 
                        href={page.url_path} 
                        target="_blank" 
                        rel="noopener noreferrer"
                        className="text-blue-600 hover:text-blue-800 flex items-center gap-1"
                    >
                        View Page ↗
                    </a>
                </div>

                <form onSubmit={handleSubmit}>
                    {/* Tabs */}
                    <div className="border-b border-gray-200 mb-6">
                        <nav className="flex gap-4">
                            {tabs.map((tab) => (
                                <button
                                    key={tab.id}
                                    type="button"
                                    onClick={() => setActiveTab(tab.id)}
                                    className={`py-3 px-1 border-b-2 font-medium text-sm transition-colors ${
                                        activeTab === tab.id
                                            ? 'border-blue-500 text-blue-600'
                                            : 'border-transparent text-gray-500 hover:text-gray-700'
                                    }`}
                                >
                                    {tab.label}
                                </button>
                            ))}
                        </nav>
                    </div>

                    {/* Basic Info Tab */}
                    {activeTab === 'basic' && (
                        <Card className="p-6 bg-white border border-gray-200">
                            <h3 className="text-lg font-semibold text-gray-900 mb-4">Basic Information</h3>
                            <div className="space-y-4">
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">Page Key *</label>
                                        <input
                                            type="text"
                                            value={data.page_key}
                                            onChange={(e) => setData('page_key', e.target.value.toLowerCase().replace(/\s+/g, '-'))}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            placeholder="e.g., home, pricing, about"
                                        />
                                        {errors.page_key && <p className="text-red-600 text-sm mt-1">{errors.page_key}</p>}
                                        <p className="text-xs text-gray-500 mt-1">Unique identifier (lowercase, no spaces)</p>
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">Page Name *</label>
                                        <input
                                            type="text"
                                            value={data.page_name}
                                            onChange={(e) => setData('page_name', e.target.value)}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            placeholder="e.g., Homepage, Pricing Page"
                                        />
                                        {errors.page_name && <p className="text-red-600 text-sm mt-1">{errors.page_name}</p>}
                                    </div>
                                </div>
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">URL Path *</label>
                                        <input
                                            type="text"
                                            value={data.url_path}
                                            onChange={(e) => setData('url_path', e.target.value)}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            placeholder="e.g., /, /pricing, /about"
                                        />
                                        {errors.url_path && <p className="text-red-600 text-sm mt-1">{errors.url_path}</p>}
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">Route Name</label>
                                        <input
                                            type="text"
                                            value={data.route_name}
                                            onChange={(e) => setData('route_name', e.target.value)}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            placeholder="e.g., marketing.home"
                                        />
                                        <p className="text-xs text-gray-500 mt-1">Laravel route name (optional)</p>
                                    </div>
                                </div>
                            </div>
                        </Card>
                    )}

                    {/* SEO Meta Tab */}
                    {activeTab === 'seo' && (
                        <Card className="p-6 bg-white border border-gray-200">
                            <h3 className="text-lg font-semibold text-gray-900 mb-4">SEO Meta Tags</h3>
                            <div className="space-y-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Meta Title
                                        <span className={`ml-2 text-xs ${(data.meta_title?.length || 0) > 60 ? 'text-orange-600' : 'text-gray-400'}`}>
                                            {data.meta_title?.length || 0}/60
                                        </span>
                                    </label>
                                    <input
                                        type="text"
                                        value={data.meta_title}
                                        onChange={(e) => setData('meta_title', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        placeholder="Page title for search engines"
                                    />
                                    {errors.meta_title && <p className="text-red-600 text-sm mt-1">{errors.meta_title}</p>}
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Meta Description
                                        <span className={`ml-2 text-xs ${(data.meta_description?.length || 0) > 160 ? 'text-orange-600' : 'text-gray-400'}`}>
                                            {data.meta_description?.length || 0}/160
                                        </span>
                                    </label>
                                    <textarea
                                        value={data.meta_description}
                                        onChange={(e) => setData('meta_description', e.target.value)}
                                        rows={3}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        placeholder="Brief description for search results"
                                    />
                                    {errors.meta_description && <p className="text-red-600 text-sm mt-1">{errors.meta_description}</p>}
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Meta Keywords</label>
                                    <input
                                        type="text"
                                        value={data.meta_keywords}
                                        onChange={(e) => setData('meta_keywords', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        placeholder="keyword1, keyword2, keyword3"
                                    />
                                    <p className="text-xs text-gray-500 mt-1">Comma-separated keywords (less important for modern SEO)</p>
                                </div>

                                {/* Preview */}
                                <div className="mt-6 p-4 bg-gray-50 rounded-lg">
                                    <h4 className="text-sm font-medium text-gray-700 mb-2">Search Result Preview</h4>
                                    <div className="bg-white p-4 rounded border">
                                        <div className="text-blue-800 text-lg hover:underline cursor-pointer">
                                            {data.meta_title || 'Page Title'}
                                        </div>
                                        <div className="text-green-700 text-sm">
                                            {window.location.origin}{data.url_path || '/'}
                                        </div>
                                        <div className="text-gray-600 text-sm mt-1">
                                            {data.meta_description || 'Meta description will appear here...'}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </Card>
                    )}

                    {/* Social / OG Tab */}
                    {activeTab === 'social' && (
                        <Card className="p-6 bg-white border border-gray-200">
                            <h3 className="text-lg font-semibold text-gray-900 mb-4">Social Media / Open Graph</h3>
                            <div className="space-y-6">
                                {/* Open Graph */}
                                <div>
                                    <h4 className="text-md font-medium text-gray-800 mb-3">Open Graph (Facebook, LinkedIn)</h4>
                                    <div className="space-y-4">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">OG Title</label>
                                            <input
                                                type="text"
                                                value={data.og_title}
                                                onChange={(e) => setData('og_title', e.target.value)}
                                                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                placeholder="Leave empty to use Meta Title"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">OG Description</label>
                                            <textarea
                                                value={data.og_description}
                                                onChange={(e) => setData('og_description', e.target.value)}
                                                rows={2}
                                                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                placeholder="Leave empty to use Meta Description"
                                            />
                                        </div>
                                        <div className="grid grid-cols-2 gap-4">
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">OG Image URL</label>
                                                <input
                                                    type="text"
                                                    value={data.og_image}
                                                    onChange={(e) => setData('og_image', e.target.value)}
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                    placeholder="/images/og-image.jpg"
                                                />
                                            </div>
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-1">OG Type</label>
                                                <select
                                                    value={data.og_type}
                                                    onChange={(e) => setData('og_type', e.target.value)}
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                >
                                                    <option value="website">website</option>
                                                    <option value="article">article</option>
                                                    <option value="product">product</option>
                                                    <option value="profile">profile</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {/* Twitter */}
                                <div className="pt-4 border-t">
                                    <h4 className="text-md font-medium text-gray-800 mb-3">Twitter Card</h4>
                                    <div className="space-y-4">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">Card Type</label>
                                            <select
                                                value={data.twitter_card}
                                                onChange={(e) => setData('twitter_card', e.target.value)}
                                                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            >
                                                <option value="summary">summary</option>
                                                <option value="summary_large_image">summary_large_image</option>
                                                <option value="app">app</option>
                                                <option value="player">player</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">Twitter Title</label>
                                            <input
                                                type="text"
                                                value={data.twitter_title}
                                                onChange={(e) => setData('twitter_title', e.target.value)}
                                                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                placeholder="Leave empty to use OG Title"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">Twitter Description</label>
                                            <textarea
                                                value={data.twitter_description}
                                                onChange={(e) => setData('twitter_description', e.target.value)}
                                                rows={2}
                                                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                placeholder="Leave empty to use OG Description"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">Twitter Image URL</label>
                                            <input
                                                type="text"
                                                value={data.twitter_image}
                                                onChange={(e) => setData('twitter_image', e.target.value)}
                                                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                placeholder="Leave empty to use OG Image"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </Card>
                    )}

                    {/* Schema Tab */}
                    {activeTab === 'schema' && (
                        <Card className="p-6 bg-white border border-gray-200">
                            <h3 className="text-lg font-semibold text-gray-900 mb-4">Schema.org JSON-LD</h3>
                            <div className="space-y-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">Apply Template</label>
                                    <div className="flex flex-wrap gap-2">
                                        {Object.keys(schemaTemplates || {}).map((key) => (
                                            <button
                                                key={key}
                                                type="button"
                                                onClick={() => handleApplySchemaTemplate(key)}
                                                className="px-3 py-1 text-sm bg-gray-100 hover:bg-gray-200 rounded-md transition-colors"
                                            >
                                                {key}
                                            </button>
                                        ))}
                                        <button
                                            type="button"
                                            onClick={() => setData('schema_json', null)}
                                            className="px-3 py-1 text-sm bg-red-100 hover:bg-red-200 text-red-700 rounded-md transition-colors"
                                        >
                                            Clear
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Schema JSON</label>
                                    <textarea
                                        value={data.schema_json ? JSON.stringify(data.schema_json, null, 2) : ''}
                                        onChange={(e) => {
                                            try {
                                                const parsed = e.target.value ? JSON.parse(e.target.value) : null;
                                                setData('schema_json', parsed);
                                            } catch (err) {
                                                // Allow typing invalid JSON temporarily
                                            }
                                        }}
                                        rows={15}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-md font-mono text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        placeholder='{"@context": "https://schema.org", "@type": "WebPage", ...}'
                                    />
                                    <p className="text-xs text-gray-500 mt-1">Valid JSON-LD for structured data</p>
                                </div>
                            </div>
                        </Card>
                    )}

                    {/* Settings Tab */}
                    {activeTab === 'settings' && (
                        <Card className="p-6 bg-white border border-gray-200">
                            <h3 className="text-lg font-semibold text-gray-900 mb-4">Page Settings</h3>
                            <div className="space-y-4">
                                <div className="flex items-center gap-4">
                                    <label className="flex items-center gap-2 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            checked={data.is_active}
                                            onChange={(e) => setData('is_active', e.target.checked)}
                                            className="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                        />
                                        <span className="text-sm text-gray-700">Page is active</span>
                                    </label>
                                </div>
                                <div className="flex items-center gap-6">
                                    <label className="flex items-center gap-2 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            checked={data.is_indexable}
                                            onChange={(e) => setData('is_indexable', e.target.checked)}
                                            className="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                        />
                                        <span className="text-sm text-gray-700">Allow search engines to index</span>
                                    </label>
                                    <label className="flex items-center gap-2 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            checked={data.is_followable}
                                            onChange={(e) => setData('is_followable', e.target.checked)}
                                            className="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                        />
                                        <span className="text-sm text-gray-700">Allow search engines to follow links</span>
                                    </label>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Canonical URL</label>
                                    <input
                                        type="text"
                                        value={data.canonical_url}
                                        onChange={(e) => setData('canonical_url', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        placeholder="https://example.com/page (leave empty for default)"
                                    />
                                    <p className="text-xs text-gray-500 mt-1">Set if this page has duplicate content elsewhere</p>
                                </div>

                                {/* Page Info */}
                                <div className="pt-4 mt-4 border-t">
                                    <h4 className="text-sm font-medium text-gray-700 mb-2">Page Info</h4>
                                    <div className="text-sm text-gray-600 space-y-1">
                                        <p>Created: {new Date(page.created_at).toLocaleString()}</p>
                                        <p>Updated: {new Date(page.updated_at).toLocaleString()}</p>
                                        {page.updated_by_user && <p>Last updated by: {page.updated_by_user.name}</p>}
                                    </div>
                                </div>
                            </div>
                        </Card>
                    )}

                    {/* Submit */}
                    <div className="mt-6 flex justify-end gap-3">
                        <Link href="/admin/page-metas">
                            <Button type="button" variant="secondary">Cancel</Button>
                        </Link>
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Saving...' : 'Save Changes'}
                        </Button>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}
