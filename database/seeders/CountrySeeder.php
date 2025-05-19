<?php

declare(strict_types=1);

namespace Denprog\Meridian\Database\Seeders;

use Denprog\Meridian\Enums\Continent;
use Denprog\Meridian\Models\Country;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonFilePath = __DIR__.'/../../resources/countries.json';

        if (! File::exists($jsonFilePath)) {
            if ($this->command) {
                $this->command->error('countries.json not found at '.$jsonFilePath);
            }
            Log::error('[CountrySeeder] countries.json not found at '.$jsonFilePath);

            return;
        }

        try {
            $jsonData = File::get($jsonFilePath);
        } catch (FileNotFoundException $e) {
            if ($this->command) {
                $this->command->error('Error reading countries.json: '.$e->getMessage());
            }
            Log::error('[CountrySeeder] Error reading countries.json: '.$e->getMessage());

            return;
        }
        $allCountriesData = json_decode($jsonData, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            if ($this->command) {
                $this->command->error('Error decoding countries.json: '.json_last_error_msg());
            }
            Log::error('[CountrySeeder] Error decoding countries.json: '.json_last_error_msg());

            return;
        }

        if (empty($allCountriesData) || ! is_array($allCountriesData)) {
            if ($this->command) {
                $this->command->info('countries.json is empty. No countries to seed.');
            }
            Log::info('[CountrySeeder] countries.json is empty.');

            return;
        }

        if ($this->command) {
            $this->command->getOutput()->progressStart(count($allCountriesData));
        }

        foreach ($allCountriesData as $countryJson) {
            $dataToSeed = [
                'name' => Arr::get($countryJson, 'name.common'),
                'official_name' => Arr::get($countryJson, 'name.official'),
                'native_name' => null, // Placeholder, will be refined below
                'iso_alpha_2' => Arr::get($countryJson, 'cca2'),
                'iso_alpha_3' => Arr::get($countryJson, 'cca3'),
                'iso_numeric' => Arr::get($countryJson, 'ccn3'),
                'phone_code' => null, // Placeholder, will be refined below
            ];

            // Native Name (first official native name found)
            $nativeNames = Arr::get($countryJson, 'name.native', []);
            if (! empty($nativeNames) && is_array($nativeNames)) {
                $firstNative = reset($nativeNames);
                if (is_array($firstNative) && isset($firstNative['official'])) {
                    $dataToSeed['native_name'] = $firstNative['official'];
                }
            }

            // Continent Code
            $region = Arr::get($countryJson, 'region');
            $subregion = Arr::get($countryJson, 'subregion');
            $continentValue = null;
            switch ($region) {
                case 'Africa':
                    $continentValue = Continent::AFRICA->value;
                    break;
                case 'Americas':
                    if (str_contains((string) $subregion, 'South America')) {
                        $continentValue = Continent::SOUTH_AMERICA->value;
                    } else {
                        // North America, Central America, Caribbean
                        $continentValue = Continent::NORTH_AMERICA->value;
                    }
                    break;
                case 'Asia':
                    $continentValue = Continent::ASIA->value;
                    break;
                case 'Europe':
                    $continentValue = Continent::EUROPE->value;
                    break;
                case 'Oceania':
                    $continentValue = Continent::OCEANIA->value;
                    break;
                case 'Antarctic':
                case 'Antarctica': // Adding 'Antarctica' as some datasets might use it
                    $continentValue = Continent::ANTARCTICA->value;
                    break;
                default:
                    Log::warning('[CountrySeeder] Unknown region for country '.$dataToSeed['iso_alpha_2'].': '.$region.' (Subregion: '.$subregion.')');
            }
            $dataToSeed['continent_code'] = $continentValue;

            // Phone Code (construct from root and suffixes)
            $idd = Arr::get($countryJson, 'idd', []);
            $root = Arr::get($idd, 'root');
            $suffixes = Arr::get($idd, 'suffixes', []);
            if ($root && ! empty($suffixes)) {
                $phoneCodes = [];
                foreach ($suffixes as $suffix) {
                    $phoneCodes[] = $root.$suffix;
                }
                $dataToSeed['phone_code'] = implode(',', $phoneCodes);
            } elseif ($root) {
                // Handle cases where there's a root but no suffixes (e.g. Antarctica +672)
                // Though the JSON structure usually has suffixes even for single ones.
                $dataToSeed['phone_code'] = $root;
            }

            // Filter out any null values if necessary, depending on column definitions
            // $dataToSeed = array_filter($dataToSeed, fn ($value) => !is_null($value));

            Country::query()->updateOrCreate(
                ['iso_alpha_2' => $dataToSeed['iso_alpha_2']], // Match by iso_alpha_2
                $dataToSeed // Data to create or update with
            );

            if ($this->command) {
                $this->command->getOutput()->progressAdvance();
            }
        }

        if ($this->command) {
            $this->command->getOutput()->progressFinish();
            $this->command->info('Countries seeded successfully from '.basename($jsonFilePath).'.');
        }
        Log::info('[CountrySeeder] Seeding completed successfully from '.basename($jsonFilePath).'.');
    }
}
