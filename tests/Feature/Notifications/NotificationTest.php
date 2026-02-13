<?php

use App\Livewire\Notifications\NotificationDropdown;
use App\Livewire\Notifications\NotificationList;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Scan;
use App\Models\Url;
use App\Models\User;
use App\Notifications\CreditsDepletedNotification;
use App\Notifications\CreditsLowNotification;
use App\Notifications\ScanCompletedNotification;
use App\Services\Notification\NotificationService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->organization = Organization::factory()->create([
        'credit_balance' => 100,
    ]);
    $this->user = User::factory()->create([
        'organization_id' => $this->organization->id,
    ]);
});

it('sends scan completed notification', function () {
    Notification::fake();

    $project = Project::factory()->create([
        'organization_id' => $this->organization->id,
    ]);
    $url = Url::factory()->create([
        'project_id' => $project->id,
    ]);
    $scan = Scan::factory()->create([
        'url_id' => $url->id,
        'triggered_by_user_id' => $this->user->id,
        'status' => 'completed',
    ]);

    $service = app(NotificationService::class);
    $service->notifyScanCompleted($scan, 5);

    Notification::assertSentTo($this->user, ScanCompletedNotification::class);
});

it('sends credits low notification', function () {
    Notification::fake();

    $this->user->assignRole('Owner');

    $service = app(NotificationService::class);
    $service->notifyCreditsLow($this->organization, 5);

    Notification::assertSentTo($this->user, CreditsLowNotification::class);
});

it('sends credits depleted notification', function () {
    Notification::fake();

    $this->user->assignRole('Owner');

    $service = app(NotificationService::class);
    $service->notifyCreditsDepeleted($this->organization);

    Notification::assertSentTo($this->user, CreditsDepletedNotification::class);
});

it('renders notification dropdown', function () {
    Livewire::actingAs($this->user)
        ->test(NotificationDropdown::class)
        ->assertStatus(200);
});

it('shows unread count in dropdown', function () {
    // Create unread notification
    $this->user->notify(new CreditsLowNotification($this->organization, 5));

    Livewire::actingAs($this->user)
        ->test(NotificationDropdown::class)
        ->assertSee('1');
});

it('marks notification as read', function () {
    $this->user->notify(new CreditsLowNotification($this->organization, 5));

    $notification = $this->user->unreadNotifications()->first();

    Livewire::actingAs($this->user)
        ->test(NotificationDropdown::class)
        ->call('markAsRead', $notification->id);

    expect($this->user->unreadNotifications()->count())->toBe(0);
});

it('marks all notifications as read', function () {
    $this->user->notify(new CreditsLowNotification($this->organization, 5));
    $this->user->notify(new CreditsDepletedNotification($this->organization));

    expect($this->user->unreadNotifications()->count())->toBe(2);

    Livewire::actingAs($this->user)
        ->test(NotificationDropdown::class)
        ->call('markAllAsRead');

    expect($this->user->unreadNotifications()->count())->toBe(0);
});

it('renders notification list page', function () {
    Livewire::actingAs($this->user)
        ->test(NotificationList::class)
        ->assertStatus(200)
        ->assertSee('Notifications');
});

it('filters notifications by read status', function () {
    $this->user->notify(new CreditsLowNotification($this->organization, 5));

    Livewire::actingAs($this->user)
        ->test(NotificationList::class)
        ->set('filter', 'unread')
        ->assertSee('Low credit balance');
});

it('deletes a notification', function () {
    $this->user->notify(new CreditsLowNotification($this->organization, 5));
    $notification = $this->user->notifications()->first();

    Livewire::actingAs($this->user)
        ->test(NotificationList::class)
        ->call('deleteNotification', $notification->id);

    expect($this->user->notifications()->count())->toBe(0);
});
