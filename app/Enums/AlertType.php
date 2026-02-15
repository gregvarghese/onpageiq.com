<?php

namespace App\Enums;

enum AlertType: string
{
    case ScoreThreshold = 'score_threshold';
    case NewCriticalIssue = 'new_critical_issue';
    case Regression = 'regression';
    case DeadlineReminder = 'deadline_reminder';
    case DeadlinePassed = 'deadline_passed';
    case AuditComplete = 'audit_complete';
    case IssueFixed = 'issue_fixed';

    /**
     * Get the human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::ScoreThreshold => 'Score Threshold Breach',
            self::NewCriticalIssue => 'New Critical Issue',
            self::Regression => 'Regression Detected',
            self::DeadlineReminder => 'Deadline Reminder',
            self::DeadlinePassed => 'Deadline Passed',
            self::AuditComplete => 'Audit Complete',
            self::IssueFixed => 'Issue Fixed',
        };
    }

    /**
     * Get the severity level (for sorting/filtering).
     */
    public function severity(): string
    {
        return match ($this) {
            self::DeadlinePassed, self::NewCriticalIssue => 'critical',
            self::Regression, self::ScoreThreshold => 'serious',
            self::DeadlineReminder => 'moderate',
            self::AuditComplete, self::IssueFixed => 'info',
        };
    }

    /**
     * Get the color for UI display.
     */
    public function color(): string
    {
        return match ($this) {
            self::DeadlinePassed, self::NewCriticalIssue => 'red',
            self::Regression, self::ScoreThreshold => 'orange',
            self::DeadlineReminder => 'yellow',
            self::AuditComplete => 'blue',
            self::IssueFixed => 'green',
        };
    }

    /**
     * Get the icon for UI display.
     */
    public function icon(): string
    {
        return match ($this) {
            self::ScoreThreshold => 'heroicon-o-chart-bar-square',
            self::NewCriticalIssue => 'heroicon-o-exclamation-triangle',
            self::Regression => 'heroicon-o-arrow-trending-down',
            self::DeadlineReminder => 'heroicon-o-clock',
            self::DeadlinePassed => 'heroicon-o-x-circle',
            self::AuditComplete => 'heroicon-o-check-circle',
            self::IssueFixed => 'heroicon-o-check-badge',
        };
    }

    /**
     * Should this alert send an email?
     */
    public function shouldEmail(): bool
    {
        return match ($this) {
            self::DeadlinePassed, self::NewCriticalIssue, self::Regression => true,
            self::ScoreThreshold, self::DeadlineReminder => true,
            self::AuditComplete, self::IssueFixed => false,
        };
    }
}
