<?php

declare(strict_types=1);

namespace Denprog\Meridian\Tests\Unit\Models;

use Denprog\Meridian\Enums\CountryLanguageStatusEnum;
use Denprog\Meridian\Models\Country;
use Denprog\Meridian\Models\CountryLanguage;
use Denprog\Meridian\Models\Language;

dataset('country language statuses', fn (): array => [
    'official' => [CountryLanguageStatusEnum::OFFICIAL],
    'regional' => [CountryLanguageStatusEnum::REGIONAL],
    'null_status' => [null],
    'national' => [CountryLanguageStatusEnum::NATIONAL],
    'regional_official' => [CountryLanguageStatusEnum::REGIONAL_OFFICIAL],
    'minority' => [CountryLanguageStatusEnum::MINORITY],
    'lingua_franca' => [CountryLanguageStatusEnum::LINGUA_FRANCA],
]);

it('has correct fillable attributes', function (): void {
    $countryLanguage = new CountryLanguage();
    expect($countryLanguage->getFillable())->toBe([
        'country_code',
        'language_code',
        'status',
    ]);
});

it('has correct table name', function (): void {
    $countryLanguage = new CountryLanguage();
    expect($countryLanguage->getTable())->toBe('country_language');
});

it('does not use timestamps by default', function (): void {
    $countryLanguage = new CountryLanguage();
    expect($countryLanguage->usesTimestamps())->toBeFalse();
});

it('is not incrementing', function (): void {
    $countryLanguage = new CountryLanguage();
    expect($countryLanguage->getIncrementing())->toBeFalse();
});

it('casts status to CountryLanguageStatusEnum or null', function (?CountryLanguageStatusEnum $status): void {
    $countryLanguage = CountryLanguage::factory()->makeOne([
        'status' => $status,
        'country_code' => 'XX',
        'language_code' => 'xx',
    ]);

    if ($status instanceof CountryLanguageStatusEnum) {
        expect($countryLanguage->status)->toBeInstanceOf(CountryLanguageStatusEnum::class)
            ->and($countryLanguage->status)->toBe($status);
    } else {
        expect($countryLanguage->status)->toBeNull();
    }
})->with('country language statuses');

it('can be created using the factory with specific data', function (?CountryLanguageStatusEnum $status): void {
    $country = Country::factory()->create(['iso_alpha_2' => 'DE']);
    $language = Language::factory()->create(['code' => 'de']);

    $pivot = CountryLanguage::factory()
        ->forCountry($country)
        ->forLanguage($language->code)
        ->withStatus($status)
        ->create();

    expect($pivot)->toBeInstanceOf(CountryLanguage::class)
        ->and($pivot->country_code)->toBe('DE')
        ->and($pivot->language_code)->toBe('de');

    if ($status instanceof CountryLanguageStatusEnum) {
        expect($pivot->status)->toBeInstanceOf(CountryLanguageStatusEnum::class)
            ->and($pivot->status)->toBe($status);
    } else {
        expect($pivot->status)->toBeNull();
    }

    $this->assertDatabaseHas('country_language', [
        'country_code' => 'DE',
        'language_code' => 'de',
        'status' => $status?->value,
    ]);
})->with('country language statuses');

test('country relationship is defined correctly and loads the related country', function (): void {
    $country = Country::factory()->create(['iso_alpha_2' => 'FR', 'name' => 'France']);
    $pivot = CountryLanguage::factory()->forCountry($country)->create();

    // Перезагружаем для чистоты теста (если PK не автоинкрементный, используем where)
    $retrievedPivot = CountryLanguage::query()->where('country_code', $pivot->country_code)
        ->where('language_code', $pivot->language_code)
        ->firstOrFail();

    expect($retrievedPivot->country)->toBeInstanceOf(Country::class)
        ->and($retrievedPivot->country->iso_alpha_2)->toBe('FR')
        ->and($retrievedPivot->country->name)->toBe('France')
        ->and($retrievedPivot->relationLoaded('country'))->toBeTrue();
});

test('language relationship is defined correctly and loads the related language', function (): void {
    $language = Language::factory()->create(['code' => 'es', 'name' => 'Spanish']);
    $pivot = CountryLanguage::factory()->forLanguage($language)->create();

    $retrievedPivot = CountryLanguage::query()->where('country_code', $pivot->country_code)
        ->where('language_code', $pivot->language_code)
        ->firstOrFail();

    expect($retrievedPivot->language)->toBeInstanceOf(Language::class)
        ->and($retrievedPivot->language->code)->toBe('es')
        ->and($retrievedPivot->language->name)->toBe('Spanish')
        ->and($retrievedPivot->relationLoaded('language'))->toBeTrue();
});

test('pivot model is correctly used in Country model many-to-many relationship with languages', function (): void {
    $country = Country::factory()->create();
    $language1 = Language::factory()->create();
    $language2 = Language::factory()->create();

    $country->languages()->attach([
        $language1->code => ['status' => CountryLanguageStatusEnum::OFFICIAL],
        $language2->code => ['status' => null],
    ]);

    $country->load('languages');

    $attachedLanguage1 = $country->languages->firstWhere('code', $language1->code);
    expect($attachedLanguage1)->not->toBeNull()
        ->and($attachedLanguage1->pivot)->toBeInstanceOf(CountryLanguage::class)
        ->and($attachedLanguage1->pivot->country_code)->toBe($country->iso_alpha_2)
        ->and($attachedLanguage1->pivot->language_code)->toBe($language1->code)
        ->and($attachedLanguage1->pivot->status)->toBe(CountryLanguageStatusEnum::OFFICIAL);

    $attachedLanguage2 = $country->languages->firstWhere('code', $language2->code);
    expect($attachedLanguage2)->not->toBeNull()
        ->and($attachedLanguage2->pivot)->toBeInstanceOf(CountryLanguage::class);
});

test('pivot model is correctly used in Language model many-to-many relationship with countries', function (): void {
    $language = Language::factory()->create();
    $country1 = Country::factory()->create();
    $country2 = Country::factory()->create();

    $language->countries()->attach([
        $country1->iso_alpha_2 => ['status' => CountryLanguageStatusEnum::REGIONAL_OFFICIAL],
        $country2->iso_alpha_2 => ['status' => CountryLanguageStatusEnum::MINORITY],
    ]);

    $language->load('countries'); // Явно загружаем отношение

    $attachedCountry1 = $language->countries->firstWhere('iso_alpha_2', $country1->iso_alpha_2);
    expect($attachedCountry1)->not->toBeNull()
        ->and($attachedCountry1->pivot)->toBeInstanceOf(CountryLanguage::class)
        ->and($attachedCountry1->pivot->country_code)->toBe($country1->iso_alpha_2)
        ->and($attachedCountry1->pivot->language_code)->toBe($language->code)
        ->and($attachedCountry1->pivot->status)->toBe(CountryLanguageStatusEnum::REGIONAL_OFFICIAL);

    $attachedCountry2 = $language->countries->firstWhere('iso_alpha_2', $country2->iso_alpha_2);
    expect($attachedCountry2)->not->toBeNull()
        ->and($attachedCountry2->pivot->status)->toBe(CountryLanguageStatusEnum::MINORITY);
});
