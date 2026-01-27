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

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 120;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public array $backoff = [30, 60, 120];

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

            Log::info('Verifying backlink', ['backlink_id' => $backlink->id]);

            // Verify the backlink
            BacklinkVerificationService::verify($backlink);

            Log::info('Backlink verification completed', ['backlink_id' => $backlink->id]);

        } catch (\Exception $e) {
            Log::error('Error in VerifyBacklinkJob', [
                'backlink_id' => $this->backlink->id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);
            
            // Only mark as failed on final attempt
            if ($this->attempts() >= $this->tries) {
                $backlink = Backlink::find($this->backlink->id);
                if ($backlink) {
                    $backlink->update([
                        'status' => Backlink::STATUS_FAILED,
                        'error_message' => 'Verification job failed: ' . $e->getMessage(),
                    ]);
                }
            }

            throw $e; // Re-throw to trigger retry
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('VerifyBacklinkJob permanently failed', [
            'backlink_id' => $this->backlink->id,
            'error' => $exception->getMessage(),
        ]);

        // Mark as failed
        $backlink = Backlink::find($this->backlink->id);
        if ($backlink) {
            $backlink->update([
                'status' => Backlink::STATUS_FAILED,
                'error_message' => 'Verification permanently failed: ' . $exception->getMessage(),
            ]);
        }
    }
}
