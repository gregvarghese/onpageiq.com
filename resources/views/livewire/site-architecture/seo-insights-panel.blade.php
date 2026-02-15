<div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700">
    @if($this->architecture)
        {{-- Header with Tabs --}}
        <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="flex -mb-px">
                <button
                    wire:click="setTab('overview')"
                    @class([
                        'px-4 py-3 text-sm font-medium border-b-2 transition-colors',
                        'border-blue-500 text-blue-600 dark:text-blue-400' => $activeTab === 'overview',
                        'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' => $activeTab !== 'overview',
                    ])
                >
                    Overview
                </button>
                <button
                    wire:click="setTab('issues')"
                    @class([
                        'px-4 py-3 text-sm font-medium border-b-2 transition-colors',
                        'border-blue-500 text-blue-600 dark:text-blue-400' => $activeTab === 'issues',
                        'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' => $activeTab !== 'issues',
                    ])
                >
                    Issues
                    @if(count($this->seoAnalysis['critical_issues'] ?? []) > 0)
                        <span class="ml-1.5 inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-red-500 rounded-full">
                            {{ count($this->seoAnalysis['critical_issues']) }}
                        </span>
                    @endif
                </button>
                <button
                    wire:click="setTab('recommendations')"
                    @class([
                        'px-4 py-3 text-sm font-medium border-b-2 transition-colors',
                        'border-blue-500 text-blue-600 dark:text-blue-400' => $activeTab === 'recommendations',
                        'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' => $activeTab !== 'recommendations',
                    ])
                >
                    Recommendations
                </button>
                <button
                    wire:click="setTab('roadmap')"
                    @class([
                        'px-4 py-3 text-sm font-medium border-b-2 transition-colors',
                        'border-blue-500 text-blue-600 dark:text-blue-400' => $activeTab === 'roadmap',
                        'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' => $activeTab !== 'roadmap',
                    ])
                >
                    Fix Roadmap
                </button>
            </nav>
        </div>

        {{-- Content --}}
        <div class="p-4">
            {{-- Overview Tab --}}
            @if($activeTab === 'overview')
                <div class="space-y-6">
                    {{-- Overall Score --}}
                    @if(isset($this->seoAnalysis['overall_score']))
                        <div class="text-center">
                            <div @class([
                                'inline-flex items-center justify-center w-24 h-24 rounded-full text-3xl font-bold',
                                'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' => ($this->seoAnalysis['overall_score']['overall'] ?? 0) >= 80,
                                'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400' => ($this->seoAnalysis['overall_score']['overall'] ?? 0) >= 60 && ($this->seoAnalysis['overall_score']['overall'] ?? 0) < 80,
                                'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' => ($this->seoAnalysis['overall_score']['overall'] ?? 0) < 60,
                            ])>
                                {{ $this->seoAnalysis['overall_score']['grade'] ?? 'N/A' }}
                            </div>
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                Overall Score: {{ $this->seoAnalysis['overall_score']['overall'] ?? 0 }}/100
                            </p>
                        </div>

                        {{-- Score Breakdown --}}
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            @foreach($this->seoAnalysis['overall_score']['breakdown'] ?? [] as $key => $score)
                                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 text-center">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">{{ ucfirst($key) }}</p>
                                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ round($score) }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Quick Stats --}}
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-4">
                            <p class="text-xs text-orange-600 dark:text-orange-400 uppercase font-medium">Orphan Pages</p>
                            <p class="text-2xl font-bold text-orange-700 dark:text-orange-300">
                                {{ $this->seoAnalysis['orphan_analysis']['count'] ?? 0 }}
                            </p>
                            <p class="text-xs text-orange-500">
                                {{ number_format(($this->seoAnalysis['orphan_analysis']['rate'] ?? 0) * 100, 1) }}% of pages
                            </p>
                        </div>

                        <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4">
                            <p class="text-xs text-purple-600 dark:text-purple-400 uppercase font-medium">Deep Pages</p>
                            <p class="text-2xl font-bold text-purple-700 dark:text-purple-300">
                                {{ $this->seoAnalysis['depth_analysis']['deep_pages_count'] ?? 0 }}
                            </p>
                            <p class="text-xs text-purple-500">
                                Max depth: {{ $this->seoAnalysis['depth_analysis']['max_depth'] ?? 0 }}
                            </p>
                        </div>

                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                            <p class="text-xs text-blue-600 dark:text-blue-400 uppercase font-medium">Depth Score</p>
                            <p class="text-2xl font-bold text-blue-700 dark:text-blue-300">
                                {{ $this->seoAnalysis['depth_analysis']['grade'] ?? 'N/A' }}
                            </p>
                            <p class="text-xs text-blue-500">
                                Avg: {{ number_format($this->seoAnalysis['depth_analysis']['average_depth'] ?? 0, 1) }} clicks
                            </p>
                        </div>

                        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                            <p class="text-xs text-green-600 dark:text-green-400 uppercase font-medium">Link Opportunities</p>
                            <p class="text-2xl font-bold text-green-700 dark:text-green-300">
                                {{ count($this->seoAnalysis['linking_opportunities'] ?? []) }}
                            </p>
                            <p class="text-xs text-green-500">Quick wins available</p>
                        </div>
                    </div>

                    {{-- Top Linking Opportunities --}}
                    @if(count($this->seoAnalysis['linking_opportunities'] ?? []) > 0)
                        <div>
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Top Linking Opportunities</h4>
                            <div class="space-y-2">
                                @foreach(array_slice($this->seoAnalysis['linking_opportunities'], 0, 3) as $opp)
                                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                                        <div class="flex items-center gap-2 text-sm">
                                            <span class="text-gray-600 dark:text-gray-300 truncate">{{ $opp['source']['title'] ?? 'Source' }}</span>
                                            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                            </svg>
                                            <span class="text-gray-600 dark:text-gray-300 truncate">{{ $opp['target']['title'] ?? 'Target' }}</span>
                                        </div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $opp['reason'] }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Issues Tab --}}
            @if($activeTab === 'issues')
                <div class="space-y-4">
                    @forelse($this->seoAnalysis['critical_issues'] ?? [] as $issue)
                        <div @class([
                            'rounded-lg p-4 border-l-4',
                            'bg-red-50 dark:bg-red-900/20 border-red-500' => $issue['severity'] === 'critical',
                            'bg-orange-50 dark:bg-orange-900/20 border-orange-500' => $issue['severity'] === 'serious',
                            'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-500' => $issue['severity'] === 'moderate',
                            'bg-blue-50 dark:bg-blue-900/20 border-blue-500' => $issue['severity'] === 'minor',
                        ])>
                            <div class="flex items-start justify-between">
                                <div>
                                    <span @class([
                                        'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium',
                                        'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' => $issue['severity'] === 'critical',
                                        'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200' => $issue['severity'] === 'serious',
                                        'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' => $issue['severity'] === 'moderate',
                                        'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' => $issue['severity'] === 'minor',
                                    ])>
                                        {{ ucfirst($issue['severity']) }}
                                    </span>
                                    <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">
                                        {{ ucfirst(str_replace('_', ' ', $issue['type'])) }}
                                    </span>
                                </div>
                            </div>
                            <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">{{ $issue['message'] }}</p>
                            @if($issue['node'])
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    Page: {{ $issue['node']['url'] }}
                                </p>
                            @endif
                            @if($issue['recommendation'])
                                <p class="mt-2 text-sm text-green-700 dark:text-green-400">
                                    <strong>Fix:</strong> {{ $issue['recommendation'] }}
                                </p>
                            @endif
                        </div>
                    @empty
                        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                            <svg class="w-12 h-12 mx-auto mb-3 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p>No critical or serious issues found!</p>
                        </div>
                    @endforelse
                </div>
            @endif

            {{-- Recommendations Tab --}}
            @if($activeTab === 'recommendations')
                <div class="space-y-3">
                    @forelse($this->recommendations as $index => $rec)
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400">#{{ $index + 1 }}</span>
                                        <span @class([
                                            'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium',
                                            'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' => $rec['severity'] === 'critical',
                                            'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200' => $rec['severity'] === 'serious',
                                            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' => $rec['severity'] === 'moderate',
                                            'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' => $rec['severity'] === 'minor',
                                            'bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-200' => $rec['severity'] === 'info',
                                        ])>
                                            {{ ucfirst($rec['severity']) }}
                                        </span>
                                        <span class="text-xs text-gray-400">{{ ucfirst(str_replace('_', ' ', $rec['category'] ?? 'general')) }}</span>
                                    </div>
                                    <h4 class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $rec['title'] }}</h4>
                                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $rec['description'] }}</p>
                                    <p class="mt-2 text-sm text-blue-600 dark:text-blue-400">
                                        <strong>Action:</strong> {{ $rec['action'] }}
                                    </p>
                                </div>
                                <div class="ml-4 text-right">
                                    <span @class([
                                        'inline-flex items-center px-2 py-1 rounded text-xs font-medium',
                                        'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' => $rec['effort'] === 'low',
                                        'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' => $rec['effort'] === 'medium',
                                        'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' => $rec['effort'] === 'high',
                                    ])>
                                        {{ ucfirst($rec['effort']) }} effort
                                    </span>
                                    <p class="mt-1 text-xs text-gray-500">Impact: {{ $rec['impact_score'] }}</p>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                            <p>No recommendations available.</p>
                        </div>
                    @endforelse
                </div>
            @endif

            {{-- Roadmap Tab --}}
            @if($activeTab === 'roadmap')
                <div class="space-y-6">
                    {{-- Quick Wins --}}
                    @if(count($this->roadmap['quick_wins'] ?? []) > 0)
                        <div>
                            <h4 class="flex items-center gap-2 text-sm font-semibold text-green-700 dark:text-green-400 mb-3">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                Quick Wins (High Impact, Low Effort)
                            </h4>
                            <div class="space-y-2">
                                @foreach($this->roadmap['quick_wins'] as $rec)
                                    <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-3">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $rec['title'] }}</p>
                                        <p class="text-xs text-gray-600 dark:text-gray-300 mt-1">{{ $rec['action'] }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Major Projects --}}
                    @if(count($this->roadmap['major_projects'] ?? []) > 0)
                        <div>
                            <h4 class="flex items-center gap-2 text-sm font-semibold text-blue-700 dark:text-blue-400 mb-3">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                Major Projects (High Impact, High Effort)
                            </h4>
                            <div class="space-y-2">
                                @foreach($this->roadmap['major_projects'] as $rec)
                                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $rec['title'] }}</p>
                                        <p class="text-xs text-gray-600 dark:text-gray-300 mt-1">{{ $rec['action'] }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Fill-ins --}}
                    @if(count($this->roadmap['fill_ins'] ?? []) > 0)
                        <div>
                            <h4 class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-400 mb-3">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                Fill-ins (Low Impact, Low Effort)
                            </h4>
                            <div class="space-y-2">
                                @foreach(array_slice($this->roadmap['fill_ins'], 0, 5) as $rec)
                                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $rec['title'] }}</p>
                                        <p class="text-xs text-gray-600 dark:text-gray-300 mt-1">{{ $rec['action'] }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    @else
        <div class="p-8 text-center text-gray-500 dark:text-gray-400">
            <p>No architecture data available for SEO analysis.</p>
        </div>
    @endif
</div>
