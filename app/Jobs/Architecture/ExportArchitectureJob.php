<?php

namespace App\Jobs\Architecture;

use App\Models\SiteArchitecture;
use App\Services\Architecture\Export\ExportService;
use App\Services\Architecture\Export\FigmaExportService;
use App\Services\Architecture\Export\MermaidExportService;
use App\Services\Architecture\Export\PdfExportService;
use App\Services\Architecture\Export\SvgExportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ExportArchitectureJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public int $timeout = 300;

    public function __construct(
        public SiteArchitecture $architecture,
        public string $format,
        public array $options = [],
        public ?string $userId = null,
        public ?string $notificationChannel = null
    ) {}

    public function handle(): void
    {
        $service = $this->getExportService();

        try {
            $content = $service->generate();
            $filename = $service->getFilename();
            $mimeType = $service->getMimeType();

            // Store the export file
            $path = $this->storeExport($content, $filename);

            // Record the export
            $this->recordExport($path, $filename, $mimeType, strlen($content));

            // Notify user if channel provided
            if ($this->notificationChannel) {
                $this->notifyUser($path, $filename);
            }
        } catch (\Throwable $e) {
            $this->recordExportFailure($e);
            throw $e;
        }
    }

    protected function getExportService(): ExportService
    {
        return match ($this->format) {
            'svg' => new SvgExportService($this->architecture, $this->options),
            'mermaid' => new MermaidExportService($this->architecture, $this->options),
            'figma' => new FigmaExportService($this->architecture, $this->options),
            'pdf' => new PdfExportService($this->architecture, $this->options),
            default => throw new \InvalidArgumentException("Unknown export format: {$this->format}"),
        };
    }

    protected function storeExport(string $content, string $filename): string
    {
        $directory = 'exports/architecture/'.$this->architecture->id;
        $path = $directory.'/'.$filename;

        Storage::disk('local')->put($path, $content);

        return $path;
    }

    protected function recordExport(string $path, string $filename, string $mimeType, int $size): void
    {
        // Store export metadata for tracking/cleanup
        $metadata = [
            'architecture_id' => $this->architecture->id,
            'project_id' => $this->architecture->project_id,
            'format' => $this->format,
            'filename' => $filename,
            'path' => $path,
            'mime_type' => $mimeType,
            'size' => $size,
            'options' => $this->options,
            'exported_at' => now()->toIso8601String(),
            'exported_by' => $this->userId,
            'expires_at' => now()->addDays(7)->toIso8601String(),
        ];

        $metaPath = str_replace($filename, $filename.'.meta.json', $path);
        Storage::disk('local')->put($metaPath, json_encode($metadata, JSON_PRETTY_PRINT));

        // Log export activity if spatie/laravel-activitylog is available
        if (function_exists('activity')) {
            activity()
                ->performedOn($this->architecture)
                ->causedBy($this->userId)
                ->withProperties([
                    'format' => $this->format,
                    'filename' => $filename,
                    'size' => $size,
                ])
                ->log('architecture_exported');
        }
    }

    protected function recordExportFailure(\Throwable $e): void
    {
        // Log export failure if spatie/laravel-activitylog is available
        if (function_exists('activity')) {
            activity()
                ->performedOn($this->architecture)
                ->causedBy($this->userId)
                ->withProperties([
                    'format' => $this->format,
                    'error' => $e->getMessage(),
                ])
                ->log('architecture_export_failed');
        }
    }

    protected function notifyUser(string $path, string $filename): void
    {
        // Broadcast to user's notification channel
        broadcast(new \App\Events\ExportCompleted(
            userId: $this->userId,
            architectureId: $this->architecture->id,
            format: $this->format,
            filename: $filename,
            downloadUrl: route('architecture.export.download', [
                'path' => $path,
                'mime' => $this->getExportService()->getMimeType(),
            ])
        ))->toOthers();
    }

    public function tags(): array
    {
        return [
            'export',
            'architecture:'.$this->architecture->id,
            'format:'.$this->format,
        ];
    }

    public function uniqueId(): string
    {
        return 'export-architecture-'.$this->architecture->id.'-'.$this->format;
    }
}
