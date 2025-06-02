<?php

namespace Denprog\Meridian\DataTransferObjects;

use GeoIp2\Model\City as MaxMindCity;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;

/**
 * Represents geolocation data for an IP address.
 */
final readonly class LocationData implements Arrayable, Jsonable, JsonSerializable
{
    /**
     * LocationData constructor.
     *
     * @param string $ipAddress The IP address that was looked up.
     * @param string|null $countryCode ISO 3166-1 alpha-2 country code (e.g., "US").
     * @param string|null $countryName Country name (e.g., "United States").
     * @param string|null $cityName City name (e.g., "Mountain View").
     * @param string|null $postalCode Postal code (e.g., "94043").
     * @param float|null $latitude Latitude.
     * @param float|null $longitude Longitude.
     * @param string|null $timezone Timezone (e.g., "America/Los_Angeles").
     * @param int|null $accuracyRadius The radius in kilometers around the latitude/longitude.
     * @param bool $isInEuropeanUnion True if the IP address is in a country in the European Union.
     * @param array<mixed>|null $raw Optionally, the raw data array from the GeoIP provider.
     */
    public function __construct(
        public string $ipAddress,
        public ?string $countryCode = null,
        public ?string $countryName = null,
        public ?string $cityName = null,
        public ?string $postalCode = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public ?string $timezone = null,
        public ?int $accuracyRadius = null,
        public bool $isInEuropeanUnion = false,
        public ?array $raw = null
    ) {
    }

    /**
     * Creates a LocationData DTO from a MaxMind City record.
     *
     * @param MaxMindCity $record The MaxMind City record.
     * @param string $ipAddress The IP address that was looked up.
     * @return self
     */
    public static function fromMaxMindRecord(MaxMindCity $record, string $ipAddress): self
    {
        return new self(
            ipAddress: $ipAddress,
            countryCode: $record->country->isoCode ?? null,
            countryName: $record->country->name ?? null,
            cityName: $record->city->name ?? null,
            postalCode: $record->postal->code ?? null,
            latitude: $record->location->latitude ?? null,
            longitude: $record->location->longitude ?? null,
            timezone: $record->location->timeZone ?? null,
            accuracyRadius: $record->location->accuracyRadius ?? null,
            isInEuropeanUnion: $record->country->isInEuropeanUnion ?? false,
            raw: $record->raw ?? null
        );
    }

    /**
     * Creates an empty LocationData DTO for a given IP address.
     *
     * @param string $ipAddress The IP address for which the lookup failed or was not found.
     * @return self
     */
    public static function empty(string $ipAddress): self
    {
        return new self(ipAddress: $ipAddress);
    }

    /**
     * Get the instance as an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'ipAddress' => $this->ipAddress,
            'countryCode' => $this->countryCode,
            'countryName' => $this->countryName,
            'cityName' => $this->cityName,
            'postalCode' => $this->postalCode,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'timezone' => $this->timezone,
            'accuracyRadius' => $this->accuracyRadius,
            'isInEuropeanUnion' => $this->isInEuropeanUnion,
            'raw' => $this->raw,
        ];
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param int $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Specify data which should be serialized to JSON.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
