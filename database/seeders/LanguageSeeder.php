<?php

declare(strict_types=1);

namespace Denprog\Meridian\Database\Seeders;

use Denprog\Meridian\Models\Language;
use Exception;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /** @var string[] $activeLanguages */
        $activeLanguages = config('meridian.active_languages', []);
        $jsonFilePath = __DIR__.'/../../resources/languages.json';

        try {
            $jsonData = File::get($jsonFilePath);
        } catch (Exception $e) {
            $this->command->error('File not found: '.$jsonFilePath.' - '.$e->getMessage());
            Log::error('LanguageSeeder: File not found: '.$jsonFilePath, ['exception' => $e]);

            return;
        }

        $allLanguagesData = json_decode($jsonData, true);

        if (! is_array($allLanguagesData)) {
            $this->command->error('Invalid JSON data in: '.$jsonFilePath);
            Log::error('LanguageSeeder: Invalid JSON data in: '.$jsonFilePath, ['data' => $jsonData]);

            return;
        }

        foreach ($allLanguagesData as $languageData) {
            if (! is_array($languageData) || empty($languageData['code'])) {
                Log::warning('LanguageSeeder: Skipping invalid language data entry.', ['entry' => $languageData]);

                continue;
            }

            $name = $languageData['name'] ?? 'Unknown Language';
            $nativeName = $languageData['native_name'] ?? $name;
            $textDirection = $languageData['text_direction'] ?? 'ltr';
            $isActive = empty($activeLanguages) || in_array($languageData['code'], $activeLanguages);

            Language::query()->updateOrCreate(
                ['code' => $languageData['code']],
                [
                    'name' => $name,
                    'native_name' => $nativeName,
                    'text_direction' => $textDirection,
                    'is_active' => $isActive,
                ]
            );
        }

        $this->command->info('Languages seeded successfully from '.$jsonFilePath);
    }
}
