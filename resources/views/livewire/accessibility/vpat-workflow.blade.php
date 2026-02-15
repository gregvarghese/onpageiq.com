<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">VPAT 2.4 Evaluation</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Voluntary Product Accessibility Template for {{ $audit->project?->name ?? 'Audit' }}
            </p>
        </div>

        @if($vpat)
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium {{ $vpat->status->badgeClasses() }}">
                    {{ $vpat->status->label() }}
                </span>

                @if($vpat->status === \App\Enums\VpatStatus::Draft)
                    <button wire:click="submitForReview" class="inline-flex items-center rounded-md bg-yellow-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-yellow-500">
                        Submit for Review
                    </button>
                @elseif($vpat->status === \App\Enums\VpatStatus::InReview)
                    <button wire:click="approve" class="inline-flex items-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500">
                        Approve
                    </button>
                @elseif($vpat->status === \App\Enums\VpatStatus::Approved)
                    <button wire:click="publish" class="inline-flex items-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">
                        Publish
                    </button>
                @endif

                <button wire:click="$set('showExportModal', true)" class="inline-flex items-center rounded-md bg-gray-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-500">
                    Export
                </button>
            </div>
        @else
            <button wire:click="$set('showCreateModal', true)" class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                Create VPAT
            </button>
        @endif
    </div>

    @if($vpat)
        {{-- Progress Overview --}}
        <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Evaluation Progress</h3>
                @if($vpat->isEditable())
                    <button wire:click="populateFromAudit" wire:loading.attr="disabled" class="text-sm text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                        <span wire:loading.remove wire:target="populateFromAudit">Auto-populate from audit</span>
                        <span wire:loading wire:target="populateFromAudit">Populating...</span>
                    </button>
                @endif
            </div>

            {{-- Progress Bar --}}
            <div class="mb-6">
                <div class="flex items-center justify-between text-sm mb-1">
                    <span class="text-gray-600 dark:text-gray-400">Completion</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ number_format($this->completionPercentage, 0) }}%</span>
                </div>
                <div class="h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                    <div class="h-2 rounded-full bg-indigo-600" style="width: {{ $this->completionPercentage }}%"></div>
                </div>
            </div>

            {{-- Conformance Summary --}}
            <div class="grid grid-cols-5 gap-4">
                @foreach(\App\Enums\VpatConformanceLevel::cases() as $level)
                    <div class="text-center">
                        <div class="text-2xl font-bold {{ $level->color() === 'green' ? 'text-green-600' : ($level->color() === 'yellow' ? 'text-yellow-600' : ($level->color() === 'red' ? 'text-red-600' : 'text-gray-500')) }}">
                            {{ $this->conformanceSummary[$level->value] ?? 0 }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $level->label() }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Product Information --}}
        <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Product Information</h3>
            <dl class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Product Name</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $vpat->product_name }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Version</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $vpat->product_version ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Vendor</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $vpat->vendor_name }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Evaluation Date</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $vpat->evaluation_date?->format('F j, Y') }}</dd>
                </div>
            </dl>
        </div>

        {{-- Principle Tabs --}}
        <div class="rounded-lg bg-white shadow dark:bg-gray-800">
            <div class="border-b border-gray-200 dark:border-gray-700">
                <nav class="flex -mb-px" aria-label="Principles">
                    @foreach($this->principles as $principle)
                        <button
                            wire:click="$set('activePrinciple', '{{ $principle }}')"
                            class="px-6 py-4 text-sm font-medium border-b-2 {{ $activePrinciple === $principle ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}"
                        >
                            {{ $principle }}
                        </button>
                    @endforeach
                </nav>
            </div>

            {{-- Criteria List --}}
            <div class="p-6">
                <div class="space-y-3">
                    @foreach($this->activeCriteria as $criterion)
                        <div class="flex items-center justify-between rounded-lg border border-gray-200 p-4 dark:border-gray-700 {{ $currentCriterion === $criterion['id'] ? 'ring-2 ring-indigo-500' : '' }}">
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-sm text-gray-500 dark:text-gray-400">{{ $criterion['id'] }}</span>
                                    <x-accessibility.wcag-badge :level="$criterion['wcagLevel']" />
                                </div>
                                <h4 class="mt-1 font-medium text-gray-900 dark:text-white">{{ $criterion['name'] }}</h4>
                                @if($criterion['remarks'])
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ Str::limit($criterion['remarks'], 100) }}</p>
                                @endif
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $criterion['conformanceLevel']->badgeClasses() }}">
                                    {{ $criterion['conformanceLevel']->label() }}
                                </span>
                                @if($vpat->isEditable())
                                    <button wire:click="editCriterion('{{ $criterion['id'] }}')" class="text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                        </svg>
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Edit Criterion Modal --}}
        @if($currentCriterion)
            <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="cancelEdit"></div>

                    <div class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6 dark:bg-gray-800">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                            Evaluate {{ $currentCriterion }}
                        </h3>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Conformance Level</label>
                                <select wire:model="conformanceLevel" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    @foreach($conformanceLevels as $level)
                                        <option value="{{ $level->value }}">{{ $level->label() }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Remarks</label>
                                <textarea wire:model="remarks" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="Describe the evaluation findings..."></textarea>
                            </div>
                        </div>

                        <div class="mt-5 sm:mt-6 flex gap-3 justify-end">
                            <button wire:click="cancelEdit" class="inline-flex justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-gray-700 dark:text-white dark:ring-gray-600">
                                Cancel
                            </button>
                            <button wire:click="saveEvaluation" class="inline-flex justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                                Save Evaluation
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @else
        {{-- No VPAT State --}}
        <div class="rounded-lg bg-white p-12 text-center shadow dark:bg-gray-800">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
            </svg>
            <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No VPAT Evaluation</h3>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                Create a VPAT 2.4 evaluation to document product accessibility conformance.
            </p>
            <button wire:click="$set('showCreateModal', true)" class="mt-4 inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                Create VPAT Evaluation
            </button>
        </div>
    @endif

    {{-- Create VPAT Modal --}}
    @if($showCreateModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="$set('showCreateModal', false)"></div>

                <div class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6 dark:bg-gray-800">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Create VPAT 2.4 Evaluation</h3>

                    <form wire:submit="createVpat" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Product Name *</label>
                            <input type="text" wire:model="productName" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                            @error('productName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Product Version</label>
                            <input type="text" wire:model="productVersion" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Product Description</label>
                            <textarea wire:model="productDescription" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Vendor Name *</label>
                            <input type="text" wire:model="vendorName" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                            @error('vendorName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Vendor Contact</label>
                            <input type="text" wire:model="vendorContact" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Report Types *</label>
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input type="checkbox" wire:model="reportTypes" value="wcag21" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">WCAG 2.1</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" wire:model="reportTypes" value="section508" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Section 508</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" wire:model="reportTypes" value="en301549" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">EN 301 549</span>
                                </label>
                            </div>
                            @error('reportTypes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="mt-5 sm:mt-6 flex gap-3 justify-end">
                            <button type="button" wire:click="$set('showCreateModal', false)" class="inline-flex justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-gray-700 dark:text-white dark:ring-gray-600">
                                Cancel
                            </button>
                            <button type="submit" class="inline-flex justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                                Create VPAT
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Export Modal --}}
    @if($showExportModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="$set('showExportModal', false)"></div>

                <div class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-sm sm:p-6 dark:bg-gray-800">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Export VPAT</h3>

                    @error('export') <p class="mb-4 text-sm text-red-600">{{ $message }}</p> @enderror

                    <div class="space-y-3">
                        <button wire:click="exportPdf" wire:loading.attr="disabled" class="w-full flex items-center justify-center gap-2 rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500">
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                            </svg>
                            <span wire:loading.remove wire:target="exportPdf">Download PDF</span>
                            <span wire:loading wire:target="exportPdf">Generating...</span>
                        </button>
                    </div>

                    <div class="mt-5">
                        <button wire:click="$set('showExportModal', false)" class="w-full inline-flex justify-center rounded-md bg-gray-100 px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm hover:bg-gray-200 dark:bg-gray-700 dark:text-white">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
