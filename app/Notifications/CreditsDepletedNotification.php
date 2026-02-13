<?php

namespace App\Notifications;

use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CreditsDepletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Organization $organization,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->notification_preferences['credits_depleted_email'] ?? true) {
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
            ->subject('Credits Depleted - Action Required')
            ->greeting("Hello {$notifiable->name},")
            ->error()
            ->line("Your organization **{$this->organization->name}** has run out of credits.")
            ->line('You will not be able to run new scans until you purchase more credits or your subscription renews.')
            ->action('Buy Credits Now', route('billing.credits'))
            ->line('Need help? Contact our support team.')
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
            'type' => 'credits_depleted',
            'organization_id' => $this->organization->id,
            'organization_name' => $this->organization->name,
            'link' => route('billing.credits'),
        ];
    }
}
