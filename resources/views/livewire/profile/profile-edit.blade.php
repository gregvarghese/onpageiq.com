<div>
    <x-slot name="header">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Profile Settings</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage your account settings and preferences</p>
        </div>
    </x-slot>

    <div class="space-y-8">
        {{-- Profile Information --}}
        <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white">Profile Information</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Update your account's profile information and email address.</p>
            </div>
            <div class="px-6 py-4">
                @if (session('success'))
                    <div class="mb-4 rounded-lg border border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/50 p-4">
                        <div class="flex">
                            <x-ui.icon name="check-circle" class="size-5 text-green-400" />
                            <p class="ml-3 text-sm font-medium text-green-800 dark:text-green-200">
                                {{ session('success') }}
                            </p>
                        </div>
                    </div>
                @endif

                <form wire:submit="updateProfile" class="space-y-4">
                    <div class="flex items-center gap-6">
                        <div class="size-20 flex-shrink-0">
                            @if($user->avatar)
                                <img class="size-20 rounded-full" src="{{ $user->avatar }}" alt="{{ $user->name }}">
                            @else
                                <div class="size-20 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center">
                                    <span class="text-2xl font-medium text-primary-600 dark:text-primary-400">
                                        {{ strtoupper(substr($user->name, 0, 2)) }}
                                    </span>
                                </div>
                            @endif
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            @if($user->provider)
                                <p>Avatar synced from {{ ucfirst($user->provider) }}</p>
                            @else
                                <p>Avatar is generated from your initials</p>
                            @endif
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                            <input
                                type="text"
                                id="name"
                                wire:model="name"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 px-3 py-2 sm:text-sm"
                            >
                            @error('name')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                            <input
                                type="email"
                                id="email"
                                wire:model="email"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 px-3 py-2 sm:text-sm"
                            >
                            @error('email')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Update Password --}}
        <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white">Update Password</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Ensure your account is using a strong password to stay secure.</p>
            </div>
            <div class="px-6 py-4">
                @if (session('password_success'))
                    <div class="mb-4 rounded-lg border border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/50 p-4">
                        <div class="flex">
                            <x-ui.icon name="check-circle" class="size-5 text-green-400" />
                            <p class="ml-3 text-sm font-medium text-green-800 dark:text-green-200">
                                {{ session('password_success') }}
                            </p>
                        </div>
                    </div>
                @endif

                @if($user->provider)
                    <div class="rounded-lg border border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-900/30 p-4">
                        <div class="flex">
                            <x-ui.icon name="information-circle" class="size-5 text-amber-400" />
                            <p class="ml-3 text-sm text-amber-800 dark:text-amber-200">
                                You signed in with {{ ucfirst($user->provider) }}. Password management is handled by your provider.
                            </p>
                        </div>
                    </div>
                @else
                    <form wire:submit="updatePassword" class="space-y-4">
                        <div>
                            <label for="current-password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Current Password</label>
                            <input
                                type="password"
                                id="current-password"
                                wire:model="currentPassword"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 px-3 py-2 sm:text-sm"
                            >
                            @error('currentPassword')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="new-password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">New Password</label>
                                <input
                                    type="password"
                                    id="new-password"
                                    wire:model="newPassword"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 px-3 py-2 sm:text-sm"
                                >
                                @error('newPassword')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="new-password-confirm" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Confirm Password</label>
                                <input
                                    type="password"
                                    id="new-password-confirm"
                                    wire:model="newPasswordConfirmation"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 px-3 py-2 sm:text-sm"
                                >
                                @error('newPasswordConfirmation')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" class="rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">
                                Update Password
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>

        {{-- Notification Preferences --}}
        <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white">Notification Preferences</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Choose what notifications you want to receive.</p>
            </div>
            <div class="px-6 py-4">
                @if (session('notifications_success'))
                    <div class="mb-4 rounded-lg border border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/50 p-4">
                        <div class="flex">
                            <x-ui.icon name="check-circle" class="size-5 text-green-400" />
                            <p class="ml-3 text-sm font-medium text-green-800 dark:text-green-200">
                                {{ session('notifications_success') }}
                            </p>
                        </div>
                    </div>
                @endif

                <form wire:submit="updateNotifications" class="space-y-4">
                    <div class="space-y-4">
                        <label class="flex items-start gap-3">
                            <input
                                type="checkbox"
                                wire:model="notifyOnScanComplete"
                                class="mt-1 rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500"
                            >
                            <div>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">Scan Complete</span>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Get notified when a scan finishes processing.</p>
                            </div>
                        </label>

                        <label class="flex items-start gap-3">
                            <input
                                type="checkbox"
                                wire:model="notifyOnIssuesFound"
                                class="mt-1 rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500"
                            >
                            <div>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">Issues Found</span>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Get notified when new issues are found in your scans.</p>
                            </div>
                        </label>

                        <label class="flex items-start gap-3">
                            <input
                                type="checkbox"
                                wire:model="notifyOnWeeklyDigest"
                                class="mt-1 rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500"
                            >
                            <div>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">Weekly Digest</span>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Receive a weekly summary of your projects and scans.</p>
                            </div>
                        </label>

                        <label class="flex items-start gap-3">
                            <input
                                type="checkbox"
                                wire:model="notifyOnBillingAlerts"
                                class="mt-1 rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500"
                            >
                            <div>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">Billing Alerts</span>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Get notified about billing events and low credit balance.</p>
                            </div>
                        </label>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">
                            Save Preferences
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Connected Accounts --}}
        @if($user->provider)
            <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">Connected Accounts</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage your connected social accounts.</p>
                </div>
                <div class="px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            @if($user->provider === 'google')
                                <div class="size-10 rounded-full bg-white flex items-center justify-center border border-gray-200 dark:border-gray-700">
                                    <svg class="size-5" viewBox="0 0 24 24">
                                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                                    </svg>
                                </div>
                            @elseif($user->provider === 'microsoft')
                                <div class="size-10 rounded-full bg-white flex items-center justify-center border border-gray-200 dark:border-gray-700">
                                    <svg class="size-5" viewBox="0 0 21 21">
                                        <rect x="1" y="1" width="9" height="9" fill="#f25022"/>
                                        <rect x="1" y="11" width="9" height="9" fill="#00a4ef"/>
                                        <rect x="11" y="1" width="9" height="9" fill="#7fba00"/>
                                        <rect x="11" y="11" width="9" height="9" fill="#ffb900"/>
                                    </svg>
                                </div>
                            @endif
                            <div>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ ucfirst($user->provider) }}</span>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Connected</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center rounded-full bg-green-100 dark:bg-green-900 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:text-green-200">
                            Active
                        </span>
                    </div>
                </div>
            </div>
        @endif

        {{-- Delete Account --}}
        <div class="overflow-hidden rounded-lg border border-red-200 dark:border-red-800 bg-white dark:bg-gray-800 shadow-sm">
            <div class="px-6 py-4 border-b border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20">
                <h2 class="text-lg font-medium text-red-800 dark:text-red-200">Danger Zone</h2>
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">Irreversible and destructive actions.</p>
            </div>
            <div class="px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-medium text-gray-900 dark:text-white">Delete Account</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Permanently delete your account and all associated data.</p>
                    </div>
                    <button
                        wire:click="confirmDelete"
                        class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700"
                    >
                        Delete Account
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Delete Account Modal --}}
    @if ($showDeleteModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75 transition-opacity" wire:click="$set('showDeleteModal', false)"></div>
                <span class="hidden sm:inline-block sm:h-screen sm:align-middle">&#8203;</span>
                <div class="inline-block transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 px-4 pb-4 pt-5 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6 sm:align-middle">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex size-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900 sm:mx-0 sm:size-10">
                            <x-ui.icon name="exclamation-triangle" class="size-6 text-red-600 dark:text-red-400" />
                        </div>
                        <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Delete Account</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    This action cannot be undone. All of your data will be permanently deleted.
                                    Please type your email address <strong>{{ $user->email }}</strong> to confirm.
                                </p>
                            </div>
                            <div class="mt-4">
                                <input
                                    type="text"
                                    wire:model="deleteConfirmation"
                                    class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-red-500 focus:ring-red-500 px-3 py-2 sm:text-sm"
                                    placeholder="Type your email to confirm"
                                >
                                @error('deleteConfirmation')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end gap-3">
                        <button wire:click="$set('showDeleteModal', false)" class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600">
                            Cancel
                        </button>
                        <button wire:click="deleteAccount" class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">
                            Delete My Account
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
