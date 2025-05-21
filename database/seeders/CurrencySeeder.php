<?php

declare(strict_types=1);

namespace Denprog\Meridian\Database\Seeders;

use Denprog\Meridian\Models\Currency;
use Exception;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if ($this->command->getOutput()->isQuiet()) {
            $this->command->line('Seeding currencies quietly...');
        }

        $jsonPath = __DIR__ . '/../../resources/currencies.json';

        if (!File::exists($jsonPath)) {
            $this->command->error('currencies.json not found at ' . $jsonPath);
            return;
        }

        try {
            $jsonData = File::get($jsonPath);
        } catch (Exception $e) {
            $this->command->error('Error reading currencies.json: ' . $e->getMessage());
            return;
        }
        $currenciesArray = json_decode($jsonData, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->command->error('Error decoding currencies.json: ' . json_last_error_msg());
            return;
        }

        if (empty($currenciesArray) || ! is_array($currenciesArray)) {
            $this->command->warn('No currencies found in currencies.json or the file is empty.');
            return;
        }

        $tableName = (new Currency())->getTable();
        DB::table($tableName)->truncate();

        if (! $this->command->getOutput()->isQuiet()) {
            $this->command->getOutput()->progressStart(count($currenciesArray));
        }

        foreach ($currenciesArray as $currencyData) {
            if (! is_array($currencyData)) {
                continue;
            }
            Currency::query()->updateOrCreate(
                [
                    'code' => $currencyData['code'],
                    'name' => $currencyData['name'],
                    'iso_numeric' => null,
                    'symbol' => $currencyData['symbol'],
                    'decimal_places' => $currencyData['decimal_digits'],
                    'enabled' => true,
                ]
            );
            if (! $this->command->getOutput()->isQuiet()) {
                $this->command->getOutput()->progressAdvance();
            }
        }

        if (! $this->command->getOutput()->isQuiet()) {
            $this->command->getOutput()->progressFinish();
        }
        $this->command->info(count($currenciesArray) . ' currencies seeded successfully from JSON.');
    }
}
