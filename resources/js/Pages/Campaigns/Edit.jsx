import { useState } from 'react';
import { useForm } from '@inertiajs/react';
import AppLayout from '../../Components/Layout/AppLayout';
import Card from '../../Components/Shared/Card';
import Button from '../../Components/Shared/Button';
import Input from '../../Components/Shared/Input';

export default function CampaignEdit({ campaign, countries, states, cities, domains, connectedAccounts }) {
    const { data, setData, put, processing, errors } = useForm({
        name: campaign.name || '',
        domain_id: campaign.domain_id || '',
        target_urls: campaign.target_urls || [],
        company_name: campaign.company_name || '',
        company_logo: null,
        company_email_address: campaign.company_email_address || '',
        company_address: campaign.company_address || '',
        company_number: campaign.company_number || '',
        company_country: campaign.company_country || '',
        company_state: campaign.company_state || '',
        company_city: campaign.company_city || '',
        web_name: campaign.web_name || '',
        web_url: campaign.web_url || '',
        web_keyword: campaign.web_keyword || '',
        web_about: campaign.web_about || '',
        web_target: campaign.web_target || '',
        keywords: campaign.keywords || [],
        backlink_types: campaign.settings?.backlink_types || [],
        daily_limit: campaign.settings?.daily_limit || campaign.daily_limit || 10,
        total_limit: campaign.settings?.total_limit || campaign.total_limit || 100,
        content_tone: campaign.settings?.content_tone || 'professional',
        anchor_text_strategy: campaign.settings?.anchor_text_strategy || 'variation',
        start_date: campaign.start_date || '',
        end_date: campaign.end_date || '',
        gmail_account_id: campaign.gmail_account_id || '',
        requires_email_verification: campaign.requires_email_verification ?? true,
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        put(`/campaign/${campaign.id}`);
    };

    return (
        <AppLayout header="Edit Campaign">
            <Card>
                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Basic Info */}
                    <div className="space-y-4">
                        <h3 className="text-lg font-medium border-b pb-2">Basic Information</h3>
                        <Input
                            label="Campaign Name"
                            name="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            error={errors.name}
                            required
                        />
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Domain
                            </label>
                            <select
                                name="domain_id"
                                value={data.domain_id}
                                onChange={(e) => setData('domain_id', e.target.value)}
                                className="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            >
                                <option value="">Select a domain</option>
                                {domains?.map((domain) => (
                                    <option key={domain.id} value={domain.id}>
                                        {domain.name}
                                    </option>
                                ))}
                            </select>
                            {errors.domain_id && (
                                <p className="mt-1 text-sm text-red-600">{errors.domain_id}</p>
                            )}
                        </div>
                    </div>

                    {/* Website Info */}
                    <div className="space-y-4">
                        <h3 className="text-lg font-medium border-b pb-2">Website Information</h3>
                        <Input
                            label="Website Name"
                            name="web_name"
                            value={data.web_name}
                            onChange={(e) => setData('web_name', e.target.value)}
                            error={errors.web_name}
                        />
                        <Input
                            label="Website URL"
                            name="web_url"
                            type="url"
                            value={data.web_url}
                            onChange={(e) => setData('web_url', e.target.value)}
                            error={errors.web_url}
                        />
                        <Input
                            label="Website Keyword"
                            name="web_keyword"
                            value={data.web_keyword}
                            onChange={(e) => setData('web_keyword', e.target.value)}
                            error={errors.web_keyword}
                        />
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Website About
                            </label>
                            <textarea
                                name="web_about"
                                value={data.web_about}
                                onChange={(e) => setData('web_about', e.target.value)}
                                rows={4}
                                className="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            />
                        </div>
                    </div>

                    {/* Company Info */}
                    <div className="space-y-4">
                        <h3 className="text-lg font-medium border-b pb-2">Company Information</h3>
                        <Input
                            label="Company Name"
                            name="company_name"
                            value={data.company_name}
                            onChange={(e) => setData('company_name', e.target.value)}
                            error={errors.company_name}
                        />
                        <Input
                            label="Company Email"
                            name="company_email_address"
                            type="email"
                            value={data.company_email_address}
                            onChange={(e) => setData('company_email_address', e.target.value)}
                            error={errors.company_email_address}
                        />
                        <Input
                            label="Company Address"
                            name="company_address"
                            value={data.company_address}
                            onChange={(e) => setData('company_address', e.target.value)}
                            error={errors.company_address}
                        />
                        <Input
                            label="Company Phone"
                            name="company_number"
                            value={data.company_number}
                            onChange={(e) => setData('company_number', e.target.value)}
                            error={errors.company_number}
                        />
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Company Logo (leave empty to keep current)
                            </label>
                            <input
                                type="file"
                                name="company_logo"
                                accept="image/*"
                                onChange={(e) => setData('company_logo', e.target.files[0])}
                                className="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                            />
                            {campaign.company_logo && (
                                <p className="mt-2 text-sm text-gray-500">Current: {campaign.company_logo}</p>
                            )}
                        </div>
                    </div>

                    {/* Settings */}
                    <div className="space-y-4">
                        <h3 className="text-lg font-medium border-b pb-2">Campaign Settings</h3>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Backlink Types
                            </label>
                            <div className="space-y-2">
                                {['comment', 'profile', 'forum', 'guestposting'].map((type) => (
                                    <label key={type} className="flex items-center">
                                        <input
                                            type="checkbox"
                                            checked={data.backlink_types?.includes(type) || false}
                                            onChange={(e) => {
                                                const types = data.backlink_types || [];
                                                if (e.target.checked) {
                                                    setData('backlink_types', [...types, type]);
                                                } else {
                                                    setData('backlink_types', types.filter(t => t !== type));
                                                }
                                            }}
                                            className="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        />
                                        <span className="ml-2 text-sm text-gray-700 capitalize">{type}</span>
                                    </label>
                                ))}
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <Input
                                label="Daily Limit"
                                name="daily_limit"
                                type="number"
                                value={data.daily_limit}
                                onChange={(e) => setData('daily_limit', parseInt(e.target.value))}
                                error={errors.daily_limit}
                            />
                            <Input
                                label="Total Limit"
                                name="total_limit"
                                type="number"
                                value={data.total_limit}
                                onChange={(e) => setData('total_limit', parseInt(e.target.value))}
                                error={errors.total_limit}
                            />
                        </div>
                    </div>

                    {/* Actions */}
                    <div className="flex gap-4">
                        <Button type="submit" variant="primary" disabled={processing}>
                            {processing ? 'Updating...' : 'Update Campaign'}
                        </Button>
                        <Button 
                            type="button" 
                            variant="outline"
                            onClick={() => window.history.back()}
                        >
                            Cancel
                        </Button>
                    </div>
                </form>
            </Card>
        </AppLayout>
    );
}

