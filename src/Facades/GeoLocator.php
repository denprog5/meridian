<?php

declare(strict_types=1);

namespace Denprog\Meridian\Facades;

use Denprog\Meridian\Contracts\GeoLocationServiceContract;
use Denprog\Meridian\DataTransferObjects\LocationData;
use Illuminate\Support\Facades\Facade;

/**
 * @method static LocationData lookup(string $ipAddress)
 * @method static string getDriverIdentifier()
 * @method static void storeLocationInSession(LocationData $locationData)
 * @method static LocationData|null getLocationFromSession()
 * @method static void clearLocationFromSession()
 *
 * @see GeoLocationServiceContract
 */
class GeoLocator extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return GeoLocationServiceContract::class;
    }
}
