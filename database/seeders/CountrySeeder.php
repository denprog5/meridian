<?php

declare(strict_types=1);

namespace Denprog\Meridian\Database\Seeders;

use Denprog\Meridian\Enums\Continent;
use Denprog\Meridian\Models\Country;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $countries = [
            [
                'iso_alpha_2' => 'DE',
                'name' => 'Germany',
                'official_name' => 'Federal Republic of Germany',
                'native_name' => 'Deutschland',
                'iso_alpha_3' => 'DEU',
                'iso_numeric' => '276',
                'continent_code' => Continent::EUROPE->value,
                'phone_code' => '+49',
                'enabled' => true,
            ],
            [
                'iso_alpha_2' => 'US',
                'name' => 'United States',
                'official_name' => 'United States of America',
                'native_name' => 'United States',
                'iso_alpha_3' => 'USA',
                'iso_numeric' => '840',
                'continent_code' => Continent::NORTH_AMERICA->value,
                'phone_code' => '+1',
                'enabled' => true,
            ],
            [
                'iso_alpha_2' => 'NG',
                'name' => 'Nigeria',
                'official_name' => 'Federal Republic of Nigeria',
                'native_name' => 'Nigeria',
                'iso_alpha_3' => 'NGA',
                'iso_numeric' => '566',
                'continent_code' => Continent::AFRICA->value,
                'phone_code' => '+234',
                'enabled' => true,
            ],
            [
                'iso_alpha_2' => 'JP',
                'name' => 'Japan',
                'official_name' => 'Japan',
                'native_name' => '日本',
                'iso_alpha_3' => 'JPN',
                'iso_numeric' => '392',
                'continent_code' => Continent::ASIA->value,
                'phone_code' => '+81',
                'enabled' => true,
            ],
            [
                'iso_alpha_2' => 'BR',
                'name' => 'Brazil',
                'official_name' => 'Federative Republic of Brazil',
                'native_name' => 'Brasil',
                'iso_alpha_3' => 'BRA',
                'iso_numeric' => '076',
                'continent_code' => Continent::SOUTH_AMERICA->value,
                'phone_code' => '+55',
                'enabled' => true,
            ],
            [
                'iso_alpha_2' => 'AU',
                'name' => 'Australia',
                'official_name' => 'Commonwealth of Australia',
                'native_name' => 'Australia',
                'iso_alpha_3' => 'AUS',
                'iso_numeric' => '036',
                'continent_code' => Continent::OCEANIA->value,
                'phone_code' => '+61',
                'enabled' => true,
            ],
            [
                'iso_alpha_2' => 'AQ',
                'name' => 'Antarctica',
                'official_name' => 'Antarctica',
                'native_name' => 'Antarctica',
                'iso_alpha_3' => 'ATA',
                'iso_numeric' => '010',
                'continent_code' => Continent::ANTARCTICA->value,
                'phone_code' => null, // Antarctica does not have a country calling code
                'enabled' => true,
            ],
        ];

        foreach ($countries as $countryData) {
            Country::query()->updateOrCreate(
                ['iso_alpha_2' => $countryData['iso_alpha_2']],
                $countryData
            );
        }
    }
}
