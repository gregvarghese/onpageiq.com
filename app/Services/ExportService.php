<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Scan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportService
{
    /**
     * Export project issues to CSV.
     */
    public function exportIssuesToCsv(Project $project, ?array $filters = []): StreamedResponse
    {
        $issues = $this->getProjectIssues($project, $filters);

        $filename = sprintf('%s-issues-%s.csv', $project->slug, now()->format('Y-m-d'));

        return response()->streamDownload(function () use ($issues) {
            $handle = fopen('php://output', 'w');

            // CSV Header
            fputcsv($handle, [
                'ID',
                'Page URL',
                'Category',
                'Severity',
                'Issue',
                'Context',
                'Suggestion',
                'Status',
                'Detected At',
            ]);

            // CSV Data
            foreach ($issues as $issue) {
                fputcsv($handle, [
                    $issue->id,
                    $issue->result?->scan?->url?->url ?? 'Unknown',
                    $issue->category,
                    $issue->severity,
                    $issue->text_excerpt ?? $issue->description ?? '',
                    $issue->context ?? '',
                    $issue->suggestion ?? '',
                    $issue->assignment?->status ?? 'open',
                    $issue->created_at->toIso8601String(),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Export project issues to JSON.
     */
    public function exportIssuesToJson(Project $project, ?array $filters = []): StreamedResponse
    {
        $issues = $this->getProjectIssues($project, $filters);

        $filename = sprintf('%s-issues-%s.json', $project->slug, now()->format('Y-m-d'));

        $data = [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'exported_at' => now()->toIso8601String(),
            ],
            'summary' => [
                'total_issues' => $issues->count(),
                'by_category' => $issues->groupBy('category')->map->count(),
                'by_severity' => $issues->groupBy('severity')->map->count(),
            ],
            'issues' => $issues->map(fn ($issue) => [
                'id' => $issue->id,
                'page_url' => $issue->result?->scan?->url?->url ?? null,
                'category' => $issue->category,
                'severity' => $issue->severity,
                'issue' => $issue->text_excerpt ?? $issue->description ?? '',
                'context' => $issue->context,
                'suggestion' => $issue->suggestion,
                'status' => $issue->assignment?->status ?? 'open',
                'metadata' => $issue->metadata,
                'detected_at' => $issue->created_at->toIso8601String(),
            ])->values(),
        ];

        return response()->streamDownload(function () use ($data) {
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }, $filename, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Export project issues to PDF.
     */
    public function exportIssuesToPdf(Project $project, ?array $filters = []): \Illuminate\Http\Response
    {
        $issues = $this->getProjectIssues($project, $filters);

        $stats = [
            'total' => $issues->count(),
            'by_category' => $issues->groupBy('category')->map->count(),
            'by_severity' => $issues->groupBy('severity')->map->count(),
        ];

        $pdf = Pdf::loadView('exports.issues-pdf', [
            'project' => $project,
            'issues' => $issues,
            'stats' => $stats,
            'exportedAt' => now(),
        ]);

        $filename = sprintf('%s-issues-%s.pdf', $project->slug, now()->format('Y-m-d'));

        return $pdf->download($filename);
    }

    /**
     * Export scan results to CSV.
     */
    public function exportScanResultsToCsv(Scan $scan): StreamedResponse
    {
        $issues = $scan->result?->issues ?? collect();

        $filename = sprintf('scan-%d-results-%s.csv', $scan->id, now()->format('Y-m-d'));

        return response()->streamDownload(function () use ($issues) {
            $handle = fopen('php://output', 'w');

            // CSV Header
            fputcsv($handle, [
                'ID',
                'Category',
                'Severity',
                'Issue',
                'Context',
                'Suggestion',
                'Line Number',
            ]);

            // CSV Data
            foreach ($issues as $issue) {
                fputcsv($handle, [
                    $issue->id,
                    $issue->category,
                    $issue->severity,
                    $issue->text_excerpt ?? $issue->description ?? '',
                    $issue->context ?? '',
                    $issue->suggestion ?? '',
                    $issue->line_number ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Export project summary report to PDF.
     */
    public function exportProjectSummaryPdf(Project $project): \Illuminate\Http\Response
    {
        $urls = $project->urls()->with(['latestScan.result.issues'])->get();

        $stats = [
            'total_urls' => $urls->count(),
            'scanned_urls' => $urls->where('status', 'completed')->count(),
            'total_issues' => 0,
            'errors' => 0,
            'warnings' => 0,
            'by_category' => [],
        ];

        foreach ($urls as $url) {
            if ($url->latestScan?->result) {
                $issues = $url->latestScan->result->issues;
                $stats['total_issues'] += $issues->count();
                $stats['errors'] += $issues->where('severity', 'error')->count();
                $stats['warnings'] += $issues->where('severity', 'warning')->count();

                foreach ($issues->groupBy('category') as $category => $categoryIssues) {
                    $stats['by_category'][$category] = ($stats['by_category'][$category] ?? 0) + $categoryIssues->count();
                }
            }
        }

        $pdf = Pdf::loadView('exports.project-summary-pdf', [
            'project' => $project,
            'urls' => $urls,
            'stats' => $stats,
            'exportedAt' => now(),
        ]);

        $filename = sprintf('%s-summary-%s.pdf', $project->slug, now()->format('Y-m-d'));

        return $pdf->download($filename);
    }

    /**
     * Get project issues with optional filters.
     *
     * @return Collection<int, \App\Models\Issue>
     */
    protected function getProjectIssues(Project $project, ?array $filters = []): Collection
    {
        $urlIds = $project->urls()->pluck('id');

        $query = \App\Models\Issue::query()
            ->whereHas('result.scan', function ($q) use ($urlIds) {
                $q->whereIn('url_id', $urlIds);
            })
            ->with(['result.scan.url', 'assignment']);

        // Apply category filter
        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        // Apply severity filter
        if (! empty($filters['severity'])) {
            $query->where('severity', $filters['severity']);
        }

        // Apply status filter
        if (! empty($filters['status'])) {
            if ($filters['status'] === 'open') {
                $query->whereDoesntHave('assignment', function ($q) {
                    $q->where('status', 'resolved');
                });
            } elseif ($filters['status'] === 'resolved') {
                $query->whereHas('assignment', function ($q) {
                    $q->where('status', 'resolved');
                });
            }
        }

        return $query->latest()->get();
    }
}
