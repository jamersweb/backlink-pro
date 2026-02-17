<?php

namespace App\Jobs;

use App\Models\Audit;
use App\Services\Google\PageSpeedService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class EnrichPsiJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 35;
    public $tries = 1;

    public function __construct(public int $auditId) {}

    public function handle(): void
    {
        $audit = Audit::find($this->auditId);
        if (!$audit) return;

        $cacheKey = 'audit_psi:' . md5($audit->normalized_url);
        $data = Cache::get($cacheKey);

        if ($data === null) {
            $service = new PageSpeedService();
            $mobile = ['status' => 'failed', 'kpis' => null, 'error' => 'Timeout'];
            $desktop = ['status' => 'failed', 'kpis' => null, 'error' => 'Timeout'];
            try {
                $mobile = $service->run($audit->normalized_url, 'mobile', $audit->organization);
                $desktop = $service->run($audit->normalized_url, 'desktop', $audit->organization);
            } catch (\Throwable $e) {
                Log::warning('EnrichPsiJob failed', ['audit_id' => $audit->id, 'error' => $e->getMessage()]);
                $mobile['error'] = $e->getMessage();
                $desktop['error'] = $e->getMessage();
            }
            $data = [
                'url' => $audit->normalized_url,
                'mobile' => $mobile,
                'desktop' => $desktop,
                'source' => $mobile['source'] ?? 'shared_key',
                'fetched_at' => $mobile['fetched_at'] ?? now()->toIso8601String(),
            ];
            Cache::put($cacheKey, $data, 60 * 6);
        }

        $audit->refresh();
        $kpis = $audit->audit_kpis ?? [];
        if (!isset($kpis['google'])) $kpis['google'] = [];
        $kpis['google']['pagespeed'] = $data;
        $audit->audit_kpis = $kpis;
        $audit->psi_ready_at = now();
        $audit->save();

        $page = $audit->pages()->first();
        if ($page && !empty($data['mobile']['kpis'])) {
            $page->lighthouse_mobile = $data['mobile']['kpis'] ?? null;
            $page->lighthouse_desktop = $data['desktop']['kpis'] ?? null;
            $page->save();
        }
    }
}
