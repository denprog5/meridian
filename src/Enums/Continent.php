<?php

declare(strict_types=1);

namespace Denprog\Meridian\Enums;

enum Continent: string
{
    case AFRICA = 'AF';
    case ANTARCTICA = 'AN';
    case ASIA = 'AS';
    case EUROPE = 'EU';
    case NORTH_AMERICA = 'NA';
    case OCEANIA = 'OC';
    case SOUTH_AMERICA = 'SA';

    public function name(): string
    {
        return match ($this) {
            self::AFRICA => 'Africa',
            self::ANTARCTICA => 'Antarctica',
            self::ASIA => 'Asia',
            self::EUROPE => 'Europe',
            self::NORTH_AMERICA => 'North America',
            self::OCEANIA => 'Oceania',
            self::SOUTH_AMERICA => 'South America',
        };
    }

    /**
     * Get the localized name of the continent.
     */
    public function localizedName(?string $locale = null): string
    {
        $key = "meridian::continents.$this->value";
        $translated = trans(key: $key, locale: $locale);

        return $translated === $key || is_array($translated) ? $this->name() : $translated;
    }
}
