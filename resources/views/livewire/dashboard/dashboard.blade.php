<div>
    {{-- Header --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Dashboard</h1>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Welcome back! Here's an overview of your account.
        </p>
    </div>

    {{-- Stats Cards --}}
    <div class="grid gap-4 mb-8 md:grid-cols-4">
        {{-- Credit Balance --}}
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Credit Balance</p>
                <svg class="h-5 w-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($organization->credit_balance) }}</p>
            <a href="{{ route('billing.credits') }}" class="mt-2 inline-block text-sm text-indigo-600 dark:text-indigo-400 hover:underline">Buy more</a>
        </div>

        {{-- Total Scans --}}
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Scans (30 days)</p>
                <svg class="h-5 w-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($scanStats['total']) }}</p>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $scanStats['success_rate'] }}% success rate</p>
        </div>

        {{-- Projects --}}
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Projects</p>
                <svg class="h-5 w-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                </svg>
            </div>
            <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ $recentProjects->count() }}</p>
            <a href="{{ route('projects.index') }}" class="mt-2 inline-block text-sm text-indigo-600 dark:text-indigo-400 hover:underline">View all</a>
        </div>

        {{-- Subscription --}}
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Plan</p>
                <svg class="h-5 w-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                </svg>
            </div>
            <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ $tierConfig['name'] }}</p>
            <a href="{{ route('billing.index') }}" class="mt-2 inline-block text-sm text-indigo-600 dark:text-indigo-400 hover:underline">Manage</a>
        </div>
    </div>

    <div class="grid gap-8 lg:grid-cols-2">
        {{-- Recent Projects --}}
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
            <div class="border-b border-gray-200 dark:border-gray-700 px-6 py-4 flex items-center justify-between">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white">Recent Projects</h2>
                <a href="{{ route('projects.create') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">New project</a>
            </div>
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($recentProjects as $project)
                    <a href="{{ route('projects.show', $project) }}" class="block px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $project->name }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $project->urls_count }} URLs</p>
                            </div>
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    </a>
                @empty
                    <div class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                        No projects yet. <a href="{{ route('projects.create') }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">Create one</a>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Recent Scans --}}
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
            <div class="border-b border-gray-200 dark:border-gray-700 px-6 py-4">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white">Recent Scans</h2>
            </div>
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($recentScans as $scan)
                    <a href="{{ route('scans.show', $scan) }}" class="block px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <div class="flex items-center justify-between">
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium text-gray-900 dark:text-white">{{ Str::limit($scan->url->url, 40) }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $scan->url->project->name }} &middot; {{ $scan->created_at->diffForHumans() }}</p>
                            </div>
                            <div class="ml-4">
                                @if ($scan->status === 'completed')
                                    <span class="inline-flex items-center rounded-full bg-green-100 dark:bg-green-900 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:text-green-200">
                                        Completed
                                    </span>
                                @elseif ($scan->status === 'processing')
                                    <span class="inline-flex items-center rounded-full bg-yellow-100 dark:bg-yellow-900 px-2.5 py-0.5 text-xs font-medium text-yellow-800 dark:text-yellow-200">
                                        Processing
                                    </span>
                                @elseif ($scan->status === 'failed')
                                    <span class="inline-flex items-center rounded-full bg-red-100 dark:bg-red-900 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:text-red-200">
                                        Failed
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-700 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:text-gray-200">
                                        Pending
                                    </span>
                                @endif
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                        No scans yet. Create a project and add URLs to get started.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Credit Usage Chart --}}
    <div class="mt-8 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
        <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Credit Usage (30 days)</h2>
        <div class="grid gap-4 md:grid-cols-3">
            <div class="text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">Credits Added</p>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">+{{ number_format($creditUsage['credits_added']) }}</p>
            </div>
            <div class="text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">Credits Used</p>
                <p class="text-2xl font-bold text-red-600 dark:text-red-400">-{{ number_format($creditUsage['credits_used']) }}</p>
            </div>
            <div class="text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">Net Change</p>
                <p class="text-2xl font-bold {{ $creditUsage['net_change'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                    {{ $creditUsage['net_change'] >= 0 ? '+' : '' }}{{ number_format($creditUsage['net_change']) }}
                </p>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="mt-8 grid gap-4 md:grid-cols-3">
        <a href="{{ route('projects.create') }}" class="flex items-center rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 hover:border-indigo-500 dark:hover:border-indigo-500 transition-colors">
            <div class="flex-shrink-0 rounded-lg bg-indigo-100 dark:bg-indigo-900 p-3">
                <svg class="h-6 w-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
            </div>
            <div class="ml-4">
                <p class="font-medium text-gray-900 dark:text-white">New Project</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Create a new project</p>
            </div>
        </a>
        <a href="{{ route('billing.credits') }}" class="flex items-center rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 hover:border-indigo-500 dark:hover:border-indigo-500 transition-colors">
            <div class="flex-shrink-0 rounded-lg bg-green-100 dark:bg-green-900 p-3">
                <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="ml-4">
                <p class="font-medium text-gray-900 dark:text-white">Buy Credits</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Purchase credit packs</p>
            </div>
        </a>
        <a href="{{ route('api.tokens') }}" class="flex items-center rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 hover:border-indigo-500 dark:hover:border-indigo-500 transition-colors">
            <div class="flex-shrink-0 rounded-lg bg-purple-100 dark:bg-purple-900 p-3">
                <svg class="h-6 w-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
            </div>
            <div class="ml-4">
                <p class="font-medium text-gray-900 dark:text-white">API Access</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Manage API tokens</p>
            </div>
        </a>
    </div>
</div>
