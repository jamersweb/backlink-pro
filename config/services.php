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
        'redirect_uri' => env('GOOGLE_REDIRECT_URI', env('APP_URL') . '/gmail/oauth/callback'),
    ],

    'google_seo' => [
        'redirect_uri' => env('GOOGLE_SEO_REDIRECT_URI', env('APP_URL') . '/seo/google/callback'),
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
