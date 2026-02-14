<?php

namespace App\Notifications;

use App\Models\ScanSchedule;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InsufficientCreditsNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ScanSchedule $schedule,
        public int $requiredCredits,
        public int $currentBalance
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

        return (new MailMessage)
            ->subject('Scheduled Scan Skipped - Insufficient Credits')
            ->greeting('Hello!')
            ->line("A scheduled scan for **{$project->name}** was skipped due to insufficient credits.")
            ->line("**Required credits:** {$this->requiredCredits}")
            ->line("**Current balance:** {$this->currentBalance}")
            ->line('**Credits needed:** '.($this->requiredCredits - $this->currentBalance))
            ->action('Purchase Credits', route('billing.credits'))
            ->line('To ensure your scheduled scans continue running, please top up your credit balance.')
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
            'type' => 'insufficient_credits',
            'schedule_id' => $this->schedule->id,
            'project_id' => $this->schedule->project_id,
            'project_name' => $this->schedule->project->name,
            'required_credits' => $this->requiredCredits,
            'current_balance' => $this->currentBalance,
            'message' => "Scheduled scan skipped - need {$this->requiredCredits} credits, only have {$this->currentBalance}",
        ];
    }
}
