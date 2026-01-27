<?php

return [
    'static' => [
        ['path' => '/', 'name' => 'marketing.home', 'priority' => 1.0, 'changefreq' => 'weekly'],
        ['path' => '/product', 'name' => 'marketing.product', 'priority' => 0.9, 'changefreq' => 'monthly'],
        ['path' => '/how-it-works', 'name' => 'marketing.howItWorks', 'priority' => 0.9, 'changefreq' => 'monthly'],
        ['path' => '/workflows', 'name' => 'marketing.workflows.index', 'priority' => 0.9, 'changefreq' => 'monthly'],
        ['path' => '/pricing', 'name' => 'marketing.pricing', 'priority' => 0.9, 'changefreq' => 'monthly'],
        ['path' => '/case-studies', 'name' => 'marketing.caseStudies.index', 'priority' => 0.8, 'changefreq' => 'monthly'],
        ['path' => '/resources', 'name' => 'marketing.resources', 'priority' => 0.7, 'changefreq' => 'monthly'],
        ['path' => '/security', 'name' => 'marketing.security', 'priority' => 0.7, 'changefreq' => 'monthly'],
        ['path' => '/partners', 'name' => 'marketing.partners', 'priority' => 0.6, 'changefreq' => 'monthly'],
        ['path' => '/about', 'name' => 'marketing.about', 'priority' => 0.6, 'changefreq' => 'yearly'],
        ['path' => '/contact', 'name' => 'marketing.contact', 'priority' => 0.8, 'changefreq' => 'monthly'],
        ['path' => '/free-backlink-plan', 'name' => 'marketing.freePlan', 'priority' => 0.9, 'changefreq' => 'weekly'],
        ['path' => '/privacy-policy', 'name' => 'marketing.privacy', 'priority' => 0.3, 'changefreq' => 'yearly'],
        ['path' => '/terms', 'name' => 'marketing.terms', 'priority' => 0.3, 'changefreq' => 'yearly'],
    ],
    // dynamic sources declared here so sitemap stays consistent:
    'dynamic' => [
        'workflows' => [
            'base' => '/workflows/',
            'source' => 'marketing_workflows.items', // config path
            'slugKey' => 'slug',
            'priority' => 0.6,
            'changefreq' => 'monthly',
        ],
        'case_studies' => [
            'base' => '/case-studies/',
            'source' => 'marketing_case_studies.items',
            'slugKey' => 'slug',
            'priority' => 0.6,
            'changefreq' => 'monthly',
        ],
    ],
];
