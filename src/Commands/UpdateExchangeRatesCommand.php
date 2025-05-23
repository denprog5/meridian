<?php

declare(strict_types=1);

namespace Denprog\Meridian\Commands;

use Denprog\Meridian\Services\ExchangeRateService;
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
    public function __construct(protected ExchangeRateService $exchangeRateService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Attempting to fetch and store exchange rates...');

        $result = $this->exchangeRateService->fetchAndStoreRatesFromProvider();

        if (! $result) {
            $this->error('Failed to update exchange rates.');
            return CommandAlias::FAILURE;
        }

        $this->info('Exchange rates updated successfully.');
        return CommandAlias::SUCCESS;
    }
}
