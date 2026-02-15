<?php

namespace App\Enums;

/**
 * Categories of accessibility issues by user impact.
 */
enum AuditCategory: string
{
    case Vision = 'vision';
    case Motor = 'motor';
    case Cognitive = 'cognitive';
    case Hearing = 'hearing';
    case General = 'general';

    /**
     * Get all category values.
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
            self::Vision => 'Vision',
            self::Motor => 'Motor',
            self::Cognitive => 'Cognitive',
            self::Hearing => 'Hearing',
            self::General => 'General',
        };
    }

    /**
     * Get the description for this category.
     */
    public function description(): string
    {
        return match ($this) {
            self::Vision => 'Issues affecting users who are blind, have low vision, or are colorblind',
            self::Motor => 'Issues affecting users with limited mobility or motor control',
            self::Cognitive => 'Issues affecting users with cognitive disabilities or learning differences',
            self::Hearing => 'Issues affecting users who are deaf or hard of hearing',
            self::General => 'Issues that may affect multiple user groups',
        };
    }

    /**
     * Get the icon name for this category.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Vision => 'eye',
            self::Motor => 'hand-raised',
            self::Cognitive => 'light-bulb',
            self::Hearing => 'speaker-wave',
            self::General => 'user-group',
        };
    }

    /**
     * Get the color for UI display.
     */
    public function color(): string
    {
        return match ($this) {
            self::Vision => 'purple',
            self::Motor => 'orange',
            self::Cognitive => 'cyan',
            self::Hearing => 'pink',
            self::General => 'gray',
        };
    }

    /**
     * Get all categories as options array for forms.
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
