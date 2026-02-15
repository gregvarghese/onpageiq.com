<div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700">
    {{-- Filter Header --}}
    <div class="px-4 py-3 flex items-center justify-between">
        <button
            wire:click="toggleExpanded"
            class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-200"
        >
            <svg class="w-5 h-5 transition-transform {{ $isExpanded ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
            Filters
            @if($hasActiveFilters)
                <span class="inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-blue-600 rounded-full">
                    !
                </span>
            @endif
        </button>

        @if($hasActiveFilters)
            <button
                wire:click="resetFilters"
                class="text-xs text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200"
            >
                Reset all
            </button>
        @endif
    </div>

    {{-- Filter Content --}}
    @if($isExpanded)
        <div class="px-4 pb-4 space-y-4 border-t border-gray-200 dark:border-gray-700 pt-4">
            {{-- Quick Filters --}}
            <div class="flex flex-wrap gap-2">
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input
                        type="checkbox"
                        wire:model.live="filters.showOrphans"
                        class="h-4 w-4 rounded border-gray-300 dark:border-gray-600 text-orange-600 focus:ring-orange-500"
                    >
                    <span class="text-sm text-gray-700 dark:text-gray-300">Orphan Pages</span>
                </label>

                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input
                        type="checkbox"
                        wire:model.live="filters.showDeep"
                        class="h-4 w-4 rounded border-gray-300 dark:border-gray-600 text-purple-600 focus:ring-purple-500"
                    >
                    <span class="text-sm text-gray-700 dark:text-gray-300">Deep Pages</span>
                </label>

                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input
                        type="checkbox"
                        wire:model.live="filters.showErrors"
                        class="h-4 w-4 rounded border-gray-300 dark:border-gray-600 text-red-600 focus:ring-red-500"
                    >
                    <span class="text-sm text-gray-700 dark:text-gray-300">Errors</span>
                </label>
            </div>

            {{-- Depth Range --}}
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="minDepth" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                        Min Depth
                    </label>
                    <input
                        type="number"
                        id="minDepth"
                        wire:model.live.debounce.300ms="filters.minDepth"
                        min="0"
                        max="20"
                        placeholder="0"
                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                    >
                </div>
                <div>
                    <label for="maxDepth" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                        Max Depth
                    </label>
                    <input
                        type="number"
                        id="maxDepth"
                        wire:model.live.debounce.300ms="filters.maxDepth"
                        min="0"
                        max="20"
                        placeholder="Any"
                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                    >
                </div>
            </div>

            {{-- Link Type --}}
            <div>
                <label for="linkType" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                    Link Type
                </label>
                <select
                    id="linkType"
                    wire:model.live="filters.linkType"
                    class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                >
                    <option value="">All Types</option>
                    @foreach($linkTypes as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Node Status --}}
            <div>
                <label for="status" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                    Page Status
                </label>
                <select
                    id="status"
                    wire:model.live="filters.status"
                    class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                >
                    <option value="">All Statuses</option>
                    @foreach($nodeStatuses as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- URL Pattern --}}
            <div>
                <label for="urlPattern" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                    URL Pattern
                </label>
                <input
                    type="text"
                    id="urlPattern"
                    wire:model.live.debounce.300ms="filters.urlPattern"
                    placeholder="/blog/*, /products/*"
                    class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                >
                <p class="mt-1 text-xs text-gray-400">Use * as wildcard</p>
            </div>

            {{-- Apply Button --}}
            <button
                wire:click="applyFilters"
                class="w-full px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors"
            >
                Apply Filters
            </button>
        </div>
    @endif
</div>
