<?php

declare(strict_types=1);

use Denprog\Meridian\Enums\Continent;

it('can be created from string value', function (Continent $continent): void {
    expect(Continent::from($continent->value))->toBe($continent);
})->with(Continent::cases());

it('return 7 continents', function (): void {
    expect(count(Continent::cases()))->toBe(7);
});

it('provides correct names', function (Continent $continent): void {
    expect($continent->name())->not()->toBeEmpty()
        ->and($continent->name())->toBeString();
})->with(Continent::cases());

it('provides correct localized names', function (Continent $continent): void {
    expect($continent->localizedName())->not()->toBeEmpty()
        ->and($continent->localizedName())->not()->toBeArray()
        ->and($continent->localizedName())->toBeString();
})->with(Continent::cases());
