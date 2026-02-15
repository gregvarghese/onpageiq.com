<?php

namespace App\Services\Accessibility;

use App\Enums\CheckStatus;
use App\Enums\ImpactLevel;
use App\Models\AccessibilityAudit;
use App\Models\AuditCheck;
use App\Models\Project;
use Illuminate\Support\Collection;

class RegressionService
{
    /**
     * Get trend data for a project over time.
     *
     * @return array<string, mixed>
     */
    public function getTrends(Project $project, int $limit = 10): array
    {
        $audits = AccessibilityAudit::query()
            ->where('project_id', $project->id)
            ->where('status', 'completed')
            ->orderByDesc('completed_at')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();

        if ($audits->isEmpty()) {
            return [
                'has_data' => false,
                'audits' => [],
                'scores' => [],
                'issues' => [],
                'by_category' => [],
                'by_level' => [],
            ];
        }

        $scores = [];
        $issues = [];
        $byCategory = [];
        $byLevel = [];
        $dates = [];

        foreach ($audits as $audit) {
            $dates[] = $audit->completed_at?->format('Y-m-d') ?? $audit->created_at->format('Y-m-d');
            $scores[] = $audit->overall_score ?? 0;

            // Count issues by status
            $failedChecks = $audit->checks()->where('status', CheckStatus::Fail)->count();
            $passedChecks = $audit->checks()->where('status', CheckStatus::Pass)->count();
            $issues[] = [
                'total' => $audit->checks()->count(),
                'failed' => $failedChecks,
                'passed' => $passedChecks,
            ];

            // By category
            $categoryScores = $audit->scores_by_category ?? [];
            foreach (['vision', 'motor', 'cognitive', 'general'] as $category) {
                $byCategory[$category][] = $categoryScores[$category] ?? null;
            }

            // By WCAG level
            $levelA = $audit->checks()
                ->where('wcag_level', 'A')
                ->where('status', CheckStatus::Fail)
                ->count();
            $levelAA = $audit->checks()
                ->where('wcag_level', 'AA')
                ->where('status', CheckStatus::Fail)
                ->count();

            $byLevel['A'][] = $levelA;
            $byLevel['AA'][] = $levelAA;
        }

        return [
            'has_data' => true,
            'audits' => $audits->map(fn ($a) => [
                'id' => $a->id,
                'date' => $a->completed_at?->format('Y-m-d'),
                'score' => $a->overall_score,
            ])->toArray(),
            'dates' => $dates,
            'scores' => $scores,
            'issues' => $issues,
            'by_category' => $byCategory,
            'by_level' => $byLevel,
            'summary' => $this->calculateTrendSummary($scores, $issues),
        ];
    }

    /**
     * Calculate trend summary statistics.
     *
     * @return array<string, mixed>
     */
    protected function calculateTrendSummary(array $scores, array $issues): array
    {
        if (count($scores) < 2) {
            return [
                'score_trend' => 'stable',
                'score_change' => 0,
                'issue_trend' => 'stable',
                'issue_change' => 0,
            ];
        }

        $latestScore = end($scores);
        $previousScore = $scores[count($scores) - 2];
        $scoreChange = $latestScore - $previousScore;

        $latestIssues = end($issues)['failed'] ?? 0;
        $previousIssues = $issues[count($issues) - 2]['failed'] ?? 0;
        $issueChange = $latestIssues - $previousIssues;

        return [
            'score_trend' => $scoreChange > 2 ? 'improving' : ($scoreChange < -2 ? 'declining' : 'stable'),
            'score_change' => round($scoreChange, 1),
            'issue_trend' => $issueChange < 0 ? 'improving' : ($issueChange > 0 ? 'declining' : 'stable'),
            'issue_change' => $issueChange,
            'average_score' => round(array_sum($scores) / count($scores), 1),
            'highest_score' => max($scores),
            'lowest_score' => min($scores),
        ];
    }

