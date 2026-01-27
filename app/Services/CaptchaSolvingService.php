<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\CaptchaLog;
use App\Models\Campaign;

class CaptchaSolvingService
{
    protected $provider;
    protected $apiKey;
    protected $apiUrl;

    public function __construct()
    {
        $this->provider = config('services.captcha.provider', '2captcha');
        $this->apiKey = config("services.captcha.{$this->provider}.api_key");
        $this->apiUrl = config("services.captcha.{$this->provider}.api_url");
    }

    /**
     * Solve a captcha
     */
    public function solve(string $captchaType, array $options = [], ?int $campaignId = null): ?array
    {
        if (!$this->apiKey) {
            Log::warning('Captcha API key not configured');
            return null;
        }

        $taskId = $this->submitTask($captchaType, $options);
        if (!$taskId) {
            return null;
        }

        // Wait for solution (poll every 5 seconds, max 2 minutes)
        $solution = $this->getSolution($taskId, 24); // 24 attempts = 2 minutes

        if ($solution) {
            // Log cost
            $cost = $this->getCost($captchaType);
            CaptchaLog::create([
                'campaign_id' => $campaignId,
                'site_domain' => $options['site_domain'] ?? 'unknown',
                'captcha_type' => $captchaType,
                'service' => $this->provider, // Map provider to service field
                'order_id' => $taskId,
                'estimated_cost' => $cost,
                'status' => CaptchaLog::STATUS_SOLVED,
                'solved_at' => now(),
            ]);

            return [
                'success' => true,
                'solution' => $solution,
                'task_id' => $taskId,
                'cost' => $cost,
            ];
        }

        // Log failure
        CaptchaLog::create([
            'campaign_id' => $campaignId,
            'site_domain' => $options['site_domain'] ?? 'unknown',
            'captcha_type' => $captchaType,
            'service' => $this->provider, // Map provider to service field
            'order_id' => $taskId,
            'estimated_cost' => 0,
            'status' => CaptchaLog::STATUS_FAILED,
            'error' => 'Solution not found',
        ]);

        return null;
    }

    /**
     * Submit captcha task
     */
    protected function submitTask(string $captchaType, array $options): ?string
    {
        try {
            if ($this->provider === '2captcha') {
                return $this->submit2Captcha($captchaType, $options);
            } elseif ($this->provider === 'anticaptcha') {
                return $this->submitAntiCaptcha($captchaType, $options);
            }
        } catch (\Exception $e) {
            Log::error('Captcha task submission failed', [
                'error' => $e->getMessage(),
                'provider' => $this->provider,
            ]);
            return null;
        }

        return null;
    }

    /**
     * Submit task to 2Captcha
     */
    protected function submit2Captcha(string $captchaType, array $options): ?string
    {
        $baseUrl = 'https://2captcha.com/in.php';
        $params = ['key' => $this->apiKey];

        switch ($captchaType) {
            case 'recaptcha_v2':
                $params['method'] = 'userrecaptcha';
                $params['googlekey'] = $options['site_key'] ?? '';
                $params['pageurl'] = $options['page_url'] ?? '';
                break;

            case 'recaptcha_v3':
                $params['method'] = 'userrecaptcha';
                $params['version'] = 'v3';
                $params['googlekey'] = $options['site_key'] ?? '';
                $params['pageurl'] = $options['page_url'] ?? '';
                $params['action'] = $options['action'] ?? 'verify';
                break;

            case 'hcaptcha':
                $params['method'] = 'hcaptcha';
                $params['sitekey'] = $options['site_key'] ?? '';
                $params['pageurl'] = $options['page_url'] ?? '';
                break;

            case 'image':
                $params['method'] = 'post';
                $params['body'] = base64_encode($options['image'] ?? '');
                break;

            default:
                return null;
        }

        $response = Http::asForm()->post($baseUrl, $params);

        if ($response->successful()) {
            $body = $response->body();
            if (strpos($body, 'OK|') === 0) {
                return substr($body, 3); // Extract task ID
            }
        }

        return null;
    }

