<?php

declare(strict_types=1);

namespace Denprog\Meridian\Database\Factories;

use Denprog\Meridian\Enums\Continent;
use Denprog\Meridian\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Country>
 */
class CountryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Country>
     */
    protected $model = Country::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->country();

        return [
            'continent_code' => fake()->randomElement(Continent::class),
            'name' => $name,
            'official_name' => $name.' Official Name',
            'native_name' => $name.' Native',
            'iso_alpha_2' => fake()->unique()->countryCode(),
            'iso_alpha_3' => fake()->unique()->countryISOAlpha3(),
            'iso_numeric' => fake()->unique()->numerify(),
            'phone_code' => fake()->numerify('+###'),
        ];
    }

    /**
     * Indicate that the country belongs to a specific continent.
     *
     * @param  Continent  $continent  The continent enum case.
     */
    public function forContinent(Continent $continent): static
    {
        return $this->state(fn (array $attributes): array => [
            'continent_code' => $continent,
        ]);
    }
}
