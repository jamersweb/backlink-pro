<?php

namespace App\Services\Google;

use App\Models\Organization;
use App\Models\PageSpeedResult;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PageSpeedService
{
    public function run(string $url, string $strategy, ?Organization $organization = null): array
    {
        $strategy = strtolower($strategy) === 'desktop' ? 'desktop' : 'mobile';
        $keyInfo = $this->resolveKey($organization);
        $orgId = $keyInfo['cache_org_id'];

        if (!Schema::hasTable('page_speed_results')) {
            return [
                'status' => 'failed',
                'cache_hit' => false,
                'kpis' => null,
                'error' => 'PageSpeed cache table missing. Please run migrations.',
                'source' => $keyInfo['source'],
                'fetched_at' => null,
            ];
        }

        $cached = PageSpeedResult::where('organization_id', $orgId)
            ->where('url', $url)
            ->where('strategy', $strategy)
            ->where('expires_at', '>', now())
            ->orderByDesc('fetched_at')
            ->first();

        if ($cached && $cached->status === 'success') {
            return [
                'status' => $cached->status,
                'cache_hit' => true,
                'kpis' => $cached->kpis ?? null,
                'error' => $cached->error_message,
                'source' => $keyInfo['source'],
                'fetched_at' => optional($cached->fetched_at)->toIso8601String(),
            ];
        }

        if (!$keyInfo['key']) {
            return [
                'status' => 'failed',
                'cache_hit' => false,
                'kpis' => null,
                'error' => $keyInfo['error'] ?? 'PageSpeed API key not configured',
                'source' => $keyInfo['source'],
                'fetched_at' => null,
            ];
        }

        try {
            $response = $this->callApi($url, $strategy, $keyInfo['key']);
            $status = $response->status();
            $body = $response->json();

            if (!$response->successful()) {
                $error = $body['error']['message'] ?? 'PageSpeed API error';
                $result = $this->safeStoreResult(
                    $orgId,
                    $url,
                    $strategy,
                    'failed',
                    $status,
                    $error,
                    $this->compactPayload($body),
                    null
                );

                return [
                    'status' => 'failed',
                    'cache_hit' => false,
                    'kpis' => null,
                    'error' => $error,
                    'source' => $keyInfo['source'],
                    'fetched_at' => optional($result?->fetched_at)->toIso8601String(),
                ];
            }

            $kpis = $this->extractKpis($body);
            $result = $this->safeStoreResult(
                $orgId,
                $url,
                $strategy,
                'success',
                $status,
                null,
                $this->compactPayload($body),
                $kpis
            );

            return [
                'status' => 'success',
                'cache_hit' => false,
                'kpis' => $kpis,
                'error' => null,
                'source' => $keyInfo['source'],
                'fetched_at' => optional($result?->fetched_at)->toIso8601String() ?? now()->toIso8601String(),
            ];
        } catch (\Exception $e) {
            Log::warning('PageSpeed API failed', [
                'url' => $url,
                'strategy' => $strategy,
                'error' => $e->getMessage(),
            ]);

            $result = $this->safeStoreResult(
                $orgId,
                $url,
                $strategy,
                'failed',
                null,
                $this->truncateError($e->getMessage()),
                null,
                null
            );

            return [
                'status' => 'failed',
                'cache_hit' => false,
                'kpis' => null,
                'error' => $this->truncateError($e->getMessage()),
                'source' => $keyInfo['source'],
                'fetched_at' => optional($result?->fetched_at)->toIso8601String(),
            ];
        }
    }

    protected function callApi(string $url, string $strategy, string $apiKey)
    {
        $globalPerMin = (int) config('services.google.pagespeed_global_per_min', 60);
        $timeoutSeconds = (int) config('services.google.pagespeed_timeout_seconds', 90);
        $connectTimeoutSeconds = (int) config('services.google.pagespeed_connect_timeout_seconds', 15);
        $retryTimes = (int) config('services.google.pagespeed_retry_times', 2);
        $retrySleepMs = (int) config('services.google.pagespeed_retry_sleep_ms', 2000);

        try {
            Cache::throttle('pagespeed-global')
                ->allow($globalPerMin)
                ->every(60)
                ->block(5);
        } catch (\Throwable $e) {
            // Some cache stores (e.g. database) do not support throttling.
        }

        $params = [
            'url' => $url,
            'strategy' => $strategy,
            'category' => ['performance', 'seo', 'accessibility', 'best-practices'],
            'key' => $apiKey,
        ];

        return Http::connectTimeout($connectTimeoutSeconds)
            ->timeout($timeoutSeconds)
            ->retry($retryTimes, $retrySleepMs)
            ->get('https://www.googleapis.com/pagespeedonline/v5/runPagespeed', $params);
    }

    protected function storeResult(
        ?int $orgId,
        string $url,
        string $strategy,
        string $status,
        ?int $httpStatus,
        ?string $errorMessage,
        ?array $payload,
        ?array $kpis
    ): PageSpeedResult {
        $expiresAt = $status === 'success'
            ? now()->addDay()
            : now()->addMinutes(10);

        return PageSpeedResult::updateOrCreate(
            [
                'organization_id' => $orgId,
                'url' => $url,
                'strategy' => $strategy,
            ],
            [
                'fetched_at' => now(),
                'expires_at' => $expiresAt,
                'status' => $status,
                'http_status' => $httpStatus,
                'error_message' => $errorMessage,
                'payload' => $payload,
                'kpis' => $kpis,
            ]
        );
    }

    protected function safeStoreResult(
        ?int $orgId,
        string $url,
        string $strategy,
        string $status,
        ?int $httpStatus,
        ?string $errorMessage,
        ?array $payload,
        ?array $kpis
    ): ?PageSpeedResult {
        try {
            return $this->storeResult($orgId, $url, $strategy, $status, $httpStatus, $errorMessage, $payload, $kpis);
        } catch (\Throwable $e) {
            Log::warning('PageSpeed result persistence failed', [
                'url' => $url,
                'strategy' => $strategy,
                'status' => $status,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected function extractKpis(array $body): array
    {
        $lighthouse = $body['lighthouseResult'] ?? [];
        $categories = $lighthouse['categories'] ?? [];
        $audits = $lighthouse['audits'] ?? [];

        $score = fn($key) => isset($categories[$key]['score']) ? (int) round($categories[$key]['score'] * 100) : null;
        $auditNumeric = fn($key) => $audits[$key]['numericValue'] ?? null;

        $opportunities = [];
        foreach ($audits as $id => $audit) {
            $details = $audit['details'] ?? null;
            if (!$details || ($details['type'] ?? '') !== 'opportunity') {
                continue;
            }
            $opportunities[] = [
                'id' => $id,
                'title' => $audit['title'] ?? $id,
                'description' => $audit['description'] ?? null,
                'savings_ms' => $details['overallSavingsMs'] ?? null,
                'savings_bytes' => $details['overallSavingsBytes'] ?? null,
            ];
        }

        usort($opportunities, function ($a, $b) {
            return (int) ($b['savings_ms'] ?? 0) <=> (int) ($a['savings_ms'] ?? 0);
        });

        return [
            'analysis_timestamp' => $lighthouse['fetchTime'] ?? $body['analysisUTCTimestamp'] ?? null,
            'categories' => [
                'performance_score' => $score('performance'),
                'seo_score' => $score('seo'),
                'accessibility_score' => $score('accessibility'),
                'best_practices_score' => $score('best-practices'),
            ],
            'lab_metrics' => [
                'fcp_ms' => $auditNumeric('first-contentful-paint'),
                'lcp_ms' => $auditNumeric('largest-contentful-paint'),
                'cls' => $auditNumeric('cumulative-layout-shift'),
                'tbt_ms' => $auditNumeric('total-blocking-time'),
                'speed_index_ms' => $auditNumeric('speed-index'),
                'tti_ms' => $auditNumeric('interactive'),
            ],
            'opportunities' => array_slice($opportunities, 0, 8),
        ];
    }

    protected function compactPayload(array $body): array
    {
        $lighthouse = $body['lighthouseResult'] ?? [];
        $audits = $lighthouse['audits'] ?? [];

        return array_filter([
            'kind' => $body['kind'] ?? null,
            'id' => $body['id'] ?? null,
            'analysis_timestamp' => $body['analysisUTCTimestamp'] ?? ($lighthouse['fetchTime'] ?? null),
            'captcha_result' => $body['captchaResult'] ?? null,
            'requested_url' => $lighthouse['requestedUrl'] ?? null,
            'final_url' => $lighthouse['finalUrl'] ?? null,
            'performance_score' => data_get($lighthouse, 'categories.performance.score'),
            'seo_score' => data_get($lighthouse, 'categories.seo.score'),
            'accessibility_score' => data_get($lighthouse, 'categories.accessibility.score'),
            'best_practices_score' => data_get($lighthouse, 'categories.best-practices.score'),
            'fetch_time' => $lighthouse['fetchTime'] ?? null,
            'run_warnings' => array_slice($lighthouse['runWarnings'] ?? [], 0, 3),
            'lcp_ms' => data_get($audits, 'largest-contentful-paint.numericValue'),
            'fcp_ms' => data_get($audits, 'first-contentful-paint.numericValue'),
            'cls' => data_get($audits, 'cumulative-layout-shift.numericValue'),
            'tbt_ms' => data_get($audits, 'total-blocking-time.numericValue'),
            'tti_ms' => data_get($audits, 'interactive.numericValue'),
        ], fn ($value) => $value !== null && $value !== []);
    }

    protected function truncateError(?string $message): ?string
    {
        if ($message === null) {
            return null;
        }

        $message = trim($message);

        if (mb_strlen($message) <= 400) {
            return $message;
        }

        return rtrim(mb_substr($message, 0, 397)) . '...';
    }

    protected function resolveKey(?Organization $organization): array
    {
        $sharedKey = config('services.google.pagespeed_api_key');

        if ($organization && $organization->pagespeed_byok_enabled) {
            if (!$organization->pagespeed_api_key_encrypted) {
                return [
                    'key' => null,
                    'source' => 'byok',
                    'cache_org_id' => $organization->id,
                    'error' => 'BYOK key missing',
                ];
            }
            if (!$organization->pagespeed_last_key_verified_at) {
                return [
                    'key' => null,
                    'source' => 'byok',
                    'cache_org_id' => $organization->id,
                    'error' => 'BYOK key not verified',
                ];
            }

            return [
                'key' => $organization->pagespeed_api_key_encrypted,
                'source' => 'byok',
                'cache_org_id' => $organization->id,
                'error' => null,
            ];
        }

        return [
            'key' => $sharedKey,
            'source' => 'shared_key',
            'cache_org_id' => null,
            'error' => $sharedKey ? null : 'PageSpeed API key not configured',
        ];
    }
}
