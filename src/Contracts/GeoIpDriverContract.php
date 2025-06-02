<?php

declare(strict_types=1);

namespace Denprog\Meridian\Contracts;

use Denprog\Meridian\DataTransferObjects\LocationData;
use Denprog\Meridian\Exceptions\GeoIpDatabaseException;
use Denprog\Meridian\Exceptions\GeoIpLookupException;
use Denprog\Meridian\Exceptions\InvalidIpAddressException;

/**
 * Interface GeoIpDriverContract
 *
 * Defines the contract for a GeoIP driver that can look up IP address information.
 */
interface GeoIpDriverContract
{
    /**
     * Looks up geolocation data for the given IP address using this specific driver.
     *
     * @param  string  $ipAddress  The IP address to look up.
     * @return LocationData The geolocation data.
     *
     * @throws InvalidIpAddressException If the IP address is invalid.
     * @throws GeoIpDatabaseException If there's an issue with the GeoIP database (e.g., not found, corrupted).
     * @throws GeoIpLookupException If the lookup fails for other reasons specific to the driver.
     */
    public function lookup(string $ipAddress): LocationData;

    /**
     * Gets a unique string identifier for this driver.
     *
     * E.g., "maxmind_database", "ip_api_com".
     */
    public function getIdentifier(): string;
}
