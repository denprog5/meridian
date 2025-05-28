<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Denprog\Meridian\Contracts\LanguageServiceContract;
use Denprog\Meridian\Models\Language;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;

beforeEach(function (): void {
    Config::set('meridian.default_language_code', 'en');
    Config::set('meridian.cache_lifetimes.languages', 60);

    if (! Language::query()->where('code', 'en')->exists()) {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'is_active' => true]);
    }
    if (! Language::query()->where('code', 'de')->exists()) {
        Language::factory()->create(['code' => 'de', 'name' => 'German', 'native_name' => 'Deutsch', 'is_active' => true]);
    }
});

it('can resolve the service via contract', function (): void {
    $service = app(LanguageServiceContract::class);
    expect($service)->toBeInstanceOf(LanguageServiceContract::class);
});

it('gets the default language if no session or browser language is set', function (): void {
    /** @var LanguageServiceContract $service */
    $service = app(LanguageServiceContract::class);
    $defaultLang = Language::query()->where('code', 'en')->firstOrFail();

    Session::forget(LanguageServiceContract::SESSION_KEY_USER_LANGUAGE);

    $currentLanguage = $service->get();

    expect($currentLanguage)->toBeInstanceOf(Language::class)
        ->and($currentLanguage->id)->toBe($defaultLang->id)
        ->and($currentLanguage->code)->toBe('en');
});

it('can set and get a language', function (): void {
    /** @var LanguageServiceContract $service */
    $service = app(LanguageServiceContract::class);
    $german = Language::query()->where('code', 'de')->firstOrFail();

    $service->set('de');
    $currentLanguage = $service->get();

    expect($currentLanguage)->toBeInstanceOf(Language::class)
        ->and($currentLanguage->id)->toBe($german->id)
        ->and($currentLanguage->code)->toBe('de')
        ->and(Session::get(LanguageServiceContract::SESSION_KEY_USER_LANGUAGE))->toBe('de');
});

it('returns default language and clears session if session language is invalid', function (): void {
    /** @var LanguageServiceContract $service */
    $service = app(LanguageServiceContract::class);
    $defaultLang = Language::query()->where('code', 'en')->firstOrFail();

    Session::put(LanguageServiceContract::SESSION_KEY_USER_LANGUAGE, 'xx'); // Invalid language code

    $currentLanguage = $service->get();

    expect($currentLanguage)->toBeInstanceOf(Language::class)
        ->and($currentLanguage->id)->toBe($defaultLang->id)
        ->and($currentLanguage->code)->toBe('en')
        ->and(Session::has(LanguageServiceContract::SESSION_KEY_USER_LANGUAGE))->toBeFalse();
});

it('returns instance-cached language on subsequent get calls', function (): void {
    /** @var LanguageServiceContract $service */
    $service = app(LanguageServiceContract::class);
    Language::query()->where('code', 'de')->firstOrFail();

    // 1. Set session to 'de' and call get()
    Session::put(LanguageServiceContract::SESSION_KEY_USER_LANGUAGE, 'de');
    $firstCallLanguage = $service->get();

    expect($firstCallLanguage->code)->toBe('de');

    // 2. Change session externally to 'en'. This change should NOT affect the already resolved language
    // within this service instance due to the internal $this->language property.
    Session::put(LanguageServiceContract::SESSION_KEY_USER_LANGUAGE, 'en');

    // 3. Call get() again on the SAME service instance
    $secondCallLanguage = $service->get();

    // Assert that the second call still returns 'de' due to instance caching
    expect($secondCallLanguage->code)->toBe('de')
        ->and($secondCallLanguage->id)->toBe($firstCallLanguage->id)
        ->and($secondCallLanguage)->toBe($firstCallLanguage); // Check for same object instance
});

it('does not update language or session when setting an invalid language code', function (): void {
    /** @var LanguageServiceContract $service */
    $service = app(LanguageServiceContract::class);
    Language::query()->where('code', 'en')->firstOrFail();

    // Get initial language (should be default)
    $initialLanguage = $service->get();
    expect($initialLanguage->code)->toBe('en');
    $initialSessionValue = Session::get(LanguageServiceContract::SESSION_KEY_USER_LANGUAGE);

    $service->set('xx');

    $currentLanguage = $service->get();

    expect($currentLanguage->code)->toBe($initialLanguage->code)
        ->and($currentLanguage->id)->toBe($initialLanguage->id)
        ->and(Session::get(LanguageServiceContract::SESSION_KEY_USER_LANGUAGE))->not->toBe('xx')
        ->and(Session::get(LanguageServiceContract::SESSION_KEY_USER_LANGUAGE))->toBe($initialSessionValue);
});

