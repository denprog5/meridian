<?php

declare(strict_types=1);

namespace Denprog\Meridian\Exceptions;

use Exception;
use Throwable;

/**
 * Base exception for all Meridian package exceptions.
 *
 * Provides a common interface and context method for structured logging.
 */
abstract class MeridianException extends Exception
{
    /**
     * Create a new exception instance.
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the exception context for structured logging.
     *
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return [];
    }
}
