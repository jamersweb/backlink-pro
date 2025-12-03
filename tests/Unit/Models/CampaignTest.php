<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Campaign;
use App\Models\User;
use App\Models\Domain;
use App\Models\Backlink;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CampaignTest extends TestCase
{
    use RefreshDatabase;

    public function test_campaign_belongs_to_user()
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $user->id]);

        $this->assertEquals($user->id, $campaign->user->id);
    }

    public function test_campaign_belongs_to_domain()
    {
        $domain = Domain::factory()->create();
        $campaign = Campaign::factory()->create(['domain_id' => $domain->id]);

        $this->assertEquals($domain->id, $campaign->domain->id);
    }

    public function test_campaign_has_many_backlinks()
    {
        $campaign = Campaign::factory()->create();
        $backlink = Backlink::factory()->create(['campaign_id' => $campaign->id]);

        $this->assertTrue($campaign->backlinks->contains($backlink));
    }

    public function test_campaign_status_constants()
    {
        $this->assertEquals('active', Campaign::STATUS_ACTIVE);
        $this->assertEquals('paused', Campaign::STATUS_PAUSED);
        $this->assertEquals('completed', Campaign::STATUS_COMPLETED);
    }
}
