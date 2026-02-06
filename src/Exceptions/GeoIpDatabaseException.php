<?php

declare(strict_types=1);

namespace Denprog\Meridian\Exceptions;

use Throwable;

/**
 * Exception thrown when the GeoIP database is invalid, missing, or corrupted.
 */
class GeoIpDatabaseException extends MeridianException
{
    /**
     * Create a new GeoIP database exception.
     */
    public function __construct(
        string $message,
        public readonly ?string $databasePath = null,
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the exception context for logging.
     *
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return array_filter([
            'database_path' => $this->databasePath,
        ]);
    }
}
