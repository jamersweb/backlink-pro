import { useState, useEffect } from 'react';
import { useForm, usePage, Link } from '@inertiajs/react';
import AppLayout from '../../Components/Layout/AppLayout';
import Card from '../../Components/Shared/Card';
import Button from '../../Components/Shared/Button';
import Input from '../../Components/Shared/Input';
import Select from '../../Components/Shared/Select';
import Textarea from '../../Components/Shared/Textarea';

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
                        {domains && domains.length > 0 ? (
                            <Select
                                label="Domain (Optional)"
                                name="domain_id"
                                value={data.domain_id}
                                onChange={(e) => setData('domain_id', e.target.value)}
                                error={errors.domain_id}
                            >
                                <option value="">Select a domain (optional)</option>
                                {domains.map((domain) => (
                                    <option key={domain.id} value={domain.id}>
                                        {domain.name}
                                    </option>
                                ))}
                            </Select>
                        ) : (
                            <div className="mb-5 p-4 bg-gradient-to-r from-yellow-50 to-orange-50 border-2 border-yellow-200 rounded-lg">
                                <div className="flex items-start gap-3">
                                    <div className="text-2xl">📋</div>
                                    <div>
                                        <p className="text-sm font-semibold text-yellow-900 mb-2">
                                            No domains available
                                        </p>
                                        <p className="text-sm text-yellow-800 mb-3">
                                            You don't have any domains yet. You can create a campaign without selecting a domain, or create a domain first.
                                        </p>
                                        <Link
                                            href="/domains/create"
                                            className="inline-flex items-center text-sm font-medium text-green-600 hover:text-green-800 transition-colors"
                                        >
                                            <span className="mr-1">➕</span> Create Domain →
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        )}
                        <Textarea
                            label="Target URLs (one per line)"
                            name="target_urls"
                            value={data.target_urls?.join('\n') || ''}
                            onChange={(e) => setData('target_urls', e.target.value.split('\n').filter(url => url.trim()))}
                            rows={5}
                            placeholder="https://example.com/page1&#10;https://example.com/page2"
                        />
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
                        <div className="mb-5">
                            <label className="block text-sm font-semibold text-gray-700 mb-2">
                                Company Logo * <span className="text-xs text-gray-500 font-normal">(JPG, PNG, Max 2MB)</span>
                            </label>
                            <div className="relative">
                                <input
                                    type="file"
                                    name="company_logo"
                                    accept="image/jpeg,image/png,image/jpg"
                                    onChange={(e) => setData('company_logo', e.target.files[0])}
                                    className="block w-full h-12 px-4 py-2 text-base text-gray-500 file:mr-4 file:py-2 file:px-6 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-gradient-to-r file:from-green-500 file:to-green-600 file:text-white hover:file:from-green-600 hover:file:to-green-700 file:cursor-pointer file:transition-all file:duration-200 border-2 border-gray-300 rounded-lg focus:border-green-500 focus:ring-2 focus:ring-green-200"
                                />
                            </div>
                            {errors.company_logo && (
                                <p className="mt-2 text-sm text-red-600 flex items-center gap-1">
                                    <svg className="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                                    </svg>
                                    {errors.company_logo}
                                </p>
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
                        <Textarea
                            label="Company Address *"
                            name="company_address"
                            value={data.company_address}
                            onChange={(e) => setData('company_address', e.target.value)}
                            rows={3}
                            error={errors.company_address}
                            required
                        />
                        <Input
                            label="Company Phone Number *"
                            name="company_number"
                            value={data.company_number}
                            onChange={(e) => setData('company_number', e.target.value)}
                            error={errors.company_number}
                            required
                        />
                        <Select
                            label="Company Country *"
                            name="company_country"
                            value={data.company_country}
                            onChange={(e) => {
                                setData('company_country', e.target.value);
                                setData('company_state', ''); // Reset state when country changes
                                setData('company_city', ''); // Reset city when country changes
                            }}
                            error={errors.company_country}
                            required
                        >
                            <option value="">Select a country</option>
                            {countries?.map((country) => (
                                <option key={country.id} value={country.id}>
                                    {country.name}
                                </option>
                            ))}
                        </Select>
                        {data.company_country && (
                            <Select
                                label="Company State *"
                                name="company_state"
                                value={data.company_state}
                                onChange={(e) => {
                                    setData('company_state', e.target.value);
                                    setData('company_city', ''); // Reset city when state changes
                                }}
                                error={errors.company_state}
                                required
                            >
                                <option value="">Select a state</option>
                                {states?.filter(s => String(s.country_id) === String(data.company_country)).map((state) => (
                                    <option key={state.id} value={state.id}>
                                        {state.name}
                                    </option>
                                ))}
                            </Select>
                        )}
                        {data.company_state && (
                            <Select
                                label="Company City"
                                name="company_city"
                                value={data.company_city}
                                onChange={(e) => setData('company_city', e.target.value)}
                                error={errors.company_city}
                            >
                                <option value="">Select a city</option>
                                {cities?.filter(c => String(c.state_id) === String(data.company_state)).map((city) => (
                                    <option key={city.id} value={city.id}>
                                        {city.name}
                                    </option>
                                ))}
                            </Select>
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
                        <Textarea
                            label="Website About *"
                            name="web_about"
                            value={data.web_about}
                            onChange={(e) => setData('web_about', e.target.value)}
                            rows={4}
                            error={errors.web_about}
                            required
                        />
                        <Select
                            label="Ranking Target *"
                            name="web_target"
                            value={data.web_target}
                            onChange={(e) => setData('web_target', e.target.value)}
                            error={errors.web_target}
                            required
                        >
                            <option value="">Select target</option>
                            <option value="worldwide">🌍 Worldwide</option>
                            <option value="specific_country">📍 Specific Country</option>
                        </Select>
                        {data.web_target === 'specific_country' && (
                            <Input
                                label="Target Country Name *"
                                name="country_name"
                                value={data.country_name}
                                onChange={(e) => setData('country_name', e.target.value)}
                                placeholder="e.g., United States"
                                error={errors.country_name}
                                required
                            />
                        )}
                    </div>
                );
            case 3:
                return (
                    <div className="space-y-4">
                        <h3 className="text-lg font-medium">Keywords</h3>
                        <Textarea
                            label="Keywords (one per line)"
                            name="keywords"
                            value={data.keywords?.join('\n') || ''}
                            onChange={(e) => setData('keywords', e.target.value.split('\n').filter(k => k.trim()))}
                            rows={6}
                            placeholder="keyword 1&#10;keyword 2&#10;keyword 3"
                        />
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
                        <Select
                            label="Content Tone"
                            name="content_tone"
                            value={data.content_tone}
                            onChange={(e) => setData('content_tone', e.target.value)}
                        >
                            <option value="professional">💼 Professional</option>
                            <option value="casual">😊 Casual</option>
                            <option value="friendly">👋 Friendly</option>
                            <option value="formal">🎩 Formal</option>
                        </Select>
                        <Select
                            label="Anchor Text Strategy"
                            name="anchor_text_strategy"
                            value={data.anchor_text_strategy}
                            onChange={(e) => setData('anchor_text_strategy', e.target.value)}
                        >
                            <option value="variation">🔄 Variation</option>
                            <option value="exact">🎯 Exact Match</option>
                            <option value="branded">🏷️ Branded</option>
                        </Select>
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

