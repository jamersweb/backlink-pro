<?php

namespace App\Jobs;

use App\Models\Client;
use App\Services\ManagedServices\DeliverableGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateMonthlyDeliverablesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $queue = 'managed_services';

    public function __construct(
        public int $clientId,
        public string $month
    ) {}

    public function handle(DeliverableGenerator $generator): void
    {
        $client = Client::find($this->clientId);
        if (!$client) {
            return;
        }

        $generator->generateMonthlyDeliverables($client, $this->month);
    }
}
