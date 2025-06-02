<?php

declare(strict_types=1);

// Default configuration values for the Meridian package.
// These values are merged with the user's published config.
return [

    /*
    |--------------------------------------------------------------------------
    | Base Currency Code
    |--------------------------------------------------------------------------
    |
    | Specify the default three-letter ISO 4217 currency code that will be
    | used as the base for currency conversions and other monetary operations.
    | Example: 'USD', 'EUR', 'GBP'.
    |
    */
    'base_currency_code' => 'USD',

    /*
    |--------------------------------------------------------------------------
    | Language Settings
    |--------------------------------------------------------------------------
    |
    | default_language_code: The default language code (e.g., 'en', 'ru') for the application.
    |   This will be used if no language is set by the user or detected from the browser.
    |
    */
    'default_language_code' => env('MERIDIAN_DEFAULT_LANGUAGE_CODE', 'en'),

    /*
    |
    | active_languages: An array of language codes that are currently active in the application.
    |   Only these languages will be available for selection or displayed in the interface.
    |   If empty, all languages will be available.
    |   Example: ['en', 'ru']
    |
    */
    'active_languages' => [],

    /*
    |--------------------------------------------------------------------------
    | Geolocation Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for the IP geolocation services. You can specify the default
    | driver and configure credentials or paths for each supported driver.
    |
    */
    'geolocation' => [
        'default_driver' => 'maxmind_database', // or 'ip_api_com', 'ipstack', etc.

        'drivers' => [
            'maxmind_database' => [
                'license_key' => env('MAXMIND_LICENSE_KEY'),
                'account_id' => env('MAXMIND_ACCOUNT_ID'),
                'database_path' => 'meridian/geoip',
                'editions' => [
                    env('MAXMIND_EDITION', 'GeoLite2-City'),
                ],
            ],

            'ip_api_com' => [
                'api_key' => env('IP_API_COM_KEY'), // Optional, for pro version
                'base_url' => 'http://ip-api.com/json/',
            ],

            // Example for ipstack - requires an API key
            // 'ipstack' => [
            //     'api_key' => env('IPSTACK_API_KEY'),
            //     'base_url' => 'http://api.ipstack.com/',
            //     'pro_version' => false, // Set to true if using a paid plan for HTTPS
            // ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Session Storage for Geolocation Data
        |--------------------------------------------------------------------------
        |
        | Configure if and how the resolved geolocation data should be stored
        | in the user's session for subsequent requests.
        |
        */
        'session' => [
            // The session key under which the LocationData will be stored.
            'key' => env('MERIDIAN_GEOLOCATION_SESSION_KEY', 'meridian_location'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Lifetimes
    |--------------------------------------------------------------------------
    |
    | Configure the duration (in seconds) for which various data retrieved
    | by the package will be cached. Setting a longer duration can improve
    | performance but may result in stale data.
    |
    */
    'cache_lifetimes' => [
        'countries' => 3600, // 1 hour
        'currencies' => 3600, // 1 hour
        'languages' => 3600, // 1 hour
        'exchange_rates' => 1800, // 30 minutes
        'geolocation' => 300,  // 5 minutes for IP-based lookups
    ],

    /*
    |--------------------------------------------------------------------------
    | Exchange Rate Providers Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the API endpoints and any other settings for exchange rate
    | providers. The 'frankfurter' key is used by default.
    |
    */
    'exchange_rate_providers' => [
        'frankfurter' => [
            'api_url' => env('FRANKFURTER_API_URL', 'https://api.frankfurter.dev/v1'),
            // Add other provider-specific settings here if needed, e.g., API key
        ],
        // Example for another provider:
        // 'other_provider' => [
        //     'api_url' => env('OTHER_PROVIDER_API_URL', 'https://api.exchangerate.host'),
        //     'api_key' => env('OTHER_PROVIDER_API_KEY'),
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Exchange Rate Providers
    |--------------------------------------------------------------------------
    |
    | Configure the services used for fetching currency exchange rates.
    | Specify the default provider and any necessary API keys or settings.
    |
    */
    'exchange_rates' => [
        'default_provider' => 'frankfurter_app', // Example: 'frankfurter_app', 'european_central_bank'

        'providers' => [
            'frankfurter_app' => [
                'base_url' => 'https://api.frankfurter.app',
                // No API key needed for frankfurter.app
            ],

            'european_central_bank' => [
                // URL for ECB's daily XML feed
                'url' => 'https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml',
            ],

            // Example for another provider (e.g., exchangerate-api.com)
            // 'exchangerate_api' => [
            // 'api_key' => env('EXCHANGERATE_API_KEY'),
            // 'base_url' => 'https://v6.exchangerate-api.com/v6',
            // ],
        ],

        // Define how many past days of exchange rates to fetch and store if the provider supports it.
        // This is relevant for the `meridian:update-exchange-rates` command.
        'historical_days' => 90, // Fetch rates for the last 90 days
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Overrides
    |--------------------------------------------------------------------------
    |
    | If you need to use your own models that extend the package's default
    | models, you can specify them here. This allows for greater
    | customization and integration with your application's logic.
    |
    */
    'models' => [
        // 'continent' => \App\Models\Meridian\Continent::class,
        // 'country' => \App\Models\Meridian\Country::class,
        // 'currency' => \App\Models\Meridian\Currency::class,
        // 'exchange_rate' => \App\Models\Meridian\ExchangeRate::class,
        // 'language' => \App\Models\Meridian\Language::class,
    ],

];
