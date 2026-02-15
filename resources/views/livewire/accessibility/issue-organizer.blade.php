<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Issue Organization</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ $this->totalIssues }} issues in {{ $this->groupCount }} groups
            </p>
        </div>

        {{-- Search --}}
        <div class="relative">
            <input type="text" wire:model.live.debounce.300ms="searchQuery" placeholder="Search issues..." class="w-64 rounded-md border-gray-300 pl-10 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400">
            <svg class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
            </svg>
        </div>
    </div>

    {{-- View Selector --}}
    <div class="flex flex-wrap gap-2 rounded-lg bg-gray-100 p-1 dark:bg-gray-800">
        @foreach($this->viewOptions as $view => $option)
            <button
                wire:click="setView('{{ $view }}')"
                class="flex items-center gap-2 rounded-md px-4 py-2 text-sm font-medium transition-colors {{ $activeView === $view ? 'bg-white text-gray-900 shadow dark:bg-gray-700 dark:text-white' : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white' }}"
            >
                @switch($option['icon'])
                    @case('document-check')
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.125 2.25h-4.5c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125v-9M10.125 2.25h.375a9 9 0 0 1 9 9v.375M10.125 2.25A3.375 3.375 0 0 1 13.5 5.625v1.5c0 .621.504 1.125 1.125 1.125h1.5a3.375 3.375 0 0 1 3.375 3.375M9 15l2.25 2.25L15 12" />
                        </svg>
                        @break
                    @case('exclamation-triangle')
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                        @break
                    @case('squares-2x2')
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                        </svg>
                        @break
                    @case('wrench-screwdriver')
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z" />
                        </svg>
                        @break
                    @case('code-bracket')
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75 22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3-4.5 16.5" />
                        </svg>
                        @break
                @endswitch
                {{ $option['label'] }}
            </button>
        @endforeach
    </div>

    {{-- Issues List --}}
    @if(count($this->activeIssues) > 0)
        <div class="space-y-3">
            @foreach($this->activeIssues as $key => $group)
                <div class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                    {{-- Group Header --}}
                    <button wire:click="toggleGroup('{{ $key }}')" class="flex w-full items-center justify-between p-4 text-left">
                        <div class="flex items-center gap-3">
                            {{-- Group Badge --}}
                            @if($activeView === 'by_wcag')
                                <x-accessibility.wcag-badge :level="$group['wcag_level'] ?? 'A'" />
                                <div>
                                    <span class="font-mono text-sm text-gray-500 dark:text-gray-400">{{ $group['criterion_id'] }}</span>
                                    <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ $group['criterion_name'] }}</span>
                                </div>
                            @elseif($activeView === 'by_impact')
                                @php $color = $this->getImpactColor($group['impact']); @endphp
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-{{ $color }}-100 text-{{ $color }}-800 dark:bg-{{ $color }}-900/30 dark:text-{{ $color }}-400">
                                    {{ ucfirst($group['impact']) }}
                                </span>
                            @elseif($activeView === 'by_category')
                                @php $color = $this->getCategoryColor($group['category']); @endphp
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-{{ $color }}-100 text-{{ $color }}-800 dark:bg-{{ $color }}-900/30 dark:text-{{ $color }}-400">
                                    {{ ucfirst($group['category']) }}
                                </span>
                            @elseif($activeView === 'by_complexity')
                                @php $color = $this->getComplexityColor($group['complexity']); @endphp
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-{{ $color }}-100 text-{{ $color }}-800 dark:bg-{{ $color }}-900/30 dark:text-{{ $color }}-400">
                                    {{ $group['complexity_label'] ?? ucfirst($group['complexity']) }}
                                </span>
                                @if(isset($group['total_effort']))
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        ~{{ $group['total_effort'] }} min
                                    </span>
                                @endif
                            @elseif($activeView === 'by_element')
                                <code class="rounded bg-gray-100 px-2 py-1 text-sm text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                    {{ Str::limit($group['selector'], 50) }}
                                </code>
                            @endif
                        </div>

                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                {{ $group['count'] }} {{ Str::plural('issue', $group['count']) }}
                            </span>
                            <svg class="h-5 w-5 text-gray-400 transition-transform {{ $expandedGroup === $key ? 'rotate-180' : '' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                            </svg>
                        </div>
                    </button>

                    {{-- Expanded Checks --}}
                    @if($expandedGroup === $key)
                        <div class="border-t border-gray-200 dark:border-gray-700">
                            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach($group['checks'] ?? [] as $check)
                                    <div class="flex items-start gap-4 p-4">
                                        {{-- Impact indicator --}}
                                        @php $impactColor = $this->getImpactColor($check['impact'] ?? 'unknown'); @endphp
                                        <div class="w-1 h-12 rounded-full bg-{{ $impactColor }}-500"></div>

                                        <div class="flex-1 min-w-0">
                                            @if($activeView !== 'by_wcag' && isset($check['criterion_id']))
                                                <span class="font-mono text-xs text-gray-500 dark:text-gray-400">{{ $check['criterion_id'] }}</span>
                                            @endif
                                            <p class="text-sm text-gray-700 dark:text-gray-300">{{ $check['message'] }}</p>
                                            @if(isset($check['element_selector']))
                                                <code class="mt-1 inline-block text-xs text-gray-500 dark:text-gray-400">{{ Str::limit($check['element_selector'], 80) }}</code>
                                            @endif
                                        </div>

                                        @if(isset($check['impact']) && $activeView !== 'by_impact')
                                            <span class="inline-flex items-center rounded px-1.5 py-0.5 text-xs font-medium bg-{{ $impactColor }}-100 text-{{ $impactColor }}-700 dark:bg-{{ $impactColor }}-900/30 dark:text-{{ $impactColor }}-400">
                                                {{ ucfirst($check['impact']) }}
                                            </span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <div class="rounded-lg border-2 border-dashed border-gray-300 p-12 text-center dark:border-gray-600">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No Issues Found</h3>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                @if($searchQuery)
                    No issues match your search query.
                @else
                    This audit has no failing checks.
                @endif
            </p>
        </div>
    @endif
</div>
