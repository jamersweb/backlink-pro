<?php

namespace App\Services\Google;

use App\Models\Domain;
use App\Models\DomainGoogleIntegration;
use App\Models\GscQueryPageMetric;
use Illuminate\Support\Facades\Log;

class GscQueryPageSyncService
{
    /**
     * Sync GSC query+page metrics for last 28 days and store in gsc_query_page_metrics.
     */
    public function syncForDomain(Domain $domain): array
    {
        $integration = $domain->googleIntegration;
        if (!$integration || !$integration->gsc_property || !$integration->connectedAccount?->isActive()) {
            return ['synced' => 0, 'message' => 'No GSC integration or property configured.'];
        }

        try {
            $service = new SearchConsoleService($integration->connectedAccount);
            $endDate = now();
            $startDate = now()->subDays(28);
            $siteUrl = $integration->gsc_property;

            $rows = $service->fetchQueryPageMetrics($siteUrl, $startDate, $endDate);

            $dateRange = $startDate->format('Y-m-d') . ' to ' . $endDate->format('Y-m-d');
            GscQueryPageMetric::where('domain_id', $domain->id)
                ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
                ->delete();

            $inserted = 0;
            foreach ($rows as $row) {
                GscQueryPageMetric::create([
                    'domain_id' => $domain->id,
                    'site_url' => $siteUrl,
                    'date' => $endDate->toDateString(),
                    'query' => $row['query'],
                    'page_url' => $row['page'],
                    'clicks' => $row['clicks'],
                    'impressions' => $row['impressions'],
                    'ctr' => $row['ctr'],
                    'position' => $row['position'],
                ]);
                $inserted++;
            }

            return ['synced' => $inserted, 'message' => "Synced {$inserted} query+page rows for {$dateRange}."];
        } catch (\Exception $e) {
            Log::error('GscQueryPageSyncService failed', ['domain_id' => $domain->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }
}
