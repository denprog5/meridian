<?php

declare(strict_types=1);

namespace Denprog\Meridian\Database\Seeders;

use Denprog\Meridian\Models\Continent;
use Denprog\Meridian\Models\Country;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        // Optional: Disable foreign key checks for truncate if needed
        // DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        // Country::truncate();
        // DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Fetch continents once to map codes to IDs efficiently
        $continents = Continent::pluck('id', 'code')->all();

        $countries = [
            [
                'continent_code' => 'NA',
                'name' => 'United States',
                'official_name' => 'United States of America',
                'native_name' => 'United States',
                'iso_alpha_2' => 'US',
                'iso_alpha_3' => 'USA',
                'iso_numeric' => '840',
                'phone_code' => '1',
                'enabled' => true,
            ],
            [
                'continent_code' => 'EU',
                'name' => 'Germany',
                'official_name' => 'Federal Republic of Germany',
                'native_name' => 'Deutschland',
                'iso_alpha_2' => 'DE',
                'iso_alpha_3' => 'DEU',
                'iso_numeric' => '276',
                'phone_code' => '49',
                'enabled' => true,
            ],
            [
                'continent_code' => 'AS',
                'name' => 'Japan',
                'official_name' => 'Japan',
                'native_name' => '日本',
                'iso_alpha_2' => 'JP',
                'iso_alpha_3' => 'JPN',
                'iso_numeric' => '392',
                'phone_code' => '81',
                'enabled' => true,
            ],
            [
                'continent_code' => 'AF',
                'name' => 'Nigeria',
                'official_name' => 'Federal Republic of Nigeria',
                'native_name' => 'Nigeria',
                'iso_alpha_2' => 'NG',
                'iso_alpha_3' => 'NGA',
                'iso_numeric' => '566',
                'phone_code' => '234',
                'enabled' => true,
            ],
            [
                'continent_code' => 'SA',
                'name' => 'Brazil',
                'official_name' => 'Federative Republic of Brazil',
                'native_name' => 'Brasil',
                'iso_alpha_2' => 'BR',
                'iso_alpha_3' => 'BRA',
                'iso_numeric' => '076',
                'phone_code' => '55',
                'enabled' => true,
            ],
            [
                'continent_code' => 'OC',
                'name' => 'Australia',
                'official_name' => 'Commonwealth of Australia',
                'native_name' => 'Australia',
                'iso_alpha_2' => 'AU',
                'iso_alpha_3' => 'AUS',
                'iso_numeric' => '036',
                'phone_code' => '61',
                'enabled' => true,
            ],
             [
                // Example of a country with a continent_code not in the initial seeder, or null
                // This country will have continent_id = null if 'XX' is not found
                'continent_code' => 'AN', // Antarctica
                'name' => 'Antarctica (Territory)',
                'official_name' => 'Antarctica',
                'native_name' => 'Antarctica',
                'iso_alpha_2' => 'AQ',
                'iso_alpha_3' => 'ATA',
                'iso_numeric' => '010',
                'phone_code' => '672',
                'enabled' => true,
            ],
        ];

        foreach ($countries as $countryData) {
            $continentCode = $countryData['continent_code'];
            unset($countryData['continent_code']); // Remove before inserting

            $countryData['continent_id'] = $continents[$continentCode] ?? null;

            Country::updateOrCreate(
                ['iso_alpha_2' => $countryData['iso_alpha_2']], // Unique key for finding
                $countryData // Data to update or create
            );
        }
    }
}
