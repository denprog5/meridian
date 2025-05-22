<?php

declare(strict_types=1);

namespace Denprog\Meridian\Commands;

use Denprog\Meridian\Services\ExchangeRateService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

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

        try {
            $result = $this->exchangeRateService->fetchAndStoreRatesFromFrankfurter();

            if ($result['success']) {
                $this->info($result['message']);
                if (isset($result['base_currency'])) {
                    $this->line('Base Currency: '.$result['base_currency']);
                }
                if (isset($result['fetched_at'])) {
                    $this->line('Rates Date: '.$result['fetched_at']);
                }
                if (isset($result['rates_processed'])) {
                    $this->line('Rates Processed: '.$result['rates_processed']);
                }

                return Command::SUCCESS;
            }
            $this->error('Failed to update exchange rates.');
            $this->error('Message: '.$result['message']);
            Log::error('UpdateExchangeRatesCommand failed.', ['result' => $result]);

            return Command::FAILURE;
        } catch (Exception $e) {
            $this->error('An unexpected error occurred while updating exchange rates.');
            $this->error('Error: '.$e->getMessage());
            Log::critical('UpdateExchangeRatesCommand unexpected exception.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }
}
