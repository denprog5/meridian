<?php

declare(strict_types=1);

namespace Denprog\Meridian\Database\Seeders;

use ArrayAccess;
use Denprog\Meridian\Enums\Continent;
use Denprog\Meridian\Models\Country;
use Exception;
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
            $this->logAndOutput('countries.json not found at '.$jsonFilePath, 'error');

            return;
        }

        try {
            $jsonData = File::get($jsonFilePath);
        } catch (Exception $e) {
            $this->logAndOutput('Error reading countries.json: '.$e->getMessage(), 'error');

            return;
        }

        $allCountriesData = json_decode($jsonData, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logAndOutput('Error decoding countries.json: '.json_last_error_msg(), 'error');

            return;
        }

        if (empty($allCountriesData) || ! is_array($allCountriesData)) {
            $this->logAndOutput('countries.json is empty. No countries to seed.');

            return;
        }

        $this->startProgress(count($allCountriesData));

        foreach ($allCountriesData as $countryJson) {
            if (! is_array($countryJson) && ! ($countryJson instanceof ArrayAccess)) {
                continue;
            }
            $dataToSeed = [
                'name' => data_get($countryJson, 'name.common'),
                'official_name' => data_get($countryJson, 'name.official'),
                'native_name' => null,
                'iso_alpha_2' => data_get($countryJson, 'cca2'),
                'iso_alpha_3' => data_get($countryJson, 'cca3'),
                'iso_numeric' => data_get($countryJson, 'ccn3'),
                'phone_code' => null,
            ];

            $nativeNames = data_get($countryJson, 'name.native', []);
            if (! empty($nativeNames) && is_array($nativeNames)) {
                $firstNative = Arr::first($nativeNames);
                if (is_array($firstNative)) {
                    $dataToSeed['native_name'] = data_get($firstNative, 'official');
                }
            }

            $region = data_get($countryJson, 'region');
            $subregion = data_get($countryJson, 'subregion');
            if (! is_string($subregion)) {
                $subregion = '';
            }

            switch ($region) {
                case 'Africa':
                    $dataToSeed['continent_code'] = Continent::AFRICA->value;
                    break;
                case 'Americas':
                    if (str_contains($subregion, 'South America')) {
                        $dataToSeed['continent_code'] = Continent::SOUTH_AMERICA->value;
                    } else {
                        $dataToSeed['continent_code'] = Continent::NORTH_AMERICA->value;
                    }
                    break;
                case 'Asia':
                    $dataToSeed['continent_code'] = Continent::ASIA->value;
                    break;
                case 'Europe':
                    $dataToSeed['continent_code'] = Continent::EUROPE->value;
                    break;
                case 'Oceania':
                    $dataToSeed['continent_code'] = Continent::OCEANIA->value;
                    break;
                case 'Antarctic':
                    $dataToSeed['continent_code'] = Continent::ANTARCTICA->value;
                    break;
            }

            $root = data_get($countryJson, 'idd.root');
            $suffixes = data_get($countryJson, 'idd.suffixes', []);

            if (is_string($root) && is_array($suffixes)) {
                $validSuffixes = collect($suffixes)
                    ->filter(fn ($suffix): bool => is_string($suffix))
                    /** @phpstan-ignore-next-line */
                    ->map(fn ($suffix): string => $root.$suffix)
                    ->all();
                $dataToSeed['phone_code'] = empty($validSuffixes) ? $root : implode(',', $validSuffixes);
            }

            Country::query()->updateOrCreate(['iso_alpha_2' => $dataToSeed['iso_alpha_2']], $dataToSeed);

            $this->advanceProgress();
        }

        $this->finishProgress();
        $this->logAndOutput('Countries seeded successfully from '.basename($jsonFilePath).'.');
    }

    /**
     * Log a message and optionally output to the console.
     *
     * @param  string  $message  The message to log and output.
     * @param  string  $logLevel  The log level (e.g., 'info', 'error', 'warning').
     * @param  string|null  $consoleStyle  The console style for output (e.g., 'info', 'error', 'warn'). Defaults to logLevel.
     */
    protected function logAndOutput(string $message, string $logLevel = 'info', ?string $consoleStyle = null): void
    {
        $logMessage = '[CountrySeeder] '.$message;
        match (mb_strtolower($logLevel)) {
            'error' => Log::error($logMessage),
            'warning' => Log::warning($logMessage),
            default => Log::info($logMessage),
        };

        /** @phpstan-ignore-next-line */
        if ($this->command) {
            $consoleOutputStyle = $consoleStyle ?? $logLevel;
            if ($consoleOutputStyle === 'warning') {
                $consoleOutputStyle = 'warn';
            }
            if (method_exists($this->command, $consoleOutputStyle)) {
                $this->command->{$consoleOutputStyle}($message);
            } else {
                $this->command->line($message);
            }
        }
    }

    /**
     * Start the progress bar if the command output is available.
     */
    protected function startProgress(int $total): void
    {
        /** @phpstan-ignore-next-line */
        $this->command?->getOutput()->progressStart($total);
    }

    /**
     * Advance the progress bar if the command output is available.
     */
    protected function advanceProgress(): void
    {
        /** @phpstan-ignore-next-line */
        $this->command?->getOutput()->progressAdvance();
    }

    /**
     * Finish the progress bar if the command output is available.
     */
    protected function finishProgress(): void
    {
        /** @phpstan-ignore-next-line */
        $this->command?->getOutput()->progressFinish();
    }
}
