<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Regression & Trends</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Track accessibility progress over time
            </p>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="border-b border-gray-200 dark:border-gray-700">
        <nav class="flex -mb-px gap-4" aria-label="Tabs">
            <button wire:click="setTab('overview')" class="py-4 px-1 text-sm font-medium border-b-2 {{ $activeTab === 'overview' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400' }}">
                Overview
            </button>
            <button wire:click="setTab('compare')" class="py-4 px-1 text-sm font-medium border-b-2 {{ $activeTab === 'compare' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400' }}">
                Compare Audits
            </button>
            <button wire:click="setTab('persistent')" class="py-4 px-1 text-sm font-medium border-b-2 {{ $activeTab === 'persistent' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400' }}">
                Persistent Issues
            </button>
        </nav>
    </div>

    @if($activeTab === 'overview')
        @if($this->trends['has_data'])
            {{-- Summary Cards --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                {{-- Score Trend --}}
                <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Score Trend</span>
                        <span class="{{ $this->getScoreTrendClass() }}">
                            @if(($this->summary['score_trend'] ?? 'stable') === 'improving')
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941" />
                                </svg>
                            @elseif(($this->summary['score_trend'] ?? 'stable') === 'declining')
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6 9 12.75l4.286-4.286a11.948 11.948 0 0 1 4.306 6.43l.776 2.898m0 0 3.182-5.511m-3.182 5.51-5.511-3.181" />
                                </svg>
                            @else
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14" />
                                </svg>
                            @endif
                        </span>
                    </div>
                    <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                        {{ $this->summary['score_change'] >= 0 ? '+' : '' }}{{ $this->summary['score_change'] ?? 0 }}%
                    </p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">vs previous audit</p>
                </div>

                {{-- Issue Trend --}}
                <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Issue Trend</span>
                        <span class="{{ $this->getIssueTrendClass() }}">
                            @if(($this->summary['issue_trend'] ?? 'stable') === 'improving')
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6 9 12.75l4.286-4.286a11.948 11.948 0 0 1 4.306 6.43l.776 2.898m0 0 3.182-5.511m-3.182 5.51-5.511-3.181" />
                                </svg>
                            @elseif(($this->summary['issue_trend'] ?? 'stable') === 'declining')
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941" />
                                </svg>
                            @else
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14" />
                                </svg>
                            @endif
                        </span>
                    </div>
                    <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                        {{ $this->summary['issue_change'] >= 0 ? '+' : '' }}{{ $this->summary['issue_change'] ?? 0 }}
                    </p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">issues vs previous</p>
                </div>

                {{-- Average Score --}}
                <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Average Score</span>
                    <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                        {{ $this->summary['average_score'] ?? 0 }}%
                    </p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        across {{ count($this->trends['audits']) }} audits
                    </p>
                </div>

                {{-- Resolution Rate --}}
                <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Resolution Rate</span>
                    <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                        {{ $this->resolutionRate['rate'] ?? 0 }}%
                    </p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        {{ $this->resolutionRate['total_fixed'] ?? 0 }} fixed / {{ ($this->resolutionRate['total_fixed'] ?? 0) + ($this->resolutionRate['total_new'] ?? 0) }} total
                    </p>
                </div>
            </div>

            {{-- Score Chart --}}
            <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Score History</h3>
                <div class="h-64 flex items-end gap-2">
                    @foreach($this->trends['scores'] as $index => $score)
                        @php
                            $height = max(10, $score);
                            $date = $this->trends['dates'][$index] ?? '';
                            $color = $score >= 80 ? 'bg-green-500' : ($score >= 60 ? 'bg-yellow-500' : 'bg-red-500');
                        @endphp
                        <div class="flex-1 flex flex-col items-center gap-1">
                            <span class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ $score }}%</span>
                            <div class="w-full {{ $color }} rounded-t transition-all" style="height: {{ $height }}%"></div>
                            <span class="text-xs text-gray-500 dark:text-gray-400 truncate w-full text-center">{{ \Carbon\Carbon::parse($date)->format('M d') }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Issues Over Time --}}
            <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Issues Over Time</h3>
                <div class="space-y-3">
                    @foreach($this->trends['issues'] as $index => $issue)
                        @php
                            $date = $this->trends['dates'][$index] ?? '';
                            $total = $issue['total'] ?? 0;
                            $failed = $issue['failed'] ?? 0;
                            $passed = $issue['passed'] ?? 0;
                            $passRate = $total > 0 ? round(($passed / $total) * 100) : 0;
                        @endphp
                        <div class="flex items-center gap-4">
                            <span class="w-20 text-sm text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::parse($date)->format('M d') }}</span>
                            <div class="flex-1 h-4 bg-gray-200 rounded-full overflow-hidden dark:bg-gray-700">
                                <div class="h-full bg-green-500" style="width: {{ $passRate }}%"></div>
                            </div>
                            <span class="w-24 text-sm text-gray-700 dark:text-gray-300">
                                {{ $failed }} issues
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="rounded-lg border-2 border-dashed border-gray-300 p-12 text-center dark:border-gray-600">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No Trend Data</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Run accessibility audits to start tracking trends.
                </p>
            </div>
        @endif
    @elseif($activeTab === 'compare')
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            {{-- Audit Selection --}}
            <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Select Audits to Compare</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Current Audit</label>
                        <select wire:model.live="selectedAudit" wire:change="selectAudit($event.target.value)" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="">Select an audit...</option>
                            @foreach($this->availableAudits as $audit)
                                <option value="{{ $audit->id }}">
                                    {{ $audit->completed_at?->format('M d, Y H:i') }} - Score: {{ $audit->overall_score }}%
                                </option>
                            @endforeach
                        </select>
                    </div>

                    @if($selectedAudit)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Compare To</label>
                            <select wire:model.live="compareAudit" wire:change="selectCompareAudit($event.target.value)" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="">Select an audit to compare...</option>
                                @foreach($this->availableAudits as $audit)
                                    @if($audit->id !== $selectedAudit->id)
                                        <option value="{{ $audit->id }}">
                                            {{ $audit->completed_at?->format('M d, Y H:i') }} - Score: {{ $audit->overall_score }}%
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Comparison Results --}}
            @if($this->comparison)
                <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Comparison Results</h3>
                        @if($this->comparison['has_regression'])
                            <span class="inline-flex items-center rounded-full bg-red-100 px-3 py-0.5 text-sm font-medium text-red-800 dark:bg-red-900/30 dark:text-red-400">
                                Regression Detected
                            </span>
                        @endif
                    </div>

                    <div class="grid grid-cols-3 gap-4 mb-6">
                        <div class="text-center p-4 rounded-lg bg-green-50 dark:bg-green-900/20">
                            <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $this->comparison['fixed_issues']['count'] }}</p>
                            <p class="text-sm text-green-700 dark:text-green-300">Fixed</p>
                        </div>
                        <div class="text-center p-4 rounded-lg bg-red-50 dark:bg-red-900/20">
                            <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $this->comparison['new_issues']['count'] }}</p>
                            <p class="text-sm text-red-700 dark:text-red-300">New</p>
                        </div>
                        <div class="text-center p-4 rounded-lg bg-gray-50 dark:bg-gray-700">
                            <p class="text-2xl font-bold text-gray-600 dark:text-gray-300">{{ $this->comparison['recurring_issues']['count'] }}</p>
                            <p class="text-sm text-gray-700 dark:text-gray-400">Recurring</p>
                        </div>
                    </div>

                    <div class="flex items-center justify-center gap-4 mb-6">
                        <div class="text-center">
                            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $this->comparison['previous_audit']['score'] }}%</p>
                            <p class="text-xs text-gray-500">Previous</p>
                        </div>
                        <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                        </svg>
                        <div class="text-center">
                            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $this->comparison['current_audit']['score'] }}%</p>
                            <p class="text-xs text-gray-500">Current</p>
                        </div>
                        <span class="{{ $this->comparison['score_change'] >= 0 ? 'text-green-600' : 'text-red-600' }} text-xl font-bold">
                            ({{ $this->comparison['score_change'] >= 0 ? '+' : '' }}{{ round($this->comparison['score_change'], 1) }})
                        </span>
                    </div>
                </div>
            @endif
        </div>

        {{-- Issue Lists --}}
        @if($this->comparison)
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                {{-- New Issues --}}
                <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                    <h4 class="text-md font-medium text-red-600 dark:text-red-400 mb-4">New Issues ({{ $this->comparison['new_issues']['count'] }})</h4>
                    @if(count($this->comparison['new_issues']['items']) > 0)
                        <div class="space-y-3 max-h-64 overflow-y-auto">
                            @foreach($this->comparison['new_issues']['items'] as $issue)
                                <div class="p-3 rounded bg-red-50 dark:bg-red-900/10">
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono text-xs text-gray-500">{{ $issue['criterion_id'] }}</span>
                                        <span class="text-xs px-1.5 py-0.5 rounded bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">{{ $issue['impact'] ?? 'unknown' }}</span>
                                    </div>
                                    <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">{{ $issue['message'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-500">No new issues</p>
                    @endif
                </div>

                {{-- Fixed Issues --}}
                <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                    <h4 class="text-md font-medium text-green-600 dark:text-green-400 mb-4">Fixed Issues ({{ $this->comparison['fixed_issues']['count'] }})</h4>
                    @if(count($this->comparison['fixed_issues']['items']) > 0)
                        <div class="space-y-3 max-h-64 overflow-y-auto">
                            @foreach($this->comparison['fixed_issues']['items'] as $issue)
                                <div class="p-3 rounded bg-green-50 dark:bg-green-900/10">
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono text-xs text-gray-500">{{ $issue['criterion_id'] }}</span>
                                    </div>
                                    <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">{{ $issue['message'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-500">No fixed issues</p>
                    @endif
                </div>
            </div>
        @endif
    @elseif($activeTab === 'persistent')
        @if($this->persistentIssues->isNotEmpty())
            <div class="rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Persistent Issues</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Issues that have appeared in 3 or more consecutive audits
                    </p>
                </div>
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($this->persistentIssues as $issue)
                        <div class="p-6">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono text-sm text-gray-500">{{ $issue['criterion_id'] }}</span>
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $issue['criterion_name'] }}</span>
                                        @if($issue['impact'])
                                            @php
                                                $impactColor = match($issue['impact']) {
                                                    'critical' => 'red',
                                                    'serious' => 'orange',
                                                    'moderate' => 'yellow',
                                                    default => 'gray',
                                                };
                                            @endphp
                                            <span class="inline-flex items-center rounded px-1.5 py-0.5 text-xs font-medium bg-{{ $impactColor }}-100 text-{{ $impactColor }}-700 dark:bg-{{ $impactColor }}-900/30 dark:text-{{ $impactColor }}-400">
                                                {{ ucfirst($issue['impact']) }}
                                            </span>
                                        @endif
                                    </div>
                                    <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">{{ $issue['message'] }}</p>
                                </div>
                                <div class="ml-4 text-right">
                                    <span class="inline-flex items-center rounded-full bg-orange-100 px-3 py-1 text-sm font-medium text-orange-800 dark:bg-orange-900/30 dark:text-orange-400">
                                        {{ $issue['occurrences'] }}x
                                    </span>
                                    <p class="mt-1 text-xs text-gray-500">
                                        Since {{ \Carbon\Carbon::parse($issue['first_seen'])->format('M d, Y') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="rounded-lg border-2 border-dashed border-gray-300 p-12 text-center dark:border-gray-600">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No Persistent Issues</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Great job! No issues have persisted across multiple audits.
                </p>
            </div>
        @endif
    @endif
</div>