it('handles case-insensitive language codes correctly when setting language', function (string $caseVariant): void {
    /** @var LanguageServiceContract $service */
    $service = app(LanguageServiceContract::class);
    $german = Language::query()->where('code', 'de')->firstOrFail();

    $service->set($caseVariant);
    $currentLanguage = $service->get();

    expect($currentLanguage)->toBeInstanceOf(Language::class)
        ->and($currentLanguage->id)->toBe($german->id)
        ->and($currentLanguage->code)->toBe('de') // Service should resolve to 'de'
        ->and(Session::get(LanguageServiceContract::SESSION_KEY_USER_LANGUAGE))->toBe('de'); // Session should store lowercase 'de'
})->with(['DE', 'De', 'dE']);

it('returns default language specified in config', function (): void {
    Config::set('meridian.default_language_code', 'de');
    // 'de' is created in beforeEach

    /** @var LanguageServiceContract $service */
    $service = app(LanguageServiceContract::class);
    $defaultLanguage = $service->default();

    expect($defaultLanguage)->toBeInstanceOf(Language::class)
        ->and($defaultLanguage->code)->toBe('de');
});

it('falls back to EN if configured default language is invalid', function (): void {
    Config::set('meridian.default_language_code', 'xx'); // Invalid code
    // 'en' is created in beforeEach as the ultimate fallback

    /** @var LanguageServiceContract $service */
    $service = app(LanguageServiceContract::class);
    $defaultLanguage = $service->default();

    expect($defaultLanguage)->toBeInstanceOf(Language::class)
        ->and($defaultLanguage->code)->toBe('en');
});

it('falls back to EN if configured default_language_code is not set (null or empty)', function (mixed $configValue): void {
    Config::set('meridian.default_language_code', $configValue);
    // 'en' is created in beforeEach

    /** @var LanguageServiceContract $service */
    $service = app(LanguageServiceContract::class);
    $defaultLanguage = $service->default();

    expect($defaultLanguage)->toBeInstanceOf(Language::class)
        ->and($defaultLanguage->code)->toBe('en');
})->with([null, '']);

it('returns instance-cached default language on subsequent default calls', function (): void {
    Config::set('meridian.default_language_code', 'de');
    // 'de' is created in beforeEach

    /** @var LanguageServiceContract $service */
    $service = app(LanguageServiceContract::class);

    $firstCallDefault = $service->default();
    expect($firstCallDefault->code)->toBe('de');

    // Change config. This should NOT affect the already resolved default language
    // within this service instance due to the internal $this->defaultLanguage property.
    Config::set('meridian.default_language_code', 'en');
    // 'en' is created in beforeEach

    $secondCallDefault = $service->default();

    expect($secondCallDefault->code)->toBe('de') // Still 'de' due to instance cache
        ->and($secondCallDefault)->toBe($firstCallDefault); // Check for same object instance
});

it('returns a collection of all active languages', function (): void {
    // Ensure we have a known set of active and inactive languages
    Language::query()->delete(); // Clear existing languages from beforeEach or other tests
    Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_active' => true]);
    Language::factory()->create(['code' => 'de', 'name' => 'German', 'is_active' => true]);
    Language::factory()->create(['code' => 'fr', 'name' => 'French', 'is_active' => false]);

    /** @var LanguageServiceContract $service */
    $service = app(LanguageServiceContract::class);
    $languages = $service->all(false); // Bypassing cache for direct DB check

    expect($languages)->toBeInstanceOf(Collection::class)
        ->toHaveCount(2)
        ->sequence(
            fn ($lang) => $lang->code->toBe('en'), // English comes before German when sorted by name
            fn ($lang) => $lang->code->toBe('de')
        );
});

