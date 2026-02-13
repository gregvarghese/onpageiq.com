<div>
    {{-- Header --}}
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Notifications</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                You have {{ $unreadCount }} unread {{ Str::plural('notification', $unreadCount) }}
            </p>
        </div>
        <div class="flex gap-2">
            @if ($unreadCount > 0)
                <button
                    wire:click="markAllAsRead"
                    class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700"
                >
                    Mark all as read
                </button>
            @endif
            <button
                wire:click="deleteAllRead"
                wire:confirm="Are you sure you want to delete all read notifications?"
                class="rounded-lg border border-red-300 dark:border-red-600 bg-white dark:bg-gray-800 px-4 py-2 text-sm font-medium text-red-700 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20"
            >
                Delete read
            </button>
        </div>
    </div>

    {{-- Filter Tabs --}}
    <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
        <nav class="-mb-px flex gap-6">
            <button
                wire:click="$set('filter', 'all')"
                class="border-b-2 pb-3 text-sm font-medium {{ $filter === 'all' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:border-gray-300 hover:text-gray-700 dark:hover:text-gray-200' }}"
            >
                All
            </button>
            <button
                wire:click="$set('filter', 'unread')"
                class="border-b-2 pb-3 text-sm font-medium {{ $filter === 'unread' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:border-gray-300 hover:text-gray-700 dark:hover:text-gray-200' }}"
            >
                Unread
                @if ($unreadCount > 0)
                    <span class="ml-1 rounded-full bg-indigo-100 dark:bg-indigo-900 px-2 py-0.5 text-xs text-indigo-600 dark:text-indigo-400">
                        {{ $unreadCount }}
                    </span>
                @endif
            </button>
            <button
                wire:click="$set('filter', 'read')"
                class="border-b-2 pb-3 text-sm font-medium {{ $filter === 'read' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:border-gray-300 hover:text-gray-700 dark:hover:text-gray-200' }}"
            >
                Read
            </button>
        </nav>
    </div>

    {{-- Notification List --}}
    <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
        @forelse ($notifications as $notification)
            <div
                wire:key="notification-{{ $notification->id }}"
                class="flex items-start gap-4 p-4 {{ $notification->read_at ? '' : 'bg-indigo-50 dark:bg-indigo-900/20' }}"
            >
                {{-- Icon --}}
                <div class="flex-shrink-0">
                    @switch($notification->data['type'] ?? 'default')
                        @case('scan_completed')
                            <div class="rounded-full bg-green-100 dark:bg-green-900 p-3">
                                <svg class="h-5 w-5 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            @break
                        @case('credits_low')
                            <div class="rounded-full bg-yellow-100 dark:bg-yellow-900 p-3">
                                <svg class="h-5 w-5 text-yellow-600 dark:text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            @break
                        @case('credits_depleted')
                            <div class="rounded-full bg-red-100 dark:bg-red-900 p-3">
                                <svg class="h-5 w-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            @break
                        @case('team_invite')
                            <div class="rounded-full bg-indigo-100 dark:bg-indigo-900 p-3">
                                <svg class="h-5 w-5 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                </svg>
                            </div>
                            @break
                        @default
                            <div class="rounded-full bg-gray-100 dark:bg-gray-700 p-3">
                                <svg class="h-5 w-5 text-gray-600 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                    @endswitch
                </div>

                {{-- Content --}}
                <div class="min-w-0 flex-1">
                    @switch($notification->data['type'] ?? 'default')
                        @case('scan_completed')
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                Scan completed for {{ $notification->data['project_name'] ?? 'Unknown project' }}
                            </p>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                URL: {{ Str::limit($notification->data['url'] ?? '', 60) }}
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $notification->data['issue_count'] ?? 0 }} issues found
                            </p>
                            @break
                        @case('credits_low')
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                Low credit balance warning
                            </p>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                Your organization <strong>{{ $notification->data['organization_name'] ?? '' }}</strong> has {{ $notification->data['remaining_credits'] ?? 0 }} credits remaining.
                            </p>
                            @break
                        @case('credits_depleted')
                            <p class="text-sm font-medium text-red-600 dark:text-red-400">
                                Credits depleted
                            </p>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                Your organization <strong>{{ $notification->data['organization_name'] ?? '' }}</strong> has run out of credits. Purchase more to continue scanning.
                            </p>
                            @break
                        @case('team_invite')
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                Team invitation
                            </p>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                {{ $notification->data['inviter_name'] ?? 'Someone' }} invited you to join <strong>{{ $notification->data['organization_name'] ?? '' }}</strong> as {{ $notification->data['role'] ?? 'member' }}.
                            </p>
                            @break
                    @endswitch
                    <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">
                        {{ $notification->created_at->diffForHumans() }}
                    </p>
                </div>

                {{-- Actions --}}
                <div class="flex-shrink-0 flex items-center gap-2">
                    @if ($notification->data['link'] ?? null)
                        <a
                            href="{{ $notification->data['link'] }}"
                            wire:click="markAsRead('{{ $notification->id }}')"
                            class="rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700"
                        >
                            View
                        </a>
                    @endif
                    @unless ($notification->read_at)
                        <button
                            wire:click="markAsRead('{{ $notification->id }}')"
                            class="rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-1.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700"
                        >
                            Mark read
                        </button>
                    @endunless
                    <button
                        wire:click="deleteNotification('{{ $notification->id }}')"
                        class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-red-500 dark:hover:bg-gray-700 dark:hover:text-red-400"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>
            </div>
        @empty
            <div class="px-6 py-12 text-center">
                <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                </svg>
                <p class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No notifications</p>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    @if ($filter === 'unread')
                        You're all caught up!
                    @elseif ($filter === 'read')
                        No read notifications yet.
                    @else
                        You don't have any notifications yet.
                    @endif
                </p>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if ($notifications->hasPages())
        <div class="mt-6">
            {{ $notifications->links() }}
        </div>
    @endif
</div>
