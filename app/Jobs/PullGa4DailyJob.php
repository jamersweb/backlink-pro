<?php

namespace App\Jobs;

use App\Models\Ga4Property;
use App\Models\Ga4DailyMetric;
use App\Models\Ga4PageMetric;
use App\Models\OauthConnection;
use App\Services\SEO\GoogleClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PullGa4DailyJob implements ShouldQueue
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

        $properties = Ga4Property::where('organization_id', $this->organizationId)->get();
        if ($properties->isEmpty()) {
            return;
        }

        $client = new GoogleClient($connection);

        foreach ($properties as $property) {
            try {
                // Pull daily metrics
                $this->pullDailyMetrics($client, $property);
                
                // Pull top pages
                $this->pullTopPages($client, $property);

            } catch (\Exception $e) {
                Log::error("GA4 pull failed for property {$property->property_id}", [
                    'organization_id' => $this->organizationId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    protected function pullDailyMetrics(GoogleClient $client, Ga4Property $property): void
    {
        $data = $client->fetchGa4DailyMetrics($property->property_id, $this->date, $this->date);
        
        if (empty($data)) {
            return;
        }

        $row = $data[0] ?? null;
        if (!$row) {
            return;
        }

        $metrics = $row['metricValues'] ?? [];
        
        Ga4DailyMetric::updateOrCreate(
            [
                'organization_id' => $this->organizationId,
                'property_id' => $property->property_id,
                'date' => $this->date,
            ],
            [
                'sessions' => $metrics[0]['value'] ?? 0,
                'users' => $metrics[1]['value'] ?? 0,
                'new_users' => $metrics[2]['value'] ?? 0,
                'engagement_rate' => isset($metrics[3]['value']) ? round($metrics[3]['value'], 4) : null,
                'avg_engagement_time_sec' => isset($metrics[4]['value']) ? (int) round($metrics[4]['value']) : null,
                'page_views' => isset($metrics[5]['value']) ? (int) $metrics[5]['value'] : null,
                'conversions' => isset($metrics[6]['value']) ? (int) $metrics[6]['value'] : null,
                'revenue' => isset($metrics[7]['value']) ? round($metrics[7]['value'], 2) : null,
            ]
        );
    }

    protected function pullTopPages(GoogleClient $client, Ga4Property $property): void
    {
        $data = $client->fetchGa4TopPages($property->property_id, $this->date, $this->date, 100);
        
        foreach ($data as $row) {
            $dimensions = $row['dimensionValues'] ?? [];
            $metrics = $row['metricValues'] ?? [];

            Ga4PageMetric::create([
                'organization_id' => $this->organizationId,
                'property_id' => $property->property_id,
                'date' => $this->date,
                'page_path' => $dimensions[1]['value'] ?? '',
                'page_title' => $dimensions[2]['value'] ?? null,
                'views' => $metrics[0]['value'] ?? 0,
                'active_users' => $metrics[1]['value'] ?? 0,
                'conversions' => isset($metrics[2]['value']) ? (int) $metrics[2]['value'] : null,
            ]);
        }
    }
}
