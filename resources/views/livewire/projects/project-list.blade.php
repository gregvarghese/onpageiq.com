<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Projects</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage your website projects and scans</p>
            </div>
            <a
                href="{{ route('projects.create') }}"
                class="inline-flex items-center gap-x-2 rounded-md bg-primary-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"
            >
                <x-ui.icon name="plus" class="size-5" />
                New Project
            </a>
        </div>
    </x-slot>

    <!-- Search -->
    <div class="mb-6">
        <div class="relative">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <x-ui.icon name="magnifying-glass" class="size-5 text-gray-400" />
            </div>
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Search projects..."
                class="block w-full rounded-md border-0 py-2.5 pl-10 pr-3 text-gray-900 dark:text-white bg-white dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6"
            />
        </div>
    </div>

    <!-- Projects Grid -->
    @if($projects->isEmpty())
        <div class="text-center py-12">
            <x-ui.icon name="folder" class="mx-auto size-12 text-gray-400" />
            <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">No projects</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by creating a new project.</p>
            <div class="mt-6">
                <a
                    href="{{ route('projects.create') }}"
                    class="inline-flex items-center gap-x-2 rounded-md bg-primary-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-500"
                >
                    <x-ui.icon name="plus" class="size-5" />
                    New Project
                </a>
            </div>
        </div>
    @else
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($projects as $project)
                <a
                    href="{{ route('projects.show', $project) }}"
                    wire:key="project-{{ $project->id }}"
                    class="group relative flex flex-col rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm hover:shadow-md hover:border-primary-300 dark:hover:border-primary-700 transition-all duration-200"
                >
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-x-3">
                            <div class="flex size-10 items-center justify-center rounded-lg bg-primary-50 dark:bg-primary-900/30">
                                <x-ui.icon name="folder" class="size-5 text-primary-600 dark:text-primary-400" />
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400">
                                    {{ $project->name }}
                                </h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $project->urls_count }} {{ Str::plural('URL', $project->urls_count) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    @if($project->description)
                        <p class="mt-3 text-sm text-gray-500 dark:text-gray-400 line-clamp-2">
                            {{ $project->description }}
                        </p>
                    @endif

                    <div class="mt-4 flex items-center gap-x-4 text-xs text-gray-500 dark:text-gray-400">
                        <span class="inline-flex items-center gap-x-1">
                            <x-ui.icon name="language" class="size-4" />
                            {{ strtoupper($project->language ?? 'en') }}
                        </span>
                        <span class="inline-flex items-center gap-x-1">
                            <x-ui.icon name="clock" class="size-4" />
                            {{ $project->created_at->diffForHumans() }}
                        </span>
                    </div>
                </a>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $projects->links() }}
        </div>
    @endif
</div>
