<?php

namespace App\Jobs;

use App\Models\GscSite;
use App\Models\GscDailyMetric;
use App\Models\GscQueryMetric;
use App\Models\GscPageMetric;
use App\Models\OauthConnection;
use App\Services\SEO\GoogleClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PullGscDailyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;
    public $tries = 2;
    public $queue = 'integrations';

    public function __construct(
        public int $organizationId,
        public ?string $date = null
    ) {
        $this->date = $date ?? Carbon::yesterday()->toDateString();
    }

    public function handle(): void
    {
        $connection = OauthConnection::where('organization_id', $this->organizationId)
            ->where('provider', 'google')
            ->where('status', 'active')
            ->first();

        if (!$connection) {
            Log::warning("No active Google connection for org {$this->organizationId}");
            return;
        }

        $sites = GscSite::where('organization_id', $this->organizationId)->get();
        if ($sites->isEmpty()) {
            return;
        }

        $client = new GoogleClient($connection);

        foreach ($sites as $site) {
            try {
                // Pull daily totals
                $this->pullDailyMetrics($client, $site);
                
                // Pull top queries (limit to top 1000 per day)
                $this->pullTopQueries($client, $site);
                
                // Pull top pages (limit to top 1000 per day)
                $this->pullTopPages($client, $site);

            } catch (\Exception $e) {
                Log::error("GSC pull failed for site {$site->site_url}", [
                    'organization_id' => $this->organizationId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    protected function pullDailyMetrics(GoogleClient $client, GscSite $site): void
    {
        $data = $client->fetchGscDailyMetrics($site->site_url, $this->date, $this->date);
        
        if (empty($data)) {
            return;
        }

        $row = $data[0] ?? null;
        if (!$row) {
            return;
        }

        GscDailyMetric::updateOrCreate(
            [
                'organization_id' => $this->organizationId,
                'site_url' => $site->site_url,
                'date' => $this->date,
            ],
            [
                'clicks' => $row['clicks'] ?? 0,
                'impressions' => $row['impressions'] ?? 0,
                'ctr' => isset($row['ctr']) ? round($row['ctr'], 4) : null,
                'position' => isset($row['position']) ? round($row['position'], 2) : null,
            ]
        );
    }

    protected function pullTopQueries(GoogleClient $client, GscSite $site): void
    {
        $data = $client->fetchGscTopQueries($site->site_url, $this->date, $this->date, 1000);
        
        foreach ($data as $row) {
            GscQueryMetric::create([
                'organization_id' => $this->organizationId,
                'site_url' => $site->site_url,
                'date' => $this->date,
                'query' => $row['keys'][0] ?? '',
                'clicks' => $row['clicks'] ?? 0,
                'impressions' => $row['impressions'] ?? 0,
                'ctr' => isset($row['ctr']) ? round($row['ctr'], 4) : null,
                'position' => isset($row['position']) ? round($row['position'], 2) : null,
            ]);
        }
    }

    protected function pullTopPages(GoogleClient $client, GscSite $site): void
    {
        $data = $client->fetchGscTopPages($site->site_url, $this->date, $this->date, 1000);
        
        foreach ($data as $row) {
            GscPageMetric::create([
                'organization_id' => $this->organizationId,
                'site_url' => $site->site_url,
                'date' => $this->date,
                'page_url' => $row['keys'][0] ?? '',
                'clicks' => $row['clicks'] ?? 0,
                'impressions' => $row['impressions'] ?? 0,
                'ctr' => isset($row['ctr']) ? round($row['ctr'], 4) : null,
                'position' => isset($row['position']) ? round($row['position'], 2) : null,
            ]);
        }
    }
}
