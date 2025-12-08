import { useState } from 'react';
import AdminLayout from '../../../Components/Layout/AdminLayout';
import Card from '../../../Components/Shared/Card';
import Button from '../../../Components/Shared/Button';
import Input from '../../../Components/Shared/Input';
import { router, usePage } from '@inertiajs/react';

export default function AdminSettingsIndex({
    captchaSettings,
    stripeSettings,
    googleSettings,
    llmSettings,
    apiSettings
}) {
    const { flash } = usePage().props;
    const [activeTab, setActiveTab] = useState('captcha');
    const [testing, setTesting] = useState(null);
    const [testResult, setTestResult] = useState(null);

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

    const tabs = [
        { id: 'captcha', label: '🧩 Captcha', icon: '🧩' },
        { id: 'stripe', label: '💳 Stripe', icon: '💳' },
        { id: 'google', label: '🔐 Google OAuth', icon: '🔐' },
        { id: 'llm', label: '🤖 LLM/AI', icon: '🤖' },
        { id: 'api', label: '🔌 API', icon: '🔌' },
    ];

    return (
        <AdminLayout header="API Settings">
            <div className="space-y-6">
                {/* Success Message */}
                {flash?.success && (
                    <div className="p-4 bg-green-50 border border-green-200 rounded-md">
                        <p className="text-sm text-green-800">{flash.success}</p>
                    </div>
                )}

                {/* Tabs */}
                <Card className="bg-white border border-gray-200 shadow-md">
                    <div className="border-b border-gray-200">
                        <nav className="flex -mb-px space-x-4 overflow-x-auto">
                            {tabs.map((tab) => (
                                <button
                                    key={tab.id}
                                    onClick={() => setActiveTab(tab.id)}
                                    className={`px-4 py-3 text-sm font-medium whitespace-nowrap border-b-2 transition-colors ${
                                        activeTab === tab.id
                                            ? 'border-blue-500 text-blue-600'
                                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                    }`}
                                >
                                    <span className="mr-2">{tab.icon}</span>
                                    {tab.label}
                                </button>
                            ))}
                        </nav>
                    </div>

                    {/* Tab Content */}
                    <div className="p-6">
                        {/* Captcha Settings */}
                        {activeTab === 'captcha' && (
                            <div className="space-y-6">
                                <div>
                                    <h3 className="text-lg font-bold text-gray-900 mb-4">2Captcha Settings</h3>
                                    <div className="space-y-4">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                2Captcha API Key
                                            </label>
                                            <Input
                                                type="password"
                                                value={captchaForm['2captcha_api_key'] || ''}
                                                onChange={(e) => setCaptchaForm({ ...captchaForm, '2captcha_api_key': e.target.value })}
                                                placeholder="Enter your 2Captcha API key"
                                            />
                                            <p className="mt-1 text-xs text-gray-500">Get your API key from <a href="https://2captcha.com" target="_blank" rel="noopener noreferrer" className="text-blue-600 hover:underline">2captcha.com</a></p>
                                        </div>
                                        <div className="flex items-center">
                                            <input
                                                type="checkbox"
                                                id="2captcha_enabled"
                                                checked={captchaForm['2captcha_enabled'] || false}
                                                onChange={(e) => setCaptchaForm({ ...captchaForm, '2captcha_enabled': e.target.checked })}
                                                className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                            />
                                            <label htmlFor="2captcha_enabled" className="ml-2 text-sm text-gray-700">
                                                Enable 2Captcha
                                            </label>
                                        </div>
                                        <div className="flex gap-2">
                                            <Button
                                                variant="secondary"
                                                onClick={() => handleTestConnection('2captcha')}
                                                disabled={testing === '2captcha'}
                                            >
                                                {testing === '2captcha' ? 'Testing...' : '🧪 Test Connection'}
                                            </Button>
                                            {testResult && testResult.service === '2captcha' && (
                                                <span className={`text-sm font-medium ${testResult.success ? 'text-green-600' : 'text-red-600'}`}>
                                                    {testResult.message}
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                </div>

                                <div className="border-t border-gray-200 pt-6">
                                    <h3 className="text-lg font-bold text-gray-900 mb-4">AntiCaptcha Settings</h3>
                                    <div className="space-y-4">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                AntiCaptcha API Key
                                            </label>
                                            <Input
                                                type="password"
                                                value={captchaForm['anticaptcha_api_key'] || ''}
                                                onChange={(e) => setCaptchaForm({ ...captchaForm, 'anticaptcha_api_key': e.target.value })}
                                                placeholder="Enter your AntiCaptcha API key"
                                            />
                                            <p className="mt-1 text-xs text-gray-500">Get your API key from <a href="https://anti-captcha.com" target="_blank" rel="noopener noreferrer" className="text-blue-600 hover:underline">anti-captcha.com</a></p>
                                        </div>
                                        <div className="flex items-center">
                                            <input
                                                type="checkbox"
                                                id="anticaptcha_enabled"
                                                checked={captchaForm['anticaptcha_enabled'] || false}
                                                onChange={(e) => setCaptchaForm({ ...captchaForm, 'anticaptcha_enabled': e.target.checked })}
                                                className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                            />
                                            <label htmlFor="anticaptcha_enabled" className="ml-2 text-sm text-gray-700">
                                                Enable AntiCaptcha
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div className="flex justify-end pt-4 border-t border-gray-200">
                                    <Button variant="primary" onClick={() => handleSave('captcha')}>
                                        💾 Save Captcha Settings
                                    </Button>
                                </div>
                            </div>
                        )}

                        {/* Stripe Settings */}
                        {activeTab === 'stripe' && (
                            <div className="space-y-6">
                                <div className="space-y-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Stripe Publishable Key
                                        </label>
                                        <Input
                                            type="text"
                                            value={stripeForm['stripe_key'] || ''}
                                            onChange={(e) => setStripeForm({ ...stripeForm, 'stripe_key': e.target.value })}
                                            placeholder="pk_test_..."
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Stripe Secret Key
                                        </label>
                                        <Input
                                            type="password"
                                            value={stripeForm['stripe_secret'] || ''}
                                            onChange={(e) => setStripeForm({ ...stripeForm, 'stripe_secret': e.target.value })}
                                            placeholder="sk_test_..."
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Stripe Webhook Secret
                                        </label>
                                        <Input
                                            type="password"
                                            value={stripeForm['stripe_webhook_secret'] || ''}
                                            onChange={(e) => setStripeForm({ ...stripeForm, 'stripe_webhook_secret': e.target.value })}
                                            placeholder="whsec_..."
                                        />
                                        <p className="mt-1 text-xs text-gray-500">Webhook endpoint: {window.location.origin}/stripe/webhook</p>
                                    </div>
                                    <div className="flex items-center">
                                        <input
                                            type="checkbox"
                                            id="stripe_enabled"
                                            checked={stripeForm['stripe_enabled'] || false}
                                            onChange={(e) => setStripeForm({ ...stripeForm, 'stripe_enabled': e.target.checked })}
                                            className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                        />
                                        <label htmlFor="stripe_enabled" className="ml-2 text-sm text-gray-700">
                                            Enable Stripe Payments
                                        </label>
                                    </div>
                                    <div className="flex gap-2">
                                        <Button
                                            variant="secondary"
                                            onClick={() => handleTestConnection('stripe')}
                                            disabled={testing === 'stripe'}
                                        >
                                            {testing === 'stripe' ? 'Testing...' : '🧪 Test Connection'}
                                        </Button>
                                        {testResult && testResult.service === 'stripe' && (
                                            <span className={`text-sm font-medium ${testResult.success ? 'text-green-600' : 'text-red-600'}`}>
                                                {testResult.message}
                                            </span>
                                        )}
                                    </div>
                                </div>
                                <div className="flex justify-end pt-4 border-t border-gray-200">
                                    <Button variant="primary" onClick={() => handleSave('stripe')}>
                                        💾 Save Stripe Settings
                                    </Button>
                                </div>
                            </div>
                        )}

                        {/* Google OAuth Settings */}
                        {activeTab === 'google' && (
                            <div className="space-y-6">
                                <div className="space-y-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Google Client ID
                                        </label>
                                        <Input
                                            type="text"
                                            value={googleForm['google_client_id'] || ''}
                                            onChange={(e) => setGoogleForm({ ...googleForm, 'google_client_id': e.target.value })}
                                            placeholder="xxxxx.apps.googleusercontent.com"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Google Client Secret
                                        </label>
                                        <Input
                                            type="password"
                                            value={googleForm['google_client_secret'] || ''}
                                            onChange={(e) => setGoogleForm({ ...googleForm, 'google_client_secret': e.target.value })}
                                            placeholder="Enter your Google Client Secret"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Redirect URI
                                        </label>
                                        <Input
                                            type="text"
                                            value={googleForm['google_redirect_uri'] || ''}
                                            onChange={(e) => setGoogleForm({ ...googleForm, 'google_redirect_uri': e.target.value })}
                                            placeholder={window.location.origin + '/gmail/oauth/callback'}
                                        />
                                        <p className="mt-1 text-xs text-gray-500">Add this URI to your Google Cloud Console OAuth credentials</p>
                                    </div>
                                    <div className="flex items-center">
                                        <input
                                            type="checkbox"
                                            id="google_enabled"
                                            checked={googleForm['google_enabled'] || false}
                                            onChange={(e) => setGoogleForm({ ...googleForm, 'google_enabled': e.target.checked })}
                                            className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                        />
                                        <label htmlFor="google_enabled" className="ml-2 text-sm text-gray-700">
                                            Enable Google OAuth
                                        </label>
                                    </div>
                                    <div className="flex gap-2">
                                        <Button
                                            variant="secondary"
                                            onClick={() => handleTestConnection('google')}
                                            disabled={testing === 'google'}
                                        >
                                            {testing === 'google' ? 'Testing...' : '🧪 Test Connection'}
                                        </Button>
                                        {testResult && testResult.service === 'google' && (
                                            <span className={`text-sm font-medium ${testResult.success ? 'text-green-600' : 'text-red-600'}`}>
                                                {testResult.message}
                                            </span>
                                        )}
                                    </div>
                                </div>
                                <div className="flex justify-end pt-4 border-t border-gray-200">
                                    <Button variant="primary" onClick={() => handleSave('google')}>
                                        💾 Save Google Settings
                                    </Button>
                                </div>
                            </div>
                        )}

                        {/* LLM/AI Settings */}
                        {activeTab === 'llm' && (
                            <div className="space-y-6">
                                <div className="space-y-4">
                                    {/* Enable/Disable Toggle - Prominent at the top */}
                                    <div className="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                        <div className="flex items-center justify-between">
                                            <div>
                                                <label htmlFor="llm_enabled" className="text-sm font-semibold text-gray-900 cursor-pointer">
                                                    Enable LLM Content Generation
                                                </label>
                                                <p className="text-xs text-gray-500 mt-1">
                                                    When enabled, the system will use AI to generate comments, forum posts, and other content
                                                </p>
                                            </div>
                                            <label className="relative inline-flex items-center cursor-pointer">
                                                <input
                                                    type="checkbox"
                                                    id="llm_enabled"
                                                    checked={llmForm['llm_enabled'] || false}
                                                    onChange={(e) => setLlmForm({ ...llmForm, 'llm_enabled': e.target.checked })}
                                                    className="sr-only peer"
                                                />
                                                <div className="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                            </label>
                                        </div>
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            LLM Provider
                                        </label>
                                        <select
                                            value={llmForm['llm_provider'] || 'deepseek'}
                                            onChange={(e) => setLlmForm({ ...llmForm, 'llm_provider': e.target.value })}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500"
                                            disabled={!llmForm['llm_enabled']}
                                        >
                                            <option value="deepseek">DeepSeek</option>
                                            <option value="openai">OpenAI</option>
                                            <option value="anthropic">Anthropic (Claude)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label className={`block text-sm font-medium mb-1 ${!llmForm['llm_enabled'] ? 'text-gray-400' : 'text-gray-700'}`}>
                                            DeepSeek API Key
                                        </label>
                                        <Input
                                            type="password"
                                            value={llmForm['deepseek_api_key'] || ''}
                                            onChange={(e) => setLlmForm({ ...llmForm, 'deepseek_api_key': e.target.value })}
                                            placeholder="Enter your DeepSeek API key"
                                            disabled={!llmForm['llm_enabled']}
                                        />
                                    </div>
                                    <div>
                                        <label className={`block text-sm font-medium mb-1 ${!llmForm['llm_enabled'] ? 'text-gray-400' : 'text-gray-700'}`}>
                                            OpenAI API Key
                                        </label>
                                        <Input
                                            type="password"
                                            value={llmForm['openai_api_key'] || ''}
                                            onChange={(e) => setLlmForm({ ...llmForm, 'openai_api_key': e.target.value })}
                                            placeholder="sk-..."
                                            disabled={!llmForm['llm_enabled']}
                                        />
                                    </div>
                                    <div>
                                        <label className={`block text-sm font-medium mb-1 ${!llmForm['llm_enabled'] ? 'text-gray-400' : 'text-gray-700'}`}>
                                            Anthropic API Key
                                        </label>
                                        <Input
                                            type="password"
                                            value={llmForm['anthropic_api_key'] || ''}
                                            onChange={(e) => setLlmForm({ ...llmForm, 'anthropic_api_key': e.target.value })}
                                            placeholder="sk-ant-..."
                                            disabled={!llmForm['llm_enabled']}
                                        />
                                    </div>
                                    <div>
                                        <label className={`block text-sm font-medium mb-1 ${!llmForm['llm_enabled'] ? 'text-gray-400' : 'text-gray-700'}`}>
                                            Model Name
                                        </label>
                                        <Input
                                            type="text"
                                            value={llmForm['llm_model'] || 'deepseek-chat'}
                                            onChange={(e) => setLlmForm({ ...llmForm, 'llm_model': e.target.value })}
                                            placeholder="deepseek-chat, gpt-4, claude-3-opus, etc."
                                            disabled={!llmForm['llm_enabled']}
                                        />
                                    </div>
                                </div>
                                <div className="flex justify-end pt-4 border-t border-gray-200">
                                    <Button variant="primary" onClick={() => handleSave('llm')}>
                                        💾 Save LLM Settings
                                    </Button>
                                </div>
                            </div>
                        )}

                        {/* API Settings */}
                        {activeTab === 'api' && (
                            <div className="space-y-6">
                                <div className="space-y-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Python Worker API Token
                                        </label>
                                        <Input
                                            type="password"
                                            value={apiForm['python_api_token'] || ''}
                                            onChange={(e) => setApiForm({ ...apiForm, 'python_api_token': e.target.value })}
                                            placeholder="Enter secure API token for Python workers"
                                        />
                                        <p className="mt-1 text-xs text-gray-500">This token is used to authenticate Python workers with Laravel API</p>
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            API Rate Limit (requests per hour)
                                        </label>
                                        <Input
                                            type="number"
                                            min="1"
                                            value={apiForm['api_rate_limit'] || 300}
                                            onChange={(e) => setApiForm({ ...apiForm, 'api_rate_limit': e.target.value })}
                                        />
                                        <p className="mt-1 text-xs text-gray-500">
                                            Maximum number of API requests allowed per hour per worker
                                        </p>
                                    </div>
                                    <div className="flex items-center">
                                        <input
                                            type="checkbox"
                                            id="api_enabled"
                                            checked={apiForm['api_enabled'] || false}
                                            onChange={(e) => setApiForm({ ...apiForm, 'api_enabled': e.target.checked })}
                                            className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                        />
                                        <label htmlFor="api_enabled" className="ml-2 text-sm text-gray-700">
                                            Enable API Access
                                        </label>
                                    </div>
                                </div>
                                <div className="flex justify-end pt-4 border-t border-gray-200">
                                    <Button variant="primary" onClick={() => handleSave('api')}>
                                        💾 Save API Settings
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

