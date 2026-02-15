<?php

namespace App\Enums;

enum VpatConformanceLevel: string
{
    case Supports = 'supports';
    case PartiallySupports = 'partially_supports';
    case DoesNotSupport = 'does_not_support';
    case NotApplicable = 'not_applicable';
    case NotEvaluated = 'not_evaluated';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Supports => 'Supports',
            self::PartiallySupports => 'Partially Supports',
            self::DoesNotSupport => 'Does Not Support',
            self::NotApplicable => 'Not Applicable',
            self::NotEvaluated => 'Not Evaluated',
        };
    }

    /**
     * Get description for the conformance level.
     */
    public function description(): string
    {
        return match ($this) {
            self::Supports => 'The functionality of the product has at least one method that meets the criterion without known defects or meets with equivalent facilitation.',
            self::PartiallySupports => 'Some functionality of the product does not meet the criterion.',
            self::DoesNotSupport => 'The majority of product functionality does not meet the criterion.',
            self::NotApplicable => 'The criterion is not relevant to the product.',
            self::NotEvaluated => 'The product has not been evaluated against the criterion.',
        };
    }

    /**
     * Get color for UI display.
     */
    public function color(): string
    {
        return match ($this) {
            self::Supports => 'green',
            self::PartiallySupports => 'yellow',
            self::DoesNotSupport => 'red',
            self::NotApplicable => 'gray',
            self::NotEvaluated => 'gray',
        };
    }

    /**
     * Get badge classes for Tailwind.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Supports => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            self::PartiallySupports => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
            self::DoesNotSupport => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
            self::NotApplicable => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
            self::NotEvaluated => 'bg-gray-100 text-gray-500 dark:bg-gray-900 dark:text-gray-400',
        };
    }
}
