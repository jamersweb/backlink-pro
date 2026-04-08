<?php

namespace App\Services\SeoAudit;

class CrawlModuleConfig
{
    public const FLAG_MAP = [
        'js_rendering_enabled' => 'js_rendering',
        'near_duplicate_enabled' => 'near_duplicate_content',
        'spelling_grammar_enabled' => 'spelling_grammar',
        'custom_source_search_enabled' => 'custom_source_search',
        'custom_extraction_enabled' => 'custom_extraction',
        'forms_auth_enabled' => 'forms_auth_summary',
        'segmentation_enabled' => 'segmentation',
        'link_metrics_enabled' => 'link_metrics',
        'site_visualisation_enabled' => 'site_visualisations',
    ];

    public function normalizeFlags(array $input): array
    {
        $normalized = [];
        foreach (array_keys(self::FLAG_MAP) as $flag) {
            $normalized[$flag] = (bool) ($input[$flag] ?? false);
        }

        return $normalized;
    }

    public function enabledModuleKeys(array $flags): array
    {
        $enabled = [];
        foreach (self::FLAG_MAP as $flag => $moduleKey) {
            if (!empty($flags[$flag])) {
                $enabled[] = $moduleKey;
            }
        }

        return $enabled;
    }
}

