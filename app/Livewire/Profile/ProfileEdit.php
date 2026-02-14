<?php

namespace App\Livewire\Profile;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ProfileEdit extends Component
{
    public string $name = '';

    public string $email = '';

    public string $currentPassword = '';

    public string $newPassword = '';

    public string $newPasswordConfirmation = '';

    public bool $notifyOnScanComplete = true;

    public bool $notifyOnIssuesFound = true;

    public bool $notifyOnWeeklyDigest = false;

    public bool $notifyOnBillingAlerts = true;

    public bool $showDeleteModal = false;

    public string $deleteConfirmation = '';

    public function mount(): void
    {
        $user = Auth::user();

        $this->name = $user->name;
        $this->email = $user->email;

        // Load notification preferences
        $preferences = $user->notification_preferences ?? [];
        $this->notifyOnScanComplete = $preferences['scan_complete'] ?? true;
        $this->notifyOnIssuesFound = $preferences['issues_found'] ?? true;
        $this->notifyOnWeeklyDigest = $preferences['weekly_digest'] ?? false;
        $this->notifyOnBillingAlerts = $preferences['billing_alerts'] ?? true;
    }

    public function updateProfile(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
        ]);

        $user->update($validated);

        session()->flash('success', 'Profile updated successfully.');
    }

    public function updatePassword(): void
    {
        $user = Auth::user();

        $this->validate([
            'currentPassword' => ['required'],
            'newPassword' => ['required', 'string', Password::defaults()],
            'newPasswordConfirmation' => ['required', 'same:newPassword'],
        ], [
            'newPasswordConfirmation.same' => 'The password confirmation does not match.',
        ]);

        if (! Hash::check($this->currentPassword, $user->password)) {
            $this->addError('currentPassword', 'The current password is incorrect.');

            return;
        }

        $user->update([
            'password' => Hash::make($this->newPassword),
        ]);

        $this->reset(['currentPassword', 'newPassword', 'newPasswordConfirmation']);

        session()->flash('password_success', 'Password updated successfully.');
    }

    public function updateNotifications(): void
    {
        $user = Auth::user();

        $user->update([
            'notification_preferences' => [
                'scan_complete' => $this->notifyOnScanComplete,
                'issues_found' => $this->notifyOnIssuesFound,
                'weekly_digest' => $this->notifyOnWeeklyDigest,
                'billing_alerts' => $this->notifyOnBillingAlerts,
            ],
        ]);

        session()->flash('notifications_success', 'Notification preferences updated.');
    }

    public function confirmDelete(): void
    {
        $this->showDeleteModal = true;
        $this->deleteConfirmation = '';
    }

    public function deleteAccount(): void
    {
        $user = Auth::user();

        if ($this->deleteConfirmation !== $user->email) {
            $this->addError('deleteConfirmation', 'Please type your email address to confirm.');

            return;
        }

        // Check if user is the organization owner
        if ($user->hasRole('Owner')) {
            $this->addError('deleteConfirmation', 'Organization owners cannot delete their account. Please transfer ownership first or delete the organization.');
            $this->showDeleteModal = false;

            return;
        }

        // Delete the user
        $user->delete();

        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        redirect()->route('home');
    }

    public function render(): View
    {
        $user = Auth::user();

        return view('livewire.profile.profile-edit', [
            'user' => $user,
        ]);
    }
}
