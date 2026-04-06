<?php

namespace Tests\Unit\SeoAudit;

use App\Models\Audit;
use App\Models\AuditPage;
use App\Services\SeoAudit\NearDuplicateContentService;
use Illuminate\Support\Str;
use Tests\TestCase;

class NearDuplicateContentServiceTest extends TestCase
{
    public function test_groups_similar_pages_into_cluster_and_sets_cluster_ids(): void
    {
        $audit = Audit::create([
            'url' => 'https://example.com',
            'normalized_url' => 'https://example.com',
            'status' => Audit::STATUS_COMPLETED,
            'mode' => Audit::MODE_GUEST,
            'share_token' => Str::random(32),
            'crawl_module_flags' => ['near_duplicate_enabled' => true],
        ]);

        $textA = 'red shoes for running lightweight breathable comfortable daily use and long distance support';
        $textB = 'red shoes for running lightweight breathable comfortable daily use and long distance support';
        AuditPage::create([
            'audit_id' => $audit->id,
            'url' => 'https://example.com/a',
            'status_code' => 200,
            'title' => 'Red Shoes',
            'meta_description' => 'Running shoes',
            'h1_count' => 1,
            'content_excerpt' => $textA,
            'word_count' => 80,
            'internal_links_count' => 10,
        ]);
        AuditPage::create([
            'audit_id' => $audit->id,
            'url' => 'https://example.com/b',
            'status_code' => 200,
            'title' => 'Red Shoes',
            'meta_description' => 'Running shoes',
            'h1_count' => 1,
            'content_excerpt' => $textB,
            'word_count' => 70,
            'internal_links_count' => 5,
        ]);

        $result = app(NearDuplicateContentService::class)->run($audit);
        $this->assertNotEmpty($result['clusters']);

        $pages = $audit->pages()->get();
        $clusterIds = $pages->pluck('near_duplicate_cluster_id')->filter()->unique()->values();
        $this->assertSame(1, $clusterIds->count());
        $this->assertNotNull($pages->first()->content_fingerprint);
    }
}

