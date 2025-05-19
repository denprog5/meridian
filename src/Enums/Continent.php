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

    public function getName(): string
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
    public function getLocalizedName(?string $locale = null): string
    {
        $key = "meridian::continents.$this->value";
        $translated = trans($key, [], $locale);

        if (is_array($translated)){
            return $this->getName();
        }

        return $translated === $key ? $this->getName() : $translated;
    }

    /**
     * @return array<string, string> An associative array of [code => name]
     */
    public static function all(): array
    {
        $cases = [];
        foreach (self::cases() as $case) {
            $cases[$case->value] = $case->getName();
        }
        return $cases;
    }

    /**
     * @return array<string, string> An associative array of [code => localized name]
     */
    public static function allLocalized(?string $locale = null): array
    {
        $cases = [];
        foreach (self::cases() as $case) {
            $cases[$case->value] = $case->getLocalizedName($locale);
        }
        return $cases;
    }
}
