<?php

namespace Denprog\Meridian\Services\Drivers\GeoIP;

use Denprog\Meridian\Contracts\GeoIpDriverContract;
use Denprog\Meridian\DataTransferObjects\LocationData;
use Denprog\Meridian\Exceptions\ConfigurationException;
use Denprog\Meridian\Exceptions\GeoIpDatabaseException;
use Denprog\Meridian\Exceptions\GeoIpLookupException;
use Denprog\Meridian\Exceptions\InvalidIpAddressException;
use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use MaxMind\Db\Reader\InvalidDatabaseException as MaxMindDbInvalidDatabaseException;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use InvalidArgumentException;

/**
 * MaxMind Database Driver for GeoIP lookups.
 */
final class MaxMindDatabaseDriver implements GeoIpDriverContract
{
    private const DRIVER_IDENTIFIER = 'maxmind_database';

    private readonly string $databasePath;

    /**
     * MaxMindDatabaseDriver constructor.
     *
     * @param ConfigRepository $config
     * @param Reader $reader
     * @throws ConfigurationException If the database path is not configured.
     */
    public function __construct(
        private readonly ConfigRepository $config,
        private readonly Reader $reader
    ) {
        $relativePath = $this->config->get('meridian.geolocation.drivers.maxmind_database.database_path');

        if (empty($relativePath)) {
            throw new ConfigurationException('MaxMind database path is not configured.');
        }
        // Ensure the path is treated as relative to storage/app/
        $this->databasePath = storage_path('app/' . ltrim($relativePath, '/\\'));
        // The injected Reader should ideally be pre-configured with this path,
        // but databasePath is kept for file_exists checks for now.
    }

    /**
     * {@inheritdoc}
     */
    public function lookup(string $ipAddress): LocationData
    {
        if (!filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            throw new InvalidIpAddressException("Invalid IP address: {$ipAddress}");
        }

        if (!file_exists($this->databasePath) || !is_readable($this->databasePath)) {
            throw new GeoIpDatabaseException("GeoIP database file not found or not readable at path: {$this->databasePath}");
        }

        try {
            // Use the injected reader instance
            $record = $this->reader->city($ipAddress);
            return LocationData::fromMaxMindRecord($record, $ipAddress);
        } catch (AddressNotFoundException) {
            // IP address not found in the database, return empty DTO
            return LocationData::empty($ipAddress);
        } catch (MaxMindDbInvalidDatabaseException $e) {
            throw new GeoIpDatabaseException("Invalid GeoIP database: {$e->getMessage()}", 0, $e);
        } catch (InvalidArgumentException $e) { // Thrown by Reader constructor for invalid locale
            throw new ConfigurationException("GeoIP Reader configuration error: {$e->getMessage()}", 0, $e);
        } catch (\Exception $e) {
            // Catch-all for other unexpected errors during lookup
            throw new GeoIpLookupException("GeoIP lookup failed: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier(): string
    {
        return self::DRIVER_IDENTIFIER;
    }
}
