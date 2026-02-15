<div class="divide-y divide-gray-200 dark:divide-gray-700">
    {{-- Summary Bar --}}
    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-x-4">
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    <span class="font-medium text-gray-900 dark:text-white">{{ $this->summary['total'] }}</span> total checks
                </span>
                <span class="text-sm text-green-600 dark:text-green-400">
                    <x-ui.icon name="check-circle" class="inline-block size-4" />
                    {{ $this->summary['passed'] }} passed
                </span>
                <span class="text-sm text-red-600 dark:text-red-400">
                    <x-ui.icon name="x-circle" class="inline-block size-4" />
                    {{ $this->summary['failed'] }} failed
                </span>
                @if($this->summary['critical'] > 0)
                    <span class="text-sm text-red-700 dark:text-red-300 font-medium">
                        {{ $this->summary['critical'] }} critical
                    </span>
                @endif
            </div>
            <div class="flex items-center gap-x-2">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search checks..."
                    class="rounded-md border-0 py-1.5 pl-3 pr-10 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-700 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 focus:ring-2 focus:ring-primary-600 w-64"
                />
                @if($search || $statusFilter || $wcagLevelFilter || $impactFilter || $categoryFilter)
                    <button
                        type="button"
                        wire:click="clearFilters"
                        class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
                    >
                        Clear filters
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Filter Pills --}}
    <div class="px-6 py-3 flex items-center gap-x-2 flex-wrap bg-white dark:bg-gray-800">
        <span class="text-xs font-medium text-gray-500 dark:text-gray-400 mr-2">Filter:</span>

        {{-- Status Filter Pills --}}
        @foreach($statuses as $status)
            <button
                type="button"
                wire:click="$set('statusFilter', '{{ $statusFilter === $status->value ? '' : $status->value }}')"
                class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium transition-colors {{ $statusFilter === $status->value ? $status->color() : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}"
            >
                {{ $status->label() }}
            </button>
        @endforeach

        <span class="mx-2 text-gray-300 dark:text-gray-600">|</span>

        {{-- Impact Filter Pills --}}
        @foreach($impactLevels as $impact)
            <button
                type="button"
                wire:click="$set('impactFilter', '{{ $impactFilter === $impact->value ? '' : $impact->value }}')"
                class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium transition-colors {{ $impactFilter === $impact->value ? $impact->color() : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}"
            >
                {{ $impact->label() }}
            </button>
        @endforeach

        <span class="mx-2 text-gray-300 dark:text-gray-600">|</span>

        {{-- WCAG Level Filter --}}
        @foreach($wcagLevels as $level)
            <button
                type="button"
                wire:click="$set('wcagLevelFilter', '{{ $wcagLevelFilter === $level->value ? '' : $level->value }}')"
                class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium transition-colors {{ $wcagLevelFilter === $level->value ? 'bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}"
            >
                Level {{ $level->value }}
            </button>
        @endforeach
    </div>

    {{-- Results Table --}}
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900/50">
                <tr>
                    <th scope="col" class="py-3.5 pl-6 pr-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        <button type="button" wire:click="sort('status')" class="group inline-flex items-center gap-x-1">
                            Status
                            @if($sortBy === 'status')
                                <x-ui.icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-3" />
                            @endif
                        </button>
                    </th>
                    <th scope="col" class="px-3 py-3.5 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        <button type="button" wire:click="sort('criterion_id')" class="group inline-flex items-center gap-x-1">
                            Criterion
                            @if($sortBy === 'criterion_id')
                                <x-ui.icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-3" />
                            @endif
                        </button>
                    </th>
                    <th scope="col" class="px-3 py-3.5 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        <button type="button" wire:click="sort('wcag_level')" class="group inline-flex items-center gap-x-1">
                            Level
                            @if($sortBy === 'wcag_level')
                                <x-ui.icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-3" />
                            @endif
                        </button>
                    </th>
                    <th scope="col" class="px-3 py-3.5 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        <button type="button" wire:click="sort('impact')" class="group inline-flex items-center gap-x-1">
                            Impact
                            @if($sortBy === 'impact')
                                <x-ui.icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-3" />
                            @endif
                        </button>
                    </th>
                    <th scope="col" class="px-3 py-3.5 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        Message
                    </th>
                    <th scope="col" class="px-3 py-3.5 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        Element
                    </th>
                    <th scope="col" class="relative py-3.5 pl-3 pr-6">
                        <span class="sr-only">Actions</span>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                @forelse($this->checks as $check)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <td class="whitespace-nowrap py-4 pl-6 pr-3">
                            <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium {{ $check->status->color() }}">
                                @if($check->status === \App\Enums\CheckStatus::Pass)
                                    <x-ui.icon name="check" class="size-3 mr-1" />
                                @elseif($check->status === \App\Enums\CheckStatus::Fail)
                                    <x-ui.icon name="x-mark" class="size-3 mr-1" />
                                @elseif($check->status === \App\Enums\CheckStatus::Warning)
                                    <x-ui.icon name="exclamation-triangle" class="size-3 mr-1" />
                                @endif
                                {{ $check->status->label() }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm">
                            <a href="{{ $check->documentation_url }}" target="_blank" class="font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400">
                                {{ $check->criterion_id }}
                            </a>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4">
                            <x-accessibility.wcag-badge :level="$check->wcag_level" size="sm" />
                        </td>
                        <td class="whitespace-nowrap px-3 py-4">
                            @if($check->impact)
                                <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium {{ $check->impact->color() }}">
                                    {{ $check->impact->label() }}
                                </span>
                            @else
                                <span class="text-gray-400 dark:text-gray-500">-</span>
                            @endif
                        </td>
                        <td class="px-3 py-4 text-sm text-gray-900 dark:text-white max-w-md">
                            <p class="truncate" title="{{ $check->message }}">{{ $check->message }}</p>
                            @if($check->suggestion)
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 truncate" title="{{ $check->suggestion }}">
                                    {{ $check->suggestion }}
                                </p>
                            @endif
                        </td>
                        <td class="px-3 py-4 text-sm text-gray-500 dark:text-gray-400 max-w-xs">
                            @if($check->element_selector)
                                <code class="text-xs bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded truncate block max-w-full" title="{{ $check->element_selector }}">
                                    {{ Str::limit($check->element_selector, 40) }}
                                </code>
                            @else
                                <span class="text-gray-400 dark:text-gray-500">-</span>
                            @endif
                        </td>
                        <td class="relative whitespace-nowrap py-4 pl-3 pr-6 text-right text-sm">
                            <button
                                type="button"
                                x-data
                                x-on:click="$dispatch('open-check-detail', { checkId: '{{ $check->id }}' })"
                                class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300"
                            >
                                View<span class="sr-only">, {{ $check->criterion_id }}</span>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <x-ui.icon name="magnifying-glass" class="mx-auto size-12 text-gray-400" />
                            <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">No results found</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                @if($search || $statusFilter || $wcagLevelFilter || $impactFilter || $categoryFilter)
                                    Try adjusting your filters or search term.
                                @else
                                    No checks have been recorded for this audit.
                                @endif
                            </p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($this->checks->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            {{ $this->checks->links() }}
        </div>
    @endif
</div>
