<?php

namespace App\Services\Export;

use App\Models\Scan;
use App\Models\ScanResult;
use App\Services\Diff\ScanComparison;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Spatie\Browsershot\Browsershot;

class PdfExportService
{
    protected string $nodePath;

    protected string $npmPath;

    public function __construct()
    {
        $this->nodePath = config('onpageiq.export.node_path', '/usr/local/bin/node');
        $this->npmPath = config('onpageiq.export.npm_path', '/usr/local/bin/npm');
    }

    /**
     * Export scan results to PDF.
     */
    public function exportScanResult(Scan $scan): string
    {
        $result = $scan->result;

        if (! $result) {
            throw new \RuntimeException('Scan has no results to export.');
        }

        $html = $this->renderScanResultHtml($scan, $result);

        return $this->generatePdf($html, "scan-report-{$scan->id}");
    }

    /**
     * Export scan comparison to PDF.
     */
    public function exportComparison(ScanComparison $comparison): string
    {
        $html = $this->renderComparisonHtml($comparison);

        return $this->generatePdf($html, "comparison-report-{$comparison->current->scan_id}");
    }

    /**
     * Render scan result HTML for PDF.
     */
    protected function renderScanResultHtml(Scan $scan, ScanResult $result): string
    {
        $issues = $result->issues()
            ->orderByRaw("CASE severity WHEN 'error' THEN 1 WHEN 'warning' THEN 2 ELSE 3 END")
            ->get();

        $categoryCounts = $result->issues()
            ->selectRaw('category, count(*) as count')
            ->groupBy('category')
            ->pluck('count', 'category')
            ->toArray();

        $severityCounts = $result->issues()
            ->selectRaw('severity, count(*) as count')
            ->groupBy('severity')
            ->pluck('count', 'severity')
            ->toArray();

        return View::make('exports.scan-result', [
            'scan' => $scan,
            'result' => $result,
            'issues' => $issues,
            'scores' => $result->scores ?? [],
            'categoryCounts' => $categoryCounts,
            'severityCounts' => $severityCounts,
            'generatedAt' => now(),
        ])->render();
    }

    /**
     * Render comparison HTML for PDF.
     */
    protected function renderComparisonHtml(ScanComparison $comparison): string
    {
        return View::make('exports.scan-comparison', [
            'comparison' => $comparison,
            'generatedAt' => now(),
        ])->render();
    }

    /**
     * Generate PDF from HTML.
     */
    protected function generatePdf(string $html, string $filename): string
    {
        $filename = "{$filename}-".now()->format('Y-m-d-His').'.pdf';
        $path = "exports/{$filename}";
        $fullPath = storage_path("app/{$path}");

        // Ensure directory exists
        if (! is_dir(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }

        Browsershot::html($html)
            ->setNodeBinary($this->nodePath)
            ->setNpmBinary($this->npmPath)
            ->showBackground()
            ->format('A4')
            ->margins(15, 15, 15, 15)
            ->save($fullPath);

        return $path;
    }

    /**
     * Get the download URL for an exported PDF.
     */
    public function getDownloadUrl(string $path): string
    {
        return Storage::disk('local')->url($path);
    }

    /**
     * Delete an exported PDF.
     */
    public function delete(string $path): void
    {
        if (Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }
    }

    /**
     * Clean up old exports.
     */
    public function cleanupOldExports(int $daysOld = 7): int
    {
        $cutoff = now()->subDays($daysOld);
        $deleted = 0;

        $files = Storage::disk('local')->files('exports');

        foreach ($files as $file) {
            $lastModified = Storage::disk('local')->lastModified($file);

            if ($lastModified < $cutoff->timestamp) {
                Storage::disk('local')->delete($file);
                $deleted++;
            }
        }

        return $deleted;
    }
}
