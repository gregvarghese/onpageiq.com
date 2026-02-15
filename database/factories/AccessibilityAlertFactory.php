<?php

namespace Database\Factories;

use App\Enums\AlertType;
use App\Models\AccessibilityAlert;
use App\Models\AccessibilityAudit;
use App\Models\ComplianceDeadline;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccessibilityAlert>
 */
class AccessibilityAlertFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'accessibility_audit_id' => null,
            'compliance_deadline_id' => null,
            'user_id' => User::factory(),
            'type' => fake()->randomElement(AlertType::cases()),
            'title' => fake()->sentence(4),
            'message' => fake()->paragraph(),
            'data' => null,
            'is_read' => false,
            'is_dismissed' => false,
            'email_sent' => false,
            'read_at' => null,
            'dismissed_at' => null,
            'email_sent_at' => null,
        ];
    }

    /**
     * Score threshold alert.
     */
    public function scoreThreshold(float $score = 65.0, float $threshold = 80.0): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AlertType::ScoreThreshold,
            'title' => 'Accessibility Score Below Threshold',
            'message' => sprintf('The accessibility score (%.1f%%) has dropped below the threshold of %.1f%%.', $score, $threshold),
            'data' => [
                'score' => $score,
                'threshold' => $threshold,
            ],
        ]);
    }

    /**
     * Regression alert.
     */
    public function regression(int $newIssues = 5, float $previousScore = 85.0, float $currentScore = 72.0): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AlertType::Regression,
            'title' => 'Accessibility Regression Detected',
            'message' => sprintf('%d new issues detected. Score dropped from %.1f%% to %.1f%%.', $newIssues, $previousScore, $currentScore),
            'data' => [
                'new_issues_count' => $newIssues,
                'previous_score' => $previousScore,
                'current_score' => $currentScore,
            ],
        ]);
    }

    /**
     * Deadline reminder alert.
     */
    public function deadlineReminder(int $daysUntil = 7): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AlertType::DeadlineReminder,
            'title' => 'Compliance Deadline Approaching',
            'message' => sprintf('Compliance deadline is in %d day(s).', $daysUntil),
            'data' => [
                'days_until' => $daysUntil,
            ],
        ]);
    }

    /**
     * Critical issue alert.
     */
    public function criticalIssue(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AlertType::NewCriticalIssue,
            'title' => 'New Critical Accessibility Issue',
            'message' => 'A critical accessibility issue has been detected.',
            'data' => [
                'criterion_id' => '1.1.1',
                'criterion_name' => 'Non-text Content',
                'impact' => 'critical',
            ],
        ]);
    }

    /**
     * Read alert.
     */
    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Dismissed alert.
     */
    public function dismissed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_dismissed' => true,
            'dismissed_at' => now(),
        ]);
    }

    /**
     * Email sent.
     */
    public function emailSent(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_sent' => true,
            'email_sent_at' => now(),
        ]);
    }

    /**
     * For a specific audit.
     */
    public function forAudit(AccessibilityAudit $audit): static
    {
        return $this->state(fn (array $attributes) => [
            'project_id' => $audit->project_id,
            'accessibility_audit_id' => $audit->id,
        ]);
    }

    /**
     * For a specific deadline.
     */
    public function forDeadline(ComplianceDeadline $deadline): static
    {
        return $this->state(fn (array $attributes) => [
            'project_id' => $deadline->project_id,
            'compliance_deadline_id' => $deadline->id,
        ]);
    }
}
