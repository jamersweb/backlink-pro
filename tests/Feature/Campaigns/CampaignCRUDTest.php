<?php

namespace Tests\Feature\Campaigns;

use Tests\TestCase;
use App\Models\User;
use App\Models\Campaign;
use App\Models\Domain;
use App\Models\Plan;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CampaignCRUDTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->plan = Plan::factory()->create();
        $this->user = User::factory()->create(['plan_id' => $this->plan->id]);
        $this->domain = Domain::factory()->create(['user_id' => $this->user->id]);
        $this->country = Country::factory()->create();
        $this->state = State::factory()->create(['country_id' => $this->country->id]);
        $this->city = City::factory()->create(['state_id' => $this->state->id]);
    }

    public function test_user_can_view_campaigns_index()
    {
        $response = $this->actingAs($this->user)->get('/campaign');
        $response->assertStatus(200);
    }

    public function test_user_can_create_campaign()
    {
        $logo = UploadedFile::fake()->image('logo.jpg', 100, 100);
        
        $response = $this->actingAs($this->user)->post('/campaign', [
            'name' => 'Test Campaign',
            'domain_id' => $this->domain->id,
            'web_name' => 'Test Site',
            'web_url' => 'https://example.com',
            'web_keyword' => 'test keyword',
            'web_about' => 'Test website description',
            'web_target' => 'worldwide',
            'company_name' => 'Test Company',
            'company_email_address' => 'test@example.com',
            'company_address' => '123 Test St',
            'company_number' => '1234567890',
            'company_country' => $this->country->id,
            'company_state' => $this->state->id,
            'company_city' => $this->city->id,
            'company_logo' => $logo,
            'gmail' => 'test@gmail.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('campaigns', [
            'name' => 'Test Campaign',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_user_can_update_campaign()
    {
        $campaign = Campaign::factory()->create(['user_id' => $this->user->id]);
        
        $response = $this->actingAs($this->user)->put("/campaign/{$campaign->id}", [
            'name' => 'Updated Campaign',
            'domain_id' => $campaign->domain_id,
            'web_name' => $campaign->web_name,
            'web_url' => $campaign->web_url,
            'web_keyword' => $campaign->web_keyword,
            'web_about' => $campaign->web_about,
            'web_target' => 'worldwide',
            'company_name' => $campaign->company_name,
            'company_email_address' => $campaign->company_email_address,
            'company_address' => $campaign->company_address,
            'company_number' => $campaign->company_number,
            'company_country' => $this->country->id,
            'company_state' => $this->state->id,
            'company_city' => $this->city->id,
            'gmail' => $campaign->gmail ?? 'test@gmail.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('campaigns', [
            'id' => $campaign->id,
            'name' => 'Updated Campaign',
        ]);
    }

    public function test_user_can_delete_campaign()
    {
        $campaign = Campaign::factory()->create(['user_id' => $this->user->id]);
        
        $response = $this->actingAs($this->user)->delete("/campaign/{$campaign->id}");
        
        $this->assertDatabaseMissing('campaigns', [
            'id' => $campaign->id,
        ]);
    }

    public function test_user_cannot_exceed_campaign_limit()
    {
        $plan = Plan::factory()->create(['max_campaigns' => 1]);
        $this->user->update(['plan_id' => $plan->id]);
        
        Campaign::factory()->create(['user_id' => $this->user->id]);
        
        $logo = UploadedFile::fake()->image('logo.jpg', 100, 100);
        
        $response = $this->actingAs($this->user)->post('/campaign', [
            'name' => 'Second Campaign',
            'domain_id' => $this->domain->id,
            'web_name' => 'Test Site',
            'web_url' => 'https://example.com',
            'web_keyword' => 'test keyword',
            'web_about' => 'Test website description',
            'web_target' => 'worldwide',
            'company_name' => 'Test Company',
            'company_email_address' => 'test@example.com',
            'company_address' => '123 Test St',
            'company_number' => '1234567890',
            'company_country' => $this->country->id,
            'company_state' => $this->state->id,
            'company_city' => $this->city->id,
            'company_logo' => $logo,
            'gmail' => 'test@gmail.com',
            'password' => 'password123',
        ]);
        
        $response->assertSessionHasErrors('campaign_limit');
    }
}

