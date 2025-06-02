<?php

declare(strict_types=1);

namespace Denprog\Meridian\Services\Drivers\GeoIP;

use Denprog\Meridian\Contracts\GeoIpDriverContract;
use Denprog\Meridian\DataTransferObjects\LocationData;
use Denprog\Meridian\Exceptions\GeoIpDatabaseException;
use Denprog\Meridian\Exceptions\GeoIpLookupException;
use Denprog\Meridian\Exceptions\InvalidIpAddressException;
use Exception;
use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use InvalidArgumentException;
use MaxMind\Db\Reader\InvalidDatabaseException as MaxMindDbInvalidDatabaseException;

/**
 * MaxMind Database Driver for GeoIP lookups.
 */
final readonly class MaxMindDatabaseDriver implements GeoIpDriverContract
{
    private Reader $reader;
    private const string DRIVER_IDENTIFIER = 'maxmind_database';

    private string $databasePath;

    /**
     * MaxMindDatabaseDriver constructor.
     *
     * @throws GeoIpDatabaseException
     */
    public function __construct() {
        $relativePath = config()->string('meridian.geolocation.drivers.maxmind_database.database_path', 'meridian/geoip/GeoLite2-City.mmdb');
        $this->databasePath = storage_path('app/'.mb_ltrim($relativePath, '/\\'));
        try {
            $this->reader = new Reader($this->databasePath);
        } catch (MaxMindDbInvalidDatabaseException $e) {
            throw new GeoIpDatabaseException("Invalid GeoIP database: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function lookup(string $ipAddress): LocationData
    {
        if (! filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            throw new InvalidIpAddressException("Invalid IP address: $ipAddress");
        }

        if (! file_exists($this->databasePath) || ! is_readable($this->databasePath)) {
            throw new GeoIpDatabaseException("GeoIP database file not found or not readable at path: $this->databasePath");
        }

        try {
            $record = $this->reader->city($ipAddress);

            return LocationData::fromMaxMindRecord($record, $ipAddress);
        } catch (AddressNotFoundException) {
            return LocationData::empty($ipAddress);
        } catch (MaxMindDbInvalidDatabaseException $e) {
            throw new GeoIpDatabaseException("Invalid GeoIP database: {$e->getMessage()}", 0, $e);
        } catch (InvalidArgumentException $e) { // Thrown by Reader constructor for invalid locale
            throw new InvalidArgumentException("Invalid argument error: {$e->getMessage()}", 0, $e);
        } catch (Exception $e) {
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
