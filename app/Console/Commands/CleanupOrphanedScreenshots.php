<?php

namespace App\Console\Commands;

use App\Services\Screenshot\IssueScreenshotService;
use Illuminate\Console\Command;

class CleanupOrphanedScreenshots extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'screenshots:cleanup
                            {--days=7 : Delete screenshots older than this many days}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up orphaned screenshot files that are no longer referenced by any issue';

    /**
     * Execute the console command.
     */
    public function handle(IssueScreenshotService $screenshotService): int
    {
        $days = (int) $this->option('days');

        $this->info("Cleaning up orphaned screenshots older than {$days} days...");

        $deleted = $screenshotService->cleanupOrphanedScreenshots($days);

        $this->info("Deleted {$deleted} orphaned screenshot(s).");

        return self::SUCCESS;
    }
}
