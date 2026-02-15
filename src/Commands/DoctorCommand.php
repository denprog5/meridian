<?php

declare(strict_types=1);

namespace Denprog\Meridian\Commands;

use Denprog\Meridian\Models\Country;
use Denprog\Meridian\Models\Currency;
use Denprog\Meridian\Models\Language;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Throwable;

#[AsCommand(name: 'meridian:doctor')]
class DoctorCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'meridian:doctor
        {--json : Output report as JSON}';

    /**
     * @var string
     */
    protected $description = 'Runs health checks for Meridian configuration and runtime dependencies.';

    public function handle(): int
    {
        $checks = [
            $this->checkPhpVersion(),
            $this->checkIntlExtension(),
            $this->checkBaseCurrency(),
            $this->checkDefaultCountry(),
            $this->checkDefaultLanguage(),
            $this->checkExchangeRateProviderUrl(),
            $this->checkGeoIpDatabaseFile(),
            $this->checkCacheLockSupport(),
        ];

        $errorsCount = count(array_filter($checks, static fn (array $check): bool => $check['status'] === 'FAIL'));
        $warningsCount = count(array_filter($checks, static fn (array $check): bool => $check['status'] === 'WARN'));

        if ((bool) $this->option('json')) {
            $this->line((string) json_encode([
                'status' => $errorsCount > 0 ? 'failed' : ($warningsCount > 0 ? 'warning' : 'ok'),
                'errors' => $errorsCount,
                'warnings' => $warningsCount,
                'checks' => $checks,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $errorsCount > 0 ? CommandAlias::FAILURE : CommandAlias::SUCCESS;
        }

        $rows = array_map(
            static fn (array $check): array => [$check['check'], $check['status'], $check['message']],
            $checks
        );

        $this->table(['Check', 'Status', 'Message'], $rows);

        if ($errorsCount > 0) {
            $this->error("Meridian doctor found $errorsCount error(s). Please resolve them before production usage.");

            return CommandAlias::FAILURE;
        }

        if ($warningsCount > 0) {
            $this->warn("Meridian doctor found $warningsCount warning(s).");
        } else {
            $this->info('Meridian doctor finished successfully. No issues detected.');
        }

        return CommandAlias::SUCCESS;
    }

    /**
     * @return array{check: string, status: string, message: string}
     */
    private function checkPhpVersion(): array
    {
        if (version_compare(PHP_VERSION, '8.3.0', '>=')) {
            return $this->result('PHP Version', 'PASS', 'Running on PHP '.PHP_VERSION);
        }

        return $this->result('PHP Version', 'FAIL', 'PHP 8.3+ is required, current version: '.PHP_VERSION);
    }

    /**
     * @return array{check: string, status: string, message: string}
     */
    private function checkIntlExtension(): array
    {
        if (extension_loaded('intl')) {
            return $this->result('ext-intl', 'PASS', 'intl extension is loaded.');
        }

        return $this->result('ext-intl', 'FAIL', 'intl extension is required for currency and locale formatting.');
    }

    /**
     * @return array{check: string, status: string, message: string}
     */
    private function checkBaseCurrency(): array
    {
        try {
            $code = mb_strtoupper(config()->string('meridian.base_currency_code', 'USD'));
            $currency = Currency::query()->where('code', $code)->first();

            if ($currency instanceof Currency) {
                return $this->result('Base Currency', 'PASS', "Configured base currency '$code' exists.");
            }

            return $this->result('Base Currency', 'FAIL', "Configured base currency '$code' was not found in database.");
        } catch (Throwable $exception) {
            return $this->result('Base Currency', 'FAIL', 'Unable to validate base currency: '.$exception->getMessage());
        }
    }

    /**
     * @return array{check: string, status: string, message: string}
     */
    private function checkDefaultCountry(): array
    {
        try {
            $countryCode = mb_strtoupper(config()->string('meridian.default_country_iso_code', 'US'));
            $country = Country::query()->where('iso_alpha_2', $countryCode)->first();

            if ($country instanceof Country) {
                return $this->result('Default Country', 'PASS', "Configured country '$countryCode' exists.");
            }

            return $this->result('Default Country', 'FAIL', "Configured country '$countryCode' was not found in database.");
        } catch (Throwable $exception) {
            return $this->result('Default Country', 'FAIL', 'Unable to validate default country: '.$exception->getMessage());
        }
    }

    /**
     * @return array{check: string, status: string, message: string}
     */
    private function checkDefaultLanguage(): array
    {
        try {
            $configuredValue = config()->get('meridian.default_language_code', 'en');
            $languageCode = is_string($configuredValue) && $configuredValue !== ''
                ? mb_strtolower($configuredValue)
                : 'en';

            $language = Language::query()->where('code', $languageCode)->first();

            if ($language instanceof Language) {
                return $this->result('Default Language', 'PASS', "Configured language '$languageCode' exists.");
            }

            return $this->result('Default Language', 'FAIL', "Configured language '$languageCode' was not found in database.");
        } catch (Throwable $exception) {
            return $this->result('Default Language', 'FAIL', 'Unable to validate default language: '.$exception->getMessage());
        }
    }

    /**
     * @return array{check: string, status: string, message: string}
     */
    private function checkExchangeRateProviderUrl(): array
    {
        $providerUrl = config()->string('meridian.exchange_rates.providers.frankfurter.api_url', '');
        if ($providerUrl === '' || filter_var($providerUrl, FILTER_VALIDATE_URL) === false) {
            return $this->result('Exchange Rate Provider URL', 'FAIL', 'Invalid Frankfurter API URL in configuration.');
        }

        return $this->result('Exchange Rate Provider URL', 'PASS', $providerUrl);
    }

    /**
     * @return array{check: string, status: string, message: string}
     */
    private function checkGeoIpDatabaseFile(): array
    {
        $directory = config()->string('meridian.geolocation.drivers.maxmind_database.database_path', 'meridian');
        $filename = config()->string('meridian.geolocation.drivers.maxmind_database.database_filename', 'GeoLite2-City.mmdb');
        $absolutePath = storage_path(mb_ltrim($directory, '/\\').DIRECTORY_SEPARATOR.$filename);

        if (! file_exists($absolutePath)) {
            return $this->result('GeoIP Database', 'WARN', "GeoIP DB file is missing: $absolutePath");
        }

        if (! is_readable($absolutePath)) {
            return $this->result('GeoIP Database', 'FAIL', "GeoIP DB file is not readable: $absolutePath");
        }

        return $this->result('GeoIP Database', 'PASS', "GeoIP DB file found: $absolutePath");
    }

    /**
     * @return array{check: string, status: string, message: string}
     */
    private function checkCacheLockSupport(): array
    {
        try {
            $lock = Cache::lock('meridian:doctor:lock-check', 5);
            if (! $lock->get()) {
                return $this->result('Cache Locks', 'WARN', 'Could not acquire lock (already held).');
            }

            $lock->release();

            return $this->result('Cache Locks', 'PASS', 'Cache lock operations are available.');
        } catch (Throwable $exception) {
            return $this->result('Cache Locks', 'WARN', 'Cache store may not support locks: '.$exception->getMessage());
        }
    }

    /**
     * @return array{check: string, status: string, message: string}
     */
    private function result(string $check, string $status, string $message): array
    {
        return [
            'check' => $check,
            'status' => $status,
            'message' => $message,
        ];
    }
}
