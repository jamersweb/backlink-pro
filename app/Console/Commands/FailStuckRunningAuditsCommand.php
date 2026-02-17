<?php

namespace App\Console\Commands;

use App\Models\Audit;
use Illuminate\Console\Command;

class FailStuckRunningAuditsCommand extends Command
{
    protected $signature = 'audit:fail-stuck-running
                            {--minutes=15 : Minutes running before marking as failed}';

    protected $description = 'Mark audits stuck in running state as failed (max runtime exceeded)';

    public function handle(): int
    {
        $minutes = (int) $this->option('minutes');
        $cutoff = now()->subMinutes($minutes);

        $stuck = Audit::where('status', Audit::STATUS_RUNNING)
            ->where('started_at', '<', $cutoff)
            ->get();

        if ($stuck->isEmpty()) {
            return self::SUCCESS;
        }

        $message = 'Timed out while generating report. Please try again.';
        $count = 0;

        foreach ($stuck as $audit) {
            $audit->status = Audit::STATUS_FAILED;
            $audit->error = $message;
            $audit->progress_stage = 'failed';
            $audit->finished_at = now();
            $audit->save();
            $count++;

            \Log::warning('Audit marked as failed (stuck running)', [
                'audit_id' => $audit->id,
                'url' => $audit->url,
                'started_at' => $audit->started_at?->toIso8601String(),
            ]);
        }

        $this->info("Marked {$count} stuck running audit(s) as failed.");
        return self::SUCCESS;
    }
}
