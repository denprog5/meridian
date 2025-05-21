<?php

declare(strict_types=1);

namespace Denprog\Meridian\Database\Seeders;

use Denprog\Meridian\Models\Currency;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

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

        $currencies = [
            [
                'name' => 'US Dollar',
                'code' => 'USD',
                'iso_numeric' => '840',
                'symbol' => '$',
                'decimal_places' => 2,
                'enabled' => true,
            ],
            [
                'name' => 'Euro',
                'code' => 'EUR',
                'iso_numeric' => '978',
                'symbol' => '€',
                'decimal_places' => 2,
                'enabled' => true,
            ],
            [
                'name' => 'British Pound',
                'code' => 'GBP',
                'iso_numeric' => '826',
                'symbol' => '£',
                'decimal_places' => 2,
                'enabled' => true,
            ],
            [
                'name' => 'Japanese Yen',
                'code' => 'JPY',
                'iso_numeric' => '392',
                'symbol' => '¥',
                'decimal_places' => 0,
                'enabled' => true,
            ],
            [
                'name' => 'Canadian Dollar',
                'code' => 'CAD',
                'iso_numeric' => '124',
                'symbol' => 'CA$',
                'decimal_places' => 2,
                'enabled' => true,
            ],
            [
                'name' => 'Australian Dollar',
                'code' => 'AUD',
                'iso_numeric' => '036',
                'symbol' => 'AU$',
                'decimal_places' => 2,
                'enabled' => true,
            ],
            [
                'name' => 'Swiss Franc',
                'code' => 'CHF',
                'iso_numeric' => '756',
                'symbol' => 'CHF',
                'decimal_places' => 2,
                'enabled' => true,
            ],
        ];

        $tableName = (new Currency())->getTable();
        DB::table($tableName)->truncate();

        if (! $this->command->getOutput()->isQuiet()) {
            $this->command->getOutput()->progressStart(count($currencies));
        }

        foreach ($currencies as $currencyData) {
            Currency::query()->updateOrCreate(['code' => $currencyData['code']], $currencyData);
            if (! $this->command->getOutput()->isQuiet()) {
                $this->command->getOutput()->progressAdvance();
            }
        }

        if (! $this->command->getOutput()->isQuiet()) {
            $this->command->getOutput()->progressFinish();
        }
        $this->command->info('Currencies seeded successfully.');
    }
}
