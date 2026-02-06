<?php

declare(strict_types=1);

namespace Denprog\Meridian\Exceptions;

/**
 * Exception thrown when updating the GeoIP database fails.
 */
class GeoIPUpdaterException extends MeridianException
{
    /**
     * Get the exception context for logging.
     *
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return [];
    }
}
