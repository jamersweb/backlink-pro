import { useForm, Link } from '@inertiajs/react';
import AppLayout from '@/Components/Layout/AppLayout';

export default function PageSpeedSettings({ organization, settings }) {
    const form = useForm({
        pagespeed_byok_enabled: settings.pagespeed_byok_enabled || false,
        pagespeed_api_key: '',
    });

    const submit = (e) => {
        e.preventDefault();
        form.post(`/orgs/${organization.id}/settings/pagespeed`);
    };

    const verify = () => {
        form.post(`/orgs/${organization.id}/settings/pagespeed/verify`, {
            preserveScroll: true,
        });
    };

    return (
        <AppLayout header="PageSpeed Settings">
            <div className="space-y-6">
                <div className="bg-white rounded-lg shadow p-6">
                    <div className="flex items-center justify-between mb-4">
                        <h2 className="text-lg font-semibold text-gray-900">Google PageSpeed API Key</h2>
                        <Link href={`/orgs/${organization.id}/integrations/google`} className="text-sm text-blue-600 hover:text-blue-700">
                            Google Integrations
                        </Link>
                    </div>

                    <form onSubmit={submit} className="space-y-4">
                        <div className="flex items-center gap-3">
                            <input
                                id="pagespeed_byok_enabled"
                                type="checkbox"
                                checked={form.data.pagespeed_byok_enabled}
                                onChange={(e) => form.setData('pagespeed_byok_enabled', e.target.checked)}
                            />
                            <label htmlFor="pagespeed_byok_enabled" className="text-sm text-gray-700">
                                Use my own PageSpeed API Key
                            </label>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">API Key</label>
                            <input
                                type="text"
                                value={form.data.pagespeed_api_key}
                                onChange={(e) => form.setData('pagespeed_api_key', e.target.value)}
                                placeholder={settings.masked_key || 'Enter API key'}
                                className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm"
                            />
                            <p className="text-xs text-gray-500 mt-1">
                                {settings.masked_key ? `Saved key: ${settings.masked_key}` : 'No key saved yet.'}
                            </p>
                            {form.errors.pagespeed_api_key && (
                                <p className="text-xs text-red-600 mt-1">{form.errors.pagespeed_api_key}</p>
                            )}
                        </div>

                        <div className="flex items-center gap-3">
                            <button
                                type="submit"
                                className="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700"
                                disabled={form.processing}
                            >
                                Save
                            </button>
                            <button
                                type="button"
                                onClick={verify}
                                className="px-4 py-2 text-sm font-medium text-gray-700 border border-gray-300 rounded-md hover:bg-gray-50"
                                disabled={form.processing}
                            >
                                Verify Key
                            </button>
                        </div>
                    </form>

                    <div className="mt-4 text-sm text-gray-600">
                        Status: {settings.status_label}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
