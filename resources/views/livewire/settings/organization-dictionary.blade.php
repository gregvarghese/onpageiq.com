<div>
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Organization Dictionary</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Add words that should be ignored during spell checking across all projects.
            </p>
        </div>
        <div class="flex gap-2">
            @if ($canBulkImport)
                <button
                    wire:click="$set('showBulkModal', true)"
                    class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600"
                >
                    Bulk Import
                </button>
            @endif
            <button
                wire:click="$set('showAddModal', true)"
                class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
            >
                Add Word
            </button>
        </div>
    </div>

    {{-- Usage Stats --}}
    <div class="mb-6 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    <span class="font-semibold text-gray-900 dark:text-white">{{ $currentCount }}</span>
                    @if ($limit !== null)
                        / {{ $limit }} words used
                    @else
                        words (unlimited)
                    @endif
                </div>
                @if ($limit !== null && $remaining !== null)
                    <div class="h-2 w-48 rounded-full bg-gray-200 dark:bg-gray-700">
                        <div
                            class="h-2 rounded-full {{ $remaining < 10 ? 'bg-red-500' : 'bg-indigo-600' }}"
                            style="width: {{ min(100, ($currentCount / $limit) * 100) }}%"
                        ></div>
                    </div>
                @endif
            </div>
            @if ($remaining !== null && $remaining < 50)
                <span class="text-sm text-amber-600 dark:text-amber-400">
                    {{ $remaining }} slots remaining
                </span>
            @endif
        </div>
    </div>

    {{-- Flash Messages --}}
    @if (session('success'))
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/50 p-4">
            <p class="text-sm text-green-800 dark:text-green-200">{{ session('success') }}</p>
        </div>
    @endif

    @if (session('error'))
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/50 p-4">
            <p class="text-sm text-red-800 dark:text-red-200">{{ session('error') }}</p>
        </div>
    @endif

    {{-- Search --}}
    <div class="mb-4">
        <input
            type="text"
            wire:model.live.debounce.300ms="search"
            placeholder="Search words..."
            class="w-full max-w-xs rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 sm:text-sm"
        >
    </div>

    {{-- Words List --}}
    <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Word</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Source</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Added By</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Added</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($words as $word)
                    <tr>
                        <td class="whitespace-nowrap px-6 py-4">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $word->word }}</span>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium
                                {{ $word->source === 'custom' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : '' }}
                                {{ $word->source === 'imported' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' : '' }}
                                {{ $word->source === 'scan_suggestion' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : '' }}
                            ">
                                {{ ucfirst(str_replace('_', ' ', $word->source)) }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                            {{ $word->addedBy?->name ?? 'System' }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                            {{ $word->created_at->format('M j, Y') }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                            <button
                                wire:click="confirmDelete({{ $word->id }})"
                                class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"
                            >
                                Remove
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                            @if ($search)
                                No words found matching "{{ $search }}".
                            @else
                                No words in your organization dictionary yet. Add words to ignore during spell checking.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if ($words->hasPages())
        <div class="mt-4">
            {{ $words->links() }}
        </div>
    @endif

    {{-- Add Word Modal --}}
    @if ($showAddModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="$set('showAddModal', false)"></div>
                <div class="inline-block transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 px-4 pb-4 pt-5 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6 sm:align-middle">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Add Word to Dictionary</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        This word will be ignored during spell checking across all projects.
                    </p>
                    <div class="mt-4">
                        <label for="new-word" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Word</label>
                        <input
                            type="text"
                            id="new-word"
                            wire:model="newWord"
                            wire:keydown.enter="addWord"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 sm:text-sm"
                            placeholder="e.g., WarrCloud"
                            autofocus
                        >
                        @error('newWord')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="mt-6 flex justify-end gap-3">
                        <button wire:click="$set('showAddModal', false)" class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600">
                            Cancel
                        </button>
                        <button wire:click="addWord" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                            Add Word
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Bulk Import Modal --}}
    @if ($showBulkModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="$set('showBulkModal', false)"></div>
                <div class="inline-block transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 px-4 pb-4 pt-5 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6 sm:align-middle">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Bulk Import Words</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Enter words separated by commas or new lines.
                    </p>
                    <div class="mt-4">
                        <label for="bulk-words" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Words</label>
                        <textarea
                            id="bulk-words"
                            wire:model="bulkWords"
                            rows="8"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 sm:text-sm"
                            placeholder="WarrCloud&#10;SaaS&#10;onboarding&#10;webhook"
                        ></textarea>
                    </div>
                    <div class="mt-6 flex justify-end gap-3">
                        <button wire:click="$set('showBulkModal', false)" class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600">
                            Cancel
                        </button>
                        <button wire:click="bulkImport" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                            Import Words
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
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Remove Word</h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Are you sure you want to remove this word from the dictionary? It will be flagged during future spell checks.
                    </p>
                    <div class="mt-6 flex justify-end gap-3">
                        <button wire:click="$set('showDeleteModal', false)" class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600">
                            Cancel
                        </button>
                        <button wire:click="deleteWord" class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">
                            Remove Word
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
