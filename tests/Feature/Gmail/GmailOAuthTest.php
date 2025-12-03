<?php

namespace Tests\Feature\Gmail;

use Tests\TestCase;
use App\Models\User;
use App\Models\ConnectedAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

class GmailOAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_user_can_view_gmail_index()
    {
        $response = $this->actingAs($this->user)->get('/gmail');
        $response->assertStatus(200);
    }

    public function test_user_can_initiate_gmail_oauth()
    {
        $response = $this->actingAs($this->user)->get('/gmail/oauth/connect');
        
        // Should redirect to Google OAuth
        $response->assertRedirect();
        $this->assertStringContainsString('accounts.google.com', $response->headers->get('Location'));
    }

    public function test_user_can_disconnect_gmail()
    {
        $account = ConnectedAccount::factory()->create([
            'user_id' => $this->user->id,
            'provider' => 'gmail',
        ]);
        
        $response = $this->actingAs($this->user)->post("/gmail/oauth/disconnect/{$account->id}");
        
        $response->assertRedirect(route('gmail.index'));
        $response->assertSessionHas('success');
        
        // Verify account status was updated to REVOKED (not deleted)
        $account->refresh();
        $this->assertEquals(ConnectedAccount::STATUS_REVOKED, $account->status);
    }
}

