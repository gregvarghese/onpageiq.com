<?php

namespace App\Http\Controllers;

use App\Models\Scan;
use App\Services\Diff\ScanDiffService;
use App\Services\Export\PdfExportService;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportController extends Controller
{
    public function __construct(
        protected PdfExportService $exportService
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

        $path = $this->exportService->exportScanResult($scan);
        $fullPath = storage_path("app/{$path}");

        return response()->download($fullPath, basename($path), [
            'Content-Type' => 'application/pdf',
        ])->deleteFileAfterSend(false);
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
        $path = $this->exportService->exportComparison($comparison);
        $fullPath = storage_path("app/{$path}");

        return response()->download($fullPath, basename($path), [
            'Content-Type' => 'application/pdf',
        ])->deleteFileAfterSend(false);
    }
}
