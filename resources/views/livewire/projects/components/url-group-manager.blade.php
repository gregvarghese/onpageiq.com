<div>
    <!-- Group List -->
    <div class="space-y-2">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-medium text-gray-900 dark:text-white">URL Groups</h3>
            @if($canCreate)
                <button
                    wire:click="openCreateModal"
                    type="button"
                    class="inline-flex items-center gap-x-1 rounded-md bg-primary-600 px-2 py-1 text-xs font-medium text-white shadow-sm hover:bg-primary-500"
                >
                    <x-ui.icon name="plus" class="size-3" />
                    New Group
                </button>
            @endif
        </div>

        @if($remainingSlots !== null)
            <p class="text-xs text-gray-500 dark:text-gray-400">
                {{ $remainingSlots }} {{ Str::plural('slot', $remainingSlots) }} remaining
            </p>
        @endif

        @if($groups->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400 py-2">
                No groups created yet.
            </p>
        @else
            <ul role="list" class="space-y-1">
                @foreach($groups as $group)
                    <li
                        wire:key="group-{{ $group->id }}"
                        class="flex items-center justify-between rounded-md bg-gray-50 dark:bg-gray-700/50 px-3 py-2"
                    >
                        <div class="flex items-center gap-x-2">
                            <span
                                class="size-3 rounded-full"
                                style="background-color: {{ $group->color }}"
                            ></span>
                            <span class="text-sm text-gray-900 dark:text-white">{{ $group->name }}</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">({{ $group->urls_count }})</span>
                        </div>
                        <div class="flex items-center gap-x-1">
                            <button
                                wire:click="openEditModal({{ $group->id }})"
                                type="button"
                                class="rounded p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                title="Edit group"
                            >
                                <x-ui.icon name="pencil" class="size-4" />
                            </button>
                            <button
                                wire:click="deleteGroup({{ $group->id }})"
                                wire:confirm="Are you sure you want to delete this group? URLs in this group will become ungrouped."
                                type="button"
                                class="rounded p-1 text-gray-400 hover:text-red-600 dark:hover:text-red-400"
                                title="Delete group"
                            >
                                <x-ui.icon name="trash" class="size-4" />
                            </button>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <!-- Create/Edit Modal -->
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

                <!-- Center modal -->
                <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>

                <div class="relative inline-block transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 px-4 pb-4 pt-5 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-sm sm:p-6 sm:align-middle">
                    <form wire:submit="save">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white" id="modal-title">
                                {{ $editingGroup ? 'Edit Group' : 'Create Group' }}
                            </h3>
                            <div class="mt-4 space-y-4">
                                <!-- Name -->
                                <div>
                                    <label for="group-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Name
                                    </label>
                                    <input
                                        type="text"
                                        id="group-name"
                                        wire:model="name"
                                        class="mt-1 block w-full rounded-md border-0 px-3 py-2 text-gray-900 dark:text-white bg-white dark:bg-gray-700 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm"
                                        placeholder="e.g., Blog Posts"
                                    />
                                    @error('name')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Color -->
                                <div>
                                    <label for="group-color" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Color
                                    </label>
                                    <div class="mt-1 flex items-center gap-x-3">
                                        <input
                                            type="color"
                                            id="group-color"
                                            wire:model="color"
                                            class="h-10 w-14 cursor-pointer rounded border-0 bg-transparent p-0"
                                        />
                                        <input
                                            type="text"
                                            wire:model="color"
                                            class="block w-full rounded-md border-0 px-3 py-2 text-gray-900 dark:text-white bg-white dark:bg-gray-700 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm font-mono"
                                            placeholder="#6B7280"
                                        />
                                    </div>
                                    @error('color')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="mt-5 sm:mt-6 flex gap-x-3">
                            <button
                                type="button"
                                wire:click="closeModal"
                                class="flex-1 rounded-md bg-white dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-white shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                class="flex-1 rounded-md bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500"
                            >
                                {{ $editingGroup ? 'Update' : 'Create' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