it('utilizes cache by default for all() method', function (): void {
    /** @var LanguageServiceContract $service */
    $service = app(LanguageServiceContract::class);

    // 1. Prime the cache
    $firstCallLanguages = $service->all(true);
    expect(Cache::has('languages.all'))->toBeTrue();

    // 2. Modify data directly in DB (or mock DB to return different data)
    // For simplicity, we'll just check that the cached version is returned
    // A more robust test might involve mocking the Language model's `query()`
    Language::factory()->create(['code' => 'es', 'name' => 'Spanish', 'is_active' => true]);

    // 3. Call again, should return cached version
    $secondCallLanguages = $service->all(true);

    expect($secondCallLanguages->pluck('code')->all())->toEqual($firstCallLanguages->pluck('code')->all());
    // Ensure 'es' is not in the cached result
    expect($secondCallLanguages->pluck('code')->contains('es'))->toBeFalse();
});

it('bypasses cache for all() when useCache is false', function (): void {
    /** @var LanguageServiceContract $service */
    $service = app(LanguageServiceContract::class);

    // 1. Call with cache (primes it or uses existing)
    $service->all(true);
    expect(Cache::has('languages.all'))->toBeTrue();

    // 2. Add a new active language
    Language::factory()->create(['code' => 'it', 'name' => 'Italian', 'is_active' => true]);

    // 3. Call with useCache = false
    $languagesNoCache = $service->all(false);

    expect($languagesNoCache->pluck('code')->contains('it'))->toBeTrue();
});

it('returns an empty collection from all() if no active languages exist', function (): void {
    Language::query()->where('is_active', true)->update(['is_active' => false]); // Deactivate all
    // Or Language::query()->delete(); if we want to ensure table is empty

    /** @var LanguageServiceContract $service */
    $service = app(LanguageServiceContract::class);
    // Clear cache to ensure DB is hit
    Cache::forget('languages.all');
    $languages = $service->all(false);

    expect($languages)->toBeInstanceOf(Collection::class)
        ->toBeEmpty();
});

it('returns the correct Language model for a valid, existing code using findByCode', function (): void {
    $service = app(LanguageServiceContract::class);
    $foundLanguage = $service->findByCode('de', false);

    expect($foundLanguage)->toBeInstanceOf(Language::class)
        ->and($foundLanguage->code)->toBe('de');
});

it('returns null from findByCode for an invalid or non-existing code', function (string $invalidCode): void {
    /** @var LanguageServiceContract $service */
    $service = app(LanguageServiceContract::class);
    $foundLanguage = $service->findByCode($invalidCode, false); // Bypassing cache

    expect($foundLanguage)->toBeNull();
})->with(['xx', 'nonexistent']);

it('handles case-insensitive language codes correctly with findByCode', function (string $caseVariant): void {
    $french = Language::factory()->create(['code' => 'fr', 'name' => 'French', 'is_active' => true]);

    /** @var LanguageServiceContract $service */
    $service = app(LanguageServiceContract::class);
    $foundLanguage = $service->findByCode($caseVariant, false); // Bypassing cache

    expect($foundLanguage)->toBeInstanceOf(Language::class)
        ->and($foundLanguage->id)->toBe($french->id)
        ->and($foundLanguage->code)->toBe('fr');
})->with(['FR', 'Fr', 'fR']);

it('utilizes cache by default for findByCode() method', function (): void {
    $spanishCode = 'es';
    Language::factory()->create(['code' => $spanishCode, 'name' => 'Spanish', 'is_active' => true]);

    /** @var LanguageServiceContract $service */
    $service = app(LanguageServiceContract::class);

    // 1. Prime the cache for 'es'
    $firstCallLanguage = $service->findByCode($spanishCode, true);
    expect(Cache::has(LanguageServiceContract::CACHE_KEY_LANGUAGE_CODE_PREFIX.$spanishCode))->toBeTrue();

    // 2. Delete or deactivate 'es' from DB to ensure cache is hit
    Language::query()->where('code', $spanishCode)->delete();

    // 3. Call again, should return cached version
    $secondCallLanguage = $service->findByCode($spanishCode, true);

    expect($secondCallLanguage)->toBeInstanceOf(Language::class)
        ->and($secondCallLanguage->id)->toBe($firstCallLanguage->id);
});

