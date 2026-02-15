<?php

namespace App\Services\Accessibility;

use App\Enums\WebhookEvent;
use App\Models\AccessibilityAudit;
use App\Models\AuditCheck;
use App\Models\ComplianceDeadline;
use App\Models\Project;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    /**
     * Send a webhook for an event.
     *
     * @param  array<string, mixed>  $payload
     */
    public function send(Project $project, WebhookEvent $event, array $payload = []): bool
    {
        $webhookUrl = $this->getWebhookUrl($project);
        $enabledEvents = $this->getEnabledEvents($project);

        if (! $webhookUrl) {
            return false;
        }

        if (! in_array($event->value, $enabledEvents)) {
            return false;
        }

        $fullPayload = [
            'event' => $event->value,
            'event_label' => $event->label(),
            'timestamp' => now()->toIso8601String(),
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ],
            'data' => $payload,
        ];

        try {
            $response = Http::timeout(10)
                ->withHeaders($this->getHeaders($project))
                ->post($webhookUrl, $fullPayload);

            $success = $response->successful();

            Log::info('Webhook sent', [
                'event' => $event->value,
                'project_id' => $project->id,
                'success' => $success,
                'status' => $response->status(),
            ]);

            return $success;
        } catch (\Exception $e) {
            Log::error('Webhook failed', [
                'event' => $event->value,
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send audit started webhook.
     */
    public function sendAuditStarted(AccessibilityAudit $audit): bool
    {
        return $this->send($audit->project, WebhookEvent::AuditStarted, [
            'audit_id' => $audit->id,
            'url' => $audit->url?->url,
            'started_at' => $audit->started_at?->toIso8601String(),
        ]);
    }

    /**
     * Send audit completed webhook.
     */
    public function sendAuditCompleted(AccessibilityAudit $audit): bool
    {
        return $this->send($audit->project, WebhookEvent::AuditCompleted, [
            'audit_id' => $audit->id,
            'url' => $audit->url?->url,
            'overall_score' => $audit->overall_score,
            'checks_total' => $audit->checks_total,
            'checks_passed' => $audit->checks_passed,
            'checks_failed' => $audit->checks_failed,
            'completed_at' => $audit->completed_at?->toIso8601String(),
            'scores_by_category' => $audit->scores_by_category,
        ]);
    }

    /**
     * Send audit failed webhook.
     */
    public function sendAuditFailed(AccessibilityAudit $audit, ?string $error = null): bool
    {
        return $this->send($audit->project, WebhookEvent::AuditFailed, [
            'audit_id' => $audit->id,
            'url' => $audit->url?->url,
            'error' => $error,
        ]);
    }

    /**
     * Send critical issue found webhook.
     */
    public function sendCriticalIssueFound(AuditCheck $check): bool
    {
        $audit = $check->audit;

        return $this->send($audit->project, WebhookEvent::CriticalIssueFound, [
            'audit_id' => $audit->id,
            'check_id' => $check->id,
            'criterion_id' => $check->criterion_id,
            'criterion_name' => $check->criterion_name,
            'message' => $check->message,
            'impact' => $check->impact?->value,
            'element_selector' => $check->element_selector,
        ]);
    }

    /**
     * Send regression detected webhook.
     *
     * @param  array<string, mixed>  $regressionData
     */
    public function sendRegressionDetected(AccessibilityAudit $audit, array $regressionData): bool
    {
        return $this->send($audit->project, WebhookEvent::RegressionDetected, [
            'audit_id' => $audit->id,
            'previous_audit_id' => $regressionData['previous_audit_id'] ?? null,
            'previous_score' => $regressionData['previous_score'] ?? null,
            'current_score' => $regressionData['current_score'] ?? null,
            'score_diff' => $regressionData['score_diff'] ?? null,
            'new_issues_count' => $regressionData['new_issues_count'] ?? 0,
            'fixed_issues_count' => $regressionData['fixed_issues_count'] ?? 0,
        ]);
    }

    /**
     * Send score threshold breach webhook.
     */
    public function sendScoreThresholdBreach(AccessibilityAudit $audit, float $threshold): bool
    {
        return $this->send($audit->project, WebhookEvent::ScoreThresholdBreach, [
            'audit_id' => $audit->id,
            'current_score' => $audit->overall_score,
            'threshold' => $threshold,
        ]);
    }

    /**
     * Send deadline approaching webhook.
     */
    public function sendDeadlineApproaching(ComplianceDeadline $deadline, int $daysRemaining): bool
    {
        return $this->send($deadline->project, WebhookEvent::DeadlineApproaching, [
            'deadline_id' => $deadline->id,
            'title' => $deadline->title,
            'type' => $deadline->type->value,
            'deadline_date' => $deadline->deadline_date->toDateString(),
            'days_remaining' => $daysRemaining,
        ]);
    }

    /**
     * Send deadline passed webhook.
     */
    public function sendDeadlinePassed(ComplianceDeadline $deadline): bool
    {
        return $this->send($deadline->project, WebhookEvent::DeadlinePassed, [
            'deadline_id' => $deadline->id,
            'title' => $deadline->title,
            'type' => $deadline->type->value,
            'deadline_date' => $deadline->deadline_date->toDateString(),
            'is_met' => $deadline->is_met,
        ]);
    }

    /**
     * Get webhook URL from project settings.
     */
    protected function getWebhookUrl(Project $project): ?string
    {
        return $project->settings['webhook_url'] ?? null;
    }

    /**
     * Get enabled webhook events from project settings.
     *
     * @return array<string>
     */
    protected function getEnabledEvents(Project $project): array
    {
        $events = $project->settings['webhook_events'] ?? null;

        if ($events === null) {
            // Return default enabled events
            return collect(WebhookEvent::defaultEnabled())
                ->map(fn ($e) => $e->value)
                ->toArray();
        }

        return $events;
    }

    /**
     * Get headers for webhook request.
     *
     * @return array<string, string>
     */
    protected function getHeaders(Project $project): array
    {
        $headers = [
            'Content-Type' => 'application/json',
            'User-Agent' => 'OnPageIQ-Accessibility/1.0',
            'X-Webhook-Event' => 'accessibility',
        ];

        // Add secret if configured
        $secret = $project->settings['webhook_secret'] ?? null;
        if ($secret) {
            $headers['X-Webhook-Secret'] = $secret;
        }

        return $headers;
    }

    /**
     * Test webhook configuration.
     */
    public function test(Project $project): bool
    {
        $webhookUrl = $this->getWebhookUrl($project);

        if (! $webhookUrl) {
            return false;
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders($this->getHeaders($project))
                ->post($webhookUrl, [
                    'event' => 'test',
                    'event_label' => 'Test Webhook',
                    'timestamp' => now()->toIso8601String(),
                    'project' => [
                        'id' => $project->id,
                        'name' => $project->name,
                    ],
                    'data' => [
                        'message' => 'This is a test webhook from OnPageIQ Accessibility.',
                    ],
                ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Webhook test failed', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
