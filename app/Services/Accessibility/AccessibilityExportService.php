<?php

namespace App\Services\Accessibility;

use App\Enums\CheckStatus;
use App\Enums\FixComplexity;
use App\Models\AccessibilityAudit;
use App\Models\AuditCheck;
use Illuminate\Support\Collection;

class AccessibilityExportService
{
    /**
     * Export audit to CSV.
     */
    public function exportToCsv(AccessibilityAudit $audit): string
    {
        $checks = $audit->checks()->get();

        $csv = $this->generateCsvContent($checks);

        $filename = sprintf(
            'accessibility-audit-%s-%s.csv',
            $audit->id,
            now()->format('Y-m-d')
        );

        $path = storage_path("app/exports/{$filename}");
        $this->ensureDirectoryExists(dirname($path));
        file_put_contents($path, $csv);

        return $path;
    }

    /**
     * Export audit to JSON.
     */
    public function exportToJson(AccessibilityAudit $audit): string
    {
        $data = $this->generateJsonData($audit);

        $filename = sprintf(
            'accessibility-audit-%s-%s.json',
            $audit->id,
            now()->format('Y-m-d')
        );

        $path = storage_path("app/exports/{$filename}");
        $this->ensureDirectoryExists(dirname($path));
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));

        return $path;
    }

    /**
     * Export audit to PDF report.
     *
     * @throws \RuntimeException If DomPDF package is not installed
     */
    public function exportToPdf(AccessibilityAudit $audit, array $options = []): string
    {
        if (! class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            throw new \RuntimeException(
                'PDF export requires the barryvdh/laravel-dompdf package. Install with: composer require barryvdh/laravel-dompdf'
            );
        }

        $data = $this->prepareReportData($audit, $options);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.accessibility-report', $data);
        $pdf->setPaper('a4', 'portrait');

        $filename = sprintf(
            'accessibility-report-%s-%s.pdf',
            $audit->id,
            now()->format('Y-m-d')
        );

        $path = storage_path("app/exports/{$filename}");
        $this->ensureDirectoryExists(dirname($path));
        $pdf->save($path);

        return $path;
    }

    /**
     * Generate CSV content.
     */
    protected function generateCsvContent(Collection $checks): string
    {
        $headers = [
            'Criterion ID',
            'Criterion Name',
            'WCAG Level',
            'Status',
            'Impact',
            'Category',
            'Message',
            'Element Selector',
            'Suggestion',
            'Complexity',
            'Effort (min)',
            'Is Recurring',
            'Documentation URL',
        ];

        $rows = [$headers];

        foreach ($checks as $check) {
            $complexity = FixComplexity::fromCriterion($check->criterion_id);

            $rows[] = [
                $check->criterion_id,
                $check->criterion_name,
                $check->wcag_level?->value,
                $check->status->value,
                $check->impact?->value,
                $check->category?->value,
                $check->message,
                $check->element_selector,
                $check->suggestion,
                $complexity->value,
                $complexity->effortMinutes(),
                $check->is_recurring ? 'Yes' : 'No',
                $check->getWcagUrl(),
            ];
        }

        // Convert to CSV string
        $output = fopen('php://temp', 'r+');
        foreach ($rows as $row) {
            fputcsv($output, $row);
        }
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Generate JSON data.
     *
     * @return array<string, mixed>
     */
    protected function generateJsonData(AccessibilityAudit $audit): array
    {
        $checks = $audit->checks()->get();

        return [
            'meta' => [
                'generated_at' => now()->toIso8601String(),
                'generator' => 'OnPageIQ Accessibility',
                'version' => '1.0',
            ],
            'audit' => [
                'id' => $audit->id,
                'status' => $audit->status,
                'overall_score' => $audit->overall_score,
                'wcag_level_target' => $audit->wcag_level_target?->value,
                'framework' => $audit->framework,
                'started_at' => $audit->started_at?->toIso8601String(),
                'completed_at' => $audit->completed_at?->toIso8601String(),
                'scores_by_category' => $audit->scores_by_category,
            ],
            'summary' => [
                'total_checks' => $checks->count(),
                'passed' => $checks->where('status', CheckStatus::Pass)->count(),
                'failed' => $checks->where('status', CheckStatus::Fail)->count(),
                'warnings' => $checks->where('status', CheckStatus::Warning)->count(),
                'manual_review' => $checks->where('status', CheckStatus::ManualReview)->count(),
            ],
            'checks' => $checks->map(fn (AuditCheck $check) => [
                'id' => $check->id,
                'criterion_id' => $check->criterion_id,
                'criterion_name' => $check->criterion_name,
                'wcag_level' => $check->wcag_level?->value,
                'status' => $check->status->value,
                'impact' => $check->impact?->value,
                'category' => $check->category?->value,
                'message' => $check->message,
                'element_selector' => $check->element_selector,
                'element_html' => $check->element_html,
                'suggestion' => $check->suggestion,
                'code_snippet' => $check->code_snippet,
                'documentation_url' => $check->getWcagUrl(),
                'is_recurring' => $check->is_recurring,
                'complexity' => FixComplexity::fromCriterion($check->criterion_id)->value,
            ])->toArray(),
        ];
    }

    /**
     * Prepare report data for PDF.
     *
     * @return array<string, mixed>
     */
    protected function prepareReportData(AccessibilityAudit $audit, array $options = []): array
    {
        $checks = $audit->checks()->get();
        $failedChecks = $checks->where('status', CheckStatus::Fail);

        // Group by criterion
        $byCriterion = $failedChecks->groupBy('criterion_id')
            ->map(fn ($group) => [
                'criterion_id' => $group->first()->criterion_id,
                'criterion_name' => $group->first()->criterion_name,
                'wcag_level' => $group->first()->wcag_level?->value,
                'count' => $group->count(),
                'issues' => $group->take(5)->values(),
            ])
            ->sortBy('criterion_id')
            ->values();

        // Group by impact
        $byImpact = $failedChecks->groupBy(fn ($c) => $c->impact?->value ?? 'unknown')
            ->map(fn ($group, $impact) => [
                'impact' => $impact,
                'count' => $group->count(),
            ])
            ->sortByDesc('count')
            ->values();

        // Group by category
        $byCategory = $failedChecks->groupBy(fn ($c) => $c->category?->value ?? 'general')
            ->map(fn ($group, $category) => [
                'category' => $category,
                'count' => $group->count(),
            ])
            ->values();

        return [
            'audit' => $audit,
            'project' => $audit->project,
            'url' => $audit->url,
            'generatedAt' => now(),
            'summary' => [
                'total' => $checks->count(),
                'passed' => $checks->where('status', CheckStatus::Pass)->count(),
                'failed' => $failedChecks->count(),
                'warnings' => $checks->where('status', CheckStatus::Warning)->count(),
                'manual_review' => $checks->where('status', CheckStatus::ManualReview)->count(),
            ],
            'byCriterion' => $byCriterion,
            'byImpact' => $byImpact,
            'byCategory' => $byCategory,
            'includeDetails' => $options['include_details'] ?? true,
            'brandLogo' => $options['brand_logo'] ?? null,
            'brandName' => $options['brand_name'] ?? null,
            'brandColor' => $options['brand_color'] ?? '#2563eb',
        ];
    }

    /**
     * Group issues for multi-view organization.
     *
     * @return array<string, mixed>
     */
    public function organizeIssues(AccessibilityAudit $audit): array
    {
        $checks = $audit->checks()->where('status', CheckStatus::Fail)->get();

        return [
            'by_wcag' => $this->groupByWcag($checks),
            'by_impact' => $this->groupByImpact($checks),
            'by_category' => $this->groupByCategory($checks),
            'by_complexity' => $this->groupByComplexity($checks),
            'by_element' => $this->groupByElement($checks),
        ];
    }

    /**
     * Group checks by WCAG criterion.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function groupByWcag(Collection $checks): array
    {
        return $checks->groupBy('criterion_id')
            ->map(fn ($group, $criterionId) => [
                'criterion_id' => $criterionId,
                'criterion_name' => $group->first()->criterion_name,
                'wcag_level' => $group->first()->wcag_level?->value,
                'count' => $group->count(),
                'checks' => $group->map(fn ($c) => [
                    'id' => $c->id,
                    'message' => $c->message,
                    'impact' => $c->impact?->value,
                    'element_selector' => $c->element_selector,
                ])->values()->toArray(),
            ])
            ->sortBy('criterion_id')
            ->toArray();
    }

    /**
     * Group checks by impact level.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function groupByImpact(Collection $checks): array
    {
        return $checks->groupBy(fn ($c) => $c->impact?->value ?? 'unknown')
            ->map(fn ($group, $impact) => [
                'impact' => $impact,
                'count' => $group->count(),
                'checks' => $group->map(fn ($c) => [
                    'id' => $c->id,
                    'criterion_id' => $c->criterion_id,
                    'message' => $c->message,
                    'element_selector' => $c->element_selector,
                ])->values()->toArray(),
            ])
            ->toArray();
    }

    /**
     * Group checks by category.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function groupByCategory(Collection $checks): array
    {
        return $checks->groupBy(fn ($c) => $c->category?->value ?? 'general')
            ->map(fn ($group, $category) => [
                'category' => $category,
                'count' => $group->count(),
                'checks' => $group->map(fn ($c) => [
                    'id' => $c->id,
                    'criterion_id' => $c->criterion_id,
                    'message' => $c->message,
                    'impact' => $c->impact?->value,
                ])->values()->toArray(),
            ])
            ->toArray();
    }

    /**
     * Group checks by fix complexity.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function groupByComplexity(Collection $checks): array
    {
        return $checks->groupBy(fn ($c) => FixComplexity::fromCriterion($c->criterion_id)->value)
            ->map(fn ($group, $complexity) => [
                'complexity' => $complexity,
                'complexity_label' => FixComplexity::from($complexity)->label(),
                'count' => $group->count(),
                'total_effort' => $group->sum(fn ($c) => FixComplexity::fromCriterion($c->criterion_id)->effortMinutes()),
                'checks' => $group->map(fn ($c) => [
                    'id' => $c->id,
                    'criterion_id' => $c->criterion_id,
                    'message' => $c->message,
                    'impact' => $c->impact?->value,
                ])->values()->toArray(),
            ])
            ->toArray();
    }

    /**
     * Group checks by element/component.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function groupByElement(Collection $checks): array
    {
        return $checks->filter(fn ($c) => $c->element_selector)
            ->groupBy(fn ($c) => $this->normalizeSelector($c->element_selector))
            ->map(fn ($group, $selector) => [
                'selector' => $selector,
                'count' => $group->count(),
                'checks' => $group->map(fn ($c) => [
                    'id' => $c->id,
                    'criterion_id' => $c->criterion_id,
                    'message' => $c->message,
                    'impact' => $c->impact?->value,
                ])->values()->toArray(),
            ])
            ->sortByDesc('count')
            ->take(20)
            ->toArray();
    }

    /**
     * Normalize CSS selector for grouping.
     */
    protected function normalizeSelector(string $selector): string
    {
        // Remove nth-child, specific IDs, etc. to group similar elements
        $normalized = preg_replace('/\[.*?\]/', '', $selector);
        $normalized = preg_replace('/:nth-child\(\d+\)/', '', $normalized);
        $normalized = preg_replace('/#[a-zA-Z0-9_-]+/', '', $normalized);

        return trim($normalized);
    }

    /**
     * Ensure directory exists.
     */
    protected function ensureDirectoryExists(string $path): void
    {
        if (! file_exists($path)) {
            mkdir($path, 0755, true);
        }
    }
}