    /**
     * Submit task to AntiCaptcha
     */
    protected function submitAntiCaptcha(string $captchaType, array $options): ?string
    {
        $baseUrl = "{$this->apiUrl}/createTask";
        $task = [];

        switch ($captchaType) {
            case 'recaptcha_v2':
                $task = [
                    'type' => 'RecaptchaV2TaskProxyless',
                    'websiteURL' => $options['page_url'] ?? '',
                    'websiteKey' => $options['site_key'] ?? '',
                ];
                break;

            case 'recaptcha_v3':
                $task = [
                    'type' => 'RecaptchaV3TaskProxyless',
                    'websiteURL' => $options['page_url'] ?? '',
                    'websiteKey' => $options['site_key'] ?? '',
                    'minScore' => $options['min_score'] ?? 0.3,
                    'pageAction' => $options['action'] ?? 'verify',
                ];
                break;

            case 'hcaptcha':
                $task = [
                    'type' => 'HcaptchaTaskProxyless',
                    'websiteURL' => $options['page_url'] ?? '',
                    'websiteKey' => $options['site_key'] ?? '',
                ];
                break;

            case 'image':
                $task = [
                    'type' => 'ImageToTextTask',
                    'body' => base64_encode($options['image'] ?? ''),
                ];
                break;

            default:
                return null;
        }

        $response = Http::post($baseUrl, [
            'clientKey' => $this->apiKey,
            'task' => $task,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            if ($data['errorId'] === 0) {
                return $data['taskId'] ?? null;
            }
        }

        return null;
    }

    /**
     * Get solution for task
     */
    protected function getSolution(string $taskId, int $maxAttempts = 24): ?string
    {
        for ($i = 0; $i < $maxAttempts; $i++) {
            sleep(5); // Wait 5 seconds between checks

            if ($this->provider === '2captcha') {
                $solution = $this->get2CaptchaSolution($taskId);
            } elseif ($this->provider === 'anticaptcha') {
                $solution = $this->getAntiCaptchaSolution($taskId);
            } else {
                return null;
            }

            if ($solution) {
                return $solution;
            }
        }

        return null;
    }

    /**
     * Get solution from 2Captcha
     */
    protected function get2CaptchaSolution(string $taskId): ?string
    {
        $response = Http::get('https://2captcha.com/res.php', [
            'key' => $this->apiKey,
            'action' => 'get',
            'id' => $taskId,
        ]);

        if ($response->successful()) {
            $body = $response->body();
            if (strpos($body, 'OK|') === 0) {
                return substr($body, 3); // Extract solution
            } elseif ($body === 'CAPCHA_NOT_READY') {
                return null; // Still processing
            }
        }

        return null;
    }

    /**
     * Get solution from AntiCaptcha
     */
    protected function getAntiCaptchaSolution(string $taskId): ?string
    {
        $response = Http::post("{$this->apiUrl}/getTaskResult", [
            'clientKey' => $this->apiKey,
            'taskId' => $taskId,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            if ($data['errorId'] === 0 && isset($data['status'])) {
                if ($data['status'] === 'ready') {
                    // Extract solution based on captcha type
                    if (isset($data['solution']['gRecaptchaResponse'])) {
                        return $data['solution']['gRecaptchaResponse'];
                    } elseif (isset($data['solution']['token'])) {
                        return $data['solution']['token'];
                    } elseif (isset($data['solution']['text'])) {
                        return $data['solution']['text'];
                    }
                } elseif ($data['status'] === 'processing') {
                    return null; // Still processing
                }
            }
        }

        return null;
    }

    /**
     * Get cost for captcha type (in USD)
     */
    protected function getCost(string $captchaType): float
    {
        // Approximate costs per provider
        $costs = [
            '2captcha' => [
                'recaptcha_v2' => 0.0025,
                'recaptcha_v3' => 0.0025,
                'hcaptcha' => 0.0025,
                'image' => 0.001,
            ],
            'anticaptcha' => [
                'recaptcha_v2' => 0.001,
                'recaptcha_v3' => 0.001,
                'hcaptcha' => 0.001,
                'image' => 0.0005,
            ],
        ];

        return $costs[$this->provider][$captchaType] ?? 0.002;
    }

    /**
     * Detect captcha type on page
     */
    public function detectCaptchaType(string $pageHtml): ?string
    {
        // Check for reCAPTCHA v2
        if (preg_match('/data-sitekey=["\']([^"\']+)["\'].*recaptcha/i', $pageHtml) ||
            preg_match('/grecaptcha\.execute/i', $pageHtml)) {
            return 'recaptcha_v2';
        }

        // Check for reCAPTCHA v3
        if (preg_match('/recaptcha.*v3/i', $pageHtml) ||
            preg_match('/grecaptcha\.ready.*v3/i', $pageHtml)) {
            return 'recaptcha_v3';
        }

        // Check for hCaptcha
        if (preg_match('/hcaptcha/i', $pageHtml) ||
            preg_match('/data-sitekey=["\']([^"\']+)["\'].*hcaptcha/i', $pageHtml)) {
            return 'hcaptcha';
        }

        // Check for image captcha
        if (preg_match('/captcha.*image/i', $pageHtml) ||
            preg_match('/<img[^>]*captcha/i', $pageHtml)) {
            return 'image';
        }

        return null;
    }
}

