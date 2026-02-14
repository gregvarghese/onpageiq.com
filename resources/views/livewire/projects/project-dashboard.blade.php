<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <!-- Breadcrumb -->
                <nav class="flex" aria-label="Breadcrumb">
                    <ol role="list" class="flex items-center space-x-2">
                        <li>
                            <a href="{{ route('projects.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">Projects</a>
                        </li>
                        <li class="flex items-center">
                            <x-ui.icon name="chevron-down" class="size-4 text-gray-400 -rotate-90" />
                            <span class="ml-2 text-sm font-medium text-gray-900 dark:text-white">{{ $project->name }}</span>
                        </li>
                    </ol>
                </nav>
                <h1 class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">{{ $project->name }}</h1>
                @if($project->description)
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $project->description }}</p>
                @endif
            </div>
            <div class="flex items-center gap-x-3">
                <!-- Dictionary Panel -->
                <livewire:projects.components.dictionary-panel :project="$project" />

                <!-- Scan Type Selector -->
                <select
                    x-data
                    x-on:change="Livewire.dispatch('set-scan-type', { type: $event.target.value })"
                    class="rounded-md border-0 py-2 pl-3 pr-8 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-primary-600"
                >
                    <option value="quick" {{ $scanType === 'quick' ? 'selected' : '' }}>Quick Scan</option>
                    <option value="deep" {{ $scanType === 'deep' ? 'selected' : '' }}>Deep Scan</option>
                </select>

                <button
                    type="button"
                    x-data="{ loading: false }"
                    x-on:click="loading = true; Livewire.dispatch('trigger-scan-all'); setTimeout(() => loading = false, 2000)"
                    x-bind:disabled="loading"
                    class="inline-flex items-center gap-x-2 rounded-md bg-primary-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <x-ui.icon name="arrow-path" class="size-5" x-bind:class="loading && 'animate-spin'" />
                    Scan All
                </button>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Dashboard Stats Section -->
        <section x-data="{ collapsed: @js($collapsedSections['dashboard']) }">
            <button
                type="button"
                wire:click="toggleSection('dashboard')"
                class="flex w-full items-center justify-between rounded-lg bg-white dark:bg-gray-800 px-4 py-3 text-left shadow-sm ring-1 ring-gray-200 dark:ring-gray-700"
            >
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Dashboard</h2>
                <x-ui.icon name="chevron-down" class="size-5 text-gray-500 transition-transform" x-bind:class="collapsed && '-rotate-180'" />
            </button>
            <div x-show="!collapsed" x-collapse class="mt-4">
                @php $stats = $this->stats; @endphp
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <!-- Score Card -->
                    <div class="rounded-lg bg-white dark:bg-gray-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Overall Score</p>
                            <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium {{ $stats['score'] >= 80 ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : ($stats['score'] >= 60 ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400') }}">
                                {{ $stats['score'] >= 80 ? 'Good' : ($stats['score'] >= 60 ? 'Fair' : 'Poor') }}
                            </span>
                        </div>
                        <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['score'] }}%</p>
                    </div>

                    <!-- Issues Card -->
                    <div class="rounded-lg bg-white dark:bg-gray-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Issues Found</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['totalIssues'] }}</p>
                        <div class="mt-2 flex items-center gap-x-3 text-sm">
                            <span class="inline-flex items-center gap-x-1 text-red-600 dark:text-red-400">
                                <span class="size-2 rounded-full bg-red-500"></span>
                                {{ $stats['errorCount'] }} errors
                            </span>
                            <span class="inline-flex items-center gap-x-1 text-yellow-600 dark:text-yellow-400">
                                <span class="size-2 rounded-full bg-yellow-500"></span>
                                {{ $stats['warningCount'] }} warnings
                            </span>
                        </div>
                    </div>

                    <!-- URLs Card -->
                    <div class="rounded-lg bg-white dark:bg-gray-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">URLs</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['totalUrls'] }}</p>
                        <div class="mt-2 flex items-center gap-x-3 text-sm">
                            <span class="text-green-600 dark:text-green-400">{{ $stats['completedUrls'] }} scanned</span>
                            @if($stats['scanningUrls'] > 0)
                                <span class="text-yellow-600 dark:text-yellow-400">{{ $stats['scanningUrls'] }} in queue</span>
                            @endif
                        </div>
                    </div>

                    <!-- Activity Card -->
                    <div class="rounded-lg bg-white dark:bg-gray-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Activity</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['totalScans'] }}</p>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            @if($stats['lastScanAt'])
                                Last scan {{ $stats['lastScanAt']->diffForHumans() }}
                            @else
                                No scans yet
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Trend Charts Section -->
        <section x-data="{ collapsed: @js($collapsedSections['charts'] ?? false) }">
            <button
                type="button"
                wire:click="toggleSection('charts')"
                class="flex w-full items-center justify-between rounded-lg bg-white dark:bg-gray-800 px-4 py-3 text-left shadow-sm ring-1 ring-gray-200 dark:ring-gray-700"
            >
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Trend Charts</h2>
                <x-ui.icon name="chevron-down" class="size-5 text-gray-500 transition-transform" x-bind:class="collapsed && '-rotate-180'" />
            </button>
            <div x-show="!collapsed" x-collapse class="mt-4">
                <livewire:projects.components.trend-charts :project="$project" />
            </div>
        </section>

        <!-- Scan Queue Section (only show when there are pending scans) -->
        @if($this->pendingScans->isNotEmpty())
            <section x-data="{ collapsed: @js($collapsedSections['queue']) }">
                <button
                    type="button"
                    wire:click="toggleSection('queue')"
                    class="flex w-full items-center justify-between rounded-lg bg-yellow-50 dark:bg-yellow-900/20 px-4 py-3 text-left shadow-sm ring-1 ring-yellow-200 dark:ring-yellow-800"
                >
                    <div class="flex items-center gap-x-2">
                        <x-ui.icon name="queue-list" class="size-5 text-yellow-600 dark:text-yellow-400" />
                        <h2 class="text-lg font-semibold text-yellow-800 dark:text-yellow-200">Scan Queue</h2>
                        <span class="inline-flex items-center rounded-full bg-yellow-100 dark:bg-yellow-800 px-2 py-0.5 text-xs font-medium text-yellow-700 dark:text-yellow-300">
                            {{ $this->pendingScans->count() }} pending
                        </span>
                    </div>
                    <x-ui.icon name="chevron-down" class="size-5 text-yellow-600 dark:text-yellow-400 transition-transform" x-bind:class="collapsed && '-rotate-180'" />
                </button>
                <div x-show="!collapsed" x-collapse class="mt-4">
                    <div class="rounded-lg bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 overflow-hidden">
                        <ul role="list" class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($this->pendingScans as $scan)
                                <li class="flex items-center justify-between px-4 py-3">
                                    <div class="flex items-center gap-x-3">
                                        <span class="flex size-2.5 rounded-full bg-yellow-500 animate-pulse"></span>
                                        <span class="text-sm text-gray-900 dark:text-white truncate max-w-md">{{ $scan->url->url }}</span>
                                    </div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $scan->status === 'processing' ? 'Processing...' : 'Queued' }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </section>
        @endif

        <!-- URL Management Section -->
        <section x-data="{ collapsed: @js($collapsedSections['urls']), showGroups: false }">
            <button
                type="button"
                wire:click="toggleSection('urls')"
                class="flex w-full items-center justify-between rounded-lg bg-white dark:bg-gray-800 px-4 py-3 text-left shadow-sm ring-1 ring-gray-200 dark:ring-gray-700"
            >
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">URL Management</h2>
                <x-ui.icon name="chevron-down" class="size-5 text-gray-500 transition-transform" x-bind:class="collapsed && '-rotate-180'" />
            </button>
            <div x-show="!collapsed" x-collapse class="mt-4 space-y-4">
                <!-- Action Bar -->
                <div class="flex items-center justify-between gap-x-3">
                    <div class="flex items-center gap-x-2">
                        <livewire:projects.components.bulk-import-modal :project="$project" />
                        @if($project->organization->canCreateUrlGroups())
                            <button
                                type="button"
                                x-on:click="showGroups = !showGroups"
                                class="inline-flex items-center gap-x-2 rounded-md bg-white dark:bg-gray-800 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700"
                            >
                                <x-ui.icon name="folder" class="size-4" />
                                <span x-text="showGroups ? 'Hide Groups' : 'Manage Groups'"></span>
                            </button>
                        @endif
                    </div>
                </div>

                <!-- URL Groups Panel -->
                <div x-show="showGroups" x-collapse class="rounded-lg bg-gray-50 dark:bg-gray-700/50 p-4">
                    <livewire:projects.components.url-group-manager :project="$project" />
                </div>

                <!-- Add URL Form -->
                <form wire:submit="addUrl" class="flex gap-x-3">
                    <div class="flex-1">
                        <label for="newUrl" class="sr-only">URL</label>
                        <input
                            type="url"
                            id="newUrl"
                            wire:model="newUrl"
                            placeholder="https://example.com/page"
                            class="block w-full rounded-md border-0 px-3 py-2.5 text-gray-900 dark:text-white bg-white dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6"
                        />
                        @error('newUrl')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:target="addUrl"
                        class="inline-flex items-center gap-x-2 rounded-md bg-gray-900 dark:bg-white px-3.5 py-2.5 text-sm font-semibold text-white dark:text-gray-900 shadow-sm hover:bg-gray-700 dark:hover:bg-gray-100 disabled:opacity-50"
                    >
                        <x-ui.icon name="plus" class="size-5" />
                        Add URL
                    </button>
                </form>

                <!-- URLs List -->
                @if($urls->isEmpty())
                    <div class="text-center py-12 border border-dashed border-gray-300 dark:border-gray-700 rounded-lg">
                        <x-ui.icon name="link" class="mx-auto size-12 text-gray-400" />
                        <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">No URLs</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Add URLs to start scanning your content.</p>
                    </div>
                @else
                    <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                        <ul role="list" class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($urls as $url)
                                <li wire:key="url-{{ $url->id }}" class="group hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <div class="flex items-center">
                                        <!-- Left clickable zone - navigates to scan -->
                                        @if($url->latestScan)
                                            <a href="{{ route('scans.show', $url->latestScan) }}" class="flex-1 flex items-center gap-x-4 p-4">
                                        @else
                                            <div class="flex-1 flex items-center gap-x-4 p-4">
                                        @endif
                                            <!-- Status Indicator -->
                                            @if($url->status === 'completed')
                                                <span class="flex size-2.5 rounded-full bg-green-500" title="Completed"></span>
                                            @elseif($url->status === 'scanning' || $url->status === 'pending')
                                                <span class="flex size-2.5 rounded-full bg-yellow-500 animate-pulse" title="Scanning"></span>
                                            @elseif($url->status === 'failed')
                                                <span class="flex size-2.5 rounded-full bg-red-500" title="Failed"></span>
                                            @else
                                                <span class="flex size-2.5 rounded-full bg-gray-400" title="Not scanned"></span>
                                            @endif

                                            <div class="min-w-0 flex-1">
                                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                                    {{ $url->url }}
                                                </p>
                                                <div class="mt-1 flex items-center gap-x-4 text-xs text-gray-500 dark:text-gray-400">
                                                    @if($url->last_scanned_at)
                                                        <span>Last scanned {{ $url->last_scanned_at->diffForHumans() }}</span>
                                                    @else
                                                        <span>Never scanned</span>
                                                    @endif

                                                    @if($url->group)
                                                        <span class="inline-flex items-center gap-x-1">
                                                            <span class="size-2 rounded-full" style="background-color: {{ $url->group->color }}"></span>
                                                            {{ $url->group->name }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>

                                            <!-- Issue Summary by Category -->
                                            @if($url->latestScan?->result)
                                                @php
                                                    $issues = $url->latestScan->result->issues;
                                                    $spellingCount = $issues->where('category', 'spelling')->count();
                                                    $grammarCount = $issues->where('category', 'grammar')->count();
                                                    $seoCount = $issues->where('category', 'seo')->count();
                                                @endphp
                                                @if($issues->count() === 0)
                                                    <span class="inline-flex items-center gap-x-1 text-green-600 dark:text-green-400 text-sm">
                                                        <x-ui.icon name="check-circle" class="size-4" />
                                                        No issues
                                                    </span>
                                                @else
                                                    <div class="flex items-center gap-x-2 text-xs">
                                                        @if($spellingCount > 0)
                                                            <span class="inline-flex items-center gap-x-1 rounded-full bg-red-100 dark:bg-red-900/30 px-2 py-0.5 text-red-700 dark:text-red-400">
                                                                Spelling: {{ $spellingCount }}
                                                            </span>
                                                        @endif
                                                        @if($grammarCount > 0)
                                                            <span class="inline-flex items-center gap-x-1 rounded-full bg-yellow-100 dark:bg-yellow-900/30 px-2 py-0.5 text-yellow-700 dark:text-yellow-400">
                                                                Grammar: {{ $grammarCount }}
                                                            </span>
                                                        @endif
                                                        @if($seoCount > 0)
                                                            <span class="inline-flex items-center gap-x-1 rounded-full bg-blue-100 dark:bg-blue-900/30 px-2 py-0.5 text-blue-700 dark:text-blue-400">
                                                                SEO: {{ $seoCount }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                @endif
                                            @endif
                                        @if($url->latestScan)
                                            </a>
                                        @else
                                            </div>
                                        @endif

                                        <!-- Right action zone -->
                                        <div class="flex items-center gap-x-1 px-4 border-l border-gray-200 dark:border-gray-700">
                                            <button
                                                wire:click="scanUrl({{ $url->id }})"
                                                wire:loading.attr="disabled"
                                                wire:target="scanUrl({{ $url->id }})"
                                                @if($url->isScanning()) disabled @endif
                                                class="rounded-md p-2 text-primary-600 dark:text-primary-400 hover:bg-primary-50 dark:hover:bg-primary-900/30 disabled:opacity-50 disabled:cursor-not-allowed"
                                                title="Scan URL"
                                            >
                                                <x-ui.icon
                                                    name="arrow-path"
                                                    class="size-5"
                                                    wire:loading.class="animate-spin"
                                                    wire:target="scanUrl({{ $url->id }})"
                                                />
                                            </button>

                                            <button
                                                wire:click="deleteUrl({{ $url->id }})"
                                                wire:confirm="Are you sure you want to delete this URL? All scan history will be lost."
                                                class="rounded-md p-2 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30"
                                                title="Delete URL"
                                            >
                                                <x-ui.icon name="trash" class="size-5" />
                                            </button>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </section>
        <!-- Issue Workflow Section -->
        <section x-data="{ collapsed: @js($collapsedSections['issues'] ?? false) }">
            <button
                type="button"
                wire:click="toggleSection('issues')"
                class="flex w-full items-center justify-between rounded-lg bg-white dark:bg-gray-800 px-4 py-3 text-left shadow-sm ring-1 ring-gray-200 dark:ring-gray-700"
            >
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Issue Workflow</h2>
                <x-ui.icon name="chevron-down" class="size-5 text-gray-500 transition-transform" x-bind:class="collapsed && '-rotate-180'" />
            </button>
            <div x-show="!collapsed" x-collapse class="mt-4">
                <livewire:projects.components.issue-workflow :project="$project" />
            </div>
        </section>

        <!-- Scheduled Scans Section -->
        @if($project->organization->canCreateScheduledScans())
            <section x-data="{ collapsed: @js($collapsedSections['schedules'] ?? false) }">
                <button
                    type="button"
                    wire:click="toggleSection('schedules')"
                    class="flex w-full items-center justify-between rounded-lg bg-white dark:bg-gray-800 px-4 py-3 text-left shadow-sm ring-1 ring-gray-200 dark:ring-gray-700"
                >
                    <div class="flex items-center gap-x-2">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Scheduled Scans</h2>
                        @if($project->scanSchedules()->where('is_active', true)->count() > 0)
                            <span class="inline-flex items-center rounded-full bg-green-100 dark:bg-green-900/30 px-2 py-0.5 text-xs font-medium text-green-700 dark:text-green-400">
                                {{ $project->scanSchedules()->where('is_active', true)->count() }} active
                            </span>
                        @endif
                    </div>
                    <x-ui.icon name="chevron-down" class="size-5 text-gray-500 transition-transform" x-bind:class="collapsed && '-rotate-180'" />
                </button>
                <div x-show="!collapsed" x-collapse class="mt-4">
                    <div class="rounded-lg bg-white dark:bg-gray-800 p-4 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
                        <livewire:projects.components.schedule-modal :project="$project" />
                    </div>
                </div>
            </section>
        @endif
    </div>
</div>
