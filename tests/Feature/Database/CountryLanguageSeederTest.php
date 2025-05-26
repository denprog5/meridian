<?php

declare(strict_types=1);

namespace Denprog\Meridian\Tests\Feature\Database;

test('can seed languages', function (): void {
    $this->artisan('db:seed --class=Denprog\\\Meridian\\\Database\\\Seeders\\\CountryLanguageSeeder')->assertExitCode(0);

    $this->assertDatabaseHas('country_language', [
        'country_code' => 'US', 'language_code' => 'en',
    ]);
});
