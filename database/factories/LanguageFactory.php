<?php

declare(strict_types=1);

namespace Denprog\Meridian\Database\Factories;

use Denprog\Meridian\Models\Language;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Language>
 */
class LanguageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Language>
     */
    protected $model = Language::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $langCode = fake()->unique()->toLower(fake()->lexify('??'));

        return [
            'name' => fake()->words(2, true),
            'native_name' => fake()->words(2, true),
            'code' => $langCode,
            'text_direction' => fake()->randomElement(['ltr', 'rtl']),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the language is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the language is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
