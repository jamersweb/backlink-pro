<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Feature flags (env-based; default OFF for risky/new features)
    |--------------------------------------------------------------------------
    */
    'edge_proxy' => env('FEATURE_EDGE_PROXY', false),
    'shopify_oauth' => env('FEATURE_SHOPIFY_OAUTH', false),
    'system_health' => env('FEATURE_SYSTEM_HEALTH', false),
    'async_status' => env('FEATURE_ASYNC_STATUS', true),
    'content_decay' => env('FEATURE_CONTENT_DECAY', false),
    'cannibalization' => env('FEATURE_CANNIBALIZATION', false),
];
