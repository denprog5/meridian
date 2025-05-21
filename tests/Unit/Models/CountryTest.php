<?php

declare(strict_types=1);

use Denprog\Meridian\Enums\Continent;
use Denprog\Meridian\Models\Country;

test('country model has correct fillable attributes', function (): void {
    $country = Country::factory()->create();

    expect(array_keys($country->toArray()))
        ->toBe([
            'continent_code',
            'name',
            'official_name',
            'native_name',
            'iso_alpha_2',
            'iso_alpha_3',
            'iso_numeric',
            'phone_code',
            'updated_at',
            'created_at',
            'id',
        ]);
});

test('country model casts continent_code to Continent enum', function (): void {
    $country = Country::factory()->create(['continent_code' => Continent::EUROPE]);

    expect($country->continent_code)->toBeInstanceOf(Continent::class)
        ->and($country->continent_code)->toBe(Continent::EUROPE);
});

test('localized name returns translated name when translation exists', function (): void {
    $country = Country::factory()->create(['iso_alpha_2' => 'US', 'name' => 'Generic US Name']);

    $originalLocale = app()->getLocale();
    app()->setLocale('en');

    expect($country->getLocalizedName())->toBe('United States of America')
        ->and($country->getLocalizedName('en'))->toBe('United States of America');

    app()->setLocale($originalLocale);
});

test('localized name falls back to name attribute when translation does not exist', function (): void {
    $country = Country::factory()->create(['iso_alpha_2' => 'XX', 'name' => 'NonExistent Country']);

    $originalLocale = app()->getLocale();
    app()->setLocale('en');

    expect($country->getLocalizedName())->toBe('NonExistent Country');

    app()->setLocale($originalLocale);
});
