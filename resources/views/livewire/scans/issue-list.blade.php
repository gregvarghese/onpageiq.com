<div class="space-y-4">
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
</div>
