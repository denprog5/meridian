<?php

declare(strict_types=1);

namespace Denprog\Meridian\Database\Factories;

use Denprog\Meridian\Models\ExchangeRate;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<ExchangeRate>
 */
class ExchangeRateFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<ExchangeRate>
     */
    protected $model = ExchangeRate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $currencyCodes = ['USD', 'EUR', 'GBP', 'JPY', 'AUD', 'CAD', 'CHF', 'CNY', 'HKD', 'NZD', 'SGD'];

        $baseCurrency = $this->faker->randomElement($currencyCodes);
        $targetCurrency = $this->faker->randomElement(array_diff($currencyCodes, [$baseCurrency]));

        return [
            'base_currency_code' => $baseCurrency,
            'target_currency_code' => $targetCurrency,
            'rate' => $this->faker->randomFloat(6, 0.1, 10.0),
            'rate_date' => Carbon::instance($this->faker->dateTimeBetween('-1 year'))->toDateString(),
        ];
    }
}
