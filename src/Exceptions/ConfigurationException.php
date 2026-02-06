<?php

declare(strict_types=1);

namespace Denprog\Meridian\Exceptions;

use Throwable;

/**
 * Exception thrown when there is a misconfiguration or missing configuration.
 */
class ConfigurationException extends MeridianException
{
    /**
     * Create a new configuration exception.
     */
    public function __construct(
        string $message,
        public readonly ?string $configKey = null,
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
            'config_key' => $this->configKey,
        ]);
    }
}
