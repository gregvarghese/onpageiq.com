<div
    class="min-h-screen bg-gray-50 dark:bg-gray-900"
    x-data="{ showKeyboardHelp: false }"
    x-on:keydown.window="
        if ($event.target.tagName === 'INPUT' || $event.target.tagName === 'TEXTAREA' || $event.target.tagName === 'SELECT') return;
        switch($event.key) {
            case '1': $wire.setViewMode('force'); break;
            case '2': $wire.setViewMode('tree'); break;
            case '3': $wire.setViewMode('directory'); break;
            case 'e': case 'E': $wire.toggleExternalLinks(); break;
            case 'c': case 'C': $wire.toggleClusters(); break;
            case 'Escape': $wire.selectNode(null); showKeyboardHelp = false; break;
            case '?': showKeyboardHelp = !showKeyboardHelp; break;
        }
    "
    role="main"
    aria-label="Site Architecture Visualization"
>
    {{-- Keyboard Shortcuts Help Modal --}}
    <div
        x-show="showKeyboardHelp"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 dark:bg-gray-900/75"
        x-on:click.self="showKeyboardHelp = false"
        x-on:keydown.escape.window="showKeyboardHelp = false"
        role="dialog"
        aria-modal="true"
        aria-labelledby="keyboard-shortcuts-title"
    >
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 id="keyboard-shortcuts-title" class="text-lg font-semibold text-gray-900 dark:text-white">Keyboard Shortcuts</h2>
                <button x-on:click="showKeyboardHelp = false" class="text-gray-400 hover:text-gray-500" aria-label="Close">
                    <x-ui.icon name="x-mark" class="h-5 w-5" />
                </button>
            </div>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">Switch to Force Graph</dt>
                    <dd class="font-mono text-xs bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-gray-700 dark:text-gray-300">1</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">Switch to Tree View</dt>
                    <dd class="font-mono text-xs bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-gray-700 dark:text-gray-300">2</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">Switch to Directory View</dt>
                    <dd class="font-mono text-xs bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-gray-700 dark:text-gray-300">3</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">Toggle External Links</dt>
                    <dd class="font-mono text-xs bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-gray-700 dark:text-gray-300">E</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">Toggle Clusters</dt>
                    <dd class="font-mono text-xs bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-gray-700 dark:text-gray-300">C</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">Clear Selection / Close Modal</dt>
                    <dd class="font-mono text-xs bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-gray-700 dark:text-gray-300">Esc</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">Show/Hide This Help</dt>
                    <dd class="font-mono text-xs bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-gray-700 dark:text-gray-300">?</dd>
                </div>
            </dl>
            <p class="mt-4 text-xs text-gray-400 dark:text-gray-500">Use +/- or mouse wheel to zoom. Drag to pan.</p>
        </div>
    </div>

    {{-- Project Navigation --}}
    <x-projects.navigation :project="$project" current="architecture" />

    {{-- Header --}}
    <div class="bg-white dark:bg-gray-800 shadow">
        <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Site Architecture</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Visualize and analyze your site's internal linking structure
                    </p>
                </div>
                <div class="flex items-center gap-4">
                    @if($architecture)
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            Last crawled: {{ $architecture->last_crawled_at?->diffForHumans() ?? 'Never' }}
                        </span>
                        {{-- Export Button --}}
                        <button
                            wire:click="$dispatch('open-export-modal', { architectureId: '{{ $architecture->id }}' })"
                            class="inline-flex items-center gap-2 rounded-md bg-white dark:bg-gray-700 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600"
                        >
                            <x-ui.icon name="arrow-down-tray" class="h-4 w-4" />
                            Export
                        </button>
                    @endif
                    <button
                        wire:click="startCrawl"
                        class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                        {{ $architecture ? 'Re-crawl' : 'Start Crawl' }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Export Modal --}}
    <livewire:site-architecture.export-config-modal />

    {{-- Crawl Progress Indicator --}}
    @if($isCrawling)
        <div class="bg-indigo-50 dark:bg-indigo-900/30 border-b border-indigo-200 dark:border-indigo-800">
            <div class="mx-auto max-w-7xl px-4 py-3 sm:px-6 lg:px-8">
                <div class="flex items-center gap-4">
                    <div class="flex-shrink-0">
                        <svg class="animate-spin h-5 w-5 text-indigo-600 dark:text-indigo-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between mb-1">
                            <p class="text-sm font-medium text-indigo-700 dark:text-indigo-300">
                                Crawling site architecture...
                            </p>
                            <p class="text-sm text-indigo-600 dark:text-indigo-400">
                                {{ $crawledPages }} / {{ $totalDiscovered }} pages ({{ $crawlProgressPercent }}%)
                            </p>
                        </div>
                        <div class="w-full bg-indigo-200 dark:bg-indigo-800 rounded-full h-2">
                            <div
                                class="bg-indigo-600 dark:bg-indigo-500 h-2 rounded-full transition-all duration-300"
                                style="width: {{ $crawlProgressPercent }}%"
                            ></div>
                        </div>
                        @if($currentCrawlUrl)
                            <p class="mt-1 text-xs text-indigo-500 dark:text-indigo-400 truncate">
                                {{ $currentCrawlUrl }}
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($architecture)
        {{-- Toolbar --}}
        <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
            <div class="mx-auto max-w-7xl px-4 py-3 sm:px-6 lg:px-8">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    {{-- View Mode Switcher --}}
                    <div class="flex items-center gap-2">
                        <span id="view-mode-label" class="text-sm font-medium text-gray-700 dark:text-gray-300">View:</span>
                        <div class="inline-flex rounded-lg border border-gray-300 dark:border-gray-600" role="group" aria-labelledby="view-mode-label">
                            <button
                                wire:click="setViewMode('force')"
                                @class([
                                    'px-4 py-2 text-sm font-medium rounded-l-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:z-10',
                                    'bg-indigo-600 text-white' => $viewMode === 'force',
                                    'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600' => $viewMode !== 'force',
                                ])
                                aria-pressed="{{ $viewMode === 'force' ? 'true' : 'false' }}"
                                aria-label="Force Graph view (press 1)"
                            >
                                Force Graph
                            </button>
                            <button
                                wire:click="setViewMode('tree')"
                                @class([
                                    'px-4 py-2 text-sm font-medium border-l border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:z-10',
                                    'bg-indigo-600 text-white' => $viewMode === 'tree',
                                    'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600' => $viewMode !== 'tree',
                                ])
                                aria-pressed="{{ $viewMode === 'tree' ? 'true' : 'false' }}"
                                aria-label="Tree view (press 2)"
                            >
                                Tree
                            </button>
                            <button
                                wire:click="setViewMode('directory')"
                                @class([
                                    'px-4 py-2 text-sm font-medium rounded-r-lg border-l border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:z-10',
                                    'bg-indigo-600 text-white' => $viewMode === 'directory',
                                    'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600' => $viewMode !== 'directory',
                                ])
                                aria-pressed="{{ $viewMode === 'directory' ? 'true' : 'false' }}"
                                aria-label="Directory view (press 3)"
                            >
                                Directory
                            </button>
                        </div>
                    </div>

                    {{-- Options --}}
                    <div class="flex items-center gap-4" role="group" aria-label="Display options">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                type="checkbox"
                                wire:click="toggleExternalLinks"
                                @checked($showExternalLinks)
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-600"
                                aria-describedby="external-links-hint"
                            >
                            <span id="external-links-hint" class="text-sm text-gray-700 dark:text-gray-300">Show External Links <kbd class="hidden sm:inline-block text-xs text-gray-400 ml-1">(E)</kbd></span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                type="checkbox"
                                wire:click="toggleClusters"
                                @checked($showClusters)
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-600"
                                aria-describedby="clusters-hint"
                            >
                            <span id="clusters-hint" class="text-sm text-gray-700 dark:text-gray-300">Show Clusters <kbd class="hidden sm:inline-block text-xs text-gray-400 ml-1">(C)</kbd></span>
                        </label>
                        @if($showClusters)
                            <select
                                wire:model.live="clusterStrategy"
                                class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                            >
                                <option value="path">By Path</option>
                                <option value="depth">By Depth</option>
                                <option value="content_type">By Content Type</option>
                                <option value="link_density">By Link Density</option>
                            </select>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Main Content --}}
        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                {{-- Statistics Panel --}}
                <div class="lg:col-span-1 space-y-6">
                    {{-- Summary Stats --}}
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Statistics</h3>
                        <dl class="space-y-3">
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500 dark:text-gray-400">Total Pages</dt>
                                <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ number_format($this->statistics['totalNodes'] ?? 0) }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500 dark:text-gray-400">Total Links</dt>
                                <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ number_format($this->statistics['totalLinks'] ?? 0) }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500 dark:text-gray-400">Max Depth</dt>
                                <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $this->statistics['maxDepth'] ?? 0 }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500 dark:text-gray-400">Orphan Pages</dt>
                                <dd class="text-sm font-medium {{ ($this->statistics['orphanCount'] ?? 0) > 0 ? 'text-amber-600' : 'text-gray-900 dark:text-white' }}">
                                    {{ $this->statistics['orphanCount'] ?? 0 }}
                                </dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500 dark:text-gray-400">Error Pages</dt>
                                <dd class="text-sm font-medium {{ ($this->statistics['errorCount'] ?? 0) > 0 ? 'text-red-600' : 'text-gray-900 dark:text-white' }}">
                                    {{ $this->statistics['errorCount'] ?? 0 }}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    {{-- Depth Score --}}
                    @if(isset($this->statistics['depthScore']))
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Depth Score</h3>
                            <div class="text-center">
                                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full
                                    {{ match($this->statistics['depthScore']['grade'] ?? 'C') {
                                        'A' => 'bg-green-100 text-green-800',
                                        'B' => 'bg-blue-100 text-blue-800',
                                        'C' => 'bg-yellow-100 text-yellow-800',
                                        'D' => 'bg-orange-100 text-orange-800',
                                        default => 'bg-red-100 text-red-800',
                                    } }}">
                                    <span class="text-2xl font-bold">{{ $this->statistics['depthScore']['grade'] ?? '-' }}</span>
                                </div>
                                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $this->statistics['depthScore']['message'] ?? '' }}</p>
                            </div>
                        </div>
                    @endif

                    {{-- Selected Node Details --}}
                    @if($this->selectedNode)
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Selected Page</h3>
                            <div class="space-y-4">
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate" title="{{ $this->selectedNode['node']->url }}">
                                        {{ $this->selectedNode['node']->getDisplayName() }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $this->selectedNode['node']->path }}</p>
                                </div>
                                <dl class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <dt class="text-gray-500 dark:text-gray-400">Depth</dt>
                                        <dd class="font-medium text-gray-900 dark:text-white">{{ $this->selectedNode['node']->depth ?? 'N/A' }}</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-gray-500 dark:text-gray-400">Inbound Links</dt>
                                        <dd class="font-medium text-gray-900 dark:text-white">{{ $this->selectedNode['node']->inbound_count }}</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-gray-500 dark:text-gray-400">Outbound Links</dt>
                                        <dd class="font-medium text-gray-900 dark:text-white">{{ $this->selectedNode['node']->outbound_count }}</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-gray-500 dark:text-gray-400">Link Equity</dt>
                                        <dd class="font-medium text-gray-900 dark:text-white">{{ number_format($this->selectedNode['node']->link_equity_score ?? 0, 1) }}</dd>
                                    </div>
                                </dl>
                                <button
                                    wire:click="selectNode(null)"
                                    class="w-full text-sm text-indigo-600 hover:text-indigo-500"
                                >
                                    Clear Selection
                                </button>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Visualization --}}
                <div class="lg:col-span-3">
                    <div
                        class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden"
                        x-data="siteArchitectureGraph({
                            viewMode: @js($viewMode),
                            graphData: @js($this->graphData),
                            clusterData: @js($this->clusterData),
                            showClusters: @js($showClusters),
                        })"
                        x-on:node-selected.window="$wire.selectNode($event.detail.nodeId)"
                        wire:ignore
                    >
                        <div class="relative" style="height: 600px;">
                            {{-- D3 Canvas --}}
                            <svg x-ref="svg" class="w-full h-full"></svg>

                            {{-- Zoom Controls --}}
                            <div class="absolute bottom-4 right-4 flex flex-col gap-2" role="group" aria-label="Zoom controls">
                                <button
                                    x-on:click="zoomIn()"
                                    class="p-2 bg-white dark:bg-gray-700 rounded-lg shadow hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                    title="Zoom In (+)"
                                    aria-label="Zoom in"
                                >
                                    <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                    </svg>
                                </button>
                                <button
                                    x-on:click="zoomOut()"
                                    class="p-2 bg-white dark:bg-gray-700 rounded-lg shadow hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                    title="Zoom Out (-)"
                                    aria-label="Zoom out"
                                >
                                    <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15" />
                                    </svg>
                                </button>
                                <button
                                    x-on:click="resetZoom()"
                                    class="p-2 bg-white dark:bg-gray-700 rounded-lg shadow hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                    title="Reset View (0)"
                                    aria-label="Reset view"
                                >
                                    <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 9V4.5M9 9H4.5M9 9L3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5M15 15v4.5m0-4.5l5.25 5.25" />
                                    </svg>
                                </button>
                                <div class="border-t border-gray-200 dark:border-gray-600 my-1"></div>
                                <button
                                    x-on:click="showKeyboardHelp = true"
                                    class="p-2 bg-white dark:bg-gray-700 rounded-lg shadow hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                    title="Keyboard Shortcuts (?)"
                                    aria-label="Show keyboard shortcuts"
                                >
                                    <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z" />
                                    </svg>
                                </button>
                            </div>

                            {{-- Legend --}}
                            <div class="absolute top-4 left-4 bg-white dark:bg-gray-700 rounded-lg shadow p-3">
                                <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-2">Link Types</h4>
                                <div class="space-y-1 text-xs">
                                    <div class="flex items-center gap-2">
                                        <span class="w-4 h-0.5 bg-blue-500"></span>
                                        <span class="text-gray-600 dark:text-gray-300">Navigation</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="w-4 h-0.5 bg-green-500"></span>
                                        <span class="text-gray-600 dark:text-gray-300">Content</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="w-4 h-0.5 bg-gray-400"></span>
                                        <span class="text-gray-600 dark:text-gray-300">Footer</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="w-4 h-0.5 bg-purple-500"></span>
                                        <span class="text-gray-600 dark:text-gray-300">Sidebar</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="w-4 h-0.5 bg-amber-500"></span>
                                        <span class="text-gray-600 dark:text-gray-300">External</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        {{-- No Architecture State --}}
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                </svg>
                <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">No site architecture</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by crawling your site to visualize its structure.</p>
                <div class="mt-6">
                    <button
                        wire:click="startCrawl"
                        class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Start Architecture Crawl
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script type="module">
import * as d3 from 'd3';

