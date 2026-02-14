<?php

namespace App\Livewire\Settings;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class SettingsIndex extends Component
{
    public string $organizationName = '';

    public string $timezone = 'UTC';

    public string $defaultLanguage = 'en';

    public bool $showDeleteOrgModal = false;

    public string $deleteConfirmation = '';

    /**
     * @var array<string, string>
     */
    public array $timezones = [
        'UTC' => 'UTC',
        'America/New_York' => 'Eastern Time (US & Canada)',
        'America/Chicago' => 'Central Time (US & Canada)',
        'America/Denver' => 'Mountain Time (US & Canada)',
        'America/Los_Angeles' => 'Pacific Time (US & Canada)',
        'Europe/London' => 'London',
        'Europe/Paris' => 'Paris',
        'Europe/Berlin' => 'Berlin',
        'Asia/Tokyo' => 'Tokyo',
        'Asia/Shanghai' => 'Shanghai',
        'Australia/Sydney' => 'Sydney',
    ];

    /**
     * @var array<string, string>
     */
    public array $languages = [
        'en' => 'English',
        'en-GB' => 'English (UK)',
        'en-AU' => 'English (Australia)',
        'es' => 'Spanish',
        'fr' => 'French',
        'de' => 'German',
        'it' => 'Italian',
        'pt' => 'Portuguese',
        'nl' => 'Dutch',
    ];

    public function mount(): void
    {
        $user = Auth::user();
        $organization = $user->organization;

        $this->organizationName = $organization->name;

        $settings = $organization->settings ?? [];
        $this->timezone = $settings['timezone'] ?? 'UTC';
        $this->defaultLanguage = $settings['default_language'] ?? 'en';
    }

    public function updateOrganization(): void
    {
        $user = Auth::user();
        $organization = $user->organization;

        $this->validate([
            'organizationName' => ['required', 'string', 'max:255'],
            'timezone' => ['required', 'string'],
            'defaultLanguage' => ['required', 'string'],
        ]);

        $organization->update([
            'name' => $this->organizationName,
            'settings' => array_merge($organization->settings ?? [], [
                'timezone' => $this->timezone,
                'default_language' => $this->defaultLanguage,
            ]),
        ]);

        session()->flash('success', 'Organization settings updated successfully.');
    }

    public function confirmDeleteOrganization(): void
    {
        $this->showDeleteOrgModal = true;
        $this->deleteConfirmation = '';
    }

    public function deleteOrganization(): void
    {
        $user = Auth::user();
        $organization = $user->organization;

        if ($this->deleteConfirmation !== $organization->name) {
            $this->addError('deleteConfirmation', 'Please type the organization name to confirm.');

            return;
        }

        // Check if user is the owner
        if (! $user->hasRole('Owner')) {
            $this->addError('deleteConfirmation', 'Only the organization owner can delete the organization.');
            $this->showDeleteOrgModal = false;

            return;
        }

        // Delete all organization data
        // Projects, scans, URLs, etc. should cascade delete via foreign keys
        $organization->users()->delete();
        $organization->delete();

        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        redirect()->route('home');
    }

    public function render(): View
    {
        $user = Auth::user();
        $organization = $user->organization;

        return view('livewire.settings.settings-index', [
            'organization' => $organization,
            'isOwner' => $user->hasRole('Owner'),
        ]);
    }
}
