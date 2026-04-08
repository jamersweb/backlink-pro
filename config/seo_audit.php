<?php

return [
    /*
    |--------------------------------------------------------------------------
    | JavaScript rendering (Playwright)
    |--------------------------------------------------------------------------
    */
    'js_render' => [
        'enabled_default' => env('JS_RENDER_ENABLED', true),
        'node_binary' => env('JS_RENDER_NODE', 'node'),
        'script_path' => env('JS_RENDER_SCRIPT', base_path('scripts/seo-audit-playwright-render.mjs')),
        'process_timeout_seconds' => (int) env('JS_RENDER_PROCESS_TIMEOUT', 180),
        'navigation_timeout_ms' => (int) env('JS_RENDER_NAV_TIMEOUT_MS', 30000),
        'settle_after_load_ms' => (int) env('JS_RENDER_SETTLE_MS', 1500),
        'max_urls_per_audit' => (int) env('JS_RENDER_MAX_PAGES', 200),
        'chunk_size' => (int) env('JS_RENDER_CHUNK_SIZE', 8),
        'block_heavy_assets' => filter_var(env('JS_RENDER_BLOCK_HEAVY_ASSETS', true), FILTER_VALIDATE_BOOLEAN),
        'content_divergence_ratio' => (float) env('JS_RENDER_CONTENT_DIVERGENCE', 0.35),
    ],

    /*
    |--------------------------------------------------------------------------
    | Spelling & grammar (heuristic, English-oriented)
    |--------------------------------------------------------------------------
    */
    'spelling' => [
        'dictionary_path' => env('SPELLING_DICTIONARY_PATH', resource_path('dictionaries/en_basic.txt')),
        'max_chars_stored' => (int) env('SPELLING_MAX_CHARS_STORED', 96000),
        'max_chars_analyzed' => (int) env('SPELLING_MAX_CHARS_ANALYZED', 96000),
        'max_issues_per_page' => (int) env('SPELLING_MAX_ISSUES_PER_PAGE', 28),
        'min_confidence' => (int) env('SPELLING_MIN_CONFIDENCE', 62),
        'high_confidence' => (int) env('SPELLING_HIGH_CONFIDENCE', 78),
        'brand_safe_terms' => [
            'seo', 'saas', 'api', 'css', 'js', 'html', 'json', 'xml', 'pdf', 'cta',
            'ui', 'ux', 'href', 'canonical', 'backlink', 'backlinks', 'sitemap',
            'backlinkpro', 'wordpress', 'woocommerce', 'shopify', 'cloudflare',
        ],
    ],

    'custom_audit' => [
        'body_html_max_chars' => (int) env('CUSTOM_AUDIT_BODY_HTML_MAX', 250000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Link metrics (optional crawl module — joins crawl with backlink DB data)
    |--------------------------------------------------------------------------
    |
    | driver: "null" | "domain_backlinks" (latest completed DomainBacklinkRun for
    |          Domain rows matching the audited host + audit user)
    |
    */
    'link_metrics' => [
        'driver' => env('SEO_AUDIT_LINK_METRICS_DRIVER', 'domain_backlinks'),
        'low_internal_links_max' => (int) env('SEO_AUDIT_LINK_LOW_INTERNAL_MAX', 3),
        'low_internal_min_equity_score' => (int) env('SEO_AUDIT_LINK_LOW_INTERNAL_MIN_EQUITY', 24),
        'max_score_penalty' => (int) env('SEO_AUDIT_LINK_MAX_PENALTY', 40),
        'priority_min_severity' => env('SEO_AUDIT_LINK_PRIORITY_MIN_SEVERITY', 'warning'),
        'tiers' => [
            'high_referring_domains' => (int) env('SEO_AUDIT_LINK_TIER_HIGH_RD', 50),
            'high_backlinks' => (int) env('SEO_AUDIT_LINK_TIER_HIGH_BL', 200),
            'medium_referring_domains' => (int) env('SEO_AUDIT_LINK_TIER_MED_RD', 10),
            'medium_backlinks' => (int) env('SEO_AUDIT_LINK_TIER_MED_BL', 40),
        ],
        'priority_score_bonus_by_tier' => [
            'high' => (int) env('SEO_AUDIT_LINK_BONUS_HIGH', 8),
            'medium' => (int) env('SEO_AUDIT_LINK_BONUS_MEDIUM', 4),
            'low' => 0,
        ],
    ],

    'forms_auth' => [
        'node_binary' => env('FORMS_AUTH_NODE', env('JS_RENDER_NODE', 'node')),
        'script_path' => env('FORMS_AUTH_SCRIPT', base_path('scripts/seo-audit-playwright-forms-auth.mjs')),
        'process_timeout_seconds' => (float) env('FORMS_AUTH_PROCESS_TIMEOUT', 120),
        'navigation_timeout_ms' => (int) env('FORMS_AUTH_NAV_TIMEOUT_MS', 45000),
        'settle_after_login_ms' => (int) env('FORMS_AUTH_SETTLE_MS', 2500),
        'max_attempts' => (int) env('FORMS_AUTH_MAX_ATTEMPTS', 2),
    ],
];
