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
                            <span class="ml-2 text-sm font-medium text-gray-900 dark:text-white">{{ $project->name }}</span>
                        </li>
                    </ol>
                </nav>
                <h1 class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">{{ $project->name }}</h1>
                @if($project->description)
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $project->description }}</p>
                @endif
            </div>
            <div class="flex items-center gap-x-3">
                <!-- Scan Type Selector -->
                <select
                    x-data
                    x-on:change="Livewire.dispatch('set-scan-type', { type: $event.target.value })"
                    class="rounded-md border-0 py-2 pl-3 pr-8 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-primary-600"
                >
                    <option value="quick" {{ $scanType === 'quick' ? 'selected' : '' }}>Quick Scan</option>
                    <option value="deep" {{ $scanType === 'deep' ? 'selected' : '' }}>Deep Scan</option>
                </select>

                <button
                    type="button"
                    x-data="{ loading: false }"
                    x-on:click="loading = true; Livewire.dispatch('trigger-scan-all'); setTimeout(() => loading = false, 2000)"
                    x-bind:disabled="loading"
                    class="inline-flex items-center gap-x-2 rounded-md bg-primary-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <x-ui.icon name="arrow-path" class="size-5" x-bind:class="loading && 'animate-spin'" />
                    Scan All
                </button>
            </div>
        </div>
    </x-slot>

    <!-- Add URL Form -->
    <div class="mb-6">
        <form wire:submit="addUrl" class="flex gap-x-3">
            <div class="flex-1">
                <label for="newUrl" class="sr-only">URL</label>
                <input
                    type="url"
                    id="newUrl"
                    wire:model="newUrl"
                    placeholder="https://example.com/page"
                    class="block w-full rounded-md border-0 px-3 py-2.5 text-gray-900 dark:text-white bg-white dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6"
                />
                @error('newUrl')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="addUrl"
                class="inline-flex items-center gap-x-2 rounded-md bg-gray-900 dark:bg-white px-3.5 py-2.5 text-sm font-semibold text-white dark:text-gray-900 shadow-sm hover:bg-gray-700 dark:hover:bg-gray-100 disabled:opacity-50"
            >
                <x-ui.icon name="plus" class="size-5" />
                Add URL
            </button>
        </form>
    </div>

    <!-- URLs List -->
    @if($urls->isEmpty())
        <div class="text-center py-12 border border-dashed border-gray-300 dark:border-gray-700 rounded-lg">
            <x-ui.icon name="link" class="mx-auto size-12 text-gray-400" />
            <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">No URLs</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Add URLs to start scanning your content.</p>
        </div>
    @else
        <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
            <ul role="list" class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($urls as $url)
                    <li wire:key="url-{{ $url->id }}" class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <div class="flex items-center justify-between gap-x-4">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-x-3">
                                    <!-- Status Indicator -->
                                    @if($url->status === 'completed')
                                        <span class="flex size-2.5 rounded-full bg-green-500" title="Completed"></span>
                                    @elseif($url->status === 'scanning' || $url->status === 'pending')
                                        <span class="flex size-2.5 rounded-full bg-yellow-500 animate-pulse" title="Scanning"></span>
                                    @elseif($url->status === 'failed')
                                        <span class="flex size-2.5 rounded-full bg-red-500" title="Failed"></span>
                                    @else
                                        <span class="flex size-2.5 rounded-full bg-gray-400" title="Not scanned"></span>
                                    @endif

                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                        {{ $url->url }}
                                    </p>
                                </div>

                                <div class="mt-1 flex items-center gap-x-4 text-xs text-gray-500 dark:text-gray-400">
                                    @if($url->last_scanned_at)
                                        <span>Last scanned {{ $url->last_scanned_at->diffForHumans() }}</span>
                                    @else
                                        <span>Never scanned</span>
                                    @endif

                                    @if($url->latestScan?->result)
                                        @php $issueCount = $url->latestScan->result->getTotalIssueCount(); @endphp
                                        <span class="inline-flex items-center gap-x-1">
                                            @if($issueCount === 0)
                                                <x-ui.icon name="check-circle" class="size-4 text-green-500" />
                                                No issues
                                            @else
                                                <x-ui.icon name="exclamation-circle" class="size-4 text-yellow-500" />
                                                {{ $issueCount }} {{ Str::plural('issue', $issueCount) }}
                                            @endif
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-center gap-x-2">
                                @if($url->latestScan)
                                    <a
                                        href="{{ route('scans.show', $url->latestScan) }}"
                                        class="rounded-md px-2.5 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                                    >
                                        <x-ui.icon name="eye" class="size-5" />
                                    </a>
                                @endif

                                <button
                                    wire:click="scanUrl({{ $url->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="scanUrl({{ $url->id }})"
                                    @if($url->isScanning()) disabled @endif
                                    class="rounded-md px-2.5 py-1.5 text-sm font-medium text-primary-600 dark:text-primary-400 hover:bg-primary-50 dark:hover:bg-primary-900/30 disabled:opacity-50 disabled:cursor-not-allowed"
                                    title="Scan URL"
                                >
                                    <x-ui.icon
                                        name="arrow-path"
                                        class="size-5"
                                        wire:loading.class="animate-spin"
                                        wire:target="scanUrl({{ $url->id }})"
                                    />
                                </button>

                                <button
                                    wire:click="deleteUrl({{ $url->id }})"
                                    wire:confirm="Are you sure you want to delete this URL? All scan history will be lost."
                                    class="rounded-md px-2.5 py-1.5 text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30"
                                    title="Delete URL"
                                >
                                    <x-ui.icon name="trash" class="size-5" />
                                </button>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
