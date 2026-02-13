<?php

namespace App\Services\Diff;

use App\Models\Issue;
use App\Models\ScanResult;
use Illuminate\Support\Collection;

class ScanDiffService
{
    /**
     * Compare two scan results and return the differences.
     */
    public function compare(ScanResult $baseline, ScanResult $current): ScanComparison
    {
        $baselineIssues = $baseline->issues()->get();
        $currentIssues = $current->issues()->get();

        $fixed = $this->findFixedIssues($baselineIssues, $currentIssues);
        $newIssues = $this->findNewIssues($baselineIssues, $currentIssues);
        $unchanged = $this->findUnchangedIssues($baselineIssues, $currentIssues);

        $scoreChanges = $this->calculateScoreChanges($baseline->scores ?? [], $current->scores ?? []);

        return new ScanComparison(
            baseline: $baseline,
            current: $current,
            fixedIssues: $fixed,
            newIssues: $newIssues,
            unchangedIssues: $unchanged,
            scoreChanges: $scoreChanges
        );
    }

    /**
     * Find issues that were in baseline but not in current (fixed).
     *
     * @param  Collection<int, Issue>  $baseline
     * @param  Collection<int, Issue>  $current
     * @return Collection<int, Issue>
     */
    protected function findFixedIssues(Collection $baseline, Collection $current): Collection
    {
        return $baseline->filter(function (Issue $baselineIssue) use ($current) {
            return ! $current->contains(function (Issue $currentIssue) use ($baselineIssue) {
                return $this->issuesMatch($baselineIssue, $currentIssue);
            });
        })->values();
    }

    /**
     * Find issues that are in current but not in baseline (new).
     *
     * @param  Collection<int, Issue>  $baseline
     * @param  Collection<int, Issue>  $current
     * @return Collection<int, Issue>
     */
    protected function findNewIssues(Collection $baseline, Collection $current): Collection
    {
        return $current->filter(function (Issue $currentIssue) use ($baseline) {
            return ! $baseline->contains(function (Issue $baselineIssue) use ($currentIssue) {
                return $this->issuesMatch($baselineIssue, $currentIssue);
            });
        })->values();
    }

    /**
     * Find issues that exist in both baseline and current (unchanged).
     *
     * @param  Collection<int, Issue>  $baseline
     * @param  Collection<int, Issue>  $current
     * @return Collection<int, array{baseline: Issue, current: Issue}>
     */
    protected function findUnchangedIssues(Collection $baseline, Collection $current): Collection
    {
        $unchanged = collect();

        foreach ($baseline as $baselineIssue) {
            $matchingCurrent = $current->first(function (Issue $currentIssue) use ($baselineIssue) {
                return $this->issuesMatch($baselineIssue, $currentIssue);
            });

            if ($matchingCurrent) {
                $unchanged->push([
                    'baseline' => $baselineIssue,
                    'current' => $matchingCurrent,
                ]);
            }
        }

        return $unchanged;
    }

    /**
     * Determine if two issues are the same.
     */
    protected function issuesMatch(Issue $a, Issue $b): bool
    {
        // Issues match if they have the same category, severity, and text excerpt
        // or if they have the same DOM selector
        if ($a->dom_selector && $b->dom_selector && $a->dom_selector === $b->dom_selector) {
            return $a->category === $b->category;
        }

        return $a->category === $b->category
            && $a->severity === $b->severity
            && $this->normalizeText($a->text_excerpt) === $this->normalizeText($b->text_excerpt);
    }

    /**
     * Normalize text for comparison.
     */
    protected function normalizeText(?string $text): string
    {
        if (! $text) {
            return '';
        }

        return mb_strtolower(preg_replace('/\s+/', ' ', trim($text)));
    }

    /**
     * Calculate score changes between baseline and current.
     *
     * @return array<string, array{baseline: float, current: float, change: float, improved: bool}>
     */
    protected function calculateScoreChanges(array $baseline, array $current): array
    {
        $changes = [];

        $allKeys = array_unique(array_merge(array_keys($baseline), array_keys($current)));

        foreach ($allKeys as $key) {
            $baselineScore = $baseline[$key] ?? 0;
            $currentScore = $current[$key] ?? 0;
            $change = $currentScore - $baselineScore;

            $changes[$key] = [
                'baseline' => (float) $baselineScore,
                'current' => (float) $currentScore,
                'change' => (float) $change,
                'improved' => $change > 0,
            ];
        }

        return $changes;
    }

    /**
     * Generate a text-based diff of the content snapshots.
     *
     * @return array<int, array{type: string, content: string}>
     */
    public function diffContent(ScanResult $baseline, ScanResult $current): array
    {
        $baselineLines = explode("\n", $baseline->content_snapshot ?? '');
        $currentLines = explode("\n", $current->content_snapshot ?? '');

        return $this->computeLineDiff($baselineLines, $currentLines);
    }

    /**
     * Compute line-by-line diff using LCS algorithm.
     *
     * @param  array<int, string>  $old
     * @param  array<int, string>  $new
     * @return array<int, array{type: string, content: string}>
     */
    protected function computeLineDiff(array $old, array $new): array
    {
        $diff = [];
        $oldCount = count($old);
        $newCount = count($new);

        // Simple diff algorithm (not full LCS for performance)
        $maxLines = max($oldCount, $newCount);

        for ($i = 0; $i < $maxLines; $i++) {
            $oldLine = $old[$i] ?? null;
            $newLine = $new[$i] ?? null;

            if ($oldLine === $newLine) {
                if ($oldLine !== null) {
                    $diff[] = ['type' => 'unchanged', 'content' => $oldLine];
                }
            } elseif ($oldLine === null) {
                $diff[] = ['type' => 'added', 'content' => $newLine];
            } elseif ($newLine === null) {
                $diff[] = ['type' => 'removed', 'content' => $oldLine];
            } else {
                $diff[] = ['type' => 'removed', 'content' => $oldLine];
                $diff[] = ['type' => 'added', 'content' => $newLine];
            }
        }

        return $diff;
    }

    /**
     * Get a summary of changes.
     *
     * @return array{fixed: int, new: int, unchanged: int, score_improved: bool}
     */
    public function getSummary(ScanComparison $comparison): array
    {
        $overallImproved = false;
        if (isset($comparison->scoreChanges['overall'])) {
            $overallImproved = $comparison->scoreChanges['overall']['improved'];
        }

        return [
            'fixed' => $comparison->fixedIssues->count(),
            'new' => $comparison->newIssues->count(),
            'unchanged' => $comparison->unchangedIssues->count(),
            'score_improved' => $overallImproved,
        ];
    }
}
