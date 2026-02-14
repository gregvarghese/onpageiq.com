<?php

namespace App\Notifications;

use App\Models\ScanSchedule;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ScheduledScanCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ScanSchedule $schedule,
        public int $urlCount
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $project = $this->schedule->project;
        $scanType = ucfirst($this->schedule->scan_type);

        return (new MailMessage)
            ->subject("Scheduled {$scanType} Scan Completed - {$project->name}")
            ->greeting('Hello!')
            ->line("Your scheduled **{$scanType}** scan for **{$project->name}** has completed.")
            ->line("**URLs scanned:** {$this->urlCount}")
            ->line("**Scan type:** {$scanType}")
            ->line("**Schedule:** {$this->schedule->frequency}")
            ->action('View Results', route('projects.show', $project))
            ->line('Review the scan results to see any new issues found.')
            ->salutation('— The OnPageIQ Team');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'scheduled_scan_completed',
            'schedule_id' => $this->schedule->id,
            'project_id' => $this->schedule->project_id,
            'project_name' => $this->schedule->project->name,
            'url_count' => $this->urlCount,
            'scan_type' => $this->schedule->scan_type,
            'message' => "Scheduled scan completed for {$this->schedule->project->name} ({$this->urlCount} URLs)",
        ];
    }
}