    /**
     * Compare two audits and return detailed diff.
     *
     * @return array<string, mixed>
     */
    public function compareAudits(AccessibilityAudit $current, AccessibilityAudit $previous): array
    {
        $currentChecks = $current->checks()->get();
        $previousChecks = $previous->checks()->get();

        $currentFailed = $currentChecks->where('status', CheckStatus::Fail);
        $previousFailed = $previousChecks->where('status', CheckStatus::Fail);

        $currentFingerprints = $currentFailed->pluck('fingerprint')->filter()->toArray();
        $previousFingerprints = $previousFailed->pluck('fingerprint')->filter()->toArray();

        // Categorize issues
        $newFingerprints = array_diff($currentFingerprints, $previousFingerprints);
        $fixedFingerprints = array_diff($previousFingerprints, $currentFingerprints);
        $recurringFingerprints = array_intersect($currentFingerprints, $previousFingerprints);

        $newIssues = $currentFailed->whereIn('fingerprint', $newFingerprints);
        $fixedIssues = $previousFailed->whereIn('fingerprint', $fixedFingerprints);
        $recurringIssues = $currentFailed->whereIn('fingerprint', $recurringFingerprints);

        // Group by criterion
        $byCriterion = $this->groupByCriterion($newIssues, $fixedIssues, $recurringIssues);

        // Group by impact
        $byImpact = $this->groupByImpact($newIssues, $fixedIssues);

        return [
            'current_audit' => [
                'id' => $current->id,
                'date' => $current->completed_at?->format('Y-m-d H:i'),
                'score' => $current->overall_score,
                'total_issues' => $currentFailed->count(),
            ],
            'previous_audit' => [
                'id' => $previous->id,
                'date' => $previous->completed_at?->format('Y-m-d H:i'),
                'score' => $previous->overall_score,
                'total_issues' => $previousFailed->count(),
            ],
            'score_change' => ($current->overall_score ?? 0) - ($previous->overall_score ?? 0),
            'new_issues' => [
                'count' => $newIssues->count(),
                'items' => $this->formatIssueList($newIssues),
            ],
            'fixed_issues' => [
                'count' => $fixedIssues->count(),
                'items' => $this->formatIssueList($fixedIssues),
            ],
            'recurring_issues' => [
                'count' => $recurringIssues->count(),
                'items' => $this->formatIssueList($recurringIssues->take(20)),
            ],
            'by_criterion' => $byCriterion,
            'by_impact' => $byImpact,
            'has_regression' => $this->determineRegression(
                ($current->overall_score ?? 0) - ($previous->overall_score ?? 0),
                $newIssues
            ),
        ];
    }

    /**
     * Group issues by WCAG criterion.
     *
     * @return array<string, array<string, int>>
     */
    protected function groupByCriterion(Collection $new, Collection $fixed, Collection $recurring): array
    {
        $criteria = [];

        foreach ($new as $issue) {
            $id = $issue->criterion_id;
            if (! isset($criteria[$id])) {
                $criteria[$id] = ['name' => $issue->criterion_name, 'new' => 0, 'fixed' => 0, 'recurring' => 0];
            }
            $criteria[$id]['new']++;
        }

        foreach ($fixed as $issue) {
            $id = $issue->criterion_id;
            if (! isset($criteria[$id])) {
                $criteria[$id] = ['name' => $issue->criterion_name, 'new' => 0, 'fixed' => 0, 'recurring' => 0];
            }
            $criteria[$id]['fixed']++;
        }

        foreach ($recurring as $issue) {
            $id = $issue->criterion_id;
            if (! isset($criteria[$id])) {
                $criteria[$id] = ['name' => $issue->criterion_name, 'new' => 0, 'fixed' => 0, 'recurring' => 0];
            }
            $criteria[$id]['recurring']++;
        }

        return $criteria;
    }

    /**
     * Group issues by impact level.
     *
     * @return array<string, array<string, int>>
     */
    protected function groupByImpact(Collection $new, Collection $fixed): array
    {
        $impacts = [];

        foreach (ImpactLevel::cases() as $level) {
            $impacts[$level->value] = [
                'new' => $new->where('impact', $level)->count(),
                'fixed' => $fixed->where('impact', $level)->count(),
            ];
        }

        return $impacts;
    }

