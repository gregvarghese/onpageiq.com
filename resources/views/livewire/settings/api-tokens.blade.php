<div>
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">API Tokens</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Manage your API tokens for programmatic access.
            </p>
        </div>
        <button
            wire:click="$set('showCreateModal', true)"
            class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
        >
            Create Token
        </button>
    </div>

    {{-- New Token Display --}}
    @if ($newToken)
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/50 p-4">
            <div class="flex">
                <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <div class="ml-3 flex-1">
                    <p class="text-sm font-medium text-green-800 dark:text-green-200">
                        Token created successfully! Copy it now - it won't be shown again.
                    </p>
                    <div class="mt-2 flex items-center gap-2">
                        <code class="flex-1 rounded bg-green-100 dark:bg-green-800 px-3 py-2 text-sm font-mono text-green-800 dark:text-green-200 break-all">
                            {{ $newToken }}
                        </code>
                        <button
                            onclick="navigator.clipboard.writeText('{{ $newToken }}')"
                            class="rounded bg-green-600 px-3 py-2 text-sm font-medium text-white hover:bg-green-700"
                        >
                            Copy
                        </button>
                    </div>
                    <button wire:click="dismissNewToken" class="mt-2 text-sm text-green-600 dark:text-green-400 hover:underline">
                        Dismiss
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Tokens List --}}
    <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Name</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Last Used</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Created</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($tokens as $token)
                    <tr>
                        <td class="whitespace-nowrap px-6 py-4">
                            <div class="flex items-center">
                                <svg class="mr-2 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                </svg>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $token->name }}</span>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                            {{ $token->last_used_at ? $token->last_used_at->diffForHumans() : 'Never' }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                            {{ $token->created_at->format('M j, Y') }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                            <button
                                wire:click="confirmDelete({{ $token->id }})"
                                class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"
                            >
                                Revoke
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                            No API tokens yet. Create one to get started.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($tokens->count() > 0)
        <div class="mt-4">
            <button
                wire:click="revokeAllTokens"
                wire:confirm="Are you sure you want to revoke all tokens?"
                class="text-sm text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"
            >
                Revoke all tokens
            </button>
        </div>
    @endif

    {{-- Create Modal --}}
    @if ($showCreateModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="$set('showCreateModal', false)"></div>
                <div class="inline-block transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 px-4 pb-4 pt-5 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6 sm:align-middle">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Create API Token</h3>
                    <div class="mt-4">
                        <label for="token-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Token Name</label>
                        <input
                            type="text"
                            id="token-name"
                            wire:model="tokenName"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            placeholder="e.g., CI/CD Pipeline"
                        >
                        @error('tokenName')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="mt-6 flex justify-end gap-3">
                        <button wire:click="$set('showCreateModal', false)" class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600">
                            Cancel
                        </button>
                        <button wire:click="createToken" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                            Create
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Delete Modal --}}
    @if ($showDeleteModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="$set('showDeleteModal', false)"></div>
                <div class="inline-block transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 px-4 pb-4 pt-5 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6 sm:align-middle">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Revoke Token</h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Are you sure you want to revoke this token? Any applications using it will no longer have access.
                    </p>
                    <div class="mt-6 flex justify-end gap-3">
                        <button wire:click="$set('showDeleteModal', false)" class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600">
                            Cancel
                        </button>
                        <button wire:click="deleteToken" class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">
                            Revoke Token
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
