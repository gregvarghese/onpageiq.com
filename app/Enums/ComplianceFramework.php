<?php

namespace App\Enums;

/**
 * Accessibility compliance frameworks.
 */
enum ComplianceFramework: string
{
    case Wcag21 = 'wcag21';
    case Wcag22 = 'wcag22';
    case Section508 = 'section508';
    case En301549 = 'en301549';
    case Ada = 'ada';

    /**
     * Get all framework values.
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
            self::Wcag21 => 'WCAG 2.1',
            self::Wcag22 => 'WCAG 2.2',
            self::Section508 => 'Section 508',
            self::En301549 => 'EN 301 549',
            self::Ada => 'ADA',
        };
    }

    /**
     * Get the full name for this framework.
     */
    public function fullName(): string
    {
        return match ($this) {
            self::Wcag21 => 'Web Content Accessibility Guidelines 2.1',
            self::Wcag22 => 'Web Content Accessibility Guidelines 2.2',
            self::Section508 => 'Section 508 of the Rehabilitation Act',
            self::En301549 => 'EN 301 549 European Accessibility Standard',
            self::Ada => 'Americans with Disabilities Act',
        };
    }

    /**
     * Get the documentation URL for this framework.
     */
    public function documentationUrl(): string
    {
        return match ($this) {
            self::Wcag21 => 'https://www.w3.org/TR/WCAG21/',
            self::Wcag22 => 'https://www.w3.org/TR/WCAG22/',
            self::Section508 => 'https://www.section508.gov/',
            self::En301549 => 'https://www.etsi.org/deliver/etsi_en/301500_301599/301549/',
            self::Ada => 'https://www.ada.gov/',
        };
    }

    /**
     * Get the color for UI display.
     */
    public function color(): string
    {
        return match ($this) {
            self::Wcag21 => 'blue',
            self::Wcag22 => 'indigo',
            self::Section508 => 'red',
            self::En301549 => 'green',
            self::Ada => 'purple',
        };
    }

    /**
     * Get all frameworks as options array for forms.
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
