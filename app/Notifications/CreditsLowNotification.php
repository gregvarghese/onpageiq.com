<?php

namespace App\Notifications;

use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CreditsLowNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Organization $organization,
        public int $remainingCredits,
        public int $threshold = 10,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->notification_preferences['credits_low_email'] ?? true) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Low Credit Balance Alert')
            ->greeting("Hello {$notifiable->name},")
            ->line("Your organization **{$this->organization->name}** is running low on credits.")
            ->line("**Current balance:** {$this->remainingCredits} credits")
            ->line('Purchase more credits to continue running scans without interruption.')
            ->action('Buy Credits', route('billing.credits'))
            ->line('Thank you for using OnPageIQ!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'credits_low',
            'organization_id' => $this->organization->id,
            'organization_name' => $this->organization->name,
            'remaining_credits' => $this->remainingCredits,
            'threshold' => $this->threshold,
            'link' => route('billing.credits'),
        ];
    }
}
