<?php

declare(strict_types=1);

namespace Denprog\Meridian\Tests\Feature\Database;

use Denprog\Meridian\Database\Seeders\CountrySeeder;
use Denprog\Meridian\Models\Country;

it('populates the database using CountrySeeder', function (): void {
    expect(Country::query()->count())->toBe(0);

    (new CountrySeeder())->run();

    expect(Country::query()->count())->toBeGreaterThan(0);

    $usa = Country::query()->where('iso_alpha_2', 'US')->first();
    expect($usa)->not->toBeNull()
        ->and($usa->name)->toBe('United States');

    $germany = Country::query()->where('iso_alpha_2', 'DE')->first();
    expect($germany)->not->toBeNull()
        ->and($germany->name)->toBe('Germany')
        ->and(Country::query()->count())->toBe(250);
});
