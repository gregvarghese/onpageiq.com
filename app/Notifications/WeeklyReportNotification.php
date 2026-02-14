<?php

namespace App\Notifications;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WeeklyReportNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $stats
     */
    public function __construct(
        public Project $project,
        public array $stats
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject("Weekly Report - {$this->project->name}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("Here's your weekly summary for **{$this->project->name}**.");

        // Score trend
        $scoreChange = $this->stats['score_change'] ?? 0;
        $scoreEmoji = $scoreChange > 0 ? '↑' : ($scoreChange < 0 ? '↓' : '→');
        $changeDisplay = $scoreChange >= 0 ? "+{$scoreChange}" : "{$scoreChange}";
        $message->line("**Quality Score:** {$this->stats['current_score']}% {$scoreEmoji} ({$changeDisplay}% from last week)");

        // Issue summary
        $message->line('---');
        $message->line('**Issue Summary**');
        $message->line("• New issues found: {$this->stats['new_issues']}");
        $message->line("• Issues resolved: {$this->stats['resolved_issues']}");
        $message->line("• Open issues: {$this->stats['open_issues']}");

        // Scans performed
        $message->line('---');
        $message->line('**Activity**');
        $message->line("• Pages scanned: {$this->stats['pages_scanned']}");
        $message->line("• Total scans: {$this->stats['total_scans']}");

        // Top issues if any
        if (! empty($this->stats['top_categories'])) {
            $message->line('---');
            $message->line('**Top Issue Categories**');
            foreach ($this->stats['top_categories'] as $category => $count) {
                $message->line('• '.ucfirst($category).": {$count}");
            }
        }

        return $message
            ->action('View Dashboard', route('projects.show', $this->project))
            ->line('Keep up the great work maintaining your content quality!')
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
            'type' => 'weekly_report',
            'project_id' => $this->project->id,
            'project_name' => $this->project->name,
            'stats' => $this->stats,
        ];
    }
}
