<?php

namespace App\Services;

use App\Models\CaptchaLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Unified Captcha Solving Service
 * 
 * Supports 2Captcha and AntiCaptcha providers for solving:
 * - reCAPTCHA v2
 * - reCAPTCHA v3
 * - hCaptcha
 * - Image captchas
 */
class CaptchaService
{
    protected string $provider;
    protected ?string $apiKey;
    protected string $apiUrl;

    /**
     * Cache duration for balance checks (5 minutes)
     */
    protected int $balanceCacheDuration = 300;

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
     * Generic solve method - supports all captcha types
     */
    public function solve(string $captchaType, array $options = [], ?int $campaignId = null): ?array
    {
        if (!$this->apiKey) {
            Log::warning('Captcha API key not configured');
            return null;
        }

        try {
            $taskId = $this->submitTask($captchaType, $options);
            if (!$taskId) {
                return null;
            }

            $solution = $this->waitForSolution($taskId);

            if ($solution) {
                $cost = $this->getCost($captchaType);
                $this->logUsage($captchaType, $taskId, true, null, $cost, $campaignId, $options['site_domain'] ?? null);

                return [
                    'success' => true,
                    'solution' => $solution,
                    'task_id' => $taskId,
                    'cost' => $cost,
                ];
            }

            $this->logUsage($captchaType, $taskId, false, 'Solution not found', 0, $campaignId, $options['site_domain'] ?? null);
            return null;

        } catch (\Exception $e) {
            Log::error('Captcha solving failed', [
                'error' => $e->getMessage(),
                'provider' => $this->provider,
                'type' => $captchaType,
            ]);
            return null;
        }
    }

    /**
     * Solve reCAPTCHA v2 (convenience method)
     */
    public function solveRecaptchaV2(string $siteKey, string $pageUrl, array $options = [], ?int $campaignId = null, ?string $siteDomain = null): ?string
    {
        $result = $this->solve('recaptcha_v2', [
            'site_key' => $siteKey,
            'page_url' => $pageUrl,
            'site_domain' => $siteDomain,
            ...$options,
        ], $campaignId);

        return $result['solution'] ?? null;
    }

    /**
     * Solve reCAPTCHA v3 (convenience method)
     */
    public function solveRecaptchaV3(string $siteKey, string $pageUrl, string $action = 'verify', float $minScore = 0.3, ?int $campaignId = null, ?string $siteDomain = null): ?string
    {
        $result = $this->solve('recaptcha_v3', [
            'site_key' => $siteKey,
            'page_url' => $pageUrl,
            'action' => $action,
            'min_score' => $minScore,
            'site_domain' => $siteDomain,
        ], $campaignId);

        return $result['solution'] ?? null;
    }

    /**
     * Solve hCaptcha (convenience method)
     */
    public function solveHcaptcha(string $siteKey, string $pageUrl, ?int $campaignId = null, ?string $siteDomain = null): ?string
    {
        $result = $this->solve('hcaptcha', [
            'site_key' => $siteKey,
            'page_url' => $pageUrl,
            'site_domain' => $siteDomain,
        ], $campaignId);

        return $result['solution'] ?? null;
    }

    /**
     * Solve image captcha (convenience method)
     */
    public function solveImageCaptcha(string $imageBase64, ?int $campaignId = null, ?string $siteDomain = null): ?string
    {
        $result = $this->solve('image', [
            'image' => $imageBase64,
            'site_domain' => $siteDomain,
        ], $campaignId);

        return $result['solution'] ?? null;
    }

    /**
     * Submit captcha task to provider
     */
    protected function submitTask(string $captchaType, array $options): ?string
    {
        if ($this->provider === '2captcha') {
            return $this->submit2Captcha($captchaType, $options);
        } elseif ($this->provider === 'anticaptcha') {
            return $this->submitAntiCaptcha($captchaType, $options);
        }

        return null;
    }

    /**
     * Submit task to 2Captcha
     */
    protected function submit2Captcha(string $captchaType, array $options): ?string
    {
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
                $params['min_score'] = $options['min_score'] ?? 0.3;
                break;

            case 'hcaptcha':
                $params['method'] = 'hcaptcha';
                $params['sitekey'] = $options['site_key'] ?? '';
                $params['pageurl'] = $options['page_url'] ?? '';
                break;

            case 'image':
                $params['method'] = 'base64';
                $params['body'] = $options['image'] ?? '';
                break;

            default:
                throw new \InvalidArgumentException("Unsupported captcha type: {$captchaType}");
        }

