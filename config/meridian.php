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
    'base_currency_code' => 'EUR',

    /*
    |--------------------------------------------------------------------------
    | Default Language Code
    |--------------------------------------------------------------------------
    |
    | Define the default two-letter ISO 639-1 language code for the package.
    | This will be used for localizing data like country or currency names
    | when a specific locale is not provided.
    | Example: 'en', 'es', 'fr'.
    |
    */
    'default_language_code' => 'en',

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
                // Default path within storage/app. The meridian:update-geoip-db command will use this.
                'database_path' => 'meridian/geoip/GeoLite2-City.mmdb',
                // You can also use an absolute path if the database is stored elsewhere:
                // 'database_path' => database_path('maxmind/GeoLite2-City.mmdb'),
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
