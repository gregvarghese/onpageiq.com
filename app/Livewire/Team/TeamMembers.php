<?php

namespace App\Livewire\Team;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class TeamMembers extends Component
{
    use WithPagination;

    public string $search = '';

    public string $roleFilter = '';

    public bool $showInviteModal = false;

    public string $inviteEmail = '';

    public string $inviteRole = 'Member';

    public bool $showRoleModal = false;

    public ?int $memberToUpdate = null;

    public string $newRole = '';

    public bool $showRemoveModal = false;

    public ?int $memberToRemove = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedRoleFilter(): void
    {
        $this->resetPage();
    }

    public function openInviteModal(): void
    {
        $this->reset(['inviteEmail', 'inviteRole']);
        $this->inviteRole = 'Member';
        $this->showInviteModal = true;
    }

    public function inviteMember(): void
    {
        $user = Auth::user();
        $organization = $user->organization;

        $this->validate([
            'inviteEmail' => [
                'required',
                'email',
                Rule::unique('users', 'email')->where('organization_id', $organization->id),
            ],
            'inviteRole' => ['required', Rule::in(Role::organizationRoles())],
        ], [
            'inviteEmail.unique' => 'This email is already a member of your organization.',
        ]);

        // Create the user with a temporary password
        $temporaryPassword = Str::random(16);

        $newUser = User::create([
            'name' => Str::before($this->inviteEmail, '@'),
            'email' => $this->inviteEmail,
            'password' => Hash::make($temporaryPassword),
            'organization_id' => $organization->id,
        ]);

        $newUser->assignRole($this->inviteRole);

        // In a real app, send an invitation email here
        // Mail::to($this->inviteEmail)->send(new TeamInvitationMail($newUser, $temporaryPassword));

        $this->showInviteModal = false;
        $this->reset(['inviteEmail', 'inviteRole']);

        session()->flash('success', "Invitation sent to {$this->inviteEmail}");
    }

    public function openRoleModal(int $userId): void
    {
        $user = Auth::user();
        $member = User::where('id', $userId)
            ->where('organization_id', $user->organization_id)
            ->first();

        if (! $member) {
            return;
        }

        $this->memberToUpdate = $userId;
        $this->newRole = $member->roles->first()?->name ?? 'Member';
        $this->showRoleModal = true;
    }

    public function updateRole(): void
    {
        $user = Auth::user();

        $this->validate([
            'newRole' => ['required', Rule::in(Role::organizationRoles())],
        ]);

        $member = User::where('id', $this->memberToUpdate)
            ->where('organization_id', $user->organization_id)
            ->first();

        if (! $member) {
            return;
        }

        // Prevent changing own role or owner's role if not owner
        if ($member->id === $user->id) {
            session()->flash('error', 'You cannot change your own role.');
            $this->showRoleModal = false;

            return;
        }

        // Sync roles (remove old, add new)
        $member->syncRoles([$this->newRole]);

        $this->showRoleModal = false;
        $this->reset(['memberToUpdate', 'newRole']);

        session()->flash('success', 'Member role updated successfully.');
    }

    public function confirmRemove(int $userId): void
    {
        $this->memberToRemove = $userId;
        $this->showRemoveModal = true;
    }

    public function removeMember(): void
    {
        $user = Auth::user();

        $member = User::where('id', $this->memberToRemove)
            ->where('organization_id', $user->organization_id)
            ->first();

        if (! $member) {
            $this->showRemoveModal = false;

            return;
        }

        // Prevent removing self
        if ($member->id === $user->id) {
            session()->flash('error', 'You cannot remove yourself from the organization.');
            $this->showRemoveModal = false;

            return;
        }

        // Prevent removing the owner
        if ($member->hasRole(Role::Owner->value)) {
            session()->flash('error', 'You cannot remove the organization owner.');
            $this->showRemoveModal = false;

            return;
        }

        $member->delete();

        $this->showRemoveModal = false;
        $this->memberToRemove = null;

        session()->flash('success', 'Member removed from organization.');
    }

    /**
     * @return array<string>
     */
    public function getAvailableRolesProperty(): array
    {
        return Role::organizationRoles();
    }

    public function render(): View
    {
        $user = Auth::user();
        $organization = $user->organization;

        $members = User::query()
            ->where('organization_id', $organization->id)
            ->with('roles', 'department')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->roleFilter, function ($query) {
                $query->whereHas('roles', function ($q) {
                    $q->where('name', $this->roleFilter);
                });
            })
            ->orderBy('created_at', 'asc')
            ->paginate(10);

        return view('livewire.team.team-members', [
            'members' => $members,
            'organization' => $organization,
            'currentUser' => $user,
        ]);
    }
}
