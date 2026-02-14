<?php

namespace App\Jobs;

use App\Models\Scan;
use App\Models\ScanSchedule;
use App\Notifications\InsufficientCreditsNotification;
use App\Notifications\ScheduledScanCompletedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class ProcessScheduledScansJob implements ShouldQueue
{
    use Queueable;

    /**
     * Credit costs per scan type.
     */
    protected const CREDIT_COSTS = [
        'quick' => 1,
        'deep' => 3,
        'full' => 5,
    ];

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
            ->with(['project.urls', 'project.organization', 'urlGroup.urls'])
            ->get();

        Log::info('Processing scheduled scans', ['count' => $dueSchedules->count()]);

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
            Log::info('Scheduled scan has no URLs to scan', [
                'schedule_id' => $schedule->id,
                'project_id' => $project->id,
            ]);
            $this->updateNextRunTime($schedule, 'skipped_no_urls');

            return;
        }

        // Calculate credit cost
        $creditCostPerUrl = self::CREDIT_COSTS[$schedule->scan_type] ?? 1;
        $totalCost = $urls->count() * $creditCostPerUrl;
        $currentBalance = $organization->credit_balance ?? 0;

        // Check if organization has enough credits
        if ($currentBalance < $totalCost) {
            $this->handleInsufficientCredits($schedule, $organization, $totalCost, $currentBalance);

            return;
        }

        // Reserve credits before starting scans
        $organization->reserveCredits($totalCost, [
            'type' => 'scheduled_scan',
            'schedule_id' => $schedule->id,
            'project_id' => $project->id,
            'url_count' => $urls->count(),
        ]);

        $scannedCount = 0;
        $skippedCount = 0;

        // Create scans for each URL
        foreach ($urls as $url) {
            // Skip URLs that are currently scanning
            if ($url->isScanning()) {
                $skippedCount++;

                continue;
            }

            $url->markAsScanning();

            $scan = Scan::create([
                'url_id' => $url->id,
                'triggered_by_user_id' => null, // System-triggered
                'scan_schedule_id' => $schedule->id,
                'scan_type' => $schedule->scan_type,
                'status' => 'pending',
                'credit_cost' => $creditCostPerUrl,
            ]);

            ScanUrlJob::dispatch($scan);
            $scannedCount++;
        }

        // Adjust reserved credits if some URLs were skipped
        if ($skippedCount > 0) {
            $refundAmount = $skippedCount * $creditCostPerUrl;
            $organization->refundCredits($refundAmount, [
                'type' => 'scheduled_scan_adjustment',
                'schedule_id' => $schedule->id,
                'skipped_urls' => $skippedCount,
            ]);
        }

        Log::info('Scheduled scan triggered', [
            'schedule_id' => $schedule->id,
            'project_id' => $project->id,
            'urls_scanned' => $scannedCount,
            'urls_skipped' => $skippedCount,
            'credits_used' => $scannedCount * $creditCostPerUrl,
        ]);

        $this->updateNextRunTime($schedule, 'completed', [
            'urls_scanned' => $scannedCount,
            'urls_skipped' => $skippedCount,
        ]);

        // Notify project owners
        $this->notifyScheduleTriggered($schedule, $scannedCount);
    }

    /**
     * Handle insufficient credits scenario.
     */
    protected function handleInsufficientCredits(
        ScanSchedule $schedule,
        $organization,
        int $requiredCredits,
        int $currentBalance
    ): void {
        Log::warning('Scheduled scan skipped - insufficient credits', [
            'schedule_id' => $schedule->id,
            'organization_id' => $organization->id,
            'required_credits' => $requiredCredits,
            'current_balance' => $currentBalance,
        ]);

        // Track consecutive failures
        $failureCount = ($schedule->metadata['consecutive_credit_failures'] ?? 0) + 1;

        $this->updateNextRunTime($schedule, 'skipped_insufficient_credits', [
            'required_credits' => $requiredCredits,
            'current_balance' => $currentBalance,
            'consecutive_credit_failures' => $failureCount,
        ]);

        // Send notification on first failure and every 3rd failure after
        if ($failureCount === 1 || $failureCount % 3 === 0) {
            $this->notifyInsufficientCredits($schedule, $organization, $requiredCredits, $currentBalance);
        }

        // Disable schedule after 10 consecutive failures
        if ($failureCount >= 10) {
            $schedule->update([
                'is_active' => false,
                'deactivated_at' => now(),
                'deactivation_reason' => 'insufficient_credits',
            ]);

            Log::warning('Scheduled scan deactivated due to repeated credit failures', [
                'schedule_id' => $schedule->id,
            ]);
        }
    }

    /**
     * Notify organization about insufficient credits.
     */
    protected function notifyInsufficientCredits(
        ScanSchedule $schedule,
        $organization,
        int $requiredCredits,
        int $currentBalance
    ): void {
        $admins = $organization->users()->whereHas('roles', function ($q) {
            $q->whereIn('name', ['owner', 'admin']);
        })->get();

        if ($admins->isNotEmpty() && class_exists(InsufficientCreditsNotification::class)) {
            Notification::send($admins, new InsufficientCreditsNotification(
                $schedule,
                $requiredCredits,
                $currentBalance
            ));
        }
    }

    /**
     * Notify about triggered schedule.
     */
    protected function notifyScheduleTriggered(ScanSchedule $schedule, int $urlCount): void
    {
        // Only notify if configured
        if (! ($schedule->notify_on_complete ?? false)) {
            return;
        }

        $project = $schedule->project;
        $notifyUsers = $project->members()->get();

        if ($notifyUsers->isNotEmpty() && class_exists(ScheduledScanCompletedNotification::class)) {
            Notification::send($notifyUsers, new ScheduledScanCompletedNotification(
                $schedule,
                $urlCount
            ));
        }
    }

    /**
     * Update the schedule's next run time and metadata.
     *
     * @param  array<string, mixed>  $metadata
     */
    protected function updateNextRunTime(ScanSchedule $schedule, string $status, array $metadata = []): void
    {
        $existingMetadata = $schedule->metadata ?? [];

        // Reset failure count on success
        if ($status === 'completed') {
            $metadata['consecutive_credit_failures'] = 0;
        }

        $schedule->update([
            'last_run_at' => now(),
            'last_run_status' => $status,
            'next_run_at' => $schedule->calculateNextRunAt(),
            'metadata' => array_merge($existingMetadata, $metadata, [
                'last_run_details' => [
                    'status' => $status,
                    'timestamp' => now()->toIso8601String(),
                ],
            ]),
        ]);
    }
}
