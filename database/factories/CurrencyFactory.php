<?php

declare(strict_types=1);

namespace Denprog\Meridian\Database\Factories;

use Denprog\Meridian\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Currency>
 */
class CurrencyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Currency>
     */
    protected $model = Currency::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'code' => fake()->unique()->currencyCode(),
            'iso_numeric' => fake()->optional(0.8)->unique()->numerify(),
            'symbol' => fake()->optional(0.9)->randomElement(['$', '€', '£', '¥', '₹', 'CHF', 'kr', 'zł']),
            'decimal_places' => fake()->randomElement([0, 2, 3]),
            'enabled' => fake()->boolean(90),
        ];
    }
}
