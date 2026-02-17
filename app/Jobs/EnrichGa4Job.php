<?php

namespace App\Jobs;

use App\Models\Audit;
use App\Models\ConnectedAccount;
use App\Services\Google\Ga4Service;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Enrichment: GA4 metrics. Timeout 25s. Marks ga4_ready_at when done (success or fail).
 */
class EnrichGa4Job implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 25;
    public $tries = 1;

    public function __construct(public int $auditId) {}

    public function handle(): void
    {
        $audit = Audit::find($this->auditId);
        if (!$audit || !$audit->user_id) {
            $this->markReady($audit);
            return;
        }

        $account = ConnectedAccount::where('user_id', $audit->user_id)
            ->where('provider', 'google')
            ->where('service', 'seo')
            ->where('status', 'active')
            ->first();

        if (!$account) {
            $this->mergeKpis($audit, ['ga4' => ['connected' => false, 'message' => 'Google Analytics not connected']]);
            $this->markReady($audit);
            return;
        }

        try {
            $ga4 = new Ga4Service($account);
            $properties = $ga4->listProperties();
            if (empty($properties)) {
                $this->mergeKpis($audit, ['ga4' => ['connected' => true, 'message' => 'No GA4 properties found', 'data' => null]]);
                $this->markReady($audit);
                return;
            }

            $propertyId = $properties[0]['propertyName'];
            $endDate = new \DateTime('now');
            $startDate = (clone $endDate)->modify('-30 days');
            $dailyMetrics = $ga4->runDailyReport($propertyId, $startDate, $endDate);
            $landingPages = [];
            try {
                $landingPages = $ga4->runLandingPagesReport($propertyId, $startDate, $endDate, 20);
            } catch (\Exception $e) {
                Log::warning('GA4 landing pages failed', ['error' => $e->getMessage()]);
            }

            $totalSessions = array_sum(array_column($dailyMetrics, 'sessions'));
            $totalUsers = array_sum(array_column($dailyMetrics, 'total_users'));
            $kpis = [
                'ga4' => [
                    'connected' => true,
                    'property' => isset($properties[0]['displayName']) ? $properties[0]['displayName'] : $propertyId,
                    'period' => $startDate->format('Y-m-d') . ' to ' . $endDate->format('Y-m-d'),
                    'summary' => [
                        'total_sessions' => $totalSessions,
                        'total_users' => $totalUsers,
                        'avg_engagement_rate' => !empty($dailyMetrics) ? round(array_sum(array_column($dailyMetrics, 'engagement_rate')) / count($dailyMetrics) * 100, 1) : 0,
                    ],
                    'daily' => $dailyMetrics,
                    'top_pages' => $landingPages,
                ],
            ];
            $this->mergeKpis($audit, $kpis);
        } catch (\Throwable $e) {
            Log::warning('EnrichGa4Job failed', ['audit_id' => $audit->id, 'error' => $e->getMessage()]);
            $this->mergeKpis($audit, ['ga4' => ['connected' => true, 'error' => $e->getMessage(), 'data' => null]]);
        }
        $this->markReady($audit);
    }

    protected function mergeKpis($audit, array $newKpis): void
    {
        if (!$audit) {
            return;
        }
        $audit->refresh();
        $kpis = array_merge($audit->audit_kpis ?? [], $newKpis);
        $audit->audit_kpis = $kpis;
        $audit->save();
    }

    protected function markReady($audit): void
    {
        if (!$audit) {
            return;
        }
        $audit->ga4_ready_at = now();
        $audit->save();
    }
}
