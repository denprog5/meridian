<?php

declare(strict_types=1);

use Denprog\Meridian\Models\Country;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('country model has correct fillable attributes', function () {
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
