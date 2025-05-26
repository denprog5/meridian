<?php

declare(strict_types=1);

namespace Denprog\Meridian\Enums;

/**
 * Enum for the status of a language in a country.
 */
enum CountryLanguageStatusEnum: string
{
    case OFFICIAL = 'official';
    case NATIONAL = 'national';
    case REGIONAL_OFFICIAL = 'regional_official';
    case REGIONAL = 'regional';
    case MINORITY = 'minority';
    case LINGUA_FRANCA = 'lingua_franca';

    /**
     * Get all enum values.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
