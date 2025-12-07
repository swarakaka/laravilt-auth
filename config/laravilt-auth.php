<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Guard
    |--------------------------------------------------------------------------
    |
    | This option defines the default authentication guard that will be used
    | by the auth package.
    |
    */

    'guard' => 'web',

    /*
    |--------------------------------------------------------------------------
    | Authentication Methods
    |--------------------------------------------------------------------------
    |
    | Configure which authentication methods are enabled for your application.
    |
    */

    'methods' => [
        'email' => true,
        'phone' => false,
        'social' => false,
        'passwordless' => false,
        'webauthn' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Social Authentication Providers
    |--------------------------------------------------------------------------
    |
    | Configure which social authentication providers are enabled.
    |
    */

    'social' => [
        'providers' => [
            'google' => [
                'client_id' => env('GOOGLE_CLIENT_ID'),
                'client_secret' => env('GOOGLE_CLIENT_SECRET'),
                'redirect' => env('GOOGLE_REDIRECT_URI', env('APP_URL').'/user/auth/google/callback'),
            ],

            'github' => [
                'client_id' => env('GITHUB_CLIENT_ID'),
                'client_secret' => env('GITHUB_CLIENT_SECRET'),
                'redirect' => env('GITHUB_REDIRECT_URI', env('APP_URL').'/user/auth/github/callback'),
            ],

            'facebook' => [
                'client_id' => env('FACEBOOK_CLIENT_ID'),
                'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
                'redirect' => env('FACEBOOK_REDIRECT_URI', env('APP_URL').'/user/auth/facebook/callback'),
            ],

            'twitter' => [
                'client_id' => env('TWITTER_CLIENT_ID'),
                'client_secret' => env('TWITTER_CLIENT_SECRET'),
                'redirect' => env('TWITTER_REDIRECT_URI', env('APP_URL').'/user/auth/twitter/callback'),
            ],

            'linkedin' => [
                'client_id' => env('LINKEDIN_CLIENT_ID'),
                'client_secret' => env('LINKEDIN_CLIENT_SECRET'),
                'redirect' => env('LINKEDIN_REDIRECT_URI', env('APP_URL').'/user/auth/linkedin/callback'),
            ],

            'discord' => [
                'client_id' => env('DISCORD_CLIENT_ID'),
                'client_secret' => env('DISCORD_CLIENT_SECRET'),
                'redirect' => env('DISCORD_REDIRECT_URI', env('APP_URL').'/user/auth/discord/callback'),
            ],

            'jira' => [
                'client_id' => env('JIRA_CLIENT_ID'),
                'client_secret' => env('JIRA_CLIENT_SECRET'),
                'redirect' => env('JIRA_REDIRECT_URI', env('APP_URL').'/user/auth/jira/callback'),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Two-Factor Authentication
    |--------------------------------------------------------------------------
    |
    | Configure two-factor authentication settings.
    |
    */

    'two_factor' => [
        'enabled' => false,
        'methods' => ['totp', 'sms', 'email'],
        'issuer' => env('APP_NAME', 'Laravilt'),
    ],

    /*
    |--------------------------------------------------------------------------
    | OTP Settings
    |--------------------------------------------------------------------------
    |
    | Configure OTP (One-Time Password) settings.
    |
    */

    'otp' => [
        'length' => 6,
        'expiry' => 5, // minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific authentication features.
    |
    */

    'features' => [
        'registration' => true,
        'email_verification' => true,
        'password_reset' => true,
        'profile' => true,
        'sessions' => true,
        'api_tokens' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    |
    | Configure routing for authentication pages.
    |
    */

    'routes' => [
        'prefix' => 'auth',
        'middleware' => ['web'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Views
    |--------------------------------------------------------------------------
    |
    | Configure view settings.
    |
    */

    'views' => [
        'theme' => 'default', // default, dark
        'rtl' => false,
    ],
];
