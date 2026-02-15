<div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700">
    {{-- Header with View Mode Toggle --}}
    <div class="p-4 border-b border-zinc-200 dark:border-zinc-700">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Comparison View</h3>

            {{-- View Mode Tabs --}}
            <div class="flex items-center gap-1 bg-zinc-100 dark:bg-zinc-700 rounded-lg p-1">
                <button
                    wire:click="setViewMode('side-by-side')"
                    class="px-3 py-1.5 text-sm rounded-md transition-colors {{ $viewMode === 'side-by-side' ? 'bg-white dark:bg-zinc-600 text-zinc-900 dark:text-zinc-100 shadow-sm' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100' }}"
                >
                    Side by Side
                </button>
                <button
                    wire:click="setViewMode('overlay')"
                    class="px-3 py-1.5 text-sm rounded-md transition-colors {{ $viewMode === 'overlay' ? 'bg-white dark:bg-zinc-600 text-zinc-900 dark:text-zinc-100 shadow-sm' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100' }}"
                >
                    Overlay Diff
                </button>
                <button
                    wire:click="setViewMode('timeline')"
                    class="px-3 py-1.5 text-sm rounded-md transition-colors {{ $viewMode === 'timeline' ? 'bg-white dark:bg-zinc-600 text-zinc-900 dark:text-zinc-100 shadow-sm' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100' }}"
                >
                    Timeline
                </button>
            </div>
        </div>

        {{-- Snapshot Selection --}}
        @if ($this->baseSnapshot && $this->targetSnapshot)
            <div class="mt-3 flex items-center gap-4 text-sm">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-amber-500"></span>
                    <span class="text-zinc-600 dark:text-zinc-400">Base:</span>
                    <span class="text-zinc-900 dark:text-zinc-100">{{ $this->baseSnapshot->created_at->format('M d, Y H:i') }}</span>
                </div>
                <button
                    wire:click="swapSnapshots"
                    class="p-1 rounded hover:bg-zinc-100 dark:hover:bg-zinc-700 text-zinc-500"
                    title="Swap snapshots"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                    </svg>
                </button>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-blue-500"></span>
                    <span class="text-zinc-600 dark:text-zinc-400">Target:</span>
                    <span class="text-zinc-900 dark:text-zinc-100">{{ $this->targetSnapshot->created_at->format('M d, Y H:i') }}</span>
                </div>
            </div>
        @endif
    </div>

    @if (!$this->baseSnapshot || !$this->targetSnapshot)
        {{-- No Comparison Selected --}}
        <div class="p-8 text-center">
            <svg class="mx-auto h-12 w-12 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Select two snapshots to compare</p>
            <p class="text-xs text-zinc-400 dark:text-zinc-500">Use the Version History panel to select snapshots</p>
        </div>
    @else
        {{-- Filter Controls (for Overlay mode) --}}
        @if ($viewMode === 'overlay')
            <div class="px-4 py-2 border-b border-zinc-200 dark:border-zinc-700 flex items-center gap-4">
                <span class="text-sm text-zinc-500 dark:text-zinc-400">Show:</span>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input
                        type="checkbox"
                        wire:click="toggleFilter('added')"
                        {{ $showAddedNodes ? 'checked' : '' }}
                        class="rounded border-zinc-300 dark:border-zinc-600 text-green-600 focus:ring-green-500"
                    >
                    <span class="text-sm text-green-600 dark:text-green-400">Added</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input
                        type="checkbox"
                        wire:click="toggleFilter('removed')"
                        {{ $showRemovedNodes ? 'checked' : '' }}
                        class="rounded border-zinc-300 dark:border-zinc-600 text-red-600 focus:ring-red-500"
                    >
                    <span class="text-sm text-red-600 dark:text-red-400">Removed</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input
                        type="checkbox"
                        wire:click="toggleFilter('changed')"
                        {{ $showChangedNodes ? 'checked' : '' }}
                        class="rounded border-zinc-300 dark:border-zinc-600 text-blue-600 focus:ring-blue-500"
                    >
                    <span class="text-sm text-blue-600 dark:text-blue-400">Changed</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input
                        type="checkbox"
                        wire:click="toggleFilter('unchanged')"
                        {{ $showUnchangedNodes ? 'checked' : '' }}
                        class="rounded border-zinc-300 dark:border-zinc-600 text-zinc-600 focus:ring-zinc-500"
                    >
                    <span class="text-sm text-zinc-600 dark:text-zinc-400">Unchanged</span>
                </label>
            </div>
        @endif

        {{-- View Content --}}
        <div class="p-4">
            @if ($viewMode === 'side-by-side')
                {{-- Side by Side View --}}
                <div class="grid grid-cols-2 gap-4">
                    {{-- Base Snapshot --}}
                    <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg overflow-hidden">
                        <div class="px-3 py-2 bg-amber-50 dark:bg-amber-900/20 border-b border-zinc-200 dark:border-zinc-700">
                            <span class="text-sm font-medium text-amber-800 dark:text-amber-200">Base Snapshot</span>
                            <span class="text-xs text-amber-600 dark:text-amber-400 ml-2">
                                {{ $this->baseSnapshot->created_at->format('M d, Y H:i') }}
                            </span>
                        </div>
                        <div class="p-4 bg-zinc-50 dark:bg-zinc-900 min-h-[300px]">
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-zinc-500 dark:text-zinc-400">Nodes:</span>
                                    <span class="font-medium text-zinc-900 dark:text-zinc-100 ml-1">{{ $this->baseSnapshot->nodes_count }}</span>
                                </div>
                                <div>
                                    <span class="text-zinc-500 dark:text-zinc-400">Links:</span>
                                    <span class="font-medium text-zinc-900 dark:text-zinc-100 ml-1">{{ $this->baseSnapshot->links_count }}</span>
                                </div>
                            </div>
                            {{-- Graph visualization would go here --}}
                            <div class="mt-4 h-48 bg-zinc-200 dark:bg-zinc-800 rounded flex items-center justify-center text-zinc-400">
                                <span class="text-xs">Base Graph Visualization</span>
                            </div>
                        </div>
                    </div>

                    {{-- Target Snapshot --}}
                    <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg overflow-hidden">
                        <div class="px-3 py-2 bg-blue-50 dark:bg-blue-900/20 border-b border-zinc-200 dark:border-zinc-700">
                            <span class="text-sm font-medium text-blue-800 dark:text-blue-200">Target Snapshot</span>
                            <span class="text-xs text-blue-600 dark:text-blue-400 ml-2">
                                {{ $this->targetSnapshot->created_at->format('M d, Y H:i') }}
                            </span>
                        </div>
                        <div class="p-4 bg-zinc-50 dark:bg-zinc-900 min-h-[300px]">
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-zinc-500 dark:text-zinc-400">Nodes:</span>
                                    <span class="font-medium text-zinc-900 dark:text-zinc-100 ml-1">{{ $this->targetSnapshot->nodes_count }}</span>
                                </div>
                                <div>
                                    <span class="text-zinc-500 dark:text-zinc-400">Links:</span>
                                    <span class="font-medium text-zinc-900 dark:text-zinc-100 ml-1">{{ $this->targetSnapshot->links_count }}</span>
                                </div>
                            </div>
                            {{-- Graph visualization would go here --}}
                            <div class="mt-4 h-48 bg-zinc-200 dark:bg-zinc-800 rounded flex items-center justify-center text-zinc-400">
                                <span class="text-xs">Target Graph Visualization</span>
                            </div>
                        </div>
                    </div>
                </div>

            @elseif ($viewMode === 'overlay')
                {{-- Overlay Diff View --}}
                <div
                    class="border border-zinc-200 dark:border-zinc-700 rounded-lg bg-zinc-50 dark:bg-zinc-900 min-h-[400px]"
                    x-data="comparisonGraph(@js($this->graphData))"
                >
                    {{-- Legend --}}
                    <div class="absolute top-4 left-4 bg-white dark:bg-zinc-800 rounded-lg shadow-lg p-3 z-10">
                        <div class="text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-2">Legend</div>
                        <div class="space-y-1.5">
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-green-500"></span>
                                <span class="text-xs text-zinc-600 dark:text-zinc-400">Added</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-red-500"></span>
                                <span class="text-xs text-zinc-600 dark:text-zinc-400">Removed</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-blue-500"></span>
                                <span class="text-xs text-zinc-600 dark:text-zinc-400">Changed</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-zinc-400"></span>
                                <span class="text-xs text-zinc-600 dark:text-zinc-400">Unchanged</span>
                            </div>
                        </div>
                    </div>

                    {{-- Graph Container --}}
                    <div class="w-full h-[400px] flex items-center justify-center text-zinc-400">
                        <span class="text-sm">Overlay Diff Visualization</span>
                        <span class="text-xs ml-2">({{ count($this->graphData['nodes']) }} nodes visible)</span>
                    </div>
                </div>

            @elseif ($viewMode === 'timeline')
                {{-- Timeline Slider View --}}
                <div class="space-y-4">
                    {{-- Timeline Slider --}}
                    <div class="px-4">
                        <label class="block text-sm text-zinc-600 dark:text-zinc-400 mb-2">Timeline Position</label>
                        <input
                            type="range"
                            min="0"
                            max="100"
                            wire:model.live="timelinePosition"
                            class="w-full h-2 bg-zinc-200 rounded-lg appearance-none cursor-pointer dark:bg-zinc-700"
                        >
                        <div class="flex justify-between text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                            <span>{{ $this->baseSnapshot?->created_at->format('M d') }}</span>
                            <span>{{ $timelinePosition }}%</span>
                            <span>{{ $this->targetSnapshot?->created_at->format('M d') }}</span>
                        </div>
                    </div>

                    {{-- Timeline Events --}}
                    @if (!empty($this->timelineData))
                        <div class="relative">
                            <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-zinc-200 dark:bg-zinc-700"></div>
                            <div class="space-y-4 pl-8">
                                @foreach ($this->timelineData as $event)
                                    <div class="relative">
                                        <div class="absolute -left-4 top-1 w-2 h-2 rounded-full {{ $event['changes'] ? 'bg-blue-500' : 'bg-zinc-400' }}"></div>
                                        <div class="bg-zinc-50 dark:bg-zinc-700/50 rounded-lg p-3">
                                            <div class="flex items-center justify-between">
                                                <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                                    {{ \Carbon\Carbon::parse($event['created_at'])->format('M d, Y H:i') }}
                                                </span>
                                                <span class="text-xs text-zinc-500 dark:text-zinc-400">
                                                    {{ $event['nodes_count'] }} pages
                                                </span>
                                            </div>
                                            @if ($event['changes'])
                                                <div class="mt-1 flex items-center gap-3 text-xs">
                                                    @if ($event['changes']['nodes_added'] > 0)
                                                        <span class="text-green-600 dark:text-green-400">+{{ $event['changes']['nodes_added'] }} added</span>
                                                    @endif
                                                    @if ($event['changes']['nodes_removed'] > 0)
                                                        <span class="text-red-600 dark:text-red-400">-{{ $event['changes']['nodes_removed'] }} removed</span>
                                                    @endif
                                                </div>
                                            @else
                                                <div class="mt-1 text-xs text-zinc-400 dark:text-zinc-500">Initial snapshot</div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        {{-- Metrics Comparison --}}
        @if ($this->comparison && $viewMode !== 'timeline')
            <div class="px-4 pb-4">
                <div class="border-t border-zinc-200 dark:border-zinc-700 pt-4">
                    <h4 class="text-sm font-medium text-zinc-900 dark:text-zinc-100 mb-3">Metric Changes</h4>
                    <div class="grid grid-cols-5 gap-4">
                        @foreach ($this->comparison['metrics'] as $metric => $data)
                            <div class="text-center">
                                <div class="text-lg font-semibold {{ $data['diff'] > 0 ? 'text-green-600 dark:text-green-400' : ($data['diff'] < 0 ? 'text-red-600 dark:text-red-400' : 'text-zinc-600 dark:text-zinc-400') }}">
                                    {{ $data['diff'] > 0 ? '+' : '' }}{{ $data['diff'] }}
                                </div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ str_replace('_', ' ', ucfirst($metric)) }}
                                </div>
                                @if ($data['percent_change'] !== null)
                                    <div class="text-xs {{ $data['percent_change'] > 0 ? 'text-green-500' : ($data['percent_change'] < 0 ? 'text-red-500' : 'text-zinc-400') }}">
                                        {{ $data['percent_change'] > 0 ? '+' : '' }}{{ $data['percent_change'] }}%
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
