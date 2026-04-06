<?php

namespace Tests\Unit\SeoAudit;

use App\Services\SeoAudit\CrawlModuleConfig;
use App\Services\SeoAudit\ReportModuleRegistry;
use Tests\TestCase;

class ReportModuleRegistryTest extends TestCase
{
    public function test_registry_returns_ordered_core_and_enabled_optional_modules(): void
    {
        $registry = new ReportModuleRegistry();
        $config = new CrawlModuleConfig();

        $flags = $config->normalizeFlags([
            'js_rendering_enabled' => true,
            'segmentation_enabled' => true,
            'site_visualisation_enabled' => false,
        ]);

        $enabled = $config->enabledModuleKeys($flags);
        $order = $registry->orderedModuleKeys($enabled);

        $this->assertSame(
            ['overview', 'on_page_seo', 'technical', 'performance', 'integrations', 'js_rendering', 'segmentation'],
            $order
        );
    }
}

