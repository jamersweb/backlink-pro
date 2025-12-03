<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ProxyHealthCheckService;

class CheckProxyHealth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'proxy:check-health {--all : Check all active proxies} {--unhealthy : Check only unhealthy proxies}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check health of proxies and update their status';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting proxy health check...');

        if ($this->option('all')) {
            $results = ProxyHealthCheckService::checkAllActiveProxies();
            $this->info("Checked: {$results['checked']}");
            $this->info("Healthy: {$results['healthy']}");
            $this->warn("Unhealthy: {$results['unhealthy']}");
        } elseif ($this->option('unhealthy')) {
            $results = ProxyHealthCheckService::checkUnhealthyProxies();
            $this->info("Checked: {$results['checked']}");
            $this->info("Recovered: {$results['recovered']}");
            $this->warn("Still Unhealthy: {$results['still_unhealthy']}");
        } else {
            // Default: check unhealthy proxies
            $results = ProxyHealthCheckService::checkUnhealthyProxies();
            $this->info("Checked: {$results['checked']}");
            $this->info("Recovered: {$results['recovered']}");
            $this->warn("Still Unhealthy: {$results['still_unhealthy']}");
        }

        $this->info('Proxy health check completed!');
        return 0;
    }
}
