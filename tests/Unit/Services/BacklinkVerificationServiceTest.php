<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\BacklinkVerificationService;
use App\Models\Backlink;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class BacklinkVerificationServiceTest extends TestCase
{

    public function test_service_can_be_initialized()
    {
        // Service uses static methods, so we just verify the class exists
        $this->assertTrue(class_exists(BacklinkVerificationService::class));
    }

    public function test_verify_backlink_success()
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create([
            'user_id' => $user->id,
            'web_url' => 'https://test-site.com',
        ]);
        $backlink = Backlink::factory()->create([
            'campaign_id' => $campaign->id,
            'url' => 'https://example.com/article',
            'status' => Backlink::STATUS_SUBMITTED,
            'anchor_text' => 'test anchor',
            'keyword' => 'seo',
        ]);

        // Mock successful HTTP response - include the domain in the HTML
        // The service checks for the domain (test-site.com) in the HTML
        // Also include anchor text in a link tag for Method 2 fallback
        Http::fake([
            '*' => Http::response('<html><body><a href="https://test-site.com">test anchor</a> and test-site.com domain</body></html>', 200),
        ]);

        // Ensure campaign relationship is loaded
        $backlink->load('campaign');
        
        $result = BacklinkVerificationService::verify($backlink);

        $this->assertTrue($result);
        $backlink->refresh();
        $this->assertEquals(Backlink::STATUS_VERIFIED, $backlink->status);
        $this->assertNotNull($backlink->verified_at);
    }

    public function test_verify_backlink_failure()
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $user->id]);
        $backlink = Backlink::factory()->create([
            'campaign_id' => $campaign->id,
            'url' => 'https://example.com/article',
            'status' => Backlink::STATUS_SUBMITTED,
        ]);

        // Mock HTTP response without backlink
        Http::fake([
            '*' => Http::response('<html><body>No backlink here</body></html>', 200),
        ]);

        $result = BacklinkVerificationService::verify($backlink);

        $this->assertFalse($result);
        $backlink->refresh();
        $this->assertEquals(Backlink::STATUS_FAILED, $backlink->status);
        $this->assertNotNull($backlink->error_message);
    }
}

