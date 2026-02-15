<?php

namespace App\Enums;

/**
 * Types of evidence for VPAT documentation.
 */
enum EvidenceType: string
{
    case Screenshot = 'screenshot';
    case Recording = 'recording';
    case Note = 'note';
    case Link = 'link';
    case Document = 'document';

    /**
     * Get all evidence type values.
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
            self::Screenshot => 'Screenshot',
            self::Recording => 'Screen Recording',
            self::Note => 'Note',
            self::Link => 'External Link',
            self::Document => 'Document',
        };
    }

    /**
     * Get the icon name for this evidence type.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Screenshot => 'camera',
            self::Recording => 'video-camera',
            self::Note => 'document-text',
            self::Link => 'link',
            self::Document => 'document',
        };
    }

    /**
     * Get the color for UI display.
     */
    public function color(): string
    {
        return match ($this) {
            self::Screenshot => 'blue',
            self::Recording => 'purple',
            self::Note => 'green',
            self::Link => 'cyan',
            self::Document => 'orange',
        };
    }

    /**
     * Check if this evidence type requires a file upload.
     */
    public function requiresFile(): bool
    {
        return in_array($this, [self::Screenshot, self::Recording, self::Document]);
    }

    /**
     * Get all evidence types as options array for forms.
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
