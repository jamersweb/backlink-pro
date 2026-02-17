<?php

namespace App\Console\Commands;

use App\Models\Audit;
use Illuminate\Console\Command;

class FailStuckQueuedAuditsCommand extends Command
{
    protected $signature = 'audit:fail-stuck-queued
                            {--minutes=5 : Minutes to wait before marking as failed}';

    protected $description = 'Mark audits stuck in queued state as failed (worker not running)';

    public function handle(): int
    {
        $minutes = (int) $this->option('minutes');
        $cutoff = now()->subMinutes($minutes);

        $stuck = Audit::where('status', Audit::STATUS_QUEUED)
            ->where('created_at', '<', $cutoff)
            ->get();

        if ($stuck->isEmpty()) {
            return self::SUCCESS;
        }

        $message = 'Worker is not running or queue is blocked. Please start the queue worker: php artisan queue:work database';
        $count = 0;

        foreach ($stuck as $audit) {
            $audit->status = Audit::STATUS_FAILED;
            $audit->error = $message;
            $audit->progress_stage = 'failed';
            $audit->finished_at = now();
            $audit->save();
            $count++;

            \Log::warning('Audit marked as failed (stuck in queue)', [
                'audit_id' => $audit->id,
                'url' => $audit->url,
                'queued_since' => $audit->created_at->toIso8601String(),
            ]);
        }

        $this->info("Marked {$count} stuck audit(s) as failed.");
        return self::SUCCESS;
    }
}
