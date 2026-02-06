<?php

declare(strict_types=1);

namespace Denprog\Meridian\Commands;

use Denprog\Meridian\Contracts\UpdateExchangeRateContract;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Symfony\Component\Console\Command\Command as CommandAlias;

/**
 * Command to fetch and store exchange rates from the configured provider.
 */
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
        $date = is_string($dateString) && $dateString !== '' ? Carbon::parse($dateString) : null;

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
