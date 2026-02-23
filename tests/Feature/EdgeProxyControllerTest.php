<?php

namespace Tests\Feature;

use App\Models\Domain;
use App\Models\DomainMetaConnector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EdgeProxyControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['features.edge_proxy' => true]);
    }

    public function test_meta_returns_401_without_token(): void
    {
        $user = User::factory()->create();
        $domain = Domain::factory()->create(['user_id' => $user->id, 'host' => 'example.com']);

        $response = $this->getJson('/edge/meta?host=example.com&path=/');

        $response->assertStatus(401);
    }

    public function test_meta_returns_401_with_invalid_token(): void
    {
        $user = User::factory()->create();
        $domain = Domain::factory()->create(['user_id' => $user->id, 'host' => 'example.com']);
        DomainMetaConnector::create([
            'domain_id' => $domain->id,
            'user_id' => $user->id,
            'type' => 'edge_proxy',
            'status' => 'disconnected',
            'auth_json' => ['edge_token' => 'valid-token-here'],
        ]);

        $response = $this->getJson('/edge/meta?host=example.com&path=/', [
            'Authorization' => 'Bearer wrong-token',
        ]);

        $response->assertStatus(401);
    }

    public function test_meta_returns_200_with_valid_token_and_json_keys(): void
    {
        $user = User::factory()->create();
        $domain = Domain::factory()->create(['user_id' => $user->id, 'host' => 'example.com']);
        DomainMetaConnector::create([
            'domain_id' => $domain->id,
            'user_id' => $user->id,
            'type' => 'edge_proxy',
            'status' => 'disconnected',
            'auth_json' => ['edge_token' => 'valid-token-here', 'cache_ttl' => 300],
        ]);

        $response = $this->getJson('/edge/meta?host=example.com&path=/', [
            'Authorization' => 'Bearer valid-token-here',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['meta']);
        $meta = $response->json('meta');
        $this->assertArrayHasKey('title', $meta);
        $this->assertArrayHasKey('description', $meta);
        $this->assertArrayHasKey('robots', $meta);
    }

    public function test_ping_returns_401_with_invalid_token(): void
    {
        $user = User::factory()->create();
        $domain = Domain::factory()->create(['user_id' => $user->id, 'host' => 'example.com']);
        DomainMetaConnector::create([
            'domain_id' => $domain->id,
            'user_id' => $user->id,
            'type' => 'edge_proxy',
            'status' => 'disconnected',
            'auth_json' => ['edge_token' => 'secret'],
        ]);

        $response = $this->postJson('/edge/ping', ['host' => 'example.com'], [
            'Authorization' => 'Bearer wrong',
        ]);

        $response->assertStatus(401);
    }

    public function test_ping_returns_200_with_valid_token(): void
    {
        $user = User::factory()->create();
        $domain = Domain::factory()->create(['user_id' => $user->id, 'host' => 'example.com']);
        DomainMetaConnector::create([
            'domain_id' => $domain->id,
            'user_id' => $user->id,
            'type' => 'edge_proxy',
            'status' => 'disconnected',
            'auth_json' => ['edge_token' => 'secret123'],
        ]);

        $response = $this->postJson('/edge/ping', ['host' => 'example.com'], [
            'Authorization' => 'Bearer secret123',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);
    }
}
