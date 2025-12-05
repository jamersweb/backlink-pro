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

        // Check if API token is configured
        $apiToken = config('app.api_token');
        if (empty($apiToken)) {
            $this->warn('LARAVEL_API_TOKEN is not set. Worker may fail to authenticate.');
        }

        // Use the new single-pass mode to avoid long-running processes from the scheduler
        $process = new Process(
            [$pythonBinary, 'worker.py', '--once', '--limit', (string) $limit],
            $projectRoot,
            [
                'LARAVEL_API_URL' => env('LARAVEL_API_URL', config('app.url')),
                'LARAVEL_API_TOKEN' => $apiToken,
                // Optional: allow overriding poll interval even though we exit after one pass
                'POLL_INTERVAL' => env('POLL_INTERVAL', '10'),
                'WORKER_ID' => 'scheduler-worker',
            ],
            null,
            300 // 5 minutes timeout to prevent runaway processes
        );

        $this->info("Starting automation worker (single pass, limit: {$limit})...");

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

