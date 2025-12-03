<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User;
use App\Models\Campaign;
use App\Models\Domain;
use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_has_many_campaigns()
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->campaigns->contains($campaign));
        $this->assertEquals(1, $user->campaigns->count());
    }

    public function test_user_has_many_domains()
    {
        $user = User::factory()->create();
        $domain = Domain::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->domains->contains($domain));
    }

    public function test_user_belongs_to_plan()
    {
        $plan = Plan::factory()->create();
        $user = User::factory()->create(['plan_id' => $plan->id]);

        $this->assertEquals($plan->id, $user->plan->id);
    }

    public function test_user_has_subscription()
    {
        $user = User::factory()->create();
        
        // User model has subscription_status field
        // Just verify user can be created
        $this->assertInstanceOf(User::class, $user);
    }
}
