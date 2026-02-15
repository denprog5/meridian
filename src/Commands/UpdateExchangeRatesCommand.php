<?php

declare(strict_types=1);

namespace Denprog\Meridian\Commands;

use Denprog\Meridian\Contracts\UpdateExchangeRateContract;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
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
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'meridian:update-exchange-rates
        {--base= : Base currency code (defaults to config value)}
        {--targets=* : Target currency codes (defaults to config value)}
        {--date= : Specific date in YYYY-MM-DD format (defaults to today)}';

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

        $success = $this->updateExchangeRateService->updateRates(
            is_string($baseCurrency) && $baseCurrency !== '' ? $baseCurrency : null,
            $targetsArray,
            $date
        );

        if ($success) {
            $this->info('Exchange rates updated successfully.');

            return CommandAlias::SUCCESS;
        }

        $this->error('Failed to update exchange rates or no rates needed updating.');

        return CommandAlias::FAILURE;
    }
}
