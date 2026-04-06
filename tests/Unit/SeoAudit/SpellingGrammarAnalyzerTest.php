<?php

namespace Tests\Unit\SeoAudit;

use App\Services\SeoAudit\SpellingDictionary;
use App\Services\SeoAudit\SpellingGrammarAnalyzer;
use Tests\TestCase;

class SpellingGrammarAnalyzerTest extends TestCase
{
    public function test_detects_common_typo_with_high_confidence(): void
    {
        $dict = new SpellingDictionary([]);
        $analyzer = new SpellingGrammarAnalyzer($dict, []);
        $findings = $analyzer->analyze('The teh quick brown fox jumps over the lazy dog.');

        $teh = collect($findings)->first(fn (array $f) => ($f['text'] ?? '') === 'teh');
        $this->assertNotNull($teh);
        $this->assertSame('spelling', $teh['kind']);
        $this->assertSame('the', $teh['suggestion']);
        $this->assertGreaterThanOrEqual(80, $teh['confidence']);
        $this->assertContains('high_confidence', $teh['filter_tags']);
    }

    public function test_allowlist_suppresses_brand_tokens(): void
    {
        $dict = new SpellingDictionary(['zorkbrand']);
        $analyzer = new SpellingGrammarAnalyzer($dict, ['zorkbrand']);
        $findings = $analyzer->analyze('Contact zorkbrand about teh pricing today.');

        $this->assertFalse(collect($findings)->contains(fn (array $f) => strtolower((string) ($f['text'] ?? '')) === 'zorkbrand'));
        $this->assertTrue(collect($findings)->contains(fn (array $f) => ($f['text'] ?? '') === 'teh'));
    }

    public function test_repeated_word_detection(): void
    {
        $dict = new SpellingDictionary([]);
        $analyzer = new SpellingGrammarAnalyzer($dict, []);
        $findings = $analyzer->analyze('This order is a good good choice for teams.');

        $this->assertTrue(collect($findings)->contains(fn (array $f) => ($f['kind'] ?? '') === 'repeated_word'));
    }

    public function test_strips_emails_before_checks(): void
    {
        $dict = new SpellingDictionary([]);
        $analyzer = new SpellingGrammarAnalyzer($dict, []);
        $findings = $analyzer->analyze('Email us at bogusname@foo.invalid for teh details.');

        $joined = strtolower(implode(' ', array_column($findings, 'context')));
        $this->assertStringNotContainsString('bogusname', $joined);
        $this->assertTrue(collect($findings)->contains(fn (array $f) => ($f['text'] ?? '') === 'teh'));
    }
}
