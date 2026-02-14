<?php

namespace App\Jobs;

use App\Models\PageMetrics;
use App\Models\Url;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MeasureCoreWebVitalsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 2;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 120;

    /**
     * The number of seconds to wait before retrying.
     */
    public int $backoff = 30;

    public function __construct(
        public Url $url,
        public ?int $scanId = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Try PageSpeed Insights API first (requires API key)
        $apiKey = config('services.pagespeed.api_key');

        if ($apiKey) {
            $metrics = $this->measureWithPageSpeed($apiKey);
        } else {
            // Fallback to basic HTTP timing measurements
            $metrics = $this->measureBasicMetrics();
        }

        if (empty($metrics)) {
            Log::warning('Failed to measure Core Web Vitals', ['url_id' => $this->url->id]);

            return;
        }

        // Update or create page metrics
        PageMetrics::updateOrCreate(
            [
                'url_id' => $this->url->id,
                'scan_id' => $this->scanId,
            ],
            array_filter([
                'lcp_ms' => $metrics['lcp'] ?? null,
                'fid_ms' => $metrics['fid'] ?? null,
                'cls_score' => $metrics['cls'] ?? null,
                'ttfb_ms' => $metrics['ttfb'] ?? null,
                'fcp_ms' => $metrics['fcp'] ?? null,
                'load_time_ms' => $metrics['load_time'] ?? null,
                'page_size_bytes' => $metrics['page_size'] ?? null,
                'request_count' => $metrics['request_count'] ?? null,
                'performance_score' => $metrics['performance_score'] ?? null,
            ])
        );

        Log::info('Core Web Vitals measured', [
            'url_id' => $this->url->id,
            'lcp' => $metrics['lcp'] ?? null,
            'fid' => $metrics['fid'] ?? null,
            'cls' => $metrics['cls'] ?? null,
        ]);
    }

    /**
     * Measure metrics using Google PageSpeed Insights API.
     *
     * @return array<string, mixed>
     */
    protected function measureWithPageSpeed(string $apiKey): array
    {
        try {
            $response = Http::timeout(60)->get('https://www.googleapis.com/pagespeedonline/v5/runPagespeed', [
                'url' => $this->url->url,
                'key' => $apiKey,
                'category' => 'performance',
                'strategy' => 'mobile',
            ]);

            if (! $response->successful()) {
                Log::warning('PageSpeed API request failed', [
                    'url_id' => $this->url->id,
                    'status' => $response->status(),
                ]);

                return $this->measureBasicMetrics();
            }

            $data = $response->json();

            // Extract Core Web Vitals from Lighthouse data
            $audits = $data['lighthouseResult']['audits'] ?? [];

            return [
                'lcp' => $this->extractMetric($audits, 'largest-contentful-paint', 'numericValue'),
                'fid' => $this->extractMetric($audits, 'max-potential-fid', 'numericValue'),
                'cls' => $this->extractMetric($audits, 'cumulative-layout-shift', 'numericValue'),
                'ttfb' => $this->extractMetric($audits, 'server-response-time', 'numericValue'),
                'fcp' => $this->extractMetric($audits, 'first-contentful-paint', 'numericValue'),
                'performance_score' => ($data['lighthouseResult']['categories']['performance']['score'] ?? 0) * 100,
                'load_time' => $this->extractMetric($audits, 'interactive', 'numericValue'),
            ];
        } catch (\Exception $e) {
            Log::warning('PageSpeed API error', [
                'url_id' => $this->url->id,
                'error' => $e->getMessage(),
            ]);

            return $this->measureBasicMetrics();
        }
    }

    /**
     * Extract a metric value from PageSpeed audits.
     *
     * @param  array<string, mixed>  $audits
     */
    protected function extractMetric(array $audits, string $auditId, string $key): ?float
    {
        if (isset($audits[$auditId][$key])) {
            return round((float) $audits[$auditId][$key], 2);
        }

        return null;
    }

    /**
     * Measure basic metrics using HTTP requests.
     *
     * @return array<string, mixed>
     */
    protected function measureBasicMetrics(): array
    {
        try {
            $startTime = microtime(true);

            $response = Http::timeout(30)
                ->withOptions([
                    'on_stats' => function ($stats) use (&$transferTime, &$ttfb) {
                        $transferTime = $stats->getTransferTime();
                        $handlerStats = $stats->getHandlerStats();
                        $ttfb = ($handlerStats['starttransfer_time'] ?? 0) * 1000;
                    },
                ])
                ->get($this->url->url);

            $loadTime = (microtime(true) - $startTime) * 1000;
            $pageSize = strlen($response->body());

            // Count resources in HTML
            $html = $response->body();
            $requestCount = 1; // Start with 1 for the main document
            $requestCount += preg_match_all('/<script[^>]+src=/i', $html, $m);
            $requestCount += preg_match_all('/<link[^>]+href=/i', $html, $m);
            $requestCount += preg_match_all('/<img[^>]+src=/i', $html, $m);

            return [
                'ttfb' => round($ttfb ?? 0, 2),
                'load_time' => round($loadTime, 2),
                'page_size' => $pageSize,
                'request_count' => $requestCount,
            ];
        } catch (\Exception $e) {
            Log::warning('Basic metrics measurement failed', [
                'url_id' => $this->url->id,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Core Web Vitals measurement job failed', [
            'url_id' => $this->url->id,
            'error' => $exception->getMessage(),
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
            'web-vitals',
            'url:'.$this->url->id,
            'project:'.$this->url->project_id,
        ];
    }
}
