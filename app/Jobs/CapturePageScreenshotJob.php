<?php

namespace App\Jobs;

use App\Models\PageScreenshot;
use App\Models\Url;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\Browsershot\Browsershot;

class CapturePageScreenshotJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 120;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 30;

    /**
     * Viewport configurations.
     *
     * @var array<string, array{width: int, height: int}>
     */
    protected array $viewports = [
        'desktop' => ['width' => 1920, 'height' => 1080],
        'mobile' => ['width' => 375, 'height' => 812],
    ];

    public function __construct(
        public Url $url,
        public ?int $scanId = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        foreach ($this->viewports as $viewport => $dimensions) {
            try {
                $this->captureScreenshot($viewport, $dimensions);
            } catch (\Exception $e) {
                Log::warning('Screenshot capture failed', [
                    'url_id' => $this->url->id,
                    'url' => $this->url->url,
                    'viewport' => $viewport,
                    'error' => $e->getMessage(),
                ]);

                // Continue with other viewports even if one fails
                continue;
            }
        }
    }

    /**
     * Capture a screenshot for a specific viewport.
     *
     * @param  array{width: int, height: int}  $dimensions
     */
    protected function captureScreenshot(string $viewport, array $dimensions): void
    {
        $filename = $this->generateFilename($viewport);
        $tempPath = storage_path('app/temp/'.$filename);

        // Ensure temp directory exists
        if (! is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }

        // Capture the screenshot using Browsershot
        $browsershot = Browsershot::url($this->url->url)
            ->windowSize($dimensions['width'], $dimensions['height'])
            ->setScreenshotType('png')
            ->waitUntilNetworkIdle()
            ->timeout(60)
            ->setDelay(1000); // Wait 1 second for any animations

        // Set mobile user agent for mobile viewport
        if ($viewport === 'mobile') {
            $browsershot->userAgent(
                'Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.0 Mobile/15E148 Safari/604.1'
            );
        }

        // Full page screenshot
        $browsershot->fullPage()->save($tempPath);

        // Get file size
        $fileSize = filesize($tempPath);

        // Move to permanent storage
        $storagePath = 'screenshots/'.$this->url->project_id.'/'.$filename;
        Storage::disk('public')->put($storagePath, file_get_contents($tempPath));

        // Clean up temp file
        @unlink($tempPath);

        // Create or update the screenshot record
        PageScreenshot::updateOrCreate(
            [
                'url_id' => $this->url->id,
                'viewport' => $viewport,
            ],
            [
                'scan_id' => $this->scanId,
                'file_path' => $storagePath,
                'file_size' => $fileSize,
                'width' => $dimensions['width'],
                'height' => $dimensions['height'],
                'captured_at' => now(),
            ]
        );

        Log::info('Screenshot captured successfully', [
            'url_id' => $this->url->id,
            'viewport' => $viewport,
            'path' => $storagePath,
        ]);
    }

    /**
     * Generate a unique filename for the screenshot.
     */
    protected function generateFilename(string $viewport): string
    {
        $urlHash = substr(md5($this->url->url), 0, 8);
        $timestamp = now()->format('Y-m-d_His');

        return "{$viewport}_{$urlHash}_{$timestamp}.png";
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Screenshot capture job failed', [
            'url_id' => $this->url->id,
            'url' => $this->url->url,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<string>
     */
    public function tags(): array
    {
        return [
            'screenshot',
            'url:'.$this->url->id,
            'project:'.$this->url->project_id,
        ];
    }
}
