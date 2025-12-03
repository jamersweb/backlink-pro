<?php

namespace App\Jobs;

use App\Models\Backlink;
use App\Services\BacklinkVerificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class VerifyBacklinkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $backlink;

    /**
     * Create a new job instance.
     */
    public function __construct(Backlink $backlink)
    {
        $this->backlink = $backlink;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Reload backlink to ensure we have latest data
            $backlink = Backlink::findOrFail($this->backlink->id);
            
            // Skip if already verified
            if ($backlink->status === Backlink::STATUS_VERIFIED) {
                Log::info('Backlink already verified', ['backlink_id' => $backlink->id]);
                return;
            }

            // Verify the backlink
            BacklinkVerificationService::verify($backlink);

        } catch (\Exception $e) {
            Log::error('Error in VerifyBacklinkJob', [
                'backlink_id' => $this->backlink->id,
                'error' => $e->getMessage(),
            ]);
            
            // Mark as failed if verification job itself fails
            $backlink = Backlink::find($this->backlink->id);
            if ($backlink) {
                $backlink->update([
                    'status' => Backlink::STATUS_FAILED,
                    'error_message' => 'Verification job failed: ' . $e->getMessage(),
                ]);
            }
        }
    }
}
