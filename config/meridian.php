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
    'base_currency_code' => env('MERIDIAN_BASE_CURRENCY_CODE', 'USD'),

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
    |--------------------------------------------------------------------------
    | Active Languages
    |--------------------------------------------------------------------------
    |
    | An array of language codes that are currently active in the application.
    | Only these languages will be available for selection or displayed in the interface.
    | If empty, all languages will be available.
    | Example: ['en', 'ru']
    |
    */
    'active_languages' => [],

    /*
    |--------------------------------------------------------------------------
    | Active Currencies
    |--------------------------------------------------------------------------
    |
    | An array of ISO 4217 currency codes that are available for selection.
    | If empty, Meridian falls back to an internal default list.
    | Example: ['USD', 'EUR', 'GBP']
    |
    */
    'active_currencies' => [],

    /*
    |--------------------------------------------------------------------------
    | Target Currency Codes For Exchange Rates
    |--------------------------------------------------------------------------
    |
    | An array of currency codes that are available for updating exchange rates.
    | If empty, all available currencies from the provider will be updated.
    | Example: ['EUR', 'GBP', 'JPY']
    |
    */
    'target_currency_codes' => [],

    /*
    |--------------------------------------------------------------------------
    | Default Country ISO Code
    |--------------------------------------------------------------------------
    |
    | The default country ISO 3166-1 alpha-2 code.
    |
    */
    'default_country_iso_code' => env('MERIDIAN_DEFAULT_COUNTRY_ISO_CODE', 'US'),

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
        'default_driver' => env('MERIDIAN_GEOIP_DRIVER', 'maxmind_database'),

        'drivers' => [
            'maxmind_database' => [
                'license_key' => env('MAXMIND_LICENSE_KEY'),
                'account_id' => env('MAXMIND_ACCOUNT_ID'),
                'database_path' => env('MERIDIAN_GEOIP_DATABASE_PATH', 'meridian'),
                'database_filename' => env('MERIDIAN_GEOIP_DATABASE_FILENAME', 'GeoLite2-City.mmdb'),
                'editions' => [
                    env('MAXMIND_EDITION', 'GeoLite2-City'),
                ],
            ],
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
    | HTTP Timeout
    |--------------------------------------------------------------------------
    |
    | The default timeout in seconds for HTTP requests to external APIs.
    |
    */
    'http_timeout' => env('MERIDIAN_HTTP_TIMEOUT', 10),

    /*
    |--------------------------------------------------------------------------
    | Exchange Rate Provider Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the API endpoints and settings for exchange rate providers.
    |
    */
    'exchange_rates' => [
        'default_provider' => env('MERIDIAN_EXCHANGE_RATE_PROVIDER', 'frankfurter'),

        'providers' => [
            'frankfurter' => [
                'api_url' => env('FRANKFURTER_API_URL', 'https://api.frankfurter.dev/v1'),
            ],
        ],

        // Number of past days of exchange rates to fetch if the provider supports it.
        'historical_days' => 90,
    ],
];
