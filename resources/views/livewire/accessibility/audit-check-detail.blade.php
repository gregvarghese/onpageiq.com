<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-start justify-between">
        <div>
            <div class="flex items-center gap-x-3">
                <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium {{ $check->status->color() }}">
                    @if($check->status === \App\Enums\CheckStatus::Pass)
                        <x-ui.icon name="check-circle" class="size-4 mr-1.5" />
                    @elseif($check->status === \App\Enums\CheckStatus::Fail)
                        <x-ui.icon name="x-circle" class="size-4 mr-1.5" />
                    @elseif($check->status === \App\Enums\CheckStatus::Warning)
                        <x-ui.icon name="exclamation-triangle" class="size-4 mr-1.5" />
                    @endif
                    {{ $check->status->label() }}
                </span>
                <x-accessibility.wcag-badge :level="$check->wcag_level" />
                @if($check->impact)
                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium {{ $check->impact->color() }}">
                        {{ $check->impact->label() }} Impact
                    </span>
                @endif
            </div>
            <h2 class="mt-3 text-xl font-semibold text-gray-900 dark:text-white">
                WCAG {{ $check->criterion_id }}: {{ $check->category?->label() ?? 'General' }}
            </h2>
        </div>
        @if($this->wcagDocUrl)
            <a
                href="{{ $this->wcagDocUrl }}"
                target="_blank"
                class="inline-flex items-center gap-x-1.5 text-sm text-primary-600 hover:text-primary-500 dark:text-primary-400"
            >
                <x-ui.icon name="book-open" class="size-4" />
                View Documentation
                <x-ui.icon name="arrow-top-right-on-square" class="size-3" />
            </a>
        @endif
    </div>

    {{-- Issue Message --}}
    <div class="rounded-lg bg-gray-50 dark:bg-gray-900/50 p-4">
        <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Issue</h3>
        <p class="text-sm text-gray-700 dark:text-gray-300">{{ $check->message }}</p>
    </div>

    {{-- Suggestion --}}
    @if($check->suggestion)
        <div class="rounded-lg bg-blue-50 dark:bg-blue-900/20 p-4">
            <h3 class="text-sm font-medium text-blue-900 dark:text-blue-300 mb-2">
                <x-ui.icon name="light-bulb" class="inline-block size-4 mr-1" />
                Suggested Fix
            </h3>
            <p class="text-sm text-blue-800 dark:text-blue-200">{{ $check->suggestion }}</p>
        </div>
    @endif

    {{-- Element Details --}}
    @if($check->element_selector || $check->element_html)
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="bg-gray-50 dark:bg-gray-900/50 px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-medium text-gray-900 dark:text-white">Affected Element</h3>
            </div>
            <div class="p-4 space-y-3">
                @if($check->element_selector)
                    <div>
                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Selector</span>
                        <code class="mt-1 block text-sm bg-gray-100 dark:bg-gray-800 p-2 rounded overflow-x-auto">
                            {{ $check->element_selector }}
                        </code>
                    </div>
                @endif
                @if($check->element_html)
                    <div>
                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">HTML</span>
                        <pre class="mt-1 text-sm bg-gray-100 dark:bg-gray-800 p-2 rounded overflow-x-auto"><code>{{ $check->element_html }}</code></pre>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Code Snippet --}}
    @if($check->code_snippet)
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="bg-gray-50 dark:bg-gray-900/50 px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="text-sm font-medium text-gray-900 dark:text-white">Example Fix</h3>
                <button
                    type="button"
                    x-data="{ copied: false }"
                    x-on:click="navigator.clipboard.writeText($refs.codeSnippet.textContent); copied = true; setTimeout(() => copied = false, 2000)"
                    class="inline-flex items-center gap-x-1 text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
                >
                    <x-ui.icon name="clipboard-document" class="size-4" x-show="!copied" />
                    <x-ui.icon name="check" class="size-4 text-green-500" x-show="copied" x-cloak />
                    <span x-text="copied ? 'Copied!' : 'Copy'"></span>
                </button>
            </div>
            <pre class="p-4 text-sm overflow-x-auto bg-gray-900 text-gray-100" x-ref="codeSnippet"><code>{{ $check->code_snippet }}</code></pre>
        </div>
    @endif

    {{-- Evidence --}}
    <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="bg-gray-50 dark:bg-gray-900/50 px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h3 class="text-sm font-medium text-gray-900 dark:text-white">Evidence & Notes</h3>
            <button
                type="button"
                wire:click="$set('showAddNoteModal', true)"
                class="inline-flex items-center gap-x-1 text-sm text-primary-600 hover:text-primary-500 dark:text-primary-400"
            >
                <x-ui.icon name="plus" class="size-4" />
                Add Note
            </button>
        </div>
        <div class="divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($this->evidence as $item)
                <div class="p-4 flex items-start justify-between">
                    <div class="flex items-start gap-x-3">
                        <div class="flex-shrink-0">
                            @if($item->type->value === 'screenshot')
                                <x-ui.icon name="photo" class="size-5 text-gray-400" />
                            @elseif($item->type->value === 'recording')
                                <x-ui.icon name="video-camera" class="size-5 text-gray-400" />
                            @elseif($item->type->value === 'note')
                                <x-ui.icon name="document-text" class="size-5 text-gray-400" />
                            @else
                                <x-ui.icon name="link" class="size-5 text-gray-400" />
                            @endif
                        </div>
                        <div>
                            <p class="text-sm text-gray-900 dark:text-white">{{ $item->notes }}</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                {{ $item->capturedBy?->name ?? 'Unknown' }} &middot; {{ $item->captured_at->diffForHumans() }}
                            </p>
                        </div>
                    </div>
                    @if($item->captured_by_user_id === auth()->id())
                        <button
                            type="button"
                            wire:click="deleteEvidence('{{ $item->id }}')"
                            wire:confirm="Are you sure you want to delete this note?"
                            class="text-gray-400 hover:text-red-500"
                        >
                            <x-ui.icon name="trash" class="size-4" />
                        </button>
                    @endif
                </div>
            @empty
                <div class="p-8 text-center">
                    <x-ui.icon name="document-text" class="mx-auto size-8 text-gray-400" />
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No evidence or notes yet.</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Related Checks --}}
    @if($this->relatedChecks->isNotEmpty())
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="bg-gray-50 dark:bg-gray-900/50 px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-medium text-gray-900 dark:text-white">Related Checks (Same Criterion)</h3>
            </div>
            <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($this->relatedChecks as $related)
                    <li class="px-4 py-3 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <div class="flex items-center gap-x-3">
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $related->status->color() }}">
                                {{ $related->status->label() }}
                            </span>
                            <span class="text-sm text-gray-900 dark:text-white truncate max-w-md">
                                {{ Str::limit($related->element_selector ?? $related->message, 50) }}
                            </span>
                        </div>
                        <button
                            type="button"
                            x-data
                            x-on:click="$dispatch('open-check-detail', { checkId: '{{ $related->id }}' })"
                            class="text-sm text-primary-600 hover:text-primary-500 dark:text-primary-400"
                        >
                            View
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Add Note Modal --}}
    @if($showAddNoteModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="add-note-modal" role="dialog" aria-modal="true">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="fixed inset-0 bg-gray-500/75 dark:bg-gray-900/75 transition-opacity" wire:click="$set('showAddNoteModal', false)"></div>
                <div class="relative transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Add Note</h3>
                        <div class="mt-4">
                            <textarea
                                wire:model="noteContent"
                                rows="4"
                                class="block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-white bg-white dark:bg-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm"
                                placeholder="Add your notes about this check..."
                            ></textarea>
                            @error('noteContent')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-6 sm:grid sm:grid-flow-row-dense sm:grid-cols-2 sm:gap-3">
                        <button
                            type="button"
                            wire:click="addNote"
                            class="inline-flex w-full justify-center rounded-md bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 sm:col-start-2"
                        >
                            Save Note
                        </button>
                        <button
                            type="button"
                            wire:click="$set('showAddNoteModal', false)"
                            class="mt-3 inline-flex w-full justify-center rounded-md bg-white dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-white shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 sm:col-start-1 sm:mt-0"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
