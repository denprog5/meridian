<?php

namespace Denprog\Meridian\Services;

use Denprog\Meridian\Contracts\GeoIpDriverContract;
use Denprog\Meridian\Contracts\GeoLocationServiceContract;
use Denprog\Meridian\DataTransferObjects\LocationData;
use Denprog\Meridian\Exceptions\ConfigurationException;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Session\Store as SessionStore;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

final class GeoLocationService implements GeoLocationServiceContract
{
    private GeoIpDriverContract $driver;
    private bool $sessionEnabled;
    private string $sessionKey;

    /**
     * GeoLocationService constructor.
     *
     * @param Application $app
     * @param ConfigRepository $config
     * @throws ConfigurationException
     */
    public function __construct(
        private readonly Application $app,
        private readonly ConfigRepository $config,
        private readonly ?SessionStore $session // Nullable for cases where session might not be available (e.g. console)
    )
    {
        $this->configureDriver();
        $this->sessionEnabled = (bool) $this->config->get('meridian.geolocation.session.store', false);
        $this->sessionKey = (string) $this->config->get('meridian.geolocation.session.key', 'meridian_location');
    }

    /**
     * Configures the GeoIP driver based on the package configuration.
     *
     * @throws ConfigurationException
     */
    private function configureDriver(): void
    {
        $driverAlias = $this->config->get('meridian.geolocation.driver');
        if (!$driverAlias) {
            throw new ConfigurationException('No GeoIP driver specified in configuration.');
        }

        // Attempt to resolve the driver from the service container
        // This assumes drivers are bound with a specific key or can be resolved via their class name
        // For a simple package, we might directly instantiate or use a factory pattern.
        // For now, let's assume a direct binding or resolvable class for the configured driver.
        // Example: 'meridian.geoip.driver.maxmind_database' => MaxMindDatabaseDriver::class

        // A more robust way would be to have a dedicated factory or manager class.
        // For this iteration, we'll assume the config points to a service container alias
        // or a fully qualified class name that can be resolved.

        // Simplified approach: Check if the alias matches our known MaxMind driver for now.
        // This needs to be more flexible if multiple drivers are to be supported dynamically.
        if ($driverAlias === 'maxmind_database') {
            // We expect MaxMindDatabaseDriver to be bound or resolvable.
            // If it's bound using its FQCN or a specific alias, $app->make() should work.
            // We'll rely on service provider bindings for this.
            $this->driver = $this->app->make(GeoIpDriverContract::class); // Assumes default binding
        } else {
            // Try to make the driver by its configured alias directly if it's a FQCN or an alias
            try {
                $driverInstance = $this->app->make($driverAlias);
                if (!$driverInstance instanceof GeoIpDriverContract) {
                    throw new ConfigurationException(
                        "The configured GeoIP driver '{$driverAlias}' does not implement GeoIpDriverContract."
                    );
                }
                $this->driver = $driverInstance;
            } catch (\Exception $e) {
                throw new ConfigurationException(
                    "Could not resolve GeoIP driver '{$driverAlias}': " . $e->getMessage(), 0, $e
                );
            }
        }

        if (!$this->driver instanceof GeoIpDriverContract) {
             throw new ConfigurationException("Failed to initialize a valid GeoIP driver for '{$driverAlias}'.");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function lookup(string $ipAddress): LocationData
    {
        // Validate IP address format
        $validator = Validator::make(['ip' => $ipAddress], ['ip' => 'ip']);
        if ($validator->fails()) {
            Log::debug("Invalid IP address format provided to GeoLocationService: {$ipAddress}");
            return LocationData::empty($ipAddress); // Return empty DTO with the original IP
        }

        try {
            $location = $this->driver->lookup($ipAddress);

            if ($this->sessionEnabled && $this->session && !$location->isEmpty()) {
                $this->storeLocationInSession($location);
            }

            return $location;
        } catch (\GeoIp2\Exception\AddressNotFoundException $e) {
            // Specific handling for address not found, which is a common case
            Log::info("GeoIP address not found for {$ipAddress}: " . $e->getMessage());
            return LocationData::empty($ipAddress); // Return empty DTO with the original IP
        } catch (\Exception $e) {
            // Catch all other exceptions from the driver or during processing
            Log::error("Error during GeoIP lookup for {$ipAddress}: " . $e->getMessage(), ['exception' => $e]);
            return LocationData::empty($ipAddress); // Return empty DTO with the original IP
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
        if ($this->sessionEnabled && $this->session) {
            $this->session->put($this->sessionKey, $locationData->toArray());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getLocationFromSession(): ?LocationData
    {
        if (!$this->sessionEnabled || !$this->session) {
            return null;
        }

        $data = $this->session->get($this->sessionKey);

        if (is_array($data) && !empty($data['ipAddress'])) {
            // Reconstruct DTO from array. A factory method on LocationData might be cleaner.
            return new LocationData(
                ipAddress: (string) ($data['ipAddress'] ?? ''),
                countryCode: isset($data['countryCode']) ? (string) $data['countryCode'] : null,
                countryName: isset($data['countryName']) ? (string) $data['countryName'] : null,
                cityName: isset($data['cityName']) ? (string) $data['cityName'] : null,
                postalCode: isset($data['postalCode']) ? (string) $data['postalCode'] : null,
                latitude: isset($data['latitude']) ? (float) $data['latitude'] : null,
                longitude: isset($data['longitude']) ? (float) $data['longitude'] : null,
                timezone: isset($data['timezone']) ? (string) $data['timezone'] : null,
                accuracyRadius: isset($data['accuracyRadius']) ? (int) $data['accuracyRadius'] : null,
                isInEuropeanUnion: (bool) ($data['isInEuropeanUnion'] ?? false),
                raw: isset($data['raw']) && is_array($data['raw']) ? $data['raw'] : null
            );
        }
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function clearLocationFromSession(): void
    {
        if ($this->sessionEnabled && $this->session) {
            $this->session->forget($this->sessionKey);
        }
    }
}
