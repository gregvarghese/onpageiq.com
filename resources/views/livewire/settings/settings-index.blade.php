<div>
    <x-slot name="header">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Settings</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage your organization settings and preferences</p>
        </div>
    </x-slot>

    <div class="space-y-8">
        {{-- Quick Links --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <a href="{{ route('settings.dictionary') }}" class="group relative flex flex-col rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm hover:shadow-md hover:border-primary-300 dark:hover:border-primary-700 transition-all duration-200">
                <div class="flex items-center gap-3">
                    <div class="flex size-10 items-center justify-center rounded-lg bg-primary-50 dark:bg-primary-900/30">
                        <x-ui.icon name="book-open" class="size-5 text-primary-600 dark:text-primary-400" />
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400">Dictionary</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Custom words</p>
                    </div>
                </div>
            </a>

            <a href="{{ route('api.tokens') }}" class="group relative flex flex-col rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm hover:shadow-md hover:border-primary-300 dark:hover:border-primary-700 transition-all duration-200">
                <div class="flex items-center gap-3">
                    <div class="flex size-10 items-center justify-center rounded-lg bg-primary-50 dark:bg-primary-900/30">
                        <x-ui.icon name="key" class="size-5 text-primary-600 dark:text-primary-400" />
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400">API Tokens</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Manage access</p>
                    </div>
                </div>
            </a>

            <a href="{{ route('api.webhooks') }}" class="group relative flex flex-col rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm hover:shadow-md hover:border-primary-300 dark:hover:border-primary-700 transition-all duration-200">
                <div class="flex items-center gap-3">
                    <div class="flex size-10 items-center justify-center rounded-lg bg-primary-50 dark:bg-primary-900/30">
                        <x-ui.icon name="link" class="size-5 text-primary-600 dark:text-primary-400" />
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400">Webhooks</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Event notifications</p>
                    </div>
                </div>
            </a>

            <a href="{{ route('billing.index') }}" class="group relative flex flex-col rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm hover:shadow-md hover:border-primary-300 dark:hover:border-primary-700 transition-all duration-200">
                <div class="flex items-center gap-3">
                    <div class="flex size-10 items-center justify-center rounded-lg bg-primary-50 dark:bg-primary-900/30">
                        <x-ui.icon name="credit-card" class="size-5 text-primary-600 dark:text-primary-400" />
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400">Billing</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Subscription & credits</p>
                    </div>
                </div>
            </a>
        </div>

        {{-- Organization Settings --}}
        <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white">Organization Settings</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Configure your organization's general settings.</p>
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

                <form wire:submit="updateOrganization" class="space-y-4">
                    <div>
                        <label for="org-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Organization Name</label>
                        <input
                            type="text"
                            id="org-name"
                            wire:model="organizationName"
                            class="mt-1 block w-full max-w-md rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 px-3 py-2 sm:text-sm"
                        >
                        @error('organizationName')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 max-w-2xl">
                        <div>
                            <label for="timezone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Timezone</label>
                            <select
                                id="timezone"
                                wire:model="timezone"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 px-3 py-2 sm:text-sm"
                            >
                                @foreach($timezones as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('timezone')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="language" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Default Language</label>
                            <select
                                id="language"
                                wire:model="defaultLanguage"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 px-3 py-2 sm:text-sm"
                            >
                                @foreach($languages as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('defaultLanguage')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="flex justify-start pt-2">
                        <button type="submit" class="rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Subscription Info --}}
        <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white">Subscription</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Your current plan and usage.</p>
            </div>
            <div class="px-6 py-4">
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Current Plan</dt>
                        <dd class="mt-1 text-lg font-semibold text-gray-900 dark:text-white capitalize">{{ $organization->subscription_tier }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Credit Balance</dt>
                        <dd class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{{ number_format($organization->credit_balance) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Team Members</dt>
                        <dd class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{{ $organization->users()->count() }}</dd>
                    </div>
                </dl>
                <div class="mt-4">
                    <a href="{{ route('billing.index') }}" class="text-sm font-medium text-primary-600 dark:text-primary-400 hover:text-primary-500">
                        Manage subscription &rarr;
                    </a>
                </div>
            </div>
        </div>

        {{-- Danger Zone --}}
        @if($isOwner)
            <div class="overflow-hidden rounded-lg border border-red-200 dark:border-red-800 bg-white dark:bg-gray-800 shadow-sm">
                <div class="px-6 py-4 border-b border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20">
                    <h2 class="text-lg font-medium text-red-800 dark:text-red-200">Danger Zone</h2>
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">Irreversible and destructive actions.</p>
                </div>
                <div class="px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-medium text-gray-900 dark:text-white">Delete Organization</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Permanently delete this organization and all its data including projects, scans, and team members.</p>
                        </div>
                        <button
                            wire:click="confirmDeleteOrganization"
                            class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700"
                        >
                            Delete Organization
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Delete Organization Modal --}}
    @if ($showDeleteOrgModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75 transition-opacity" wire:click="$set('showDeleteOrgModal', false)"></div>
                <span class="hidden sm:inline-block sm:h-screen sm:align-middle">&#8203;</span>
                <div class="inline-block transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 px-4 pb-4 pt-5 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6 sm:align-middle">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex size-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900 sm:mx-0 sm:size-10">
                            <x-ui.icon name="exclamation-triangle" class="size-6 text-red-600 dark:text-red-400" />
                        </div>
                        <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Delete Organization</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    This action cannot be undone. All organization data including projects, scans, team members, and settings will be permanently deleted.
                                </p>
                                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                    Please type <strong>{{ $organization->name }}</strong> to confirm.
                                </p>
                            </div>
                            <div class="mt-4">
                                <input
                                    type="text"
                                    wire:model="deleteConfirmation"
                                    class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-red-500 focus:ring-red-500 px-3 py-2 sm:text-sm"
                                    placeholder="Type organization name to confirm"
                                >
                                @error('deleteConfirmation')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end gap-3">
                        <button wire:click="$set('showDeleteOrgModal', false)" class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600">
                            Cancel
                        </button>
                        <button wire:click="deleteOrganization" class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">
                            Delete Organization
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
