<?php

namespace App\Notifications;

use App\Models\Scan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ScanCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Scan $scan,
        public int $issueCount = 0,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->notification_preferences['scan_completed_email'] ?? true) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $url = $this->scan->url;
        $status = $this->scan->status === 'completed' ? 'completed successfully' : 'failed';

        $message = (new MailMessage)
            ->subject("Scan {$status}: {$url->url}")
            ->greeting("Hello {$notifiable->name},");

        if ($this->scan->status === 'completed') {
            $message->line("Your scan of **{$url->url}** has completed.")
                ->line("**Issues found:** {$this->issueCount}")
                ->action('View Results', route('scans.show', $this->scan));
        } else {
            $message->line("Your scan of **{$url->url}** has failed.")
                ->line('Please check the URL and try again.')
                ->action('View Details', route('scans.show', $this->scan));
        }

        return $message->line('Thank you for using OnPageIQ!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'scan_completed',
            'scan_id' => $this->scan->id,
            'url' => $this->scan->url->url,
            'project_name' => $this->scan->url->project->name,
            'status' => $this->scan->status,
            'issue_count' => $this->issueCount,
            'link' => route('scans.show', $this->scan),
        ];
    }
}
