<?php

declare(strict_types=1);

namespace Tests\Unit\Commands;

use Denprog\Meridian\Contracts\UpdateExchangeRateContract;
use Mockery;
use Symfony\Component\Console\Command\Command as CommandAlias;

beforeEach(function (): void {
    $this->exchangeRateServiceMock = Mockery::mock(UpdateExchangeRateContract::class);
    $this->app->instance(UpdateExchangeRateContract::class, $this->exchangeRateServiceMock);
});

it('handles command successfully when rates are updated', function (): void {
    $this->exchangeRateServiceMock
        ->shouldReceive('updateRates')
        ->once()
        ->andReturn(true);

    $this->artisan('meridian:update-exchange-rates')
        ->expectsOutput('Attempting to fetch and store exchange rates...')
        ->expectsOutput('Exchange rates updated successfully.')
        ->assertExitCode(CommandAlias::SUCCESS);
});

it('handles command failure when rate update fails', function (): void {
    $this->exchangeRateServiceMock
        ->shouldReceive('updateRates')
        ->once()
        ->andReturn(false);

    $this->artisan('meridian:update-exchange-rates')
        ->expectsOutput('Attempting to fetch and store exchange rates...')
        ->expectsOutput('Failed to update exchange rates or no rates needed updating.')
        ->assertExitCode(CommandAlias::FAILURE);
});
