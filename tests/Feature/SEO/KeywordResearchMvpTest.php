<?php

namespace Tests\Feature\SEO;

use App\Models\KeywordResearchItem;
use App\Models\KeywordResearchRun;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;
use App\Services\AI\LLMClient;
use App\Services\AI\LLMResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class KeywordResearchMvpTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_open_keyword_page(): void
    {
        [$user, $organization] = $this->makeUserWithOrganization();

        $response = $this->actingAs($user)->get(route('keyword-research.index'));

        $response->assertOk();
    }

    public function test_user_can_submit_keyword_research_in_keyword_mode(): void
    {
        [$user, $organization] = $this->makeUserWithOrganization();
        $this->mockAiJsonResponse();

        $response = $this->actingAs($user)->post(route('keyword-research.store'), [
            'input_type' => 'keyword',
            'input_text' => 'gym management software',
            'locale_country' => 'PK',
            'locale_language' => 'en',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('keyword_research_runs', [
            'user_id' => $user->id,
            'input_type' => 'keyword',
            'seed_query' => 'gym management software',
        ]);
    }

    public function test_user_can_submit_keyword_research_in_product_mode(): void
    {
        [$user, $organization] = $this->makeUserWithOrganization();
        $this->mockAiJsonResponse();

        $response = $this->actingAs($user)->post(route('keyword-research.store'), [
            'input_type' => 'product',
            'input_text' => 'I provide salon booking software for small businesses',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('keyword_research_runs', [
            'user_id' => $user->id,
            'input_type' => 'product',
        ]);
    }

    public function test_user_can_submit_keyword_research_in_page_mode(): void
    {
        [$user, $organization] = $this->makeUserWithOrganization();
        $this->mockAiJsonResponse();

        $response = $this->actingAs($user)->post(route('keyword-research.store'), [
            'input_type' => 'page',
            'page_url' => 'https://example.com/services/seo',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('keyword_research_runs', [
            'user_id' => $user->id,
            'input_type' => 'page',
            'seed_url' => 'https://example.com/services/seo',
        ]);
    }

    public function test_keyword_research_run_and_items_are_saved(): void
    {
        [$user, $organization] = $this->makeUserWithOrganization();
        $this->mockAiJsonResponse();

        $this->actingAs($user)->post(route('keyword-research.store'), [
            'input_type' => 'keyword',
            'input_text' => 'email marketing tools',
        ])->assertSessionHasNoErrors();

        $run = KeywordResearchRun::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($run);
        $this->assertNull($run->project_id);
        $this->assertGreaterThan(0, KeywordResearchItem::query()->where('run_id', $run->id)->count());
    }

    public function test_invalid_ai_response_falls_back_safely(): void
    {
        [$user, $organization] = $this->makeUserWithOrganization();
        $this->mockAiInvalidResponse();

        $this->actingAs($user)->post(route('keyword-research.store'), [
            'input_type' => 'keyword',
            'input_text' => 'local dentist marketing',
        ])->assertSessionHasNoErrors();

        $run = KeywordResearchRun::query()->where('user_id', $user->id)->latest()->first();
        $this->assertNotNull($run);
        $this->assertStringContainsString('fallback', strtolower((string) $run->summary_text));
        $this->assertGreaterThan(0, $run->items()->count());
    }

    public function test_user_can_toggle_save_on_keyword_item(): void
    {
        [$user, $organization] = $this->makeUserWithOrganization();
        $run = KeywordResearchRun::create([
            'user_id' => $user->id,
            'input_type' => 'keyword',
            'seed_query' => 'seo tools',
            'status' => 'completed',
            'summary_text' => 'Summary',
            'result_count' => 1,
        ]);

        $item = KeywordResearchItem::create([
            'run_id' => $run->id,
            'keyword' => 'best seo tools',
            'normalized_keyword' => 'best seo tools',
        ]);

        $this->actingAs($user)->post(route('keyword-research.items.toggle-save', [
            'item' => $item->id,
        ]))->assertSessionHas('success');

        $this->assertTrue($item->fresh()->is_saved);
    }

    public function test_user_cannot_access_another_users_keyword_research_runs_or_items(): void
    {
        [$owner, $organization] = $this->makeUserWithOrganization();
        $other = User::factory()->create();
        OrganizationUser::create([
            'organization_id' => $organization->id,
            'user_id' => $other->id,
            'role' => OrganizationUser::ROLE_MEMBER,
        ]);

        $run = KeywordResearchRun::create([
            'user_id' => $owner->id,
            'input_type' => 'keyword',
            'seed_query' => 'private keyword',
            'status' => 'completed',
            'summary_text' => 'Summary',
            'result_count' => 1,
        ]);

        $item = KeywordResearchItem::create([
            'run_id' => $run->id,
            'keyword' => 'private keyword',
            'normalized_keyword' => 'private keyword',
        ]);

        $this->actingAs($other)->get(route('keyword-research.index', [
            'run' => $run->id,
        ]))->assertNotFound();

        $this->actingAs($other)->post(route('keyword-research.items.toggle-save', [
            'item' => $item->id,
        ]))->assertForbidden();
    }

    protected function mockAiJsonResponse(): void
    {
        $mock = Mockery::mock(LLMClient::class);
        $mock->shouldReceive('generateWithSystemPrompt')
            ->andReturn(new LLMResponse(json_encode([
                'summary' => 'High-intent ideas for growth.',
                'keywords' => [
                    [
                        'keyword' => 'gym management software',
                        'intent' => 'commercial',
                        'funnel_stage' => 'mofu',
                        'cluster_name' => 'Software',
                        'recommended_content_type' => 'landing_page',
                        'confidence_score' => 82,
                        'business_relevance_score' => 88,
                        'ai_reason' => 'Matches the buying intent of users looking for SaaS options.',
                    ],
                    [
                        'keyword' => 'best gym software pricing',
                        'intent' => 'transactional',
                        'funnel_stage' => 'bofu',
                        'cluster_name' => 'Pricing',
                        'recommended_content_type' => 'service_page',
                        'confidence_score' => 79,
                        'business_relevance_score' => 84,
                        'ai_reason' => 'Strong purchase-oriented query.',
                    ],
                ],
            ])));

        $this->app->instance(LLMClient::class, $mock);
    }

    protected function mockAiInvalidResponse(): void
    {
        $mock = Mockery::mock(LLMClient::class);
        $mock->shouldReceive('generateWithSystemPrompt')
            ->andReturn(new LLMResponse('not-json-response'));

        $this->app->instance(LLMClient::class, $mock);
    }

    protected function makeUserWithOrganization(): array
    {
        $user = User::factory()->create();
        $organization = Organization::create([
            'name' => 'Org ' . $user->id,
            'owner_user_id' => $user->id,
        ]);

        OrganizationUser::create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => OrganizationUser::ROLE_OWNER,
        ]);

        return [$user, $organization];
    }
}
