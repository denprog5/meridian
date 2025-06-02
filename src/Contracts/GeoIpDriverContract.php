<?php

namespace Denprog\Meridian\Contracts;

use Denprog\Meridian\DataTransferObjects\LocationData;

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
     * @param string $ipAddress The IP address to look up.
     * @return LocationData The geolocation data.
     * @throws \Denprog\Meridian\Exceptions\InvalidIpAddressException If the IP address is invalid.
     * @throws \Denprog\Meridian\Exceptions\GeoIpDatabaseException If there's an issue with the GeoIP database (e.g., not found, corrupted).
     * @throws \Denprog\Meridian\Exceptions\GeoIpLookupException If the lookup fails for other reasons specific to the driver.
     */
    public function lookup(string $ipAddress): LocationData;

    /**
     * Gets a unique string identifier for this driver.
     *
     * E.g., "maxmind_database", "ip_api_com".
     *
     * @return string
     */
    public function getIdentifier(): string;
}
