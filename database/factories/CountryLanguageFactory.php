<?php

declare(strict_types=1);

namespace Denprog\Meridian\Database\Factories;

use Denprog\Meridian\Enums\CountryLanguageStatusEnum;
use Denprog\Meridian\Models\Country;
use Denprog\Meridian\Models\CountryLanguage;
use Denprog\Meridian\Models\Language;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CountryLanguage>
 */
class CountryLanguageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<CountryLanguage>
     */
    protected $model = CountryLanguage::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        /**
         * By default, this factory will create new Country and Language records
         * and use their respective codes. This ensures that foreign key constraints
         * are met if they exist and provides valid default codes.
         *
         * In tests, you might often want to provide specific existing Country and Language
         * instances or their codes using the state methods or by overriding these attributes
         * when calling the factory.
         */
        return [
            'country_code' => Country::factory(), // Laravel handles resolving this to the appropriate key on creation
            'language_code' => Language::factory(), // Same as above
            'status' => $this->faker->randomElement(CountryLanguageStatusEnum::cases()),
        ];
    }

    /**
     * Indicate that the pivot record is for a specific country.
     *
     * @param  Country|string  $country  The Country model instance or its ISO alpha-2 code.
     */
    public function forCountry(Country|string $country): static
    {
        return $this->state(fn (array $attributes): array => [
            'country_code' => $country instanceof Country ? $country->iso_alpha_2 : $country,
        ]);
    }

    /**
     * Indicate that the pivot record is for a specific language.
     *
     * @param  Language|string  $language  The Language model instance or its ISO 639-1 code.
     */
    public function forLanguage(Language|string $language): static
    {
        return $this->state(fn (array $attributes): array => [
            'language_code' => $language instanceof Language ? $language->code : $language,
        ]);
    }

    /**
     * Indicate a specific status for the language in the country.
     */
    public function withStatus(?CountryLanguageStatusEnum $status): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => $status,
        ]);
    }
}
