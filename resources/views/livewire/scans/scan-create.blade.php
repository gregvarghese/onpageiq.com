<div>
    <x-slot name="header">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">New Scan</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Scan URLs for spelling, grammar, and content issues</p>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
        {{-- Main Form --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Project Selection --}}
            <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">Select Project</h2>
                </div>
                <div class="px-6 py-4">
                    <div class="flex gap-4">
                        <div class="flex-1">
                            <select
                                wire:model.live="selectedProjectId"
                                class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 px-3 py-2.5 sm:text-sm"
                            >
                                <option value="">Select a project...</option>
                                @foreach($projects as $project)
                                    <option value="{{ $project->id }}">{{ $project->name }}</option>
                                @endforeach
                            </select>
                            @error('selectedProjectId')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                        <button
                            wire:click="openNewProjectModal"
                            type="button"
                            class="inline-flex items-center gap-x-2 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600"
                        >
                            <x-ui.icon name="plus" class="size-4" />
                            New Project
                        </button>
                    </div>
                </div>
            </div>

            {{-- URLs Input --}}
            <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">URLs to Scan</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Enter one URL per line</p>
                </div>
                <div class="px-6 py-4">
                    <textarea
                        wire:model.live.debounce.500ms="urls"
                        rows="6"
                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 px-3 py-2 sm:text-sm font-mono"
                        placeholder="https://example.com&#10;https://example.com/about&#10;https://example.com/contact"
                    ></textarea>
                    @error('urls')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                    @if($urlCount > 0)
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            {{ $urlCount }} {{ Str::plural('URL', $urlCount) }} detected
                        </p>
                    @endif
                </div>
            </div>

            {{-- Scan Options --}}
            <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">Scan Options</h2>
                </div>
                <div class="px-6 py-4 space-y-6">
                    {{-- Scan Type --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Scan Type</label>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <label class="relative flex cursor-pointer rounded-lg border p-4 focus:outline-none @if($scanType === 'quick') border-primary-500 ring-2 ring-primary-500 bg-primary-50 dark:bg-primary-900/20 @else border-gray-200 dark:border-gray-700 @endif">
                                <input type="radio" wire:model.live="scanType" value="quick" class="sr-only">
                                <span class="flex flex-1">
                                    <span class="flex flex-col">
                                        <span class="block text-sm font-medium text-gray-900 dark:text-white">Quick Scan</span>
                                        <span class="mt-1 flex items-center text-sm text-gray-500 dark:text-gray-400">1 credit per URL</span>
                                        <span class="mt-1 text-sm text-gray-500 dark:text-gray-400">Fast results using GPT-4o-mini</span>
                                    </span>
                                </span>
                                @if($scanType === 'quick')
                                    <x-ui.icon name="check-circle" class="size-5 text-primary-600" />
                                @endif
                            </label>

                            <label class="relative flex cursor-pointer rounded-lg border p-4 focus:outline-none @if($scanType === 'deep') border-primary-500 ring-2 ring-primary-500 bg-primary-50 dark:bg-primary-900/20 @else border-gray-200 dark:border-gray-700 @endif">
                                <input type="radio" wire:model.live="scanType" value="deep" class="sr-only">
                                <span class="flex flex-1">
                                    <span class="flex flex-col">
                                        <span class="block text-sm font-medium text-gray-900 dark:text-white">Deep Scan</span>
                                        <span class="mt-1 flex items-center text-sm text-gray-500 dark:text-gray-400">3 credits per URL</span>
                                        <span class="mt-1 text-sm text-gray-500 dark:text-gray-400">Comprehensive analysis using GPT-4o</span>
                                    </span>
                                </span>
                                @if($scanType === 'deep')
                                    <x-ui.icon name="check-circle" class="size-5 text-primary-600" />
                                @endif
                            </label>
                        </div>
                    </div>

                    {{-- Check Types --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Checks to Perform</label>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <label class="flex items-center gap-3">
                                <input
                                    type="checkbox"
                                    wire:model.live="checkSpelling"
                                    class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500"
                                >
                                <div>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">Spelling</span>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Find misspelled words</p>
                                </div>
                            </label>

                            <label class="flex items-center gap-3 @if(!$organization->getAvailableChecks()['grammar']) opacity-50 @endif">
                                <input
                                    type="checkbox"
                                    wire:model.live="checkGrammar"
                                    class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500"
                                    @if(!$organization->getAvailableChecks()['grammar']) disabled @endif
                                >
                                <div>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">Grammar</span>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Check grammar rules</p>
                                </div>
                            </label>

                            <label class="flex items-center gap-3 @if(!$organization->getAvailableChecks()['seo']) opacity-50 @endif">
                                <input
                                    type="checkbox"
                                    wire:model.live="checkSeo"
                                    class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500"
                                    @if(!$organization->getAvailableChecks()['seo']) disabled @endif
                                >
                                <div>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">SEO</span>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">SEO best practices</p>
                                </div>
                            </label>

                            <label class="flex items-center gap-3 @if(!$organization->getAvailableChecks()['readability']) opacity-50 @endif">
                                <input
                                    type="checkbox"
                                    wire:model.live="checkReadability"
                                    class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500"
                                    @if(!$organization->getAvailableChecks()['readability']) disabled @endif
                                >
                                <div>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">Readability</span>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Reading level analysis</p>
                                </div>
                            </label>
                        </div>

                        @if($organization->isFreeTier())
                            <p class="mt-3 text-sm text-amber-600 dark:text-amber-400">
                                <x-ui.icon name="information-circle" class="inline size-4" />
                                Upgrade to Pro or higher to unlock all check types.
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Credit Summary --}}
            <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">Summary</h2>
                </div>
                <div class="px-6 py-4 space-y-4">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500 dark:text-gray-400">URLs</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $urlCount }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Scan type</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white capitalize">{{ $scanType }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Credits per URL</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $scanType === 'deep' ? 3 : 1 }}</span>
                    </div>
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                        <div class="flex justify-between">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">Total credits</span>
                            <span class="text-lg font-semibold text-primary-600 dark:text-primary-400">{{ $this->estimatedCredits }}</span>
                        </div>
                    </div>

                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500 dark:text-gray-400">Your balance</span>
                        <span class="font-medium @if($organization->credit_balance < $this->estimatedCredits) text-red-600 dark:text-red-400 @else text-gray-900 dark:text-white @endif">
                            {{ number_format($organization->credit_balance) }} credits
                        </span>
                    </div>

                    @if($organization->credit_balance < $this->estimatedCredits && $urlCount > 0)
                        <div class="rounded-md bg-red-50 dark:bg-red-900/30 p-3">
                            <p class="text-sm text-red-700 dark:text-red-300">
                                Insufficient credits. You need {{ $this->estimatedCredits - $organization->credit_balance }} more credits.
                            </p>
                            <a href="{{ route('billing.credits') }}" class="mt-2 inline-block text-sm font-medium text-red-700 dark:text-red-300 underline">
                                Buy credits
                            </a>
                        </div>
                    @endif

                    <button
                        wire:click="startScan"
                        wire:loading.attr="disabled"
                        class="w-full rounded-md bg-primary-600 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        @if($urlCount === 0 || !$selectedProjectId) disabled @endif
                    >
                        <span wire:loading.remove wire:target="startScan">
                            Start Scan
                        </span>
                        <span wire:loading wire:target="startScan">
                            Starting...
                        </span>
                    </button>
                </div>
            </div>

            {{-- Recent Scans --}}
            @if($recentScans->isNotEmpty())
                <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-white">Recent Scans</h2>
                    </div>
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($recentScans as $scan)
                            <a href="{{ route('scans.show', $scan) }}" class="block px-6 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                    {{ parse_url($scan->url->url, PHP_URL_HOST) }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $scan->url->project->name }} &bull; {{ $scan->created_at->diffForHumans() }}
                                </p>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- New Project Modal --}}
    @if ($showNewProjectModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75 transition-opacity" wire:click="$set('showNewProjectModal', false)"></div>
                <span class="hidden sm:inline-block sm:h-screen sm:align-middle">&#8203;</span>
                <div class="inline-block transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 px-4 pb-4 pt-5 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6 sm:align-middle">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Create New Project</h3>
                    <div class="mt-4 space-y-4">
                        <div>
                            <label for="project-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Project Name</label>
                            <input
                                type="text"
                                id="project-name"
                                wire:model="newProjectName"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 px-3 py-2 sm:text-sm"
                                placeholder="My Website"
                            >
                            @error('newProjectName')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="project-language" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Language</label>
                            <select
                                id="project-language"
                                wire:model="newProjectLanguage"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 px-3 py-2 sm:text-sm"
                            >
                                @foreach($languages as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('newProjectLanguage')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end gap-3">
                        <button wire:click="$set('showNewProjectModal', false)" class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600">
                            Cancel
                        </button>
                        <button wire:click="createProject" class="rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">
                            Create Project
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
