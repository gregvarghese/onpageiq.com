<?php

namespace App\Services\Diff;

use App\Models\Issue;
use App\Models\ScanResult;
use Illuminate\Support\Collection;

readonly class ScanComparison
{
    /**
     * @param  Collection<int, Issue>  $fixedIssues
     * @param  Collection<int, Issue>  $newIssues
     * @param  Collection<int, array{baseline: Issue, current: Issue}>  $unchangedIssues
     * @param  array<string, array{baseline: float, current: float, change: float, improved: bool}>  $scoreChanges
     */
    public function __construct(
        public ScanResult $baseline,
        public ScanResult $current,
        public Collection $fixedIssues,
        public Collection $newIssues,
        public Collection $unchangedIssues,
        public array $scoreChanges = []
    ) {}

    /**
     * Get the total number of issues fixed.
     */
    public function fixedCount(): int
    {
        return $this->fixedIssues->count();
    }

    /**
     * Get the total number of new issues.
     */
    public function newCount(): int
    {
        return $this->newIssues->count();
    }

    /**
     * Get the total number of unchanged issues.
     */
    public function unchangedCount(): int
    {
        return $this->unchangedIssues->count();
    }

    /**
     * Check if overall score improved.
     */
    public function scoreImproved(): bool
    {
        return isset($this->scoreChanges['overall'])
            && $this->scoreChanges['overall']['improved'];
    }

    /**
     * Get the overall score change.
     */
    public function overallScoreChange(): float
    {
        return $this->scoreChanges['overall']['change'] ?? 0.0;
    }

    /**
     * Check if there are any changes.
     */
    public function hasChanges(): bool
    {
        return $this->fixedCount() > 0 || $this->newCount() > 0;
    }

    /**
     * Get issues grouped by category with their status.
     *
     * @return array<string, array{fixed: Collection, new: Collection, unchanged: Collection}>
     */
    public function getIssuesByCategory(): array
    {
        $categories = [];

        // Group fixed issues
        foreach ($this->fixedIssues->groupBy('category') as $category => $issues) {
            $categories[$category]['fixed'] = $issues;
        }

        // Group new issues
        foreach ($this->newIssues->groupBy('category') as $category => $issues) {
            $categories[$category]['new'] = $issues;
        }

        // Group unchanged issues
        foreach ($this->unchangedIssues->groupBy(fn ($item) => $item['current']->category) as $category => $issues) {
            $categories[$category]['unchanged'] = $issues;
        }

        // Ensure all categories have all keys
        foreach ($categories as $category => $data) {
            $categories[$category] = array_merge([
                'fixed' => collect(),
                'new' => collect(),
                'unchanged' => collect(),
            ], $data);
        }

        return $categories;
    }

    /**
     * Get a summary array for JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'baseline_scan_id' => $this->baseline->scan_id,
            'current_scan_id' => $this->current->scan_id,
            'baseline_date' => $this->baseline->created_at->toIso8601String(),
            'current_date' => $this->current->created_at->toIso8601String(),
            'fixed_count' => $this->fixedCount(),
            'new_count' => $this->newCount(),
            'unchanged_count' => $this->unchangedCount(),
            'score_changes' => $this->scoreChanges,
            'has_changes' => $this->hasChanges(),
        ];
    }
}
