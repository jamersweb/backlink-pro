<?php

return [
    'api_key' => env('SHOPIFY_API_KEY', ''),
    'api_secret' => env('SHOPIFY_API_SECRET', ''),
    'scopes' => env('SHOPIFY_SCOPES', 'read_products,write_products,read_content,write_content'),
    'api_version' => env('SHOPIFY_API_VERSION', '2024-01'),
];
