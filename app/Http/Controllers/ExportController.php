<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Scan;
use App\Services\Diff\ScanDiffService;
use App\Services\Export\PdfExportService;
use App\Services\ExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function __construct(
        protected PdfExportService $pdfExportService,
        protected ExportService $exportService
    ) {}

    /**
     * Export scan results as PDF.
     */
    public function scanPdf(Scan $scan): BinaryFileResponse
    {
        Gate::authorize('view', $scan->url->project);

        if (! $scan->result) {
            abort(404, 'Scan has no results.');
        }

        $path = $this->pdfExportService->exportScanResult($scan);
        $fullPath = storage_path("app/{$path}");

        return response()->download($fullPath, basename($path), [
            'Content-Type' => 'application/pdf',
        ])->deleteFileAfterSend(false);
    }

    /**
     * Export project issues as CSV.
     */
    public function projectIssuesCsv(Project $project, Request $request): StreamedResponse
    {
        Gate::authorize('view', $project);

        $filters = $request->only(['category', 'severity', 'status']);

        return $this->exportService->exportIssuesToCsv($project, $filters);
    }

    /**
     * Export project issues as JSON.
     */
    public function projectIssuesJson(Project $project, Request $request): StreamedResponse
    {
        Gate::authorize('view', $project);

        $filters = $request->only(['category', 'severity', 'status']);

        return $this->exportService->exportIssuesToJson($project, $filters);
    }

    /**
     * Export project issues as PDF.
     */
    public function projectIssuesPdf(Project $project, Request $request): \Illuminate\Http\Response
    {
        Gate::authorize('view', $project);

        $filters = $request->only(['category', 'severity', 'status']);

        return $this->exportService->exportIssuesToPdf($project, $filters);
    }

    /**
     * Export project summary as PDF.
     */
    public function projectSummaryPdf(Project $project): \Illuminate\Http\Response
    {
        Gate::authorize('view', $project);

        return $this->exportService->exportProjectSummaryPdf($project);
    }

    /**
     * Export scan results as CSV.
     */
    public function scanCsv(Scan $scan): StreamedResponse
    {
        Gate::authorize('view', $scan->url->project);

        if (! $scan->result) {
            abort(404, 'Scan has no results.');
        }

        return $this->exportService->exportScanResultsToCsv($scan);
    }

    /**
     * Export scan comparison as PDF.
     */
    public function comparisonPdf(Scan $scan, Scan $baseline, ScanDiffService $diffService): BinaryFileResponse
    {
        Gate::authorize('view', $scan->url->project);

        if (! $scan->result || ! $baseline->result) {
            abort(404, 'One or both scans have no results.');
        }

        if ($scan->url_id !== $baseline->url_id) {
            abort(400, 'Scans must be for the same URL.');
        }

        $comparison = $diffService->compare($baseline->result, $scan->result);
        $path = $this->pdfExportService->exportComparison($comparison);
        $fullPath = storage_path("app/{$path}");

        return response()->download($fullPath, basename($path), [
            'Content-Type' => 'application/pdf',
        ])->deleteFileAfterSend(false);
    }
}
