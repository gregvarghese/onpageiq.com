<?php

namespace App\Enums;

enum ManualTestStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Passed = 'passed';
    case Failed = 'failed';
    case Blocked = 'blocked';
    case Skipped = 'skipped';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::InProgress => 'In Progress',
            self::Passed => 'Passed',
            self::Failed => 'Failed',
            self::Blocked => 'Blocked',
            self::Skipped => 'Skipped',
        };
    }

    /**
     * Get badge classes for Tailwind.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Pending => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
            self::InProgress => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
            self::Passed => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            self::Failed => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
            self::Blocked => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
            self::Skipped => 'bg-gray-100 text-gray-500 dark:bg-gray-900 dark:text-gray-400',
        };
    }

    /**
     * Check if this is a terminal status.
     */
    public function isTerminal(): bool
    {
        return in_array($this, [self::Passed, self::Failed, self::Blocked, self::Skipped]);
    }

    /**
     * Check if this indicates success.
     */
    public function isSuccess(): bool
    {
        return $this === self::Passed;
    }
}
