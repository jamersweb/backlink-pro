<?php

namespace App\Console\Commands;

use App\Models\Domain;
use App\Jobs\Insights\GenerateDomainInsightsJob;
use Illuminate\Console\Command;

class InsightsGenerate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'insights:generate {--domain-id= : Generate for specific domain}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate insights for all domains or a specific domain';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $domainId = $this->option('domain-id');

        if ($domainId) {
            $domain = Domain::find($domainId);
            if (!$domain) {
                $this->error("Domain not found: {$domainId}");
                return 1;
            }

            $this->info("Generating insights for domain: {$domain->name}");
            GenerateDomainInsightsJob::dispatch($domain->id);
            $this->info('Job dispatched');
            return 0;
        }

        // Generate for all domains
        $domains = Domain::where('status', Domain::STATUS_ACTIVE)->get();
        $this->info("Generating insights for {$domains->count()} domains...");

        $chunkSize = 50;
        $chunks = $domains->chunk($chunkSize);

        foreach ($chunks as $chunk) {
            foreach ($chunk as $domain) {
                GenerateDomainInsightsJob::dispatch($domain->id);
            }
            $this->info("Dispatched jobs for " . $chunk->count() . " domains");
            
            // Throttle to avoid overwhelming the queue
            if ($chunks->count() > 1) {
                sleep(1);
            }
        }

        $this->info('All insight generation jobs dispatched');
        return 0;
    }
}
