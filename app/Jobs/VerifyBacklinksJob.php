<?php

namespace App\Jobs;

use App\Models\BacklinkCampaign;
use App\Models\BacklinkVerification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VerifyBacklinksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;
    public $tries = 2;

    public function __construct(
        public int $campaignId
    ) {}

    public function handle(): void
    {
        $campaign = BacklinkCampaign::find($this->campaignId);
        if (!$campaign) {
            return;
        }

        $verifications = $campaign->verifications()->where('status', BacklinkVerification::STATUS_ACTIVE)->get();

        foreach ($verifications as $verification) {
            try {
                $this->verifyLink($verification);
            } catch (\Exception $e) {
                Log::warning("Failed to verify link", [
                    'verification_id' => $verification->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    protected function verifyLink(BacklinkVerification $verification): void
    {
        $response = Http::timeout(10)->get($verification->found_on_url);

        if (!$response->successful()) {
            $verification->update([
                'status' => BacklinkVerification::STATUS_LOST,
            ]);
            return;
        }

        $html = $response->body();
        $targetDomain = parse_url($verification->target_url, PHP_URL_HOST);

        // Check if link exists in HTML
        $linkPattern = '/<a[^>]+href=["\']([^"\']*' . preg_quote($targetDomain, '/') . '[^"\']*)["\'][^>]*>/i';
        
        if (preg_match($linkPattern, $html)) {
            $verification->update([
                'status' => BacklinkVerification::STATUS_ACTIVE,
                'last_seen_at' => now(),
            ]);
        } else {
            $verification->update([
                'status' => BacklinkVerification::STATUS_LOST,
            ]);
        }
    }
}
