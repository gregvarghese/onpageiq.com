<div>
    @if($isOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            {{-- Backdrop --}}
            <div
                class="fixed inset-0 bg-gray-500/75 dark:bg-gray-900/75 transition-opacity"
                wire:click="close"
            ></div>

            {{-- Modal --}}
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                    {{-- Header --}}
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white" id="modal-title">
                            Crawl Configuration
                        </h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Configure how the site architecture crawler should behave.
                        </p>
                    </div>

                    {{-- Content --}}
                    <div class="px-6 py-4 space-y-4 max-h-[60vh] overflow-y-auto">
                        {{-- Max Depth --}}
                        <div>
                            <label for="maxDepth" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Maximum Crawl Depth
                            </label>
                            <input
                                type="number"
                                id="maxDepth"
                                wire:model="maxDepth"
                                min="1"
                                max="10"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                            >
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                How many clicks from the homepage to crawl (1-10)
                            </p>
                            @error('maxDepth')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Max Pages --}}
                        <div>
                            <label for="maxPages" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Maximum Pages
                            </label>
                            <input
                                type="number"
                                id="maxPages"
                                wire:model="maxPages"
                                min="10"
                                max="10000"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                            >
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Maximum number of pages to crawl (10-10,000)
                            </p>
                            @error('maxPages')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Timeout --}}
                        <div>
                            <label for="timeout" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Page Timeout (ms)
                            </label>
                            <input
                                type="number"
                                id="timeout"
                                wire:model="timeout"
                                min="100"
                                max="60000"
                                step="100"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                            >
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Timeout per page in milliseconds (100-60,000)
                            </p>
                            @error('timeout')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Exclude Patterns --}}
                        <div>
                            <label for="excludePatterns" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Exclude URL Patterns
                            </label>
                            <textarea
                                id="excludePatterns"
                                wire:model="excludePatterns"
                                rows="3"
                                placeholder="/admin/*&#10;/api/*&#10;*.pdf"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm font-mono"
                            ></textarea>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                One pattern per line. Use * as wildcard.
                            </p>
                            @error('excludePatterns')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Include Patterns --}}
                        <div>
                            <label for="includePatterns" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Include URL Patterns (Optional)
                            </label>
                            <textarea
                                id="includePatterns"
                                wire:model="includePatterns"
                                rows="3"
                                placeholder="/blog/*&#10;/products/*"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm font-mono"
                            ></textarea>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Only crawl URLs matching these patterns. Leave empty to crawl all.
                            </p>
                            @error('includePatterns')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Toggle Options --}}
                        <div class="space-y-3 pt-2">
                            {{-- Respect robots.txt --}}
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input
                                    type="checkbox"
                                    wire:model="respectRobotsTxt"
                                    class="h-4 w-4 rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500"
                                >
                                <div>
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Respect robots.txt
                                    </span>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        Skip URLs disallowed by the site's robots.txt
                                    </p>
                                </div>
                            </label>

                            {{-- Enable JS Rendering --}}
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input
                                    type="checkbox"
                                    wire:model="enableJsRendering"
                                    class="h-4 w-4 rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500"
                                >
                                <div>
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Enable JavaScript Rendering
                                    </span>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        Use headless browser for SPAs (slower but more accurate)
                                    </p>
                                </div>
                            </label>

                            {{-- Follow External Links --}}
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input
                                    type="checkbox"
                                    wire:model="followExternalLinks"
                                    class="h-4 w-4 rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500"
                                >
                                <div>
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Track External Links
                                    </span>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        Record outbound links to external domains
                                    </p>
                                </div>
                            </label>

                            {{-- Divider --}}
                            <div class="border-t border-gray-200 dark:border-gray-700 pt-3">
                                {{-- Save as Defaults --}}
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        wire:model="saveAsDefaults"
                                        class="h-4 w-4 rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500"
                                    >
                                    <div>
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Save as project defaults
                                        </span>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            Remember these settings for future crawls
                                        </p>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
                        <button
                            type="button"
                            wire:click="close"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            wire:click="startCrawl"
                            wire:loading.attr="disabled"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 rounded-lg transition-colors flex items-center gap-2"
                        >
                            <span wire:loading.remove wire:target="startCrawl">Start Crawl</span>
                            <span wire:loading wire:target="startCrawl">
                                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Starting...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
