<?php

return [
    'free' => [
        'pages_limit' => 5,
        'crawl_depth' => 1,
        'lighthouse_pages' => 1,
        'pagespeed_runs_per_day' => 2,
        'pdf_export' => false, // Watermarked
        'public_share' => true,
        'widget' => true,
        'audits_per_day' => 10,
        'white_label' => false,
        'custom_domain' => false,
        'keywords_tracked' => 20,
        'rank_check_frequency' => 'weekly',
        'data_retention_days' => 90,
        'monthly_report' => false,
    ],

    'pro' => [
        'pages_limit' => 50,
        'crawl_depth' => 3,
        'lighthouse_pages' => 5,
        'pagespeed_runs_per_day' => 50,
        'pdf_export' => true,
        'public_share' => true,
        'widget' => true,
        'audits_per_day' => 200,
        'white_label' => false,
        'custom_domain' => false,
        'keywords_tracked' => 200,
        'rank_check_frequency' => 'daily',
        'data_retention_days' => 365,
        'monthly_report' => true,
    ],

    'agency' => [
        'pages_limit' => 200,
        'crawl_depth' => 5,
        'lighthouse_pages' => 10,
        'pagespeed_runs_per_day' => 200,
        'pdf_export' => true,
        'public_share' => true,
        'widget' => true,
        'audits_per_day' => 1000,
        'white_label' => true,
        'custom_domain' => true,
        'keywords_tracked' => 1000,
        'rank_check_frequency' => 'daily',
        'data_retention_days' => 730,
        'monthly_report' => true,
    ],
];
