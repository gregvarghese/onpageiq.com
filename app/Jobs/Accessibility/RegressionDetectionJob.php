<?php

namespace App\Jobs\Accessibility;

use App\Enums\CheckStatus;
use App\Enums\ImpactLevel;
use App\Models\AccessibilityAlert;
use App\Models\AccessibilityAudit;
use App\Models\AuditCheck;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class RegressionDetectionJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public AccessibilityAudit $audit
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $previousAudit = $this->getPreviousAudit();

        if (! $previousAudit) {
            Log::info('RegressionDetection: No previous audit found', [
                'audit_id' => $this->audit->id,
            ]);

            return;
        }

        $regressionData = $this->detectRegression($previousAudit);

        // Store regression data in audit metadata
        $this->audit->update([
            'metadata' => array_merge($this->audit->metadata ?? [], [
                'regression' => $regressionData,
            ]),
        ]);

        // Mark recurring issues
        $this->markRecurringIssues($previousAudit);

        // Create alerts if regression detected
        $this->createAlertsIfNeeded($regressionData);

        Log::info('RegressionDetection: Analysis complete', [
            'audit_id' => $this->audit->id,
            'previous_audit_id' => $previousAudit->id,
            'regression_detected' => $regressionData['has_regression'],
        ]);
    }

    /**
     * Get the previous audit for comparison.
     */
    protected function getPreviousAudit(): ?AccessibilityAudit
    {
        return AccessibilityAudit::query()
            ->where('project_id', $this->audit->project_id)
            ->where('url_id', $this->audit->url_id)
            ->where('id', '<', $this->audit->id)
            ->where('status', 'completed')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Detect regression between current and previous audit.
     *
     * @return array<string, mixed>
     */
    protected function detectRegression(AccessibilityAudit $previousAudit): array
    {
        $currentChecks = $this->audit->checks()->where('status', CheckStatus::Fail)->get();
        $previousChecks = $previousAudit->checks()->where('status', CheckStatus::Fail)->get();

        $currentFingerprints = $currentChecks->pluck('fingerprint')->filter()->toArray();
        $previousFingerprints = $previousChecks->pluck('fingerprint')->filter()->toArray();

        // Issues that are in current but not in previous (new issues)
        $newFingerprints = array_diff($currentFingerprints, $previousFingerprints);
        $newIssues = $currentChecks->whereIn('fingerprint', $newFingerprints);

        // Issues that are in previous but not in current (fixed issues)
        $fixedFingerprints = array_diff($previousFingerprints, $currentFingerprints);
        $fixedIssues = $previousChecks->whereIn('fingerprint', $fixedFingerprints);

        // Issues that appear in both (recurring)
        $recurringFingerprints = array_intersect($currentFingerprints, $previousFingerprints);
        $recurringIssues = $currentChecks->whereIn('fingerprint', $recurringFingerprints);

        $currentScore = $this->audit->overall_score ?? 0;
        $previousScore = $previousAudit->overall_score ?? 0;
        $scoreDiff = $currentScore - $previousScore;

        // Determine if there's a regression
        $hasRegression = $scoreDiff < -5 || // Score dropped by more than 5 points
            count($newFingerprints) > 3 ||   // More than 3 new issues
            $newIssues->where('impact', ImpactLevel::Critical)->isNotEmpty(); // Any new critical issues

        return [
            'has_regression' => $hasRegression,
            'previous_audit_id' => $previousAudit->id,
            'previous_score' => $previousScore,
            'current_score' => $currentScore,
            'score_diff' => $scoreDiff,
            'new_issues_count' => count($newFingerprints),
            'fixed_issues_count' => count($fixedFingerprints),
            'recurring_issues_count' => count($recurringFingerprints),
            'new_issues' => $this->summarizeIssues($newIssues),
            'fixed_issues' => $this->summarizeIssues($fixedIssues),
            'new_critical_count' => $newIssues->where('impact', ImpactLevel::Critical)->count(),
            'new_serious_count' => $newIssues->where('impact', ImpactLevel::Serious)->count(),
        ];
    }

    /**
     * Summarize issues for storage.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function summarizeIssues(Collection $issues): array
    {
        return $issues->take(10)->map(fn (AuditCheck $check) => [
            'id' => $check->id,
            'criterion_id' => $check->criterion_id,
            'criterion_name' => $check->criterion_name,
            'impact' => $check->impact?->value,
            'message' => $check->message,
            'fingerprint' => $check->fingerprint,
        ])->values()->toArray();
    }

    /**
     * Mark issues as recurring if they appeared in previous audit.
     */
    protected function markRecurringIssues(AccessibilityAudit $previousAudit): void
    {
        $previousFingerprints = $previousAudit->checks()
            ->where('status', CheckStatus::Fail)
            ->whereNotNull('fingerprint')
            ->pluck('fingerprint')
            ->toArray();

        $this->audit->checks()
            ->where('status', CheckStatus::Fail)
            ->whereIn('fingerprint', $previousFingerprints)
            ->update(['is_recurring' => true]);
    }

    /**
     * Create alerts if regression is detected.
     */
    protected function createAlertsIfNeeded(array $regressionData): void
    {
        if (! $regressionData['has_regression']) {
            return;
        }

        // Create regression alert
        AccessibilityAlert::createRegressionAlert($this->audit, $regressionData);

        // Create alerts for new critical issues
        $newCriticalIssues = $this->audit->checks()
            ->where('status', CheckStatus::Fail)
            ->where('impact', ImpactLevel::Critical)
            ->where('is_recurring', false)
            ->get();

        foreach ($newCriticalIssues as $check) {
            AccessibilityAlert::createCriticalIssueAlert($this->audit, $check);
        }
    }
}
