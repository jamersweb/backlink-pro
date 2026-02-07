<?php

namespace App\Jobs;

use App\Models\Audit;
use App\Services\FixAutomation\PatchBuilder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateFixCandidatesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;
    public $tries = 2;

    public function __construct(
        public int $auditId,
        public ?int $repoId = null
    ) {}

    public function handle(): void
    {
        $audit = Audit::find($this->auditId);
        if (!$audit) {
            return;
        }

        $repo = $this->repoId ? \App\Models\Repo::find($this->repoId) : null;

        try {
            $patchBuilder = new PatchBuilder();
            $candidates = $patchBuilder->generateCandidates($audit, $repo);

            Log::info("Generated fix candidates", [
                'audit_id' => $audit->id,
                'candidates_count' => count($candidates),
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to generate fix candidates", [
                'audit_id' => $audit->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
