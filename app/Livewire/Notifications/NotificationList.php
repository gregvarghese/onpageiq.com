<?php

namespace App\Livewire\Notifications;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class NotificationList extends Component
{
    use WithPagination;

    public string $filter = 'all';

    public function markAsRead(string $notificationId): void
    {
        Auth::user()
            ->notifications()
            ->where('id', $notificationId)
            ->first()
            ?->markAsRead();
    }

    public function markAllAsRead(): void
    {
        Auth::user()->unreadNotifications->markAsRead();
    }

    public function deleteNotification(string $notificationId): void
    {
        Auth::user()
            ->notifications()
            ->where('id', $notificationId)
            ->delete();
    }

    public function deleteAllRead(): void
    {
        Auth::user()
            ->readNotifications()
            ->delete();
    }

    public function getNotificationsProperty(): LengthAwarePaginator
    {
        $query = Auth::user()->notifications();

        if ($this->filter === 'unread') {
            $query->whereNull('read_at');
        } elseif ($this->filter === 'read') {
            $query->whereNotNull('read_at');
        }

        return $query->latest()->paginate(15);
    }

    public function render(): View
    {
        return view('livewire.notifications.notification-list', [
            'notifications' => $this->notifications,
            'unreadCount' => Auth::user()->unreadNotifications()->count(),
        ]);
    }
}
