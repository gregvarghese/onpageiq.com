<?php

namespace App\Jobs;

use App\Events\ArchitectureCrawlCompleted;
use App\Events\ArchitectureCrawlFailed;
use App\Models\Project;
use App\Services\Architecture\ArchitectureCrawlService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CrawlArchitectureJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 3600; // 1 hour max

    public int $maxExceptions = 1;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Project $project,
        public array $config = []
    ) {}

    /**
     * Execute the job.
     */
    public function handle(ArchitectureCrawlService $crawlService): void
    {
        Log::info('Starting architecture crawl', [
            'project_id' => $this->project->id,
            'project_name' => $this->project->name,
        ]);

        try {
            $architecture = $crawlService->crawl($this->project, $this->config);

            Log::info('Architecture crawl completed', [
                'project_id' => $this->project->id,
                'architecture_id' => $architecture->id,
                'total_nodes' => $architecture->total_nodes,
                'total_links' => $architecture->total_links,
            ]);

            // Broadcast completion event
            ArchitectureCrawlCompleted::dispatch($architecture);

        } catch (\Throwable $e) {
            Log::error('Architecture crawl failed', [
                'project_id' => $this->project->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Try to find and update the architecture status
            $architecture = $this->project->siteArchitectures()
                ->whereIn('status', ['crawling', 'analyzing', 'pending'])
                ->latest()
                ->first();

            if ($architecture) {
                $architecture->markAsFailed();
                ArchitectureCrawlFailed::dispatch($architecture, $e->getMessage());
            }

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(?\Throwable $exception): void
    {
        Log::error('CrawlArchitectureJob failed permanently', [
            'project_id' => $this->project->id,
            'error' => $exception?->getMessage(),
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
            'architecture',
            'crawl',
            'project:'.$this->project->id,
        ];
    }
}
