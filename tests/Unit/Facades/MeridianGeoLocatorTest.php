<?php

declare(strict_types=1);

namespace Denprog\Meridian\Tests\Unit\Facades;

use Denprog\Meridian\Facades\MeridianGeoLocator;
use Exception;
use GeoIp2\Database\Reader;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;

it('returns the correct driver identifier and MaxMind driver can load the database', function (): void {
    $relativeDirectoryForConfig = 'meridian_test_data/geoip';
    $absoluteDirectoryToCreate = storage_path('app/'.$relativeDirectoryForConfig);

    $mmdbFileName = 'GeoLite2-City.mmdb';

    $absolutePathToActualFile = $absoluteDirectoryToCreate.'/'.$mmdbFileName;

    $packageRootPath = dirname(__DIR__, 3);
    $pathToResourceMmdb = $packageRootPath.'/resources/'.$mmdbFileName;

    if (! File::exists($pathToResourceMmdb)) {
        $this->fail("Файл ресурса не найден по пути: $pathToResourceMmdb. Пожалуйста, убедитесь, что {$mmdbFileName} находится в директории resources/ вашего пакета.");
    }

    File::ensureDirectoryExists($absoluteDirectoryToCreate);
    File::copy($pathToResourceMmdb, $absolutePathToActualFile);

    Config::set('meridian.geolocation.driver', 'maxmind_database');
    Config::set('meridian.geolocation.drivers.maxmind_database.database_path', $relativeDirectoryForConfig);

    $driverIdentifier = MeridianGeoLocator::getDriverIdentifier();
    expect($driverIdentifier)->toBe('maxmind_database');

    try {
        $readerTest = new Reader($absolutePathToActualFile);
        expect($readerTest)->toBeInstanceOf(Reader::class);

    } catch (Exception $e) {
        $this->fail('Произошла ошибка при попытке загрузить валидную базу данных: '.$e::class.' - '.$e->getMessage()."\nПроверяемый файл: ".$absolutePathToActualFile."\nИсходный файл ресурса: ".$pathToResourceMmdb);
    }

    if (File::exists($absolutePathToActualFile)) {
        File::delete($absolutePathToActualFile);
    }
    if (File::isDirectory($absoluteDirectoryToCreate)) {
        if (count(File::allFiles($absoluteDirectoryToCreate)) === 0 && count(File::directories($absoluteDirectoryToCreate)) === 0) {
            File::deleteDirectory($absoluteDirectoryToCreate);
        }
        $parentOfCreated = dirname($absoluteDirectoryToCreate);
        if (basename($parentOfCreated) === 'meridian_test_data' && File::isDirectory($parentOfCreated) && count(File::allFiles($parentOfCreated)) === 0 && count(File::directories($parentOfCreated)) === 0) {
            File::deleteDirectory($parentOfCreated);
        }
    }
});
