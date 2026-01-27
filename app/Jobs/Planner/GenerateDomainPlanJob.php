<?php

namespace App\Jobs\Planner;

use App\Models\Domain;
use App\Models\DomainPlan;
use App\Services\Planner\DomainActionPlanner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateDomainPlanJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 300;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public array $backoff = [30, 60, 120];

    public function __construct(
        public int $domainId,
        public int $userId,
        public int $periodDays = 28
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $domain = Domain::findOrFail($this->domainId);
            
            Log::info('Generating domain plan', [
                'domain_id' => $this->domainId,
                'user_id' => $this->userId,
                'period_days' => $this->periodDays,
            ]);

            $planner = new DomainActionPlanner($domain, $this->periodDays);
            $planItems = $planner->generatePlan();

            // Create or update draft plan
            DomainPlan::updateOrCreate(
                [
                    'domain_id' => $domain->id,
                    'user_id' => $this->userId,
                    'status' => DomainPlan::STATUS_DRAFT,
                ],
                [
                    'period_days' => $this->periodDays,
                    'plan_json' => $planItems,
                    'generated_by' => DomainPlan::GENERATED_BY_HEURISTIC,
                    'generated_at' => now(),
                ]
            );

            Log::info('Domain plan generated successfully', [
                'domain_id' => $this->domainId,
                'items_count' => count($planItems),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to generate domain plan', [
                'domain_id' => $this->domainId,
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e; // Re-throw to trigger retry
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateDomainPlanJob permanently failed', [
            'domain_id' => $this->domainId,
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
        ]);
    }
}
