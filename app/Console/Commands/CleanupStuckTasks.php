<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AutomationTask;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CleanupStuckTasks extends Command
{
    protected $signature = 'tasks:cleanup-stuck {--timeout=30 : Minutes after which a running task is considered stuck}';
    protected $description = 'Clean up tasks that have been running too long (stuck tasks)';

    public function handle()
    {
        $timeoutMinutes = (int) $this->option('timeout');
        $timeoutTime = now()->subMinutes($timeoutMinutes);

        $this->info("Cleaning up tasks stuck in 'running' status for more than {$timeoutMinutes} minutes...");
        $this->newLine();

        // Find stuck tasks
        $stuckTasks = AutomationTask::where('status', AutomationTask::STATUS_RUNNING)
            ->where(function($query) use ($timeoutTime) {
                // Either locked_at is older than timeout, or started_at is older than timeout
                $query->where(function($q) use ($timeoutTime) {
                    $q->where('locked_at', '<', $timeoutTime)
                      ->orWhereNull('locked_at');
                })
                ->where(function($q) use ($timeoutTime) {
                    $q->where('started_at', '<', $timeoutTime)
                      ->orWhereNull('started_at');
                });
            })
            ->get();

        $this->info("Found {$stuckTasks->count()} stuck task(s)");
        $this->newLine();

        if ($stuckTasks->isEmpty()) {
            $this->info('No stuck tasks found.');
            return 0;
        }

        $cleaned = 0;
        foreach ($stuckTasks as $task) {
            $lockedAt = $task->locked_at ? $task->locked_at->format('Y-m-d H:i:s') : 'N/A';
            $startedAt = $task->started_at ? $task->started_at->format('Y-m-d H:i:s') : 'N/A';
            $elapsed = $task->started_at ? $task->started_at->diffForHumans() : 'N/A';
            $elapsedMinutes = $task->started_at ? round($task->started_at->diffInMinutes(now())) : 0;

            $this->line("Task #{$task->id}:");
            $this->line("  Type: {$task->type}");
            $this->line("  Locked at: {$lockedAt}");
            $this->line("  Started at: {$startedAt} ({$elapsed})");
            $this->line("  Worker: {$task->locked_by}");

            // Analyze why it might be stuck
            $reasons = [];
            
            // Check if lock expired
            if ($task->locked_at && $task->locked_at->addMinutes(30)->isPast()) {
                $reasons[] = "Lock expired (locked for more than 30 minutes)";
            }
            
            // Check if started but no recent activity
            if ($task->started_at && $elapsedMinutes > 60) {
                $reasons[] = "Running for {$elapsedMinutes} minutes without completion";
            }
            
            // Check if worker might have died
            if ($task->locked_by) {
                $reasons[] = "Worker '{$task->locked_by}' may have crashed or stopped";
            }
            
            // Check if there's an error but status wasn't updated
            if ($task->error_message && $task->status === AutomationTask::STATUS_RUNNING) {
                $reasons[] = "Has error message but status wasn't updated to failed";
            }
            
            // Check payload for clues
            $payload = $task->payload ?? [];
            if (isset($payload['target_urls']) && !empty($payload['target_urls'])) {
                $url = is_array($payload['target_urls']) ? $payload['target_urls'][0] : $payload['target_urls'];
                $reasons[] = "Target URL: {$url}";
            }
            
            // Build detailed error message
            $primaryReason = !empty($reasons) ? $reasons[0] : "No activity detected";
            $details = implode("; ", array_slice($reasons, 1));
            
            $errorMessage = "Task stuck in running status for {$elapsedMinutes} minutes. ";
            $errorMessage .= "Reason: {$primaryReason}";
            if ($details) {
                $errorMessage .= ". Additional info: {$details}";
            }
            $errorMessage .= ". Reset to pending for retry.";

            // Reset task to pending so it can be retried
            $task->update([
                'status' => AutomationTask::STATUS_PENDING,
                'locked_at' => null,
                'locked_by' => null,
                'error_message' => $errorMessage,
            ]);

            $this->info("  → Reset to pending status");
            $this->line("  Reason: {$primaryReason}");
            if ($details) {
                $this->line("  Details: {$details}");
            }
            $cleaned++;
            $this->newLine();

            Log::warning("Cleaned up stuck task", [
                'task_id' => $task->id,
                'type' => $task->type,
                'campaign_id' => $task->campaign_id,
                'locked_by' => $task->locked_by,
                'started_at' => $task->started_at,
                'timeout_minutes' => $timeoutMinutes,
            ]);
        }

        $this->info("Successfully cleaned up {$cleaned} stuck task(s)");
        return 0;
    }
}

