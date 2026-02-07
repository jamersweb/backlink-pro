<?php

namespace App\Jobs;

use App\Models\AuditFixCandidate;
use App\Services\FixAutomation\PatchBuilder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GeneratePatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;
    public $tries = 2;

    public function __construct(
        public int $fixCandidateId
    ) {}

    public function handle(): void
    {
        $candidate = AuditFixCandidate::find($this->fixCandidateId);
        if (!$candidate) {
            return;
        }

        try {
            $patchBuilder = new PatchBuilder();
            $repo = $candidate->audit->organization->repos()->where('is_active', true)->first();
            $patch = $patchBuilder->generatePatch($candidate, $repo);

            if ($patch) {
                Log::info("Generated patch for candidate", [
                    'candidate_id' => $candidate->id,
                    'patch_id' => $patch->id,
                ]);
            } else {
                Log::info("Patch generation skipped (manual steps required)", [
                    'candidate_id' => $candidate->id,
                ]);
            }

        } catch (\Exception $e) {
            Log::error("Failed to generate patch", [
                'candidate_id' => $candidate->id,
                'error' => $e->getMessage(),
            ]);

            $candidate->update([
                'status' => AuditFixCandidate::STATUS_DRAFT,
            ]);

            throw $e;
        }
    }
}
