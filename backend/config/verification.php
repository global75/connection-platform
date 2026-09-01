<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Employer verification
    |--------------------------------------------------------------------------
    */

    'employer' => [
        // Employers must register on a company domain. Anything on this list is
        // refused outright — a free mailbox proves nothing about a business.
        'blocked_email_domains' => [
            'gmail.com', 'googlemail.com', 'yahoo.com', 'ymail.com', 'hotmail.com',
            'outlook.com', 'live.com', 'msn.com', 'aol.com', 'icloud.com', 'me.com',
            'proton.me', 'protonmail.com', 'gmx.com', 'mail.com', 'zoho.com',
            'yandex.com', 'tutanota.com', 'fastmail.com',
            // Disposable providers.
            'mailinator.com', 'guerrillamail.com', '10minutemail.com', 'tempmail.com',
            'throwaway.email', 'sharklasers.com', 'yopmail.com',
        ],

        // Prefix for the TXT record an employer publishes to prove domain control.
        'dns_token_prefix' => 'remotearena-verification',

        // How long a domain verification stands before it must be re-proved.
        'domain_ttl_days' => (int) env('VERIFY_DOMAIN_TTL_DAYS', 365),

        // Gate job posting on a verified company.
        'require_for_posting' => env('VERIFY_REQUIRE_FOR_POSTING', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Candidate verification
    |--------------------------------------------------------------------------
    */

    'candidate' => [
        // A GitHub account younger than this, or with fewer public repos, is
        // reported but not treated as proof of a development history.
        'github_min_account_age_days' => (int) env('VERIFY_GITHUB_MIN_AGE_DAYS', 180),
        'github_min_public_repos'     => (int) env('VERIFY_GITHUB_MIN_REPOS', 3),
        'identity_ttl_days'           => (int) env('VERIFY_IDENTITY_TTL_DAYS', 730),
    ],

    /*
    |--------------------------------------------------------------------------
    | Third-party providers
    |--------------------------------------------------------------------------
    |
    | Each is inert until credentials are present: the checker reports itself
    | unavailable and the API returns 503 rather than pretending to verify.
    |
    */

    'providers' => [
        'company_registry' => [
            'driver'   => env('VERIFY_REGISTRY_DRIVER', 'opencorporates'),
            'api_key'  => env('OPENCORPORATES_API_KEY'),
            'base_url' => env('OPENCORPORATES_BASE_URL', 'https://api.opencorporates.com/v0.4'),
        ],

        'government_id' => [
            'driver'     => env('VERIFY_IDENTITY_DRIVER', 'stripe_identity'),
            'secret_key' => env('STRIPE_SECRET'),
        ],

        'github' => [
            'client_id'     => env('GITHUB_CLIENT_ID'),
            'client_secret' => env('GITHUB_CLIENT_SECRET'),
            'api_url'       => env('GITHUB_API_URL', 'https://api.github.com'),
        ],

        'linkedin' => [
            'client_id'     => env('LINKEDIN_CLIENT_ID'),
            'client_secret' => env('LINKEDIN_CLIENT_SECRET'),
        ],
    ],

];
