<?php

declare(strict_types=1);

namespace Denprog\Meridian\Tests\Unit\Facades;

use Denprog\Meridian\Facades\MeridianLanguage;
use Denprog\Meridian\Models\Language;
use Illuminate\Support\Facades\Config;

test('facade get() return Country from session', function (): void {
    Config::set('meridian.default_language_code', 'fr');
    Language::factory()->create(['code' => 'fr']);

    $result = MeridianLanguage::get();

    expect($result)->toBeInstanceOf(Language::class)
        ->and($result->code)->toBe('fr');
});
