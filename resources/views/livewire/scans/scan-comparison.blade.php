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
                            <a href="{{ route('projects.show', $currentScan->url->project) }}" class="ml-2 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">{{ $currentScan->url->project->name }}</a>
                        </li>
                        <li class="flex items-center">
                            <x-ui.icon name="chevron-down" class="size-4 text-gray-400 -rotate-90" />
                            <span class="ml-2 text-sm font-medium text-gray-900 dark:text-white">Compare Scans</span>
                        </li>
                    </ol>
                </nav>
                <h1 class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">Compare Scans</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $currentScan->url->url }}</p>
            </div>
        </div>
    </x-slot>

    <!-- Baseline Selector -->
    <div class="mb-6 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
        <div class="flex items-end gap-x-4">
            <div class="flex-1">
                <label for="baseline" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Compare against
                </label>
                <select
                    id="baseline"
                    wire:model="baselineScanId"
                    class="block w-full rounded-md border-0 py-2.5 pl-3 pr-10 text-gray-900 dark:text-white bg-white dark:bg-gray-700 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 focus:ring-2 focus:ring-primary-600 sm:text-sm"
                >
                    <option value="">Select a previous scan...</option>
                    @foreach($availableBaselines as $baseline)
                        <option value="{{ $baseline->id }}">
                            {{ $baseline->completed_at->format('M j, Y g:i A') }}
                            ({{ ucfirst($baseline->scan_type) }})
                        </option>
                    @endforeach
                </select>
                @error('baseline')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
            <button
                wire:click="compare"
                wire:loading.attr="disabled"
                :disabled="!$baselineScanId"
                class="inline-flex items-center gap-x-2 rounded-md bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <x-ui.icon name="arrow-path" class="size-5" wire:loading.class="animate-spin" />
                Compare
            </button>
        </div>
    </div>

    @if($comparison)
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-8">
            <!-- Fixed Issues -->
            <div class="rounded-lg border border-green-200 dark:border-green-900 bg-green-50 dark:bg-green-900/20 p-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-medium text-green-800 dark:text-green-300">Fixed</h3>
                    <x-ui.icon name="check-circle" class="size-5 text-green-600 dark:text-green-400" />
                </div>
                <p class="mt-2 text-3xl font-semibold text-green-600 dark:text-green-400">
                    {{ $comparison->fixedCount() }}
                </p>
                <p class="mt-1 text-xs text-green-700 dark:text-green-300">issues resolved</p>
            </div>

            <!-- New Issues -->
            <div class="rounded-lg border border-red-200 dark:border-red-900 bg-red-50 dark:bg-red-900/20 p-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-medium text-red-800 dark:text-red-300">New</h3>
                    <x-ui.icon name="exclamation-circle" class="size-5 text-red-600 dark:text-red-400" />
                </div>
                <p class="mt-2 text-3xl font-semibold text-red-600 dark:text-red-400">
                    {{ $comparison->newCount() }}
                </p>
                <p class="mt-1 text-xs text-red-700 dark:text-red-300">new issues found</p>
            </div>

            <!-- Unchanged Issues -->
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">Unchanged</h3>
                    <x-ui.icon name="exclamation-triangle" class="size-5 text-gray-500 dark:text-gray-400" />
                </div>
                <p class="mt-2 text-3xl font-semibold text-gray-600 dark:text-gray-400">
                    {{ $comparison->unchangedCount() }}
                </p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">issues remaining</p>
            </div>

            <!-- Overall Score Change -->
            <div @class([
                'rounded-lg border p-6',
                'border-green-200 dark:border-green-900 bg-green-50 dark:bg-green-900/20' => $comparison->scoreImproved(),
                'border-red-200 dark:border-red-900 bg-red-50 dark:bg-red-900/20' => !$comparison->scoreImproved() && $comparison->overallScoreChange() < 0,
                'border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800' => $comparison->overallScoreChange() === 0.0,
            ])>
                <div class="flex items-center justify-between">
                    <h3 @class([
                        'text-sm font-medium',
                        'text-green-800 dark:text-green-300' => $comparison->scoreImproved(),
                        'text-red-800 dark:text-red-300' => !$comparison->scoreImproved() && $comparison->overallScoreChange() < 0,
                        'text-gray-700 dark:text-gray-300' => $comparison->overallScoreChange() === 0.0,
                    ])>Score Change</h3>
                    <x-ui.icon name="chart-bar" @class([
                        'size-5',
                        'text-green-600 dark:text-green-400' => $comparison->scoreImproved(),
                        'text-red-600 dark:text-red-400' => !$comparison->scoreImproved() && $comparison->overallScoreChange() < 0,
                        'text-gray-500 dark:text-gray-400' => $comparison->overallScoreChange() === 0.0,
                    ]) />
                </div>
                <p @class([
                    'mt-2 text-3xl font-semibold',
                    'text-green-600 dark:text-green-400' => $comparison->scoreImproved(),
                    'text-red-600 dark:text-red-400' => !$comparison->scoreImproved() && $comparison->overallScoreChange() < 0,
                    'text-gray-600 dark:text-gray-400' => $comparison->overallScoreChange() === 0.0,
                ])>
                    {{ $comparison->overallScoreChange() >= 0 ? '+' : '' }}{{ number_format($comparison->overallScoreChange(), 1) }}%
                </p>
                <p @class([
                    'mt-1 text-xs',
                    'text-green-700 dark:text-green-300' => $comparison->scoreImproved(),
                    'text-red-700 dark:text-red-300' => !$comparison->scoreImproved() && $comparison->overallScoreChange() < 0,
                    'text-gray-500 dark:text-gray-400' => $comparison->overallScoreChange() === 0.0,
                ])>
                    overall score
                </p>
            </div>
        </div>

        <!-- Tabs -->
        <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
            <nav class="-mb-px flex space-x-8">
                <button
                    wire:click="setTab('summary')"
                    @class([
                        'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-colors',
                        'border-primary-500 text-primary-600 dark:text-primary-400' => $activeTab === 'summary',
                        'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' => $activeTab !== 'summary',
                    ])
                >
                    Summary
                </button>
                <button
                    wire:click="setTab('fixed')"
                    @class([
                        'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-colors',
                        'border-primary-500 text-primary-600 dark:text-primary-400' => $activeTab === 'fixed',
                        'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' => $activeTab !== 'fixed',
                    ])
                >
                    Fixed ({{ $comparison->fixedCount() }})
                </button>
                <button
                    wire:click="setTab('new')"
                    @class([
                        'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-colors',
                        'border-primary-500 text-primary-600 dark:text-primary-400' => $activeTab === 'new',
                        'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' => $activeTab !== 'new',
                    ])
                >
                    New ({{ $comparison->newCount() }})
                </button>
                <button
                    wire:click="setTab('unchanged')"
                    @class([
                        'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-colors',
                        'border-primary-500 text-primary-600 dark:text-primary-400' => $activeTab === 'unchanged',
                        'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' => $activeTab !== 'unchanged',
                    ])
                >
                    Unchanged ({{ $comparison->unchangedCount() }})
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="space-y-4">
            @if($activeTab === 'summary')
                <!-- Score Changes Table -->
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Metric</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Baseline</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Current</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Change</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($comparison->scoreChanges as $metric => $data)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                        {{ ucfirst($metric) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ number_format($data['baseline'], 1) }}%
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ number_format($data['current'], 1) }}%
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span @class([
                                            'inline-flex items-center gap-x-1',
                                            'text-green-600 dark:text-green-400' => $data['improved'],
                                            'text-red-600 dark:text-red-400' => !$data['improved'] && $data['change'] < 0,
                                            'text-gray-500 dark:text-gray-400' => $data['change'] === 0.0,
                                        ])>
                                            {{ $data['change'] >= 0 ? '+' : '' }}{{ number_format($data['change'], 1) }}%
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            @elseif($activeTab === 'fixed')
                @forelse($comparison->fixedIssues as $issue)
                    <div class="rounded-lg border border-green-200 dark:border-green-800 bg-white dark:bg-gray-800 p-4">
                        <div class="flex items-start gap-x-4">
                            <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900/30">
                                <x-ui.icon name="check-circle" class="size-5 text-green-600 dark:text-green-400" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-x-2">
                                    <span class="inline-flex items-center rounded-md bg-green-50 dark:bg-green-900/30 px-2 py-1 text-xs font-medium text-green-700 dark:text-green-400 ring-1 ring-inset ring-green-600/20">
                                        Fixed
                                    </span>
                                    <span class="inline-flex items-center rounded-md bg-gray-50 dark:bg-gray-700 px-2 py-1 text-xs font-medium text-gray-600 dark:text-gray-400 ring-1 ring-inset ring-gray-500/10">
                                        {{ ucfirst($issue->category) }}
                                    </span>
                                </div>
                                <p class="mt-2 text-sm text-gray-900 dark:text-white line-through opacity-75">
                                    {{ $issue->text_excerpt }}
                                </p>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8">
                        <p class="text-sm text-gray-500 dark:text-gray-400">No issues were fixed between these scans.</p>
                    </div>
                @endforelse

            @elseif($activeTab === 'new')
                @forelse($comparison->newIssues as $issue)
                    <div class="rounded-lg border border-red-200 dark:border-red-800 bg-white dark:bg-gray-800 p-4">
                        <div class="flex items-start gap-x-4">
                            <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-red-100 dark:bg-red-900/30">
                                <x-ui.icon name="exclamation-circle" class="size-5 text-red-600 dark:text-red-400" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-x-2">
                                    <span class="inline-flex items-center rounded-md bg-red-50 dark:bg-red-900/30 px-2 py-1 text-xs font-medium text-red-700 dark:text-red-400 ring-1 ring-inset ring-red-600/20">
                                        New
                                    </span>
                                    <span class="inline-flex items-center rounded-md bg-gray-50 dark:bg-gray-700 px-2 py-1 text-xs font-medium text-gray-600 dark:text-gray-400 ring-1 ring-inset ring-gray-500/10">
                                        {{ ucfirst($issue->category) }}
                                    </span>
                                </div>
                                <p class="mt-2 text-sm text-gray-900 dark:text-white">
                                    <code class="rounded bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 text-red-600 dark:text-red-400">{{ $issue->text_excerpt }}</code>
                                </p>
                                @if($issue->suggestion)
                                    <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">
                                        <span class="font-medium text-green-600 dark:text-green-400">Suggestion:</span>
                                        {{ $issue->suggestion }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8">
                        <p class="text-sm text-gray-500 dark:text-gray-400">No new issues were introduced.</p>
                    </div>
                @endforelse

            @elseif($activeTab === 'unchanged')
                @forelse($comparison->unchangedIssues as $pair)
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
                        <div class="flex items-start gap-x-4">
                            <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-yellow-100 dark:bg-yellow-900/30">
                                <x-ui.icon name="exclamation-triangle" class="size-5 text-yellow-600 dark:text-yellow-400" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-x-2">
                                    <span class="inline-flex items-center rounded-md bg-yellow-50 dark:bg-yellow-900/30 px-2 py-1 text-xs font-medium text-yellow-700 dark:text-yellow-400 ring-1 ring-inset ring-yellow-600/20">
                                        Unchanged
                                    </span>
                                    <span class="inline-flex items-center rounded-md bg-gray-50 dark:bg-gray-700 px-2 py-1 text-xs font-medium text-gray-600 dark:text-gray-400 ring-1 ring-inset ring-gray-500/10">
                                        {{ ucfirst($pair['current']->category) }}
                                    </span>
                                </div>
                                <p class="mt-2 text-sm text-gray-900 dark:text-white">
                                    <code class="rounded bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 text-yellow-600 dark:text-yellow-400">{{ $pair['current']->text_excerpt }}</code>
                                </p>
                                @if($pair['current']->suggestion)
                                    <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">
                                        <span class="font-medium text-green-600 dark:text-green-400">Suggestion:</span>
                                        {{ $pair['current']->suggestion }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8">
                        <p class="text-sm text-gray-500 dark:text-gray-400">All previous issues have been addressed.</p>
                    </div>
                @endforelse
            @endif
        </div>
    @else
        <!-- Empty State -->
        <div class="text-center py-12 border border-dashed border-gray-300 dark:border-gray-700 rounded-lg">
            <x-ui.icon name="chart-bar" class="mx-auto size-12 text-gray-400" />
            <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">No comparison yet</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Select a previous scan to compare against.</p>
        </div>
    @endif
</div>
