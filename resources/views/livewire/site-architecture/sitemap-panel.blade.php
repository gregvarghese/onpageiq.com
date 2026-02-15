<div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700">
    {{-- Header with Tabs --}}
    <div class="border-b border-zinc-200 dark:border-zinc-700">
        <div class="p-4">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Sitemap Tools</h3>
        </div>
        <div class="flex gap-1 px-4">
            <button
                wire:click="setTab('generate')"
                class="px-4 py-2 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'generate' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-300' }}"
            >
                Generate
            </button>
            <button
                wire:click="setTab('validate')"
                class="px-4 py-2 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'validate' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-300' }}"
            >
                Validate
            </button>
            <button
                wire:click="setTab('visual')"
                class="px-4 py-2 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'visual' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-300' }}"
            >
                Visual
            </button>
        </div>
    </div>

    @if (!$this->architecture)
        <div class="p-8 text-center">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">No architecture data available</p>
        </div>
    @else
        {{-- Generate Tab --}}
        @if ($activeTab === 'generate')
            <div class="p-4 space-y-4">
                {{-- Stats --}}
                <div class="grid grid-cols-4 gap-4">
                    <div class="bg-zinc-50 dark:bg-zinc-700/50 rounded-lg p-3 text-center">
                        <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $this->stats['total_urls'] ?? 0 }}</div>
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">Total URLs</div>
                    </div>
                    <div class="bg-zinc-50 dark:bg-zinc-700/50 rounded-lg p-3 text-center">
                        <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $this->stats['sitemap_count'] ?? 1 }}</div>
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">Sitemap Files</div>
                    </div>
                    <div class="bg-zinc-50 dark:bg-zinc-700/50 rounded-lg p-3 text-center">
                        <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $this->stats['max_depth'] ?? 0 }}</div>
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">Max Depth</div>
                    </div>
                    <div class="bg-zinc-50 dark:bg-zinc-700/50 rounded-lg p-3 text-center">
                        <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($this->stats['avg_priority'] ?? 0, 2) }}</div>
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">Avg Priority</div>
                    </div>
                </div>

                {{-- Format Selection --}}
                <div class="flex items-center gap-4">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Format:</label>
                    <div class="flex gap-2">
                        <button
                            wire:click="$set('sitemapFormat', 'xml')"
                            class="px-3 py-1.5 text-sm rounded-md {{ $sitemapFormat === 'xml' ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-400' }}"
                        >
                            XML Sitemap
                        </button>
                        <button
                            wire:click="$set('sitemapFormat', 'html')"
                            class="px-3 py-1.5 text-sm rounded-md {{ $sitemapFormat === 'html' ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-400' }}"
                        >
                            HTML Sitemap
                        </button>
                    </div>

                    @if ($sitemapFormat === 'html')
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300 ml-4">Layout:</label>
                        <select wire:model.live="htmlLayout" class="text-sm rounded-md border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700">
                            <option value="sections">By Sections</option>
                            <option value="hierarchy">Hierarchical</option>
                        </select>
                    @endif
                </div>

                {{-- Priority Distribution --}}
                @if (!empty($this->stats['by_priority']))
                    <div>
                        <h4 class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Priority Distribution</h4>
                        <div class="flex gap-2">
                            @foreach ($this->stats['by_priority'] as $priority => $count)
                                <div class="flex-1 bg-zinc-100 dark:bg-zinc-700 rounded p-2 text-center">
                                    <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $count }}</div>
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $priority }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Actions --}}
                <div class="flex items-center gap-3">
                    <button
                        wire:click="togglePreview"
                        class="px-4 py-2 text-sm font-medium rounded-md bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-600"
                    >
                        {{ $showPreview ? 'Hide Preview' : 'Show Preview' }}
                    </button>
                    @if ($sitemapFormat === 'xml')
                        <button
                            wire:click="downloadXml"
                            class="px-4 py-2 text-sm font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700"
                        >
                            Download XML
                        </button>
                    @else
                        <button
                            wire:click="downloadHtml"
                            class="px-4 py-2 text-sm font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700"
                        >
                            Download HTML
                        </button>
                    @endif
                </div>

                {{-- Preview --}}
                @if ($showPreview)
                    <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg overflow-hidden">
                        <div class="bg-zinc-50 dark:bg-zinc-700/50 px-3 py-2 border-b border-zinc-200 dark:border-zinc-700">
                            <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Preview</span>
                        </div>
                        <pre class="p-4 text-xs text-zinc-700 dark:text-zinc-300 overflow-x-auto max-h-96 bg-zinc-900 text-zinc-100"><code>{{ $sitemapFormat === 'xml' ? $this->generatedXml : $this->generatedHtml }}</code></pre>
                    </div>
                @endif
            </div>
        @endif

        {{-- Validate Tab --}}
        @if ($activeTab === 'validate')
            <div class="p-4 space-y-4">
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Sitemap URL</label>
                        <div class="flex gap-2">
                            <input
                                type="url"
                                wire:model="existingSitemapUrl"
                                placeholder="https://example.com/sitemap.xml"
                                class="flex-1 rounded-md border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 text-sm"
                            >
                            <button
                                wire:click="validateFromUrl"
                                wire:loading.attr="disabled"
                                class="px-4 py-2 text-sm font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50"
                            >
                                <span wire:loading.remove wire:target="validateFromUrl">Validate</span>
                                <span wire:loading wire:target="validateFromUrl">Validating...</span>
                            </button>
                        </div>
                    </div>

                    <div class="text-center text-sm text-zinc-500 dark:text-zinc-400">- or -</div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Paste Sitemap XML</label>
                        <textarea
                            wire:model="existingSitemapContent"
                            rows="5"
                            placeholder="Paste your sitemap XML content here..."
                            class="w-full rounded-md border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 text-sm font-mono"
                        ></textarea>
                        <button
                            wire:click="validateFromContent"
                            wire:loading.attr="disabled"
                            class="mt-2 px-4 py-2 text-sm font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50"
                        >
                            Validate Content
                        </button>
                    </div>
                </div>

                {{-- Validation Results --}}
                @if ($validationResult)
                    <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg overflow-hidden">
                        <div class="px-4 py-3 {{ $validationResult['valid'] ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20' }} border-b border-zinc-200 dark:border-zinc-700">
                            <div class="flex items-center gap-2">
                                @if ($validationResult['valid'])
                                    <svg class="h-5 w-5 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span class="font-medium text-green-800 dark:text-green-200">Validation Passed</span>
                                @else
                                    <svg class="h-5 w-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    <span class="font-medium text-red-800 dark:text-red-200">Validation Failed</span>
                                @endif
                            </div>
                            @if (isset($validationResult['error']))
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $validationResult['error'] }}</p>
                            @endif
                        </div>

                        @if (isset($validationResult['summary']))
                            <div class="p-4">
                                <div class="grid grid-cols-3 gap-4 mb-4">
                                    <div class="text-center">
                                        <div class="text-xl font-bold text-green-600 dark:text-green-400">{{ $validationResult['summary']['matching'] }}</div>
                                        <div class="text-xs text-zinc-500 dark:text-zinc-400">Matching URLs</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-xl font-bold text-amber-600 dark:text-amber-400">{{ $validationResult['summary']['extra_in_sitemap'] }}</div>
                                        <div class="text-xs text-zinc-500 dark:text-zinc-400">Stale URLs</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-xl font-bold text-blue-600 dark:text-blue-400">{{ $validationResult['summary']['missing_from_sitemap'] }}</div>
                                        <div class="text-xs text-zinc-500 dark:text-zinc-400">Missing URLs</div>
                                    </div>
                                </div>

                                @if (!empty($validationResult['issues']))
                                    <div class="border-t border-zinc-200 dark:border-zinc-700 pt-4">
                                        <h4 class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Issues ({{ count($validationResult['issues']) }})</h4>
                                        <div class="space-y-2 max-h-48 overflow-y-auto">
                                            @foreach (array_slice($validationResult['issues'], 0, 10) as $issue)
                                                <div class="flex items-start gap-2 text-sm">
                                                    @if ($issue['severity'] === 'warning')
                                                        <span class="px-1.5 py-0.5 text-xs rounded bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300">Warning</span>
                                                    @else
                                                        <span class="px-1.5 py-0.5 text-xs rounded bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">Info</span>
                                                    @endif
                                                    <div class="flex-1">
                                                        <div class="text-zinc-700 dark:text-zinc-300">{{ $issue['message'] }}</div>
                                                        <div class="text-xs text-zinc-500 dark:text-zinc-400 truncate">{{ $issue['url'] }}</div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>

                    <button
                        wire:click="clearSitemapValidation"
                        class="text-sm text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-300"
                    >
                        Clear Results
                    </button>
                @endif
            </div>
        @endif

        {{-- Visual Tab --}}
        @if ($activeTab === 'visual')
            <div class="p-4 space-y-4">
                {{-- Structure Stats --}}
                <div class="grid grid-cols-4 gap-4">
                    <div class="bg-zinc-50 dark:bg-zinc-700/50 rounded-lg p-3 text-center">
                        <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $this->structureStats['total_pages'] ?? 0 }}</div>
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">Total Pages</div>
                    </div>
                    <div class="bg-zinc-50 dark:bg-zinc-700/50 rounded-lg p-3 text-center">
                        <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $this->structureStats['total_sections'] ?? 0 }}</div>
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">Sections</div>
                    </div>
                    <div class="bg-zinc-50 dark:bg-zinc-700/50 rounded-lg p-3 text-center">
                        <div class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $this->structureStats['orphan_pages'] ?? 0 }}</div>
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">Orphan Pages</div>
                    </div>
                    <div class="bg-zinc-50 dark:bg-zinc-700/50 rounded-lg p-3 text-center">
                        <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $this->structureStats['deep_pages'] ?? 0 }}</div>
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">Deep Pages</div>
                    </div>
                </div>

                {{-- Pages by Depth --}}
                @if (!empty($this->structureStats['pages_by_depth']))
                    <div>
                        <h4 class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Pages by Depth</h4>
                        <div class="flex gap-1">
                            @foreach ($this->structureStats['pages_by_depth'] as $depth => $count)
                                <div class="flex-1">
                                    <div class="bg-blue-100 dark:bg-blue-900/30 rounded-t text-center py-1">
                                        <span class="text-sm font-medium text-blue-700 dark:text-blue-300">{{ $count }}</span>
                                    </div>
                                    <div class="bg-zinc-100 dark:bg-zinc-700 rounded-b text-center py-0.5">
                                        <span class="text-xs text-zinc-500 dark:text-zinc-400">L{{ $depth }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Sections Preview --}}
                @if (!empty($this->sections))
                    <div>
                        <h4 class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Site Sections</h4>
                        <div class="grid grid-cols-2 gap-3 max-h-64 overflow-y-auto">
                            @foreach ($this->sections as $section)
                                <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg p-3">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $section['name'] }}</span>
                                        <span class="text-xs px-2 py-0.5 rounded-full bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-400">
                                            {{ $section['count'] }} pages
                                        </span>
                                    </div>
                                    <div class="space-y-1">
                                        @foreach (array_slice($section['pages'], 0, 3) as $page)
                                            <div class="text-xs text-zinc-500 dark:text-zinc-400 truncate">{{ $page['path'] }}</div>
                                        @endforeach
                                        @if (count($section['pages']) > 3)
                                            <div class="text-xs text-blue-600 dark:text-blue-400">+{{ count($section['pages']) - 3 }} more</div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif
    @endif
</div>
