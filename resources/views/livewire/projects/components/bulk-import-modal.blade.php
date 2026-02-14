<div>
    <!-- Trigger Button -->
    <button
        wire:click="openModal"
        type="button"
        class="inline-flex items-center gap-x-2 rounded-md bg-white dark:bg-gray-800 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700"
    >
        <x-ui.icon name="arrow-up-tray" class="size-4" />
        Import URLs
    </button>

    <!-- Modal -->
    @if($showModal)
        <div
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title"
            role="dialog"
            aria-modal="true"
        >
            <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div
                    wire:click="closeModal"
                    class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity dark:bg-gray-900 dark:bg-opacity-75"
                    aria-hidden="true"
                ></div>

                <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>

                <div class="relative inline-block transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl sm:align-middle">
                    <div class="px-4 pb-4 pt-5 sm:p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white" id="modal-title">
                                Import URLs
                            </h3>
                            <button
                                wire:click="closeModal"
                                type="button"
                                class="rounded-md text-gray-400 hover:text-gray-500 dark:hover:text-gray-300"
                            >
                                <x-ui.icon name="x-mark" class="size-6" />
                            </button>
                        </div>

                        <!-- Import Method Tabs -->
                        <div class="border-b border-gray-200 dark:border-gray-700 mb-4">
                            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                                <button
                                    wire:click="$set('importMethod', 'paste')"
                                    type="button"
                                    class="whitespace-nowrap border-b-2 py-2 px-1 text-sm font-medium {{ $importMethod === 'paste' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}"
                                >
                                    Paste URLs
                                </button>
                                <button
                                    wire:click="$set('importMethod', 'sitemap')"
                                    type="button"
                                    class="whitespace-nowrap border-b-2 py-2 px-1 text-sm font-medium {{ $importMethod === 'sitemap' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}"
                                >
                                    From Sitemap
                                </button>
                            </nav>
                        </div>

                        <!-- Error Message -->
                        @if($errorMessage)
                            <div class="mb-4 rounded-md bg-red-50 dark:bg-red-900/30 p-3">
                                <p class="text-sm text-red-700 dark:text-red-400">{{ $errorMessage }}</p>
                            </div>
                        @endif

                        <!-- Paste URLs Method -->
                        @if($importMethod === 'paste' && empty($discoveredUrls))
                            <div>
                                <label for="urls-text" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Paste URLs (one per line)
                                </label>
                                <textarea
                                    id="urls-text"
                                    wire:model="urlsText"
                                    rows="8"
                                    class="block w-full rounded-md border-0 px-3 py-2 text-gray-900 dark:text-white bg-white dark:bg-gray-700 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm font-mono"
                                    placeholder="https://example.com/page1&#10;https://example.com/page2&#10;https://example.com/page3"
                                ></textarea>
                                <div class="mt-4 flex justify-end">
                                    <button
                                        wire:click="parseUrls"
                                        type="button"
                                        class="inline-flex items-center gap-x-2 rounded-md bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500"
                                    >
                                        Parse URLs
                                    </button>
                                </div>
                            </div>
                        @endif

                        <!-- Sitemap Method -->
                        @if($importMethod === 'sitemap' && empty($discoveredUrls))
                            <div>
                                <label for="sitemap-url" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Sitemap URL
                                </label>
                                <div class="flex gap-x-3">
                                    <input
                                        type="url"
                                        id="sitemap-url"
                                        wire:model="sitemapUrl"
                                        class="block flex-1 rounded-md border-0 px-3 py-2 text-gray-900 dark:text-white bg-white dark:bg-gray-700 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm"
                                        placeholder="https://example.com/sitemap.xml"
                                    />
                                    <button
                                        wire:click="fetchSitemap"
                                        wire:loading.attr="disabled"
                                        wire:target="fetchSitemap"
                                        type="button"
                                        class="inline-flex items-center gap-x-2 rounded-md bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 disabled:opacity-50"
                                    >
                                        <x-ui.icon name="arrow-path" class="size-4" wire:loading.class="animate-spin" wire:target="fetchSitemap" />
                                        Fetch
                                    </button>
                                </div>
                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    Enter the URL of your sitemap.xml file to discover pages.
                                </p>
                            </div>
                        @endif

                        <!-- Discovered URLs List -->
                        @if(!empty($discoveredUrls))
                            <div>
                                <div class="flex items-center justify-between mb-3">
                                    <p class="text-sm text-gray-700 dark:text-gray-300">
                                        Found {{ count($discoveredUrls) }} URLs. {{ $selectedCount }} selected.
                                    </p>
                                    <div class="flex gap-x-2">
                                        <button
                                            wire:click="selectAll"
                                            type="button"
                                            class="text-xs text-primary-600 dark:text-primary-400 hover:underline"
                                        >
                                            Select All
                                        </button>
                                        <button
                                            wire:click="deselectAll"
                                            type="button"
                                            class="text-xs text-gray-500 dark:text-gray-400 hover:underline"
                                        >
                                            Deselect All
                                        </button>
                                    </div>
                                </div>

                                <div class="max-h-64 overflow-y-auto rounded-md border border-gray-200 dark:border-gray-700">
                                    <ul role="list" class="divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach($discoveredUrls as $index => $urlData)
                                            <li
                                                wire:key="discovered-{{ $index }}"
                                                wire:click="toggleUrl({{ $index }})"
                                                class="flex items-center gap-x-3 px-3 py-2 {{ $urlData['existing'] ? 'bg-gray-50 dark:bg-gray-700/50 opacity-60' : 'cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50' }}"
                                            >
                                                <input
                                                    type="checkbox"
                                                    @checked($urlData['selected'])
                                                    @disabled($urlData['existing'])
                                                    class="h-4 w-4 rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-600 disabled:opacity-50"
                                                />
                                                <span class="text-sm text-gray-900 dark:text-white truncate flex-1">
                                                    {{ $urlData['url'] }}
                                                </span>
                                                @if($urlData['existing'])
                                                    <span class="text-xs text-gray-500 dark:text-gray-400">Already added</span>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>

                                <div class="mt-4 flex justify-between">
                                    <button
                                        wire:click="$set('discoveredUrls', [])"
                                        type="button"
                                        class="inline-flex items-center gap-x-2 rounded-md bg-white dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600"
                                    >
                                        Back
                                    </button>
                                    <button
                                        wire:click="importSelected"
                                        wire:loading.attr="disabled"
                                        wire:target="importSelected"
                                        type="button"
                                        class="inline-flex items-center gap-x-2 rounded-md bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 disabled:opacity-50"
                                        @disabled($selectedCount === 0)
                                    >
                                        Import {{ $selectedCount }} {{ Str::plural('URL', $selectedCount) }}
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
