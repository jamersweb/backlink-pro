<?php

namespace App\Jobs;

use App\Models\AuditPatch;
use App\Services\FixAutomation\GitHubPullRequestService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class OpenPullRequestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;
    public $tries = 2;

    public function __construct(
        public int $patchId
    ) {}

    public function handle(): void
    {
        $patch = AuditPatch::find($this->patchId);
        if (!$patch || !$patch->repo) {
            return;
        }

        try {
            $prService = new GitHubPullRequestService();
            $prUrl = $prService->createPullRequest($patch);

            $patch->update([
                'status' => AuditPatch::STATUS_PR_OPENED,
                'pr_url' => $prUrl,
            ]);

            Log::info("Opened pull request", [
                'patch_id' => $patch->id,
                'pr_url' => $prUrl,
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to open pull request", [
                'patch_id' => $patch->id,
                'error' => $e->getMessage(),
            ]);

            $patch->update([
                'status' => AuditPatch::STATUS_FAILED,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
