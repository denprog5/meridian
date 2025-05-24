<?php

declare(strict_types=1);

namespace Denprog\Meridian\Tests\Feature\Database;

use Denprog\Meridian\Models\Language;
use Illuminate\Support\Facades\Config;

test('can seed languages', function (): void {
    $this->artisan('db:seed --class=Denprog\\\Meridian\\\Database\\\Seeders\\\LanguageSeeder')->assertExitCode(0);

    $this->assertDatabaseHas('languages', [
        'code' => 'en',
    ]);
});

it('contains active and inactive languages', function (): void {
    Config::set('meridian.active_languages', ['en', 'fr']);
    $this->artisan('db:seed --class=Denprog\\\Meridian\\\Database\\\Seeders\\\LanguageSeeder')->assertExitCode(0);

    $activeLanguage = Language::query()->where('code', 'en')->first();
    $inactiveLanguage = Language::query()->where('code', 'de')->first();

    expect($activeLanguage->is_active)->toBeTrue();
    expect($inactiveLanguage->is_active)->toBeFalse();
});
