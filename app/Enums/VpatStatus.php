<?php

namespace App\Enums;

enum VpatStatus: string
{
    case Draft = 'draft';
    case InReview = 'in_review';
    case Approved = 'approved';
    case Published = 'published';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::InReview => 'In Review',
            self::Approved => 'Approved',
            self::Published => 'Published',
        };
    }

    /**
     * Get badge classes for Tailwind.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Draft => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
            self::InReview => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
            self::Approved => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            self::Published => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        };
    }

    /**
     * Check if editing is allowed.
     */
    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::InReview]);
    }
}
