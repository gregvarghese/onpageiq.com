<div>
    <!-- Modal -->
    @if($showModal)
        <div
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title"
            role="dialog"
            aria-modal="true"
        >
            <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div
                    class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75 transition-opacity"
                    wire:click="closeModal"
                ></div>

                <!-- Modal panel -->
                <div class="relative inline-block transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 px-4 pt-5 pb-4 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6 sm:align-middle">
                    @if($submitted)
                        <!-- Success State -->
                        <div class="text-center py-6">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30">
                                <x-ui.icon name="check" class="h-6 w-6 text-green-600 dark:text-green-400" />
                            </div>
                            <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">Report Submitted</h3>
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                Thank you for your feedback. We'll review this report and improve our detection.
                            </p>
                            @if($addToDictionary && $issue?->text_excerpt)
                                <p class="mt-2 text-sm text-green-600 dark:text-green-400">
                                    "{{ $issue->text_excerpt }}" has been added to your organization's dictionary.
                                </p>
                            @endif
                            <div class="mt-6">
                                <button
                                    type="button"
                                    wire:click="closeModal"
                                    class="inline-flex justify-center rounded-md bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"
                                >
                                    Done
                                </button>
                            </div>
                        </div>
                    @else
                        <!-- Report Form -->
                        <div>
                            <!-- Header -->
                            <div class="flex items-start justify-between">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white" id="modal-title">
                                        Report False Positive
                                    </h3>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        Help us improve by reporting incorrectly flagged content.
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    wire:click="closeModal"
                                    class="rounded-md text-gray-400 hover:text-gray-500 dark:hover:text-gray-300"
                                >
                                    <x-ui.icon name="x-mark" class="h-6 w-6" />
                                </button>
                            </div>

                            <!-- Issue Preview -->
                            @if($issue)
                                <div class="mt-4 rounded-lg bg-gray-50 dark:bg-gray-700/50 p-4">
                                    <div class="flex items-start gap-x-3">
                                        @php
                                            $categoryColors = [
                                                'spelling' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                                'grammar' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                                                'seo' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                            ];
                                            $colorClass = $categoryColors[$issue->category] ?? 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
                                        @endphp
                                        <span class="inline-flex items-center rounded px-2 py-0.5 text-xs font-medium capitalize {{ $colorClass }}">
                                            {{ $issue->category }}
                                        </span>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm text-gray-900 dark:text-white">
                                                {{ $issue->text_excerpt ?? $issue->message }}
                                            </p>
                                            @if($issue->suggestion)
                                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                    Suggestion: <span class="text-primary-600 dark:text-primary-400">{{ $issue->suggestion }}</span>
                                                </p>
                                            @endif
                                            @if($issue->context)
                                                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500 font-mono truncate">
                                                    "{{ $issue->context }}"
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- Category Selection -->
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Why is this a false positive?
                                </label>
                                <div class="mt-2 space-y-2">
                                    @foreach($categoryOptions as $value => $option)
                                        <label class="relative flex cursor-pointer rounded-lg border p-4 {{ $category === $value ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50' }}">
                                            <input
                                                type="radio"
                                                wire:model="category"
                                                value="{{ $value }}"
                                                class="sr-only"
                                            />
                                            <div class="flex flex-1">
                                                <div class="flex flex-col">
                                                    <span class="block text-sm font-medium text-gray-900 dark:text-white">
                                                        {{ $option['label'] }}
                                                    </span>
                                                    <span class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                                        {{ $option['description'] }}
                                                    </span>
                                                </div>
                                            </div>
                                            @if($category === $value)
                                                <x-ui.icon name="check-circle" class="h-5 w-5 text-primary-600 dark:text-primary-400" />
                                            @endif
                                        </label>
                                    @endforeach
                                </div>
                                @error('category')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Context Text Field -->
                            <div class="mt-4">
                                <label for="context" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Additional context (optional)
                                </label>
                                <textarea
                                    id="context"
                                    wire:model="context"
                                    rows="3"
                                    placeholder="Provide any additional information that might help us understand why this was flagged incorrectly..."
                                    class="mt-1 block w-full rounded-md border-0 py-2 px-3 text-gray-900 dark:text-white bg-white dark:bg-gray-700 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm"
                                ></textarea>
                                @error('context')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Add to Dictionary Option -->
                            @if($issue?->text_excerpt && in_array($issue->category, ['spelling']))
                                <div class="mt-4">
                                    <label class="flex items-center gap-x-3 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            wire:model="addToDictionary"
                                            class="h-4 w-4 rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-600"
                                        />
                                        <div>
                                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                Add "{{ $issue->text_excerpt }}" to dictionary
                                            </span>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                This word won't be flagged again in future scans
                                            </p>
                                        </div>
                                    </label>
                                </div>
                            @endif

                            <!-- Actions -->
                            <div class="mt-6 flex justify-end gap-x-3">
                                <button
                                    type="button"
                                    wire:click="closeModal"
                                    class="rounded-md bg-white dark:bg-gray-700 px-4 py-2 text-sm font-semibold text-gray-900 dark:text-white shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="button"
                                    wire:click="submit"
                                    wire:loading.attr="disabled"
                                    wire:target="submit"
                                    class="inline-flex items-center gap-x-2 rounded-md bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 disabled:opacity-50"
                                >
                                    <span wire:loading.remove wire:target="submit">Submit Report</span>
                                    <span wire:loading wire:target="submit">Submitting...</span>
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