it('bypasses cache for findByCode() when useCache is false', function (): void {
    $italianCode = 'it';
    Language::factory()->create(['code' => $italianCode, 'name' => 'Italian', 'is_active' => true]);

    /** @var LanguageServiceContract $service */
    $service = app(LanguageServiceContract::class);

    // 1. Call with cache (primes it or uses existing for 'it')
    $service->findByCode($italianCode, true);
    expect(Cache::has(LanguageServiceContract::CACHE_KEY_LANGUAGE_CODE_PREFIX.$italianCode))->toBeTrue();

    // 2. Update the language in DB (e.g., change its name)
    Language::query()->where('code', $italianCode)->update(['name' => 'Italiano Supremo']);
    $updatedItalian = Language::query()->where('code', $italianCode)->first();

    // 3. Call with useCache = false
    $languageNoCache = $service->findByCode($italianCode, false);

    expect($languageNoCache)->toBeInstanceOf(Language::class)
        ->and($languageNoCache->id)->toBe($updatedItalian->id)
        ->and($languageNoCache->name)->toBe('Italiano Supremo');
});

it('detects browser language correctly from Accept-Language header', function (string $acceptLanguageHeader, ?string $expectedLanguageCode): void {
    $this->withHeaders([
        'Accept-Language' => $acceptLanguageHeader,
    ])->get('/');

    /** @var LanguageServiceContract $service */
    $service = app(LanguageServiceContract::class);
    $detected = $service->detectBrowserLanguage();

    expect($detected)->toBe($expectedLanguageCode);
})->with([
    // SUB001: Simple header, 'en' preferred
    'simple_en_preferred' => ['en-US,en;q=0.9,de;q=0.8', 'en'],
    // SUB002: 'fr' preferred
    'simple_fr_preferred' => ['fr-FR,fr;q=0.9,es;q=0.8', 'fr'],
    // SUB003: 'de' preferred
    'simple_de_preferred' => ['de-DE;q=0.9,en-US;q=0.8', 'de'],
    // SUB004: Regional code 'zh-CN', base 'zh' is returned
    'regional_zh_CN' => ['zh-CN', 'zh'],
    // SUB007: Complex header with q-values, 'fr' should be chosen
    'complex_q_values_fr' => ['fr-CA,fr;q=0.8,en-US;q=0.6,en;q=0.4,de;q=0.2', 'fr'],
    // SUB008: Longer codes, 'en' from 'eng' should be chosen
    'longer_codes_eng' => ['eng,deu;q=0.9', 'en'],
    // Test with only one language, no q-values
    'single_lang_es' => ['es', 'es'],
    // Test with multiple languages, no q-values (first one should be picked)
    'multiple_no_q_pt_br' => ['pt,br', 'pt'],
]);

it('returns null from detectBrowserLanguage with an empty Accept-Language header (SUB005)', function (): void {
    $this->withHeaders([
        'Accept-Language' => '',
    ])->get('/');

    /** @var LanguageServiceContract $service */
    $service = app(LanguageServiceContract::class);
    $detected = $service->detectBrowserLanguage();

    expect($detected)->toBeNull();
});

it('sets language if valid browser language is detected and active (SUB001)', function (): void {
    Language::factory()->create(['code' => 'fr', 'name' => 'French', 'is_active' => true]);
    // 'en' is already created in beforeEach as default
    Config::set('meridian.default_language_code', 'en');

    $this->withHeaders([
        'Accept-Language' => 'fr-FR,fr;q=0.9',
    ])->get('/');

    /** @var LanguageServiceContract $service */
    $service = app(LanguageServiceContract::class);
    $service->setByBrowserLanguage();

    $currentLanguage = $service->get();
    expect($currentLanguage->code)->toBe('fr')
        ->and(Session::get(LanguageServiceContract::SESSION_KEY_USER_LANGUAGE))->toBe('fr');
});

it('sets default language if no browser language is detected (SUB003)', function (): void {
    // 'en' is default and created in beforeEach
    Config::set('meridian.default_language_code', 'en');

    $this->withHeaders([
        'Accept-Language' => '',
    ])->get('/');

    /** @var LanguageServiceContract $service */
    $service = app(LanguageServiceContract::class);
    $service->setByBrowserLanguage();

    $currentLanguage = $service->get();
    expect($currentLanguage->code)->toBe('en')
        ->and(Session::get(LanguageServiceContract::SESSION_KEY_USER_LANGUAGE))->toBe('en');
});
