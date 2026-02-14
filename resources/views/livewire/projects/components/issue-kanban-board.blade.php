<div
    x-data="{
        dragging: null,
        dragOver: null,
        startDrag(event, issueId) {
            this.dragging = issueId;
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', issueId);
        },
        onDragOver(event, column) {
            event.preventDefault();
            this.dragOver = column;
        },
        onDragLeave() {
            this.dragOver = null;
        },
        onDrop(event, column) {
            event.preventDefault();
            const issueId = event.dataTransfer.getData('text/plain');
            if (issueId && this.dragging) {
                $wire.moveIssue(parseInt(issueId), column);
            }
            this.dragging = null;
            this.dragOver = null;
        },
        endDrag() {
            this.dragging = null;
            this.dragOver = null;
        }
    }"
    class="space-y-4"
>
    <!-- Filters Bar -->
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-x-3">
            <!-- Assignee Filter -->
            <div class="relative">
                <select
                    wire:model.live="assigneeFilter"
                    class="rounded-md border-0 py-1.5 pl-3 pr-8 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-primary-600"
                >
                    <option value="">All Assignees</option>
                    @foreach($this->teamMembers as $member)
                        <option value="{{ $member->id }}">{{ $member->name }}</option>
                    @endforeach
                </select>
            </div>

            @if(!$project)
                <!-- Project Filter -->
                <div class="relative">
                    <select
                        wire:model.live="projectFilter"
                        class="rounded-md border-0 py-1.5 pl-3 pr-8 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-primary-600"
                    >
                        <option value="">All Projects</option>
                        @foreach($this->availableProjects as $proj)
                            <option value="{{ $proj->id }}">{{ $proj->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if($assigneeFilter || (!$project && $projectFilter))
                <button
                    wire:click="clearFilters"
                    class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300"
                >
                    Clear filters
                </button>
            @endif
        </div>

        <!-- My Assigned Count Widget -->
        <div class="flex items-center gap-x-2 text-sm">
            <span class="text-gray-500 dark:text-gray-400">My Issues:</span>
            <span class="inline-flex items-center rounded-full bg-primary-100 dark:bg-primary-900/30 px-2.5 py-0.5 text-sm font-medium text-primary-700 dark:text-primary-400">
                {{ $this->myAssignedCount }}
            </span>
        </div>
    </div>

    <!-- Kanban Board -->
    @php $issuesByColumn = $this->issuesByColumn; $counts = $this->columnCounts; @endphp
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach($columns as $status => $label)
            <div
                class="flex flex-col rounded-lg bg-gray-100 dark:bg-gray-800/50 min-h-[400px] transition-colors"
                :class="dragOver === '{{ $status }}' && 'ring-2 ring-primary-500 bg-primary-50 dark:bg-primary-900/20'"
                @dragover="onDragOver($event, '{{ $status }}')"
                @dragleave="onDragLeave"
                @drop="onDrop($event, '{{ $status }}')"
            >
                <!-- Column Header -->
                <div class="flex items-center justify-between px-3 py-2 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-x-2">
                        @php
                            $statusColors = [
                                'open' => 'bg-gray-500',
                                'in_progress' => 'bg-blue-500',
                                'review' => 'bg-yellow-500',
                                'resolved' => 'bg-green-500',
                            ];
                        @endphp
                        <span class="size-2 rounded-full {{ $statusColors[$status] }}"></span>
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ $label }}</h3>
                    </div>
                    <span class="inline-flex items-center rounded-full bg-gray-200 dark:bg-gray-700 px-2 py-0.5 text-xs font-medium text-gray-600 dark:text-gray-400">
                        {{ $counts[$status] }}
                    </span>
                </div>

                <!-- Column Content -->
                <div class="flex-1 p-2 space-y-2 overflow-y-auto">
                    @forelse($issuesByColumn[$status] as $issue)
                        <div
                            draggable="true"
                            @dragstart="startDrag($event, {{ $issue->id }})"
                            @dragend="endDrag"
                            :class="dragging === {{ $issue->id }} && 'opacity-50'"
                            class="rounded-lg bg-white dark:bg-gray-800 p-3 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 cursor-grab active:cursor-grabbing hover:shadow-md transition-shadow"
                        >
                            <!-- Issue Category Badge -->
                            @php
                                $categoryColors = [
                                    'spelling' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                    'grammar' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                                    'seo' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                    'accessibility' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
                                ];
                                $colorClass = $categoryColors[$issue->category] ?? 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
                            @endphp
                            <span class="inline-flex items-center rounded px-1.5 py-0.5 text-xs font-medium capitalize {{ $colorClass }}">
                                {{ $issue->category }}
                            </span>

                            <!-- Issue Content -->
                            <p class="mt-2 text-sm text-gray-900 dark:text-white line-clamp-2">
                                {{ $issue->text_excerpt ?? $issue->message }}
                            </p>

                            <!-- Page URL -->
                            @if($issue->result?->scan?->url)
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 truncate">
                                    {{ parse_url($issue->result->scan->url->url, PHP_URL_PATH) ?: '/' }}
                                </p>
                            @endif

                            <!-- Footer: Assignee & Actions -->
                            <div class="mt-3 flex items-center justify-between">
                                <!-- Assignee -->
                                <div x-data="{ showAssign: false }" class="relative">
                                    @if($issue->assignment?->assignedTo)
                                        <button
                                            @click="showAssign = !showAssign"
                                            class="flex items-center gap-x-1.5 text-xs text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white"
                                        >
                                            <span class="flex items-center justify-center size-5 rounded-full bg-primary-100 dark:bg-primary-900 text-primary-700 dark:text-primary-300 text-xs font-medium">
                                                {{ substr($issue->assignment->assignedTo->name, 0, 1) }}
                                            </span>
                                            <span class="truncate max-w-[80px]">{{ $issue->assignment->assignedTo->name }}</span>
                                        </button>
                                    @else
                                        <button
                                            @click="showAssign = !showAssign"
                                            class="flex items-center gap-x-1 text-xs text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300"
                                        >
                                            <x-ui.icon name="user-plus" class="size-4" />
                                            <span>Assign</span>
                                        </button>
                                    @endif

                                    <!-- Assignee Dropdown -->
                                    <div
                                        x-show="showAssign"
                                        @click.away="showAssign = false"
                                        x-transition
                                        class="absolute left-0 z-20 mt-1 w-40 origin-top-left rounded-md bg-white dark:bg-gray-800 shadow-lg ring-1 ring-gray-200 dark:ring-gray-700"
                                    >
                                        <div class="py-1">
                                            <button
                                                wire:click="assignIssue({{ $issue->id }}, null)"
                                                @click="showAssign = false"
                                                class="flex w-full items-center px-3 py-1.5 text-xs text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700"
                                            >
                                                Unassign
                                            </button>
                                            @foreach($this->teamMembers as $member)
                                                <button
                                                    wire:click="assignIssue({{ $issue->id }}, {{ $member->id }})"
                                                    @click="showAssign = false"
                                                    class="flex w-full items-center gap-x-2 px-3 py-1.5 text-xs text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                                                >
                                                    <span class="flex items-center justify-center size-4 rounded-full bg-primary-100 dark:bg-primary-900 text-primary-700 dark:text-primary-300 text-xs">
                                                        {{ substr($member->name, 0, 1) }}
                                                    </span>
                                                    {{ $member->name }}
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <!-- Severity Indicator -->
                                @php
                                    $severityColors = [
                                        'error' => 'text-red-500',
                                        'warning' => 'text-yellow-500',
                                        'suggestion' => 'text-blue-500',
                                    ];
                                @endphp
                                <span class="{{ $severityColors[$issue->severity] ?? 'text-gray-400' }}">
                                    @if($issue->severity === 'error')
                                        <x-ui.icon name="exclamation-circle" class="size-4" />
                                    @elseif($issue->severity === 'warning')
                                        <x-ui.icon name="exclamation-triangle" class="size-4" />
                                    @else
                                        <x-ui.icon name="information-circle" class="size-4" />
                                    @endif
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="flex items-center justify-center h-24 text-sm text-gray-400 dark:text-gray-500">
                            No issues
                        </div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>

    <!-- Quick Stats -->
    <div class="flex items-center justify-center gap-x-6 text-xs text-gray-500 dark:text-gray-400">
        <span>Total: {{ array_sum($counts) }}</span>
        <span>Open: {{ $counts['open'] }}</span>
        <span>In Progress: {{ $counts['in_progress'] }}</span>
        <span>Review: {{ $counts['review'] }}</span>
        <span class="text-green-600 dark:text-green-400">Resolved: {{ $counts['resolved'] }}</span>
    </div>
</div>
