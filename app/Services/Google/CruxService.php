<?php

namespace App\Services\Google;

use App\Models\CruxResult;
use App\Models\Organization;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class CruxService
{
    public function queryRecord(
        string $targetType,
        string $targetValue,
        ?string $formFactor = null,
        ?array $metrics = null,
        ?Organization $organization = null
    ): CruxResultDto {
        $targetType = strtolower($targetType) === 'origin' ? 'origin' : 'url';
        $formFactor = $formFactor ? strtoupper($formFactor) : 'ALL';

        $keyInfo = $this->resolveKey($organization);
        $orgId = $keyInfo['cache_org_id'];

        if (!Schema::hasTable('crux_results')) {
            return new CruxResultDto(
                'failed',
                false,
                null,
                null,
                'CrUX cache table missing. Please run migrations.',
                $targetType,
                $targetValue,
                $formFactor,
                null
            );
        }

        $cached = CruxResult::where('organization_id', $orgId)
            ->where('target_type', $targetType)
            ->where('target_value', $targetValue)
            ->where('form_factor', $formFactor)
            ->where('expires_at', '>', now())
            ->orderByDesc('fetched_at')
            ->first();

        if ($cached) {
            return new CruxResultDto(
                $cached->status,
                true,
                $cached->kpis,
                $cached->raw_payload,
                $cached->error_message,
                $targetType,
                $targetValue,
                $formFactor,
                optional($cached->fetched_at)->toIso8601String(),
                data_get($cached->kpis, 'form_factors')
            );
        }

        if (!$keyInfo['key']) {
            return new CruxResultDto(
                'failed',
                false,
                null,
                null,
                $keyInfo['error'] ?? 'CrUX API key not configured',
                $targetType,
                $targetValue,
                $formFactor,
                null
            );
        }

        try {
            $response = $this->callApi($targetType, $targetValue, $formFactor, $metrics, $keyInfo['key']);
            $status = $response->status();
            $body = $response->json();

            if (!$response->successful()) {
                $errorMessage = $body['error']['message'] ?? 'CrUX API error';
                $statusLabel = $this->isNoDataResponse($status, $errorMessage) ? 'no_data' : 'failed';
                $result = $this->storeResult($orgId, $targetType, $targetValue, $formFactor, $statusLabel, $errorMessage, $body, null);

                return new CruxResultDto(
                    $statusLabel,
                    false,
                    null,
                    $body,
                    $errorMessage,
                    $targetType,
                    $targetValue,
                    $formFactor,
                    optional($result->fetched_at)->toIso8601String()
                );
            }

            $record = $body['record'] ?? null;
            if (!$record || empty($record['metrics'])) {
                $result = $this->storeResult($orgId, $targetType, $targetValue, $formFactor, 'no_data', 'No data found', $body, null);
                return new CruxResultDto(
                    'no_data',
                    false,
                    null,
                    $body,
                    'No data found',
                    $targetType,
                    $targetValue,
                    $formFactor,
                    optional($result->fetched_at)->toIso8601String()
                );
            }

            $normalized = $this->normalizeRecord($record);
            $result = $this->storeResult($orgId, $targetType, $targetValue, $formFactor, 'success', null, $body, $normalized);

            return new CruxResultDto(
                'success',
                false,
                $normalized,
                $body,
                null,
                $targetType,
                $targetValue,
                $formFactor,
                optional($result->fetched_at)->toIso8601String(),
                $normalized['form_factors'] ?? null
            );
        } catch (\Exception $e) {
            Log::warning('CrUX API failed', [
                'target_type' => $targetType,
                'target_value' => $targetValue,
                'form_factor' => $formFactor,
                'error' => $e->getMessage(),
            ]);

            $result = $this->storeResult($orgId, $targetType, $targetValue, $formFactor, 'failed', $e->getMessage(), null, null);

            return new CruxResultDto(
                'failed',
                false,
                null,
                null,
                $e->getMessage(),
                $targetType,
                $targetValue,
                $formFactor,
                optional($result->fetched_at)->toIso8601String()
            );
        }
    }

    public function resolveKey(?Organization $organization): array
    {
        $sharedKey = config('services.google.crux_api_key');

        if ($organization && $organization->crux_byok_enabled) {
            if (!$organization->crux_api_key_encrypted) {
                return [
                    'key' => null,
                    'source' => 'byok',
                    'cache_org_id' => $organization->id,
                    'error' => 'BYOK key missing',
                ];
            }

            return [
                'key' => $organization->crux_api_key_encrypted,
                'source' => 'byok',
                'cache_org_id' => $organization->id,
                'error' => null,
            ];
        }

        return [
            'key' => $sharedKey,
            'source' => 'shared_key',
            'cache_org_id' => null,
            'error' => $sharedKey ? null : 'CrUX API key not configured',
        ];
    }

    protected function callApi(
        string $targetType,
        string $targetValue,
        string $formFactor,
        ?array $metrics,
        string $apiKey
    ) {
        $payload = [];
        if ($targetType === 'origin') {
            $payload['origin'] = $targetValue;
        } else {
            $payload['url'] = $targetValue;
        }

        if ($formFactor !== 'ALL') {
            $payload['formFactor'] = $formFactor;
        }

        if (!empty($metrics)) {
            $payload['metrics'] = $metrics;
        }

        $url = 'https://chromeuxreport.googleapis.com/v1/records:queryRecord?key=' . $apiKey;

        return Http::timeout(20)
            ->retry(2, 1000)
            ->post($url, $payload);
    }

    protected function storeResult(
        ?int $orgId,
        string $targetType,
        string $targetValue,
        string $formFactor,
        string $status,
        ?string $errorMessage,
        ?array $payload,
        ?array $kpis
    ): CruxResult {
        $expiresAt = $status === 'failed'
            ? now()->addMinutes(10)
            : now()->addDay();

        return CruxResult::updateOrCreate(
            [
                'organization_id' => $orgId,
                'target_type' => $targetType,
                'target_value' => $targetValue,
                'form_factor' => $formFactor,
            ],
            [
                'fetched_at' => now(),
                'expires_at' => $expiresAt,
                'status' => $status,
                'error_message' => $errorMessage,
                'raw_payload' => $payload,
                'kpis' => $kpis,
            ]
        );
    }

    protected function normalizeRecord(array $record): array
    {
        $metrics = $record['metrics'] ?? [];
        $normalized = [
            'lcp_p75_ms' => $this->metricP75($metrics, 'largest_contentful_paint'),
            'inp_p75_ms' => $this->metricP75($metrics, 'interaction_to_next_paint'),
            'cls_p75' => $this->metricP75($metrics, 'cumulative_layout_shift'),
            'ttfb_p75_ms' => $this->metricP75($metrics, 'experimental_time_to_first_byte'),
            'rtt_p75_ms' => $this->metricP75($metrics, 'round_trip_time'),
            'lcp_distribution' => $this->metricDistribution($metrics, 'largest_contentful_paint'),
            'inp_distribution' => $this->metricDistribution($metrics, 'interaction_to_next_paint'),
            'cls_distribution' => $this->metricDistribution($metrics, 'cumulative_layout_shift'),
            'ttfb_distribution' => $this->metricDistribution($metrics, 'experimental_time_to_first_byte'),
            'rtt_distribution' => $this->metricDistribution($metrics, 'round_trip_time'),
            'lcp_status' => $this->metricStatus('lcp', $this->metricP75($metrics, 'largest_contentful_paint')),
            'inp_status' => $this->metricStatus('inp', $this->metricP75($metrics, 'interaction_to_next_paint')),
            'cls_status' => $this->metricStatus('cls', $this->metricP75($metrics, 'cumulative_layout_shift')),
            'form_factors' => $record['formFactors'] ?? null,
        ];

        return $normalized;
    }

    protected function metricP75(array $metrics, string $key): ?float
    {
        if (!isset($metrics[$key]['percentiles']['p75'])) {
            return null;
        }
        $value = $metrics[$key]['percentiles']['p75'];
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return (float) $value;
        }
        $normalized = str_replace(',', '', (string) $value);
        return is_numeric($normalized) ? (float) $normalized : null;
    }

    protected function metricDistribution(array $metrics, string $key): ?array
    {
        $hist = $metrics[$key]['histogram'] ?? null;
        if (!$hist || !is_array($hist)) {
            return null;
        }

        $good = $hist[0]['density'] ?? null;
        $ni = $hist[1]['density'] ?? null;
        $poor = $hist[2]['density'] ?? null;

        return [
            'good_density' => $good,
            'ni_density' => $ni,
            'poor_density' => $poor,
        ];
    }

    protected function metricStatus(string $metric, ?float $p75): ?string
    {
        if ($p75 === null) {
            return null;
        }

        if ($metric === 'lcp') {
            if ($p75 <= 2500) return 'good';
            if ($p75 <= 4000) return 'ni';
            return 'poor';
        }

        if ($metric === 'inp') {
            if ($p75 <= 200) return 'good';
            if ($p75 <= 500) return 'ni';
            return 'poor';
        }

        if ($metric === 'cls') {
            if ($p75 <= 0.1) return 'good';
            if ($p75 <= 0.25) return 'ni';
            return 'poor';
        }

        return null;
    }

    protected function isNoDataResponse(int $status, ?string $message): bool
    {
        if ($status === 404) {
            return true;
        }
        if (!$message) {
            return false;
        }
        $message = strtolower($message);
        return str_contains($message, 'no data') || str_contains($message, 'not found');
    }
}
