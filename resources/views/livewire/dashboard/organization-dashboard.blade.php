<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $organization->name }}</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Organization Health Dashboard</p>
            </div>
            <div class="flex items-center gap-x-3">
                <a
                    href="{{ route('projects.create') }}"
                    class="inline-flex items-center gap-x-2 rounded-md bg-primary-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-500"
                >
                    <x-ui.icon name="plus" class="size-5" />
                    New Project
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Alerts Section -->
        @php $alerts = $this->alerts; @endphp
        @if(count($alerts) > 0)
            <div class="space-y-3">
                @foreach($alerts as $alert)
                    <div class="rounded-lg p-4 {{ $alert['severity'] === 'error' ? 'bg-red-50 dark:bg-red-900/20 ring-1 ring-red-200 dark:ring-red-800' : 'bg-yellow-50 dark:bg-yellow-900/20 ring-1 ring-yellow-200 dark:ring-yellow-800' }}">
                        <div class="flex items-center gap-x-3">
                            @if($alert['severity'] === 'error')
                                <x-ui.icon name="exclamation-circle" class="size-5 text-red-600 dark:text-red-400" />
                            @else
                                <x-ui.icon name="exclamation-triangle" class="size-5 text-yellow-600 dark:text-yellow-400" />
                            @endif
                            <p class="text-sm font-medium {{ $alert['severity'] === 'error' ? 'text-red-800 dark:text-red-200' : 'text-yellow-800 dark:text-yellow-200' }}">
                                {{ $alert['message'] }}
                            </p>
                            @if($alert['type'] === 'credits')
                                <a href="{{ route('billing.credits') }}" class="ml-auto text-sm font-medium {{ $alert['severity'] === 'error' ? 'text-red-600 dark:text-red-400 hover:text-red-700' : 'text-yellow-600 dark:text-yellow-400 hover:text-yellow-700' }}">
                                    Purchase Credits &rarr;
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Stats Overview Cards -->
        @php $issueCounts = $this->issueCounts; $creditUsage = $this->creditUsage; @endphp
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Total Open Issues -->
            <div class="rounded-xl bg-white dark:bg-gray-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
                <div class="flex items-center gap-x-3">
                    <div class="flex-shrink-0 rounded-lg bg-red-100 dark:bg-red-900/30 p-3">
                        <x-ui.icon name="exclamation-triangle" class="size-6 text-red-600 dark:text-red-400" />
                    </div>
                    <div>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $issueCounts['total'] }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Open Issues</p>
                    </div>
                </div>
                <div class="mt-4 flex items-center gap-x-4 text-xs">
                    <span class="inline-flex items-center gap-x-1 text-red-600 dark:text-red-400">
                        <span class="size-2 rounded-full bg-red-500"></span>
                        {{ $issueCounts['errors'] }} errors
                    </span>
                    <span class="inline-flex items-center gap-x-1 text-yellow-600 dark:text-yellow-400">
                        <span class="size-2 rounded-full bg-yellow-500"></span>
                        {{ $issueCounts['warnings'] }} warnings
                    </span>
                </div>
            </div>

            <!-- Credit Balance -->
            <div class="rounded-xl bg-white dark:bg-gray-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
                <div class="flex items-center gap-x-3">
                    <div class="flex-shrink-0 rounded-lg {{ $creditUsage['lowBalance'] ? 'bg-yellow-100 dark:bg-yellow-900/30' : 'bg-green-100 dark:bg-green-900/30' }} p-3">
                        <x-ui.icon name="currency-dollar" class="size-6 {{ $creditUsage['lowBalance'] ? 'text-yellow-600 dark:text-yellow-400' : 'text-green-600 dark:text-green-400' }}" />
                    </div>
                    <div>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($creditUsage['balance']) }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Credits Remaining</p>
                    </div>
                </div>
                <div class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                    @if($creditUsage['daysRemaining'] !== null)
                        ~{{ $creditUsage['daysRemaining'] }} days at current usage
                    @else
                        No recent usage
                    @endif
                </div>
            </div>

            <!-- Monthly Usage -->
            <div class="rounded-xl bg-white dark:bg-gray-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
                <div class="flex items-center gap-x-3">
                    <div class="flex-shrink-0 rounded-lg bg-blue-100 dark:bg-blue-900/30 p-3">
                        <x-ui.icon name="chart-bar" class="size-6 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($creditUsage['monthlyUsage']) }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Credits This Month</p>
                    </div>
                </div>
                <div class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                    @if($creditUsage['lastMonthUsage'] > 0)
                        @php
                            $change = $creditUsage['monthlyUsage'] - $creditUsage['lastMonthUsage'];
                            $percentChange = $creditUsage['lastMonthUsage'] > 0 ? round(($change / $creditUsage['lastMonthUsage']) * 100) : 0;
                        @endphp
                        <span class="{{ $change >= 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                            {{ $change >= 0 ? '+' : '' }}{{ $percentChange }}%
                        </span>
                        vs last month
                    @else
                        First month of usage
                    @endif
                </div>
            </div>

            <!-- Projects Count -->
            <div class="rounded-xl bg-white dark:bg-gray-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
                <div class="flex items-center gap-x-3">
                    <div class="flex-shrink-0 rounded-lg bg-purple-100 dark:bg-purple-900/30 p-3">
                        <x-ui.icon name="folder" class="size-6 text-purple-600 dark:text-purple-400" />
                    </div>
                    <div>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $organization->projects->count() }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Active Projects</p>
                    </div>
                </div>
                <div class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                    {{ $organization->projects->sum(fn($p) => $p->urls()->count()) }} total URLs
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Issues by Project -->
            <div class="lg:col-span-2 rounded-lg bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Issues by Project</h3>
                </div>
                <div class="p-4">
                    @if(count($issueCounts['byProject']) > 0)
                        <div class="space-y-3">
                            @foreach($issueCounts['byProject'] as $projectId => $data)
                                <div class="flex items-center justify-between">
                                    <a href="{{ route('projects.show', $projectId) }}" class="text-sm text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400">
                                        {{ $data['name'] }}
                                    </a>
                                    <div class="flex items-center gap-x-3">
                                        <div class="w-32 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                            @php $maxCount = max(array_column($issueCounts['byProject'], 'count')); @endphp
                                            <div
                                                class="h-2 rounded-full {{ $data['count'] > 0 ? 'bg-red-500' : 'bg-green-500' }}"
                                                style="width: {{ $maxCount > 0 ? ($data['count'] / $maxCount * 100) : 0 }}%"
                                            ></div>
                                        </div>
                                        <span class="text-sm font-medium text-gray-900 dark:text-white w-8 text-right">{{ $data['count'] }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-8">No projects yet</p>
                    @endif
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="rounded-lg bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Recent Activity</h3>
                </div>
                <div class="divide-y divide-gray-200 dark:divide-gray-700 max-h-80 overflow-y-auto">
                    @forelse($this->recentActivity as $activity)
                        <div class="px-4 py-3">
                            <div class="flex items-start gap-x-3">
                                @if($activity['type'] === 'scan')
                                    <span class="flex-shrink-0 size-2 mt-2 rounded-full {{ $activity['status'] === 'completed' ? 'bg-green-500' : ($activity['status'] === 'failed' ? 'bg-red-500' : 'bg-yellow-500') }}"></span>
                                @else
                                    <span class="flex-shrink-0 size-2 mt-2 rounded-full bg-blue-500"></span>
                                @endif
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-gray-900 dark:text-white">{{ $activity['message'] }}</p>
                                    <div class="mt-0.5 flex items-center gap-x-2 text-xs text-gray-500 dark:text-gray-400">
                                        <span>{{ $activity['user'] }}</span>
                                        @if($activity['project'])
                                            <span>&middot;</span>
                                            <span>{{ $activity['project'] }}</span>
                                        @endif
                                        <span>&middot;</span>
                                        <span>{{ $activity['created_at']->diffForHumans() }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-8">No recent activity</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Trend Charts -->
        @php $trendData = $this->trendData; @endphp
        <div class="rounded-lg bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Issues & Resolutions (Last 30 Days)</h3>
            </div>
            <div class="p-4">
                <div
                    x-data="{
                        issues: @js(array_values($trendData['issues'])),
                        resolutions: @js(array_values($trendData['resolutions'])),
                        labels: @js(array_keys($trendData['issues'])),
                        maxValue: Math.max(...@js(array_values($trendData['issues'])), ...@js(array_values($trendData['resolutions'])), 1)
                    }"
                    class="h-48"
                >
                    <div class="flex items-end justify-between h-full gap-1">
                        <template x-for="(value, index) in issues" :key="index">
                            <div class="flex-1 flex flex-col items-center gap-1">
                                <div class="w-full flex flex-col gap-0.5" style="height: 160px;">
                                    <div
                                        class="w-full bg-red-400 dark:bg-red-500 rounded-t"
                                        :style="'height: ' + (value / maxValue * 100) + '%'"
                                        :title="labels[index] + ': ' + value + ' issues'"
                                    ></div>
                                    <div
                                        class="w-full bg-green-400 dark:bg-green-500 rounded-b"
                                        :style="'height: ' + (resolutions[index] / maxValue * 100) + '%'"
                                        :title="labels[index] + ': ' + resolutions[index] + ' resolved'"
                                    ></div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
                <div class="flex items-center justify-center gap-x-6 mt-4 text-xs">
                    <span class="flex items-center gap-x-2">
                        <span class="size-3 rounded bg-red-400 dark:bg-red-500"></span>
                        <span class="text-gray-600 dark:text-gray-400">New Issues</span>
                    </span>
                    <span class="flex items-center gap-x-2">
                        <span class="size-3 rounded bg-green-400 dark:bg-green-500"></span>
                        <span class="text-gray-600 dark:text-gray-400">Resolved</span>
                    </span>
                </div>
            </div>
        </div>

        <!-- Scheduled Scans -->
        @php $scheduledScans = $this->scheduledScans; @endphp
        @if($scheduledScans->isNotEmpty())
            <div class="rounded-lg bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Active Scheduled Scans</h3>
                </div>
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($scheduledScans as $schedule)
                        <div class="px-4 py-3 flex items-center justify-between">
                            <div class="flex items-center gap-x-3">
                                <x-ui.icon name="clock" class="size-5 text-gray-400" />
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $schedule->project->name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ ucfirst($schedule->frequency) }}
                                        @if($schedule->next_run_at)
                                            &middot; Next: {{ $schedule->next_run_at->diffForHumans() }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <span class="inline-flex items-center rounded-full bg-green-100 dark:bg-green-900/30 px-2 py-0.5 text-xs font-medium text-green-700 dark:text-green-400">
                                Active
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
