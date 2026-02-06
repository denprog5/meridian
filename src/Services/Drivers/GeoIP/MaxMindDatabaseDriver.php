<?php

declare(strict_types=1);

namespace Denprog\Meridian\Services\Drivers\GeoIP;

use Closure;
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
 *
 * Implements lazy loading of the Reader to avoid initialization overhead
 * when GeoIP functionality is not used.
 */
final class MaxMindDatabaseDriver implements GeoIpDriverContract
{
    public const string FILE_NAME = 'GeoLite2-City.mmdb';

    private const string DRIVER_IDENTIFIER = 'maxmind_database';

    private string $databasePath;

    /**
     * Lazy-loaded Reader instance wrapped in a Closure for deferred initialization.
     */
    private ?Reader $reader = null;

    /**
     * MaxMindDatabaseDriver constructor.
     *
     * Sets up the database path but defers Reader initialization until first use.
     */
    public function __construct()
    {
        $filename = config()->string(
            'meridian.geolocation.drivers.maxmind_database.database_filename',
            self::FILE_NAME
        );
        $relativePath = config()->string(
            'meridian.geolocation.drivers.maxmind_database.database_path',
            'meridian'
        );
        $this->databasePath = storage_path(
            mb_ltrim($relativePath, '/\\').DIRECTORY_SEPARATOR.$filename
        );
    }

    /**
     * {@inheritdoc}
     */
    public function lookup(string $ipAddress): LocationData
    {
        if (! filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            throw new InvalidIpAddressException("Invalid IP address: $ipAddress", $ipAddress);
        }

        try {
            $reader = $this->getReader();
            $record = $reader->city($ipAddress);

            return LocationData::fromMaxMindRecord($record, $ipAddress);
        } catch (AddressNotFoundException) {
            return LocationData::empty($ipAddress);
        } catch (MaxMindDbInvalidDatabaseException $e) {
            throw new GeoIpDatabaseException(
                "Invalid GeoIP database: {$e->getMessage()}",
                $this->databasePath,
                0,
                $e
            );
        } catch (InvalidArgumentException $e) {
            throw new InvalidIpAddressException(
                "Invalid argument error: {$e->getMessage()}",
                $ipAddress,
                0,
                $e
            );
        } catch (Exception $e) {
            throw new GeoIpLookupException(
                "GeoIP lookup failed: {$e->getMessage()}",
                $ipAddress,
                0,
                $e
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier(): string
    {
        return self::DRIVER_IDENTIFIER;
    }

    /**
     * Get or initialize the MaxMind Reader instance (lazy loading).
     *
     * @throws GeoIpDatabaseException If the database file is not found or is invalid.
     */
    private function getReader(): Reader
    {
        if ($this->reader !== null) {
            return $this->reader;
        }

        if (! file_exists($this->databasePath) || ! is_readable($this->databasePath)) {
            throw new GeoIpDatabaseException(
                'GeoIP database file not found or not readable. '.
                "Run 'php artisan meridian:update-geoip-db' to download it.",
                $this->databasePath
            );
        }

        try {
            $this->reader = new Reader($this->databasePath);
        } catch (MaxMindDbInvalidDatabaseException $e) {
            throw new GeoIpDatabaseException(
                "Invalid GeoIP database: {$e->getMessage()}",
                $this->databasePath,
                0,
                $e
            );
        }

        return $this->reader;
    }
}
