<?php

declare(strict_types=1);

namespace Denprog\Meridian\Database\Seeders;

use Illuminate\Database\Seeder;

class MeridianDatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('Seeding Meridian package data...');

        $this->call([
            CurrencySeeder::class,
            CountrySeeder::class,
            LanguageSeeder::class,
            CountryLanguageSeeder::class,
            // Add other package seeders here
        ]);

        $this->command->info('Meridian package data seeded successfully.');
    }
}
