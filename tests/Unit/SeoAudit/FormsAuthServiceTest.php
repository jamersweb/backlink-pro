<?php

namespace Tests\Unit\SeoAudit;

use App\Models\Audit;
use App\Services\SeoAudit\AuthCrawlMetadataBuilder;
use App\Services\SeoAudit\FormsAuthService;
use PHPUnit\Framework\TestCase;

class FormsAuthServiceTest extends TestCase
{
    public function test_mask_username_email(): void
    {
        $this->assertSame('ab**@example.com', FormsAuthService::maskUsername('abcd@example.com'));
    }

    public function test_mask_username_non_email(): void
    {
        $this->assertSame('u**r', FormsAuthService::maskUsername('user'));
    }

    public function test_auth_crawl_metadata_when_login_failed(): void
    {
        $audit = new Audit([
            'normalized_url' => 'https://example.com/',
            'crawl_module_flags' => ['forms_auth_enabled' => true],
            'forms_auth_login_url' => 'https://example.com/login',
            'forms_auth_state' => ['login_success' => false],
        ]);

        $meta = AuthCrawlMetadataBuilder::build($audit, 200, 'https://example.com/login', 'Sign in', 20);
        $this->assertFalse($meta['likely_authenticated_content']);
    }

    public function test_auth_crawl_metadata_authenticated_page(): void
    {
        $audit = new Audit([
            'normalized_url' => 'https://example.com/',
            'crawl_module_flags' => ['forms_auth_enabled' => true],
            'forms_auth_login_url' => 'https://example.com/login',
            'forms_auth_state' => ['login_success' => true],
        ]);

        $meta = AuthCrawlMetadataBuilder::build($audit, 200, 'https://example.com/dashboard', 'Dashboard', 200);
        $this->assertTrue($meta['likely_authenticated_content']);
        $this->assertFalse($meta['http_auth_blocked']);
    }
}
