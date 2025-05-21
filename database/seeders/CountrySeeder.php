<?php

declare(strict_types=1);

namespace Denprog\Meridian\Database\Seeders;

use ArrayAccess;
use Denprog\Meridian\Enums\Continent;
use Denprog\Meridian\Models\Country;
use Exception;
use Illuminate\Database\Seeder;
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
            $this->logAndOutput('countries.json is empty or not an array. No countries to seed.');

            return;
        }

        $this->startProgress(count($allCountriesData));

        foreach ($allCountriesData as $countryData) {
            if (! is_array($countryData) && ! ($countryData instanceof ArrayAccess)) {
                 $this->logAndOutput('Skipping invalid country data entry.', 'warning');
                continue;
            }

            // Direct mapping from the new JSON structure
            $dataToSeed = [
                'name' => $countryData['name'] ?? null,
                'official_name' => $countryData['official_name'] ?? null,
                'native_name' => $countryData['native_name'] ?? null,
                'iso_alpha_2' => $countryData['iso_alpha_2'] ?? null,
                'iso_alpha_3' => $countryData['iso_alpha_3'] ?? null,
                'iso_numeric' => $countryData['iso_numeric'] ?? null,
                'phone_code' => $countryData['phone_code'] ?? null,
                'continent_code' => $countryData['continent_code'] ?? null,
            ];

            // Basic validation for essential fields
            if (empty($dataToSeed['name']) || empty($dataToSeed['iso_alpha_2'])) {
                $this->logAndOutput('Skipping country due to missing name or iso_alpha_2.', 'warning');
                $this->advanceProgress(); // Still advance progress for skipped items
                continue;
            }

            // Ensure continent_code is valid or null if not set
            if (isset($dataToSeed['continent_code']) && !empty($dataToSeed['continent_code'])) {
                if (!Continent::tryFrom($dataToSeed['continent_code'])) {
                    $this->logAndOutput(
                        "Invalid continent_code '{$dataToSeed['continent_code']}' for country {$dataToSeed['name']}. Setting to null.",
                        'warning'
                    );
                    $dataToSeed['continent_code'] = null;
                }
            } else {
                 $dataToSeed['continent_code'] = null;
            }

            Country::query()->updateOrCreate($dataToSeed);

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

    /**
     * Start the progress bar if the command output is available.
     */
    protected function startProgress(int $total): void
    {
        $this->command->getOutput()->progressStart($total);
    }

    /**
     * Advance the progress bar if the command output is available.
     */
    protected function advanceProgress(): void
    {
        $this->command->getOutput()->progressAdvance();
    }

    /**
     * Finish the progress bar if the command output is available.
     */
    protected function finishProgress(): void
    {
        $this->command->getOutput()->progressFinish();
    }
}
