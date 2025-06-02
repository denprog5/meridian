<?php

declare(strict_types=1);

namespace Denprog\Meridian\Enums;

/**
 * Enum for the status of a language in a country.
 */
enum CountryLanguageStatusEnum: string
{
    // Существующие
    case OFFICIAL = 'official';
    case NATIONAL = 'national';
    case REGIONAL_OFFICIAL = 'regional_official';
    case REGIONAL = 'regional';
    case MINORITY = 'minority';
    case LINGUA_FRANCA = 'lingua_franca';
    case RECOGNIZED = 'recognized';
    case WORKING = 'working';
    case OFFICIAL_ADDITIONAL = 'official_additional';
    case OFFICIAL_NATIONAL = 'official_national';
    case OFFICIAL_SECONDARY = 'official_secondary';
    case SPECIAL_STATUS = 'special_status';
    case STATE = 'state';
    case MUNICIPAL_OFFICIAL = 'municipal_official';
    case ADMINISTRATIVE = 'administrative';
    case WIDELY_USED = 'widely_used';
    case DE_FACTO_NATIONAL = 'de_facto_national';
    case WIDELY_SPOKEN = 'widely_spoken';
    case MAJOR = 'major';
    case CO_OFFICIAL = 'co_official';
    case LINK_LANGUAGE = 'link_language';
    case INTER_ETHNIC_COMMUNICATION = 'inter_ethnic_communication';

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
