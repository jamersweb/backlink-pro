<?php

namespace Tests\Unit\Audits;

use App\Services\Audits\CrawlBootstrapResolver;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CrawlBootstrapResolverTest extends TestCase
{
    public function test_normalize_base_url_accepts_domain_without_scheme(): void
    {
        $this->assertSame(
            'https://apple.com',
            CrawlBootstrapResolver::normalizeBaseUrl('  apple.com  ')
        );
    }

    public function test_normalize_base_url_preserves_www_host(): void
    {
        $this->assertSame(
            'https://www.apple.com',
            CrawlBootstrapResolver::normalizeBaseUrl('www.apple.com')
        );
    }

    public function test_normalize_base_url_keeps_https_input(): void
    {
        $this->assertSame(
            'https://apple.com',
            CrawlBootstrapResolver::normalizeBaseUrl('https://apple.com')
        );
    }

    public function test_candidate_urls_include_www_fallback_when_host_has_no_www(): void
    {
        $candidates = CrawlBootstrapResolver::candidateBaseUrls('https://apple.com');
        $this->assertSame(['https://apple.com', 'https://www.apple.com'], $candidates);
    }

    public function test_candidate_urls_include_non_www_fallback_when_host_has_www(): void
    {
        $candidates = CrawlBootstrapResolver::candidateBaseUrls('https://www.apple.com');
        $this->assertSame(['https://www.apple.com', 'https://apple.com'], $candidates);
    }

    public function test_resolver_falls_back_to_alternate_host_when_primary_fails(): void
    {
        Http::fake(function (Request $request) {
            $url = (string) $request->url();
            if ($url === 'https://apple.com/') {
                throw new \Exception('cURL error 6: Could not resolve host: apple.com');
            }
            if ($url === 'https://www.apple.com/') {
                return Http::response('<html>ok</html>', 200);
            }

            return Http::response('', 404);
        });

        $result = CrawlBootstrapResolver::resolve('apple.com');

        $this->assertTrue($result['success']);
        $this->assertSame('https://www.apple.com', $result['working_base_url']);
        $this->assertCount(2, $result['attempts']);
        $this->assertFalse($result['attempts'][0]['success']);
        $this->assertTrue($result['attempts'][1]['success']);
    }

    public function test_resolver_returns_clean_failure_when_both_variants_fail(): void
    {
        Http::fake(function () {
            throw new \Exception('cURL error 6: Could not resolve host');
        });

        $result = CrawlBootstrapResolver::resolve('invalid-domain-for-test.local');

        $this->assertFalse($result['success']);
        $this->assertNull($result['working_base_url']);
        $this->assertCount(2, $result['attempts']);
        $this->assertNotEmpty($result['error']);
    }
}