        $response = Http::timeout(30)->asForm()->post("{$this->apiUrl}/in.php", $params);

        if (!$response->successful()) {
            throw new \RuntimeException('2Captcha API request failed: ' . $response->status());
        }

        $body = $response->body();
        if (str_starts_with($body, 'OK|')) {
            return explode('|', $body)[1];
        }

        throw new \RuntimeException('2Captcha submission failed: ' . $body);
    }

    /**
     * Submit task to AntiCaptcha
     */
    protected function submitAntiCaptcha(string $captchaType, array $options): ?string
    {
        $task = match ($captchaType) {
            'recaptcha_v2' => [
                'type' => 'RecaptchaV2TaskProxyless',
                'websiteURL' => $options['page_url'] ?? '',
                'websiteKey' => $options['site_key'] ?? '',
            ],
            'recaptcha_v3' => [
                'type' => 'RecaptchaV3TaskProxyless',
                'websiteURL' => $options['page_url'] ?? '',
                'websiteKey' => $options['site_key'] ?? '',
                'minScore' => $options['min_score'] ?? 0.3,
                'pageAction' => $options['action'] ?? 'verify',
            ],
            'hcaptcha' => [
                'type' => 'HCaptchaTaskProxyless',
                'websiteURL' => $options['page_url'] ?? '',
                'websiteKey' => $options['site_key'] ?? '',
            ],
            'image' => [
                'type' => 'ImageToTextTask',
                'body' => $options['image'] ?? '',
            ],
            default => throw new \InvalidArgumentException("Unsupported captcha type: {$captchaType}"),
        };

        $response = Http::timeout(30)->post("{$this->apiUrl}/createTask", [
            'clientKey' => $this->apiKey,
            'task' => $task,
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('AntiCaptcha API request failed: ' . $response->status());
        }

        $data = $response->json();
        if (($data['errorId'] ?? 1) !== 0) {
            throw new \RuntimeException('AntiCaptcha submission failed: ' . ($data['errorDescription'] ?? 'Unknown error'));
        }

        return (string) ($data['taskId'] ?? '');
    }

    /**
     * Wait for and retrieve captcha solution
     */
    protected function waitForSolution(string $taskId, int $maxAttempts = 24): ?string
    {
        for ($i = 0; $i < $maxAttempts; $i++) {
            sleep(5); // Wait 5 seconds between checks

            $solution = $this->provider === '2captcha'
                ? $this->get2CaptchaSolution($taskId)
                : $this->getAntiCaptchaSolution($taskId);

            if ($solution !== null) {
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
        $response = Http::timeout(30)->get("{$this->apiUrl}/res.php", [
            'key' => $this->apiKey,
            'action' => 'get',
            'id' => $taskId,
        ]);

        if (!$response->successful()) {
            return null;
        }

        $body = $response->body();

        if (str_starts_with($body, 'OK|')) {
            return explode('|', $body)[1];
        }

        if ($body === 'CAPCHA_NOT_READY') {
            return null; // Still processing
        }

        // Error occurred
        throw new \RuntimeException('2Captcha solution failed: ' . $body);
    }

    /**
     * Get solution from AntiCaptcha
     */
    protected function getAntiCaptchaSolution(string $taskId): ?string
    {
        $response = Http::timeout(30)->post("{$this->apiUrl}/getTaskResult", [
            'clientKey' => $this->apiKey,
            'taskId' => $taskId,
        ]);

        if (!$response->successful()) {
            return null;
        }

        $data = $response->json();

        if (($data['errorId'] ?? 1) !== 0) {
            throw new \RuntimeException('AntiCaptcha solution failed: ' . ($data['errorDescription'] ?? 'Unknown error'));
        }

        if (($data['status'] ?? '') === 'ready') {
            return $data['solution']['gRecaptchaResponse']
                ?? $data['solution']['token']
                ?? $data['solution']['text']
                ?? null;
        }

        // Still processing
        return null;
    }

    /**
     * Get account balance (cached)
     */
    public function getBalance(): ?float
    {
        $cacheKey = "captcha:balance:{$this->provider}";

        return Cache::remember($cacheKey, $this->balanceCacheDuration, function () {
            try {
                if ($this->provider === '2captcha') {
                    $response = Http::timeout(10)->get("{$this->apiUrl}/res.php", [
                        'key' => $this->apiKey,
                        'action' => 'getbalance',
                    ]);

                    if ($response->successful() && is_numeric($response->body())) {
                        return (float) $response->body();
                    }
                } else {
                    $response = Http::timeout(10)->post("{$this->apiUrl}/getBalance", [
                        'clientKey' => $this->apiKey,
                    ]);

                    $data = $response->json();
                    if (($data['errorId'] ?? 1) === 0) {
                        return (float) ($data['balance'] ?? 0);
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Failed to get captcha balance', ['error' => $e->getMessage()]);
            }

            return null;
        });
    }

    /**
     * Clear balance cache
     */
    public function clearBalanceCache(): void
    {
        Cache::forget("captcha:balance:{$this->provider}");
    }

    /**
     * Detect captcha type from HTML
     */
    public function detectCaptchaType(string $pageHtml): ?string
    {
        // reCAPTCHA v3 (check first as it's more specific)
        if (preg_match('/grecaptcha\.ready|recaptcha.*v3/i', $pageHtml)) {
            return 'recaptcha_v3';
        }

        // reCAPTCHA v2
        if (preg_match('/data-sitekey=["\'][^"\']+["\'].*(?:recaptcha|g-recaptcha)/i', $pageHtml) ||
            preg_match('/class=["\']g-recaptcha["\']/i', $pageHtml)) {
            return 'recaptcha_v2';
        }

        // hCaptcha
        if (preg_match('/hcaptcha|h-captcha/i', $pageHtml)) {
            return 'hcaptcha';
        }

        // Image captcha
        if (preg_match('/<img[^>]*captcha/i', $pageHtml) ||
            preg_match('/captcha.*image/i', $pageHtml)) {
            return 'image';
        }

        return null;
    }

    /**
     * Extract site key from HTML
     */
    public function extractSiteKey(string $pageHtml, string $captchaType): ?string
    {
        $pattern = match ($captchaType) {
            'recaptcha_v2', 'recaptcha_v3' => '/data-sitekey=["\']([^"\']+)["\']/',
            'hcaptcha' => '/data-sitekey=["\']([^"\']+)["\']/',
            default => null,
        };

        if ($pattern && preg_match($pattern, $pageHtml, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Log captcha usage
     */
    protected function logUsage(
        string $captchaType,
        string $taskId,
        bool $success,
        ?string $error,
        float $cost,
        ?int $campaignId,
        ?string $siteDomain
    ): void {
        try {
            $service = $this->provider === '2captcha'
                ? CaptchaLog::SERVICE_2CAPTCHA
                : CaptchaLog::SERVICE_ANTICAPTCHA;

            $logType = match ($captchaType) {
                'recaptcha_v2' => CaptchaLog::TYPE_RECAPTCHA_V2,
                'recaptcha_v3' => CaptchaLog::TYPE_RECAPTCHA_V3 ?? CaptchaLog::TYPE_RECAPTCHA_V2,
                'recaptcha_invisible' => CaptchaLog::TYPE_RECAPTCHA_INVISIBLE,
                'hcaptcha' => CaptchaLog::TYPE_HCAPTCHA,
                default => CaptchaLog::TYPE_IMAGE,
            };

            CaptchaLog::create([
                'campaign_id' => $campaignId,
                'site_domain' => $siteDomain ?? 'unknown',
                'captcha_type' => $logType,
                'service' => $service,
                'order_id' => $taskId,
                'status' => $success ? CaptchaLog::STATUS_SOLVED : CaptchaLog::STATUS_FAILED,
                'error' => $error,
                'estimated_cost' => $cost,
                'solved_at' => $success ? now() : null,
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to log captcha usage', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get cost estimate for captcha type (in USD)
     */
    protected function getCost(string $captchaType): float
    {
        $costs = [
            '2captcha' => [
                'recaptcha_v2' => 0.0029,
                'recaptcha_v3' => 0.0029,
                'hcaptcha' => 0.0029,
                'image' => 0.001,
            ],
            'anticaptcha' => [
                'recaptcha_v2' => 0.002,
                'recaptcha_v3' => 0.002,
                'hcaptcha' => 0.002,
                'image' => 0.001,
            ],
        ];

        return $costs[$this->provider][$captchaType] ?? 0.002;
    }

    /**
     * Get current provider name
     */
    public function getProvider(): string
    {
        return $this->provider;
    }

    /**
     * Check if service is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }
}
