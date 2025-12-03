import { useForm } from '@inertiajs/react';
import AppLayout from '../../Components/Layout/AppLayout';
import Card from '../../Components/Shared/Card';
import Button from '../../Components/Shared/Button';
import Input from '../../Components/Shared/Input';

export default function SiteAccountsEdit({ siteAccount, campaigns }) {
    const { data, setData, put, processing, errors } = useForm({
        campaign_id: siteAccount.campaign_id || '',
        site_domain: siteAccount.site_domain || '',
        login_email: siteAccount.login_email || '',
        username: siteAccount.username || '',
        password: '',
        status: siteAccount.status || 'created',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        put(`/site-accounts/${siteAccount.id}`);
    };

    return (
        <AppLayout header="Edit Site Account">
            <Card>
                <form onSubmit={handleSubmit} className="space-y-6">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Campaign</label>
                        <select
                            name="campaign_id"
                            value={data.campaign_id}
                            onChange={(e) => setData('campaign_id', e.target.value)}
                            className="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            required
                        >
                            <option value="">Select a campaign</option>
                            {campaigns?.map((campaign) => (
                                <option key={campaign.id} value={campaign.id}>
                                    {campaign.name}
                                </option>
                            ))}
                        </select>
                        {errors.campaign_id && (
                            <p className="mt-1 text-sm text-red-600">{errors.campaign_id}</p>
                        )}
                    </div>

                    <Input
                        label="Site Domain"
                        name="site_domain"
                        value={data.site_domain}
                        onChange={(e) => setData('site_domain', e.target.value)}
                        error={errors.site_domain}
                        required
                    />

                    <Input
                        label="Login Email"
                        name="login_email"
                        type="email"
                        value={data.login_email}
                        onChange={(e) => setData('login_email', e.target.value)}
                        error={errors.login_email}
                        required
                    />

                    <Input
                        label="Username"
                        name="username"
                        value={data.username}
                        onChange={(e) => setData('username', e.target.value)}
                        error={errors.username}
                    />

                    <Input
                        label="Password (leave empty to keep current)"
                        name="password"
                        type="password"
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                        error={errors.password}
                    />

                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select
                            name="status"
                            value={data.status}
                            onChange={(e) => setData('status', e.target.value)}
                            className="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        >
                            <option value="created">Created</option>
                            <option value="waiting_email">Waiting Email</option>
                            <option value="verified">Verified</option>
                            <option value="failed">Failed</option>
                        </select>
                    </div>

                    <div className="flex gap-4">
                        <Button type="submit" variant="primary" disabled={processing}>
                            {processing ? 'Updating...' : 'Update Site Account'}
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

