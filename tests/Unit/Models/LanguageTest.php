<?php

declare(strict_types=1);

namespace Denprog\Meridian\Tests\Feature\Database;

use Denprog\Meridian\Models\Language;

test('language model has correct fillable attributes', function (): void {
    $language = Language::factory()->create();

    expect(array_keys($language->toArray()))
        ->toBe([
            'name',
            'native_name',
            'code',
            'text_direction',
            'is_active',
            'updated_at',
            'created_at',
            'id',
        ]);
});

test('localized name returns translated name when translation exists', function (): void {
    $language = Language::factory()->create(['code' => 'en', 'name' => 'English Test']);
    $languagePlCode = Language::factory()->create(['code' => 'pl', 'name' => 'Polish Test']);

    $originalLocale = app()->getLocale();
    app()->setLocale('en');

    expect($language->getLocalizedName())->toBe('English')
        ->and($languagePlCode->getLocalizedName('pl'))->toBe('Polish Test');

    app()->setLocale($originalLocale);
});
