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

class GenerateDomainPlanJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
        $domain = Domain::findOrFail($this->domainId);
        
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
    }
}
