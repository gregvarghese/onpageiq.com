<div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700">
    <div class="p-4 border-b border-zinc-200 dark:border-zinc-700">
        <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Version History</h3>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">Compare architecture snapshots over time</p>
    </div>

    @if (empty($this->snapshots))
        <div class="p-8 text-center">
            <svg class="mx-auto h-12 w-12 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">No snapshots available</p>
            <p class="text-xs text-zinc-400 dark:text-zinc-500">Run a crawl to create the first snapshot</p>
        </div>
    @else
        {{-- Comparison Mode Banner --}}
        @if ($showComparison && $this->comparison)
            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border-b border-blue-200 dark:border-blue-800">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-sm font-medium text-blue-900 dark:text-blue-100">Comparing Snapshots</span>
                        <p class="text-xs text-blue-700 dark:text-blue-300">
                            {{ $this->comparison['time_between'] }} difference
                        </p>
                    </div>
                    <button
                        wire:click="cancelComparison"
                        class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200"
                    >
                        Cancel
                    </button>
                </div>

                {{-- Summary Stats --}}
                <div class="mt-3 grid grid-cols-4 gap-2">
                    <div class="text-center">
                        <span class="block text-lg font-semibold text-green-600 dark:text-green-400">
                            +{{ $this->comparison['summary']['nodes_added'] }}
                        </span>
                        <span class="text-xs text-zinc-500 dark:text-zinc-400">Added</span>
                    </div>
                    <div class="text-center">
                        <span class="block text-lg font-semibold text-red-600 dark:text-red-400">
                            -{{ $this->comparison['summary']['nodes_removed'] }}
                        </span>
                        <span class="text-xs text-zinc-500 dark:text-zinc-400">Removed</span>
                    </div>
                    <div class="text-center">
                        <span class="block text-lg font-semibold text-blue-600 dark:text-blue-400">
                            +{{ $this->comparison['summary']['links_added'] }}
                        </span>
                        <span class="text-xs text-zinc-500 dark:text-zinc-400">Links+</span>
                    </div>
                    <div class="text-center">
                        <span class="block text-lg font-semibold text-orange-600 dark:text-orange-400">
                            -{{ $this->comparison['summary']['links_removed'] }}
                        </span>
                        <span class="text-xs text-zinc-500 dark:text-zinc-400">Links-</span>
                    </div>
                </div>

                {{-- Highlights --}}
                @if (!empty($this->comparison['highlights']))
                    <div class="mt-3 space-y-1">
                        @foreach ($this->comparison['highlights'] as $highlight)
                            <div class="flex items-start gap-2 text-xs">
                                @if ($highlight['type'] === 'warning')
                                    <svg class="h-4 w-4 text-amber-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                @else
                                    <svg class="h-4 w-4 text-blue-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                @endif
                                <span class="text-zinc-700 dark:text-zinc-300">{{ $highlight['message'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        {{-- Snapshot List --}}
        <div class="divide-y divide-zinc-200 dark:divide-zinc-700 max-h-96 overflow-y-auto">
            @foreach ($this->snapshots as $snapshot)
                <div
                    class="p-3 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 cursor-pointer transition-colors {{ $selectedSnapshotId === $snapshot['id'] ? 'bg-blue-50 dark:bg-blue-900/20' : '' }} {{ $compareSnapshotId === $snapshot['id'] ? 'bg-amber-50 dark:bg-amber-900/20' : '' }}"
                    wire:click="selectSnapshot('{{ $snapshot['id'] }}')"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            {{-- Selection Indicator --}}
                            @if ($selectedSnapshotId === $snapshot['id'])
                                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                            @elseif ($compareSnapshotId === $snapshot['id'])
                                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                            @else
                                <span class="w-2 h-2 rounded-full bg-zinc-300 dark:bg-zinc-600"></span>
                            @endif

                            <div>
                                <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $snapshot['created_at_human'] }}
                                </span>
                                <span class="text-xs text-zinc-500 dark:text-zinc-400 ml-2">
                                    {{ $snapshot['nodes_count'] }} pages, {{ $snapshot['links_count'] }} links
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            {{-- Change indicators --}}
                            @if ($snapshot['has_changes'])
                                @if ($snapshot['added'] > 0)
                                    <span class="text-xs text-green-600 dark:text-green-400">+{{ $snapshot['added'] }}</span>
                                @endif
                                @if ($snapshot['removed'] > 0)
                                    <span class="text-xs text-red-600 dark:text-red-400">-{{ $snapshot['removed'] }}</span>
                                @endif
                            @endif

                            {{-- Compare Button --}}
                            @if ($selectedSnapshotId !== $snapshot['id'] && !$showComparison)
                                <button
                                    wire:click.stop="startComparison('{{ $snapshot['id'] }}')"
                                    class="text-xs px-2 py-1 rounded bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-600"
                                >
                                    Compare
                                </button>
                            @endif

                            {{-- Delete Button --}}
                            @if (count($this->snapshots) > 1)
                                <button
                                    wire:click.stop="deleteSnapshot('{{ $snapshot['id'] }}')"
                                    wire:confirm="Are you sure you want to delete this snapshot?"
                                    class="text-xs px-2 py-1 rounded text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Changed Pages List (when comparing) --}}
        @if ($showComparison && $this->comparison && !empty($this->comparison['changed_pages']))
            <div class="border-t border-zinc-200 dark:border-zinc-700">
                <div class="p-3 bg-zinc-50 dark:bg-zinc-700/50">
                    <h4 class="text-sm font-medium text-zinc-900 dark:text-zinc-100">Changed Pages</h4>
                </div>
                <div class="divide-y divide-zinc-200 dark:divide-zinc-700 max-h-64 overflow-y-auto">
                    @foreach (array_slice($this->comparison['changed_pages'], 0, 20) as $page)
                        <div class="p-2 text-sm">
                            <div class="flex items-center gap-2">
                                @if ($page['change_type'] === 'added')
                                    <span class="px-1.5 py-0.5 text-xs rounded bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300">Added</span>
                                @elseif ($page['change_type'] === 'removed')
                                    <span class="px-1.5 py-0.5 text-xs rounded bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300">Removed</span>
                                @else
                                    <span class="px-1.5 py-0.5 text-xs rounded bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">Modified</span>
                                @endif
                                <span class="text-zinc-900 dark:text-zinc-100 truncate flex-1">{{ $page['url'] }}</span>
                            </div>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1 pl-14">{{ $page['details'] }}</p>
                        </div>
                    @endforeach
                </div>
                @if (count($this->comparison['changed_pages']) > 20)
                    <div class="p-2 text-center text-xs text-zinc-500 dark:text-zinc-400 bg-zinc-50 dark:bg-zinc-700/50">
                        And {{ count($this->comparison['changed_pages']) - 20 }} more changes...
                    </div>
                @endif
            </div>
        @endif
    @endif
</div>
