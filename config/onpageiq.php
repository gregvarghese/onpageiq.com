<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Browser Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the browser service used for rendering pages. Supports local
    | Playwright installation or external services like Browserless.
    |
    */

    'browser' => [
        'driver' => env('BROWSER_DRIVER', 'local'),

        'local' => [
            'executable_path' => env('PLAYWRIGHT_EXECUTABLE'),
            'timeout' => env('BROWSER_TIMEOUT', 30000),
        ],

        'browserless' => [
            'url' => env('BROWSERLESS_URL', 'wss://chrome.browserless.io'),
            'token' => env('BROWSERLESS_TOKEN'),
            'timeout' => env('BROWSER_TIMEOUT', 30000),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | OpenAI Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the AI models used for content analysis.
    |
    */

    'ai' => [
        'quick_model' => env('AI_QUICK_MODEL', 'gpt-4o-mini'),
        'deep_model' => env('AI_DEEP_MODEL', 'gpt-4o'),
        'max_tokens' => env('AI_MAX_TOKENS', 4096),
        'chunk_size' => env('AI_CHUNK_SIZE', 50000),
    ],

    /*
    |--------------------------------------------------------------------------
    | LanguageTool Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the LanguageTool server for additional grammar/spell checking.
    | Run via Docker: docker compose up -d languagetool
    |
    */

    'languagetool' => [
        'enabled' => env('LANGUAGETOOL_ENABLED', false),
        'url' => env('LANGUAGETOOL_URL', 'http://localhost:8082'),
        'timeout' => env('LANGUAGETOOL_TIMEOUT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Scanning Configuration
    |--------------------------------------------------------------------------
    |
    | Configure scanning behavior and limits.
    |
    */

    'scanning' => [
        'parallel_limit' => env('SCAN_PARALLEL_LIMIT', 5),
        'large_page_threshold' => env('SCAN_LARGE_PAGE_THRESHOLD', 50000),
        'screenshot_quality' => env('SCAN_SCREENSHOT_QUALITY', 80),
        'capture_issue_screenshots' => env('SCAN_CAPTURE_ISSUE_SCREENSHOTS', true),
        'max_issue_screenshots' => env('SCAN_MAX_ISSUE_SCREENSHOTS', 20),
    ],

    /*
    |--------------------------------------------------------------------------
    | Credit Configuration
    |--------------------------------------------------------------------------
    |
    | Configure credit costs for different operations.
    |
    */

    'credits' => [
        'quick_scan' => 1,
        'deep_scan' => 3,
        'large_page_multiplier' => 2,
    ],

    /*
    |--------------------------------------------------------------------------
    | Export Configuration
    |--------------------------------------------------------------------------
    |
    | Configure PDF export settings.
    |
    */

    'export' => [
        'node_path' => env('NODE_PATH', '/usr/local/bin/node'),
        'npm_path' => env('NPM_PATH', '/usr/local/bin/npm'),
        'cleanup_days' => env('EXPORT_CLEANUP_DAYS', 7),
    ],

    /*
    |--------------------------------------------------------------------------
    | Subscription Tiers Configuration
    |--------------------------------------------------------------------------
    |
    | Define the subscription tiers with their features and limits.
    |
    */

    'tiers' => [
        'free' => [
            'name' => 'Free',
            'price_monthly' => 0,
            'stripe_price_id' => null,
            'credits_monthly' => 0, // One-time 5 credits on signup
            'credits_onetime' => 5,
            'projects_limit' => 1,
            'team_size' => 1,
            'checks' => ['spelling'],
            'features' => [
                'basic_reports' => true,
                'pdf_export' => false,
                'api_access' => false,
                'team_features' => false,
                'priority_support' => false,
                'sso' => false,
            ],
            'history_days' => 30,
            'queue_priority' => 'low',
        ],

        'pro' => [
            'name' => 'Pro',
            'price_monthly' => 2900, // $29.00 in cents
            'stripe_price_id' => env('STRIPE_PRICE_PRO_MONTHLY'),
            'credits_monthly' => 100,
            'credits_onetime' => 0,
            'projects_limit' => null, // Unlimited
            'team_size' => 1,
            'checks' => ['spelling', 'grammar', 'seo', 'readability'],
            'features' => [
                'basic_reports' => true,
                'pdf_export' => true,
                'api_access' => true,
                'team_features' => false,
                'priority_support' => false,
                'sso' => false,
            ],
            'history_days' => null, // Unlimited
            'queue_priority' => 'default',
        ],

        'team' => [
            'name' => 'Team',
            'price_monthly' => 7900, // $79.00 in cents
            'stripe_price_id' => env('STRIPE_PRICE_TEAM_MONTHLY'),
            'credits_monthly' => 500,
            'credits_onetime' => 0,
            'projects_limit' => null, // Unlimited
            'team_size' => 10,
            'checks' => ['spelling', 'grammar', 'seo', 'readability'],
            'features' => [
                'basic_reports' => true,
                'pdf_export' => true,
                'api_access' => true,
                'team_features' => true,
                'priority_support' => true,
                'sso' => false,
            ],
            'history_days' => null, // Unlimited
            'queue_priority' => 'default',
        ],

        'enterprise' => [
            'name' => 'Enterprise',
            'price_monthly' => null, // Custom pricing
            'stripe_price_id' => env('STRIPE_PRICE_ENTERPRISE_MONTHLY'),
            'credits_monthly' => null, // Custom
            'credits_onetime' => 0,
            'projects_limit' => null, // Unlimited
            'team_size' => null, // Unlimited
            'checks' => ['spelling', 'grammar', 'seo', 'readability'],
            'features' => [
                'basic_reports' => true,
                'pdf_export' => true,
                'api_access' => true,
                'team_features' => true,
                'priority_support' => true,
                'sso' => true,
            ],
            'history_days' => null, // Unlimited
            'queue_priority' => 'high',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Credit Packs
    |--------------------------------------------------------------------------
    |
    | Define purchasable credit packs.
    |
    */

    'credit_packs' => [
        'small' => [
            'name' => '50 Credits',
            'credits' => 50,
            'price' => 999, // $9.99 in cents
            'stripe_price_id' => env('STRIPE_PRICE_CREDITS_50'),
        ],
        'medium' => [
            'name' => '150 Credits',
            'credits' => 150,
            'price' => 2499, // $24.99 in cents (save ~17%)
            'stripe_price_id' => env('STRIPE_PRICE_CREDITS_150'),
        ],
        'large' => [
            'name' => '500 Credits',
            'credits' => 500,
            'price' => 6999, // $69.99 in cents (save ~30%)
            'stripe_price_id' => env('STRIPE_PRICE_CREDITS_500'),
        ],
    ],

];
