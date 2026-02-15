<div>
    @if($isOpen && $this->link)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            {{-- Backdrop --}}
            <div
                class="fixed inset-0 bg-gray-500/75 dark:bg-gray-900/75 transition-opacity"
                wire:click="close"
            ></div>

            {{-- Modal --}}
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-md">
                    {{-- Header --}}
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white" id="modal-title">
                            Override Link Classification
                        </h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Manually set the type for this link.
                        </p>
                    </div>

                    {{-- Content --}}
                    <div class="px-6 py-4 space-y-4">
                        {{-- Link Info --}}
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 space-y-2">
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">From</p>
                                <p class="text-sm text-gray-900 dark:text-white truncate">
                                    {{ $this->link->sourceNode?->path ?? 'Unknown' }}
                                </p>
                            </div>
                            <div class="flex items-center justify-center">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">To</p>
                                <p class="text-sm text-gray-900 dark:text-white truncate">
                                    {{ $this->link->targetNode?->path ?? $this->link->external_domain ?? 'External' }}
                                </p>
                            </div>
                            @if($this->link->anchor_text)
                                <div class="pt-2 border-t border-gray-200 dark:border-gray-600">
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Anchor Text</p>
                                    <p class="text-sm text-gray-900 dark:text-white">
                                        "{{ $this->link->anchor_text }}"
                                    </p>
                                </div>
                            @endif
                        </div>

                        {{-- Current Classification --}}
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-500 dark:text-gray-400">Auto-detected:</span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                                {{ $this->link->link_type->label() }}
                            </span>
                            @if($this->link->link_type_override)
                                <span class="text-xs text-orange-600 dark:text-orange-400">(overridden)</span>
                            @endif
                        </div>

                        {{-- Link Type Selection --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Select Link Type
                            </label>
                            <div class="grid grid-cols-2 gap-2">
                                @foreach($linkTypes as $value => $label)
                                    <label class="relative flex cursor-pointer rounded-lg border p-3 focus:outline-none {{ $selectedLinkType === $value ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 dark:border-gray-600' }}">
                                        <input
                                            type="radio"
                                            name="linkType"
                                            value="{{ $value }}"
                                            wire:model="selectedLinkType"
                                            class="sr-only"
                                        >
                                        <span class="flex items-center gap-2">
                                            <span @class([
                                                'w-3 h-3 rounded-full',
                                                'bg-blue-500' => $value === 'navigation',
                                                'bg-green-500' => $value === 'content',
                                                'bg-gray-500' => $value === 'footer',
                                                'bg-purple-500' => $value === 'sidebar',
                                                'bg-indigo-500' => $value === 'header',
                                                'bg-teal-500' => $value === 'breadcrumb',
                                                'bg-cyan-500' => $value === 'pagination',
                                                'bg-amber-500' => $value === 'external',
                                            ])></span>
                                            <span class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $label }}
                                            </span>
                                        </span>
                                        @if($selectedLinkType === $value)
                                            <svg class="absolute top-3 right-3 h-4 w-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                            </svg>
                                        @endif
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-between">
                        <div>
                            @if($this->link->link_type_override)
                                <button
                                    type="button"
                                    wire:click="clearOverride"
                                    class="text-sm text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300"
                                >
                                    Clear Override
                                </button>
                            @endif
                        </div>
                        <div class="flex gap-3">
                            <button
                                type="button"
                                wire:click="close"
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
                            >
                                Cancel
                            </button>
                            <button
                                type="button"
                                wire:click="save"
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors"
                            >
                                Save Classification
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
