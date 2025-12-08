<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index()
    {
        // Get all settings grouped by service
        $settings = Setting::orderBy('group')->orderBy('key')->get()->groupBy('group');

        // Get current values for each service
        $captchaSettings = [
            '2captcha_api_key' => Setting::get('captcha_2captcha_api_key', ''),
            '2captcha_enabled' => Setting::get('captcha_2captcha_enabled', false),
            'anticaptcha_api_key' => Setting::get('captcha_anticaptcha_api_key', ''),
            'anticaptcha_enabled' => Setting::get('captcha_anticaptcha_enabled', false),
        ];

        $stripeSettings = [
            'stripe_key' => Setting::get('stripe_key', config('services.stripe.key', '')),
            'stripe_secret' => Setting::get('stripe_secret', config('services.stripe.secret', '')),
            'stripe_webhook_secret' => Setting::get('stripe_webhook_secret', config('services.stripe.webhook_secret', '')),
            'stripe_enabled' => Setting::get('stripe_enabled', true),
        ];

        $googleSettings = [
            'google_client_id' => Setting::get('google_client_id', config('services.google.client_id', '')),
            'google_client_secret' => Setting::get('google_client_secret', config('services.google.client_secret', '')),
            'google_redirect_uri' => Setting::get('google_redirect_uri', config('services.google.redirect_uri', '')),
            'google_enabled' => Setting::get('google_enabled', true),
        ];

        $llmSettings = [
            'llm_provider' => Setting::get('llm_provider', 'deepseek'), // deepseek, openai, anthropic
            'deepseek_api_key' => Setting::get('llm_deepseek_api_key', ''),
            'openai_api_key' => Setting::get('llm_openai_api_key', ''),
            'anthropic_api_key' => Setting::get('llm_anthropic_api_key', ''),
            'llm_model' => Setting::get('llm_model', 'deepseek-chat'),
            'llm_enabled' => Setting::get('llm_enabled', true),
        ];

        $apiSettings = [
            'python_api_token' => Setting::get('api_python_api_token', config('app.api_token', '')),
            'api_rate_limit' => Setting::get('api_api_rate_limit', 300),
            'api_enabled' => Setting::get('api_api_enabled', true),
        ];

        return Inertia::render('Admin/Settings/Index', [
            'captchaSettings' => $captchaSettings,
            'stripeSettings' => $stripeSettings,
            'googleSettings' => $googleSettings,
            'llmSettings' => $llmSettings,
            'apiSettings' => $apiSettings,
        ]);
    }

    public function update(Request $request, $group)
    {
        $data = $request->except(['group', '_method', '_token']);

        foreach ($data as $key => $value) {
            $fullKey = $group . '_' . $key;

            // Determine if this should be encrypted (passwords, secrets, keys)
            $encrypt = in_array($key, [
                'api_key', 'api_secret', 'client_secret', 'secret',
                'webhook_secret', 'password', 'token', '2captcha_api_key',
                'anticaptcha_api_key', 'stripe_secret', 'stripe_webhook_secret',
                'google_client_secret', 'deepseek_api_key', 'openai_api_key',
                'anthropic_api_key', 'python_api_token'
            ]);

            Setting::set($fullKey, $value, $group, $this->getType($key), $encrypt);
        }

        // Clear config cache if needed
        if (app()->environment('production')) {
            \Artisan::call('config:clear');
        }

        return back()->with('success', ucfirst($group) . ' settings updated successfully.');
    }

    protected function getType(string $key): string
    {
        if (str_contains($key, 'enabled') || str_contains($key, 'active')) {
            return 'boolean';
        }
        // Detect numeric settings (rate limits, counts, etc.)
        if (str_contains($key, 'rate_limit') || str_contains($key, 'limit') ||
            str_contains($key, 'count') || str_contains($key, 'max_') ||
            str_contains($key, 'min_') || str_contains($key, 'timeout') ||
            str_contains($key, 'interval') || str_contains($key, 'port')) {
            return 'number';
        }
        if (is_numeric($key)) {
            return 'number';
        }
        return 'string';
    }

    public function testConnection(Request $request)
    {
        $service = $request->input('service');

        try {
            switch ($service) {
                case 'stripe':
                    $key = Setting::get('stripe_key');
                    $secret = Setting::get('stripe_secret');
                    if (!$key || !$secret) {
                        return response()->json(['success' => false, 'message' => 'Stripe keys not configured', 'service' => 'stripe']);
                    }
                    // Test Stripe connection
                    \Stripe\Stripe::setApiKey($secret);
                    \Stripe\Account::retrieve();
                    return response()->json(['success' => true, 'message' => 'Stripe connection successful', 'service' => 'stripe']);

                case 'google':
                    $clientId = Setting::get('google_client_id');
                    $clientSecret = Setting::get('google_client_secret');
                    if (!$clientId || !$clientSecret) {
                        return response()->json(['success' => false, 'message' => 'Google OAuth credentials not configured', 'service' => 'google']);
                    }
                    return response()->json(['success' => true, 'message' => 'Google OAuth credentials configured', 'service' => 'google']);

                case '2captcha':
                    $apiKey = Setting::get('captcha_2captcha_api_key');
                    if (!$apiKey) {
                        return response()->json(['success' => false, 'message' => '2Captcha API key not configured', 'service' => '2captcha']);
                    }
                    // Test 2Captcha connection
                    try {
                        $response = @file_get_contents("http://2captcha.com/in.php?key={$apiKey}&method=user&action=getbalance");
                        if ($response && strpos($response, 'OK|') === 0) {
                            $balance = explode('|', $response)[1];
                            return response()->json(['success' => true, 'message' => "2Captcha connected. Balance: \${$balance}", 'service' => '2captcha']);
                        }
                        return response()->json(['success' => false, 'message' => '2Captcha connection failed', 'service' => '2captcha']);
                    } catch (\Exception $e) {
                        return response()->json(['success' => false, 'message' => '2Captcha connection error: ' . $e->getMessage(), 'service' => '2captcha']);
                    }

                default:
                    return response()->json(['success' => false, 'message' => 'Unknown service']);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}

