<?php

namespace Tests\Unit\SeoAudit;

use App\Services\SeoAudit\VisibleTextExtractor;
use Tests\TestCase;

class VisibleTextExtractorTest extends TestCase
{
    public function test_strips_navigation_and_script_content(): void
    {
        $html = '<html><body>'
            .'<nav><a href="/">Home</a> Sidebar boilerplate text</nav>'
            .'<script>alert("x")</script>'
            .'<main><p>Primary marketing copy about widgets.</p></main>'
            .'<footer>Footer legal</footer>'
            .'</body></html>';

        $text = VisibleTextExtractor::extractFromHtml($html);

        $this->assertStringContainsString('Primary marketing copy about widgets.', $text);
        $this->assertStringNotContainsString('boilerplate', $text);
        $this->assertStringNotContainsString('Footer legal', $text);
        $this->assertStringNotContainsString('alert', $text);
    }

    public function test_removes_pre_code_blocks(): void
    {
        $html = '<html><body><p>Intro</p><pre><code>const x = 1;</code></pre><p>Outro</p></body></html>';
        $text = VisibleTextExtractor::extractFromHtml($html);
        $this->assertStringContainsString('Intro', $text);
        $this->assertStringContainsString('Outro', $text);
        $this->assertStringNotContainsString('const x', $text);
    }
}
