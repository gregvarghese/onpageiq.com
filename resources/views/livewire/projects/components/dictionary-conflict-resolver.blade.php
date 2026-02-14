<div>
    <!-- Trigger Button -->
    @if($this->summary['total'] > 0)
        <button
            type="button"
            wire:click="openModal"
            class="inline-flex items-center gap-x-2 rounded-md bg-yellow-50 dark:bg-yellow-900/20 px-3 py-2 text-sm font-medium text-yellow-700 dark:text-yellow-300 ring-1 ring-inset ring-yellow-600/20 dark:ring-yellow-500/30 hover:bg-yellow-100 dark:hover:bg-yellow-900/30"
        >
            <x-ui.icon name="exclamation-triangle" class="size-4" />
            {{ $this->summary['total'] }} Conflict{{ $this->summary['total'] !== 1 ? 's' : '' }}
        </button>
    @endif

    <!-- Modal -->
    @if($showModal)
        <div
            x-data="{ show: @entangle('showModal') }"
            x-show="show"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title"
            role="dialog"
            aria-modal="true"
        >
            <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                <!-- Backdrop -->
                <div
                    class="fixed inset-0 bg-gray-500/75 dark:bg-gray-900/75 transition-opacity"
                    @click="$wire.closeModal()"
                ></div>

                <!-- Modal Panel -->
                <div
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="relative inline-block w-full max-w-3xl transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 text-left align-bottom shadow-xl transition-all sm:my-8 sm:align-middle"
                >
                    <!-- Header -->
                    <div class="border-b border-gray-200 dark:border-gray-700 px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white" id="modal-title">
                                    Dictionary Conflict Resolution
                                </h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    Resolve conflicts between project and organization dictionaries
                                </p>
                            </div>
                            <button
                                type="button"
                                wire:click="closeModal"
                                class="rounded-md text-gray-400 hover:text-gray-500 dark:hover:text-gray-300"
                            >
                                <x-ui.icon name="x-mark" class="size-6" />
                            </button>
                        </div>
                    </div>

                    <!-- Summary Cards -->
                    <div class="grid grid-cols-3 gap-4 px-6 py-4 bg-gray-50 dark:bg-gray-700/50">
                        <div class="text-center">
                            <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $this->summary['duplicates'] }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Duplicates</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $this->summary['case_mismatches'] }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Case Mismatches</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $this->summary['project_only'] }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Project Only</p>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="flex flex-wrap items-center gap-3 px-6 py-3 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center gap-2">
                            <button
                                wire:click="$set('conflictFilter', 'all')"
                                class="px-3 py-1.5 text-sm font-medium rounded-full transition-colors {{ $conflictFilter === 'all' ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}"
                            >
                                All
                            </button>
                            <button
                                wire:click="$set('conflictFilter', 'duplicate')"
                                class="px-3 py-1.5 text-sm font-medium rounded-full transition-colors {{ $conflictFilter === 'duplicate' ? 'bg-red-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}"
                            >
                                Duplicates
                            </button>
                            <button
                                wire:click="$set('conflictFilter', 'case_mismatch')"
                                class="px-3 py-1.5 text-sm font-medium rounded-full transition-colors {{ $conflictFilter === 'case_mismatch' ? 'bg-yellow-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}"
                            >
                                Case Mismatches
                            </button>
                            <button
                                wire:click="$set('conflictFilter', 'project_only')"
                                class="px-3 py-1.5 text-sm font-medium rounded-full transition-colors {{ $conflictFilter === 'project_only' ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}"
                            >
                                Project Only
                            </button>
                        </div>

                        <div class="flex-1">
                            <input
                                type="text"
                                wire:model.live.debounce.300ms="search"
                                placeholder="Search words..."
                                class="w-full rounded-md border-0 px-3 py-1.5 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-700 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 placeholder:text-gray-400 focus:ring-2 focus:ring-primary-600"
                            />
                        </div>
                    </div>

                    <!-- Selection Actions -->
                    @if(count($selectedWords) > 0)
                        <div class="flex items-center justify-between gap-3 px-6 py-3 bg-primary-50 dark:bg-primary-900/20 border-b border-primary-200 dark:border-primary-800">
                            <div class="flex items-center gap-x-3">
                                <span class="text-sm font-medium text-primary-700 dark:text-primary-300">
                                    {{ count($selectedWords) }} selected
                                </span>
                                <button wire:click="deselectAll" class="text-sm text-primary-600 dark:text-primary-400 hover:underline">
                                    Clear
                                </button>
                            </div>
                            <div class="flex items-center gap-x-2">
                                <button
                                    wire:click="removeDuplicatesFromProject"
                                    class="inline-flex items-center gap-x-1.5 rounded-md bg-white dark:bg-gray-800 px-3 py-1.5 text-sm font-medium text-red-700 dark:text-red-400 shadow-sm ring-1 ring-inset ring-red-300 dark:ring-red-700 hover:bg-red-50 dark:hover:bg-red-900/20"
                                >
                                    <x-ui.icon name="trash" class="size-4" />
                                    Remove from Project
                                </button>
                                @can('update', $project->organization)
                                    <button
                                        wire:click="promoteToOrganization"
                                        class="inline-flex items-center gap-x-1.5 rounded-md bg-white dark:bg-gray-800 px-3 py-1.5 text-sm font-medium text-blue-700 dark:text-blue-400 shadow-sm ring-1 ring-inset ring-blue-300 dark:ring-blue-700 hover:bg-blue-50 dark:hover:bg-blue-900/20"
                                    >
                                        <x-ui.icon name="arrow-up-circle" class="size-4" />
                                        Promote to Org
                                    </button>
                                @endcan
                                <button
                                    wire:click="standardizeCase('organization')"
                                    class="inline-flex items-center gap-x-1.5 rounded-md bg-white dark:bg-gray-800 px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700"
                                >
                                    <x-ui.icon name="arrows-right-left" class="size-4" />
                                    Match Org Case
                                </button>
                            </div>
                        </div>
                    @endif

                    <!-- Conflicts List -->
                    <div class="max-h-96 overflow-y-auto">
                        @php $conflicts = $this->conflicts; @endphp
                        @if(empty($conflicts))
                            <div class="text-center py-12">
                                <x-ui.icon name="check-circle" class="mx-auto size-12 text-green-500" />
                                <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">No conflicts</h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    @if($search)
                                        No conflicts match your search.
                                    @else
                                        All dictionary entries are in sync.
                                    @endif
                                </p>
                            </div>
                        @else
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700/50 sticky top-0">
                                    <tr>
                                        <th scope="col" class="relative w-12 px-4 py-3">
                                            <input
                                                type="checkbox"
                                                wire:click="selectAll"
                                                @checked(count($selectedWords) === count($conflicts) && count($conflicts) > 0)
                                                class="size-4 rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-600"
                                            />
                                        </th>
                                        <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                            Word
                                        </th>
                                        <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                            Type
                                        </th>
                                        <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                            Details
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($conflicts as $word => $conflict)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 {{ in_array($word, $selectedWords) ? 'bg-primary-50 dark:bg-primary-900/10' : '' }}">
                                            <td class="relative w-12 px-4 py-3">
                                                <input
                                                    type="checkbox"
                                                    wire:click="toggleWord('{{ $word }}')"
                                                    @checked(in_array($word, $selectedWords))
                                                    class="size-4 rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-600"
                                                />
                                            </td>
                                            <td class="px-3 py-3">
                                                <span class="font-mono text-sm text-gray-900 dark:text-white">{{ $conflict['word'] }}</span>
                                                @if(isset($conflict['org_variant']))
                                                    <span class="block text-xs text-gray-500 dark:text-gray-400">
                                                        Org: <span class="font-mono">{{ $conflict['org_variant'] }}</span>
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-3">
                                                @php
                                                    $typeColors = [
                                                        'duplicate' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                                        'case_mismatch' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                                                        'project_only' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                                    ];
                                                    $typeLabels = [
                                                        'duplicate' => 'Duplicate',
                                                        'case_mismatch' => 'Case Mismatch',
                                                        'project_only' => 'Project Only',
                                                    ];
                                                @endphp
                                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $typeColors[$conflict['type']] ?? 'bg-gray-100 text-gray-700' }}">
                                                    {{ $typeLabels[$conflict['type']] ?? $conflict['type'] }}
                                                </span>
                                            </td>
                                            <td class="px-3 py-3">
                                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $conflict['description'] }}</p>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>

                    <!-- Footer -->
                    <div class="border-t border-gray-200 dark:border-gray-700 px-6 py-4 flex justify-end">
                        <button
                            type="button"
                            wire:click="closeModal"
                            class="rounded-md bg-white dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