    /**
     * Format issue list for display.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function formatIssueList(Collection $issues): array
    {
        return $issues->map(fn (AuditCheck $check) => [
            'id' => $check->id,
            'criterion_id' => $check->criterion_id,
            'criterion_name' => $check->criterion_name,
            'message' => $check->message,
            'impact' => $check->impact?->value,
            'element_selector' => $check->element_selector,
        ])->values()->toArray();
    }

    /**
     * Determine if there's a regression.
     */
    protected function determineRegression(float $scoreDiff, Collection $newIssues): bool
    {
        // Regression if score dropped significantly
        if ($scoreDiff < -5) {
            return true;
        }

        // Regression if many new issues
        if ($newIssues->count() > 3) {
            return true;
        }

        // Regression if new critical issues
        if ($newIssues->where('impact', ImpactLevel::Critical)->isNotEmpty()) {
            return true;
        }

        return false;
    }

    /**
     * Get issue resolution rate for a project.
     *
     * @return array<string, mixed>
     */
    public function getResolutionRate(Project $project, int $auditCount = 5): array
    {
        $audits = AccessibilityAudit::query()
            ->where('project_id', $project->id)
            ->where('status', 'completed')
            ->orderByDesc('completed_at')
            ->limit($auditCount)
            ->get();

        if ($audits->count() < 2) {
            return [
                'has_data' => false,
                'rate' => 0,
                'total_fixed' => 0,
                'total_new' => 0,
            ];
        }

        $totalFixed = 0;
        $totalNew = 0;

        for ($i = 0; $i < $audits->count() - 1; $i++) {
            $current = $audits[$i];
            $previous = $audits[$i + 1];

            $comparison = $this->compareAudits($current, $previous);
            $totalFixed += $comparison['fixed_issues']['count'];
            $totalNew += $comparison['new_issues']['count'];
        }

        $rate = $totalNew > 0
            ? round(($totalFixed / ($totalFixed + $totalNew)) * 100, 1)
            : ($totalFixed > 0 ? 100 : 0);

        return [
            'has_data' => true,
            'rate' => $rate,
            'total_fixed' => $totalFixed,
            'total_new' => $totalNew,
            'net_change' => $totalFixed - $totalNew,
        ];
    }

    /**
     * Get issues that have been recurring for multiple audits.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function getPersistentIssues(Project $project, int $minOccurrences = 3): Collection
    {
        $audits = AccessibilityAudit::query()
            ->where('project_id', $project->id)
            ->where('status', 'completed')
            ->orderByDesc('completed_at')
            ->limit(10)
            ->get();

        if ($audits->count() < $minOccurrences) {
            return collect();
        }

        // Collect all fingerprints with their occurrence count
        $fingerprintCounts = [];
        $fingerprintData = [];

        foreach ($audits as $audit) {
            $checks = $audit->checks()
                ->where('status', CheckStatus::Fail)
                ->whereNotNull('fingerprint')
                ->get();

            foreach ($checks as $check) {
                $fp = $check->fingerprint;
                $fingerprintCounts[$fp] = ($fingerprintCounts[$fp] ?? 0) + 1;

                if (! isset($fingerprintData[$fp])) {
                    $fingerprintData[$fp] = [
                        'fingerprint' => $fp,
                        'criterion_id' => $check->criterion_id,
                        'criterion_name' => $check->criterion_name,
                        'message' => $check->message,
                        'impact' => $check->impact?->value,
                        'first_seen' => $audit->completed_at,
                        'occurrences' => 0,
                    ];
                }

                $fingerprintData[$fp]['occurrences']++;
                $fingerprintData[$fp]['last_seen'] = $audit->completed_at;
            }
        }

        // Filter to persistent issues
        return collect($fingerprintData)
            ->filter(fn ($data) => $data['occurrences'] >= $minOccurrences)
            ->sortByDesc('occurrences')
            ->values();
    }
}
