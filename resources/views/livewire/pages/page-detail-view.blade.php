<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <nav class="flex" aria-label="Breadcrumb">
                    <ol role="list" class="flex items-center space-x-2">
                        <li>
                            <a href="{{ route('projects.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">Projects</a>
                        </li>
                        <li class="flex items-center">
                            <x-ui.icon name="chevron-down" class="size-4 text-gray-400 -rotate-90" />
                            <a href="{{ route('projects.show', $url->project) }}" class="ml-2 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">{{ $url->project->name }}</a>
                        </li>
                        <li class="flex items-center">
                            <x-ui.icon name="chevron-down" class="size-4 text-gray-400 -rotate-90" />
                            <span class="ml-2 text-sm font-medium text-gray-900 dark:text-white truncate max-w-xs">{{ parse_url($url->url, PHP_URL_PATH) ?: '/' }}</span>
                        </li>
                    </ol>
                </nav>
                <h1 class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white truncate">{{ $url->url }}</h1>
            </div>
            <div class="flex items-center gap-x-3">
                @php $architectureNode = $this->architectureNode; @endphp
                @if($architectureNode)
                    <a href="{{ route('projects.architecture', ['project' => $url->project, 'node' => $architectureNode->id]) }}" class="inline-flex items-center gap-x-2 rounded-md bg-indigo-50 dark:bg-indigo-900/30 px-3.5 py-2.5 text-sm font-semibold text-indigo-700 dark:text-indigo-300 shadow-sm ring-1 ring-inset ring-indigo-200 dark:ring-indigo-800 hover:bg-indigo-100 dark:hover:bg-indigo-900/50">
                        <x-ui.icon name="share" class="size-5" />
                        View in Architecture
                    </a>
                @endif
                <a href="{{ $url->url }}" target="_blank" class="inline-flex items-center gap-x-2 rounded-md bg-white dark:bg-gray-800 px-3.5 py-2.5 text-sm font-semibold text-gray-900 dark:text-white shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                    <x-ui.icon name="arrow-top-right-on-square" class="size-5" />
                    Visit Page
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Screenshots Section -->
        @php $screenshots = $this->screenshots; @endphp
        @if($screenshots['desktop'] || $screenshots['mobile'])
            <div class="rounded-lg bg-white dark:bg-gray-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Screenshots</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @if($screenshots['desktop'])
                        <div>
                            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Desktop</h3>
                            <img src="{{ Storage::url($screenshots['desktop']->file_path) }}" alt="Desktop screenshot" class="rounded-lg border border-gray-200 dark:border-gray-700 w-full" />
                        </div>
                    @endif
                    @if($screenshots['mobile'])
                        <div>
                            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Mobile</h3>
                            <img src="{{ Storage::url($screenshots['mobile']->file_path) }}" alt="Mobile screenshot" class="rounded-lg border border-gray-200 dark:border-gray-700 max-w-xs mx-auto" />
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <!-- Core Web Vitals -->
        @php $vitals = $this->coreWebVitals; @endphp
        <div class="rounded-lg bg-white dark:bg-gray-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Core Web Vitals</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- LCP -->
                <div class="text-center p-4 rounded-lg {{ $vitals['lcp']['status'] === 'good' ? 'bg-green-50 dark:bg-green-900/20' : ($vitals['lcp']['status'] === 'needs-improvement' ? 'bg-yellow-50 dark:bg-yellow-900/20' : 'bg-red-50 dark:bg-red-900/20') }}">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">LCP</p>
                    <p class="text-2xl font-bold {{ $vitals['lcp']['status'] === 'good' ? 'text-green-600 dark:text-green-400' : ($vitals['lcp']['status'] === 'needs-improvement' ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400') }}">
                        {{ $vitals['lcp']['value'] ? number_format($vitals['lcp']['value'] / 1000, 2) . 's' : 'N/A' }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Largest Contentful Paint</p>
                </div>
                <!-- FID -->
                <div class="text-center p-4 rounded-lg {{ $vitals['fid']['status'] === 'good' ? 'bg-green-50 dark:bg-green-900/20' : ($vitals['fid']['status'] === 'needs-improvement' ? 'bg-yellow-50 dark:bg-yellow-900/20' : 'bg-red-50 dark:bg-red-900/20') }}">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">FID</p>
                    <p class="text-2xl font-bold {{ $vitals['fid']['status'] === 'good' ? 'text-green-600 dark:text-green-400' : ($vitals['fid']['status'] === 'needs-improvement' ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400') }}">
                        {{ $vitals['fid']['value'] ? number_format($vitals['fid']['value']) . 'ms' : 'N/A' }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">First Input Delay</p>
                </div>
                <!-- CLS -->
                <div class="text-center p-4 rounded-lg {{ $vitals['cls']['status'] === 'good' ? 'bg-green-50 dark:bg-green-900/20' : ($vitals['cls']['status'] === 'needs-improvement' ? 'bg-yellow-50 dark:bg-yellow-900/20' : 'bg-red-50 dark:bg-red-900/20') }}">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">CLS</p>
                    <p class="text-2xl font-bold {{ $vitals['cls']['status'] === 'good' ? 'text-green-600 dark:text-green-400' : ($vitals['cls']['status'] === 'needs-improvement' ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400') }}">
                        {{ $vitals['cls']['value'] !== null ? number_format($vitals['cls']['value'], 3) : 'N/A' }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Cumulative Layout Shift</p>
                </div>
            </div>
        </div>

        <!-- Performance & Readability Metrics -->
        @php $metrics = $this->metrics; $readability = $this->readability; @endphp
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Performance Metrics -->
            <div class="rounded-lg bg-white dark:bg-gray-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Performance</h2>
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Load Time</dt>
                        <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $metrics?->load_time_ms ? number_format($metrics->load_time_ms / 1000, 2) . 's' : 'N/A' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Page Size</dt>
                        <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $metrics?->page_size_bytes ? number_format($metrics->page_size_bytes / 1024, 1) . ' KB' : 'N/A' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Requests</dt>
                        <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $metrics?->request_count ?? 'N/A' }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Readability -->
            <div class="rounded-lg bg-white dark:bg-gray-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Readability</h2>
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Grade Level</dt>
                        <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $readability['grade'] ? 'Grade ' . $readability['grade'] : 'N/A' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Reading Ease</dt>
                        <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $readability['ease'] ? $readability['ease'] . '/100' : 'N/A' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Word Count</dt>
                        <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $readability['wordCount'] ? number_format($readability['wordCount']) : 'N/A' }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Issues List -->
        @php $issuesByCategory = $this->issuesByCategory; @endphp
        <div class="rounded-lg bg-white dark:bg-gray-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Issues</h2>

            @php $totalIssues = collect($issuesByCategory)->flatten()->count(); @endphp
            @if($totalIssues === 0)
                <div class="text-center py-8">
                    <x-ui.icon name="check-circle" class="size-12 text-green-500 mx-auto" />
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No issues found on this page!</p>
                </div>
            @else
                <div class="space-y-4">
                    @foreach($issuesByCategory as $category => $issues)
                        @if($issues->isNotEmpty())
                            <div>
                                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 capitalize mb-2">{{ $category }} ({{ $issues->count() }})</h3>
                                <ul class="space-y-2">
                                    @foreach($issues as $issue)
                                        <li class="flex items-start gap-x-3 p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                                            <span class="flex-shrink-0 size-2 mt-2 rounded-full {{ $issue->severity === 'error' ? 'bg-red-500' : ($issue->severity === 'warning' ? 'bg-yellow-500' : 'bg-blue-500') }}"></span>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm text-gray-900 dark:text-white">{{ $issue->message }}</p>
                                                @if($issue->suggestion)
                                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                                        Suggestion: <span class="text-primary-600 dark:text-primary-400">{{ $issue->suggestion }}</span>
                                                    </p>
                                                @endif
                                                @if($issue->context)
                                                    <p class="mt-1 text-xs text-gray-400 dark:text-gray-500 font-mono truncate">"{{ $issue->context }}"</p>
                                                @endif
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Broken Links -->
        @php $brokenLinks = $this->brokenLinks; @endphp
        @if($brokenLinks->isNotEmpty())
            <div class="rounded-lg bg-white dark:bg-gray-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Broken Links ({{ $brokenLinks->count() }})</h2>
                <ul class="space-y-2">
                    @foreach($brokenLinks as $link)
                        <li class="flex items-center justify-between p-3 rounded-lg bg-red-50 dark:bg-red-900/20">
                            <div class="flex items-center gap-x-3 min-w-0">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $link->status === 'broken' ? 'bg-red-100 text-red-700 dark:bg-red-800 dark:text-red-300' : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-800 dark:text-yellow-300' }}">
                                    {{ $link->status_code ?? strtoupper($link->status) }}
                                </span>
                                <span class="text-sm text-gray-900 dark:text-white truncate">{{ $link->link_url }}</span>
                            </div>
                            <span class="text-xs text-gray-500 dark:text-gray-400 capitalize">{{ $link->link_type }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Schema Validations -->
        @php $schemas = $this->schemaValidations; @endphp
        @if($schemas->isNotEmpty())
            <div class="rounded-lg bg-white dark:bg-gray-800 p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Structured Data</h2>
                <div class="space-y-3">
                    @foreach($schemas as $schema)
                        <div class="p-4 rounded-lg {{ $schema->is_valid ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20' }}">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $schema->schema_type }}</span>
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $schema->is_valid ? 'bg-green-100 text-green-700 dark:bg-green-800 dark:text-green-300' : 'bg-red-100 text-red-700 dark:bg-red-800 dark:text-red-300' }}">
                                    {{ $schema->is_valid ? 'Valid' : 'Invalid' }}
                                </span>
                            </div>
                            @if($schema->errors)
                                <ul class="mt-2 space-y-1">
                                    @foreach($schema->errors as $error)
                                        <li class="text-xs text-red-600 dark:text-red-400">{{ $error }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
