<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', env('APP_URL') . '/auth/google/callback'),
        'redirect_uri' => env('GOOGLE_GMAIL_REDIRECT_URI', env('APP_URL') . '/gmail/oauth/callback'),
        'pagespeed_api_key' => env('GOOGLE_PAGESPEED_API_KEY', env('PAGESPEED_API_KEY')),
        'pagespeed_global_per_min' => env('GOOGLE_PAGESPEED_GLOBAL_PER_MIN', 60),
        'pagespeed_timeout_seconds' => env('GOOGLE_PAGESPEED_TIMEOUT_SECONDS', 90),
        'pagespeed_connect_timeout_seconds' => env('GOOGLE_PAGESPEED_CONNECT_TIMEOUT_SECONDS', 15),
        'pagespeed_retry_times' => env('GOOGLE_PAGESPEED_RETRY_TIMES', 2),
        'pagespeed_retry_sleep_ms' => env('GOOGLE_PAGESPEED_RETRY_SLEEP_MS', 2000),
        'crux_api_key' => env('GOOGLE_CRUX_API_KEY'),
    ],

    'github' => [
        'client_id' => env('GITHUB_CLIENT_ID'),
        'client_secret' => env('GITHUB_CLIENT_SECRET'),
        'redirect' => env('GITHUB_REDIRECT_URI', env('APP_URL') . '/auth/github/callback'),
    ],

    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('FACEBOOK_REDIRECT_URI', env('APP_URL') . '/auth/facebook/callback'),
    ],

    'microsoft' => [
        'client_id' => env('MICROSOFT_CLIENT_ID'),
        'client_secret' => env('MICROSOFT_CLIENT_SECRET'),
        'redirect' => env('MICROSOFT_REDIRECT_URI', env('APP_URL') . '/auth/microsoft/callback'),
        'tenant' => env('MICROSOFT_TENANT_ID', 'common'),
    ],

    'apple' => [
        'client_id' => env('APPLE_CLIENT_ID'),
        'client_secret' => env('APPLE_CLIENT_SECRET'),
        'redirect' => env('APPLE_REDIRECT_URI', env('APP_URL') . '/auth/apple/callback'),
        'team_id' => env('APPLE_TEAM_ID'),
        'key_id' => env('APPLE_KEY_ID'),
        'private_key' => env('APPLE_PRIVATE_KEY'),
    ],

    'google_seo' => [
        'redirect_uri' => env('GOOGLE_SEO_REDIRECT_URI', env('APP_URL') . '/seo/google/callback'),
    ],

    'google_ads' => [
        'api_key' => env('GOOGLE_ADS_API_KEY'),
        'developer_token' => env('GOOGLE_ADS_DEVELOPER_TOKEN'),
        'customer_id' => env('GOOGLE_ADS_CUSTOMER_ID'),
        'login_customer_id' => env('GOOGLE_ADS_LOGIN_CUSTOMER_ID'),
        'access_token' => env('GOOGLE_ADS_ACCESS_TOKEN'),
    ],

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'llm' => [
        'provider' => env('LLM_PROVIDER', 'openai'), // 'openai', 'deepseek', or 'anthropic'
        'api_key' => env('LLM_API_KEY'), // Fallback - prefer Settings table via Admin UI
        'openai_url' => env('OPENAI_API_URL', 'https://api.openai.com/v1'),
        'deepseek_url' => env('DEEPSEEK_API_URL', 'https://api.deepseek.com/v1'),
        'anthropic_url' => env('ANTHROPIC_API_URL', 'https://api.anthropic.com/v1'),
    ],

    'captcha' => [
        'provider' => env('CAPTCHA_PROVIDER', '2captcha'), // '2captcha' or 'anticaptcha'
        '2captcha' => [
            'api_key' => env('2CAPTCHA_API_KEY'),
            'api_url' => env('2CAPTCHA_API_URL', 'https://2captcha.com/in.php'),
        ],
        'anticaptcha' => [
            'api_key' => env('ANTICAPTCHA_API_KEY'),
            'api_url' => env('ANTICAPTCHA_API_URL', 'https://api.anti-captcha.com'),
        ],
    ],

    'backlinks' => [
        'provider' => env('BACKLINK_PROVIDER', 'dataforseo'),
        'dataforseo' => [
            'login' => env('DATAFORSEO_LOGIN'),
            'password' => env('DATAFORSEO_PASSWORD'),
        ],
        'suspicious_tlds' => [
            '.xyz', '.top', '.click', '.download', '.online', '.site', '.website',
        ],
    ],

];



