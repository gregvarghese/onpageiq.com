<div>
    <!-- Filters -->
    <div class="flex flex-wrap items-center gap-3 mb-4">
        <select
            wire:model.live="categoryFilter"
            class="rounded-md border-0 py-1.5 pl-3 pr-8 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-700 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 focus:ring-2 focus:ring-primary-600"
        >
            <option value="">All Categories</option>
            <option value="spelling">Spelling</option>
            <option value="grammar">Grammar</option>
            <option value="seo">SEO</option>
            <option value="readability">Readability</option>
        </select>

        <select
            wire:model.live="severityFilter"
            class="rounded-md border-0 py-1.5 pl-3 pr-8 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-700 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 focus:ring-2 focus:ring-primary-600"
        >
            <option value="">All Severities</option>
            <option value="error">Errors</option>
            <option value="warning">Warnings</option>
            <option value="suggestion">Suggestions</option>
        </select>

        @if($project->organization->canUseIssueAssignments())
            <select
                wire:model.live="assigneeFilter"
                class="rounded-md border-0 py-1.5 pl-3 pr-8 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-700 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 focus:ring-2 focus:ring-primary-600"
            >
                <option value="">All Assignees</option>
                <option value="unassigned">Unassigned</option>
                @foreach($this->teamMembers as $member)
                    <option value="{{ $member->id }}">{{ $member->name }}</option>
                @endforeach
            </select>

            <select
                wire:model.live="statusFilter"
                class="rounded-md border-0 py-1.5 pl-3 pr-8 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-700 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 focus:ring-2 focus:ring-primary-600"
            >
                <option value="">All Statuses</option>
                <option value="open">Open</option>
                <option value="in_progress">In Progress</option>
                <option value="resolved">Resolved</option>
                <option value="dismissed">Dismissed</option>
            </select>
        @endif
    </div>

    <!-- Issues List -->
    @if($this->issues->isEmpty())
        <div class="text-center py-12 border border-dashed border-gray-300 dark:border-gray-700 rounded-lg">
            <x-ui.icon name="check-circle" class="mx-auto size-12 text-green-400" />
            <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">No Issues Found</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                @if($categoryFilter || $severityFilter || $assigneeFilter || $statusFilter)
                    Try adjusting your filters.
                @else
                    Great job! No issues detected in your content.
                @endif
            </p>
        </div>
    @else
        <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
            <ul role="list" class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($this->issues as $issue)
                    <li wire:key="issue-{{ $issue->id }}" class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <div class="flex items-start justify-between gap-x-4">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-x-2">
                                    <!-- Severity Badge -->
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                        {{ $issue->severity === 'error' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' : '' }}
                                        {{ $issue->severity === 'warning' ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400' : '' }}
                                        {{ $issue->severity === 'suggestion' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' : '' }}
                                    ">
                                        {{ ucfirst($issue->severity) }}
                                    </span>

                                    <!-- Category Badge -->
                                    <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-700 px-2 py-0.5 text-xs font-medium text-gray-700 dark:text-gray-300">
                                        {{ ucfirst($issue->category) }}
                                    </span>

                                    <!-- Assignment Status -->
                                    @if($issue->assignment)
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                            {{ $issue->assignment->status === 'open' ? 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' : '' }}
                                            {{ $issue->assignment->status === 'in_progress' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' : '' }}
                                            {{ $issue->assignment->status === 'resolved' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : '' }}
                                            {{ $issue->assignment->status === 'dismissed' ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400' : '' }}
                                        ">
                                            {{ ucfirst(str_replace('_', ' ', $issue->assignment->status)) }}
                                        </span>
                                    @endif
                                </div>

                                <p class="mt-2 text-sm text-gray-900 dark:text-white">
                                    <span class="font-mono bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 px-1 rounded">{{ $issue->text_excerpt }}</span>
                                    @if($issue->suggestion)
                                        <span class="text-gray-500 dark:text-gray-400 mx-1">&rarr;</span>
                                        <span class="font-mono bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 px-1 rounded">{{ $issue->suggestion }}</span>
                                    @endif
                                </p>

                                @if($issue->context)
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 truncate">
                                        Context: {{ Str::limit($issue->context, 100) }}
                                    </p>
                                @endif

                                <div class="mt-2 flex items-center gap-x-4 text-xs text-gray-500 dark:text-gray-400">
                                    <span>{{ Str::limit($issue->scanResult?->scan?->url?->url ?? 'Unknown URL', 40) }}</span>

                                    @if($issue->assignment)
                                        <span class="inline-flex items-center gap-x-1">
                                            <x-ui.icon name="user" class="size-3" />
                                            {{ $issue->assignment->assignedTo->name }}
                                        </span>
                                        @if($issue->assignment->due_date)
                                            <span class="inline-flex items-center gap-x-1 {{ $issue->assignment->isOverdue() ? 'text-red-600 dark:text-red-400' : '' }}">
                                                <x-ui.icon name="calendar" class="size-3" />
                                                {{ $issue->assignment->due_date->format('M j') }}
                                            </span>
                                        @endif
                                    @endif
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-x-1">
                                @if($issue->category === 'spelling')
                                    <button
                                        wire:click="$dispatch('add-to-dictionary', { issueId: {{ $issue->id }} })"
                                        type="button"
                                        class="rounded-md p-2 text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 hover:bg-gray-100 dark:hover:bg-gray-700"
                                        title="Add to Dictionary"
                                    >
                                        <x-ui.icon name="book-open" class="size-4" />
                                    </button>
                                @endif

                                @if($project->organization->canUseIssueAssignments())
                                    <button
                                        wire:click="openAssignModal({{ $issue->id }})"
                                        type="button"
                                        class="rounded-md p-2 text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 hover:bg-gray-100 dark:hover:bg-gray-700"
                                        title="Assign"
                                    >
                                        <x-ui.icon name="user-plus" class="size-4" />
                                    </button>

                                    @if($issue->assignment && $issue->assignment->status !== 'resolved')
                                        <button
                                            wire:click="updateStatus({{ $issue->id }}, 'resolved')"
                                            type="button"
                                            class="rounded-md p-2 text-gray-400 hover:text-green-600 dark:hover:text-green-400 hover:bg-gray-100 dark:hover:bg-gray-700"
                                            title="Mark Resolved"
                                        >
                                            <x-ui.icon name="check" class="size-4" />
                                        </button>
                                    @endif
                                @endif

                                <button
                                    wire:click="openDismissModal({{ $issue->id }})"
                                    type="button"
                                    class="rounded-md p-2 text-gray-400 hover:text-yellow-600 dark:hover:text-yellow-400 hover:bg-gray-100 dark:hover:bg-gray-700"
                                    title="Dismiss"
                                >
                                    <x-ui.icon name="x-circle" class="size-4" />
                                </button>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="mt-4">
            {{ $this->issues->links() }}
        </div>
    @endif

    <!-- Assign Modal -->
    @if($showAssignModal && $selectedIssue)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="assign-modal-title" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                <div wire:click="closeAssignModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75"></div>
                <span class="hidden sm:inline-block sm:h-screen sm:align-middle">&#8203;</span>
                <div class="relative inline-block transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 px-4 pb-4 pt-5 text-left align-bottom shadow-xl sm:my-8 sm:w-full sm:max-w-md sm:p-6 sm:align-middle">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white" id="assign-modal-title">Assign Issue</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 truncate">{{ $selectedIssue->text_excerpt }}</p>

                        <div class="mt-4 space-y-4">
                            <div>
                                <label for="assignee" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Assign To</label>
                                <select
                                    id="assignee"
                                    wire:model="assignToUserId"
                                    class="mt-1 block w-full rounded-md border-0 py-2 pl-3 pr-8 text-gray-900 dark:text-white bg-white dark:bg-gray-700 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 focus:ring-2 focus:ring-primary-600 sm:text-sm"
                                >
                                    <option value="">Select team member...</option>
                                    @foreach($this->teamMembers as $member)
                                        <option value="{{ $member->id }}">{{ $member->name }}</option>
                                    @endforeach
                                </select>
                                @error('assignToUserId')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="dueDate" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Due Date (Optional)</label>
                                <input
                                    type="date"
                                    id="dueDate"
                                    wire:model="dueDate"
                                    class="mt-1 block w-full rounded-md border-0 py-2 px-3 text-gray-900 dark:text-white bg-white dark:bg-gray-700 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 focus:ring-2 focus:ring-primary-600 sm:text-sm"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 flex gap-x-3">
                        <button wire:click="closeAssignModal" type="button" class="flex-1 rounded-md bg-white dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-white shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600">
                            Cancel
                        </button>
                        <button wire:click="saveAssignment" type="button" class="flex-1 rounded-md bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500">
                            Assign
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Dismiss Modal -->
    @if($showDismissModal && $selectedIssue)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="dismiss-modal-title" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                <div wire:click="closeDismissModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75"></div>
                <span class="hidden sm:inline-block sm:h-screen sm:align-middle">&#8203;</span>
                <div class="relative inline-block transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 px-4 pb-4 pt-5 text-left align-bottom shadow-xl sm:my-8 sm:w-full sm:max-w-md sm:p-6 sm:align-middle">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white" id="dismiss-modal-title">Dismiss Issue</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            <span class="font-mono">{{ $selectedIssue->text_excerpt }}</span>
                        </p>

                        <div class="mt-4 space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Dismiss Scope</label>
                                <div class="space-y-2">
                                    <label class="flex items-center gap-x-2">
                                        <input type="radio" wire:model="dismissScope" value="url" class="h-4 w-4 text-primary-600 focus:ring-primary-600 border-gray-300 dark:border-gray-600">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">This URL only</span>
                                    </label>
                                    <label class="flex items-center gap-x-2">
                                        <input type="radio" wire:model="dismissScope" value="project" class="h-4 w-4 text-primary-600 focus:ring-primary-600 border-gray-300 dark:border-gray-600">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">Entire project</span>
                                    </label>
                                    <label class="flex items-center gap-x-2">
                                        <input type="radio" wire:model="dismissScope" value="pattern" class="h-4 w-4 text-primary-600 focus:ring-primary-600 border-gray-300 dark:border-gray-600">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">Pattern match (all similar)</span>
                                    </label>
                                </div>
                            </div>

                            <div>
                                <label for="dismissReason" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Reason (Optional)</label>
                                <textarea
                                    id="dismissReason"
                                    wire:model="dismissReason"
                                    rows="2"
                                    class="mt-1 block w-full rounded-md border-0 py-2 px-3 text-gray-900 dark:text-white bg-white dark:bg-gray-700 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 focus:ring-2 focus:ring-primary-600 sm:text-sm"
                                    placeholder="Why are you dismissing this issue?"
                                ></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 flex gap-x-3">
                        <button wire:click="closeDismissModal" type="button" class="flex-1 rounded-md bg-white dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-white shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600">
                            Cancel
                        </button>
                        <button wire:click="dismissIssue" type="button" class="flex-1 rounded-md bg-yellow-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-yellow-500">
                            Dismiss
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
