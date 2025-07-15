<?php

declare(strict_types=1);

namespace Denprog\Meridian\Tests\Unit\Facades;

use Denprog\Meridian\Contracts\UpdateExchangeRateContract;
use Denprog\Meridian\Facades\MeridianUpdateExchangeRate;
use Mockery;

test('facade calls the correct service method', function (): void {
    $mock = Mockery::mock(UpdateExchangeRateContract::class);
    $mock->shouldReceive('update')->once();

    MeridianUpdateExchangeRate::swap($mock);
    MeridianUpdateExchangeRate::update();

    expect(true)->toBeTrue();

    Mockery::close();
});
