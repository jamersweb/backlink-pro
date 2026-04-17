import { useForm } from '@inertiajs/react';
import AppLayout from '../../Components/Layout/AppLayout';
import Card from '../../Components/Shared/Card';
import Button from '../../Components/Shared/Button';
import Input from '../../Components/Shared/Input';
import Select from '../../Components/Shared/Select';

export default function DomainsCreate({ platforms, defaultSettings }) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        url: '',
        platform: 'custom',
        status: 'active',
        default_settings: defaultSettings || {
            crawl_limit: 100,
            max_depth: 3,
            include_sitemap: true,
            user_agent: 'BacklinkProBot/1.0',
        },
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/domains');
    };

    const updateDefaultSetting = (key, value) => {
        setData('default_settings', {
            ...data.default_settings,
            [key]: value,
        });
    };

    return (
        <AppLayout header="Add Domain">
            <Card>
                <form onSubmit={handleSubmit} className="space-y-6">
                    <Input
                        label="Website URL"
                        name="url"
                        type="url"
                        value={data.url}
                        onChange={(e) => setData('url', e.target.value)}
                        error={errors.url}
                        required
                        placeholder="https://example.com or example.com"
                        helpText="Enter the full website URL. We'll automatically extract the domain."
                    />

                    <Input
                        label="Display Name (Optional)"
                        name="name"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        error={errors.name}
                        placeholder="My Website"
                        helpText="Leave blank to use the domain name as display name."
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

                    {errors.domain_limit && (
                        <div className="p-4 bg-red-50 border border-red-200 rounded-md">
                            <p className="text-sm text-red-800">{errors.domain_limit}</p>
                        </div>
                    )}

                    {errors.plan && (
                        <div className="p-4 bg-red-50 border border-red-200 rounded-md">
                            <p className="text-sm text-red-800">{errors.plan}</p>
                        </div>
                    )}

                    <div className="flex gap-4">
                        <Button type="submit" variant="primary" disabled={processing}>
                            {processing ? 'Creating...' : 'Create Domain'}
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

