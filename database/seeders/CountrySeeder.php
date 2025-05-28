<?php

declare(strict_types=1);

namespace Denprog\Meridian\Database\Seeders;

use Denprog\Meridian\Enums\Continent;
use Denprog\Meridian\Models\Country;
use Exception;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonFilePath = __DIR__.'/../../resources/countries.json';
        try {
            $jsonData = File::get($jsonFilePath);
        } catch (Exception $e) {
            $this->command->error('File not found: '.$jsonFilePath.' - '.$e->getMessage());

            return;
        }

        /** @var array<array<string, mixed>> $allCountriesData */
        $allCountriesData = json_decode($jsonData, true);

        foreach ($allCountriesData as $countryData) {
            if (! empty($countryData['continent_code']) && is_string($countryData['continent_code'])) {
                $continent = Continent::tryFrom($countryData['continent_code']);
                if ($continent === null) {
                    $this->command->error('Invalid continent code: '.$countryData['continent_code']);

                    continue;
                }
                $countryData['continent_code'] = $continent->value;
            }
            Country::query()->updateOrCreate(
                ['iso_alpha_2' => $countryData['iso_alpha_2']],
                $countryData
            );
        }
    }
}
