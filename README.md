# !!!IN DEVELOPMENT!!! Meridian: Your Elegant Compass for Geodata, Currencies, and Languages in Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/denprog/meridian.svg?style=flat-square)](https://packagist.org/packages/denprog/meridian)
[![Total Downloads](https://img.shields.io/packagist/dt/denprog/meridian.svg?style=flat-square)](https://packagist.org/packages/denprog/meridian)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/github/actions/workflow/status/denprog/meridian/ci.yml?branch=main&style=flat-square)](https://github.com/denprog/meridian/actions)
[![Coverage Status](https://img.shields.io/coveralls/github/denprog/meridian/main.svg?style=flat-square)](https://coveralls.io/github/denprog/meridian?branch=main)

`Meridian` is a comprehensive Open-Source package for Laravel (11.x, 12.x) designed to provide developers with intuitive tools and structured data for working with countries, continents, currencies (including exchange rates), and languages. Simplify internationalization, localization, and geo-dependent features in your applications with an elegant API and a well-thought-out architecture.

## Quick Start

1.  **Install the package via Composer:**
    ```bash
    composer require denprog/meridian
    ```

2.  **Run the install command:**
    This will publish necessary assets (configuration, translations) and optionally run migrations and seed initial data.
    ```bash
    php artisan meridian:install
    ```
    Follow the on-screen prompts. After this, you can configure the package via `config/meridian.php` and start using its features.

---

## Full Documentation

### Features

*   🌍 **Country Management:**
    *   Database of countries with ISO codes (alpha-2, alpha-3), names, phone codes, and continent associations.
    *   Easy-to-use `Meridian::countries()` facade for data retrieval (all, by code, by phone code, search).
    *   Continent data included.
    *   Localized country names via standard Laravel translation files.
*   📍 **Geolocation by IP (GeoIP):**
    *   `Meridian::geoLocator()` facade to determine user's country (and optionally city/region).
    *   Integration with MaxMind GeoLite2 (Country and City databases).
    *   Configurable MaxMind `ACCOUNT_ID` and `LICENSE_KEY`.
    *   Flexible storage path for the GeoIP database.
    *   Driver system: supports MaxMind local database by default, extendable for other API services (e.g., ip-api.com).
    *   Console command `meridian:update-geoip-db` for manual or scheduled database updates.
*   💰 **Currency Management:**
    *   Database of currencies with ISO 4217 codes, names, symbols, and decimal places.
    *   Configurable base currency (defaults to 'EUR').
    *   `Meridian::currencies()` facade (all, by code, base currency, currency for a country).
    *   Localized currency names.
*   💱 **Exchange Rate Management:**
    *   Storage of daily exchange rates relative to the chosen base currency.
    *   `Meridian::exchangeRates()` facade for currency conversion:
        *   Convert between any two non-base currencies.
        *   Convert to and from the base currency.
    *   Driver system for exchange rate providers (defaults to Frankfurter.app, extendable).
    *   Historical rate data storage and retrieval.
    *   Console command `meridian:update-exchange-rates` for updating rates.
    *   `MoneyFormatter` helper for consistent currency amount formatting.
*   🗣️ **Language Management:**
    *   Database of languages with ISO 639-1 codes, English names, native names, and text direction (LTR/RTL).
    *   Many-to-many relationship between countries and languages (official, common, regional).
    *   `Meridian::languages()` facade (all, by code, for country, browser-preferred, default).
    *   Configurable default language.
    *   Console command `meridian:seed-languages` to populate the language database.
    *   Localized language names.
*   ⚙️ **Flexibility and Extensibility:**
    *   Publishable configuration file, migrations, and translation files.
    *   Option to extend/override core models.
    *   Laravel event system integration for key actions.
    *   Comprehensive test suite using Pest PHP.

### Requirements

*   PHP 8.1+
*   Laravel 11.x or 12.x

### Installation Details

The `meridian:install` command will guide you through the following:
1.  Publishing the configuration file to `config/meridian.php`.
2.  Publishing the translation files to `lang/vendor/meridian/` (or `resources/lang/vendor/meridian/` depending on your Laravel version's structure).
3.  Prompting you to run database migrations for the package.
4.  Prompting you to seed initial data (continents, popular countries, currencies, languages).
5.  Providing instructions for setting up MaxMind GeoIP if you plan to use its features.

### Configuration

After publishing, the main configuration file can be found at `config/meridian.php`.
Key settings include:

*   **Base Currency:** `base_currency_code` (e.g., `'EUR'`)
*   **Default Language:** `default_language_code` (e.g., `'en'`)
*   **GeoIP Settings:**
    *   `default_driver` (e.g., `'maxmind_database'`)
    *   `drivers.maxmind_database.license_key` (Your MaxMind License Key)
    *   `drivers.maxmind_database.account_id` (Your MaxMind Account ID)
    *   `drivers.maxmind_database.database_path`
    *   Configuration for other GeoIP drivers.
*   **Exchange Rate Settings:**
    *   `default_provider` (e.g., `'frankfurter_app'`)
    *   Configuration for exchange rate providers.
*   **Cache Lifetimes** and other operational settings.

**MaxMind GeoIP Setup:**
To use the MaxMind GeoLite2 database for IP geolocation:
1.  Sign up for a MaxMind account at [https://www.maxmind.com](https://www.maxmind.com) to get a free License Key and Account ID.
2.  Enter these credentials in your `.env` file:
    ```env
    MAXMIND_LICENSE_KEY=your_license_key
    MAXMIND_ACCOUNT_ID=your_account_id
    ```
    (Alternatively, you can set them directly in `config/meridian.php`, but `.env` is recommended.)
3.  Run the command to download/update the database:
    ```bash
    php artisan meridian:update-geoip-db
    ```
    You can schedule this command to run regularly in your `app/Console/Kernel.php`.

### Usage

Meridian provides a simple and elegant API primarily through its main facade `Meridian` and specific sub-service facades if preferred.

#### Main Facade `Meridian`

```php
use DenProg\Meridian\Facades\Meridian;

// Countries
$countries = Meridian::countries()->all();
$usa = Meridian::countries()->findByCode('US');
$europeanCountries = Meridian::countries()->forContinent('EU');

// Currencies
$baseCurrency = Meridian::currencies()->base();
$euro = Meridian::currencies()->findByCode('EUR');

// Languages
$defaultLanguage = Meridian::languages()->default();
$english = Meridian::languages()->findByCode('en');
$browserLanguage = Meridian::languages()->fromBrowser(); // Based on request headers

// Geolocation
// Ensures you have configured a GeoIP driver and downloaded the database for MaxMind
try {
    $locationData = Meridian::geoLocator()->locate('8.8.8.8'); // or request()->ip()
    if ($locationData && $locationData->countryCode) {
        $countryFromIp = Meridian::countries()->findByCode($locationData->countryCode);
        // $locationData->countryName, $locationData->cityName, etc. are available on the DTO
    }
} catch (\DenProg\Meridian\Exceptions\GeoIpDatabaseMissingException $e) {
    // Handle a missing database, e.g., log or use a default
    Log::error('GeoIP database missing: ' . $e->getMessage());
} catch (\Exception $e) {
    // Handle other GeoIP errors
    Log::error('GeoIP service error: ' . $e->getMessage());
}


// Exchange Rates
// Ensure you have configured an exchange rate provider
$amountInEur = 100;
try {
    $amountInUsd = Meridian::exchangeRates()->convert($amountInEur, 'EUR', 'USD');
    $amountInBase = Meridian::exchangeRates()->convertToBase(150, 'GBP'); // Converts GBP to your configured base currency
} catch (\DenProg\Meridian\Exceptions\ExchangeRateProviderException $e) {
    // Handle provider errors, e.g., API down or invalid currency code
    Log::error('Exchange rate provider error: ' . $e->getMessage());
}
```
