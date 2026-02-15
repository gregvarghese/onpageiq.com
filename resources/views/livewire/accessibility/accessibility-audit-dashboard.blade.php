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
                            <a href="{{ route('projects.show', $project) }}" class="ml-2 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">{{ $project->name }}</a>
                        </li>
                        <li class="flex items-center">
                            <x-ui.icon name="chevron-down" class="size-4 text-gray-400 -rotate-90" />
                            <span class="ml-2 text-sm font-medium text-gray-900 dark:text-white">Accessibility Audit</span>
                        </li>
                    </ol>
                </nav>
                <h1 class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">Accessibility Audit</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">WCAG 2.1 compliance testing for {{ $project->name }}</p>
            </div>
            <div class="flex items-center gap-x-3">
                <button
                    type="button"
                    wire:click="$set('showRunModal', true)"
                    class="inline-flex items-center gap-x-2 rounded-md bg-primary-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"
                >
                    <x-ui.icon name="play" class="size-5" />
                    Run Audit
                </button>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        {{-- Audit Selector --}}
        @if($this->audits->isNotEmpty())
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 p-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-x-4">
                        <label for="audit-select" class="text-sm font-medium text-gray-700 dark:text-gray-300">Select Audit:</label>
                        <select
                            id="audit-select"
                            wire:model.live="selectedAudit"
                            wire:change="selectAudit($event.target.value)"
                            class="rounded-md border-0 py-1.5 pl-3 pr-8 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-700 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 focus:ring-2 focus:ring-primary-600"
                        >
                            @foreach($this->audits as $audit)
                                <option value="{{ $audit->id }}" {{ $selectedAudit?->id === $audit->id ? 'selected' : '' }}>
                                    {{ $audit->created_at->format('M j, Y g:i A') }} - {{ $audit->status->label() }}
                                    @if($audit->isCompleted())
                                        ({{ number_format($audit->overall_score, 1) }}%)
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @if($selectedAudit)
                        <div class="flex items-center gap-x-2">
                            <x-accessibility.wcag-badge :level="$selectedAudit->wcag_level_target" />
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $selectedAudit->status->color() }}">
                                {{ $selectedAudit->status->label() }}
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        @if($selectedAudit)
            {{-- Status Banner for Running/Pending Audits --}}
            @if($selectedAudit->isRunning() || $selectedAudit->isPending())
                <div class="rounded-xl bg-blue-50 dark:bg-blue-900/20 p-4 ring-1 ring-blue-200 dark:ring-blue-800">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <x-ui.icon name="arrow-path" class="size-5 text-blue-400 animate-spin" />
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800 dark:text-blue-300">
                                Audit {{ $selectedAudit->isPending() ? 'Queued' : 'In Progress' }}
                            </h3>
                            <p class="mt-1 text-sm text-blue-700 dark:text-blue-400">
                                The accessibility audit is currently running. Results will appear here when complete.
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Overview Cards --}}
            @if($selectedAudit->isCompleted())
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {{-- Overall Score Card --}}
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
                        <div class="flex items-center gap-x-3">
                            <div class="flex-shrink-0 rounded-lg p-3 {{ $selectedAudit->overall_score >= 90 ? 'bg-green-100 dark:bg-green-900/30' : ($selectedAudit->overall_score >= 70 ? 'bg-yellow-100 dark:bg-yellow-900/30' : 'bg-red-100 dark:bg-red-900/30') }}">
                                <x-ui.icon name="chart-pie" class="size-6 {{ $selectedAudit->overall_score >= 90 ? 'text-green-600 dark:text-green-400' : ($selectedAudit->overall_score >= 70 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400') }}" />
                            </div>
                            <div>
                                <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($selectedAudit->overall_score, 1) }}%</p>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Overall Score</p>
                            </div>
                        </div>
                    </div>

                    {{-- Passed Checks --}}
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
                        <div class="flex items-center gap-x-3">
                            <div class="flex-shrink-0 rounded-lg bg-green-100 dark:bg-green-900/30 p-3">
                                <x-ui.icon name="check-circle" class="size-6 text-green-600 dark:text-green-400" />
                            </div>
                            <div>
                                <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $selectedAudit->checks_passed }}</p>
                                <p class="text-sm font-medium text-green-600 dark:text-green-400">Checks Passed</p>
                            </div>
                        </div>
                    </div>

                    {{-- Failed Checks --}}
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
                        <div class="flex items-center gap-x-3">
                            <div class="flex-shrink-0 rounded-lg bg-red-100 dark:bg-red-900/30 p-3">
                                <x-ui.icon name="x-circle" class="size-6 text-red-600 dark:text-red-400" />
                            </div>
                            <div>
                                <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $selectedAudit->checks_failed }}</p>
                                <p class="text-sm font-medium text-red-600 dark:text-red-400">Issues Found</p>
                            </div>
                        </div>
                    </div>

                    {{-- Duration --}}
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
                        <div class="flex items-center gap-x-3">
                            <div class="flex-shrink-0 rounded-lg bg-gray-100 dark:bg-gray-700 p-3">
                                <x-ui.icon name="clock" class="size-6 text-gray-600 dark:text-gray-400" />
                            </div>
                            <div>
                                <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $selectedAudit->getFormattedDuration() ?? 'N/A' }}</p>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Duration</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Radar Chart and Category Breakdown --}}
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    {{-- Radar Chart --}}
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Category Scores</h3>
                        <div class="flex justify-center">
                            <livewire:accessibility.radar-chart :scores="$this->categoryScores" :size="300" />
                        </div>
                    </div>

                    {{-- Category Breakdown --}}
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Issues by Category</h3>
                        <div class="space-y-4">
                            @foreach($categories as $category)
                                @php
                                    $count = $this->categoryCounts[$category->value] ?? 0;
                                    $score = $this->categoryScores[$category->value] ?? 0;
                                @endphp
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $category->label() }}</span>
                                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ number_format($score, 1) }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                        <div class="h-2 rounded-full {{ $score >= 90 ? 'bg-green-500' : ($score >= 70 ? 'bg-yellow-500' : 'bg-red-500') }}" style="width: {{ $score }}%"></div>
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $count }} checks</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Results List --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Audit Results</h3>
                            <div class="flex items-center gap-x-2">
                                {{-- Category Filter --}}
                                <select
                                    wire:model.live="categoryFilter"
                                    class="rounded-md border-0 py-1.5 pl-3 pr-8 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-700 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 focus:ring-2 focus:ring-primary-600"
                                >
                                    <option value="">All Categories</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->value }}">{{ $category->label() }}</option>
                                    @endforeach
                                </select>

                                {{-- Status Filter --}}
                                <select
                                    wire:model.live="statusFilter"
                                    class="rounded-md border-0 py-1.5 pl-3 pr-8 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-700 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 focus:ring-2 focus:ring-primary-600"
                                >
                                    <option value="">All Statuses</option>
                                    <option value="fail">Failed</option>
                                    <option value="warning">Warning</option>
                                    <option value="pass">Passed</option>
                                    <option value="not_applicable">N/A</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <livewire:accessibility.audit-results-list :audit="$selectedAudit" :key="$selectedAudit->id" />
                </div>
            @endif

            {{-- Failed Audit Banner --}}
            @if($selectedAudit->isFailed())
                <div class="rounded-xl bg-red-50 dark:bg-red-900/20 p-4 ring-1 ring-red-200 dark:ring-red-800">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <x-ui.icon name="x-circle" class="size-5 text-red-400" />
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800 dark:text-red-300">Audit Failed</h3>
                            <p class="mt-1 text-sm text-red-700 dark:text-red-400">
                                {{ $selectedAudit->error_message ?? 'An unknown error occurred during the audit.' }}
                            </p>
                        </div>
                    </div>
                </div>
            @endif
        @else
            {{-- Empty State --}}
            <div class="text-center py-12">
                <x-ui.icon name="clipboard-document-check" class="mx-auto size-12 text-gray-400" />
                <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">No audits yet</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by running your first accessibility audit.</p>
                <div class="mt-6">
                    <button
                        type="button"
                        wire:click="$set('showRunModal', true)"
                        class="inline-flex items-center gap-x-2 rounded-md bg-primary-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-500"
                    >
                        <x-ui.icon name="play" class="size-5" />
                        Run Accessibility Audit
                    </button>
                </div>
            </div>
        @endif
    </div>

    {{-- Run Audit Modal --}}
    @if($showRunModal)
        <div class="relative z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-gray-500/75 dark:bg-gray-900/75 transition-opacity"></div>
            <div class="fixed inset-0 z-10 overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <div class="relative transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
                        <div>
                            <div class="mx-auto flex size-12 items-center justify-center rounded-full bg-primary-100 dark:bg-primary-900/30">
                                <x-ui.icon name="clipboard-document-check" class="size-6 text-primary-600 dark:text-primary-400" />
                            </div>
                            <div class="mt-3 text-center sm:mt-5">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white" id="modal-title">Run Accessibility Audit</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        Select the WCAG conformance level to test against.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="mt-5 space-y-4">
                            <div>
                                <label for="wcag-level" class="block text-sm font-medium text-gray-700 dark:text-gray-300">WCAG Level</label>
                                <select
                                    id="wcag-level"
                                    wire:model="wcagLevelTarget"
                                    class="mt-1 block w-full rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 dark:text-white bg-white dark:bg-gray-700 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 focus:ring-2 focus:ring-primary-600 sm:text-sm"
                                >
                                    @foreach($wcagLevels as $level)
                                        <option value="{{ $level->value }}">{{ $level->label() }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    Level AA is recommended for most websites. Level AAA criteria will be flagged as opportunities.
                                </p>
                            </div>
                        </div>
                        <div class="mt-5 sm:mt-6 sm:grid sm:grid-flow-row-dense sm:grid-cols-2 sm:gap-3">
                            <button
                                type="button"
                                wire:click="runAudit"
                                class="inline-flex w-full justify-center rounded-md bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 sm:col-start-2"
                            >
                                Start Audit
                            </button>
                            <button
                                type="button"
                                wire:click="$set('showRunModal', false)"
                                class="mt-3 inline-flex w-full justify-center rounded-md bg-white dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-white shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 sm:col-start-1 sm:mt-0"
                            >
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
