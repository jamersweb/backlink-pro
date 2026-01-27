<?php

namespace App\Jobs\Insights;

use App\Models\Domain;
use App\Models\DomainInsightRun;
use App\Services\Insights\DomainInsightsEngine;
use App\Services\System\ActivityLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateDomainInsightsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 2;
    public $queue = 'insights';

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $domainId,
        public int $periodDays = 28
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $domain = Domain::findOrFail($this->domainId);

        // Create insight run
        $run = DomainInsightRun::create([
            'domain_id' => $domain->id,
            'user_id' => $domain->user_id,
            'status' => DomainInsightRun::STATUS_RUNNING,
            'period_days' => $this->periodDays,
            'started_at' => now(),
        ]);

        try {
            // Run insights engine
            $engine = new DomainInsightsEngine($domain, $this->periodDays);
            $result = $engine->generate();

            // Update run
            $run->update([
                'status' => DomainInsightRun::STATUS_COMPLETED,
                'finished_at' => now(),
                'summary_json' => $result['summary'],
            ]);

            // Log success
            $logger = app(ActivityLogger::class);
            $logger->success(
                'insights',
                'completed',
                "Insights generated: {$result['tasks_created']} tasks, {$result['alerts_created']} alerts",
                $domain->user_id,
                $domain->id,
                ['run_id' => $run->id, 'tasks_created' => $result['tasks_created'], 'alerts_created' => $result['alerts_created']]
            );

            Log::info('Domain insights generated', [
                'domain_id' => $domain->id,
                'tasks_created' => $result['tasks_created'],
                'alerts_created' => $result['alerts_created'],
            ]);
        } catch (\Exception $e) {
            $logger = app(ActivityLogger::class);
            $logger->logJobFailure(
                'insights',
                'GenerateDomainInsightsJob',
                $e,
                $domain->id,
                $domain->user_id,
                ActivityLogger::runRef('insights', $run->id),
                ['run_id' => $run->id, 'domain_id' => $domain->id]
            );

            Log::error('Domain insights generation failed', [
                'domain_id' => $domain->id,
                'error' => $e->getMessage(),
            ]);

            $run->update([
                'status' => DomainInsightRun::STATUS_FAILED,
                'error_message' => $e->getMessage(),
                'finished_at' => now(),
            ]);

            throw $e;
        }
    }
}
