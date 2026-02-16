# Upgrade Guide

## Upgrading from 1.2.1 to 1.2.2

Version `1.2.2` is backward-compatible and focuses on runtime hardening, CI checks and operational tooling.

### What changed

- Added `meridian:doctor` command for runtime diagnostics.
- Added lock/retry/dry-run options to:
  - `meridian:update-exchange-rates`
  - `meridian:update-geoip-db`
- Added optional GeoIP archive checksum config:
  - `meridian.geolocation.drivers.maxmind_database.expected_sha256`
- Added command runtime config section:
  - `meridian.commands.update_exchange_rates.*`
  - `meridian.commands.update_geoip_db.*`

### Required actions

1. Publish and review updated config:

```bash
php artisan vendor:publish --provider="Denprog\Meridian\MeridianServiceProvider" --tag=meridian-config
```

2. Merge new keys into your existing `config/meridian.php`:

- `geolocation.drivers.maxmind_database.expected_sha256`
- `commands.update_exchange_rates`
- `commands.update_geoip_db`

3. (Optional) Add checksum env var for stronger archive validation:

```env
MERIDIAN_GEOIP_DB_SHA256=
```

4. Run diagnostics:

```bash
php artisan meridian:doctor
```

5. Optional: validate deterministic GeoIP command behavior in your environment:

```bash
php artisan meridian:update-geoip-db --dry-run
```
