<?php

declare(strict_types=1);

namespace Denprog\Meridian\Commands;

use Denprog\Meridian\Contracts\UpdateExchangeRateContract;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Throwable;

/**
 * Command to fetch and store exchange rates from the configured provider.
 */
#[AsCommand(name: 'meridian:update-exchange-rates')]
class UpdateExchangeRatesCommand extends Command
{
    private const string LOCK_KEY = 'meridian:update-exchange-rates';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'meridian:update-exchange-rates
        {--base= : Base currency code (defaults to config value)}
        {--targets=* : Target currency codes (defaults to config value)}
        {--date= : Specific date in YYYY-MM-DD format (defaults to today)}
        {--dry-run : Show execution plan without updating rates}
        {--retries= : Number of retry attempts for failed updates}
        {--retry-delay= : Delay in milliseconds between retries}
        {--lock-seconds= : Cache lock lifetime in seconds}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetches and stores exchange rates from the configured provider.';

    /**
     * Create a new command instance.
     */
    public function __construct(protected UpdateExchangeRateContract $updateExchangeRateService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $baseCurrency = $this->option('base');
        $targets = $this->option('targets');
        $dateString = $this->option('date');
        try {
            $retries = $this->resolvePositiveIntOption(
                'retries',
                config()->integer('meridian.commands.update_exchange_rates.retries', 2)
            );
            $retryDelayMs = $this->resolveNonNegativeIntOption(
                'retry-delay',
                config()->integer('meridian.commands.update_exchange_rates.retry_delay_ms', 500)
            );
            $lockSeconds = $this->resolvePositiveIntOption(
                'lock-seconds',
                config()->integer('meridian.commands.update_exchange_rates.lock_seconds', 300)
            );
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return CommandAlias::FAILURE;
        }

        $date = null;
        if (is_string($dateString) && $dateString !== '') {
            try {
                $parsedDate = Carbon::createFromFormat('Y-m-d', $dateString);
                if (! $parsedDate instanceof Carbon) {
                    throw new RuntimeException('Invalid date format.');
                }

                $date = $parsedDate->startOfDay();
            } catch (Throwable) {
                $this->error('Invalid --date option. Use YYYY-MM-DD format.');

                return CommandAlias::FAILURE;
            }
        }

        $this->info('Attempting to fetch and store exchange rates...');

        if (is_string($baseCurrency) && $baseCurrency !== '') {
            $this->line("  Base currency: $baseCurrency");
        }

        /** @var non-empty-array<int, string>|null $targetsArray */
        $targetsArray = is_array($targets) && $targets !== [] ? $targets : null;
        if ($targetsArray !== null) {
            $this->line('  Target currencies: '.implode(', ', $targetsArray));
        }
        if ($date !== null) {
            $this->line("  Date: {$date->toDateString()}");
        }
        $this->line("  Retry attempts: $retries");
        $this->line("  Retry delay: {$retryDelayMs}ms");

        if ((bool) $this->option('dry-run')) {
            $this->warn('Dry run mode enabled. No updates were executed.');

            return CommandAlias::SUCCESS;
        }

        $lock = Cache::lock(self::LOCK_KEY, $lockSeconds);
        if (! $lock->get()) {
            $this->warn('Another exchange rate update is already running. Skipping.');

            return CommandAlias::SUCCESS;
        }

        try {
            $success = $this->attemptUpdateRates(
                is_string($baseCurrency) && $baseCurrency !== '' ? $baseCurrency : null,
                $targetsArray,
                $date,
                $retries,
                $retryDelayMs
            );
        } finally {
            $lock->release();
        }

        if ($success) {
            $this->info('Exchange rates updated successfully.');

            return CommandAlias::SUCCESS;
        }

        $this->error('Failed to update exchange rates or no rates needed updating.');

        return CommandAlias::FAILURE;
    }

    /**
     * @param  non-empty-array<int, string>|null  $targets
     */
    private function attemptUpdateRates(
        ?string $baseCurrency,
        ?array $targets,
        ?Carbon $date,
        int $retries,
        int $retryDelayMs
    ): bool {
        $lastException = null;

        for ($attempt = 1; $attempt <= $retries; $attempt++) {
            try {
                if ($this->updateExchangeRateService->updateRates($baseCurrency, $targets, $date)) {
                    return true;
                }
            } catch (Throwable $exception) {
                $lastException = $exception;
                Log::warning('Exchange rate update attempt failed with exception.', [
                    'attempt' => $attempt,
                    'retries' => $retries,
                    'exception' => $exception,
                ]);
            }

            if ($attempt < $retries && $retryDelayMs > 0) {
                usleep($retryDelayMs * 1000);
            }
        }

        if ($lastException !== null) {
            Log::error('Exchange rate update failed after all retries.', [
                'retries' => $retries,
                'exception' => $lastException,
            ]);
        }

        return false;
    }

    private function resolvePositiveIntOption(string $name, int $defaultValue): int
    {
        $value = $this->option($name);

        if ($value === null || $value === '') {
            return max(1, $defaultValue);
        }

        if (! is_numeric($value)) {
            throw new RuntimeException("Option --$name must be a positive integer.");
        }

        $resolved = (int) $value;
        if ($resolved < 1) {
            throw new RuntimeException("Option --$name must be a positive integer.");
        }

        return $resolved;
    }

    private function resolveNonNegativeIntOption(string $name, int $defaultValue): int
    {
        $value = $this->option($name);

        if ($value === null || $value === '') {
            return max(0, $defaultValue);
        }

        if (! is_numeric($value)) {
            throw new RuntimeException("Option --$name must be a non-negative integer.");
        }

        $resolved = (int) $value;
        if ($resolved < 0) {
            throw new RuntimeException("Option --$name must be a non-negative integer.");
        }

        return $resolved;
    }
}
