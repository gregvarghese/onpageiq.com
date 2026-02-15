<?php

namespace App\Enums;

/**
 * Impact severity levels for accessibility issues.
 */
enum ImpactLevel: string
{
    case Critical = 'critical';
    case Serious = 'serious';
    case Moderate = 'moderate';
    case Minor = 'minor';

    /**
     * Get all impact level values.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Critical => 'Critical',
            self::Serious => 'Serious',
            self::Moderate => 'Moderate',
            self::Minor => 'Minor',
        };
    }

    /**
     * Get the description for this impact level.
     */
    public function description(): string
    {
        return match ($this) {
            self::Critical => 'Completely blocks access for some users - must fix immediately',
            self::Serious => 'Creates significant barriers - high priority fix',
            self::Moderate => 'Causes some difficulty - should be addressed',
            self::Minor => 'Minor inconvenience - fix when possible',
        };
    }

    /**
     * Get the color for UI display.
     */
    public function color(): string
    {
        return match ($this) {
            self::Critical => 'red',
            self::Serious => 'orange',
            self::Moderate => 'yellow',
            self::Minor => 'blue',
        };
    }

    /**
     * Get the numeric weight for sorting/scoring.
     */
    public function weight(): int
    {
        return match ($this) {
            self::Critical => 4,
            self::Serious => 3,
            self::Moderate => 2,
            self::Minor => 1,
        };
    }

    /**
     * Get all impact levels as options array for forms.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
