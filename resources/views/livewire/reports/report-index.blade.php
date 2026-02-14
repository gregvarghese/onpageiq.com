<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Reports</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Overview of scan results and issues across all projects</p>
            </div>
            <a
                href="{{ route('scans.create') }}"
                class="inline-flex items-center gap-x-2 rounded-md bg-primary-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-500"
            >
                <x-ui.icon name="plus" class="size-5" />
                New Scan
            </a>
        </div>
    </x-slot>

    {{-- Filters --}}
    <div class="mb-6 flex flex-wrap items-center gap-4">
        <div>
            <select
                wire:model.live="dateRange"
                class="rounded-md border-0 py-2 pl-3 pr-10 text-gray-900 dark:text-white bg-white dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-primary-600 sm:text-sm"
            >
                <option value="7">Last 7 days</option>
                <option value="30">Last 30 days</option>
                <option value="90">Last 90 days</option>
                <option value="365">Last year</option>
            </select>
        </div>
        <div>
            <select
                wire:model.live="projectFilter"
                class="rounded-md border-0 py-2 pl-3 pr-10 text-gray-900 dark:text-white bg-white dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-primary-600 sm:text-sm"
            >
                <option value="">All Projects</option>
                @foreach($projects as $project)
                    <option value="{{ $project->id }}">{{ $project->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <select
                wire:model.live="severityFilter"
                class="rounded-md border-0 py-2 pl-3 pr-10 text-gray-900 dark:text-white bg-white dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-primary-600 sm:text-sm"
            >
                <option value="">All Severities</option>
                <option value="error">Errors</option>
                <option value="warning">Warnings</option>
                <option value="suggestion">Suggestions</option>
            </select>
        </div>
        <div>
            <select
                wire:model.live="categoryFilter"
                class="rounded-md border-0 py-2 pl-3 pr-10 text-gray-900 dark:text-white bg-white dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-primary-600 sm:text-sm"
            >
                <option value="">All Categories</option>
                <option value="spelling">Spelling</option>
                <option value="grammar">Grammar</option>
                <option value="seo">SEO</option>
                <option value="readability">Readability</option>
            </select>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="flex size-12 items-center justify-center rounded-lg bg-primary-50 dark:bg-primary-900/30">
                        <x-ui.icon name="document-magnifying-glass" class="size-6 text-primary-600 dark:text-primary-400" />
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Scans</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($this->statistics['totalScans']) }}</p>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="flex size-12 items-center justify-center rounded-lg bg-green-50 dark:bg-green-900/30">
                        <x-ui.icon name="check-circle" class="size-6 text-green-600 dark:text-green-400" />
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Completed</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($this->statistics['completedScans']) }}</p>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="flex size-12 items-center justify-center rounded-lg bg-amber-50 dark:bg-amber-900/30">
                        <x-ui.icon name="exclamation-triangle" class="size-6 text-amber-600 dark:text-amber-400" />
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Issues</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($this->statistics['totalIssues']) }}</p>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="flex size-12 items-center justify-center rounded-lg bg-red-50 dark:bg-red-900/30">
                        <x-ui.icon name="x-circle" class="size-6 text-red-600 dark:text-red-400" />
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Errors</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($this->statistics['errorCount']) }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Issue Breakdown --}}
    <div class="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- By Category --}}
        <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white">Issues by Category</h2>
            </div>
            <div class="px-6 py-4">
                <div class="space-y-4">
                    @php $byCategory = $this->issuesByCategory; $maxCategory = max(1, max($byCategory)); @endphp
                    <div class="flex items-center">
                        <span class="w-24 text-sm text-gray-600 dark:text-gray-400">Spelling</span>
                        <div class="flex-1 mx-4">
                            <div class="h-4 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                                <div class="h-full bg-blue-500 rounded-full" style="width: {{ ($byCategory['spelling'] / $maxCategory) * 100 }}%"></div>
                            </div>
                        </div>
                        <span class="w-12 text-right text-sm font-medium text-gray-900 dark:text-white">{{ $byCategory['spelling'] }}</span>
                    </div>
                    <div class="flex items-center">
                        <span class="w-24 text-sm text-gray-600 dark:text-gray-400">Grammar</span>
                        <div class="flex-1 mx-4">
                            <div class="h-4 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                                <div class="h-full bg-purple-500 rounded-full" style="width: {{ ($byCategory['grammar'] / $maxCategory) * 100 }}%"></div>
                            </div>
                        </div>
                        <span class="w-12 text-right text-sm font-medium text-gray-900 dark:text-white">{{ $byCategory['grammar'] }}</span>
                    </div>
                    <div class="flex items-center">
                        <span class="w-24 text-sm text-gray-600 dark:text-gray-400">SEO</span>
                        <div class="flex-1 mx-4">
                            <div class="h-4 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                                <div class="h-full bg-green-500 rounded-full" style="width: {{ ($byCategory['seo'] / $maxCategory) * 100 }}%"></div>
                            </div>
                        </div>
                        <span class="w-12 text-right text-sm font-medium text-gray-900 dark:text-white">{{ $byCategory['seo'] }}</span>
                    </div>
                    <div class="flex items-center">
                        <span class="w-24 text-sm text-gray-600 dark:text-gray-400">Readability</span>
                        <div class="flex-1 mx-4">
                            <div class="h-4 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                                <div class="h-full bg-amber-500 rounded-full" style="width: {{ ($byCategory['readability'] / $maxCategory) * 100 }}%"></div>
                            </div>
                        </div>
                        <span class="w-12 text-right text-sm font-medium text-gray-900 dark:text-white">{{ $byCategory['readability'] }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- By Severity --}}
        <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white">Issues by Severity</h2>
            </div>
            <div class="px-6 py-4">
                <div class="space-y-4">
                    @php $bySeverity = $this->issuesBySeverity; $maxSeverity = max(1, max($bySeverity)); @endphp
                    <div class="flex items-center">
                        <span class="w-24 text-sm text-gray-600 dark:text-gray-400">Errors</span>
                        <div class="flex-1 mx-4">
                            <div class="h-4 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                                <div class="h-full bg-red-500 rounded-full" style="width: {{ ($bySeverity['error'] / $maxSeverity) * 100 }}%"></div>
                            </div>
                        </div>
                        <span class="w-12 text-right text-sm font-medium text-gray-900 dark:text-white">{{ $bySeverity['error'] }}</span>
                    </div>
                    <div class="flex items-center">
                        <span class="w-24 text-sm text-gray-600 dark:text-gray-400">Warnings</span>
                        <div class="flex-1 mx-4">
                            <div class="h-4 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                                <div class="h-full bg-amber-500 rounded-full" style="width: {{ ($bySeverity['warning'] / $maxSeverity) * 100 }}%"></div>
                            </div>
                        </div>
                        <span class="w-12 text-right text-sm font-medium text-gray-900 dark:text-white">{{ $bySeverity['warning'] }}</span>
                    </div>
                    <div class="flex items-center">
                        <span class="w-24 text-sm text-gray-600 dark:text-gray-400">Suggestions</span>
                        <div class="flex-1 mx-4">
                            <div class="h-4 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                                <div class="h-full bg-blue-500 rounded-full" style="width: {{ ($bySeverity['suggestion'] / $maxSeverity) * 100 }}%"></div>
                            </div>
                        </div>
                        <span class="w-12 text-right text-sm font-medium text-gray-900 dark:text-white">{{ $bySeverity['suggestion'] }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Scans Table --}}
    <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white">Recent Scans</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">URL</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Project</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Issues</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Type</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Date</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($scans as $scan)
                        <tr wire:key="scan-{{ $scan->id }}">
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="flex items-center">
                                    <div class="max-w-xs truncate">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ parse_url($scan->url->url, PHP_URL_HOST) }}
                                        </span>
                                        <span class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ parse_url($scan->url->url, PHP_URL_PATH) ?: '/' }}
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                {{ $scan->url->project->name }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                @if($scan->result)
                                    @php
                                        $issues = $scan->result->issues;
                                        $errors = $issues->where('severity', 'error')->count();
                                        $warnings = $issues->where('severity', 'warning')->count();
                                        $suggestions = $issues->where('severity', 'suggestion')->count();
                                    @endphp
                                    <div class="flex items-center gap-2">
                                        @if($errors > 0)
                                            <span class="inline-flex items-center rounded-full bg-red-100 dark:bg-red-900 px-2 py-0.5 text-xs font-medium text-red-800 dark:text-red-200">
                                                {{ $errors }} {{ Str::plural('error', $errors) }}
                                            </span>
                                        @endif
                                        @if($warnings > 0)
                                            <span class="inline-flex items-center rounded-full bg-amber-100 dark:bg-amber-900 px-2 py-0.5 text-xs font-medium text-amber-800 dark:text-amber-200">
                                                {{ $warnings }}
                                            </span>
                                        @endif
                                        @if($suggestions > 0)
                                            <span class="inline-flex items-center rounded-full bg-blue-100 dark:bg-blue-900 px-2 py-0.5 text-xs font-medium text-blue-800 dark:text-blue-200">
                                                {{ $suggestions }}
                                            </span>
                                        @endif
                                        @if($issues->isEmpty())
                                            <span class="inline-flex items-center rounded-full bg-green-100 dark:bg-green-900 px-2 py-0.5 text-xs font-medium text-green-800 dark:text-green-200">
                                                No issues
                                            </span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-sm text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <span @class([
                                    'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                                    'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' => $scan->scan_type === 'deep',
                                    'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' => $scan->scan_type === 'quick',
                                ])>
                                    {{ ucfirst($scan->scan_type) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                {{ $scan->created_at->format('M j, Y g:i A') }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                <a
                                    href="{{ route('scans.show', $scan) }}"
                                    class="text-primary-600 dark:text-primary-400 hover:text-primary-900 dark:hover:text-primary-300"
                                >
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                <x-ui.icon name="document-magnifying-glass" class="mx-auto size-12 text-gray-400 mb-4" />
                                <p>No scans found for the selected filters.</p>
                                <a href="{{ route('scans.create') }}" class="mt-2 inline-block text-primary-600 dark:text-primary-400 hover:underline">
                                    Start a new scan
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($scans->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $scans->links() }}
            </div>
        @endif
    </div>
</div>
