<?php

namespace App\Notifications;

use App\Models\Scan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewIssuesFoundNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, int>  $issueCounts
     */
    public function __construct(
        public Scan $scan,
        public int $totalIssues,
        public array $issueCounts = []
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
        $url = $this->scan->url;
        $project = $url->project;

        $message = (new MailMessage)
            ->subject("New Issues Found - {$project->name}")
            ->greeting('Hello!')
            ->line("A scan has found **{$this->totalIssues} new issue(s)** on your website.")
            ->line("**Project:** {$project->name}")
            ->line("**Page:** {$url->url}");

        // Add issue breakdown
        if (! empty($this->issueCounts)) {
            $breakdown = [];
            foreach ($this->issueCounts as $category => $count) {
                if ($count > 0) {
                    $breakdown[] = ucfirst($category).": {$count}";
                }
            }
            if (! empty($breakdown)) {
                $message->line('**Issue Breakdown:**')
                    ->line(implode(' | ', $breakdown));
            }
        }

        return $message
            ->action('View Scan Results', route('scans.show', $this->scan))
            ->line('Review and address these issues to improve your content quality.')
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
            'type' => 'new_issues_found',
            'scan_id' => $this->scan->id,
            'url_id' => $this->scan->url_id,
            'url' => $this->scan->url->url,
            'project_id' => $this->scan->url->project_id,
            'project_name' => $this->scan->url->project->name,
            'total_issues' => $this->totalIssues,
            'issue_counts' => $this->issueCounts,
            'message' => "{$this->totalIssues} new issue(s) found on {$this->scan->url->url}",
        ];
    }
}
