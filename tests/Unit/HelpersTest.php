<?php

declare(strict_types=1);

namespace Denprog\Meridian\Tests\Unit;

use Denprog\Meridian\Database\Factories\CountryFactory;
use Denprog\Meridian\Database\Factories\CurrencyFactory;
use Denprog\Meridian\Models\Country;
use Denprog\Meridian\Models\Currency;
use Denprog\Meridian\Services\CountryService;
use Denprog\Meridian\Services\CurrencyService;


describe('currency helper', function () {
    it('returns CurrencyService instance when no code is provided', function () {
        $result = currency();
        expect($result)->toBeInstanceOf(CurrencyService::class);
    });

    it('returns Currency model when a valid code is provided', function () {
        CurrencyFactory::new()->create(['code' => 'EUR', 'enabled' => true]);

        $result = currency('EUR');
        expect($result)->toBeInstanceOf(Currency::class)
            ->and($result->code)->toBe('EUR');
    });

    it('returns null when an invalid code is provided', function () {
        $result = currency('XYZ');
        expect($result)->toBeNull();
    });
});

describe('country helper', function () {
    it('returns CountryService instance when no code is provided', function () {
        $result = country();
        expect($result)->toBeInstanceOf(CountryService::class);
    });

    it('returns Country model when a valid ISO code is provided', function () {
        CurrencyFactory::new()->create(['code' => 'EUR', 'enabled' => true]);
        CountryFactory::new()->create(['iso_alpha_2' => 'DE', 'currency_code' => 'EUR']);

        $result = country('DE');
        expect($result)->toBeInstanceOf(Country::class)
            ->and($result->iso_alpha_2)->toBe('DE');
    });

    it('returns null when an invalid ISO code is provided', function () {
        // Ensure no country with code 'XX' exists
        $result = country('XX');
        expect($result)->toBeNull();
    });
});
