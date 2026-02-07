<?php

namespace App\Jobs;

use App\Services\ManagedServices\SlaChecker;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckSlaBreachesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $queue = 'managed_services';

    public function handle(SlaChecker $checker): void
    {
        $checker->checkBreaches();
    }
}
