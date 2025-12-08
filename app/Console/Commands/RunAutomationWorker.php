<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class RunAutomationWorker extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'automation:run-worker {--limit=5 : Number of pending tasks to pull}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kick the Python automation worker once to auto-run pending tasks.';

    public function handle(): int
    {
        $pythonBinary = env('PYTHON_BINARY', 'python');
        $projectRoot = base_path('python');
        $limit = (int) $this->option('limit');

        // Check if Python directory exists
        if (!is_dir($projectRoot)) {
            $this->error("Python directory not found: {$projectRoot}");
            return Command::FAILURE;
        }

        // Check if worker.py exists
        $workerFile = $projectRoot . DIRECTORY_SEPARATOR . 'worker.py';
        if (!file_exists($workerFile)) {
            $this->error("Worker file not found: {$workerFile}");
            return Command::FAILURE;
        }

        // Check if API token is configured (check multiple possible env vars)
        // Priority: config (Laravel's source of truth) > getenv() (Docker env) > env() (.env file)
        // Always use Laravel's config as the source of truth to ensure tokens match
        $apiToken = config('app.api_token')
            ?: getenv('APP_API_TOKEN')
            ?: getenv('PYTHON_API_TOKEN')
            ?: getenv('LARAVEL_API_TOKEN')
            ?: env('LARAVEL_API_TOKEN')
            ?: env('PYTHON_API_TOKEN')
            ?: env('APP_API_TOKEN')
            ?: 'your-secure-api-token-change-in-production'; // Final fallback

        // If token is the old short default, use the correct one
        // This handles cases where .env has the old default
        if ($apiToken === 'your-api-token-here') {
            $apiToken = 'your-secure-api-token-change-in-production';
        }

        if (empty($apiToken)) {
            $this->warn('API token is not set. Worker may fail to authenticate.');
        } else {
            $this->info("Using API token: " . substr($apiToken, 0, 10) . "... (length: " . strlen($apiToken) . ")");
        }

        // Use the new single-pass mode to avoid long-running processes from the scheduler
        // Get LARAVEL_API_URL from environment (Docker) or fallback to config
        // Priority: getenv() (Docker env) > env() (.env file) > config > default
        $apiUrl = getenv('LARAVEL_API_URL') ?: env('LARAVEL_API_URL') ?: config('app.url', 'http://nginx');

        // Ensure we use nginx, not app:8000
        if (strpos($apiUrl, 'app:8000') !== false || strpos($apiUrl, 'localhost:8000') !== false) {
            $apiUrl = 'http://nginx';
        }

        // Pass environment variables to Python process
        // Use LARAVEL_API_TOKEN (not APP_API_TOKEN) since Python worker expects that name
        $process = new Process(
            [$pythonBinary, 'worker.py', '--once', '--limit', (string) $limit],
            $projectRoot,
            [
                'LARAVEL_API_URL' => $apiUrl,
                'LARAVEL_API_TOKEN' => $apiToken, // Python expects LARAVEL_API_TOKEN
                'APP_API_TOKEN' => $apiToken, // Also set APP_API_TOKEN for consistency
                // Optional: allow overriding poll interval even though we exit after one pass
                'POLL_INTERVAL' => env('POLL_INTERVAL', '10'),
                'WORKER_ID' => 'scheduler-worker',
            ],
            null,
            300 // 5 minutes timeout to prevent runaway processes
        );

        $this->info("Starting automation worker (single pass, limit: {$limit})...");
        $this->info("Using API URL: {$apiUrl}");

        try {
            $process->run(function ($type, $buffer) {
                if ($type === Process::ERR) {
                    $this->error($buffer);
                } else {
                    $this->line($buffer);
                }
            });

            if (!$process->isSuccessful()) {
                $errorOutput = $process->getErrorOutput();
                $this->error('Automation worker failed: ' . ($errorOutput ?: $process->getOutput()));
                return Command::FAILURE;
            }

            $this->info('Automation worker completed successfully.');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Exception running automation worker: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

