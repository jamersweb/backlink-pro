<?php

namespace Tests\Unit\SeoAudit;

use App\Services\SeoAudit\CustomAuditRulesValidator;
use App\Services\SeoAudit\CustomExtractionEvaluator;
use App\Services\SeoAudit\CustomSourceSearchEvaluator;
use PHPUnit\Framework\TestCase;

class CustomAuditEvaluatorsTest extends TestCase
{
    public function test_search_contains_and_regex(): void
    {
        $html = '<html><body><h1 data-x="1">Hello World</h1></body></html>';
        $headers = ['X-Foo' => 'Bar'];
        $r1 = CustomSourceSearchEvaluator::evaluate(
            ['target_scope' => 'raw_html', 'match_type' => 'contains', 'pattern' => 'World'],
            $html,
            $headers,
            '',
            null
        );
        $this->assertTrue($r1['matched']);
        $this->assertGreaterThan(0, $r1['match_count']);

        $r2 = CustomSourceSearchEvaluator::evaluate(
            ['target_scope' => 'raw_html', 'match_type' => 'regex', 'pattern' => '/<h1[^>]*>([^<]+)</'],
            $html,
            $headers,
            '',
            null
        );
        $this->assertTrue($r2['matched']);
        $this->assertNotNull($r2['sample_match']);
    }

    public function test_search_css_and_xpath_exist(): void
    {
        $html = '<html><body><div class="wrap"><span id="t">x</span></div></body></html>';
        $css = CustomSourceSearchEvaluator::evaluate(
            ['target_scope' => 'raw_html', 'match_type' => 'css_selector_exists', 'pattern' => 'div.wrap span#t'],
            $html,
            [],
            '',
            null
        );
        $this->assertTrue($css['matched']);

        $xpath = CustomSourceSearchEvaluator::evaluate(
            ['target_scope' => 'raw_html', 'match_type' => 'xpath_exists', 'pattern' => '//span[@id="t"]'],
            $html,
            [],
            '',
            null
        );
        $this->assertTrue($xpath['matched']);
    }

    public function test_extraction_css_xpath_regex_meta_and_json_ld(): void
    {
        $html = '<html><head>'
            .'<link rel="canonical" href="https://ex.com/c">'
            .'<link rel="alternate" hreflang="en" href="https://ex.com/en">'
            .'<meta name="keywords" content="a,b">'
            .'<script type="application/ld+json">{"@type":"Product","name":"P1"}</script>'
            .'</head><body><h1>Title</h1></body></html>';

        $canon = CustomExtractionEvaluator::extract(
            ['target_scope' => 'raw_html', 'extraction_type' => 'css', 'extractor' => 'link[rel="canonical"]', 'attribute' => 'href', 'multiple' => false],
            $html,
            [],
            '',
            null
        );
        $this->assertSame(['https://ex.com/c'], $canon['values']);

        $h1 = CustomExtractionEvaluator::extract(
            ['target_scope' => 'raw_html', 'extraction_type' => 'xpath', 'extractor' => '//h1', 'attribute' => null, 'multiple' => false],
            $html,
            [],
            '',
            null
        );
        $this->assertSame(['Title'], $h1['values']);

        $hreflang = CustomExtractionEvaluator::extract(
            ['target_scope' => 'raw_html', 'extraction_type' => 'regex', 'extractor' => '/hreflang="([^"]+)"/', 'multiple' => true],
            $html,
            [],
            '',
            null
        );
        $this->assertContains('en', $hreflang['values']);

        $meta = CustomExtractionEvaluator::extract(
            ['target_scope' => 'raw_html', 'extraction_type' => 'meta_tag', 'extractor' => 'name=keywords', 'attribute' => 'content', 'multiple' => false],
            $html,
            [],
            '',
            null
        );
        $this->assertSame(['a,b'], $meta['values']);

        $jsonLd = CustomExtractionEvaluator::extract(
            ['target_scope' => 'raw_html', 'extraction_type' => 'json_ld', 'extractor' => 'name', 'multiple' => false],
            $html,
            [],
            '',
            null
        );
        $this->assertSame(['P1'], $jsonLd['values']);
    }

    public function test_validator_rejects_bad_regex(): void
    {
        $v = CustomAuditRulesValidator::validateSearchPayload([
            'rules' => [[
                'id' => 'bad',
                'rule_name' => 'bad',
                'target_scope' => 'raw_html',
                'match_type' => 'regex',
                'pattern' => '(',
                'severity' => 'warning',
            ]],
        ]);
        $this->assertFalse($v['valid']);
        $this->assertNotEmpty($v['errors']);
    }
}
