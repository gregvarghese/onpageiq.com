<?php

namespace App\Enums;

/**
 * Status values for individual accessibility checks.
 */
enum CheckStatus: string
{
    case Pass = 'pass';
    case Fail = 'fail';
    case Warning = 'warning';
    case ManualReview = 'manual_review';
    case NotApplicable = 'not_applicable';
    case Opportunity = 'opportunity';

    /**
     * Get all status values.
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
            self::Pass => 'Pass',
            self::Fail => 'Fail',
            self::Warning => 'Warning',
            self::ManualReview => 'Manual Review',
            self::NotApplicable => 'Not Applicable',
            self::Opportunity => 'Opportunity',
        };
    }

    /**
     * Get the description for this status.
     */
    public function description(): string
    {
        return match ($this) {
            self::Pass => 'The criterion was successfully met',
            self::Fail => 'The criterion was not met - action required',
            self::Warning => 'Potential issue that needs human verification',
            self::ManualReview => 'Cannot be automatically tested - requires manual review',
            self::NotApplicable => 'This criterion does not apply to this content',
            self::Opportunity => 'Enhancement opportunity (AAA criteria)',
        };
    }

    /**
     * Get the color for UI display.
     */
    public function color(): string
    {
        return match ($this) {
            self::Pass => 'green',
            self::Fail => 'red',
            self::Warning => 'yellow',
            self::ManualReview => 'purple',
            self::NotApplicable => 'gray',
            self::Opportunity => 'blue',
        };
    }

    /**
     * Get the icon name for this status.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Pass => 'check-circle',
            self::Fail => 'x-circle',
            self::Warning => 'exclamation-triangle',
            self::ManualReview => 'eye',
            self::NotApplicable => 'minus-circle',
            self::Opportunity => 'light-bulb',
        };
    }

    /**
     * Check if this status counts as a failure.
     */
    public function isFailure(): bool
    {
        return $this === self::Fail;
    }

    /**
     * Check if this status requires attention.
     */
    public function requiresAttention(): bool
    {
        return in_array($this, [self::Fail, self::Warning, self::ManualReview]);
    }

    /**
     * Get all statuses as options array for forms.
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
