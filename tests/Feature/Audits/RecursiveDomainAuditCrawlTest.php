<?php

namespace Tests\Feature\Audits;

use App\Jobs\Audits\StartDomainAuditJob;
use App\Models\Domain;
use App\Models\DomainAudit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RecursiveDomainAuditCrawlTest extends TestCase
{
    use RefreshDatabase;

    public function test_recursive_crawl_enqueues_internal_links_and_crawls_multiple_pages(): void
    {
        $audit = $this->makeAudit([
            'crawl_limit' => 10,
            'max_depth' => 3,
            'include_sitemap' => false,
        ]);

        Http::fake(function (Request $request) {
            $url = (string) $request->url();
            if ($url === 'https://example.com/') {
                return Http::response('<html><head><title>Home</title></head><body><a href="/about">About</a><a href="/contact">Contact</a></body></html>', 200, ['Content-Type' => 'text/html']);
            }
            if ($url === 'https://example.com/about') {
                return Http::response('<html><head><title>About</title></head><body><a href="/team">Team</a></body></html>', 200, ['Content-Type' => 'text/html']);
            }
            if ($url === 'https://example.com/contact') {
                return Http::response('<html><head><title>Contact</title></head><body>Contact page</body></html>', 200, ['Content-Type' => 'text/html']);
            }
            if ($url === 'https://example.com/team') {
                return Http::response('<html><head><title>Team</title></head><body>Team page</body></html>', 200, ['Content-Type' => 'text/html']);
            }

            return Http::response('', 404);
        });

        (new StartDomainAuditJob($audit->id))->handle();

        $audit->refresh();
        $this->assertGreaterThan(1, $audit->pages()->count());
        $this->assertDatabaseHas('domain_audit_pages', [
            'domain_audit_id' => $audit->id,
            'url' => 'https://example.com/about',
            'crawl_depth' => 1,
        ]);
    }

    public function test_max_depth_zero_only_crawls_start_page(): void
    {
        $audit = $this->makeAudit([
            'crawl_limit' => 10,
            'max_depth' => 0,
            'include_sitemap' => false,
        ]);

        Http::fake([
            'https://example.com/' => Http::response('<html><body><a href="/about">About</a></body></html>', 200, ['Content-Type' => 'text/html']),
        ]);

        (new StartDomainAuditJob($audit->id))->handle();

        $this->assertSame(1, $audit->fresh()->pages()->count());
    }

    public function test_crawl_limit_caps_total_discovered_urls(): void
    {
        $audit = $this->makeAudit([
            'crawl_limit' => 2,
            'max_depth' => 3,
            'include_sitemap' => false,
        ]);

        Http::fake([
            'https://example.com/' => Http::response('<html><body><a href="/a">A</a><a href="/b">B</a><a href="/c">C</a></body></html>', 200, ['Content-Type' => 'text/html']),
            'https://example.com/a' => Http::response('<html><body>A</body></html>', 200, ['Content-Type' => 'text/html']),
            'https://example.com/b' => Http::response('<html><body>B</body></html>', 200, ['Content-Type' => 'text/html']),
            'https://example.com/c' => Http::response('<html><body>C</body></html>', 200, ['Content-Type' => 'text/html']),
        ]);

        (new StartDomainAuditJob($audit->id))->handle();

        $this->assertSame(2, $audit->fresh()->pages()->count());
    }

    protected function makeAudit(array $settings): DomainAudit
    {
        $user = User::factory()->create();
        $domain = Domain::create([
            'user_id' => $user->id,
            'name' => 'Example',
            'url' => 'https://example.com',
            'host' => 'example.com',
            'platform' => Domain::PLATFORM_CUSTOM,
            'verification_status' => Domain::VERIFICATION_UNVERIFIED,
            'status' => Domain::STATUS_ACTIVE,
        ]);

        return DomainAudit::create([
            'domain_id' => $domain->id,
            'user_id' => $user->id,
            'status' => DomainAudit::STATUS_QUEUED,
            'settings_json' => $settings,
        ]);
    }
}

