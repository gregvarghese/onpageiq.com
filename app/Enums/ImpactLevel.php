<?php

namespace App\Enums;

enum ImpactLevel: string
{
    case Critical = 'critical';
    case Serious = 'serious';
    case Moderate = 'moderate';
    case Minor = 'minor';

    public function label(): string
    {
        return match ($this) {
            self::Critical => 'Critical',
            self::Serious => 'Serious',
            self::Moderate => 'Moderate',
            self::Minor => 'Minor',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Critical => '#dc2626',
            self::Serious => '#ea580c',
            self::Moderate => '#ca8a04',
            self::Minor => '#2563eb',
        };
    }

    public function tailwindColor(): string
    {
        return match ($this) {
            self::Critical => 'red',
            self::Serious => 'orange',
            self::Moderate => 'yellow',
            self::Minor => 'blue',
        };
    }

    public function weight(): int
    {
        return match ($this) {
            self::Critical => 4,
            self::Serious => 3,
            self::Moderate => 2,
            self::Minor => 1,
        };
    }

    public function isCritical(): bool
    {
        return $this === self::Critical;
    }

    public function isSerious(): bool
    {
        return $this === self::Serious;
    }

    public function isHighPriority(): bool
    {
        return in_array($this, [self::Critical, self::Serious], true);
    }
}
