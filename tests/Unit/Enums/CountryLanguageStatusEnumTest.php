<?php

declare(strict_types=1);

use Denprog\Meridian\Enums\CountryLanguageStatusEnum;

it('can be created from string value', function (CountryLanguageStatusEnum $continent): void {
    expect(CountryLanguageStatusEnum::from($continent->value))->toBe($continent);
})->with(CountryLanguageStatusEnum::cases());

it('return 6 statuses', function (): void {
    expect(count(CountryLanguageStatusEnum::values()))->toBe(6);
});
