<?php

namespace App\Console\Commands;

use App\Models\DomainGoogleIntegration;
use App\Jobs\Integrations\SyncGscDomainJob;
use App\Jobs\Integrations\SyncGa4DomainJob;
use Illuminate\Console\Command;

class IntegrationsSyncGoogle extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'integrations:sync-google';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Google Search Console and GA4 data for all connected domains';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $integrations = DomainGoogleIntegration::where('status', DomainGoogleIntegration::STATUS_CONNECTED)
            ->with(['connectedAccount', 'domain'])
            ->get();

        $this->info("Found {$integrations->count()} connected integrations");

        // Process in chunks to avoid quota issues
        $integrations->chunk(50)->each(function ($chunk) {
            foreach ($chunk as $integration) {
                if (!$integration->connectedAccount || !$integration->connectedAccount->isActive()) {
                    continue;
                }

                if ($integration->gsc_property) {
                    SyncGscDomainJob::dispatch($integration->domain_id);
                    $this->info("Dispatched GSC sync for domain {$integration->domain_id}");
                }

                if ($integration->ga4_property_id) {
                    SyncGa4DomainJob::dispatch($integration->domain_id);
                    $this->info("Dispatched GA4 sync for domain {$integration->domain_id}");
                }
            }
        });

        $this->info('Sync jobs dispatched successfully');
    }
}
