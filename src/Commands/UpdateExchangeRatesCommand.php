<?php

declare(strict_types=1);

namespace Denprog\Meridian\Commands;

use Denprog\Meridian\Contracts\UpdateExchangeRateContract;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class UpdateExchangeRatesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'meridian:update-exchange-rates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetches and stores the latest exchange rates from the configured provider.';

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
        $this->info('Attempting to fetch and store exchange rates...');

        $success = $this->updateExchangeRateService->updateRates();

        if ($success) {
            $this->info('Exchange rates updated successfully.');
        } else {
            $this->error('Failed to update exchange rates or no rates needed updating.');

            // Consider if this should always be a FAILURE. If no rates were found for today but provider was reached, is it a failure?
            // For now, let's assume any non-true result means something didn't go as fully expected.
            return CommandAlias::FAILURE;
        }

        return CommandAlias::SUCCESS;
    }
}
