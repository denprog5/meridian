<?php

namespace Denprog\Meridian\Contracts;

use Denprog\Meridian\DataTransferObjects\LocationData;

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
     * @param string $ipAddress The IP address to look up.
     * @return LocationData The geolocation data.
     * @throws \Denprog\Meridian\Exceptions\InvalidIpAddressException If the IP address is invalid.
     * @throws \Denprog\Meridian\Exceptions\GeoIpLookupException If the lookup fails for other reasons.
     */
    public function lookup(string $ipAddress): LocationData;

    /**
     * Gets an identifier for the currently configured GeoIP driver.
     *
     * @return string
     */
    public function getDriverIdentifier(): string;

    /**
     * Stores the given LocationData in the session, if session storage is enabled.
     *
     * @param LocationData $locationData The location data to store.
     * @return void
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
     *
     * @return void
     */
    public function clearLocationFromSession(): void;
}
