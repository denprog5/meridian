<?php

declare(strict_types=1);

namespace Denprog\Meridian\Services;

use Denprog\Meridian\Contracts\GeoIpDriverContract;
use Denprog\Meridian\Contracts\GeoLocationServiceContract;
use Denprog\Meridian\DataTransferObjects\LocationData;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

final readonly class GeoLocationService implements GeoLocationServiceContract
{
    private string $sessionKey;

    /**
     * GeoLocationService constructor.
     *
     */
    public function __construct(
        private GeoIpDriverContract $driver
    ) {
        $this->sessionKey = config()->string('meridian.geolocation.session.key', 'meridian_location');
    }

    /**
     * {@inheritdoc}
     */
    public function lookup(string $ipAddress): LocationData
    {
        $validator = Validator::make(['ip' => $ipAddress], ['ip' => 'ip']);
        if ($validator->fails()) {
            Log::debug("Invalid IP address format provided to GeoLocationService: $ipAddress");

            return LocationData::empty($ipAddress);
        }

        try {
            $location = $this->driver->lookup($ipAddress);

            $this->storeLocationInSession($location);

            return $location;
        } catch (Exception $e) {
            Log::error("Error during GeoIP lookup for $ipAddress: ".$e->getMessage(), ['exception' => $e]);

            return LocationData::empty($ipAddress);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDriverIdentifier(): string
    {
        return $this->driver->getIdentifier();
    }

    /**
     * {@inheritdoc}
     */
    public function storeLocationInSession(LocationData $locationData): void
    {
        session()->put($this->sessionKey, $locationData->toArray());
    }

    /**
     * {@inheritdoc}
     */
    public function getLocationFromSession(): ?LocationData
    {
        $data = null;
        try {
            /** @var array<string, mixed>|null $data */
            $data = session()->get($this->sessionKey);
        } catch (NotFoundExceptionInterface|ContainerExceptionInterface $e) {
            Log::error("Error retrieving location data from session: ".$e->getMessage(), ['exception' => $e]);
        }

        if (is_array($data)) {
            return LocationData::fromArray($data);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function clearLocationFromSession(): void
    {
        session()->forget($this->sessionKey);
    }
}
