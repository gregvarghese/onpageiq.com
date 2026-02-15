<?php

namespace App\Enums;

/**
 * WCAG conformance levels (A, AA, AAA).
 */
enum WcagLevel: string
{
    case A = 'A';
    case AA = 'AA';
    case AAA = 'AAA';

    /**
     * Get all level values.
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
            self::A => 'Level A',
            self::AA => 'Level AA',
            self::AAA => 'Level AAA',
        };
    }

    /**
     * Get the description for this level.
     */
    public function description(): string
    {
        return match ($this) {
            self::A => 'Minimum level of accessibility - essential requirements',
            self::AA => 'Standard level of accessibility - commonly required by regulations',
            self::AAA => 'Highest level of accessibility - enhanced requirements',
        };
    }

    /**
     * Get the color for UI display.
     */
    public function color(): string
    {
        return match ($this) {
            self::A => 'amber',
            self::AA => 'blue',
            self::AAA => 'purple',
        };
    }

    /**
     * Check if this level includes another level.
     */
    public function includes(WcagLevel $level): bool
    {
        return match ($this) {
            self::A => $level === self::A,
            self::AA => in_array($level, [self::A, self::AA]),
            self::AAA => true,
        };
    }

    /**
     * Get all levels as options array for forms.
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
