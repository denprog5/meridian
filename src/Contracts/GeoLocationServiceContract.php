<?php

declare(strict_types=1);

namespace Denprog\Meridian\Contracts;

use Denprog\Meridian\DataTransferObjects\LocationData;
use Denprog\Meridian\Exceptions\GeoIpLookupException;
use Denprog\Meridian\Exceptions\InvalidIpAddressException;

/**
 * Interface GeoLocationServiceContract
 *
 * Defines the contract for a service that provides geolocation information for IP addresses.
 */
interface GeoLocationServiceContract
{
    /**
     * Looks up geolocation data for the given IP address.
     *
     * @param  string  $ipAddress  The IP address to look up.
     * @return LocationData The geolocation data.
     *
     * @throws InvalidIpAddressException If the IP address is invalid.
     * @throws GeoIpLookupException If the lookup fails for other reasons.
     */
    public function lookup(string $ipAddress): LocationData;

    /**
     * Gets an identifier for the currently configured GeoIP driver.
     */
    public function getDriverIdentifier(): string;

    /**
     * Stores the given LocationData in the session, if session storage is enabled.
     *
     * @param  LocationData  $locationData  The location data to store.
     */
    public function storeLocationInSession(LocationData $locationData): void;

    /**
     * Retrieves LocationData from the session, if available and session storage is enabled.
     *
     * @return LocationData|null The location data from session, or null if not found or not enabled.
     */
    public function getLocationFromSession(): ?LocationData;

    /**
     * Clears any stored LocationData from the session.
     */
    public function clearLocationFromSession(): void;
}
