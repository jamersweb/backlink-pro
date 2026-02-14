import { useState } from 'react';
import AdminLayout from '@/Components/Layout/AdminLayout';
import Card from '@/Components/Shared/Card';
import Button from '@/Components/Shared/Button';
import Input from '@/Components/Shared/Input';
import { router, usePage } from '@inertiajs/react';
import { useTheme } from '@/Contexts/ThemeContext';

export default function AdminSettingsIndex({
    captchaSettings,
    stripeSettings,
    googleSettings,
    llmSettings,
    apiSettings
}) {
    const { flash } = usePage().props;
    const { theme, setDarkMode, setLightMode } = useTheme();
    const [activeTab, setActiveTab] = useState('captcha');
    const [testing, setTesting] = useState(null);
    const [testResult, setTestResult] = useState(null);
    const [showKeys, setShowKeys] = useState({});

    // Form states for each service
    const [captchaForm, setCaptchaForm] = useState(captchaSettings || {});
    const [stripeForm, setStripeForm] = useState(stripeSettings || {});
    const [googleForm, setGoogleForm] = useState(googleSettings || {});
    const [llmForm, setLlmForm] = useState(llmSettings || {});
    const [apiForm, setApiForm] = useState(apiSettings || {});

    const handleSave = (group) => {
        let formData;
        switch (group) {
            case 'captcha':
                formData = captchaForm;
                break;
            case 'stripe':
                formData = stripeForm;
                break;
            case 'google':
                formData = googleForm;
                break;
            case 'llm':
                formData = llmForm;
                break;
            case 'api':
                formData = apiForm;
                break;
            default:
                return;
        }

        router.put(`/admin/settings/${group}`, { group, ...formData }, {
            preserveScroll: true,
        });
    };

    const handleTestConnection = async (service) => {
        setTesting(service);
        setTestResult(null);

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const response = await fetch('/admin/settings/test-connection', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ service }),
            });

            const data = await response.json();
            setTestResult({ ...data, service });
        } catch (error) {
            setTestResult({ success: false, message: 'Connection test failed', service });
        } finally {
            setTesting(null);
        }
    };

    const toggleShowKey = (keyName) => {
        setShowKeys(prev => ({ ...prev, [keyName]: !prev[keyName] }));
    };

    const tabs = [
        { id: 'captcha', label: 'Captcha', icon: 'bi-puzzle' },
        { id: 'stripe', label: 'Stripe', icon: 'bi-credit-card' },
        { id: 'google', label: 'Google OAuth', icon: 'bi-google' },
        { id: 'llm', label: 'LLM/AI', icon: 'bi-stars' },
        { id: 'api', label: 'API', icon: 'bi-plug' },
    ];

    // Sub-card component for form sections
    const FormSection = ({ title, icon, children }) => (
        <div className="bg-[var(--admin-surface-2)] border border-[var(--admin-border)] rounded-xl p-5 transition-all duration-150">
            <div className="flex items-center gap-3 mb-4">
                <div className="w-8 h-8 rounded-lg bg-[#2F6BFF]/15 flex items-center justify-center flex-shrink-0">
                    <i className={`bi ${icon} text-[#5B8AFF]`}></i>
                </div>
                <h3 className="text-base font-semibold text-[var(--admin-text)]">{title}</h3>
            </div>
            {children}
        </div>
    );

    // Input with icon component
    const InputWithIcon = ({ icon, showToggle, keyName, ...props }) => (
        <div className="relative">
            <div className="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                <i className={`bi ${icon} text-[var(--admin-text-dim)] text-sm`}></i>
            </div>
            <Input {...props} className="pl-10 pr-10" />
            {showToggle && (
                <button
                    type="button"
                    onClick={() => toggleShowKey(keyName)}
                    className="absolute inset-y-0 right-0 flex items-center pr-3 text-[var(--admin-text-muted)] hover:text-[var(--admin-text)] transition-colors duration-150"
                >
                    <i className={`bi ${showKeys[keyName] ? 'bi-eye-slash' : 'bi-eye'} text-sm`}></i>
                </button>
            )}
        </div>
    );

    return (
        <AdminLayout header="API Settings">
            <div className="space-y-6">
                {/* Success Message */}
                {flash?.success && (
                    <div className="p-4 rounded-xl border border-[#12B76A]/30 bg-[#12B76A]/10 animate-in fade-in duration-150">
                        <p className="text-sm text-[#12B76A] font-medium flex items-center gap-2">
                            <i className="bi bi-check-circle-fill"></i>
                            {flash.success}
                        </p>
                    </div>
                )}

                {/* Appearance Card with Theme Toggle */}
                <Card variant="elevated">
                    <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                        <div className="flex items-start gap-4">
                            <div className="w-12 h-12 rounded-xl bg-gradient-to-br from-[#2F6BFF]/20 to-[#B6F400]/20 flex items-center justify-center flex-shrink-0">
                                <i className="bi bi-palette text-2xl text-[#2F6BFF]"></i>
                            </div>
                            <div className="flex-1">
                                <h3 className="text-lg font-semibold text-[var(--admin-text)] mb-1">Appearance</h3>
                                <p className="text-sm text-[var(--admin-text-muted)]">
                                    Switch your admin theme between Light and Dark.
                                </p>
                            </div>
                        </div>
                        
                        {/* Theme Toggle */}
                        <div className="flex items-center gap-2 bg-[var(--admin-surface-2)] p-1.5 rounded-lg border border-[var(--admin-border)] flex-shrink-0">
                            <button
                                onClick={setLightMode}
                                className={`px-4 py-2 rounded-md font-medium text-sm transition-all duration-150 flex items-center gap-2 ${
                                    theme === 'light'
                                        ? 'bg-white text-gray-900 shadow-md'
                                        : 'text-[var(--admin-text-muted)] hover:text-[var(--admin-text)]'
                                }`}
                            >
                                <i className="bi bi-sun"></i>
                                <span className="hidden sm:inline">Light</span>
                            </button>
                            <button
                                onClick={setDarkMode}
                                className={`px-4 py-2 rounded-md font-medium text-sm transition-all duration-150 flex items-center gap-2 ${
                                    theme === 'dark'
                                        ? 'bg-gradient-to-r from-[#2F6BFF] to-[#2457D6] text-white shadow-lg shadow-[#2F6BFF]/20'
                                        : 'text-[var(--admin-text-muted)] hover:text-[var(--admin-text)]'
                                }`}
                            >
                                <i className="bi bi-moon-stars"></i>
                                <span className="hidden sm:inline">Dark</span>
                            </button>
                        </div>
                    </div>
                </Card>

                {/* API Settings Main Card */}
                <Card variant="elevated">
                    {/* Header */}
                    <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-6 border-b border-[var(--admin-border)]">
                        <div>
                            <h2 className="text-xl font-bold text-[var(--admin-text)] mb-1">API Settings</h2>
                            <p className="text-sm text-[var(--admin-text-muted)]">Manage integrations & API keys</p>
                        </div>
                    </div>

                    {/* Premium Pill Tabs */}
                    <div className="py-6 border-b border-[var(--admin-border)]">
                        <div className="flex gap-2 overflow-x-auto pb-2 scrollbar-hide">
                            {tabs.map((tab) => (
                                <button
                                    key={tab.id}
                                    onClick={() => setActiveTab(tab.id)}
                                    className={`px-4 py-2.5 rounded-lg font-medium text-sm whitespace-nowrap transition-all duration-150 flex items-center gap-2 ${
                                        activeTab === tab.id
                                            ? 'bg-gradient-to-r from-[#2F6BFF] to-[#2457D6] text-white shadow-md shadow-[#2F6BFF]/20'
                                            : 'bg-[var(--admin-surface-2)] text-[var(--admin-text-muted)] hover:text-[var(--admin-text)] hover:bg-[var(--admin-hover-bg)]'
                                    }`}
                                >
                                    <i className={`bi ${tab.icon}`}></i>
                                    {tab.label}
                                </button>
                            ))}
                        </div>
                    </div>

                    {/* Tab Content */}
                    <div className="py-6">
                        {/* Captcha Settings */}
                        {activeTab === 'captcha' && (
                            <div className="space-y-5">
                                {/* 2Captcha Section */}
                                <FormSection title="2Captcha Settings" icon="bi-puzzle">
                                    <div className="space-y-4">
                                        <div>
                                            <label className="block text-sm font-semibold text-[var(--admin-text)] mb-2">
                                                API Key
                                            </label>
                                            <InputWithIcon
                                                icon="bi-key"
                                                showToggle={true}
                                                keyName="2captcha_api_key"
                                                type={showKeys['2captcha_api_key'] ? 'text' : 'password'}
                                                value={captchaForm['2captcha_api_key'] || ''}
                                                onChange={(e) => setCaptchaForm({ ...captchaForm, '2captcha_api_key': e.target.value })}
                                                placeholder="Enter your 2Captcha API key"
                                            />
                                            <p className="mt-2 text-xs text-[var(--admin-text-dim)]">
                                                Get your API key from <a href="https://2captcha.com" target="_blank" rel="noopener noreferrer" className="text-[#5B8AFF] hover:text-[#2F6BFF] transition-colors duration-150">2captcha.com</a>
                                            </p>
                                        </div>
                                        <div className="flex items-center justify-between">
                                            <div className="flex items-center gap-2">
                                                <input
                                                    type="checkbox"
                                                    id="2captcha_enabled"
                                                    checked={captchaForm['2captcha_enabled'] || false}
                                                    onChange={(e) => setCaptchaForm({ ...captchaForm, '2captcha_enabled': e.target.checked })}
                                                    className="h-4 w-4 rounded border-[var(--admin-border)] bg-[var(--admin-bg)] text-[#2F6BFF] focus:ring-[#2F6BFF] focus:ring-offset-0 cursor-pointer transition-all duration-150"
                                                />
                                                <label htmlFor="2captcha_enabled" className="text-sm text-[var(--admin-text)] cursor-pointer font-medium">
                                                    Enable 2Captcha
                                                </label>
                                            </div>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => handleTestConnection('2captcha')}
                                                disabled={testing === '2captcha'}
                                            >
                                                {testing === '2captcha' ? (
                                                    <>
                                                        <i className="bi bi-arrow-repeat animate-spin mr-2"></i>
                                                        Testing...
                                                    </>
                                                ) : (
                                                    <>
                                                        <i className="bi bi-plug mr-2"></i>
                                                        Test Connection
                                                    </>
                                                )}
                                            </Button>
                                        </div>
                                        {testResult && testResult.service === '2captcha' && (
                                            <div className={`flex items-center gap-2 p-3 rounded-lg border animate-in fade-in duration-150 ${
                                                testResult.success 
                                                    ? 'bg-[#12B76A]/10 border-[#12B76A]/30' 
                                                    : 'bg-[#F04438]/10 border-[#F04438]/30'
                                            }`}>
                                                <i className={`bi ${testResult.success ? 'bi-check-circle' : 'bi-x-circle'} ${testResult.success ? 'text-[#12B76A]' : 'text-[#F04438]'}`}></i>
                                                <span className={`text-sm font-medium ${testResult.success ? 'text-[#12B76A]' : 'text-[#F04438]'}`}>
                                                    {testResult.message}
                                                </span>
                                            </div>
                                        )}
                                    </div>
                                </FormSection>

                                {/* AntiCaptcha Section */}
                                <FormSection title="AntiCaptcha Settings" icon="bi-shield-check">
                                    <div className="space-y-4">
                                        <div>
                                            <label className="block text-sm font-semibold text-[var(--admin-text)] mb-2">
                                                API Key
                                            </label>
                                            <InputWithIcon
                                                icon="bi-key"
                                                showToggle={true}
                                                keyName="anticaptcha_api_key"
                                                type={showKeys['anticaptcha_api_key'] ? 'text' : 'password'}
                                                value={captchaForm['anticaptcha_api_key'] || ''}
                                                onChange={(e) => setCaptchaForm({ ...captchaForm, 'anticaptcha_api_key': e.target.value })}
                                                placeholder="Enter your AntiCaptcha API key"
                                            />
                                            <p className="mt-2 text-xs text-[var(--admin-text-dim)]">
                                                Get your API key from <a href="https://anti-captcha.com" target="_blank" rel="noopener noreferrer" className="text-[#5B8AFF] hover:text-[#2F6BFF] transition-colors duration-150">anti-captcha.com</a>
                                            </p>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <input
                                                type="checkbox"
                                                id="anticaptcha_enabled"
                                                checked={captchaForm['anticaptcha_enabled'] || false}
                                                onChange={(e) => setCaptchaForm({ ...captchaForm, 'anticaptcha_enabled': e.target.checked })}
                                                className="h-4 w-4 rounded border-[var(--admin-border)] bg-[var(--admin-bg)] text-[#2F6BFF] focus:ring-[#2F6BFF] focus:ring-offset-0 cursor-pointer transition-all duration-150"
                                            />
                                            <label htmlFor="anticaptcha_enabled" className="text-sm text-[var(--admin-text)] cursor-pointer font-medium">
                                                Enable AntiCaptcha
                                            </label>
                                        </div>
                                    </div>
                                </FormSection>

                                {/* Save Button */}
                                <div className="flex justify-end pt-2">
                                    <Button variant="primary" onClick={() => handleSave('captcha')}>
                                        <i className="bi bi-check-circle mr-2"></i>
                                        Save Captcha Settings
                                    </Button>
                                </div>
                            </div>
                        )}

                        {/* Stripe Settings */}
                        {activeTab === 'stripe' && (
                            <div className="space-y-5">
                                <FormSection title="Stripe Configuration" icon="bi-credit-card">
                                    <div className="space-y-4">
                                        <div>
                                            <label className="block text-sm font-semibold text-[var(--admin-text)] mb-2">
                                                Publishable Key
                                            </label>
                                            <InputWithIcon
                                                icon="bi-key"
                                                type="text"
                                                value={stripeForm['stripe_key'] || ''}
                                                onChange={(e) => setStripeForm({ ...stripeForm, 'stripe_key': e.target.value })}
                                                placeholder="pk_test_..."
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-semibold text-[var(--admin-text)] mb-2">
                                                Secret Key
                                            </label>
                                            <InputWithIcon
                                                icon="bi-key"
                                                showToggle={true}
                                                keyName="stripe_secret"
                                                type={showKeys['stripe_secret'] ? 'text' : 'password'}
                                                value={stripeForm['stripe_secret'] || ''}
                                                onChange={(e) => setStripeForm({ ...stripeForm, 'stripe_secret': e.target.value })}
                                                placeholder="sk_test_..."
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-semibold text-[var(--admin-text)] mb-2">
                                                Webhook Secret
                                            </label>
                                            <InputWithIcon
                                                icon="bi-key"
                                                showToggle={true}
                                                keyName="stripe_webhook_secret"
                                                type={showKeys['stripe_webhook_secret'] ? 'text' : 'password'}
                                                value={stripeForm['stripe_webhook_secret'] || ''}
                                                onChange={(e) => setStripeForm({ ...stripeForm, 'stripe_webhook_secret': e.target.value })}
                                                placeholder="whsec_..."
                                            />
                                            <p className="mt-2 text-xs text-[var(--admin-text-dim)]">Webhook endpoint: {window.location.origin}/stripe/webhook</p>
                                        </div>
                                        <div className="flex items-center justify-between">
                                            <div className="flex items-center gap-2">
                                                <input
                                                    type="checkbox"
                                                    id="stripe_enabled"
                                                    checked={stripeForm['stripe_enabled'] || false}
                                                    onChange={(e) => setStripeForm({ ...stripeForm, 'stripe_enabled': e.target.checked })}
                                                    className="h-4 w-4 rounded border-[var(--admin-border)] bg-[var(--admin-bg)] text-[#2F6BFF] focus:ring-[#2F6BFF] focus:ring-offset-0 cursor-pointer transition-all duration-150"
                                                />
                                                <label htmlFor="stripe_enabled" className="text-sm text-[var(--admin-text)] cursor-pointer font-medium">
                                                    Enable Stripe Payments
                                                </label>
                                            </div>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => handleTestConnection('stripe')}
                                                disabled={testing === 'stripe'}
                                            >
                                                {testing === 'stripe' ? (
                                                    <>
                                                        <i className="bi bi-arrow-repeat animate-spin mr-2"></i>
                                                        Testing...
                                                    </>
                                                ) : (
                                                    <>
                                                        <i className="bi bi-plug mr-2"></i>
                                                        Test Connection
                                                    </>
                                                )}
                                            </Button>
                                        </div>
                                        {testResult && testResult.service === 'stripe' && (
                                            <div className={`flex items-center gap-2 p-3 rounded-lg border animate-in fade-in duration-150 ${
                                                testResult.success 
                                                    ? 'bg-[#12B76A]/10 border-[#12B76A]/30' 
                                                    : 'bg-[#F04438]/10 border-[#F04438]/30'
                                            }`}>
                                                <i className={`bi ${testResult.success ? 'bi-check-circle' : 'bi-x-circle'} ${testResult.success ? 'text-[#12B76A]' : 'text-[#F04438]'}`}></i>
                                                <span className={`text-sm font-medium ${testResult.success ? 'text-[#12B76A]' : 'text-[#F04438]'}`}>
                                                    {testResult.message}
                                                </span>
                                            </div>
                                        )}
                                    </div>
                                </FormSection>

                                <div className="flex justify-end pt-2">
                                    <Button variant="primary" onClick={() => handleSave('stripe')}>
                                        <i className="bi bi-check-circle mr-2"></i>
                                        Save Stripe Settings
                                    </Button>
                                </div>
                            </div>
                        )}

                        {/* Google OAuth Settings */}
                        {activeTab === 'google' && (
                            <div className="space-y-5">
                                <FormSection title="Google OAuth Configuration" icon="bi-google">
                                    <div className="space-y-4">
                                        <div>
                                            <label className="block text-sm font-semibold text-[var(--admin-text)] mb-2">
                                                Client ID
                                            </label>
                                            <InputWithIcon
                                                icon="bi-person-badge"
                                                type="text"
                                                value={googleForm['google_client_id'] || ''}
                                                onChange={(e) => setGoogleForm({ ...googleForm, 'google_client_id': e.target.value })}
                                                placeholder="xxxxx.apps.googleusercontent.com"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-semibold text-[var(--admin-text)] mb-2">
                                                Client Secret
                                            </label>
                                            <InputWithIcon
                                                icon="bi-key"
                                                showToggle={true}
                                                keyName="google_client_secret"
                                                type={showKeys['google_client_secret'] ? 'text' : 'password'}
                                                value={googleForm['google_client_secret'] || ''}
                                                onChange={(e) => setGoogleForm({ ...googleForm, 'google_client_secret': e.target.value })}
                                                placeholder="Enter your Google Client Secret"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-semibold text-[var(--admin-text)] mb-2">
                                                Redirect URI
                                            </label>
                                            <InputWithIcon
                                                icon="bi-link-45deg"
                                                type="text"
                                                value={googleForm['google_redirect_uri'] || ''}
                                                onChange={(e) => setGoogleForm({ ...googleForm, 'google_redirect_uri': e.target.value })}
                                                placeholder={window.location.origin + '/gmail/oauth/callback'}
                                            />
                                            <p className="mt-2 text-xs text-[var(--admin-text-dim)]">Add this URI to your Google Cloud Console OAuth credentials</p>
                                        </div>
                                        <div className="flex items-center justify-between">
                                            <div className="flex items-center gap-2">
                                                <input
                                                    type="checkbox"
                                                    id="google_enabled"
                                                    checked={googleForm['google_enabled'] || false}
                                                    onChange={(e) => setGoogleForm({ ...googleForm, 'google_enabled': e.target.checked })}
                                                    className="h-4 w-4 rounded border-[var(--admin-border)] bg-[var(--admin-bg)] text-[#2F6BFF] focus:ring-[#2F6BFF] focus:ring-offset-0 cursor-pointer transition-all duration-150"
                                                />
                                                <label htmlFor="google_enabled" className="text-sm text-[var(--admin-text)] cursor-pointer font-medium">
                                                    Enable Google OAuth
                                                </label>
                                            </div>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => handleTestConnection('google')}
                                                disabled={testing === 'google'}
                                            >
                                                {testing === 'google' ? (
                                                    <>
                                                        <i className="bi bi-arrow-repeat animate-spin mr-2"></i>
                                                        Testing...
                                                    </>
                                                ) : (
                                                    <>
                                                        <i className="bi bi-plug mr-2"></i>
                                                        Test Connection
                                                    </>
                                                )}
                                            </Button>
                                        </div>
                                        {testResult && testResult.service === 'google' && (
                                            <div className={`flex items-center gap-2 p-3 rounded-lg border animate-in fade-in duration-150 ${
                                                testResult.success 
                                                    ? 'bg-[#12B76A]/10 border-[#12B76A]/30' 
                                                    : 'bg-[#F04438]/10 border-[#F04438]/30'
                                            }`}>
                                                <i className={`bi ${testResult.success ? 'bi-check-circle' : 'bi-x-circle'} ${testResult.success ? 'text-[#12B76A]' : 'text-[#F04438]'}`}></i>
                                                <span className={`text-sm font-medium ${testResult.success ? 'text-[#12B76A]' : 'text-[#F04438]'}`}>
                                                    {testResult.message}
                                                </span>
                                            </div>
                                        )}
                                    </div>
                                </FormSection>

                                <div className="flex justify-end pt-2">
                                    <Button variant="primary" onClick={() => handleSave('google')}>
                                        <i className="bi bi-check-circle mr-2"></i>
                                        Save Google Settings
                                    </Button>
                                </div>
                            </div>
                        )}

                        {/* LLM/AI Settings */}
                        {activeTab === 'llm' && (
                            <div className="space-y-5">
                                <FormSection title="LLM Content Generation" icon="bi-stars">
                                    <div className="space-y-4">
                                        {/* Enable Toggle */}
                                        <div className="bg-[var(--admin-surface)] p-4 rounded-lg border border-[var(--admin-border)]">
                                            <div className="flex items-center justify-between gap-4">
                                                <div className="flex-1">
                                                    <label htmlFor="llm_enabled" className="text-sm font-semibold text-[var(--admin-text)] cursor-pointer">
                                                        Enable LLM Content Generation
                                                    </label>
                                                    <p className="text-xs text-[var(--admin-text-dim)] mt-1">
                                                        Use AI to generate comments, forum posts, and other content
                                                    </p>
                                                </div>
                                                <label className="relative inline-flex items-center cursor-pointer flex-shrink-0">
                                                    <input
                                                        type="checkbox"
                                                        id="llm_enabled"
                                                        checked={llmForm['llm_enabled'] || false}
                                                        onChange={(e) => setLlmForm({ ...llmForm, 'llm_enabled': e.target.checked })}
                                                        className="sr-only peer"
                                                    />
                                                    <div className="w-11 h-6 bg-[var(--admin-bg)] peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-[#2F6BFF]/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-[var(--admin-text)] after:border-[var(--admin-border)] after:border after:rounded-full after:h-5 after:w-5 after:transition-all duration-150 peer-checked:bg-gradient-to-r peer-checked:from-[#2F6BFF] peer-checked:to-[#2457D6]"></div>
                                                </label>
                                            </div>
                                        </div>

                                        <div>
                                            <label className="block text-sm font-semibold text-[var(--admin-text)] mb-2">
                                                LLM Provider
                                            </label>
                                            <select
                                                value={llmForm['llm_provider'] || 'deepseek'}
                                                onChange={(e) => setLlmForm({ ...llmForm, 'llm_provider': e.target.value })}
                                                className="w-full px-4 py-2.5 bg-[var(--admin-surface)] border border-[var(--admin-border)] text-[var(--admin-text)] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#2F6BFF] focus:border-[#2F6BFF] disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-150"
                                                disabled={!llmForm['llm_enabled']}
                                            >
                                                <option value="deepseek">DeepSeek</option>
                                                <option value="openai">OpenAI</option>
                                                <option value="anthropic">Anthropic (Claude)</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label className={`block text-sm font-semibold mb-2 transition-colors duration-150 ${!llmForm['llm_enabled'] ? 'text-[var(--admin-text-dim)]' : 'text-[var(--admin-text)]'}`}>
                                                DeepSeek API Key
                                            </label>
                                            <InputWithIcon
                                                icon="bi-key"
                                                showToggle={true}
                                                keyName="deepseek_api_key"
                                                type={showKeys['deepseek_api_key'] ? 'text' : 'password'}
                                                value={llmForm['deepseek_api_key'] || ''}
                                                onChange={(e) => setLlmForm({ ...llmForm, 'deepseek_api_key': e.target.value })}
                                                placeholder="Enter your DeepSeek API key"
                                                disabled={!llmForm['llm_enabled']}
                                            />
                                        </div>
                                        <div>
                                            <label className={`block text-sm font-semibold mb-2 transition-colors duration-150 ${!llmForm['llm_enabled'] ? 'text-[var(--admin-text-dim)]' : 'text-[var(--admin-text)]'}`}>
                                                OpenAI API Key
                                            </label>
                                            <InputWithIcon
                                                icon="bi-key"
                                                showToggle={true}
                                                keyName="openai_api_key"
                                                type={showKeys['openai_api_key'] ? 'text' : 'password'}
                                                value={llmForm['openai_api_key'] || ''}
                                                onChange={(e) => setLlmForm({ ...llmForm, 'openai_api_key': e.target.value })}
                                                placeholder="sk-..."
                                                disabled={!llmForm['llm_enabled']}
                                            />
                                        </div>
                                        <div>
                                            <label className={`block text-sm font-semibold mb-2 transition-colors duration-150 ${!llmForm['llm_enabled'] ? 'text-[var(--admin-text-dim)]' : 'text-[var(--admin-text)]'}`}>
                                                Anthropic API Key
                                            </label>
                                            <InputWithIcon
                                                icon="bi-key"
                                                showToggle={true}
                                                keyName="anthropic_api_key"
                                                type={showKeys['anthropic_api_key'] ? 'text' : 'password'}
                                                value={llmForm['anthropic_api_key'] || ''}
                                                onChange={(e) => setLlmForm({ ...llmForm, 'anthropic_api_key': e.target.value })}
                                                placeholder="sk-ant-..."
                                                disabled={!llmForm['llm_enabled']}
                                            />
                                        </div>
                                        <div>
                                            <label className={`block text-sm font-semibold mb-2 transition-colors duration-150 ${!llmForm['llm_enabled'] ? 'text-[var(--admin-text-dim)]' : 'text-[var(--admin-text)]'}`}>
                                                Model Name
                                            </label>
                                            <InputWithIcon
                                                icon="bi-cpu"
                                                type="text"
                                                value={llmForm['llm_model'] || 'deepseek-chat'}
                                                onChange={(e) => setLlmForm({ ...llmForm, 'llm_model': e.target.value })}
                                                placeholder="deepseek-chat, gpt-4, claude-3-opus, etc."
                                                disabled={!llmForm['llm_enabled']}
                                            />
                                        </div>
                                    </div>
                                </FormSection>

                                <div className="flex justify-end pt-2">
                                    <Button variant="primary" onClick={() => handleSave('llm')}>
                                        <i className="bi bi-check-circle mr-2"></i>
                                        Save LLM Settings
                                    </Button>
                                </div>
                            </div>
                        )}

                        {/* API Settings */}
                        {activeTab === 'api' && (
                            <div className="space-y-5">
                                <FormSection title="API Configuration" icon="bi-plug">
                                    <div className="space-y-4">
                                        <div>
                                            <label className="block text-sm font-semibold text-[var(--admin-text)] mb-2">
                                                Python Worker API Token
                                            </label>
                                            <InputWithIcon
                                                icon="bi-key"
                                                showToggle={true}
                                                keyName="python_api_token"
                                                type={showKeys['python_api_token'] ? 'text' : 'password'}
                                                value={apiForm['python_api_token'] || ''}
                                                onChange={(e) => setApiForm({ ...apiForm, 'python_api_token': e.target.value })}
                                                placeholder="Enter secure API token for Python workers"
                                            />
                                            <p className="mt-2 text-xs text-[var(--admin-text-dim)]">This token is used to authenticate Python workers with Laravel API</p>
                                        </div>
                                        <div>
                                            <label className="block text-sm font-semibold text-[var(--admin-text)] mb-2">
                                                API Rate Limit (requests per hour)
                                            </label>
                                            <InputWithIcon
                                                icon="bi-speedometer"
                                                type="number"
                                                min="1"
                                                value={apiForm['api_rate_limit'] || 300}
                                                onChange={(e) => setApiForm({ ...apiForm, 'api_rate_limit': e.target.value })}
                                            />
                                            <p className="mt-2 text-xs text-[var(--admin-text-dim)]">
                                                Maximum number of API requests allowed per hour per worker
                                            </p>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <input
                                                type="checkbox"
                                                id="api_enabled"
                                                checked={apiForm['api_enabled'] || false}
                                                onChange={(e) => setApiForm({ ...apiForm, 'api_enabled': e.target.checked })}
                                                className="h-4 w-4 rounded border-[var(--admin-border)] bg-[var(--admin-bg)] text-[#2F6BFF] focus:ring-[#2F6BFF] focus:ring-offset-0 cursor-pointer transition-all duration-150"
                                            />
                                            <label htmlFor="api_enabled" className="text-sm text-[var(--admin-text)] cursor-pointer font-medium">
                                                Enable API Access
                                            </label>
                                        </div>
                                    </div>
                                </FormSection>

                                <div className="flex justify-end pt-2">
                                    <Button variant="primary" onClick={() => handleSave('api')}>
                                        <i className="bi bi-check-circle mr-2"></i>
                                        Save API Settings
                                    </Button>
                                </div>
                            </div>
                        )}
                    </div>
                </Card>
            </div>
        </AdminLayout>
    );
}
