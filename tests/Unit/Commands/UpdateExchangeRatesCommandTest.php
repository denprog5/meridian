<?php

declare(strict_types=1);

namespace Tests\Unit\Commands;

use Denprog\Meridian\Commands\UpdateExchangeRatesCommand;
use Denprog\Meridian\Services\ExchangeRateService;
use Mockery;
use Symfony\Component\Console\Command\Command as CommandAlias;

beforeEach(function (): void {
    $this->exchangeRateServiceMock = Mockery::mock(ExchangeRateService::class);
});

it('handles command successfully when rates are updated', function (): void {
    $this->exchangeRateServiceMock
        ->shouldReceive('fetchAndStoreRatesFromProvider')
        ->once()
        ->andReturn(['updated_count' => 5]);

    $command = new UpdateExchangeRatesCommand($this->exchangeRateServiceMock);

    $this->app->instance(UpdateExchangeRatesCommand::class, $command);

    $this->artisan('meridian:update-exchange-rates')
        ->expectsOutput('Attempting to fetch and store exchange rates...')
        ->expectsOutput('Exchange rates updated successfully.')
        ->assertExitCode(CommandAlias::SUCCESS);
});

it('handles command failure when rate update fails', function (): void {
    $this->exchangeRateServiceMock
        ->shouldReceive('fetchAndStoreRatesFromProvider')
        ->once()
        ->andReturnNull();

    $command = new UpdateExchangeRatesCommand($this->exchangeRateServiceMock);
    $this->app->instance(UpdateExchangeRatesCommand::class, $command);

    $this->artisan('meridian:update-exchange-rates')
        ->expectsOutput('Attempting to fetch and store exchange rates...')
        ->expectsOutput('Failed to update exchange rates.') // This covers lines 45-46
        ->assertExitCode(CommandAlias::FAILURE);
});
