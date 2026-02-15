[![Latest Version on Packagist](https://img.shields.io/packagist/v/denprog/meridian.svg?style=flat-square)](https://packagist.org/packages/denprog/meridian)
[![Total Downloads](https://img.shields.io/packagist/dt/denprog/meridian.svg?style=flat-square)](https://packagist.org/packages/denprog/meridian)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/github/actions/workflow/status/denprog5/meridian/tests.yml?branch=main&style=flat-square)](https://github.com/denprog5/meridian/actions)
[![Coverage Status](https://img.shields.io/coveralls/github/denprog5/meridian/main.svg?style=flat-square)](https://coveralls.io/github/denprog5/meridian?branch=main)

`Meridian` is a comprehensive Open-Source package for Laravel (11.x, 12.x) designed to provide developers with intuitive tools and structured data for working with countries, continents, currencies (including exchange rates), and languages. Simplify internationalization, localization, and geo-dependent features in your applications with an elegant API and a well-thought-out architecture.

## Requirements

-   PHP 8.3+
-   Laravel 11.x or 12.x

## Compatibility Matrix

| Meridian | PHP | Laravel |
| --- | --- | --- |
| 1.2.x | 8.3, 8.4 | 11.x, 12.x |

## Installation & Setup

### 1. Install via Composer

```bash
composer require denprog/meridian
```

### 2. Run the Install Command

This command publishes the configuration file and migrations.

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
MERIDIAN_GEOIP_DB_SHA256=optional_sha256_checksum
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

### Artisan Commands

Meridian provides several Artisan commands to manage your data:

```bash
# Install the package (publish config and migrations)
php artisan meridian:install
php artisan meridian:install --force

# Seed the database with countries, currencies, and languages
php artisan meridian:install-data

# Run package health diagnostics
php artisan meridian:doctor
php artisan meridian:doctor --json

# Update GeoIP database
php artisan meridian:update-geoip-db
php artisan meridian:update-geoip-db --dry-run
php artisan meridian:update-geoip-db --retries=5 --retry-delay=1500

# Update exchange rates (with optional parameters)
php artisan meridian:update-exchange-rates
php artisan meridian:update-exchange-rates --base=EUR
php artisan meridian:update-exchange-rates --targets=GBP --targets=JPY
php artisan meridian:update-exchange-rates --date=2025-01-15
php artisan meridian:update-exchange-rates --base=USD --targets=EUR --date=2025-01-15
php artisan meridian:update-exchange-rates --dry-run
php artisan meridian:update-exchange-rates --retries=3 --retry-delay=700
```

**Exchange Rate Command Options:**
- `--base` - Specify the base currency code (default: from config)
- `--targets` - Specify target currencies (can be used multiple times)
- `--date` - Fetch rates for a specific date (format: YYYY-MM-DD)
- `--dry-run` - Validate and print execution plan without updating rates
- `--retries` - Number of retries for failed update attempts
- `--retry-delay` - Delay (ms) between retries
- `--lock-seconds` - Lock lifetime to prevent overlapping runs

**GeoIP Command Options:**
- `--dry-run` - Validate configuration and show execution plan only
- `--retries` - Number of retries for archive download
- `--retry-delay` - Delay (ms) between retries
- `--lock-seconds` - Lock lifetime to prevent overlapping runs

## Usage

Meridian provides a simple and elegant API through facades and global helper functions.

### Country Data

```php
use Denprog\Meridian\Facades\MeridianCountry;

// Get all countries
$countries = MeridianCountry::all();

// Find a country by its ISO code
$usa = MeridianCountry::findByCode('US');

// Get the default country (based on config)
$default = MeridianCountry::default();

// Get country details
echo MeridianCountry::name(); // United States
echo MeridianCountry::code(); // US
echo MeridianCountry::currencyCode(); // USD
echo MeridianCountry::nativeName(); // United States
echo MeridianCountry::continent()->name(); // North America
echo MeridianCountry::isoAlpha2Code(); // US
echo MeridianCountry::isoAlpha3Code(); // USA
echo MeridianCountry::defaultName(); // United States
echo MeridianCountry::defaultCode(); // US
echo MeridianCountry::defaultCurrencyCode(); // USD
echo MeridianCountry::defaultNativeName(); // United States
```

### Geolocation

```php
use Denprog\Meridian\Facades\MeridianGeoLocator;

// Look up an IP address (e.g., from the request)
$location = MeridianGeoLocator::lookup(request()->ip());

if (! $location->isEmpty()) {
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
echo MeridianCurrency::baseName(); // US Dollar
echo MeridianCurrency::baseCode(); // USD
echo MeridianCurrency::baseSymbol(); // $
```

### Currency Conversion

```php
use Denprog\Meridian\Facades\MeridianExchangeRate;

// Convert 100 units from the base currency to the current display currency
$convertedAmount = MeridianExchangeRate::convert(100);
$convertedAmount = MeridianExchangeRate::convert(100, true, 'de_DE');

// Convert between two currencies on a specific date.
// Ensure rates for the date are already downloaded.
$convertedBetween = MeridianExchangeRate::convertBetween(100, 'EUR', 'USD', true, '2025-01-01');
```

### Updating Exchange Rates Manually

While it's recommended to update rates via the scheduled Artisan command, you can also trigger an update manually using the `MeridianUpdateExchangeRate` facade. This will fetch and save the latest rates for all active currencies defined in your `config/meridian.php` file.

```php
use Denprog\Meridian\Facades\MeridianUpdateExchangeRate;
use Illuminate\Support\Carbon;

// Fetches and saves the latest exchange rates for all active currencies.
MeridianUpdateExchangeRate::updateRates();
MeridianUpdateExchangeRate::updateRates('USD', ['GBP'], Carbon::parse('2025-01-01'));
```

### Language Management

```php
use Denprog\Meridian\Facades\MeridianLanguage;

// Get the current language (from session or config)
$currentLanguage = MeridianLanguage::get();

// Set the current language
MeridianLanguage::set('de');

// Detect browser language and apply it
$browserLanguage = MeridianLanguage::detectBrowserLanguage();
MeridianLanguage::setByBrowserLanguage();
```

### Global Helper Functions

The package also includes lightweight global helpers.

```php
// Get service instances
$currencyService = currency();
$countryService = country();
$languageService = language();
$geoService = geoLocation();

// Resolve entities directly
$eur = currency('EUR');
$germany = country('DE');
$german = language('de');

// Resolve geolocation for IP
$location = geoLocation('8.8.8.8');
echo $location->countryName;

// Convert amount using current currency context
$formatted = exchangeRate(100, true);
```

## Troubleshooting

- `meridian:doctor` reports missing base/default entities:
  - Run `php artisan meridian:install-data` and verify `config/meridian.php` values.
- GeoIP update fails with authentication/configuration errors:
  - Check `MAXMIND_ACCOUNT_ID`, `MAXMIND_LICENSE_KEY`, and optional `MERIDIAN_GEOIP_DB_SHA256`.
- GeoIP update says lock is already acquired:
  - Wait for the running task to finish or reduce lock time in `meridian.commands.update_geoip_db.lock_seconds`.
- Exchange rate update keeps failing:
  - Run with `--dry-run`, then increase retries (`--retries`, `--retry-delay`) and verify provider URL.
- CI security audit fails:
  - Run `composer security:audit` locally and update vulnerable dependencies.
