<div>
    <!-- Toggle Button -->
    <button
        type="button"
        wire:click="togglePanel"
        class="inline-flex items-center gap-x-2 rounded-md bg-white dark:bg-gray-800 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700"
    >
        <x-ui.icon name="magnifying-glass-circle" class="size-5" />
        Discovered URLs
        @if($this->statusCounts['pending'] > 0)
            <span class="inline-flex items-center rounded-full bg-yellow-100 dark:bg-yellow-900/30 px-2 py-0.5 text-xs font-medium text-yellow-700 dark:text-yellow-400">
                {{ $this->statusCounts['pending'] }}
            </span>
        @endif
    </button>

    <!-- Panel -->
    @if($showPanel)
        <div class="mt-4 rounded-lg bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 overflow-hidden">
            <!-- Header -->
            <div class="border-b border-gray-200 dark:border-gray-700 px-4 py-3">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">URL Discovery</h3>
                        <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                            Review and approve URLs discovered during scans
                        </p>
                    </div>
                    <button
                        type="button"
                        wire:click="togglePanel"
                        class="rounded-md text-gray-400 hover:text-gray-500 dark:hover:text-gray-300"
                    >
                        <x-ui.icon name="x-mark" class="size-5" />
                    </button>
                </div>
            </div>

            <!-- Status Tabs -->
            <div class="flex items-center gap-x-1 px-4 py-2 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
                <button
                    wire:click="$set('statusFilter', 'pending')"
                    class="inline-flex items-center gap-x-1.5 rounded-md px-3 py-1.5 text-sm font-medium transition-colors {{ $statusFilter === 'pending' ? 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-600' }}"
                >
                    Pending
                    <span class="text-xs">{{ $this->statusCounts['pending'] }}</span>
                </button>
                <button
                    wire:click="$set('statusFilter', 'approved')"
                    class="inline-flex items-center gap-x-1.5 rounded-md px-3 py-1.5 text-sm font-medium transition-colors {{ $statusFilter === 'approved' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-600' }}"
                >
                    Approved
                    <span class="text-xs">{{ $this->statusCounts['approved'] }}</span>
                </button>
                <button
                    wire:click="$set('statusFilter', 'rejected')"
                    class="inline-flex items-center gap-x-1.5 rounded-md px-3 py-1.5 text-sm font-medium transition-colors {{ $statusFilter === 'rejected' ? 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-600' }}"
                >
                    Rejected
                    <span class="text-xs">{{ $this->statusCounts['rejected'] }}</span>
                </button>
                <button
                    wire:click="$set('statusFilter', 'all')"
                    class="inline-flex items-center gap-x-1.5 rounded-md px-3 py-1.5 text-sm font-medium transition-colors {{ $statusFilter === 'all' ? 'bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-600' }}"
                >
                    All
                    <span class="text-xs">{{ $this->statusCounts['all'] }}</span>
                </button>

                <div class="flex-1"></div>

                <!-- Search -->
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search URLs..."
                    class="w-64 rounded-md border-0 px-3 py-1.5 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-700 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 placeholder:text-gray-400 focus:ring-2 focus:ring-primary-600"
                />

                @if($statusFilter === 'rejected')
                    <button
                        wire:click="deleteRejected"
                        wire:confirm="Are you sure you want to permanently delete all rejected URLs?"
                        class="inline-flex items-center gap-x-1.5 rounded-md bg-red-50 dark:bg-red-900/20 px-3 py-1.5 text-sm font-medium text-red-700 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/30"
                    >
                        <x-ui.icon name="trash" class="size-4" />
                        Clear Rejected
                    </button>
                @endif
            </div>

            <!-- Bulk Actions -->
            @if(count($selectedUrls) > 0 && $statusFilter === 'pending')
                <div class="flex items-center justify-between gap-3 px-4 py-2 bg-primary-50 dark:bg-primary-900/20 border-b border-primary-200 dark:border-primary-800">
                    <div class="flex items-center gap-x-3">
                        <span class="text-sm font-medium text-primary-700 dark:text-primary-300">
                            {{ count($selectedUrls) }} selected
                        </span>
                        <button wire:click="deselectAll" class="text-sm text-primary-600 dark:text-primary-400 hover:underline">
                            Clear
                        </button>
                    </div>
                    <div class="flex items-center gap-x-2">
                        <button
                            wire:click="bulkApprove"
                            class="inline-flex items-center gap-x-1.5 rounded-md bg-green-600 px-3 py-1.5 text-sm font-medium text-white shadow-sm hover:bg-green-500"
                        >
                            <x-ui.icon name="check" class="size-4" />
                            Approve Selected
                        </button>
                        <button
                            wire:click="bulkReject"
                            class="inline-flex items-center gap-x-1.5 rounded-md bg-red-600 px-3 py-1.5 text-sm font-medium text-white shadow-sm hover:bg-red-500"
                        >
                            <x-ui.icon name="x-mark" class="size-4" />
                            Reject Selected
                        </button>
                    </div>
                </div>
            @endif

            <!-- URL List -->
            <div class="max-h-96 overflow-y-auto">
                @php $urls = $this->discoveredUrls; @endphp
                @if($urls->isEmpty())
                    <div class="text-center py-12">
                        <x-ui.icon name="magnifying-glass" class="mx-auto size-12 text-gray-400" />
                        <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">No URLs found</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            @if($search)
                                No URLs match your search.
                            @elseif($statusFilter === 'pending')
                                No pending URLs to review.
                            @else
                                No discovered URLs in this category.
                            @endif
                        </p>
                    </div>
                @else
                    <ul role="list" class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($urls as $url)
                            <li wire:key="discovered-{{ $url->id }}" class="flex items-center gap-x-4 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 {{ in_array($url->id, $selectedUrls) ? 'bg-primary-50 dark:bg-primary-900/10' : '' }}">
                                @if($statusFilter === 'pending')
                                    <input
                                        type="checkbox"
                                        wire:click="toggleSelection({{ $url->id }})"
                                        @checked(in_array($url->id, $selectedUrls))
                                        class="size-4 rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-600"
                                    />
                                @endif

                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-x-2">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                            {{ $url->url }}
                                        </p>
                                        <a href="{{ $url->url }}" target="_blank" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                            <x-ui.icon name="arrow-top-right-on-square" class="size-4" />
                                        </a>
                                    </div>
                                    <div class="mt-1 flex items-center gap-x-4 text-xs text-gray-500 dark:text-gray-400">
                                        <span>Discovered {{ $url->discovered_at->diffForHumans() }}</span>
                                        @if($url->source_url)
                                            <span>From: {{ parse_url($url->source_url, PHP_URL_PATH) ?: '/' }}</span>
                                        @endif
                                        @if($url->link_text)
                                            <span class="truncate max-w-xs">"{{ $url->link_text }}"</span>
                                        @endif
                                    </div>
                                    @if($url->rejection_reason)
                                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">
                                            Rejection reason: {{ $url->rejection_reason }}
                                        </p>
                                    @endif
                                </div>

                                <!-- Status Badge -->
                                @php
                                    $statusColors = [
                                        'pending' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                                        'approved' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                        'rejected' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                    ];
                                @endphp
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $statusColors[$url->status] ?? 'bg-gray-100 text-gray-700' }}">
                                    {{ ucfirst($url->status) }}
                                </span>

                                <!-- Actions -->
                                <div class="flex items-center gap-x-1">
                                    @if($url->status === 'pending')
                                        <button
                                            wire:click="approveUrl({{ $url->id }})"
                                            class="rounded-md p-1.5 text-green-600 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/30"
                                            title="Approve"
                                        >
                                            <x-ui.icon name="check" class="size-5" />
                                        </button>
                                        <button
                                            wire:click="rejectUrl({{ $url->id }})"
                                            class="rounded-md p-1.5 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30"
                                            title="Reject"
                                        >
                                            <x-ui.icon name="x-mark" class="size-5" />
                                        </button>
                                    @elseif($url->status === 'rejected')
                                        <button
                                            wire:click="requeueUrl({{ $url->id }})"
                                            class="rounded-md p-1.5 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700"
                                            title="Re-queue for review"
                                        >
                                            <x-ui.icon name="arrow-path" class="size-5" />
                                        </button>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>

                    <!-- Pagination -->
                    @if($urls->hasPages())
                        <div class="border-t border-gray-200 dark:border-gray-700 px-4 py-3">
                            {{ $urls->links() }}
                        </div>
                    @endif
                @endif
            </div>
        </div>
    @endif
</div>
