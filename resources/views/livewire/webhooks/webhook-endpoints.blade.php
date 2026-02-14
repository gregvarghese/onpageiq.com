<div>
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Webhook Endpoints</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Configure webhooks to receive real-time notifications.
            </p>
        </div>
        <button
            wire:click="$set('showCreateModal', true)"
            class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
        >
            Add Endpoint
        </button>
    </div>

    {{-- Endpoints List --}}
    <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">URL</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Events</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Deliveries</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($endpoints as $endpoint)
                    <tr>
                        <td class="px-6 py-4">
                            <div class="max-w-xs truncate text-sm font-medium text-gray-900 dark:text-white">{{ $endpoint->url }}</div>
                            @if ($endpoint->description)
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $endpoint->description }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-wrap gap-1">
                                @foreach ($endpoint->events as $event)
                                    <span class="inline-flex rounded-full bg-gray-100 dark:bg-gray-700 px-2 py-0.5 text-xs font-medium text-gray-700 dark:text-gray-300">
                                        {{ $event }}
                                    </span>
                                @endforeach
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <button wire:click="toggleActive({{ $endpoint->id }})" class="inline-flex items-center">
                                @if ($endpoint->is_active)
                                    <span class="inline-flex items-center rounded-full bg-green-100 dark:bg-green-900 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:text-green-200">
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-700 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:text-gray-200">
                                        Inactive
                                    </span>
                                @endif
                            </button>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                            <a href="{{ route('api.webhooks.deliveries', $endpoint) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                {{ number_format($endpoint->deliveries_count) }} deliveries
                            </a>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                            <button wire:click="editEndpoint({{ $endpoint->id }})" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 mr-3">
                                Edit
                            </button>
                            <button wire:click="confirmDelete({{ $endpoint->id }})" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">
                                Delete
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                            No webhook endpoints configured yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $endpoints->links() }}
    </div>

    {{-- Create/Edit Modal --}}
    @if ($showCreateModal || $showEditModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="$set('{{ $showCreateModal ? 'showCreateModal' : 'showEditModal' }}', false)"></div>
                <div class="inline-block transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 px-4 pb-4 pt-5 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6 sm:align-middle">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                        {{ $showCreateModal ? 'Add Webhook Endpoint' : 'Edit Webhook Endpoint' }}
                    </h3>

                    <div class="mt-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">URL</label>
                            <input type="url" wire:model="url" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 sm:text-sm" placeholder="https://example.com/webhook">
                            @error('url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description (optional)</label>
                            <input type="text" wire:model="description" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 sm:text-sm" placeholder="Production webhook">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Events</label>
                            <div class="space-y-2">
                                @foreach ($availableEvents as $event)
                                    <label class="flex items-center">
                                        <input type="checkbox" wire:model="events" value="{{ $event }}" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $event }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('events') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" wire:model="isActive" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Active</span>
                            </label>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button wire:click="$set('{{ $showCreateModal ? 'showCreateModal' : 'showEditModal' }}', false)" class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600">
                            Cancel
                        </button>
                        <button wire:click="{{ $showCreateModal ? 'createEndpoint' : 'updateEndpoint' }}" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                            {{ $showCreateModal ? 'Create' : 'Save' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Delete Modal --}}
    @if ($showDeleteModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="$set('showDeleteModal', false)"></div>
                <div class="inline-block transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 px-4 pb-4 pt-5 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6 sm:align-middle">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Delete Webhook Endpoint</h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Are you sure you want to delete this webhook endpoint? All delivery history will be permanently removed.
                    </p>
                    <div class="mt-6 flex justify-end gap-3">
                        <button wire:click="$set('showDeleteModal', false)" class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600">
                            Cancel
                        </button>
                        <button wire:click="deleteEndpoint" class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
