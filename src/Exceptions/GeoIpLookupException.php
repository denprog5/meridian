<?php

namespace Denprog\Meridian\Exceptions;

use Exception;

/**
 * Class GeoIpLookupException
 *
 * Thrown when a GeoIP lookup fails for reasons other than an invalid IP or database issue
 * (e.g., service unavailable, API error, unexpected data format from provider).
 */
class GeoIpLookupException extends Exception
{
    // You can add custom properties or methods if needed in the future.
}
