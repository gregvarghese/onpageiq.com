<?php

namespace App\Notifications;

use App\Models\Issue;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class IssueAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Issue $issue,
        public User $assignedBy
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
        $project = $this->issue->result->scan->url->project;
        $url = $this->issue->result->scan->url;

        return (new MailMessage)
            ->subject("Issue Assigned to You - {$project->name}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("{$this->assignedBy->name} has assigned you an issue to review.")
            ->line("**Project:** {$project->name}")
            ->line("**Page:** {$url->url}")
            ->line('**Issue Type:** '.ucfirst($this->issue->category))
            ->line("**Issue:** {$this->issue->text_excerpt}")
            ->when($this->issue->suggestion, function ($message) {
                return $message->line("**Suggested Fix:** {$this->issue->suggestion}");
            })
            ->action('View Issue', route('scans.show', $this->issue->result->scan))
            ->line('Please review and address this issue at your earliest convenience.')
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
            'type' => 'issue_assigned',
            'issue_id' => $this->issue->id,
            'assigned_by_id' => $this->assignedBy->id,
            'assigned_by_name' => $this->assignedBy->name,
            'category' => $this->issue->category,
            'message' => "{$this->assignedBy->name} assigned you an issue: {$this->issue->text_excerpt}",
        ];
    }
}
