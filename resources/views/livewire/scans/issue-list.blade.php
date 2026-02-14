<div class="space-y-4">
    {{-- Flash Messages --}}
    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/50 p-3">
            <p class="text-sm text-green-800 dark:text-green-200">{{ session('success') }}</p>
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/50 p-3">
            <p class="text-sm text-red-800 dark:text-red-200">{{ session('error') }}</p>
        </div>
    @endif

    <!-- Filter Buttons -->
    <div class="flex flex-wrap items-center gap-3 mb-4">
        @php
            $categoryCounts = $issues->groupBy('category')->map->count();
            $severityCounts = $issues->groupBy('severity')->map->count();
        @endphp

        @if($categoryCounts->isNotEmpty() || $severityCounts->isNotEmpty())
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Filter:</span>

            @foreach($categoryCounts as $cat => $count)
                <button
                    wire:click="filterByCategory('{{ $cat }}')"
                    @class([
                        'inline-flex items-center gap-x-1.5 rounded-full px-3 py-1.5 text-xs font-medium transition-colors',
                        'bg-primary-100 text-primary-700 dark:bg-primary-900/50 dark:text-primary-300' => $category === $cat,
                        'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600' => $category !== $cat,
                    ])
                >
                    {{ ucfirst($cat) }} ({{ $count }})
                </button>
            @endforeach

            <div class="h-4 w-px bg-gray-300 dark:bg-gray-600"></div>

            @foreach($severityCounts as $sev => $count)
                <button
                    wire:click="filterBySeverity('{{ $sev }}')"
                    @class([
                        'inline-flex items-center gap-x-1.5 rounded-full px-3 py-1.5 text-xs font-medium transition-colors',
                        'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300' => $sev === 'error' && $severity === $sev,
                        'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/50 dark:text-yellow-300' => $sev === 'warning' && $severity === $sev,
                        'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300' => $sev === 'suggestion' && $severity === $sev,
                        'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600' => $severity !== $sev,
                    ])
                >
                    {{ ucfirst($sev) }} ({{ $count }})
                </button>
            @endforeach

            @if($category || $severity)
                <button
                    wire:click="clearFilters"
                    class="text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 underline"
                >
                    Clear
                </button>
            @endif
        @endif
    </div>

    <!-- Issues -->
    @forelse($issues as $issue)
        <div
            wire:key="issue-{{ $issue->id }}"
            class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4"
        >
            <div class="flex items-start gap-x-4">
                <!-- Severity Icon -->
                <div @class([
                    'flex size-10 shrink-0 items-center justify-center rounded-lg',
                    'bg-red-100 dark:bg-red-900/30' => $issue->severity === 'error',
                    'bg-yellow-100 dark:bg-yellow-900/30' => $issue->severity === 'warning',
                    'bg-blue-100 dark:bg-blue-900/30' => $issue->severity === 'suggestion',
                ])>
                    @if($issue->severity === 'error')
                        <x-ui.icon name="x-circle" class="size-5 text-red-600 dark:text-red-400" />
                    @elseif($issue->severity === 'warning')
                        <x-ui.icon name="exclamation-triangle" class="size-5 text-yellow-600 dark:text-yellow-400" />
                    @else
                        <x-ui.icon name="light-bulb" class="size-5 text-blue-600 dark:text-blue-400" />
                    @endif
                </div>

                <div class="min-w-0 flex-1">
                    <div class="flex items-center justify-between gap-x-2">
                        <div class="flex items-center gap-x-2">
                            <span @class([
                                'inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset',
                                'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-900/30 dark:text-red-400 dark:ring-red-500/30' => $issue->severity === 'error',
                                'bg-yellow-50 text-yellow-700 ring-yellow-600/20 dark:bg-yellow-900/30 dark:text-yellow-400 dark:ring-yellow-500/30' => $issue->severity === 'warning',
                                'bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-900/30 dark:text-blue-400 dark:ring-blue-500/30' => $issue->severity === 'suggestion',
                            ])>
                                {{ ucfirst($issue->severity) }}
                            </span>
                            <span class="inline-flex items-center rounded-md bg-gray-50 dark:bg-gray-700 px-2 py-1 text-xs font-medium text-gray-600 dark:text-gray-400 ring-1 ring-inset ring-gray-500/10 dark:ring-gray-500/30">
                                {{ ucfirst($issue->category) }}
                            </span>
                        </div>

                        {{-- Add to Dictionary button for spelling issues --}}
                        @if($issue->category === 'spelling' && ($canAddToProjectDictionary || $canAddToOrganizationDictionary))
                            <button
                                wire:click="openAddToDictionaryModal('{{ addslashes($issue->text_excerpt) }}')"
                                class="inline-flex items-center gap-x-1 rounded-md px-2 py-1 text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition-colors"
                                title="Add to dictionary"
                            >
                                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                <span class="hidden sm:inline">Add to dictionary</span>
                            </button>
                        @endif
                    </div>

                    <!-- Issue Text -->
                    <div class="mt-2">
                        <p class="text-sm text-gray-900 dark:text-white">
                            <span class="font-medium">Found:</span>
                            <code class="ml-1 rounded bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 text-red-600 dark:text-red-400">{{ $issue->text_excerpt }}</code>
                        </p>
                    </div>

                    <!-- Suggestion -->
                    @if($issue->suggestion)
                        <div class="mt-2">
                            <p class="text-sm text-gray-700 dark:text-gray-300">
                                <span class="font-medium text-green-600 dark:text-green-400">Suggestion:</span>
                                <span class="ml-1">{{ $issue->suggestion }}</span>
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="text-center py-8">
            <x-ui.icon name="check-circle" class="mx-auto size-10 text-green-500" />
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                @if($category || $severity)
                    No issues match your filters.
                @else
                    No issues found.
                @endif
            </p>
        </div>
    @endforelse

    {{-- Add to Dictionary Modal --}}
    @if ($showAddToDictionaryModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="$set('showAddToDictionaryModal', false)"></div>
                <div class="inline-block transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 px-4 pb-4 pt-5 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6 sm:align-middle">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Add to Dictionary</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Add "<strong class="text-gray-900 dark:text-white">{{ $wordToAdd }}</strong>" to your dictionary. It will not be flagged in future scans.
                    </p>

                    <div class="mt-4 space-y-3">
                        @if ($canAddToProjectDictionary)
                            <label class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer {{ $addToScope === 'project' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30' : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50' }}">
                                <input
                                    type="radio"
                                    wire:model="addToScope"
                                    value="project"
                                    class="mt-0.5 text-indigo-600 focus:ring-indigo-500"
                                >
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">Project Dictionary</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Only ignored for this project</p>
                                </div>
                            </label>
                        @endif

                        @if ($canAddToOrganizationDictionary)
                            <label class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer {{ $addToScope === 'organization' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30' : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50' }}">
                                <input
                                    type="radio"
                                    wire:model="addToScope"
                                    value="organization"
                                    class="mt-0.5 text-indigo-600 focus:ring-indigo-500"
                                >
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">Organization Dictionary</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Ignored across all projects in your organization</p>
                                </div>
                            </label>
                        @endif
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button wire:click="$set('showAddToDictionaryModal', false)" class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600">
                            Cancel
                        </button>
                        <button wire:click="addToDictionary" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                            Add to Dictionary
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
