<div>
    <!-- Schedule List -->
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-medium text-gray-900 dark:text-white">Scheduled Scans</h3>
            @if($canCreate)
                <button
                    wire:click="openCreateModal"
                    type="button"
                    class="inline-flex items-center gap-x-1 rounded-md bg-primary-600 px-2 py-1 text-xs font-medium text-white shadow-sm hover:bg-primary-500"
                >
                    <x-ui.icon name="plus" class="size-3" />
                    New Schedule
                </button>
            @endif
        </div>

        @if($this->schedules->isEmpty())
            <div class="text-center py-6 border border-dashed border-gray-300 dark:border-gray-700 rounded-lg">
                <x-ui.icon name="clock" class="mx-auto size-8 text-gray-400" />
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No scheduled scans</p>
                @if($canCreate)
                    <button
                        wire:click="openCreateModal"
                        type="button"
                        class="mt-2 text-sm text-primary-600 dark:text-primary-400 hover:underline"
                    >
                        Create your first schedule
                    </button>
                @endif
            </div>
        @else
            <ul role="list" class="space-y-2">
                @foreach($this->schedules as $schedule)
                    <li
                        wire:key="schedule-{{ $schedule->id }}"
                        class="flex items-center justify-between rounded-lg bg-gray-50 dark:bg-gray-700/50 px-4 py-3"
                    >
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-x-2">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $schedule->is_active ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-600 dark:text-gray-300' }}">
                                    {{ $schedule->is_active ? 'Active' : 'Paused' }}
                                </span>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $schedule->getFrequencyLabel() }}
                                </span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    ({{ ucfirst($schedule->scan_type) }} scan)
                                </span>
                            </div>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                {{ $schedule->getDescription() }}
                                @if($schedule->urlGroup)
                                    &bull; Group: {{ $schedule->urlGroup->name }}
                                @endif
                            </p>
                            @if($schedule->next_run_at)
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    Next run: {{ $schedule->next_run_at->diffForHumans() }}
                                </p>
                            @endif
                        </div>
                        <div class="flex items-center gap-x-1">
                            <button
                                wire:click="toggleActive({{ $schedule->id }})"
                                type="button"
                                class="rounded p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600"
                                title="{{ $schedule->is_active ? 'Pause' : 'Resume' }}"
                            >
                                <x-ui.icon name="{{ $schedule->is_active ? 'pause' : 'play' }}" class="size-4" />
                            </button>
                            <button
                                wire:click="openEditModal({{ $schedule->id }})"
                                type="button"
                                class="rounded p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600"
                                title="Edit"
                            >
                                <x-ui.icon name="pencil" class="size-4" />
                            </button>
                            <button
                                wire:click="deleteSchedule({{ $schedule->id }})"
                                wire:confirm="Are you sure you want to delete this schedule?"
                                type="button"
                                class="rounded p-1.5 text-gray-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-gray-200 dark:hover:bg-gray-600"
                                title="Delete"
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
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="schedule-modal-title" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                <div wire:click="closeModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75"></div>
                <span class="hidden sm:inline-block sm:h-screen sm:align-middle">&#8203;</span>
                <div class="relative inline-block transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 px-4 pb-4 pt-5 text-left align-bottom shadow-xl sm:my-8 sm:w-full sm:max-w-md sm:p-6 sm:align-middle">
                    <form wire:submit="save">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white" id="schedule-modal-title">
                            {{ $editingSchedule ? 'Edit Schedule' : 'Create Schedule' }}
                        </h3>

                        <div class="mt-4 space-y-4">
                            <!-- Frequency -->
                            <div>
                                <label for="frequency" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Frequency</label>
                                <select
                                    id="frequency"
                                    wire:model.live="frequency"
                                    class="mt-1 block w-full rounded-md border-0 py-2 pl-3 pr-8 text-gray-900 dark:text-white bg-white dark:bg-gray-700 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 focus:ring-2 focus:ring-primary-600 sm:text-sm"
                                >
                                    <option value="hourly">Hourly</option>
                                    <option value="daily">Daily</option>
                                    <option value="weekly">Weekly</option>
                                    <option value="monthly">Monthly</option>
                                </select>
                            </div>

                            <!-- Scan Type -->
                            <div>
                                <label for="scanType" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Scan Type</label>
                                <select
                                    id="scanType"
                                    wire:model="scanType"
                                    class="mt-1 block w-full rounded-md border-0 py-2 pl-3 pr-8 text-gray-900 dark:text-white bg-white dark:bg-gray-700 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 focus:ring-2 focus:ring-primary-600 sm:text-sm"
                                >
                                    <option value="quick">Quick Scan (1 credit/URL)</option>
                                    <option value="deep">Deep Scan (3 credits/URL)</option>
                                </select>
                            </div>

                            <!-- Preferred Time -->
                            @if($frequency !== 'hourly')
                                <div>
                                    <label for="preferredTime" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Preferred Time</label>
                                    <input
                                        type="time"
                                        id="preferredTime"
                                        wire:model="preferredTime"
                                        class="mt-1 block w-full rounded-md border-0 py-2 px-3 text-gray-900 dark:text-white bg-white dark:bg-gray-700 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 focus:ring-2 focus:ring-primary-600 sm:text-sm"
                                    />
                                </div>
                            @endif

                            <!-- Day of Week (for weekly) -->
                            @if($frequency === 'weekly')
                                <div>
                                    <label for="dayOfWeek" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Day of Week</label>
                                    <select
                                        id="dayOfWeek"
                                        wire:model="dayOfWeek"
                                        class="mt-1 block w-full rounded-md border-0 py-2 pl-3 pr-8 text-gray-900 dark:text-white bg-white dark:bg-gray-700 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 focus:ring-2 focus:ring-primary-600 sm:text-sm"
                                    >
                                        <option value="0">Sunday</option>
                                        <option value="1">Monday</option>
                                        <option value="2">Tuesday</option>
                                        <option value="3">Wednesday</option>
                                        <option value="4">Thursday</option>
                                        <option value="5">Friday</option>
                                        <option value="6">Saturday</option>
                                    </select>
                                </div>
                            @endif

                            <!-- Day of Month (for monthly) -->
                            @if($frequency === 'monthly')
                                <div>
                                    <label for="dayOfMonth" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Day of Month</label>
                                    <select
                                        id="dayOfMonth"
                                        wire:model="dayOfMonth"
                                        class="mt-1 block w-full rounded-md border-0 py-2 pl-3 pr-8 text-gray-900 dark:text-white bg-white dark:bg-gray-700 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 focus:ring-2 focus:ring-primary-600 sm:text-sm"
                                    >
                                        @for($i = 1; $i <= 28; $i++)
                                            <option value="{{ $i }}">{{ $i }}</option>
                                        @endfor
                                    </select>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Days 29-31 are not available to ensure consistency across months.</p>
                                </div>
                            @endif

                            <!-- URL Group (optional) -->
                            @if($this->urlGroups->isNotEmpty())
                                <div>
                                    <label for="urlGroupId" class="block text-sm font-medium text-gray-700 dark:text-gray-300">URL Group (Optional)</label>
                                    <select
                                        id="urlGroupId"
                                        wire:model="urlGroupId"
                                        class="mt-1 block w-full rounded-md border-0 py-2 pl-3 pr-8 text-gray-900 dark:text-white bg-white dark:bg-gray-700 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 focus:ring-2 focus:ring-primary-600 sm:text-sm"
                                    >
                                        <option value="">All URLs</option>
                                        @foreach($this->urlGroups as $group)
                                            <option value="{{ $group->id }}">{{ $group->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            <!-- Active Toggle -->
                            <div class="flex items-center gap-x-2">
                                <input
                                    type="checkbox"
                                    id="isActive"
                                    wire:model="isActive"
                                    class="h-4 w-4 rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-600"
                                />
                                <label for="isActive" class="text-sm text-gray-700 dark:text-gray-300">Active</label>
                            </div>
                        </div>

                        <div class="mt-5 flex gap-x-3">
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
                                {{ $editingSchedule ? 'Update' : 'Create' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
