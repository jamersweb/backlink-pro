<?php

namespace App\Jobs;

use App\Models\Audit;
use App\Models\AuditPage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class RunLighthouseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 180; // 3 minutes per Lighthouse run
    public $tries = 1;
    public $queue = 'lighthouse';

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $auditId,
        public int $pageId,
        public string $preset // 'mobile' or 'desktop'
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $audit = Audit::find($this->auditId);
        $page = AuditPage::find($this->pageId);
        
        if (!$audit || !$page) {
            Log::warning("Audit or page not found", [
                'audit_id' => $this->auditId,
                'page_id' => $this->pageId,
            ]);
            return;
        }

        try {
            // Get Node.js path (try common locations)
            $nodePath = $this->findNodePath();
            if (!$nodePath) {
                throw new \Exception('Node.js not found. Please install Node.js to run Lighthouse audits.');
            }

            // Get script path
            $scriptPath = base_path('scripts/runLighthouse.js');
            if (!file_exists($scriptPath)) {
                throw new \Exception("Lighthouse script not found: {$scriptPath}");
            }

            // Run Lighthouse script
            $process = new Process([
                $nodePath,
                $scriptPath,
                $page->url,
                $this->preset,
            ], base_path(), null, null, 120); // 120 second timeout

            $process->run();

            if (!$process->isSuccessful()) {
                $error = $process->getErrorOutput() ?: $process->getOutput() ?: 'Unknown error';
                Log::warning("Lighthouse failed for page {$this->pageId}: {$error}");
                
                // Try to parse error as JSON
                $errorData = json_decode($error, true);
                if ($errorData && isset($errorData['error'])) {
                    $this->storeError($page, $errorData['error']);
                } else {
                    $this->storeError($page, $error);
                }
                return;
            }

            // Parse JSON output
            $output = trim($process->getOutput());
            
            // Check if output is empty
            if (empty($output)) {
                throw new \Exception("Lighthouse script returned empty output");
            }
            
            $result = json_decode($output, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                // Try to extract error from stderr
                $errorOutput = $process->getErrorOutput();
                throw new \Exception("Invalid JSON from Lighthouse: " . json_last_error_msg() . ($errorOutput ? " | Error: {$errorOutput}" : ""));
            }

            // Check for error in result
            if (isset($result['error'])) {
                Log::warning("Lighthouse error: {$result['error']}");
                $this->storeError($page, $result['error']);
                return;
            }

            // Store results
            $this->storeResults($page, $result);

            // Record usage: lighthouse_run
            if ($audit->organization_id) {
                \App\Services\Billing\UsageRecorder::record(
                    $audit->organization_id,
                    \App\Models\UsageEvent::TYPE_LIGHTHOUSE_RUN,
                    1,
                    $audit->id,
                    ['page_url' => $page->url, 'preset' => $this->preset]
                );
            }

        } catch (\Exception $e) {
            Log::error("RunLighthouseJob failed: {$e->getMessage()}", [
                'audit_id' => $this->auditId,
                'page_id' => $this->pageId,
                'preset' => $this->preset,
                'exception' => $e,
            ]);

            // Store error but don't fail the audit
            if (isset($page)) {
                $this->storeError($page, $e->getMessage());
            }
        }
    }

    /**
     * Store Lighthouse results in page record
     */
    protected function storeResults(AuditPage $page, array $result): void
    {
        $field = $this->preset === 'mobile' ? 'lighthouse_mobile' : 'lighthouse_desktop';
        
        $page->$field = $result;
        
        // Update performance_metrics
        $metrics = $page->performance_metrics ?? [];
        $metrics[$this->preset] = [
            'fcp' => $result['fcp'] ?? null,
            'lcp' => $result['lcp'] ?? null,
            'cls' => $result['cls'] ?? null,
            'tbt' => $result['tbt'] ?? null,
            'tti' => $result['tti'] ?? null,
            'si' => $result['si'] ?? null,
            'score' => $result['score'] ?? null,
            'accessibility_score' => $result['accessibility_score'] ?? null,
            'tap_targets_ok' => $result['tap_targets_ok'] ?? null,
            'font_size_ok' => $result['font_size_ok'] ?? null,
            'opportunities' => $result['opportunities'] ?? [],
        ];
        
        $page->performance_metrics = $metrics;
        $page->save();
    }

    /**
     * Store error in page record
     */
    protected function storeError(AuditPage $page, string $error): void
    {
        $field = $this->preset === 'mobile' ? 'lighthouse_mobile' : 'lighthouse_desktop';
        
        $page->$field = [
            'error' => $error,
            'available' => false,
        ];
        
        $page->save();
    }

    /**
     * Find Node.js executable path
     */
    protected function findNodePath(): ?string
    {
        $paths = [
            'node',
            '/usr/bin/node',
            '/usr/local/bin/node',
            'C:\\Program Files\\nodejs\\node.exe',
        ];

        foreach ($paths as $path) {
            $process = new Process([$path, '--version']);
            $process->run();
            if ($process->isSuccessful()) {
                return $path;
            }
        }

        return null;
    }
}
