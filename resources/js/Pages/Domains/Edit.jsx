import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '../../Components/Layout/AppLayout';
import Card from '../../Components/Shared/Card';
import Button from '../../Components/Shared/Button';
import Input from '../../Components/Shared/Input';
import Select from '../../Components/Shared/Select';

export default function DomainsEdit({ domain, platforms }) {
    const [urlChanged, setUrlChanged] = useState(false);
    const originalUrl = domain.url || '';

    const { data, setData, put, processing, errors } = useForm({
        name: domain.name || '',
        url: domain.url || '',
        platform: domain.platform || 'custom',
        status: domain.status || 'active',
        default_settings: domain.default_settings || {
            crawl_limit: 100,
            max_depth: 3,
            include_sitemap: true,
            user_agent: 'BacklinkProBot/1.0',
        },
    });

    const handleUrlChange = (e) => {
        setData('url', e.target.value);
        setUrlChanged(e.target.value !== originalUrl);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        put(`/domains/${domain.id}`);
    };

    const updateDefaultSetting = (key, value) => {
        setData('default_settings', {
            ...data.default_settings,
            [key]: value,
        });
    };

    return (
        <AppLayout header="Edit Domain">
            <Card>
                <form onSubmit={handleSubmit} className="space-y-6">
                    {urlChanged && (
                        <div className="p-4 bg-yellow-50 border border-yellow-200 rounded-md">
                            <p className="text-sm text-yellow-800">
                                <i className="bi bi-exclamation-triangle"></i> <strong>Warning:</strong> Changing the URL will reset the verification status. You'll need to verify the domain again.
                            </p>
                        </div>
                    )}

                    <Input
                        label="Website URL"
                        name="url"
                        type="url"
                        value={data.url}
                        onChange={handleUrlChange}
                        error={errors.url}
                        required
                        placeholder="https://example.com or example.com"
                    />

                    <Input
                        label="Display Name (Optional)"
                        name="name"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        error={errors.name}
                        placeholder="My Website"
                    />

                    <Select
                        label="Platform"
                        name="platform"
                        value={data.platform}
                        onChange={(e) => setData('platform', e.target.value)}
                        error={errors.platform}
                        required
                    >
                        {Object.entries(platforms || {}).map(([value, label]) => (
                            <option key={value} value={value}>{label}</option>
                        ))}
                    </Select>

                    <Select
                        label="Status"
                        name="status"
                        value={data.status}
                        onChange={(e) => setData('status', e.target.value)}
                        error={errors.status}
                    >
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </Select>

                    {/* Default Settings Section */}
                    <div className="border-t pt-6 mt-6">
                        <h3 className="text-lg font-semibold text-gray-900 mb-4">Default Settings</h3>
                        <div>
                            <Select
                                label="Crawl Limit"
                                name="default_crawl_limit"
                                value={String(data.default_settings?.crawl_limit ?? 100)}
                                onChange={(e) => updateDefaultSetting('crawl_limit', parseInt(e.target.value, 10))}
                                error={errors['default_settings.crawl_limit']}
                            >
                                <option value={20}>20 pages</option>
                                <option value={100}>100 pages</option>
                                <option value={500}>500 pages</option>
                            </Select>

                            <Select
                                label="Max Depth"
                                name="default_max_depth"
                                value={String(data.default_settings?.max_depth ?? 3)}
                                onChange={(e) => updateDefaultSetting('max_depth', parseInt(e.target.value, 10))}
                                error={errors['default_settings.max_depth']}
                            >
                                <option value={1}>1 level</option>
                                <option value={2}>2 levels</option>
                                <option value={3}>3 levels</option>
                                <option value={4}>4 levels</option>
                                <option value={5}>5 levels</option>
                            </Select>

                            <div className="flex items-center">
                                <input
                                    type="checkbox"
                                    id="include_sitemap"
                                    checked={data.default_settings?.include_sitemap || false}
                                    onChange={(e) => updateDefaultSetting('include_sitemap', e.target.checked)}
                                    className="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                />
                                <label htmlFor="include_sitemap" className="ml-2 block text-sm text-gray-700">
                                    Include sitemap
                                </label>
                            </div>
                        </div>
                    </div>

                    <div className="flex gap-4">
                        <Button type="submit" variant="primary" disabled={processing}>
                            {processing ? 'Updating...' : 'Update Domain'}
                        </Button>
                        <Button type="button" variant="outline" onClick={() => window.history.back()}>
                            Cancel
                        </Button>
                    </div>
                </form>
            </Card>
        </AppLayout>
    );
}

