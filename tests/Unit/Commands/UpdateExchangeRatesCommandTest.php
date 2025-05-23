<?php

declare(strict_types=1);

namespace Tests\Unit\Commands;

it('can run the update exchange rates command', function (): void {
    $this->artisan('meridian:update-exchange-rates')->assertSuccessful();
});
