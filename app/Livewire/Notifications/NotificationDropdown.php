<?php

namespace App\Livewire\Notifications;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class NotificationDropdown extends Component
{
    public bool $showDropdown = false;

    #[Computed]
    public function unreadCount(): int
    {
        return Auth::user()->unreadNotifications()->count();
    }

    #[Computed]
    public function notifications(): mixed
    {
        return Auth::user()
            ->notifications()
            ->latest()
            ->limit(10)
            ->get();
    }

    public function toggleDropdown(): void
    {
        $this->showDropdown = ! $this->showDropdown;
    }

    public function markAsRead(string $notificationId): void
    {
        Auth::user()
            ->notifications()
            ->where('id', $notificationId)
            ->first()
            ?->markAsRead();

        unset($this->notifications, $this->unreadCount);
    }

    public function markAllAsRead(): void
    {
        Auth::user()->unreadNotifications->markAsRead();

        unset($this->notifications, $this->unreadCount);
    }

    #[On('notification-received')]
    public function refreshNotifications(): void
    {
        unset($this->notifications, $this->unreadCount);
    }

    public function render(): View
    {
        return view('livewire.notifications.notification-dropdown');
    }
}
