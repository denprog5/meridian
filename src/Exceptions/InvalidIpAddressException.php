<?php

declare(strict_types=1);

namespace Denprog\Meridian\Exceptions;

use Throwable;

/**
 * Exception thrown when an invalid IP address is provided.
 */
class InvalidIpAddressException extends MeridianException
{
    /**
     * Create a new invalid IP address exception.
     */
    public function __construct(
        string $message,
        public readonly ?string $ipAddress = null,
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
            'ip_address' => $this->ipAddress,
        ]);
    }
}
