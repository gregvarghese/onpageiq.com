<div>
    <!-- Toggle Button -->
    <button
        type="button"
        wire:click="$set('showPanel', true)"
        class="inline-flex items-center gap-x-2 rounded-md bg-white dark:bg-gray-800 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700"
    >
        <x-ui.icon name="book-open" class="size-4" />
        Dictionary
        @php $stats = $this->wordStats; @endphp
        <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-700 px-2 py-0.5 text-xs font-medium text-gray-600 dark:text-gray-400">
            {{ $stats['current'] }}@if($stats['limit'])/{{ $stats['limit'] }}@endif
        </span>
    </button>

    <!-- Slide-out Panel -->
    @if($showPanel)
        <div
            class="fixed inset-0 z-50 overflow-hidden"
            aria-labelledby="dictionary-panel-title"
            role="dialog"
            aria-modal="true"
        >
            <!-- Backdrop -->
            <div
                wire:click="closePanel"
                class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75 transition-opacity"
            ></div>

            <!-- Panel -->
            <div class="fixed inset-y-0 right-0 flex max-w-full pl-10">
                <div class="w-screen max-w-md">
                    <div class="flex h-full flex-col overflow-y-scroll bg-white dark:bg-gray-800 shadow-xl">
                        <!-- Header -->
                        <div class="bg-primary-600 px-4 py-6 sm:px-6">
                            <div class="flex items-center justify-between">
                                <h2 id="dictionary-panel-title" class="text-lg font-semibold text-white">
                                    Project Dictionary
                                </h2>
                                <button
                                    type="button"
                                    wire:click="closePanel"
                                    class="rounded-md text-white hover:text-gray-200 focus:outline-none focus:ring-2 focus:ring-white"
                                >
                                    <span class="sr-only">Close panel</span>
                                    <x-ui.icon name="x-mark" class="size-6" />
                                </button>
                            </div>
                            <p class="mt-1 text-sm text-primary-100">
                                Words added here will be ignored during spell checking.
                            </p>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 px-4 py-6 sm:px-6">
                            <!-- Word Stats -->
                            <div class="mb-6 flex items-center justify-between rounded-lg bg-gray-50 dark:bg-gray-700/50 px-4 py-3">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Dictionary Words</span>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $stats['current'] }}@if($stats['limit']) / {{ $stats['limit'] }}@endif
                                </span>
                            </div>

                            <!-- Add Word Form -->
                            <form wire:submit="addWord" class="mb-6">
                                <label for="newWord" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Add New Word
                                </label>
                                <div class="mt-2 flex gap-x-2">
                                    <input
                                        type="text"
                                        id="newWord"
                                        wire:model="newWord"
                                        placeholder="e.g., brandname"
                                        class="block w-full rounded-md border-0 px-3 py-2 text-gray-900 dark:text-white bg-white dark:bg-gray-700 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 placeholder:text-gray-400 focus:ring-2 focus:ring-primary-600 sm:text-sm"
                                    />
                                    <button
                                        type="submit"
                                        class="inline-flex items-center rounded-md bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500"
                                    >
                                        <x-ui.icon name="plus" class="size-4" />
                                    </button>
                                </div>
                                @error('newWord')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror

                                <!-- Scope Selection -->
                                <div class="mt-3 flex items-center gap-x-4">
                                    <label class="flex items-center gap-x-2">
                                        <input
                                            type="radio"
                                            wire:model="wordScope"
                                            value="project"
                                            class="size-4 border-gray-300 text-primary-600 focus:ring-primary-600"
                                        />
                                        <span class="text-sm text-gray-700 dark:text-gray-300">This project only</span>
                                    </label>
                                    <label class="flex items-center gap-x-2">
                                        <input
                                            type="radio"
                                            wire:model="wordScope"
                                            value="organization"
                                            class="size-4 border-gray-300 text-primary-600 focus:ring-primary-600"
                                        />
                                        <span class="text-sm text-gray-700 dark:text-gray-300">All projects</span>
                                    </label>
                                </div>
                            </form>

                            <!-- Search -->
                            <div class="mb-4">
                                <label for="searchQuery" class="sr-only">Search words</label>
                                <div class="relative">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <x-ui.icon name="magnifying-glass" class="size-4 text-gray-400" />
                                    </div>
                                    <input
                                        type="text"
                                        id="searchQuery"
                                        wire:model.live.debounce.300ms="searchQuery"
                                        placeholder="Search words..."
                                        class="block w-full rounded-md border-0 py-2 pl-10 pr-3 text-gray-900 dark:text-white bg-white dark:bg-gray-700 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 placeholder:text-gray-400 focus:ring-2 focus:ring-primary-600 sm:text-sm"
                                    />
                                </div>
                            </div>

                            <!-- Project Words -->
                            <div class="mb-6">
                                <h3 class="mb-3 flex items-center gap-x-2 text-sm font-medium text-gray-900 dark:text-white">
                                    <x-ui.icon name="folder" class="size-4 text-primary-600" />
                                    Project Words
                                    <span class="text-gray-500 dark:text-gray-400">({{ $this->projectWords->count() }})</span>
                                </h3>
                                @if($this->projectWords->isEmpty())
                                    <p class="text-sm text-gray-500 dark:text-gray-400 italic">No project-specific words yet.</p>
                                @else
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($this->projectWords as $word)
                                            <span
                                                wire:key="project-word-{{ $word->id }}"
                                                class="group inline-flex items-center gap-x-1 rounded-full bg-primary-100 dark:bg-primary-900/30 px-3 py-1 text-sm text-primary-700 dark:text-primary-400"
                                            >
                                                {{ $word->word }}
                                                <button
                                                    type="button"
                                                    wire:click="deleteWord({{ $word->id }})"
                                                    wire:confirm="Remove '{{ $word->word }}' from the dictionary?"
                                                    class="ml-1 rounded-full p-0.5 opacity-0 group-hover:opacity-100 hover:bg-primary-200 dark:hover:bg-primary-800 transition-opacity"
                                                >
                                                    <x-ui.icon name="x-mark" class="size-3" />
                                                </button>
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <!-- Organization Words -->
                            <div>
                                <h3 class="mb-3 flex items-center gap-x-2 text-sm font-medium text-gray-900 dark:text-white">
                                    <x-ui.icon name="building-office" class="size-4 text-gray-600 dark:text-gray-400" />
                                    Organization Words
                                    <span class="text-gray-500 dark:text-gray-400">({{ $this->organizationWords->count() }})</span>
                                </h3>
                                @if($this->organizationWords->isEmpty())
                                    <p class="text-sm text-gray-500 dark:text-gray-400 italic">No organization-wide words yet.</p>
                                @else
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($this->organizationWords as $word)
                                            <span
                                                wire:key="org-word-{{ $word->id }}"
                                                class="group inline-flex items-center gap-x-1 rounded-full bg-gray-100 dark:bg-gray-700 px-3 py-1 text-sm text-gray-700 dark:text-gray-300"
                                            >
                                                {{ $word->word }}
                                                <button
                                                    type="button"
                                                    wire:click="deleteWord({{ $word->id }})"
                                                    wire:confirm="Remove '{{ $word->word }}' from the dictionary?"
                                                    class="ml-1 rounded-full p-0.5 opacity-0 group-hover:opacity-100 hover:bg-gray-200 dark:hover:bg-gray-600 transition-opacity"
                                                >
                                                    <x-ui.icon name="x-mark" class="size-3" />
                                                </button>
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="border-t border-gray-200 dark:border-gray-700 px-4 py-4 sm:px-6">
                            <button
                                type="button"
                                wire:click="closePanel"
                                class="w-full rounded-md bg-white dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-white shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600"
                            >
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
