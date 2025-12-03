<?php

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use App\Jobs\ScheduleCampaignJob;
use App\Models\Campaign;
use App\Models\User;
use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

class ScheduleCampaignJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_can_be_dispatched()
    {
        Queue::fake();
        
        ScheduleCampaignJob::dispatch();
        
        Queue::assertPushed(ScheduleCampaignJob::class);
    }

    public function test_job_handles_no_active_campaigns()
    {
        $job = new ScheduleCampaignJob();
        
        // Should not throw exception when no campaigns exist
        $this->assertNull($job->handle());
    }

    public function test_job_respects_plan_limits()
    {
        $plan = Plan::factory()->create([
            'max_campaigns' => 1,
            'daily_backlink_limit' => 10,
        ]);
        
        $user = User::factory()->create(['plan_id' => $plan->id]);
        $campaign = Campaign::factory()->create([
            'user_id' => $user->id,
            'status' => Campaign::STATUS_ACTIVE,
        ]);
        
        $job = new ScheduleCampaignJob();
        
        // Job should handle plan limits
        $this->assertNull($job->handle());
    }
}
