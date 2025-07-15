[![Latest Version on Packagist](https://img.shields.io/packagist/v/denprog/meridian.svg?style=flat-square)](https://packagist.org/packages/denprog/meridian)
[![Total Downloads](https://img.shields.io/packagist/dt/denprog/meridian.svg?style=flat-square)](https://packagist.org/packages/denprog/meridian)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/github/actions/workflow/status/denprog/meridian/ci.yml?branch=main&style=flat-square)](https://github.com/denprog/meridian/actions)
[![Coverage Status](https://img.shields.io/coveralls/github/denprog/meridian/main.svg?style=flat-square)](https://coveralls.io/github/denprog/meridian?branch=main)

`Meridian` is a comprehensive Open-Source package for Laravel (11.x, 12.x) designed to provide developers with intuitive tools and structured data for working with countries, continents, currencies (including exchange rates), and languages. Simplify internationalization, localization, and geo-dependent features in your applications with an elegant API and a well-thought-out architecture.

## Requirements

-   PHP 8.2+
-   Laravel 11.x or 12.x

## Installation & Setup

### 1. Install via Composer

```bash
composer require denprog/meridian
```

### 2. Run the Install Command

This command publishes the configuration file, translations, and runs database migrations.

```bash
php artisan meridian:install
```

### 3. Install Core Data

This one-time command seeds the database with essential data for countries, currencies, and languages.

```bash
php artisan meridian:install-data
```

### 4. Configure Your Environment

After publishing, the main configuration file is `config/meridian.php`. For GeoIP lookups, you must get a free MaxMind account and add your credentials to your `.env` file:

```env
MAXMIND_ACCOUNT_ID=your_account_id
MAXMIND_LICENSE_KEY=your_license_key
```

### 5. Schedule Data Updates

To keep your data current, define the schedule in your application's `routes/console.php` file.

```php
use Illuminate\Support\Facades\Schedule;

// To update the GeoIP database (e.g., weekly)
Schedule::command('meridian:update-geoip-db')->weekly();

// To update currency exchange rates (e.g., daily)
Schedule::command('meridian:update-exchange-rates')->daily();
```

Alternatively, you can define the schedule in your `bootstrap/app.php` file:

```php
use Illuminate\Console\Scheduling\Schedule;

->withSchedule(function (Schedule $schedule) {
    $schedule->command('meridian:update-geoip-db')->weekly();
    $schedule->command('meridian:update-exchange-rates')->daily();
})
```

## Usage

Meridian provides a simple and elegant API through its facades and a global helper function.

### Country Data

```php
use Denprog\Meridian\Facades\MeridianCountry;

// Get all countries
$countries = MeridianCountry::all();

// Find a country by its ISO code
$usa = MeridianCountry::findByCode('US');

// Get the default country (based on config)
$default = MeridianCountry::default();

// Get 
echo MeridianCountry::name(); // United States
echo MeridianCountry::code(); // US
echo MeridianCountry::currencyCode() // USD
echo MeridianCountry::nativeName() // United States
echo MeridianCountry::continent()->name(); // North America
echo MeridianCountry::isoAlpha2Code() // US
echo MeridianCountry::isoAlpha3Code() // USA
echo MeridianCountry::defaultName() // United States
echo MeridianCountry::defaultCode() // US
echo MeridianCountry::defaultCurrencyCode() // USD
echo MeridianCountry::defaultNativeName() // United States
```

### Geolocation

```php
use Denprog\Meridian\Facades\MeridianGeoLocator;

// Look up an IP address (e.g., from the request)
$location = MeridianGeoLocator::lookup(request()->ip());

if ($location->success) {
    echo 'Country: ' . $location->countryName; // e.g., United States
    echo 'City: ' . $location->cityName; // e.g., Mountain View
}
```

### Currency Management

```php
use Denprog\Meridian\Facades\MeridianCurrency;

// Get the current display currency (from session or base currency)
$currentCurrency = MeridianCurrency::get();

// Set the display currency for the user's session
MeridianCurrency::set('EUR');

// Get details of the current currency
echo MeridianCurrency::name(); // Euro
echo MeridianCurrency::code(); // EUR
echo MeridianCurrency::symbol(); // €
echo MeridianCurrency::baseName() // US Dollar
echo MeridianCurrency::baseCode() // USD
echo MeridianCurrency::baseSymbol() // $
```

### Currency Conversion

```php
use Denprog\Meridian\Facades\MeridianExchangeRate;

// Convert 100 units from the base currency to the current display currency
$convertedAmount = MeridianExchangeRate::convert(100);
$convertedAmount = MeridianExchangeRate::convert(100, true, 'de_DE');

// Converts an amount between two specified currencies, optionally for a specific date.
// Make sure you download the exchange rate for the desired currency pair on the desired date MeridianUpdateExchangeRate::update('EUR', 'USD', '2025-01-01')
$convertedBetween = MeridianExchangeRate::convertBetween(100, 'EUR', 'USD', true, '2025-01-01');
```

### Updating Exchange Rates Manually

While it's recommended to update rates via the scheduled Artisan command, you can also trigger an update manually using the `MeridianUpdateExchangeRate` facade. This will fetch and save the latest rates for all active currencies defined in your `config/meridian.php` file.

```php
use Denprog\Meridian\Facades\MeridianUpdateExchangeRate;

// Fetches and saves the latest exchange rates for all active currencies.
MeridianUpdateExchangeRate::update();
MeridianUpdateExchangeRate::update('USD', 'GBP', '2025-01-01');
```

### Language Management

```php
use Denprog\Meridian\Facades\MeridianLanguage;

// Get the current language (from session or config)
$currentLanguage = MeridianLanguage::get();

// Set the current language
MeridianLanguage::set('de');

// Get the language from the user's browser agent
$browserLanguage = MeridianLanguage::fromBrowser();
```

### Global Helper Function

The package also includes a `meridian()` helper for easy access to the core services.

```php
// Access the Currency Service
$currencyService = meridian()->currency();
echo $currencyService->code(); // e.g., EUR

// Access the GeoLocation Service
$location = meridian()->geoLocator()->lookup('8.8.8.8');
echo $location->countryName;

// Access the Country Service
$germany = meridian()->country()->findByCode('DE');
```
