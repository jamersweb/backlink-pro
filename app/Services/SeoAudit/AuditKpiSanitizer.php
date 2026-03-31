<?php

namespace App\Services\SeoAudit;

class AuditKpiSanitizer
{
    public function sanitize(array $kpis): array
    {
        return $this->sanitizeValue($kpis, 'root');
    }

    protected function sanitizeValue(mixed $value, string $key): mixed
    {
        if (!is_array($value)) {
            return is_string($value) ? $this->truncateString($value, $key) : $value;
        }

        $isList = array_is_list($value);
        $items = $isList ? array_slice($value, 0, $this->listLimit($key)) : $value;
        $sanitized = [];

        foreach ($items as $itemKey => $itemValue) {
            $childKey = is_string($itemKey) ? $itemKey : $key;
            $sanitized[$itemKey] = $this->sanitizeValue($itemValue, $childKey);
        }

        return $sanitized;
    }

    protected function listLimit(string $key): int
    {
        return match ($key) {
            'daily' => 14,
            'top_pages',
            'top_queries',
            'heavy_assets',
            'broken_links_examples',
            'redirect_chains_examples',
            'non_200_pages',
            'duplicate_titles_table',
            'missing_meta_table',
            'missing_h1_table',
            'security_headers_list' => 8,
            'top_keywords',
            'phrases',
            'mobile_opportunities',
            'desktop_opportunities',
            'detected_technologies' => 6,
            default => 10,
        };
    }

    protected function truncateString(string $value, string $key): string
    {
        $value = trim($value);
        $limit = match ($key) {
            'url',
            'asset_url',
            'from_url',
            'to_url',
            'canonical_url',
            'robots_txt_url',
            'sitemap_url',
            'llms_txt_url',
            'facebook_url',
            'x_url',
            'instagram_url',
            'linkedin_url',
            'youtube_url',
            'site_url' => 500,
            'title_tag_text',
            'title',
            'name',
            'keyword',
            'query' => 180,
            'meta_description_text',
            'meta_description',
            'description',
            'recommendation',
            'fix_steps',
            'dmarc_record',
            'spf_record' => 320,
            default => 220,
        };

        if (mb_strlen($value) <= $limit) {
            return $value;
        }

        return rtrim(mb_substr($value, 0, $limit - 3)) . '...';
    }
}
