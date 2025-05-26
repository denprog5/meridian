<?php

declare(strict_types=1);

namespace Denprog\Meridian\Database\Seeders;

use Denprog\Meridian\Enums\CountryLanguageStatusEnum;
use Denprog\Meridian\Models\CountryLanguage;
use Exception;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Throwable;

class CountryLanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonFilePath = __DIR__.'/../../resources/country_language.json';

        try {
            $jsonData = File::get($jsonFilePath);
        } catch (Exception $e) {
            $this->command->error('File not found: '.$jsonFilePath.' - '.$e->getMessage());
            Log::error('CountryLanguageSeeder: File not found: '.$jsonFilePath, ['exception' => $e]);

            return;
        }

        $allData = json_decode($jsonData, true);

        if (!is_array($allData)) {
            $this->command->error('Invalid JSON data in: '.$jsonFilePath);
            Log::error('CountryLanguageSeeder: Invalid JSON data in: '.$jsonFilePath, ['data' => $jsonData]);

            return;
        }

        try {
            DB::transaction(function () use ($allData) {
                foreach ($allData as $item) {
                    if (
                        !is_array($item) ||
                        empty($item['country_code']) ||
                        empty($item['language_code'])
                    ) {
                        Log::warning('CountryLanguageSeeder: Skipping invalid data entry due to missing codes.', ['entry' => $item]);
                        continue;
                    }

                    $statusValue = $item['status'] ?? null;
                    $statusEnum = null;

                    if (is_string($statusValue) && $statusValue !== '') {
                        $statusEnum = CountryLanguageStatusEnum::tryFrom($statusValue);
                        if ($statusEnum === null) {
                            Log::warning(
                                'CountryLanguageSeeder: Invalid status value encountered. Will be stored as NULL.',
                                [
                                    'country_code' => $item['country_code'],
                                    'language_code' => $item['language_code'],
                                    'invalid_status' => $statusValue,
                                ]
                            );
                        }
                    }

                    CountryLanguage::query()->updateOrCreate(
                        [
                            'country_code' => $item['country_code'],
                            'language_code' => $item['language_code'],
                        ],
                        [
                            'status' => $statusEnum,
                        ]
                    );
                }
            });
        } catch (Throwable $e) {
            $this->command->error('Error seeding country-language relationships: '.$e->getMessage());
            Log::error('CountryLanguageSeeder: Error seeding country-language relationships', ['exception' => $e]);

            return;
        }

        $this->command->info('Country-language relationships seeded successfully.');
    }
}
