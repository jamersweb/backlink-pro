<?php

namespace App\Jobs;

use App\Models\Audit;
use App\Models\ConnectedAccount;
use App\Services\Google\SearchConsoleService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Enrichment: Google Search Console. Timeout 25s. Marks gsc_ready_at when done.
 */
class EnrichGscJob implements ShouldQueue
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
            $this->mergeKpis($audit, ['gsc' => ['connected' => false, 'message' => 'Search Console not connected']]);
            $this->markReady($audit);
            return;
        }

        try {
            $gsc = new SearchConsoleService($account);
            $sites = $gsc->listSites();
            $auditHost = parse_url($audit->normalized_url, PHP_URL_HOST);
            $siteUrl = null;
            foreach ($sites as $site) {
                $siteHost = parse_url($site['siteUrl'], PHP_URL_HOST) ?? str_replace('sc-domain:', '', $site['siteUrl']);
                if ($siteHost === $auditHost || str_contains($site['siteUrl'], $auditHost)) {
                    $siteUrl = $site['siteUrl'];
                    break;
                }
            }
            if (!$siteUrl && !empty($sites)) $siteUrl = $sites[0]['siteUrl'];
            if (!$siteUrl) {
                $this->mergeKpis($audit, ['gsc' => ['connected' => true, 'message' => 'No matching Search Console property', 'data' => null]]);
                $this->markReady($audit);
                return;
            }

            $endDate = new \DateTime('now');
            $startDate = (clone $endDate)->modify('-30 days');
            $dailyMetrics = $gsc->fetchDailyMetrics($siteUrl, $startDate, $endDate);
            $topQueries = $gsc->fetchTopQueries($siteUrl, $startDate, $endDate, 20);
            $topPages = $gsc->fetchTopPages($siteUrl, $startDate, $endDate, 20);

            $totalClicks = array_sum(array_column($dailyMetrics, 'clicks'));
            $totalImpressions = array_sum(array_column($dailyMetrics, 'impressions'));
            $avgPosition = !empty($dailyMetrics) ? round(array_sum(array_column($dailyMetrics, 'position')) / count($dailyMetrics), 1) : 0;
            $avgCtr = $totalImpressions > 0 ? round(($totalClicks / $totalImpressions) * 100, 2) : 0;

            $this->mergeKpis($audit, [
                'gsc' => [
                    'connected' => true,
                    'site_url' => $siteUrl,
                    'period' => $startDate->format('Y-m-d') . ' to ' . $endDate->format('Y-m-d'),
                    'summary' => [
                        'total_clicks' => $totalClicks,
                        'total_impressions' => $totalImpressions,
                        'avg_ctr' => $avgCtr,
                        'avg_position' => $avgPosition,
                    ],
                    'daily' => $dailyMetrics,
                    'top_queries' => $topQueries,
                    'top_pages' => $topPages,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::warning('EnrichGscJob failed', ['audit_id' => $audit->id, 'error' => $e->getMessage()]);
            $this->mergeKpis($audit, ['gsc' => ['connected' => true, 'error' => $e->getMessage(), 'data' => null]]);
        }
        $this->markReady($audit);
    }

    protected function mergeKpis(?Audit $audit, array $newKpis): void
    {
        if (!$audit) return;
        $audit->refresh();
        $kpis = array_merge($audit->audit_kpis ?? [], $newKpis);
        $audit->audit_kpis = $kpis;
        $audit->save();
    }

    protected function markReady(?Audit $audit): void
    {
        if (!$audit) return;
        $audit->gsc_ready_at = now();
        $audit->save();
    }
}
