<?php

namespace App\Jobs;

use App\Models\Audit;
use App\Services\Google\CruxResultDto;
use App\Services\Google\CruxService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RunCruxJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;
    public $tries = 2;

    public function __construct(
        public int $auditId,
        public string $url
    ) {
        $this->onQueue('integrations');
    }

    public function handle(): void
    {
        // Manual QA: use https://web.dev for success, a low-traffic site for no_data, re-run within 24h for cache.
        $audit = Audit::find($this->auditId);
        if (!$audit) {
            return;
        }

        $organization = $audit->organization;
        $service = new CruxService();
        $keyInfo = $service->resolveKey($organization);

        if (!$keyInfo['key']) {
            $this->persistKpis([
                'status' => 'failed',
                'cache_hit' => false,
                'error' => $keyInfo['error'] ?? 'CrUX API key not configured',
                'fetched_at' => now()->toIso8601String(),
            ]);
            return;
        }

        $origin = $this->deriveOrigin($this->url);
        if (!$origin) {
            $this->persistKpis([
                'status' => 'failed',
                'cache_hit' => false,
                'error' => 'Unable to derive origin from URL',
                'fetched_at' => now()->toIso8601String(),
            ]);
            return;
        }

        $metrics = [
            'largest_contentful_paint',
            'interaction_to_next_paint',
            'cumulative_layout_shift',
            'experimental_time_to_first_byte',
            'round_trip_time',
        ];

        $mobile = $this->fetchWithFallback($service, $this->url, $origin, 'PHONE', $metrics, $organization);
        $desktop = $this->fetchWithFallback($service, $this->url, $origin, 'DESKTOP', $metrics, $organization);
        $all = $this->fetchWithFallback($service, $this->url, $origin, 'ALL', null, $organization);

        $levelUsed = $this->resolveLevelUsed($mobile, $desktop, $all);

        $cacheHit = ($mobile->cacheHit ?? false)
            && ($desktop->cacheHit ?? false)
            && ($all->cacheHit ?? false);

        $status = $this->overallStatus([$mobile, $desktop]);
        $fetchedAt = $mobile->fetchedAt ?? $desktop->fetchedAt ?? $all->fetchedAt ?? now()->toIso8601String();

        $this->persistKpis([
            'level_used' => $levelUsed,
            'url_requested' => $this->url,
            'origin_used' => $origin,
            'mobile' => $this->auditPayload($mobile),
            'desktop' => $this->auditPayload($desktop),
            'all' => $this->auditPayload($all),
            'fetched_at' => $fetchedAt,
            'cache_hit' => $cacheHit,
            'status' => $status,
        ]);
    }

    protected function fetchWithFallback(
        CruxService $service,
        string $url,
        string $origin,
        string $formFactor,
        ?array $metrics,
        $organization
    ): CruxResultDto {
        $formFactor = strtoupper($formFactor);
        $ff = $formFactor === 'ALL' ? null : $formFactor;

        $primary = $service->queryRecord('url', $url, $ff, $metrics, $organization);
        if ($primary->status !== 'no_data') {
            return $primary;
        }

        $fallback = $service->queryRecord('origin', $origin, $ff, $metrics, $organization);
        return $fallback;
    }

    protected function resolveLevelUsed(CruxResultDto $mobile, CruxResultDto $desktop, CruxResultDto $all): string
    {
        $levels = array_filter([
            $mobile->targetType,
            $desktop->targetType,
            $all->targetType,
        ]);

        $levels = array_unique($levels);
        if (count($levels) === 1) {
            return $levels[0];
        }

        return 'mixed';
    }

    protected function overallStatus(array $results): string
    {
        $statuses = collect($results)->pluck('status')->filter()->unique()->values();
        if ($statuses->contains('success')) {
            return 'success';
        }
        if ($statuses->contains('no_data')) {
            return 'no_data';
        }
        return 'failed';
    }

    protected function deriveOrigin(string $url): ?string
    {
        $parts = parse_url($url);
        if (!$parts || empty($parts['host'])) {
            return null;
        }
        $scheme = $parts['scheme'] ?? 'https';
        return $scheme . '://' . $parts['host'];
    }

    protected function persistKpis(array $crux): void
    {
        $audit = Audit::find($this->auditId);
        if (!$audit) {
            return;
        }

        try {
            $kpis = $audit->audit_kpis ?? [];
            $kpis['google'] = $kpis['google'] ?? [];
            $kpis['google']['crux'] = $crux;
            $audit->audit_kpis = $kpis;
            $audit->save();
        } catch (\Exception $e) {
            Log::warning('Failed to persist CrUX KPIs', [
                'audit_id' => $this->auditId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function auditPayload(CruxResultDto $dto): array
    {
        $payload = $dto->toArray();
        unset($payload['raw_payload']);
        return $payload;
    }
}