window.siteArchitectureGraph = function(config) {
    return {
        viewMode: config.viewMode,
        graphData: config.graphData,
        clusterData: config.clusterData,
        showClusters: config.showClusters,
        simulation: null,
        svg: null,
        g: null,
        zoom: null,

        init() {
            this.svg = d3.select(this.$refs.svg);
            this.setupGraph();

            this.$watch('viewMode', () => this.setupGraph());
            this.$watch('graphData', () => this.setupGraph());
            this.$watch('showClusters', () => this.setupGraph());
        },

        setupGraph() {
            this.svg.selectAll('*').remove();

            const width = this.$refs.svg.clientWidth;
            const height = this.$refs.svg.clientHeight;

            this.zoom = d3.zoom()
                .scaleExtent([0.1, 4])
                .on('zoom', (event) => {
                    this.g.attr('transform', event.transform);
                });

            this.svg.call(this.zoom);

            this.g = this.svg.append('g');

            if (this.showClusters && this.clusterData.clusters?.length > 0) {
                this.renderClusters(width, height);
            } else if (this.viewMode === 'force') {
                this.renderForceGraph(width, height);
            } else if (this.viewMode === 'tree') {
                this.renderTreeGraph(width, height);
            } else {
                this.renderDirectoryView(width, height);
            }
        },

        renderForceGraph(width, height) {
            const nodes = this.graphData.nodes || [];
            const links = this.graphData.links || [];

            if (nodes.length === 0) return;

            // Create simulation
            this.simulation = d3.forceSimulation(nodes)
                .force('link', d3.forceLink(links).id(d => d.id).distance(80))
                .force('charge', d3.forceManyBody().strength(-200))
                .force('center', d3.forceCenter(width / 2, height / 2))
                .force('collision', d3.forceCollide().radius(20));

            // Draw links
            const link = this.g.append('g')
                .attr('class', 'links')
                .selectAll('line')
                .data(links)
                .enter()
                .append('line')
                .attr('stroke', d => d.color || '#999')
                .attr('stroke-opacity', 0.6)
                .attr('stroke-width', 1.5);

            // Draw nodes
            const node = this.g.append('g')
                .attr('class', 'nodes')
                .selectAll('circle')
                .data(nodes)
                .enter()
                .append('circle')
                .attr('r', d => Math.max(5, Math.min(15, (d.link_equity || 50) / 10)))
                .attr('fill', d => this.getNodeColor(d))
                .attr('stroke', '#fff')
                .attr('stroke-width', 1.5)
                .style('cursor', 'pointer')
                .call(this.drag(this.simulation))
                .on('click', (event, d) => {
                    this.$dispatch('node-selected', { nodeId: d.id });
                });

            // Add tooltips
            node.append('title')
                .text(d => d.label || d.url);

            // Update positions on tick
            this.simulation.on('tick', () => {
                link
                    .attr('x1', d => d.source.x)
                    .attr('y1', d => d.source.y)
                    .attr('x2', d => d.target.x)
                    .attr('y2', d => d.target.y);

                node
                    .attr('cx', d => d.x)
                    .attr('cy', d => d.y);
            });
        },

        renderTreeGraph(width, height) {
            const nodes = this.graphData.nodes || [];
            if (nodes.length === 0) return;

            // Group nodes by depth
            const nodesByDepth = d3.group(nodes, d => d.depth || 0);
            const maxDepth = Math.max(...nodes.map(d => d.depth || 0));

            const levelHeight = height / (maxDepth + 2);

            nodesByDepth.forEach((levelNodes, depth) => {
                const levelWidth = width / (levelNodes.length + 1);
                levelNodes.forEach((node, i) => {
                    node.x = (i + 1) * levelWidth;
                    node.y = (depth + 1) * levelHeight;
                });
            });

            // Draw links
            const links = this.graphData.links || [];
            this.g.append('g')
                .attr('class', 'links')
                .selectAll('line')
                .data(links)
                .enter()
                .append('line')
                .attr('x1', d => this.findNode(d.source)?.x || 0)
                .attr('y1', d => this.findNode(d.source)?.y || 0)
                .attr('x2', d => this.findNode(d.target)?.x || 0)
                .attr('y2', d => this.findNode(d.target)?.y || 0)
                .attr('stroke', d => d.color || '#999')
                .attr('stroke-opacity', 0.4)
                .attr('stroke-width', 1);

            // Draw nodes
            this.g.append('g')
                .attr('class', 'nodes')
                .selectAll('circle')
                .data(nodes)
                .enter()
                .append('circle')
                .attr('cx', d => d.x)
                .attr('cy', d => d.y)
                .attr('r', 8)
                .attr('fill', d => this.getNodeColor(d))
                .attr('stroke', '#fff')
                .attr('stroke-width', 1.5)
                .style('cursor', 'pointer')
                .on('click', (event, d) => {
                    this.$dispatch('node-selected', { nodeId: d.id });
                })
                .append('title')
                .text(d => d.label || d.url);
        },

        renderDirectoryView(width, height) {
            const nodes = this.graphData.nodes || [];
            if (nodes.length === 0) return;

            // Group by first path segment
            const grouped = d3.group(nodes, d => {
                const path = d.path || '/';
                const segments = path.split('/').filter(Boolean);
                return segments[0] || 'root';
            });

            const padding = 20;
            const boxWidth = 200;
            const boxHeight = 30;
            let y = padding;

            grouped.forEach((groupNodes, groupName) => {
                // Draw group header
                this.g.append('rect')
                    .attr('x', padding)
                    .attr('y', y)
                    .attr('width', width - padding * 2)
                    .attr('height', boxHeight)
                    .attr('fill', '#f3f4f6')
                    .attr('rx', 4);

                this.g.append('text')
                    .attr('x', padding + 10)
                    .attr('y', y + 20)
                    .attr('font-size', '14px')
                    .attr('font-weight', 'bold')
                    .text(`/${groupName} (${groupNodes.length} pages)`);

                y += boxHeight + 5;

                // Draw pages (limit to 10 per group)
                groupNodes.slice(0, 10).forEach(node => {
                    this.g.append('rect')
                        .attr('x', padding + 20)
                        .attr('y', y)
                        .attr('width', width - padding * 2 - 40)
                        .attr('height', 24)
                        .attr('fill', '#fff')
                        .attr('stroke', '#e5e7eb')
                        .attr('rx', 2)
                        .style('cursor', 'pointer')
                        .on('click', () => {
                            this.$dispatch('node-selected', { nodeId: node.id });
                        });

                    this.g.append('text')
                        .attr('x', padding + 30)
                        .attr('y', y + 16)
                        .attr('font-size', '12px')
                        .attr('fill', '#374151')
                        .text(node.label || node.path || node.url);

                    y += 28;
                });

                if (groupNodes.length > 10) {
                    this.g.append('text')
                        .attr('x', padding + 30)
                        .attr('y', y + 16)
                        .attr('font-size', '12px')
                        .attr('fill', '#6b7280')
                        .attr('font-style', 'italic')
                        .text(`... and ${groupNodes.length - 10} more`);
                    y += 28;
                }

                y += 10;
            });
        },

        renderClusters(width, height) {
            const clusters = this.clusterData.clusters || [];
            if (clusters.length === 0) return;

            // Simple grid layout for clusters
            const cols = Math.ceil(Math.sqrt(clusters.length));
            const cellWidth = width / cols;
            const cellHeight = height / Math.ceil(clusters.length / cols);

            clusters.forEach((cluster, i) => {
                const col = i % cols;
                const row = Math.floor(i / cols);
                const cx = col * cellWidth + cellWidth / 2;
                const cy = row * cellHeight + cellHeight / 2;

                this.g.append('circle')
                    .attr('cx', cx)
                    .attr('cy', cy)
                    .attr('r', cluster.radius || 40)
                    .attr('fill', '#e0e7ff')
                    .attr('stroke', '#6366f1')
                    .attr('stroke-width', 2);

                this.g.append('text')
                    .attr('x', cx)
                    .attr('y', cy)
                    .attr('text-anchor', 'middle')
                    .attr('dominant-baseline', 'middle')
                    .attr('font-size', '12px')
                    .attr('font-weight', 'bold')
                    .text(cluster.label);

                this.g.append('text')
                    .attr('x', cx)
                    .attr('y', cy + 15)
                    .attr('text-anchor', 'middle')
                    .attr('font-size', '10px')
                    .attr('fill', '#6b7280')
                    .text(`${cluster.node_count} pages`);
            });
        },

        findNode(id) {
            return this.graphData.nodes?.find(n => n.id === id);
        },

        getNodeColor(node) {
            if (node.type === 'external_domain') return '#f59e0b';
            if (node.is_orphan) return '#ef4444';
            if (node.is_deep) return '#f97316';
            if (node.depth === 0) return '#10b981';
            return '#3b82f6';
        },

        drag(simulation) {
            return d3.drag()
                .on('start', (event, d) => {
                    if (!event.active) simulation.alphaTarget(0.3).restart();
                    d.fx = d.x;
                    d.fy = d.y;
                })
                .on('drag', (event, d) => {
                    d.fx = event.x;
                    d.fy = event.y;
                })
                .on('end', (event, d) => {
                    if (!event.active) simulation.alphaTarget(0);
                    d.fx = null;
                    d.fy = null;
                });
        },

        zoomIn() {
            this.svg.transition().call(this.zoom.scaleBy, 1.5);
        },

        zoomOut() {
            this.svg.transition().call(this.zoom.scaleBy, 0.67);
        },

        resetZoom() {
            this.svg.transition().call(this.zoom.transform, d3.zoomIdentity);
        }
    };
};
</script>
@endpush
