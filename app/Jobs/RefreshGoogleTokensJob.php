<?php

namespace App\Jobs;

use App\Models\OauthConnection;
use App\Services\SEO\GoogleClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RefreshGoogleTokensJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60;
    public $tries = 2;
    public $queue = 'integrations';

    public function handle(): void
    {
        // Find connections that expire within 1 hour
        $connections = OauthConnection::where('provider', 'google')
            ->where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now()->addHour())
            ->get();

        foreach ($connections as $connection) {
            try {
                $client = new GoogleClient($connection);
                // Token refresh happens automatically in GoogleClient
                $client->getAccessToken();
                
                Log::info("Token refreshed", ['connection_id' => $connection->id]);
            } catch (\Exception $e) {
                Log::error("Token refresh failed", [
                    'connection_id' => $connection->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
