<?php

return [
    /*
    |--------------------------------------------------------------------------
    | LLM Provider Configuration
    |--------------------------------------------------------------------------
    |
    | Configure which LLM provider to use and its API credentials.
    | Supported providers: openai, deepseek, anthropic
    |
    */

    'provider' => env('LLM_PROVIDER', 'openai'),

    'api_key' => env('LLM_API_KEY', env('OPENAI_API_KEY')),

    'model' => env('LLM_MODEL', 'gpt-4o-mini'),

    /*
    |--------------------------------------------------------------------------
    | Provider URLs
    |--------------------------------------------------------------------------
    */

    'openai_url' => env('OPENAI_URL', 'https://api.openai.com'),
    'deepseek_url' => env('DEEPSEEK_URL', 'https://api.deepseek.com'),
    'anthropic_url' => env('ANTHROPIC_URL', 'https://api.anthropic.com'),

    /*
    |--------------------------------------------------------------------------
    | Default Options
    |--------------------------------------------------------------------------
    */

    'default_temperature' => 0.3,
    'default_max_tokens' => 2000,
];
