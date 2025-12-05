<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Campaign;
use App\Models\Plan;
use App\Models\Category;
use App\Models\BacklinkOpportunity;
use App\Models\Backlink;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OpportunitySelectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a plan with PA/DA limits
        $this->plan = Plan::factory()->create([
            'name' => 'Test Plan',
            'slug' => 'test-plan',
            'min_pa' => 0,
            'max_pa' => 50,
            'min_da' => 0,
            'max_da' => 60,
        ]);
        
        // Create a user with the plan
        $this->user = User::factory()->create([
            'plan_id' => $this->plan->id,
        ]);
        
        // Get or create categories
        $this->category = Category::firstOrCreate(
            ['slug' => 'technology'],
            ['name' => 'Technology', 'status' => 'active']
        );
        
        $this->subcategory = Category::firstOrCreate(
            ['slug' => 'technology-web-development'],
            [
                'name' => 'Web Development',
                'parent_id' => $this->category->id,
                'status' => 'active'
            ]
        );
        
        // Create a campaign with category
        $this->campaign = Campaign::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'subcategory_id' => $this->subcategory->id,
            'daily_limit' => 10,
        ]);
    }

    public function test_opportunity_selection_without_opportunities()
    {
        $response = $this->getJson("/api/opportunities/for-campaign/{$this->campaign->id}", [
            'X-API-Token' => config('app.api_token'),
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'opportunities' => [],
            ]);
    }

    public function test_opportunity_selection_with_matching_opportunities()
    {
        // Create opportunities matching the category
        $opportunity1 = BacklinkOpportunity::factory()->create([
            'pa' => 30,
            'da' => 40,
            'status' => 'active',
            'site_type' => 'comment',
            'daily_site_limit' => 5,
        ]);
        
        $opportunity1->categories()->attach([$this->category->id, $this->subcategory->id]);
        
        $opportunity2 = BacklinkOpportunity::factory()->create([
            'pa' => 45,
            'da' => 55,
            'status' => 'active',
            'site_type' => 'comment',
            'daily_site_limit' => 3,
        ]);
        
        $opportunity2->categories()->attach([$this->category->id]);
        
        $response = $this->getJson("/api/opportunities/for-campaign/{$this->campaign->id}?count=2", [
            'X-API-Token' => config('app.api_token'),
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'opportunities' => [
                    '*' => ['id', 'url', 'pa', 'da', 'site_type', 'categories'],
                ],
                'campaign',
                'plan_limits',
            ]);
        
        $data = $response->json();
        $this->assertGreaterThan(0, count($data['opportunities']));
        
        // Verify PA/DA are within plan limits
        foreach ($data['opportunities'] as $opp) {
            $this->assertGreaterThanOrEqual($this->plan->min_pa, $opp['pa']);
            $this->assertLessThanOrEqual($this->plan->max_pa, $opp['pa']);
            $this->assertGreaterThanOrEqual($this->plan->min_da, $opp['da']);
            $this->assertLessThanOrEqual($this->plan->max_da, $opp['da']);
        }
    }

    public function test_opportunity_selection_filters_by_pa_da_limits()
    {
        // Create opportunity outside PA/DA limits
        $opportunityHigh = BacklinkOpportunity::factory()->create([
            'pa' => 80, // Above max_pa (50)
            'da' => 70, // Above max_da (60)
            'status' => 'active',
        ]);
        $opportunityHigh->categories()->attach([$this->category->id]);
        
        // Create opportunity within limits
        $opportunityGood = BacklinkOpportunity::factory()->create([
            'pa' => 30,
            'da' => 40,
            'status' => 'active',
        ]);
        $opportunityGood->categories()->attach([$this->category->id]);
        
        $response = $this->getJson("/api/opportunities/for-campaign/{$this->campaign->id}", [
            'X-API-Token' => config('app.api_token'),
        ]);

        $data = $response->json();
        
        // Should only return opportunity within limits
        $this->assertCount(1, $data['opportunities']);
        $this->assertEquals($opportunityGood->id, $data['opportunities'][0]['id']);
    }

    public function test_opportunity_selection_respects_daily_limits()
    {
        $opportunity = BacklinkOpportunity::factory()->create([
            'pa' => 30,
            'da' => 40,
            'status' => 'active',
            'daily_site_limit' => 2,
        ]);
        $opportunity->categories()->attach([$this->category->id]);
        
        // Create 2 backlinks today (reaching daily limit)
        Backlink::factory()->count(2)->create([
            'campaign_id' => $this->campaign->id,
            'backlink_opportunity_id' => $opportunity->id,
            'created_at' => now(),
        ]);
        
        $response = $this->getJson("/api/opportunities/for-campaign/{$this->campaign->id}", [
            'X-API-Token' => config('app.api_token'),
        ]);

        $data = $response->json();
        
        // Should not return opportunity that reached daily limit
        $this->assertCount(0, $data['opportunities']);
    }

    public function test_opportunity_selection_requires_category()
    {
        // Create campaign without category
        $campaignNoCategory = Campaign::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => null,
            'subcategory_id' => null,
        ]);
        
        $response = $this->getJson("/api/opportunities/for-campaign/{$campaignNoCategory->id}", [
            'X-API-Token' => config('app.api_token'),
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error' => 'Campaign must have a category or subcategory selected',
            ]);
    }

    public function test_opportunity_selection_filters_by_site_type()
    {
        $opportunityComment = BacklinkOpportunity::factory()->create([
            'pa' => 30,
            'da' => 40,
            'status' => 'active',
            'site_type' => 'comment',
        ]);
        $opportunityComment->categories()->attach([$this->category->id]);
        
        $opportunityProfile = BacklinkOpportunity::factory()->create([
            'pa' => 35,
            'da' => 45,
            'status' => 'active',
            'site_type' => 'profile',
        ]);
        $opportunityProfile->categories()->attach([$this->category->id]);
        
        $response = $this->getJson("/api/opportunities/for-campaign/{$this->campaign->id}?site_type=comment", [
            'X-API-Token' => config('app.api_token'),
        ]);

        $data = $response->json();
        
        // Should only return comment type opportunities
        foreach ($data['opportunities'] as $opp) {
            $this->assertEquals('comment', $opp['site_type']);
        }
    }
}

