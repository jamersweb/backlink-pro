import { useState, useEffect } from 'react';
import { useForm, usePage, Link } from '@inertiajs/react';
import AppLayout from '../../Components/Layout/AppLayout';
import Card from '../../Components/Shared/Card';
import Button from '../../Components/Shared/Button';
import Input from '../../Components/Shared/Input';

export default function CampaignCreate({ countries, states, cities, domains, connectedAccounts, planSettings, plan }) {
    const { flash } = usePage().props;
    const [currentStep, setCurrentStep] = useState(1);
    const totalSteps = 7;

    const { data, setData, post, processing, errors, reset } = useForm({
        // Step 1: Basic Info
        name: '',
        domain_id: '',
        target_urls: [],
        
        // Step 2: Brand & Niche
        company_name: '',
        company_logo: null,
        company_email_address: '',
        company_address: '',
        company_number: '',
        company_country: '',
        company_state: '',
        company_city: '',
        web_name: '',
        web_url: '',
        web_keyword: '',
        web_about: '',
        web_target: '',
        
        // Step 3: Keywords
        keywords: [],
        
        // Step 4: Backlink Types & Limits (from plan - auto-populated)
        backlink_types: planSettings?.backlink_types || [],
        daily_limit: planSettings?.daily_limit || 10,
        total_limit: planSettings?.total_limit || 300,
        
        // Step 5: Content Settings
        content_tone: 'professional',
        anchor_text_strategy: 'variation',
        
        // Step 6: Scheduling
        start_date: '',
        end_date: '',
        
        // Step 7: Gmail Verification
        gmail_account_id: '',
        requires_email_verification: true,
        gmail: '',
        password: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        if (currentStep < totalSteps) {
            setCurrentStep(currentStep + 1);
        } else {
            // Transform data before submission
            const submitData = {
                ...data,
                company_country: data.company_country ? parseInt(data.company_country) : '',
                company_state: data.company_state ? parseInt(data.company_state) : '',
                company_city: data.company_city ? parseInt(data.company_city) : null,
                gmail_account_id: data.gmail_account_id ? parseInt(data.gmail_account_id) : null,
            };
            
            // Remove gmail/password if gmail_account_id is set
            if (submitData.gmail_account_id) {
                delete submitData.gmail;
                delete submitData.password;
            }
            
            // Settings are auto-set from plan, so we keep them but backend will override
            // Keep backlink_types, daily_limit, total_limit from planSettings
            
            // Update form data
            Object.keys(submitData).forEach(key => {
                setData(key, submitData[key]);
            });
            
            post('/campaign', {
                forceFormData: true, // Important for file uploads
                onSuccess: () => {
                    reset();
                    setCurrentStep(1);
                },
                onError: (errors) => {
                    // Scroll to first error
                    const firstError = Object.keys(errors)[0];
                    if (firstError) {
                        const element = document.querySelector(`[name="${firstError}"]`);
                        if (element) {
                            element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            element.focus();
                        }
                    }
                },
            });
        }
    };

    const handleNext = () => {
        if (currentStep < totalSteps) {
            setCurrentStep(currentStep + 1);
        }
    };

    const handlePrevious = () => {
        if (currentStep > 1) {
            setCurrentStep(currentStep - 1);
        }
    };

    const renderStepContent = () => {
        switch (currentStep) {
            case 1:
                return (
                    <div className="space-y-4">
                        <h3 className="text-lg font-medium">Basic Information</h3>
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
                                Domain <span className="text-xs text-gray-500">(Optional)</span>
                            </label>
                            {domains && domains.length > 0 ? (
                                <select
                                    name="domain_id"
                                    value={data.domain_id}
                                    onChange={(e) => setData('domain_id', e.target.value)}
                                    className="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                >
                                    <option value="">Select a domain (optional)</option>
                                    {domains.map((domain) => (
                                        <option key={domain.id} value={domain.id}>
                                            {domain.name}
                                        </option>
                                    ))}
                                </select>
                            ) : (
                                <div className="p-4 bg-yellow-50 border border-yellow-200 rounded-md">
                                    <p className="text-sm text-yellow-800 mb-2">
                                        You don't have any domains yet. You can create a campaign without selecting a domain, or create a domain first.
                                    </p>
                                    <Link
                                        href="/domains/create"
                                        className="inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-800"
                                    >
                                        Create Domain →
                                    </Link>
                                </div>
                            )}
                            {errors.domain_id && (
                                <p className="mt-1 text-sm text-red-600">{errors.domain_id}</p>
                            )}
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Target URLs (one per line)
                            </label>
                            <textarea
                                name="target_urls"
                                value={data.target_urls?.join('\n') || ''}
                                onChange={(e) => setData('target_urls', e.target.value.split('\n').filter(url => url.trim()))}
                                rows={5}
                                className="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                placeholder="https://example.com/page1&#10;https://example.com/page2"
                            />
                        </div>
                    </div>
                );
            case 2:
                return (
                    <div className="space-y-4">
                        <h3 className="text-lg font-medium">Brand & Niche Information</h3>
                        <Input
                            label="Company Name *"
                            name="company_name"
                            value={data.company_name}
                            onChange={(e) => setData('company_name', e.target.value)}
                            error={errors.company_name}
                            required
                        />
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Company Logo * <span className="text-xs text-gray-500">(JPG, PNG, Max 2MB)</span>
                            </label>
                            <input
                                type="file"
                                name="company_logo"
                                accept="image/jpeg,image/png,image/jpg"
                                onChange={(e) => setData('company_logo', e.target.files[0])}
                                className="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                            />
                            {errors.company_logo && (
                                <p className="mt-1 text-sm text-red-600">{errors.company_logo}</p>
                            )}
                        </div>
                        <Input
                            label="Company Email *"
                            name="company_email_address"
                            type="email"
                            value={data.company_email_address}
                            onChange={(e) => setData('company_email_address', e.target.value)}
                            error={errors.company_email_address}
                            required
                        />
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Company Address *
                            </label>
                            <textarea
                                name="company_address"
                                value={data.company_address}
                                onChange={(e) => setData('company_address', e.target.value)}
                                rows={3}
                                className="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                required
                            />
                            {errors.company_address && (
                                <p className="mt-1 text-sm text-red-600">{errors.company_address}</p>
                            )}
                        </div>
                        <Input
                            label="Company Phone Number *"
                            name="company_number"
                            value={data.company_number}
                            onChange={(e) => setData('company_number', e.target.value)}
                            error={errors.company_number}
                            required
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
                                {states?.filter(s => String(s.country_id) === String(data.company_country)).length === 0 && (
                                    <p className="mt-1 text-sm text-yellow-600">No states available for this country. Please select a different country or contact support.</p>
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
                        <Input
                            label="Website Name *"
                            name="web_name"
                            value={data.web_name}
                            onChange={(e) => setData('web_name', e.target.value)}
                            error={errors.web_name}
                            required
                        />
                        <Input
                            label="Website URL *"
                            name="web_url"
                            type="url"
                            value={data.web_url}
                            onChange={(e) => setData('web_url', e.target.value)}
                            error={errors.web_url}
                            required
                        />
                        <Input
                            label="Website Keyword *"
                            name="web_keyword"
                            value={data.web_keyword}
                            onChange={(e) => setData('web_keyword', e.target.value)}
                            error={errors.web_keyword}
                            required
                        />
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Website About *
                            </label>
                            <textarea
                                name="web_about"
                                value={data.web_about}
                                onChange={(e) => setData('web_about', e.target.value)}
                                rows={4}
                                className="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                required
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
                                <option value="">Select target</option>
                                <option value="worldwide">Worldwide</option>
                                <option value="specific_country">Specific Country</option>
                            </select>
                            {errors.web_target && (
                                <p className="mt-1 text-sm text-red-600">{errors.web_target}</p>
                            )}
                        </div>
                        {data.web_target === 'specific_country' && (
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Target Country Name *
                                </label>
                                <input
                                    type="text"
                                    name="country_name"
                                    value={data.country_name}
                                    onChange={(e) => setData('country_name', e.target.value)}
                                    className="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    placeholder="e.g., United States"
                                    required
                                />
                                {errors.country_name && (
                                    <p className="mt-1 text-sm text-red-600">{errors.country_name}</p>
                                )}
                            </div>
                        )}
                    </div>
                );
            case 3:
                return (
                    <div className="space-y-4">
                        <h3 className="text-lg font-medium">Keywords</h3>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Keywords (one per line)
                            </label>
                            <textarea
                                name="keywords"
                                value={data.keywords?.join('\n') || ''}
                                onChange={(e) => setData('keywords', e.target.value.split('\n').filter(k => k.trim()))}
                                rows={6}
                                className="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                placeholder="keyword 1&#10;keyword 2&#10;keyword 3"
                            />
                        </div>
                    </div>
                );
            case 4:
                return (
                    <div className="space-y-4">
                        <h3 className="text-lg font-medium">Campaign Settings</h3>
                        {plan && (
                            <div className="p-4 bg-blue-50 border border-blue-200 rounded-md mb-4">
                                <p className="text-sm font-semibold text-blue-900 mb-2">
                                    📋 Settings from your plan: <span className="text-blue-700">{plan.name}</span>
                                </p>
                                <div className="text-sm text-blue-800 space-y-1">
                                    <p>• Daily Limit: <strong>{plan.daily_backlink_limit === -1 ? 'Unlimited' : plan.daily_backlink_limit}</strong> backlinks/day</p>
                                    <p>• Allowed Backlink Types: <strong>{plan.backlink_types?.join(', ') || 'None'}</strong></p>
                                </div>
                            </div>
                        )}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Backlink Types (from your plan)
                            </label>
                            <div className="space-y-2 p-3 bg-gray-50 rounded-md">
                                {['comment', 'profile', 'forum', 'guestposting'].map((type) => {
                                    const isAllowed = data.backlink_types?.includes(type) || false;
                                    return (
                                        <label key={type} className="flex items-center">
                                            <input
                                                type="checkbox"
                                                checked={isAllowed}
                                                disabled={true}
                                                className="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                            />
                                            <span className={`ml-2 text-sm capitalize ${isAllowed ? 'text-gray-900 font-medium' : 'text-gray-400'}`}>
                                                {type} {isAllowed && '✓'}
                                            </span>
                                        </label>
                                    );
                                })}
                            </div>
                            <p className="mt-2 text-xs text-gray-500">
                                These settings are automatically set based on your plan. Upgrade your plan to access more backlink types.
                            </p>
                        </div>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div className="p-3 bg-gray-50 rounded-md">
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Daily Limit
                                </label>
                                <p className="text-lg font-semibold text-gray-900">
                                    {plan?.daily_backlink_limit === -1 ? 'Unlimited' : plan?.daily_backlink_limit || data.daily_limit}
                                </p>
                                <p className="text-xs text-gray-500 mt-1">Set by your plan</p>
                            </div>
                            <div className="p-3 bg-gray-50 rounded-md">
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Total Limit (Estimated Monthly)
                                </label>
                                <p className="text-lg font-semibold text-gray-900">
                                    {plan?.daily_backlink_limit === -1 ? 'Unlimited' : (plan?.daily_backlink_limit * 30) || data.total_limit}
                                </p>
                                <p className="text-xs text-gray-500 mt-1">Based on daily limit × 30 days</p>
                            </div>
                        </div>
                    </div>
                );
            case 5:
                return (
                    <div className="space-y-4">
                        <h3 className="text-lg font-medium">Content Settings</h3>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Content Tone
                            </label>
                            <select
                                name="content_tone"
                                value={data.content_tone}
                                onChange={(e) => setData('content_tone', e.target.value)}
                                className="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            >
                                <option value="professional">Professional</option>
                                <option value="casual">Casual</option>
                                <option value="friendly">Friendly</option>
                                <option value="formal">Formal</option>
                            </select>
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Anchor Text Strategy
                            </label>
                            <select
                                name="anchor_text_strategy"
                                value={data.anchor_text_strategy}
                                onChange={(e) => setData('anchor_text_strategy', e.target.value)}
                                className="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            >
                                <option value="variation">Variation</option>
                                <option value="exact">Exact Match</option>
                                <option value="branded">Branded</option>
                            </select>
                        </div>
                    </div>
                );
            case 6:
                return (
                    <div className="space-y-4">
                        <h3 className="text-lg font-medium">Scheduling</h3>
                        <Input
                            label="Start Date"
                            name="start_date"
                            type="date"
                            value={data.start_date}
                            onChange={(e) => setData('start_date', e.target.value)}
                            error={errors.start_date}
                        />
                        <Input
                            label="End Date (Optional)"
                            name="end_date"
                            type="date"
                            value={data.end_date}
                            onChange={(e) => setData('end_date', e.target.value)}
                            error={errors.end_date}
                        />
                    </div>
                );
            case 7:
                return (
                    <div className="space-y-4">
                        <h3 className="text-lg font-medium">Gmail Verification Settings</h3>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Connected Gmail Account (Optional)
                            </label>
                            <select
                                name="gmail_account_id"
                                value={data.gmail_account_id}
                                onChange={(e) => {
                                    setData('gmail_account_id', e.target.value);
                                    if (e.target.value) {
                                        // Clear manual gmail/password when connected account is selected
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
                        {data.gmail_account_id && (
                            <div className="p-3 bg-green-50 border border-green-200 rounded-md">
                                <p className="text-sm text-green-800">
                                    ✓ Using connected account: {connectedAccounts?.find(a => a.id == data.gmail_account_id)?.email}
                                </p>
                            </div>
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
                );
            default:
                return null;
        }
    };

    return (
        <AppLayout header="Create Campaign">
            {/* Success Message */}
            {flash?.success && (
                <div className="mb-4 p-4 bg-green-50 border border-green-200 rounded-md">
                    <p className="text-sm text-green-800">{flash.success}</p>
                </div>
            )}
            
            {/* Error Messages */}
            {Object.keys(errors).length > 0 && (
                <div className="mb-4 p-4 bg-red-50 border border-red-200 rounded-md">
                    <p className="text-sm font-semibold text-red-800 mb-2">Please fix the following errors:</p>
                    <ul className="list-disc list-inside text-sm text-red-700">
                        {Object.entries(errors).map(([key, value]) => (
                            <li key={key}>{value}</li>
                        ))}
                    </ul>
                </div>
            )}
            
            <Card>
                {/* Progress Bar */}
                <div className="mb-6">
                    <div className="flex items-center justify-between mb-2">
                        <span className="text-sm font-medium text-gray-700">
                            Step {currentStep} of {totalSteps}
                        </span>
                        <span className="text-sm text-gray-500">
                            {Math.round((currentStep / totalSteps) * 100)}% Complete
                        </span>
                    </div>
                    <div className="w-full bg-gray-200 rounded-full h-2">
                        <div
                            className="bg-indigo-600 h-2 rounded-full transition-all duration-300"
                            style={{ width: `${(currentStep / totalSteps) * 100}%` }}
                        />
                    </div>
                </div>

                {/* Step Content */}
                <form onSubmit={handleSubmit}>
                    {renderStepContent()}

                    {/* Navigation Buttons */}
                    <div className="mt-6 flex justify-between">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={handlePrevious}
                            disabled={currentStep === 1}
                        >
                            Previous
                        </Button>
                        {currentStep < totalSteps ? (
                            <Button type="button" variant="primary" onClick={handleNext}>
                                Next
                            </Button>
                        ) : (
                            <Button type="submit" variant="primary" disabled={processing}>
                                {processing ? 'Creating...' : 'Create Campaign'}
                            </Button>
                        )}
                    </div>
                </form>
            </Card>
        </AppLayout>
    );
}

