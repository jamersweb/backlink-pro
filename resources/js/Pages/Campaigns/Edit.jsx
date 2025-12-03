import { useState } from 'react';
import { useForm, usePage, Link } from '@inertiajs/react';
import AppLayout from '../../Components/Layout/AppLayout';
import Card from '../../Components/Shared/Card';
import Button from '../../Components/Shared/Button';
import Input from '../../Components/Shared/Input';

export default function CampaignEdit({ campaign, countries, states, cities, domains, connectedAccounts }) {
    const { flash } = usePage().props;
    const { data, setData, put, processing, errors } = useForm({
        name: campaign.name || '',
        domain_id: campaign.domain_id || '',
        target_urls: campaign.target_urls || [],
        company_name: campaign.company_name || '',
        company_logo: null,
        company_email_address: campaign.company_email_address || '',
        company_address: campaign.company_address || '',
        company_number: campaign.company_number || '',
        company_country: campaign.company_country ? String(campaign.company_country) : '',
        company_state: campaign.company_state ? String(campaign.company_state) : '',
        company_city: campaign.company_city ? String(campaign.company_city) : '',
        web_name: campaign.web_name || '',
        web_url: campaign.web_url || '',
        web_keyword: campaign.web_keyword || '',
        web_about: campaign.web_about || '',
        web_target: campaign.web_target || 'worldwide',
        country_name: campaign.country_name || '',
        keywords: campaign.keywords || [],
        start_date: campaign.start_date || '',
        end_date: campaign.end_date || '',
        gmail_account_id: campaign.gmail_account_id ? String(campaign.gmail_account_id) : '',
        requires_email_verification: campaign.requires_email_verification ?? true,
        gmail: campaign.gmail || '',
        password: campaign.password || '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        
        // Transform data before submission - similar to Create form
        const submitData = { ...data };
        
        // Trim all string values
        Object.keys(submitData).forEach(key => {
            if (typeof submitData[key] === 'string') {
                submitData[key] = submitData[key].trim();
            }
        });
        
        // Convert empty strings to null for optional fields
        if (submitData.domain_id === '') submitData.domain_id = null;
        if (submitData.company_city === '') submitData.company_city = null;
        if (submitData.gmail_account_id === '') submitData.gmail_account_id = null;
        
        // Convert country/state to integers
        if (submitData.company_country) {
            submitData.company_country = parseInt(submitData.company_country) || null;
        }
        if (submitData.company_state) {
            submitData.company_state = parseInt(submitData.company_state) || null;
        }
        if (submitData.company_city) {
            submitData.company_city = parseInt(submitData.company_city) || null;
        }
        if (submitData.gmail_account_id) {
            submitData.gmail_account_id = parseInt(submitData.gmail_account_id) || null;
        }
        
        // Clear gmail/password if gmail_account_id is set
        if (submitData.gmail_account_id) {
            submitData.gmail = '';
            submitData.password = '';
        }
        
        // Clear country_name if web_target is worldwide
        if (submitData.web_target === 'worldwide') {
            submitData.country_name = '';
        }
        
        // Remove logo if not changed (null or empty)
        if (!submitData.company_logo) {
            delete submitData.company_logo;
        }
        
        // Update form fields in DOM to ensure forceFormData reads correct values
        Object.keys(submitData).forEach(key => {
            const element = document.querySelector(`[name="${key}"]`);
            if (element) {
                if (element.tagName === 'SELECT' || element.tagName === 'INPUT' || element.tagName === 'TEXTAREA') {
                    if (element.type === 'checkbox' || element.type === 'radio') {
                        element.checked = submitData[key];
                    } else {
                        element.value = submitData[key] !== null && submitData[key] !== undefined ? submitData[key] : '';
                    }
                }
            }
        });
        
        // Update form state
        Object.keys(submitData).forEach(key => {
            setData(key, submitData[key]);
        });
        
        // Use forceFormData only if there's a file upload
        const hasFileUpload = submitData.company_logo instanceof File;
        
        // Submit with a small delay to ensure DOM and state are synced
        setTimeout(() => {
            put(`/campaign/${campaign.id}`, {
                forceFormData: hasFileUpload,
                preserveScroll: true,
                onSuccess: () => {
                    // Success handled by redirect
                },
                onError: (errors) => {
                    // Scroll to first error
                    const firstError = Object.keys(errors)[0];
                    if (firstError) {
                        const element = document.querySelector(`[name="${firstError}"]`);
                        if (element) {
                            element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                    }
                },
            });
        }, 50);
    };

    return (
        <AppLayout header="Edit Campaign">
            {/* Success/Error Messages */}
            {flash?.success && (
                <div className="mb-4 p-4 bg-green-50 border border-green-200 rounded-md">
                    <p className="text-sm text-green-800">{flash.success}</p>
                </div>
            )}
            {flash?.error && (
                <div className="mb-4 p-4 bg-red-50 border border-red-200 rounded-md">
                    <p className="text-sm text-red-800">{flash.error}</p>
                </div>
            )}
            
            <Card>
                <form onSubmit={handleSubmit} encType="multipart/form-data" className="space-y-6">
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
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Website Keywords * (comma-separated)
                            </label>
                            <input
                                type="text"
                                name="web_keyword"
                                value={Array.isArray(data.web_keyword) ? data.web_keyword.join(', ') : (data.web_keyword || '')}
                                onChange={(e) => {
                                    const value = e.target.value;
                                    // Store as comma-separated string for backend
                                    setData('web_keyword', value);
                                }}
                                placeholder="keyword1, keyword2, keyword3"
                                className="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                required
                            />
                            <p className="mt-1 text-xs text-gray-500">
                                Enter multiple keywords separated by commas (e.g., "SEO, backlinks, digital marketing")
                            </p>
                            {errors.web_keyword && (
                                <p className="mt-1 text-sm text-red-600">{errors.web_keyword}</p>
                            )}
                        </div>
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
                            {errors.web_about && (
                                <p className="mt-1 text-sm text-red-600">{errors.web_about}</p>
                            )}
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Ranking Target *
                            </label>
                            <select
                                name="web_target"
                                value={data.web_target}
                                onChange={(e) => setData('web_target', e.target.value)}
                                className="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                required
                            >
                                <option value="worldwide">Worldwide</option>
                                <option value="specific_country">Specific Country</option>
                            </select>
                            {errors.web_target && (
                                <p className="mt-1 text-sm text-red-600">{errors.web_target}</p>
                            )}
                        </div>
                        {data.web_target === 'specific_country' && (
                            <Input
                                label="Target Country Name *"
                                name="country_name"
                                value={data.country_name}
                                onChange={(e) => setData('country_name', e.target.value)}
                                error={errors.country_name}
                                required
                            />
                        )}
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
                                Company Country *
                            </label>
                            <select
                                name="company_country"
                                value={data.company_country}
                                onChange={(e) => {
                                    setData('company_country', e.target.value);
                                    setData('company_state', ''); // Reset state when country changes
                                    setData('company_city', ''); // Reset city when country changes
                                }}
                                className="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                required
                            >
                                <option value="">Select a country</option>
                                {countries?.map((country) => (
                                    <option key={country.id} value={country.id}>
                                        {country.name}
                                    </option>
                                ))}
                            </select>
                            {errors.company_country && (
                                <p className="mt-1 text-sm text-red-600">{errors.company_country}</p>
                            )}
                        </div>
                        {data.company_country && (
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Company State *
                                </label>
                                <select
                                    name="company_state"
                                    value={data.company_state}
                                    onChange={(e) => {
                                        setData('company_state', e.target.value);
                                        setData('company_city', ''); // Reset city when state changes
                                    }}
                                    className="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    required
                                >
                                    <option value="">Select a state</option>
                                    {states?.filter(s => String(s.country_id) === String(data.company_country)).map((state) => (
                                        <option key={state.id} value={state.id}>
                                            {state.name}
                                        </option>
                                    ))}
                                </select>
                                {errors.company_state && (
                                    <p className="mt-1 text-sm text-red-600">{errors.company_state}</p>
                                )}
                            </div>
                        )}
                        {data.company_state && (
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Company City
                                </label>
                                <select
                                    name="company_city"
                                    value={data.company_city}
                                    onChange={(e) => setData('company_city', e.target.value)}
                                    className="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                >
                                    <option value="">Select a city</option>
                                    {cities?.filter(c => String(c.state_id) === String(data.company_state)).map((city) => (
                                        <option key={city.id} value={city.id}>
                                            {city.name}
                                        </option>
                                    ))}
                                </select>
                                {errors.company_city && (
                                    <p className="mt-1 text-sm text-red-600">{errors.company_city}</p>
                                )}
                            </div>
                        )}
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
                            {errors.company_logo && (
                                <p className="mt-1 text-sm text-red-600">{errors.company_logo}</p>
                            )}
                        </div>
                    </div>

                    {/* Gmail Verification */}
                    <div className="space-y-4">
                        <h3 className="text-lg font-medium border-b pb-2">Gmail Verification Settings</h3>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Connected Gmail Account (Optional)
                            </label>
                            <select
                                name="gmail_account_id"
                                value={data.gmail_account_id}
                                onChange={(e) => {
                                    setData('gmail_account_id', e.target.value);
                                    // Clear manual gmail/password if a connected account is selected
                                    if (e.target.value) {
                                        setData('gmail', '');
                                        setData('password', '');
                                    }
                                }}
                                className="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            >
                                <option value="">Select a Gmail account (or enter manually below)</option>
                                {connectedAccounts?.map((account) => (
                                    <option key={account.id} value={account.id}>
                                        {account.email}
                                    </option>
                                ))}
                            </select>
                            {errors.gmail_account_id && (
                                <p className="mt-1 text-sm text-red-600">{errors.gmail_account_id}</p>
                            )}
                            {data.gmail_account_id && (
                                <p className="mt-2 text-sm text-green-600">
                                    Using connected account: {connectedAccounts?.find(acc => String(acc.id) === String(data.gmail_account_id))?.email}
                                </p>
                            )}
                        </div>
                        {!data.gmail_account_id && (
                            <>
                                <Input
                                    label="Gmail Address *"
                                    name="gmail"
                                    type="email"
                                    value={data.gmail}
                                    onChange={(e) => setData('gmail', e.target.value)}
                                    error={errors.gmail}
                                    placeholder="your-email@gmail.com"
                                    required
                                />
                                <Input
                                    label="Gmail Password *"
                                    name="password"
                                    type="password"
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    error={errors.password}
                                    required
                                />
                            </>
                        )}
                        <label className="flex items-center">
                            <input
                                type="checkbox"
                                checked={data.requires_email_verification}
                                onChange={(e) => setData('requires_email_verification', e.target.checked)}
                                className="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            />
                            <span className="ml-2 text-sm text-gray-700">Require email verification</span>
                        </label>
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

