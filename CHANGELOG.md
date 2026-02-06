# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [1.2.0] - 2026-02-06

### Added
- Complete DocBlocks for all Facades with IDE autocompletion support
- `findByCode()` alias in CountryService for easier API usage
- Base `MeridianException` class with `context()` method for structured logging
- `SECURITY.md` file with security policy and reporting guidelines
- CLI options for `UpdateExchangeRatesCommand`:
  - `--base` - Specify base currency code
  - `--targets` - Specify target currency codes (multiple allowed)
  - `--date` - Fetch rates for a specific date (YYYY-MM-DD format)
- `LocationData` DTO enhancements:
  - `fromSession()` - Create instance from session data
  - `hasCoordinates()` - Check if latitude/longitude exist
  - `hasCountry()` - Check if country code exists
  - `hasCity()` - Check if city name exists
  - `toFormattedString()` - Get "City, Country" formatted string
- Facade aliases in `composer.json` for auto-discovery

### Changed
- Refactored `MeridianServiceProvider` with helper methods for better maintainability
- Improved `MaxMindDatabaseDriver` with lazy loading (Reader initialized on first use)
- Exception classes now extend base `MeridianException` with additional context properties
- Cleaned up `config/meridian.php` - removed duplicate sections, added `env()` wrappers
- All models now have explicit `$table` property

### Fixed
- Added missing `use Log` import in `helpers.php`
- Fixed all PHPStan errors (level max)
- Fixed test migrations issue with Orchestra Testbench

## [1.1.0] - 2025-05-25

### Added
- Exchange rate conversion with `MeridianExchangeRate` facade
- `MeridianUpdateExchangeRate` facade for manual rate updates
- Support for historical exchange rates with date parameter

## [1.0.0] - 2025-05-20

### Added
- Initial release
- Country service with ISO codes support
- Currency service with symbols and decimal places
- Language service with browser detection
- GeoIP lookup with MaxMind database support
- Artisan commands for installation and data updates
