<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Departments</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Organize team members into departments with credit budgets</p>
            </div>
            @if($organization->hasTeamFeatures())
                <button
                    wire:click="openCreateModal"
                    class="inline-flex items-center gap-x-2 rounded-md bg-primary-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"
                >
                    <x-ui.icon name="plus" class="size-5" />
                    New Department
                </button>
            @endif
        </div>
    </x-slot>

    {{-- Flash Messages --}}
    @if (session('success'))
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/50 p-4">
            <div class="flex">
                <x-ui.icon name="check-circle" class="size-5 text-green-400" />
                <p class="ml-3 text-sm font-medium text-green-800 dark:text-green-200">
                    {{ session('success') }}
                </p>
            </div>
        </div>
    @endif

    {{-- Team Features Gate --}}
    @if(!$organization->hasTeamFeatures())
        <div class="rounded-lg border border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-900/30 p-6 text-center">
            <x-ui.icon name="building-office" class="mx-auto size-12 text-amber-400" />
            <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">Department Management</h3>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                Upgrade to the Team or Enterprise plan to create departments and manage credit budgets.
            </p>
            <a
                href="{{ route('billing.index') }}"
                class="mt-4 inline-flex items-center gap-x-2 rounded-md bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-500"
            >
                Upgrade Plan
            </a>
        </div>
    @else
        {{-- Search --}}
        <div class="mb-6">
            <div class="relative max-w-md">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <x-ui.icon name="magnifying-glass" class="size-5 text-gray-400" />
                </div>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search departments..."
                    class="block w-full rounded-md border-0 py-2.5 pl-10 pr-3 text-gray-900 dark:text-white bg-white dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6"
                />
            </div>
        </div>

        {{-- Departments Grid --}}
        @if($departments->isEmpty())
            <div class="text-center py-12">
                <x-ui.icon name="building-office" class="mx-auto size-12 text-gray-400" />
                <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">No departments</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by creating a new department.</p>
                <div class="mt-6">
                    <button
                        wire:click="openCreateModal"
                        class="inline-flex items-center gap-x-2 rounded-md bg-primary-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-500"
                    >
                        <x-ui.icon name="plus" class="size-5" />
                        New Department
                    </button>
                </div>
            </div>
        @else
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($departments as $department)
                    <div
                        wire:key="department-{{ $department->id }}"
                        class="relative flex flex-col rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm"
                    >
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-x-3">
                                <div class="flex size-10 items-center justify-center rounded-lg bg-primary-50 dark:bg-primary-900/30">
                                    <x-ui.icon name="building-office" class="size-5 text-primary-600 dark:text-primary-400" />
                                </div>
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ $department->name }}
                                    </h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $department->users_count }} {{ Str::plural('member', $department->users_count) }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button
                                    wire:click="openMembersModal({{ $department->id }})"
                                    class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300"
                                    title="Manage Members"
                                >
                                    <x-ui.icon name="users" class="size-5" />
                                </button>
                                <button
                                    wire:click="openEditModal({{ $department->id }})"
                                    class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300"
                                    title="Edit"
                                >
                                    <x-ui.icon name="pencil" class="size-5" />
                                </button>
                                <button
                                    wire:click="confirmDelete({{ $department->id }})"
                                    class="text-gray-400 hover:text-red-500"
                                    title="Delete"
                                >
                                    <x-ui.icon name="trash" class="size-5" />
                                </button>
                            </div>
                        </div>

                        <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500 dark:text-gray-400">Credit Budget</span>
                                <span class="font-medium text-gray-900 dark:text-white">
                                    {{ number_format($department->credit_budget) }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-sm mt-2">
                                <span class="text-gray-500 dark:text-gray-400">Used</span>
                                <span class="font-medium text-gray-900 dark:text-white">
                                    {{ number_format($department->credit_used) }}
                                </span>
                            </div>
                            @if($department->credit_budget > 0)
                                <div class="mt-3">
                                    <div class="h-2 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                                        @php
                                            $percentage = min(100, ($department->credit_used / $department->credit_budget) * 100);
                                        @endphp
                                        <div
                                            class="h-full rounded-full @if($percentage >= 90) bg-red-500 @elseif($percentage >= 75) bg-amber-500 @else bg-primary-500 @endif"
                                            style="width: {{ $percentage }}%"
                                        ></div>
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ number_format($department->getRemainingBudget()) }} credits remaining
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @endif

    {{-- Create Modal --}}
    @if ($showCreateModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75 transition-opacity" wire:click="$set('showCreateModal', false)"></div>
                <span class="hidden sm:inline-block sm:h-screen sm:align-middle">&#8203;</span>
                <div class="inline-block transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 px-4 pb-4 pt-5 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6 sm:align-middle">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Create Department</h3>
                    <div class="mt-4 space-y-4">
                        <div>
                            <label for="dept-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Department Name</label>
                            <input
                                type="text"
                                id="dept-name"
                                wire:model="name"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 px-3 py-2 sm:text-sm"
                                placeholder="e.g., Marketing"
                            >
                            @error('name')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="dept-budget" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Credit Budget (optional)</label>
                            <input
                                type="number"
                                id="dept-budget"
                                wire:model="creditBudget"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 px-3 py-2 sm:text-sm"
                                placeholder="0"
                                min="0"
                            >
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Monthly credit limit for this department. Leave empty for unlimited.</p>
                            @error('creditBudget')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end gap-3">
                        <button wire:click="$set('showCreateModal', false)" class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600">
                            Cancel
                        </button>
                        <button wire:click="createDepartment" class="rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">
                            Create Department
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Edit Modal --}}
    @if ($showEditModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75 transition-opacity" wire:click="$set('showEditModal', false)"></div>
                <span class="hidden sm:inline-block sm:h-screen sm:align-middle">&#8203;</span>
                <div class="inline-block transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 px-4 pb-4 pt-5 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6 sm:align-middle">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Edit Department</h3>
                    <div class="mt-4 space-y-4">
                        <div>
                            <label for="edit-dept-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Department Name</label>
                            <input
                                type="text"
                                id="edit-dept-name"
                                wire:model="name"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 px-3 py-2 sm:text-sm"
                            >
                            @error('name')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="edit-dept-budget" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Credit Budget</label>
                            <input
                                type="number"
                                id="edit-dept-budget"
                                wire:model="creditBudget"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 px-3 py-2 sm:text-sm"
                                min="0"
                            >
                            @error('creditBudget')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end gap-3">
                        <button wire:click="$set('showEditModal', false)" class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600">
                            Cancel
                        </button>
                        <button wire:click="updateDepartment" class="rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">
                            Update Department
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Delete Modal --}}
    @if ($showDeleteModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75 transition-opacity" wire:click="$set('showDeleteModal', false)"></div>
                <span class="hidden sm:inline-block sm:h-screen sm:align-middle">&#8203;</span>
                <div class="inline-block transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 px-4 pb-4 pt-5 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6 sm:align-middle">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Delete Department</h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Are you sure you want to delete this department? Members will be unassigned but not removed from the organization.
                    </p>
                    <div class="mt-6 flex justify-end gap-3">
                        <button wire:click="$set('showDeleteModal', false)" class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600">
                            Cancel
                        </button>
                        <button wire:click="deleteDepartment" class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">
                            Delete Department
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Manage Members Modal --}}
    @if ($showMembersModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75 transition-opacity" wire:click="$set('showMembersModal', false)"></div>
                <span class="hidden sm:inline-block sm:h-screen sm:align-middle">&#8203;</span>
                <div class="inline-block transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 px-4 pb-4 pt-5 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6 sm:align-middle">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Manage Department Members</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Select members to assign to this department.</p>
                    <div class="mt-4 max-h-64 overflow-y-auto space-y-2">
                        @foreach($allMembers as $member)
                            <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer">
                                <input
                                    type="checkbox"
                                    wire:model="selectedMembers"
                                    value="{{ $member->id }}"
                                    class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500"
                                >
                                <div class="flex items-center gap-3">
                                    <div class="size-8 flex-shrink-0">
                                        @if($member->avatar)
                                            <img class="size-8 rounded-full" src="{{ $member->avatar }}" alt="{{ $member->name }}">
                                        @else
                                            <div class="size-8 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center">
                                                <span class="text-xs font-medium text-primary-600 dark:text-primary-400">
                                                    {{ strtoupper(substr($member->name, 0, 2)) }}
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                    <div>
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $member->name }}</span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400 block">{{ $member->email }}</span>
                                    </div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                    <div class="mt-6 flex justify-end gap-3">
                        <button wire:click="$set('showMembersModal', false)" class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600">
                            Cancel
                        </button>
                        <button wire:click="updateMembers" class="rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">
                            Save Members
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
