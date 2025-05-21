<?php

declare(strict_types=1);

use Denprog\Meridian\Models\Currency;

test('currency model has correct fillable attributes', function (): void {
    $country = Currency::factory()->create();

    expect(array_keys($country->toArray()))
        ->toBe([
            'name',
            'code',
            'symbol',
            'decimal_places',
            'enabled',
            'updated_at',
            'created_at',
            'id',
        ]);
});
