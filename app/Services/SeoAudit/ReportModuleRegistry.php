<?php

namespace App\Services\SeoAudit;

class ReportModuleRegistry
{
    public const CORE_MODULES = [
        'overview' => 'Overview',
        'on_page_seo' => 'On-Page SEO',
        'technical' => 'Technical SEO',
        'performance' => 'Performance',
        'integrations' => 'Integrations',
    ];

    public const OPTIONAL_MODULES = [
        'js_rendering' => 'JavaScript Rendering',
        'near_duplicate_content' => 'Near Duplicate Content',
        'segmentation' => 'Segmentation',
        'site_visualisations' => 'Site Visualisations',
        'spelling_grammar' => 'Spelling & Grammar',
        'custom_source_search' => 'Custom Source Code Search',
        'custom_extraction' => 'Custom Extraction',
        'forms_auth_summary' => 'Forms Authentication Summary',
        'link_metrics' => 'Link Metrics',
    ];

    public function titlesByKey(): array
    {
        return array_merge(self::CORE_MODULES, self::OPTIONAL_MODULES);
    }

    public function orderedModuleKeys(array $enabledOptionalKeys = []): array
    {
        $keys = array_keys(self::CORE_MODULES);

        foreach (array_keys(self::OPTIONAL_MODULES) as $optionalKey) {
            if (in_array($optionalKey, $enabledOptionalKeys, true)) {
                $keys[] = $optionalKey;
            }
        }

        return $keys;
    }
}

