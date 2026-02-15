<?php

namespace App\Enums;

enum WebhookEvent: string
{
    case AuditStarted = 'audit.started';
    case AuditCompleted = 'audit.completed';
    case AuditFailed = 'audit.failed';
    case CriticalIssueFound = 'issue.critical';
    case RegressionDetected = 'regression.detected';
    case ScoreThresholdBreach = 'score.threshold_breach';
    case DeadlineApproaching = 'deadline.approaching';
    case DeadlinePassed = 'deadline.passed';

    /**
     * Get the human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::AuditStarted => 'Audit Started',
            self::AuditCompleted => 'Audit Completed',
            self::AuditFailed => 'Audit Failed',
            self::CriticalIssueFound => 'Critical Issue Found',
            self::RegressionDetected => 'Regression Detected',
            self::ScoreThresholdBreach => 'Score Threshold Breach',
            self::DeadlineApproaching => 'Deadline Approaching',
            self::DeadlinePassed => 'Deadline Passed',
        };
    }

    /**
     * Get the description.
     */
    public function description(): string
    {
        return match ($this) {
            self::AuditStarted => 'Fired when an accessibility audit begins',
            self::AuditCompleted => 'Fired when an accessibility audit completes successfully',
            self::AuditFailed => 'Fired when an accessibility audit fails',
            self::CriticalIssueFound => 'Fired when a critical accessibility issue is detected',
            self::RegressionDetected => 'Fired when accessibility regression is detected',
            self::ScoreThresholdBreach => 'Fired when score drops below configured threshold',
            self::DeadlineApproaching => 'Fired when a compliance deadline is approaching',
            self::DeadlinePassed => 'Fired when a compliance deadline has passed',
        };
    }

    /**
     * Get all events that are enabled by default.
     *
     * @return array<self>
     */
    public static function defaultEnabled(): array
    {
        return [
            self::AuditCompleted,
            self::CriticalIssueFound,
            self::RegressionDetected,
            self::DeadlinePassed,
        ];
    }
}
