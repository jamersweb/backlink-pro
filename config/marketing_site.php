<?php

return [
    'brand' => [
        'name' => 'BacklinkPro',
        'title_template' => '%s — BacklinkPro',
        'default_title' => 'BacklinkPro — Guardrailed Backlink Automation',
        'default_description' => 'Guardrailed backlink workflows with approvals, evidence logs, and monitoring transparency.',
    ],
    'urls' => [
        'app_url' => env('APP_URL', 'http://localhost'),
    ],
    'contacts' => [
        'support' => 'support@backlinkpro.example',
        'privacy' => 'privacy@backlinkpro.example',
        'security' => 'security@backlinkpro.example',
    ],
    'social' => [
        'x' => null,
        'linkedin' => null,
        'youtube' => null,
    ],
    'seo' => [
        'og_image' => '/images/og-default.png',
        'twitter_card' => 'summary_large_image',
    ],
    'nav' => [
        ['label' => 'Product', 'href' => '/product'],
        ['label' => 'How it works', 'href' => '/how-it-works'],
        ['label' => 'Workflows', 'href' => '/workflows'],
        ['label' => 'Pricing', 'href' => '/pricing'],
        ['label' => 'Case Studies', 'href' => '/case-studies'],
        ['label' => 'Resources', 'href' => '/resources'],
    ],
    'nav_secondary' => [
        ['label' => 'Security', 'href' => '/security'],
        ['label' => 'Partners', 'href' => '/partners'],
        ['label' => 'About', 'href' => '/about'],
        ['label' => 'Contact', 'href' => '/contact'],
    ],
    'legal' => [
        ['label' => 'Privacy Policy', 'href' => '/privacy-policy'],
        ['label' => 'Terms', 'href' => '/terms'],
    ],
    'analytics' => [
        'gtm_id' => env('GTM_ID'),
    ],
    'maintenance' => [
        'enabled' => env('MARKETING_BANNER_ENABLED', false),
        'style' => env('MARKETING_BANNER_STYLE', 'info'), // info|warning|success
        'message' => env('MARKETING_BANNER_MESSAGE', 'We are improving BacklinkPro. Some features may be temporarily unavailable.'),
        'cta_label' => env('MARKETING_BANNER_CTA_LABEL', 'Status'),
        'cta_href' => env('MARKETING_BANNER_CTA_HREF', '/contact'),
    ],
];
