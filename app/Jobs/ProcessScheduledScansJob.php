<?php

namespace App\Jobs;

use App\Models\Scan;
use App\Models\ScanSchedule;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessScheduledScansJob implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $dueSchedules = ScanSchedule::query()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('next_run_at')
                    ->orWhere('next_run_at', '<=', now());
            })
            ->with(['project.urls', 'urlGroup.urls'])
            ->get();

        foreach ($dueSchedules as $schedule) {
            $this->processSchedule($schedule);
        }
    }

    /**
     * Process a single schedule.
     */
    protected function processSchedule(ScanSchedule $schedule): void
    {
        $project = $schedule->project;
        $organization = $project->organization;

        // Get URLs to scan
        if ($schedule->url_group_id && $schedule->urlGroup) {
            $urls = $schedule->urlGroup->urls;
        } else {
            $urls = $project->urls;
        }

        if ($urls->isEmpty()) {
            Log::info("Scheduled scan {$schedule->id} has no URLs to scan");
            $this->updateNextRunTime($schedule);

            return;
        }

        // Check if organization has enough credits
        $creditCost = $schedule->scan_type === 'deep' ? 3 : 1;
        $totalCost = $urls->count() * $creditCost;

        if (! $organization->hasCredits($totalCost)) {
            Log::warning("Scheduled scan {$schedule->id} skipped - insufficient credits");
            $this->updateNextRunTime($schedule);

            return;
        }

        // Create scans for each URL
        foreach ($urls as $url) {
            // Skip URLs that are currently scanning
            if ($url->isScanning()) {
                continue;
            }

            $url->markAsScanning();

            $scan = Scan::create([
                'url_id' => $url->id,
                'triggered_by_user_id' => null, // System-triggered
                'scan_type' => $schedule->scan_type,
                'status' => 'pending',
            ]);

            ScanUrlJob::dispatch($scan);
        }

        Log::info("Scheduled scan {$schedule->id} triggered for {$urls->count()} URLs");

        $this->updateNextRunTime($schedule);
    }

    /**
     * Update the schedule's next run time.
     */
    protected function updateNextRunTime(ScanSchedule $schedule): void
    {
        $schedule->update([
            'last_run_at' => now(),
            'next_run_at' => $schedule->calculateNextRunAt(),
        ]);
    }
}
