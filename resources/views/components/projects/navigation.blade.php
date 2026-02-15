@props(['project', 'current' => 'dashboard'])

<nav class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-12 items-center justify-between">
            {{-- Breadcrumb --}}
            <div class="flex items-center space-x-2 text-sm">
                <a href="{{ route('projects.index') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                    Projects
                </a>
                <x-ui.icon name="chevron-down" class="size-4 text-gray-400 -rotate-90" />
                <a href="{{ route('projects.show', $project) }}" class="font-medium text-gray-900 dark:text-white hover:text-gray-600 dark:hover:text-gray-300">
                    {{ $project->name }}
                </a>
            </div>

            {{-- Navigation Tabs --}}
            <div class="flex items-center space-x-1">
                <a
                    href="{{ route('projects.show', $project) }}"
                    @class([
                        'inline-flex items-center gap-x-2 px-3 py-2 text-sm font-medium rounded-md transition-colors',
                        'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' => $current === 'dashboard',
                        'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-700/50' => $current !== 'dashboard',
                    ])
                >
                    <x-ui.icon name="home" class="size-4" />
                    <span class="hidden sm:inline">Dashboard</span>
                </a>

                <a
                    href="{{ route('projects.architecture', $project) }}"
                    @class([
                        'inline-flex items-center gap-x-2 px-3 py-2 text-sm font-medium rounded-md transition-colors',
                        'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' => $current === 'architecture',
                        'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-700/50' => $current !== 'architecture',
                    ])
                >
                    <x-ui.icon name="share" class="size-4" />
                    <span class="hidden sm:inline">Architecture</span>
                </a>

                <a
                    href="{{ route('projects.dictionary', $project) }}"
                    @class([
                        'inline-flex items-center gap-x-2 px-3 py-2 text-sm font-medium rounded-md transition-colors',
                        'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' => $current === 'dictionary',
                        'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-700/50' => $current !== 'dictionary',
                    ])
                >
                    <x-ui.icon name="book-open" class="size-4" />
                    <span class="hidden sm:inline">Dictionary</span>
                </a>

            </div>
        </div>
    </div>
</nav>
