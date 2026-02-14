<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Team Members</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage your organization's team members and their roles</p>
            </div>
            @if($organization->hasTeamFeatures())
                <button
                    wire:click="openInviteModal"
                    class="inline-flex items-center gap-x-2 rounded-md bg-primary-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"
                >
                    <x-ui.icon name="plus" class="size-5" />
                    Invite Member
                </button>
            @endif
        </div>
    </x-slot>

    {{-- Flash Messages --}}
    @if (session('success'))
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/50 p-4">
            <div class="flex">
                <x-ui.icon name="check-circle" class="size-5 text-green-400" />
                <p class="ml-3 text-sm font-medium text-green-800 dark:text-green-200">
                    {{ session('success') }}
                </p>
            </div>
        </div>
    @endif

    @if (session('error'))
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/50 p-4">
            <div class="flex">
                <x-ui.icon name="x-circle" class="size-5 text-red-400" />
                <p class="ml-3 text-sm font-medium text-red-800 dark:text-red-200">
                    {{ session('error') }}
                </p>
            </div>
        </div>
    @endif

    {{-- Team Features Gate --}}
    @if(!$organization->hasTeamFeatures())
        <div class="rounded-lg border border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-900/30 p-6 text-center">
            <x-ui.icon name="users" class="mx-auto size-12 text-amber-400" />
            <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">Team Features</h3>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                Upgrade to the Team or Enterprise plan to invite team members and manage roles.
            </p>
            <a
                href="{{ route('billing.index') }}"
                class="mt-4 inline-flex items-center gap-x-2 rounded-md bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-500"
            >
                Upgrade Plan
            </a>
        </div>
    @else
        {{-- Search and Filters --}}
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="relative flex-1 max-w-md">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <x-ui.icon name="magnifying-glass" class="size-5 text-gray-400" />
                </div>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search members..."
                    class="block w-full rounded-md border-0 py-2.5 pl-10 pr-3 text-gray-900 dark:text-white bg-white dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6"
                />
            </div>
            <div>
                <select
                    wire:model.live="roleFilter"
                    class="block rounded-md border-0 py-2.5 pl-3 pr-10 text-gray-900 dark:text-white bg-white dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-primary-600 sm:text-sm sm:leading-6"
                >
                    <option value="">All Roles</option>
                    @foreach($this->availableRoles as $role)
                        <option value="{{ $role }}">{{ $role }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Members Table --}}
        <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Member</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Role</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Department</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Joined</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($members as $member)
                        <tr wire:key="member-{{ $member->id }}">
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="flex items-center">
                                    <div class="size-10 flex-shrink-0">
                                        @if($member->avatar)
                                            <img class="size-10 rounded-full" src="{{ $member->avatar }}" alt="{{ $member->name }}">
                                        @else
                                            <div class="size-10 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center">
                                                <span class="text-sm font-medium text-primary-600 dark:text-primary-400">
                                                    {{ strtoupper(substr($member->name, 0, 2)) }}
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $member->name }}
                                            @if($member->id === $currentUser->id)
                                                <span class="ml-1 text-xs text-gray-500 dark:text-gray-400">(you)</span>
                                            @endif
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $member->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                @php $role = $member->roles->first()?->name ?? 'Member'; @endphp
                                <span @class([
                                    'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                                    'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' => $role === 'Owner',
                                    'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' => $role === 'Admin',
                                    'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' => $role === 'Manager',
                                    'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' => $role === 'Member',
                                    'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' => $role === 'Viewer',
                                ])>
                                    {{ $role }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                {{ $member->department?->name ?? '-' }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                {{ $member->created_at->format('M j, Y') }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                @if($member->id !== $currentUser->id && !$member->hasRole('Owner'))
                                    <button
                                        wire:click="openRoleModal({{ $member->id }})"
                                        class="text-primary-600 dark:text-primary-400 hover:text-primary-900 dark:hover:text-primary-300 mr-3"
                                    >
                                        Change Role
                                    </button>
                                    <button
                                        wire:click="confirmRemove({{ $member->id }})"
                                        class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"
                                    >
                                        Remove
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                No team members found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($members->hasPages())
            <div class="mt-6">
                {{ $members->links() }}
            </div>
        @endif
    @endif

    {{-- Invite Modal --}}
    @if ($showInviteModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75 transition-opacity" wire:click="$set('showInviteModal', false)"></div>
                <span class="hidden sm:inline-block sm:h-screen sm:align-middle">&#8203;</span>
                <div class="inline-block transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 px-4 pb-4 pt-5 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6 sm:align-middle">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Invite Team Member</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Send an invitation to join your organization.
                    </p>
                    <div class="mt-4 space-y-4">
                        <div>
                            <label for="invite-email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email Address</label>
                            <input
                                type="email"
                                id="invite-email"
                                wire:model="inviteEmail"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 px-3 py-2 sm:text-sm"
                                placeholder="colleague@example.com"
                            >
                            @error('inviteEmail')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="invite-role" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Role</label>
                            <select
                                id="invite-role"
                                wire:model="inviteRole"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 px-3 py-2 sm:text-sm"
                            >
                                @foreach($this->availableRoles as $role)
                                    @if($role !== 'Owner')
                                        <option value="{{ $role }}">{{ $role }}</option>
                                    @endif
                                @endforeach
                            </select>
                            @error('inviteRole')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end gap-3">
                        <button wire:click="$set('showInviteModal', false)" class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600">
                            Cancel
                        </button>
                        <button wire:click="inviteMember" class="rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">
                            Send Invitation
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Change Role Modal --}}
    @if ($showRoleModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75 transition-opacity" wire:click="$set('showRoleModal', false)"></div>
                <span class="hidden sm:inline-block sm:h-screen sm:align-middle">&#8203;</span>
                <div class="inline-block transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 px-4 pb-4 pt-5 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6 sm:align-middle">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Change Member Role</h3>
                    <div class="mt-4">
                        <label for="new-role" class="block text-sm font-medium text-gray-700 dark:text-gray-300">New Role</label>
                        <select
                            id="new-role"
                            wire:model="newRole"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 px-3 py-2 sm:text-sm"
                        >
                            @foreach($this->availableRoles as $role)
                                @if($role !== 'Owner')
                                    <option value="{{ $role }}">{{ $role }}</option>
                                @endif
                            @endforeach
                        </select>
                        @error('newRole')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="mt-6 flex justify-end gap-3">
                        <button wire:click="$set('showRoleModal', false)" class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600">
                            Cancel
                        </button>
                        <button wire:click="updateRole" class="rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">
                            Update Role
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Remove Member Modal --}}
    @if ($showRemoveModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75 transition-opacity" wire:click="$set('showRemoveModal', false)"></div>
                <span class="hidden sm:inline-block sm:h-screen sm:align-middle">&#8203;</span>
                <div class="inline-block transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 px-4 pb-4 pt-5 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6 sm:align-middle">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Remove Team Member</h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Are you sure you want to remove this member from your organization? They will lose access to all projects and data.
                    </p>
                    <div class="mt-6 flex justify-end gap-3">
                        <button wire:click="$set('showRemoveModal', false)" class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600">
                            Cancel
                        </button>
                        <button wire:click="removeMember" class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">
                            Remove Member
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
