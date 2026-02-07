<?php

namespace App\Jobs;

use App\Models\Audit;
use App\Models\UsageEvent;
use App\Services\Google\PageSpeedService;
use App\Services\Billing\PlanLimiter;
use App\Services\Billing\UsageRecorder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RunPageSpeedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;
    public $tries = 2;

    public function __construct(
        public int $auditId,
        public string $url
    ) {
        $this->onQueue('integrations');
    }

    public function handle(): void
    {
        $audit = Audit::find($this->auditId);
        if (!$audit) {
            return;
        }

        $organization = $audit->organization;
        if ($organization) {
            $planLimiter = new PlanLimiter();
            if (!$planLimiter->canRunPageSpeed($organization)) {
                $this->persistKpis([
                    'url' => $this->url,
                    'status' => 'limit_exceeded',
                    'cache_hit' => false,
                    'kpis' => null,
                    'error' => 'Daily PageSpeed limit exceeded',
                    'source' => $organization->pagespeed_byok_enabled ? 'byok' : 'shared_key',
                    'fetched_at' => now()->toIso8601String(),
                ]);
                return;
            }
        }

        $service = new PageSpeedService();
        $mobile = $service->run($this->url, 'mobile', $organization);
        $desktop = $service->run($this->url, 'desktop', $organization);

        if ($organization) {
            $apiCalls = 0;
            $apiCalls += $mobile['cache_hit'] ? 0 : 1;
            $apiCalls += $desktop['cache_hit'] ? 0 : 1;

            if ($apiCalls > 0) {
                UsageRecorder::record(
                    $organization->id,
                    UsageEvent::TYPE_PAGESPEED_RUN,
                    $apiCalls,
                    $audit->id,
                    ['url' => $this->url]
                );
            }
        }

        $this->persistKpis([
            'url' => $this->url,
            'mobile' => $mobile,
            'desktop' => $desktop,
            'source' => $mobile['source'] ?? 'shared_key',
            'fetched_at' => $mobile['fetched_at'] ?? now()->toIso8601String(),
            'cache_hit' => ($mobile['cache_hit'] ?? false) && ($desktop['cache_hit'] ?? false),
        ]);
    }

    protected function persistKpis(array $pagespeed): void
    {
        $audit = Audit::find($this->auditId);
        if (!$audit) {
            return;
        }

        try {
            $kpis = $audit->audit_kpis ?? [];
            $kpis['google'] = $kpis['google'] ?? [];
            $kpis['google']['pagespeed'] = $pagespeed;
            $audit->audit_kpis = $kpis;
            $audit->save();
        } catch (\Exception $e) {
            Log::warning('Failed to persist PageSpeed KPIs', [
                'audit_id' => $this->auditId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
