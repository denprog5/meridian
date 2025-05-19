<?php

declare(strict_types=1);

namespace Denprog\Meridian\Database\Seeders;

use Denprog\Meridian\Models\Continent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the continents table with basic data.
 */
class ContinentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        // Prevents foreign key constraint errors during truncate/seed
        // DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        // Continent::truncate(); // Optional: Truncate table before seeding
        // DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $continents = [
            ['code' => 'AF', 'name' => 'Africa'],
            ['code' => 'AN', 'name' => 'Antarctica'],
            ['code' => 'AS', 'name' => 'Asia'],
            ['code' => 'EU', 'name' => 'Europe'],
            ['code' => 'NA', 'name' => 'North America'],
            ['code' => 'OC', 'name' => 'Oceania'],
            ['code' => 'SA', 'name' => 'South America'],
        ];

        foreach ($continents as $continentData) {
            Continent::updateOrCreate(
                ['code' => $continentData['code']], // Attributes to find the record
                ['name' => $continentData['name']]  // Attributes to update or create
            );
        }
    }
}
