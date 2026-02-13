<?php

namespace App\Notifications;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TeamInviteNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Organization $organization,
        public User $inviter,
        public string $role = 'member',
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
        return (new MailMessage)
            ->subject("You've been invited to join {$this->organization->name}")
            ->greeting("Hello {$notifiable->name},")
            ->line("**{$this->inviter->name}** has invited you to join **{$this->organization->name}** on OnPageIQ.")
            ->line("You've been assigned the **{$this->role}** role.")
            ->action('Accept Invitation', route('dashboard'))
            ->line('If you did not expect this invitation, you can ignore this email.')
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
            'type' => 'team_invite',
            'organization_id' => $this->organization->id,
            'organization_name' => $this->organization->name,
            'inviter_id' => $this->inviter->id,
            'inviter_name' => $this->inviter->name,
            'role' => $this->role,
            'link' => route('dashboard'),
        ];
    }
}
