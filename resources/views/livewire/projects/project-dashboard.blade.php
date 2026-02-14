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
        <!-- Overview Cards (Clickable Filters) -->
        @php $stats = $this->stats; @endphp
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <!-- Typos Found Card -->
            <button
                wire:click="setCardFilter('typos')"
                class="relative rounded-xl p-6 text-left transition-all duration-200 {{ $activeCardFilter === 'typos' ? 'ring-2 ring-red-500 bg-red-50 dark:bg-red-900/20' : 'bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/50' }} shadow-sm ring-1 ring-gray-200 dark:ring-gray-700"
            >
                @if($activeCardFilter === 'typos')
                    <div class="absolute top-3 right-3">
                        <span class="inline-flex items-center rounded-full bg-red-100 dark:bg-red-800 px-2 py-0.5 text-xs font-medium text-red-700 dark:text-red-300">Active</span>
                    </div>
                @endif
                <div class="flex items-center gap-x-3">
                    <div class="flex-shrink-0 rounded-lg bg-red-100 dark:bg-red-900/30 p-3">
                        <x-ui.icon name="exclamation-triangle" class="size-6 text-red-600 dark:text-red-400" />
                    </div>
                    <div>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['totalIssues'] }}</p>
                        <p class="text-sm font-medium text-red-600 dark:text-red-400">Typos Found!</p>
                    </div>
                </div>
                <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">Review website typos and grammar issues. Ignore any false positives.</p>
            </button>

            <!-- Pages Have Issues Card -->
            <button
                wire:click="setCardFilter('issues')"
                class="relative rounded-xl p-6 text-left transition-all duration-200 {{ $activeCardFilter === 'issues' ? 'ring-2 ring-yellow-500 bg-yellow-50 dark:bg-yellow-900/20' : 'bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/50' }} shadow-sm ring-1 ring-gray-200 dark:ring-gray-700"
            >
                @if($activeCardFilter === 'issues')
                    <div class="absolute top-3 right-3">
                        <span class="inline-flex items-center rounded-full bg-yellow-100 dark:bg-yellow-800 px-2 py-0.5 text-xs font-medium text-yellow-700 dark:text-yellow-300">Active</span>
                    </div>
                @endif
                <div class="flex items-center gap-x-3">
                    <div class="flex-shrink-0 rounded-lg bg-yellow-100 dark:bg-yellow-900/30 p-3">
                        <x-ui.icon name="document-magnifying-glass" class="size-6 text-yellow-600 dark:text-yellow-400" />
                    </div>
                    <div>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['pagesWithIssues'] }}</p>
                        <p class="text-sm font-medium text-yellow-600 dark:text-yellow-400">Pages Have Issues</p>
                    </div>
                </div>
                <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">Automate this report to catch new typos right away.</p>
            </button>

            <!-- Pages Look Good Card -->
            <button
                wire:click="setCardFilter('good')"
                class="relative rounded-xl p-6 text-left transition-all duration-200 {{ $activeCardFilter === 'good' ? 'ring-2 ring-green-500 bg-green-50 dark:bg-green-900/20' : 'bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/50' }} shadow-sm ring-1 ring-gray-200 dark:ring-gray-700"
            >
                @if($activeCardFilter === 'good')
                    <div class="absolute top-3 right-3">
                        <span class="inline-flex items-center rounded-full bg-green-100 dark:bg-green-800 px-2 py-0.5 text-xs font-medium text-green-700 dark:text-green-300">Active</span>
                    </div>
                @endif
                <div class="flex items-center gap-x-3">
                    <div class="flex-shrink-0 rounded-lg bg-green-100 dark:bg-green-900/30 p-3">
                        <x-ui.icon name="check-circle" class="size-6 text-green-600 dark:text-green-400" />
                    </div>
                    <div>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['pagesLookGood'] }}</p>
                        <p class="text-sm font-medium text-green-600 dark:text-green-400">Pages Look Good</p>
                    </div>
                </div>
                <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">Get peace of mind on pages that are typo free.</p>
            </button>
        </div>

        <!-- Scan Progress Indicator -->
        @if($stats['scanningUrls'] > 0)
            <div class="rounded-lg bg-blue-50 dark:bg-blue-900/20 p-4 ring-1 ring-blue-200 dark:ring-blue-800">
                <div class="flex items-center gap-x-3">
                    <x-ui.icon name="arrow-path" class="size-5 text-blue-600 dark:text-blue-400 animate-spin" />
                    <p class="text-sm font-medium text-blue-700 dark:text-blue-300">
                        Scanning {{ $stats['scanningUrls'] }}/{{ $stats['totalUrls'] }} pages...
                    </p>
                </div>
            </div>
        @endif

        <!-- Page Status Grids (Success/Warning/Error) -->
        @php
            $successUrls = $urls->filter(fn($url) => $url->status === 'completed' && ($url->latestScan?->result?->issues?->count() ?? 0) === 0);
            $warningUrls = $urls->filter(fn($url) => $url->status === 'completed' && ($url->latestScan?->result?->issues?->count() ?? 0) > 0);
            $errorUrls = $urls->filter(fn($url) => $url->status === 'failed');
        @endphp
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <!-- Successful Pages -->
            <div class="rounded-lg bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 overflow-hidden">
                <div class="flex items-center gap-x-2 px-4 py-3 bg-green-50 dark:bg-green-900/20 border-b border-green-200 dark:border-green-800">
                    <x-ui.icon name="check-circle" class="size-5 text-green-600 dark:text-green-400" />
                    <h3 class="text-sm font-semibold text-green-800 dark:text-green-200">Successful Pages</h3>
                    <span class="ml-auto inline-flex items-center rounded-full bg-green-100 dark:bg-green-800 px-2 py-0.5 text-xs font-medium text-green-700 dark:text-green-300">{{ $successUrls->count() }}</span>
                </div>
                <div class="max-h-48 overflow-y-auto p-2">
                    @forelse($successUrls as $url)
                        <div class="flex items-center justify-between px-2 py-1.5 rounded hover:bg-gray-50 dark:hover:bg-gray-700/50 group">
                            @if($url->latestScan)
                                <a href="{{ route('scans.show', $url->latestScan) }}" class="text-sm text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 truncate flex-1">
                                    {{ parse_url($url->url, PHP_URL_PATH) ?: '/' }}
                                </a>
                            @else
                                <span class="text-sm text-gray-700 dark:text-gray-300 truncate flex-1">
                                    {{ parse_url($url->url, PHP_URL_PATH) ?: '/' }}
                                </span>
                            @endif
                            <a href="{{ $url->url }}" target="_blank" class="opacity-0 group-hover:opacity-100 ml-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-opacity">
                                <x-ui.icon name="arrow-top-right-on-square" class="size-4" />
                            </a>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">No pages yet</p>
                    @endforelse
                </div>
            </div>

            <!-- Warning Pages (Has Issues) -->
            <div class="rounded-lg bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 overflow-hidden">
                <div class="flex items-center gap-x-2 px-4 py-3 bg-yellow-50 dark:bg-yellow-900/20 border-b border-yellow-200 dark:border-yellow-800">
                    <x-ui.icon name="exclamation-triangle" class="size-5 text-yellow-600 dark:text-yellow-400" />
                    <h3 class="text-sm font-semibold text-yellow-800 dark:text-yellow-200">Pages With Issues</h3>
                    <span class="ml-auto inline-flex items-center rounded-full bg-yellow-100 dark:bg-yellow-800 px-2 py-0.5 text-xs font-medium text-yellow-700 dark:text-yellow-300">{{ $warningUrls->count() }}</span>
                </div>
                <div class="max-h-48 overflow-y-auto p-2">
                    @forelse($warningUrls as $url)
                        <div class="flex items-center justify-between px-2 py-1.5 rounded hover:bg-gray-50 dark:hover:bg-gray-700/50 group">
                            @if($url->latestScan)
                                <a href="{{ route('scans.show', $url->latestScan) }}" class="text-sm text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 truncate flex-1">
                                    {{ parse_url($url->url, PHP_URL_PATH) ?: '/' }}
                                    <span class="text-xs text-yellow-600 dark:text-yellow-400 ml-1">({{ $url->latestScan->result->issues->count() }})</span>
                                </a>
                            @else
                                <span class="text-sm text-gray-700 dark:text-gray-300 truncate flex-1">
                                    {{ parse_url($url->url, PHP_URL_PATH) ?: '/' }}
                                </span>
                            @endif
                            <a href="{{ $url->url }}" target="_blank" class="opacity-0 group-hover:opacity-100 ml-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-opacity">
                                <x-ui.icon name="arrow-top-right-on-square" class="size-4" />
                            </a>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">No pages with issues</p>
                    @endforelse
                </div>
            </div>

            <!-- Failed Pages -->
            <div class="rounded-lg bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 overflow-hidden">
                <div class="flex items-center gap-x-2 px-4 py-3 bg-red-50 dark:bg-red-900/20 border-b border-red-200 dark:border-red-800">
                    <x-ui.icon name="x-circle" class="size-5 text-red-600 dark:text-red-400" />
                    <h3 class="text-sm font-semibold text-red-800 dark:text-red-200">Failed Pages</h3>
                    <span class="ml-auto inline-flex items-center rounded-full bg-red-100 dark:bg-red-800 px-2 py-0.5 text-xs font-medium text-red-700 dark:text-red-300">{{ $errorUrls->count() }}</span>
                </div>
                <div class="max-h-48 overflow-y-auto p-2">
                    @forelse($errorUrls as $url)
                        <div class="flex items-center justify-between px-2 py-1.5 rounded hover:bg-gray-50 dark:hover:bg-gray-700/50 group">
                            <span class="text-sm text-gray-700 dark:text-gray-300 truncate flex-1">
                                {{ parse_url($url->url, PHP_URL_PATH) ?: '/' }}
                            </span>
                            <a href="{{ $url->url }}" target="_blank" class="opacity-0 group-hover:opacity-100 ml-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-opacity">
                                <x-ui.icon name="arrow-top-right-on-square" class="size-4" />
                            </a>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">No failed pages</p>
                    @endforelse
                </div>
            </div>
        </div>

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

        <!-- Findings Table Section -->
        <section x-data="{ collapsed: false, showPageFilter: false }">
            <button
                type="button"
                @click="collapsed = !collapsed"
                class="flex w-full items-center justify-between rounded-lg bg-white dark:bg-gray-800 px-4 py-3 text-left shadow-sm ring-1 ring-gray-200 dark:ring-gray-700"
            >
                <div class="flex items-center gap-x-2">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Findings</h2>
                    <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-700 px-2 py-0.5 text-xs font-medium text-gray-700 dark:text-gray-300">
                        {{ $this->findingsCounts['all'] }}
                    </span>
                </div>
                <x-ui.icon name="chevron-down" class="size-5 text-gray-500 transition-transform" x-bind:class="collapsed && '-rotate-180'" />
            </button>
            <div x-show="!collapsed" x-collapse class="mt-4 space-y-4">
                <!-- Filter Bar -->
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <!-- Category Filter Chips -->
                    <div class="flex flex-wrap items-center gap-2">
                        @php $counts = $this->findingsCounts; @endphp
                        <button
                            wire:click="setFindingsFilter('all')"
                            class="inline-flex items-center gap-x-1.5 rounded-full px-3 py-1.5 text-sm font-medium transition-colors {{ $findingsFilter === 'all' ? 'bg-primary-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}"
                        >
                            All
                            <span class="text-xs {{ $findingsFilter === 'all' ? 'text-primary-200' : 'text-gray-500 dark:text-gray-400' }}">{{ $counts['all'] }}</span>
                        </button>
                        <button
                            wire:click="setFindingsFilter('content')"
                            class="inline-flex items-center gap-x-1.5 rounded-full px-3 py-1.5 text-sm font-medium transition-colors {{ $findingsFilter === 'content' ? 'bg-red-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}"
                        >
                            Content
                            <span class="text-xs {{ $findingsFilter === 'content' ? 'text-red-200' : 'text-gray-500 dark:text-gray-400' }}">{{ $counts['content'] }}</span>
                        </button>
                        <button
                            wire:click="setFindingsFilter('accessibility')"
                            class="inline-flex items-center gap-x-1.5 rounded-full px-3 py-1.5 text-sm font-medium transition-colors {{ $findingsFilter === 'accessibility' ? 'bg-purple-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}"
                        >
                            Accessibility
                            <span class="text-xs {{ $findingsFilter === 'accessibility' ? 'text-purple-200' : 'text-gray-500 dark:text-gray-400' }}">{{ $counts['accessibility'] }}</span>
                        </button>
                        <button
                            wire:click="setFindingsFilter('meta')"
                            class="inline-flex items-center gap-x-1.5 rounded-full px-3 py-1.5 text-sm font-medium transition-colors {{ $findingsFilter === 'meta' ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}"
                        >
                            Meta
                            <span class="text-xs {{ $findingsFilter === 'meta' ? 'text-blue-200' : 'text-gray-500 dark:text-gray-400' }}">{{ $counts['meta'] }}</span>
                        </button>
                        <button
                            wire:click="setFindingsFilter('links')"
                            class="inline-flex items-center gap-x-1.5 rounded-full px-3 py-1.5 text-sm font-medium transition-colors {{ $findingsFilter === 'links' ? 'bg-orange-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}"
                        >
                            Links
                            <span class="text-xs {{ $findingsFilter === 'links' ? 'text-orange-200' : 'text-gray-500 dark:text-gray-400' }}">{{ $counts['links'] }}</span>
                        </button>
                    </div>

                    <!-- Page Filter Dropdown -->
                    <div class="relative" x-data="{ open: false }" @click.away="open = false">
                        <button
                            type="button"
                            @click="open = !open"
                            class="inline-flex items-center gap-x-2 rounded-md bg-white dark:bg-gray-800 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700"
                        >
                            <x-ui.icon name="funnel" class="size-4" />
                            Filter by Page
                            @if(count($pageFilter) > 0)
                                <span class="inline-flex items-center rounded-full bg-primary-100 dark:bg-primary-900 px-2 py-0.5 text-xs font-medium text-primary-700 dark:text-primary-300">
                                    {{ count($pageFilter) }}
                                </span>
                            @endif
                        </button>

                        <div
                            x-show="open"
                            x-transition
                            class="absolute right-0 z-10 mt-2 w-80 origin-top-right rounded-lg bg-white dark:bg-gray-800 shadow-lg ring-1 ring-gray-200 dark:ring-gray-700"
                        >
                            <div class="p-3">
                                <input
                                    type="text"
                                    wire:model.live.debounce.300ms="pageSearch"
                                    placeholder="Search pages..."
                                    class="w-full rounded-md border-0 px-3 py-2 text-sm text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-700 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 placeholder:text-gray-400 focus:ring-2 focus:ring-primary-600"
                                />
                            </div>
                            <div class="max-h-60 overflow-y-auto border-t border-gray-200 dark:border-gray-700">
                                @foreach($this->filterableUrls as $filterUrl)
                                    <label class="flex items-center gap-x-3 px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            wire:click="togglePageFilter({{ $filterUrl->id }})"
                                            @checked(in_array($filterUrl->id, $pageFilter))
                                            class="size-4 rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-600"
                                        />
                                        <span class="text-sm text-gray-700 dark:text-gray-300 truncate">
                                            {{ parse_url($filterUrl->url, PHP_URL_PATH) ?: '/' }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            @if(count($pageFilter) > 0)
                                <div class="border-t border-gray-200 dark:border-gray-700 p-2">
                                    <button
                                        wire:click="clearPageFilter"
                                        class="w-full text-center text-sm text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300"
                                    >
                                        Clear filter
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Bulk Actions Bar (shown when items selected) -->
                @if(count($selectedIssues) > 0)
                    <div class="flex items-center justify-between rounded-lg bg-primary-50 dark:bg-primary-900/20 px-4 py-3 ring-1 ring-primary-200 dark:ring-primary-800">
                        <div class="flex items-center gap-x-3">
                            <span class="text-sm font-medium text-primary-700 dark:text-primary-300">
                                {{ count($selectedIssues) }} selected
                            </span>
                            <button wire:click="deselectAllIssues" class="text-sm text-primary-600 dark:text-primary-400 hover:underline">
                                Clear selection
                            </button>
                        </div>
                        <div class="flex items-center gap-x-2">
                            <button
                                wire:click="bulkMarkAsFixed"
                                wire:loading.attr="disabled"
                                wire:target="bulkMarkAsFixed"
                                class="inline-flex items-center gap-x-1.5 rounded-md bg-white dark:bg-gray-800 px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50"
                            >
                                <x-ui.icon name="check" class="size-4" wire:loading.class="animate-spin" wire:target="bulkMarkAsFixed" />
                                Mark Fixed
                            </button>
                            <button
                                wire:click="bulkIgnoreIssues"
                                wire:loading.attr="disabled"
                                wire:target="bulkIgnoreIssues"
                                class="inline-flex items-center gap-x-1.5 rounded-md bg-white dark:bg-gray-800 px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50"
                            >
                                <x-ui.icon name="eye-slash" class="size-4" />
                                Ignore
                            </button>
                            <button
                                wire:click="bulkAddToDictionary"
                                wire:loading.attr="disabled"
                                wire:target="bulkAddToDictionary"
                                class="inline-flex items-center gap-x-1.5 rounded-md bg-white dark:bg-gray-800 px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50"
                            >
                                <x-ui.icon name="book-open" class="size-4" />
                                Add to Dictionary
                            </button>
                        </div>
                    </div>
                @endif

                <!-- Findings Table -->
                <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                    @php $findings = $this->findings; @endphp
                    @if($findings->isEmpty())
                        <div class="text-center py-12">
                            <x-ui.icon name="check-circle" class="mx-auto size-12 text-green-500" />
                            <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">No issues found</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Great job! All pages look clean.</p>
                        </div>
                    @else
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700/50">
                                <tr>
                                    <th scope="col" class="relative w-12 px-4 py-3">
                                        <input
                                            type="checkbox"
                                            wire:click="selectAllIssues"
                                            @checked(count($selectedIssues) === $findings->count() && $findings->count() > 0)
                                            class="size-4 rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-600"
                                        />
                                    </th>
                                    <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Page
                                    </th>
                                    <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Type
                                    </th>
                                    <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Issue
                                    </th>
                                    <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Suggestion
                                    </th>
                                    <th scope="col" class="relative w-12 px-3 py-3">
                                        <span class="sr-only">Actions</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($findings->take(50) as $issue)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 {{ in_array($issue->id, $selectedIssues) ? 'bg-primary-50 dark:bg-primary-900/10' : '' }}">
                                        <td class="relative w-12 px-4 py-3">
                                            <input
                                                type="checkbox"
                                                wire:click="toggleIssueSelection({{ $issue->id }})"
                                                @checked(in_array($issue->id, $selectedIssues))
                                                class="size-4 rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-600"
                                            />
                                        </td>
                                        <td class="px-3 py-3">
                                            @if($issue->result?->scan?->url)
                                                <a href="{{ route('projects.pages.show', ['project' => $project, 'url' => $issue->result->scan->url]) }}" class="text-sm text-primary-600 dark:text-primary-400 hover:underline truncate block max-w-xs">
                                                    {{ parse_url($issue->result->scan->url->url, PHP_URL_PATH) ?: '/' }}
                                                </a>
                                            @else
                                                <span class="text-sm text-gray-500 dark:text-gray-400">Unknown</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3">
                                            @php
                                                $typeColors = [
                                                    'spelling' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                                    'grammar' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                                                    'seo' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                                    'accessibility' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
                                                    'readability' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                                    'links' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
                                                ];
                                                $colorClass = $typeColors[$issue->category] ?? 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
                                            @endphp
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium capitalize {{ $colorClass }}">
                                                {{ $issue->category }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-3">
                                            <p class="text-sm text-gray-900 dark:text-white">{{ Str::limit($issue->message, 60) }}</p>
                                            @if($issue->context)
                                                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400 font-mono truncate max-w-xs">"{{ Str::limit($issue->context, 40) }}"</p>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3">
                                            @if($issue->suggestion)
                                                <div class="flex items-center gap-x-2">
                                                    <span class="text-sm text-primary-600 dark:text-primary-400">{{ Str::limit($issue->suggestion, 30) }}</span>
                                                    <button
                                                        type="button"
                                                        x-data
                                                        x-on:click="navigator.clipboard.writeText('{{ addslashes($issue->suggestion) }}'); $dispatch('notify', { message: 'Copied!' })"
                                                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                                        title="Copy suggestion"
                                                    >
                                                        <x-ui.icon name="clipboard" class="size-4" />
                                                    </button>
                                                </div>
                                            @else
                                                <span class="text-sm text-gray-400 dark:text-gray-500">—</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3 text-right">
                                            <div x-data="{ open: false }" class="relative">
                                                <button
                                                    type="button"
                                                    @click="open = !open"
                                                    class="rounded p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                                                >
                                                    <x-ui.icon name="ellipsis-vertical" class="size-5" />
                                                </button>
                                                <div
                                                    x-show="open"
                                                    @click.away="open = false"
                                                    x-transition
                                                    class="absolute right-0 z-10 mt-1 w-48 origin-top-right rounded-md bg-white dark:bg-gray-800 shadow-lg ring-1 ring-gray-200 dark:ring-gray-700"
                                                >
                                                    <div class="py-1">
                                                        <button
                                                            wire:click="markIssueAsFixed({{ $issue->id }})"
                                                            @click="open = false"
                                                            class="flex w-full items-center gap-x-2 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                                                        >
                                                            <x-ui.icon name="check" class="size-4" />
                                                            Mark as Fixed
                                                        </button>
                                                        <button
                                                            wire:click="ignoreIssue({{ $issue->id }})"
                                                            @click="open = false"
                                                            class="flex w-full items-center gap-x-2 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                                                        >
                                                            <x-ui.icon name="eye-slash" class="size-4" />
                                                            Ignore Issue
                                                        </button>
                                                        @if(in_array($issue->category, ['spelling', 'grammar']))
                                                            <button
                                                                wire:click="addIssueToDictionary({{ $issue->id }})"
                                                                @click="open = false"
                                                                class="flex w-full items-center gap-x-2 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                                                            >
                                                                <x-ui.icon name="book-open" class="size-4" />
                                                                Add to Dictionary
                                                            </button>
                                                        @endif
                                                        <button
                                                            x-data
                                                            @click="$dispatch('open-false-positive-modal', { issueId: {{ $issue->id }} }); open = false"
                                                            class="flex w-full items-center gap-x-2 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                                                        >
                                                            <x-ui.icon name="flag" class="size-4" />
                                                            Report False Positive
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @if($findings->count() > 50)
                            <div class="border-t border-gray-200 dark:border-gray-700 px-4 py-3 text-center">
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Showing 50 of {{ $findings->count() }} issues.
                                    <a href="#" class="text-primary-600 dark:text-primary-400 hover:underline">View all</a>
                                </p>
                            </div>
                        @endif
                    @endif
                </div>
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
