<?php

declare(strict_types=1);

namespace Denprog\Meridian\Tests\Feature\Database;

use Denprog\Meridian\Database\Seeders\CountrySeeder;
use Denprog\Meridian\Models\Country;
use Illuminate\Console\Command;
use Mockery\MockInterface;
use Symfony\Component\Console\Output\OutputInterface;

it('populates the database using CountrySeeder', function (): void {
    expect(Country::query()->count())->toBe(0);

    $mockCommand = $this->mock(Command::class, function (MockInterface $mock): void {
        $outputMock = $this->mock(OutputInterface::class);
        $outputMock->shouldReceive('progressStart')->zeroOrMoreTimes();
        $outputMock->shouldReceive('progressAdvance')->zeroOrMoreTimes();
        $outputMock->shouldReceive('progressFinish')->zeroOrMoreTimes();
        $mock->shouldReceive('getOutput')->zeroOrMoreTimes()->andReturn($outputMock);
        $mock->shouldReceive('info')->zeroOrMoreTimes();
        $mock->shouldReceive('line')->zeroOrMoreTimes();
        $mock->shouldReceive('error')->zeroOrMoreTimes();
        $mock->shouldReceive('warn')->zeroOrMoreTimes();
    });

    $seeder = new CountrySeeder();
    $seeder->setCommand($mockCommand);
    $seeder->run();

    expect(Country::query()->count())->toBeGreaterThan(0);

    $usa = Country::query()->where('iso_alpha_2', 'US')->first();
    expect($usa)->not->toBeNull()
        ->and($usa->name)->toBe('United States');

    $germany = Country::query()->where('iso_alpha_2', 'DE')->first();
    expect($germany)->not->toBeNull()
        ->and($germany->name)->toBe('Germany')
        ->and(Country::query()->count())->toBe(250);
});
