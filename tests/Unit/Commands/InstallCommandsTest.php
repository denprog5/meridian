<?php

declare(strict_types=1);

namespace Denprog\Meridian\Tests\Unit\Commands;

it('can run the install command', function (): void {
    $this->artisan('meridian:install')->assertSuccessful();
});
