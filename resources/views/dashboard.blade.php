<x-layouts.app>
    <x-slot:title>Dashboard - OnPageIQ</x-slot:title>

    <x-slot:header>
        <h1 class="text-2xl font-bold leading-7 text-gray-900 dark:text-white sm:truncate sm:text-3xl sm:tracking-tight">
            Dashboard
        </h1>
    </x-slot:header>

    <!-- Stats cards -->
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Total Scans -->
        <div class="overflow-hidden rounded-lg bg-white dark:bg-gray-900 px-4 py-5 shadow ring-1 ring-gray-900/5 dark:ring-white/10 sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">Total Scans</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">0</dd>
        </div>

        <!-- Active Projects -->
        <div class="overflow-hidden rounded-lg bg-white dark:bg-gray-900 px-4 py-5 shadow ring-1 ring-gray-900/5 dark:ring-white/10 sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">Active Projects</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">0</dd>
        </div>

        <!-- Issues Found -->
        <div class="overflow-hidden rounded-lg bg-white dark:bg-gray-900 px-4 py-5 shadow ring-1 ring-gray-900/5 dark:ring-white/10 sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">Issues Found</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">0</dd>
        </div>

        <!-- Credits Remaining -->
        <div class="overflow-hidden rounded-lg bg-white dark:bg-gray-900 px-4 py-5 shadow ring-1 ring-gray-900/5 dark:ring-white/10 sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">Credits Remaining</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-primary-600 dark:text-primary-400">5</dd>
        </div>
    </div>

    <!-- Recent Activity & Projects Grid -->
    <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Recent Activity -->
        <div class="overflow-hidden rounded-lg bg-white dark:bg-gray-900 shadow ring-1 ring-gray-900/5 dark:ring-white/10">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-800">
                <h3 class="text-base font-semibold leading-6 text-gray-900 dark:text-white">Recent Activity</h3>
            </div>
            <div class="px-4 py-5 sm:p-6">
                <div class="text-center py-8">
                    <x-ui.icon name="document-text" class="mx-auto size-12 text-gray-400 dark:text-gray-600" />
                    <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">No recent scans</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by creating your first project and running a scan.</p>
                    <div class="mt-6">
                        <a href="{{ route('scans.create') }}" class="inline-flex items-center rounded-md bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600">
                            <x-ui.icon name="plus-circle" class="-ml-0.5 mr-1.5 size-5" />
                            New Scan
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Projects Overview -->
        <div class="overflow-hidden rounded-lg bg-white dark:bg-gray-900 shadow ring-1 ring-gray-900/5 dark:ring-white/10">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                <h3 class="text-base font-semibold leading-6 text-gray-900 dark:text-white">Projects</h3>
                <a href="{{ route('projects.index') }}" class="text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300">
                    View all &rarr;
                </a>
            </div>
            <div class="px-4 py-5 sm:p-6">
                <div class="text-center py-8">
                    <x-ui.icon name="folder" class="mx-auto size-12 text-gray-400 dark:text-gray-600" />
                    <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">No projects</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Create a project to organize your URLs and scans.</p>
                    <div class="mt-6">
                        <a href="{{ route('projects.create') }}" class="inline-flex items-center rounded-md bg-white dark:bg-gray-800 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-white shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                            <x-ui.icon name="plus-circle" class="-ml-0.5 mr-1.5 size-5 text-gray-400" />
                            New Project
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Start Guide -->
    <div class="mt-8 overflow-hidden rounded-lg bg-primary-50 dark:bg-primary-950/50 shadow ring-1 ring-primary-200 dark:ring-primary-800">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-base font-semibold leading-6 text-primary-900 dark:text-primary-100">Getting Started</h3>
            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="flex items-start gap-x-3">
                    <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-primary-600 text-white text-sm font-semibold">1</div>
                    <div>
                        <p class="text-sm font-medium text-primary-900 dark:text-primary-100">Create a project</p>
                        <p class="mt-1 text-sm text-primary-700 dark:text-primary-300">Organize your URLs into projects for easy management.</p>
                    </div>
                </div>
                <div class="flex items-start gap-x-3">
                    <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-primary-600 text-white text-sm font-semibold">2</div>
                    <div>
                        <p class="text-sm font-medium text-primary-900 dark:text-primary-100">Add URLs</p>
                        <p class="mt-1 text-sm text-primary-700 dark:text-primary-300">Add the pages you want to check for spelling and grammar.</p>
                    </div>
                </div>
                <div class="flex items-start gap-x-3">
                    <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-primary-600 text-white text-sm font-semibold">3</div>
                    <div>
                        <p class="text-sm font-medium text-primary-900 dark:text-primary-100">Run a scan</p>
                        <p class="mt-1 text-sm text-primary-700 dark:text-primary-300">Get a comprehensive report with issues and suggestions.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
