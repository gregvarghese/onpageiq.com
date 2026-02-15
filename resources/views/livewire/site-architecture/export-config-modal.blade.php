<div>
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div
                    class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity dark:bg-gray-900 dark:bg-opacity-75"
                    wire:click="closeModal"
                ></div>

                <!-- Modal panel -->
                <div class="inline-block transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all dark:bg-gray-800 sm:my-8 sm:w-full sm:max-w-2xl sm:align-middle">
                    <!-- Header -->
                    <div class="border-b border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-900">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white" id="modal-title">
                                Export Architecture
                            </h3>
                            <button
                                type="button"
                                wire:click="closeModal"
                                class="rounded-md text-gray-400 hover:text-gray-500 focus:outline-none dark:hover:text-gray-300"
                            >
                                <x-heroicon-o-x-mark class="h-6 w-6" />
                            </button>
                        </div>
                    </div>

                    <div class="px-6 py-4">
                        @if($exportError)
                            <div class="mb-4 rounded-md bg-red-50 p-4 dark:bg-red-900/20">
                                <div class="flex">
                                    <x-heroicon-s-x-circle class="h-5 w-5 text-red-400" />
                                    <p class="ml-3 text-sm text-red-700 dark:text-red-400">{{ $exportError }}</p>
                                </div>
                            </div>
                        @endif

                        @if($downloadUrl)
                            <div class="mb-4 rounded-md bg-green-50 p-4 dark:bg-green-900/20">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <x-heroicon-s-check-circle class="h-5 w-5 text-green-400" />
                                        <p class="ml-3 text-sm text-green-700 dark:text-green-400">Export ready!</p>
                                    </div>
                                    <a
                                        href="{{ $downloadUrl }}"
                                        download
                                        class="inline-flex items-center rounded-md bg-green-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-green-700"
                                    >
                                        <x-heroicon-o-arrow-down-tray class="mr-1.5 h-4 w-4" />
                                        Download
                                    </a>
                                </div>
                            </div>
                        @endif

                        <!-- Format Selection -->
                        <div class="mb-6">
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Export Format
                            </label>
                            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                @foreach($this->formatOptions as $format => $info)
                                    <button
                                        type="button"
                                        wire:click="selectFormat('{{ $format }}')"
                                        @class([
                                            'relative rounded-lg border p-3 text-center transition-all focus:outline-none',
                                            'border-blue-500 bg-blue-50 ring-2 ring-blue-500 dark:bg-blue-900/20' => $exportFormat === $format,
                                            'border-gray-200 hover:border-gray-300 dark:border-gray-600 dark:hover:border-gray-500' => $exportFormat !== $format,
                                        ])
                                    >
                                        <x-dynamic-component
                                            :component="'heroicon-o-' . $info['icon']"
                                            @class([
                                                'mx-auto h-6 w-6',
                                                'text-blue-600 dark:text-blue-400' => $exportFormat === $format,
                                                'text-gray-400' => $exportFormat !== $format,
                                            ])
                                        />
                                        <span @class([
                                            'mt-1 block text-sm font-medium',
                                            'text-blue-900 dark:text-blue-100' => $exportFormat === $format,
                                            'text-gray-700 dark:text-gray-300' => $exportFormat !== $format,
                                        ])>
                                            {{ $info['label'] }}
                                        </span>
                                        <span class="mt-0.5 block text-xs text-gray-500 dark:text-gray-400">
                                            {{ $info['description'] }}
                                        </span>
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <!-- Format-specific options -->
                        <div class="space-y-4">
                            @if($exportFormat === 'svg')
                                <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                                    <h4 class="mb-3 font-medium text-gray-900 dark:text-white">SVG Options</h4>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm text-gray-700 dark:text-gray-300">Width</label>
                                            <input
                                                type="number"
                                                wire:model="svgWidth"
                                                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-700"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-sm text-gray-700 dark:text-gray-300">Height</label>
                                            <input
                                                type="number"
                                                wire:model="svgHeight"
                                                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-700"
                                            />
                                        </div>
                                    </div>
                                    <div class="mt-3 space-y-2">
                                        <label class="flex items-center">
                                            <input type="checkbox" wire:model="svgIncludeLegend" class="rounded border-gray-300 text-blue-600 dark:border-gray-600" />
                                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Include legend</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" wire:model="svgIncludeMetadata" class="rounded border-gray-300 text-blue-600 dark:border-gray-600" />
                                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Include metadata</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" wire:model="svgShowLabels" class="rounded border-gray-300 text-blue-600 dark:border-gray-600" />
                                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Show labels</span>
                                        </label>
                                    </div>
                                    <div class="mt-3">
                                        <label class="block text-sm text-gray-700 dark:text-gray-300">Color Scheme</label>
                                        <select wire:model="svgColorScheme" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-700">
                                            <option value="status">By Status</option>
                                            <option value="depth">By Depth</option>
                                            <option value="equity">By Link Equity</option>
                                        </select>
                                    </div>
                                </div>
                            @endif

                            @if($exportFormat === 'mermaid')
                                <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                                    <h4 class="mb-3 font-medium text-gray-900 dark:text-white">Mermaid Options</h4>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm text-gray-700 dark:text-gray-300">Diagram Type</label>
                                            <select wire:model="mermaidDiagramType" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-700">
                                                <option value="flowchart">Flowchart</option>
                                                <option value="mindmap">Mindmap</option>
                                                <option value="graph">Graph</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm text-gray-700 dark:text-gray-300">Direction</label>
                                            <select wire:model="mermaidDirection" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-700">
                                                <option value="TB">Top to Bottom</option>
                                                <option value="BT">Bottom to Top</option>
                                                <option value="LR">Left to Right</option>
                                                <option value="RL">Right to Left</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <label class="block text-sm text-gray-700 dark:text-gray-300">Max Label Length</label>
                                        <input
                                            type="number"
                                            wire:model="mermaidMaxLabelLength"
                                            min="10"
                                            max="100"
                                            class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-700"
                                        />
                                    </div>
                                    <div class="mt-3">
                                        <label class="flex items-center">
                                            <input type="checkbox" wire:model="mermaidGroupByDepth" class="rounded border-gray-300 text-blue-600 dark:border-gray-600" />
                                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Group by depth (subgraphs)</span>
                                        </label>
                                    </div>
                                </div>
                            @endif

                            @if($exportFormat === 'figma')
                                <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                                    <h4 class="mb-3 font-medium text-gray-900 dark:text-white">Figma Options</h4>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm text-gray-700 dark:text-gray-300">Canvas Width</label>
                                            <input
                                                type="number"
                                                wire:model="figmaCanvasWidth"
                                                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-700"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-sm text-gray-700 dark:text-gray-300">Canvas Height</label>
                                            <input
                                                type="number"
                                                wire:model="figmaCanvasHeight"
                                                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-700"
                                            />
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <label class="flex items-center">
                                            <input type="checkbox" wire:model="figmaIncludeConnections" class="rounded border-gray-300 text-blue-600 dark:border-gray-600" />
                                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Include connection lines</span>
                                        </label>
                                    </div>
                                </div>
                            @endif

                            @if($exportFormat === 'pdf')
                                <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                                    <h4 class="mb-3 font-medium text-gray-900 dark:text-white">PDF Options</h4>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm text-gray-700 dark:text-gray-300">Page Size</label>
                                            <select wire:model="pdfPageSize" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-700">
                                                <option value="A4">A4</option>
                                                <option value="letter">Letter</option>
                                                <option value="legal">Legal</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm text-gray-700 dark:text-gray-300">Orientation</label>
                                            <select wire:model="pdfOrientation" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-700">
                                                <option value="portrait">Portrait</option>
                                                <option value="landscape">Landscape</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <label class="block text-sm text-gray-700 dark:text-gray-300">Brand Color</label>
                                        <input
                                            type="color"
                                            wire:model="pdfBrandColor"
                                            class="mt-1 h-10 w-20 cursor-pointer rounded border-gray-300 dark:border-gray-600"
                                        />
                                    </div>
                                    <div class="mt-3 space-y-2">
                                        <label class="flex items-center">
                                            <input type="checkbox" wire:model="pdfIncludeCover" class="rounded border-gray-300 text-blue-600 dark:border-gray-600" />
                                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Include cover page</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" wire:model="pdfIncludeToc" class="rounded border-gray-300 text-blue-600 dark:border-gray-600" />
                                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Include table of contents</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" wire:model="pdfIncludeStatistics" class="rounded border-gray-300 text-blue-600 dark:border-gray-600" />
                                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Include statistics</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" wire:model="pdfIncludeNodeList" class="rounded border-gray-300 text-blue-600 dark:border-gray-600" />
                                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Include page inventory</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" wire:model="pdfIncludeRecommendations" class="rounded border-gray-300 text-blue-600 dark:border-gray-600" />
                                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Include recommendations</span>
                                        </label>
                                    </div>
                                </div>
                            @endif

                            <!-- Common Options -->
                            <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                                <h4 class="mb-3 font-medium text-gray-900 dark:text-white">Content Options</h4>
                                <div class="space-y-2">
                                    <label class="flex items-center">
                                        <input type="checkbox" wire:model="includeErrors" class="rounded border-gray-300 text-blue-600 dark:border-gray-600" />
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Include error pages (4xx/5xx)</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" wire:model="includeExternal" class="rounded border-gray-300 text-blue-600 dark:border-gray-600" />
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Include external links</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-900">
                        <button
                            type="button"
                            wire:click="closeModal"
                            class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            wire:click="startExport"
                            wire:loading.attr="disabled"
                            class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                        >
                            <span wire:loading.remove wire:target="startExport">
                                <x-heroicon-o-arrow-down-tray class="mr-1.5 h-4 w-4" />
                                Export
                            </span>
                            <span wire:loading wire:target="startExport" class="inline-flex items-center">
                                <svg class="mr-2 h-4 w-4 animate-spin" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Exporting...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
