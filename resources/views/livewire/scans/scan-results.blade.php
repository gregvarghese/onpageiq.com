<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <nav class="flex" aria-label="Breadcrumb">
                    <ol role="list" class="flex items-center space-x-2">
                        <li>
                            <a href="{{ route('projects.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">Projects</a>
                        </li>
                        <li class="flex items-center">
                            <x-ui.icon name="chevron-down" class="size-4 text-gray-400 -rotate-90" />
                            <a href="{{ route('projects.show', $scan->url->project) }}" class="ml-2 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">{{ $scan->url->project->name }}</a>
                        </li>
                        <li class="flex items-center">
                            <x-ui.icon name="chevron-down" class="size-4 text-gray-400 -rotate-90" />
                            <span class="ml-2 text-sm font-medium text-gray-900 dark:text-white">Scan Results</span>
                        </li>
                    </ol>
                </nav>
                <h1 class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white truncate max-w-2xl">{{ $scan->url->url }}</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Scanned {{ $scan->completed_at?->diffForHumans() ?? 'In progress' }}
                    · {{ ucfirst($scan->scan_type) }} scan
                </p>
            </div>
            <div class="flex items-center gap-x-3">
                <button
                    type="button"
                    class="inline-flex items-center gap-x-2 rounded-md bg-white dark:bg-gray-800 px-3.5 py-2.5 text-sm font-semibold text-gray-900 dark:text-white shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700"
                >
                    <x-ui.icon name="arrow-down-tray" class="size-5" />
                    Export PDF
                </button>
            </div>
        </div>
    </x-slot>

    @if(!$result)
        <div class="text-center py-12">
            <x-ui.icon name="arrow-path" class="mx-auto size-12 text-gray-400 animate-spin" />
            <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">Scan in progress</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Please wait while we analyze your content.</p>
        </div>
    @else
        <!-- Score Cards -->
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-8">
            <!-- Overall Score -->
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Overall Score</h3>
                    <x-ui.icon name="chart-bar" class="size-5 text-gray-400" />
                </div>
                @php $overallScore = $scores['overall'] ?? 0; @endphp
                <p class="mt-2 text-3xl font-semibold @if($overallScore >= 80) text-green-600 dark:text-green-400 @elseif($overallScore >= 60) text-yellow-600 dark:text-yellow-400 @else text-red-600 dark:text-red-400 @endif">
                    {{ number_format($overallScore) }}%
                </p>
            </div>

            <!-- Readability Score -->
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Readability</h3>
                    <x-ui.icon name="book-open" class="size-5 text-gray-400" />
                </div>
                @php $readabilityScore = $scores['readability'] ?? 0; @endphp
                <p class="mt-2 text-3xl font-semibold @if($readabilityScore >= 80) text-green-600 dark:text-green-400 @elseif($readabilityScore >= 60) text-yellow-600 dark:text-yellow-400 @else text-red-600 dark:text-red-400 @endif">
                    {{ number_format($readabilityScore) }}%
                </p>
            </div>

            <!-- SEO Score -->
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">SEO</h3>
                    <x-ui.icon name="globe-alt" class="size-5 text-gray-400" />
                </div>
                @php $seoScore = $scores['seo'] ?? 0; @endphp
                <p class="mt-2 text-3xl font-semibold @if($seoScore >= 80) text-green-600 dark:text-green-400 @elseif($seoScore >= 60) text-yellow-600 dark:text-yellow-400 @else text-red-600 dark:text-red-400 @endif">
                    {{ number_format($seoScore) }}%
                </p>
            </div>

            <!-- Issues Count -->
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Issues</h3>
                    <x-ui.icon name="exclamation-circle" class="size-5 text-gray-400" />
                </div>
                <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-white">
                    {{ $issues->count() }}
                </p>
            </div>
        </div>

        <!-- Filters -->
        <div class="mb-6 flex flex-wrap items-center gap-3">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Filter by:</span>

            <!-- Category Filters -->
            <div class="flex flex-wrap gap-2">
                @foreach($categoryCounts as $category => $count)
                    <button
                        wire:click="$set('categoryFilter', '{{ $categoryFilter === $category ? '' : $category }}')"
                        @class([
                            'inline-flex items-center gap-x-1.5 rounded-full px-3 py-1.5 text-xs font-medium transition-colors',
                            'bg-primary-100 text-primary-700 dark:bg-primary-900/50 dark:text-primary-300' => $categoryFilter === $category,
                            'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600' => $categoryFilter !== $category,
                        ])
                    >
                        {{ ucfirst($category) }}
                        <span class="rounded-full bg-white/50 dark:bg-gray-800/50 px-1.5 py-0.5">{{ $count }}</span>
                    </button>
                @endforeach
            </div>

            <div class="h-4 w-px bg-gray-300 dark:bg-gray-600"></div>

            <!-- Severity Filters -->
            <div class="flex flex-wrap gap-2">
                @foreach($severityCounts as $severity => $count)
                    <button
                        wire:click="$set('severityFilter', '{{ $severityFilter === $severity ? '' : $severity }}')"
                        @class([
                            'inline-flex items-center gap-x-1.5 rounded-full px-3 py-1.5 text-xs font-medium transition-colors',
                            'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300' => $severity === 'error' && $severityFilter === $severity,
                            'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/50 dark:text-yellow-300' => $severity === 'warning' && $severityFilter === $severity,
                            'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300' => $severity === 'suggestion' && $severityFilter === $severity,
                            'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600' => $severityFilter !== $severity,
                        ])
                    >
                        {{ ucfirst($severity) }}
                        <span class="rounded-full bg-white/50 dark:bg-gray-800/50 px-1.5 py-0.5">{{ $count }}</span>
                    </button>
                @endforeach
            </div>

            @if($categoryFilter || $severityFilter)
                <button
                    wire:click="$set('categoryFilter', ''); $set('severityFilter', '')"
                    class="text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
                >
                    Clear filters
                </button>
            @endif
        </div>

        <!-- Issues List -->
        @if($issues->isEmpty())
            <div class="text-center py-12 border border-dashed border-gray-300 dark:border-gray-700 rounded-lg">
                <x-ui.icon name="check-circle" class="mx-auto size-12 text-green-500" />
                <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">No issues found</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    @if($categoryFilter || $severityFilter)
                        No issues match your current filters.
                    @else
                        Great job! Your content looks perfect.
                    @endif
                </p>
            </div>
        @else
            <div class="space-y-4">
                @foreach($issues as $issue)
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

                                <!-- DOM Selector (for developers) -->
                                @if($issue->dom_selector)
                                    <div class="mt-2">
                                        <p class="text-xs text-gray-500 dark:text-gray-400 font-mono truncate">
                                            {{ $issue->dom_selector }}
                                        </p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @endif
</div>
