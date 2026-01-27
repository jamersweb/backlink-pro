<?php

namespace App\Services;

use App\Models\CaptchaLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CaptchaService
{
    protected $provider;
    protected $apiKey;
    protected $apiUrl;

    public function __construct()
    {
        $this->provider = config('services.captcha.provider', '2captcha');
        
        if ($this->provider === 'anticaptcha') {
            $this->apiKey = config('services.captcha.anticaptcha.api_key');
            $this->apiUrl = config('services.captcha.anticaptcha.api_url', 'https://api.anti-captcha.com');
        } else {
            $this->apiKey = config('services.captcha.2captcha.api_key');
            $this->apiUrl = config('services.captcha.2captcha.api_url', 'https://2captcha.com');
        }
    }

    /**
     * Solve reCAPTCHA v2
     */
    public function solveRecaptchaV2(string $siteKey, string $pageUrl, array $options = [], ?int $campaignId = null, ?string $siteDomain = null): ?string
    {
        if (!$this->apiKey) {
            Log::warning('Captcha API key not configured');
            return null;
        }

        try {
            if ($this->provider === 'anticaptcha') {
                return $this->solveWithAntiCaptcha('RecaptchaV2TaskProxyless', [
                    'websiteURL' => $pageUrl,
                    'websiteKey' => $siteKey,
                ], $campaignId, $siteDomain);
            } else {
                return $this->solveWith2Captcha('recaptcha', [
                    'googlekey' => $siteKey,
                    'pageurl' => $pageUrl,
                ], $campaignId, $siteDomain);
            }
        } catch (\Exception $e) {
            Log::error('Captcha solving failed', [
                'error' => $e->getMessage(),
                'provider' => $this->provider,
                'site_key' => $siteKey,
            ]);
            return null;
        }
    }

    /**
     * Solve hCaptcha
     */
    public function solveHcaptcha(string $siteKey, string $pageUrl): ?string
    {
        if (!$this->apiKey) {
            return null;
        }

        try {
            if ($this->provider === 'anticaptcha') {
                return $this->solveWithAntiCaptcha('HCaptchaTaskProxyless', [
                    'websiteURL' => $pageUrl,
                    'websiteKey' => $siteKey,
                ]);
            } else {
                return $this->solveWith2Captcha('hcaptcha', [
                    'sitekey' => $siteKey,
                    'pageurl' => $pageUrl,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('hCaptcha solving failed', [
                'error' => $e->getMessage(),
                'provider' => $this->provider,
            ]);
            return null;
        }
    }

    /**
     * Solve with 2Captcha
     */
    protected function solveWith2Captcha(string $method, array $params, ?int $campaignId = null, ?string $siteDomain = null): ?string
    {
        // Submit captcha
        $submitResponse = Http::asForm()->post("{$this->apiUrl}/in.php", [
            'key' => $this->apiKey,
            'method' => $method,
            ...$params,
        ]);

        if (!$submitResponse->successful() || !str_starts_with($submitResponse->body(), 'OK|')) {
            throw new \Exception('Failed to submit captcha: ' . $submitResponse->body());
        }

        $captchaId = explode('|', $submitResponse->body())[1];
        
        // Wait for solution (poll every 5 seconds, max 2 minutes)
        $maxAttempts = 24;
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            sleep(5);
            $attempt++;

            $resultResponse = Http::get("{$this->apiUrl}/res.php", [
                'key' => $this->apiKey,
                'action' => 'get',
                'id' => $captchaId,
            ]);

            $body = $resultResponse->body();

            if (str_starts_with($body, 'OK|')) {
                $token = explode('|', $body)[1];
                $this->logCaptchaUsage('2captcha', $method, $captchaId, true, null, $campaignId, $siteDomain);
                return $token;
            } elseif ($body === 'CAPCHA_NOT_READY') {
                continue; // Still processing
            } else {
                $this->logCaptchaUsage('2captcha', $method, $captchaId, false, $body, $campaignId, $siteDomain);
                throw new \Exception('Captcha solving failed: ' . $body);
            }
        }

        throw new \Exception('Captcha solving timeout');
    }

    /**
     * Solve with AntiCaptcha
     */
    protected function solveWithAntiCaptcha(string $taskType, array $taskData, ?int $campaignId = null, ?string $siteDomain = null): ?string
    {
        // Create task
        $createResponse = Http::post("{$this->apiUrl}/createTask", [
            'clientKey' => $this->apiKey,
            'task' => [
                'type' => $taskType,
                ...$taskData,
            ],
        ]);

        $createData = $createResponse->json();
        
        if ($createData['errorId'] !== 0) {
            throw new \Exception('Failed to create task: ' . $createData['errorDescription']);
        }

        $taskId = $createData['taskId'];

        // Wait for solution (poll every 5 seconds, max 2 minutes)
        $maxAttempts = 24;
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            sleep(5);
            $attempt++;

            $resultResponse = Http::post("{$this->apiUrl}/getTaskResult", [
                'clientKey' => $this->apiKey,
                'taskId' => $taskId,
            ]);

            $resultData = $resultResponse->json();

            if ($resultData['status'] === 'ready') {
                $token = $resultData['solution']['gRecaptchaResponse'] ?? $resultData['solution']['token'] ?? null;
                $this->logCaptchaUsage('anticaptcha', $taskType, $taskId, true, null, $campaignId, $siteDomain);
                return $token;
            } elseif ($resultData['status'] === 'processing') {
                continue;
            } else {
                $this->logCaptchaUsage('anticaptcha', $taskType, $taskId, false, $resultData['errorDescription'] ?? 'Unknown error', $campaignId, $siteDomain);
                throw new \Exception('Captcha solving failed: ' . ($resultData['errorDescription'] ?? 'Unknown error'));
            }
        }

        throw new \Exception('Captcha solving timeout');
    }

    /**
     * Log captcha usage
     */
    protected function logCaptchaUsage(string $provider, string $type, string $taskId, bool $success, ?string $error = null, ?int $campaignId = null, ?string $siteDomain = null): void
    {
        try {
            // Map provider to service enum value
            $service = ($provider === '2captcha') ? CaptchaLog::SERVICE_2CAPTCHA : CaptchaLog::SERVICE_ANTICAPTCHA;
            
            // Map type to captcha_type enum
            $captchaType = match($type) {
                'RecaptchaV2TaskProxyless', 'recaptcha_v2' => CaptchaLog::TYPE_RECAPTCHA_V2,
                'HCaptchaTaskProxyless', 'hcaptcha' => CaptchaLog::TYPE_HCAPTCHA,
                'recaptcha_invisible' => CaptchaLog::TYPE_RECAPTCHA_INVISIBLE,
                default => CaptchaLog::TYPE_IMAGE,
            };
            
            CaptchaLog::create([
                'campaign_id' => $campaignId,
                'site_domain' => $siteDomain ?? 'unknown',
                'captcha_type' => $captchaType,
                'service' => $service,
                'order_id' => $taskId,
                'status' => $success ? CaptchaLog::STATUS_SOLVED : CaptchaLog::STATUS_FAILED,
                'error' => $error,
                'estimated_cost' => $this->getCost($provider, $type, $success),
                'solved_at' => $success ? now() : null,
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to log captcha usage', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get cost estimate for captcha solving
     */
    protected function getCost(string $provider, string $type, bool $success): float
    {
        if (!$success) {
            return 0;
        }

        // Approximate costs (in USD)
        $costs = [
            '2captcha' => [
                'recaptcha' => 0.0025,
                'hcaptcha' => 0.0025,
            ],
            'anticaptcha' => [
                'RecaptchaV2TaskProxyless' => 0.001,
                'HCaptchaTaskProxyless' => 0.001,
            ],
        ];

        return $costs[$provider][$type] ?? 0.002;
    }
}

