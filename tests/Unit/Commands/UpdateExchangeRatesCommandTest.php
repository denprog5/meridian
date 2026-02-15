<?php

declare(strict_types=1);

namespace Tests\Unit\Commands;

use Denprog\Meridian\Contracts\UpdateExchangeRateContract;
use Illuminate\Support\Facades\Cache;
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

    $this->artisan('meridian:update-exchange-rates --retries=1')
        ->expectsOutput('Attempting to fetch and store exchange rates...')
        ->expectsOutput('Failed to update exchange rates or no rates needed updating.')
        ->assertExitCode(CommandAlias::FAILURE);
});

it('handles dry-run mode without calling update service', function (): void {
    $this->exchangeRateServiceMock
        ->shouldNotReceive('updateRates');

    $this->artisan('meridian:update-exchange-rates --dry-run')
        ->expectsOutput('Attempting to fetch and store exchange rates...')
        ->expectsOutput('Dry run mode enabled. No updates were executed.')
        ->assertExitCode(CommandAlias::SUCCESS);
});

it('fails for invalid date option format', function (): void {
    $this->exchangeRateServiceMock
        ->shouldNotReceive('updateRates');

    $this->artisan('meridian:update-exchange-rates --date=2026/01/01')
        ->expectsOutput('Invalid --date option. Use YYYY-MM-DD format.')
        ->assertExitCode(CommandAlias::FAILURE);
});

it('fails for invalid retries option', function (): void {
    $this->exchangeRateServiceMock
        ->shouldNotReceive('updateRates');

    $this->artisan('meridian:update-exchange-rates --retries=0')
        ->expectsOutput('Option --retries must be a positive integer.')
        ->assertExitCode(CommandAlias::FAILURE);
});

it('skips update when lock cannot be acquired', function (): void {
    Cache::shouldReceive('lock->get')->once()->andReturn(false);

    $this->exchangeRateServiceMock
        ->shouldNotReceive('updateRates');

    $this->artisan('meridian:update-exchange-rates')
        ->expectsOutput('Attempting to fetch and store exchange rates...')
        ->expectsOutput('Another exchange rate update is already running. Skipping.')
        ->assertExitCode(CommandAlias::SUCCESS);
});
