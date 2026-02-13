<div class="relative" x-data="{ open: @entangle('showDropdown') }">
    {{-- Notification Bell Button --}}
    <button
        @click="open = !open"
        type="button"
        class="relative rounded-full p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
    >
        <span class="sr-only">View notifications</span>
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
        </svg>

        {{-- Unread Badge --}}
        @if ($this->unreadCount > 0)
            <span class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-xs font-medium text-white">
                {{ $this->unreadCount > 9 ? '9+' : $this->unreadCount }}
            </span>
        @endif
    </button>

    {{-- Dropdown Panel --}}
    <div
        x-show="open"
        @click.outside="open = false"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute right-0 z-50 mt-2 w-80 origin-top-right rounded-lg bg-white dark:bg-gray-800 shadow-lg ring-1 ring-black/5 dark:ring-white/10"
        style="display: none;"
    >
        {{-- Header --}}
        <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 px-4 py-3">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Notifications</h3>
            @if ($this->unreadCount > 0)
                <button
                    wire:click="markAllAsRead"
                    class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline"
                >
                    Mark all as read
                </button>
            @endif
        </div>

        {{-- Notification List --}}
        <div class="max-h-96 overflow-y-auto">
            @forelse ($this->notifications as $notification)
                <div
                    wire:key="notification-{{ $notification->id }}"
                    class="border-b border-gray-100 dark:border-gray-700 last:border-0 {{ $notification->read_at ? 'bg-white dark:bg-gray-800' : 'bg-indigo-50 dark:bg-indigo-900/20' }}"
                >
                    <a
                        href="{{ $notification->data['link'] ?? '#' }}"
                        wire:click="markAsRead('{{ $notification->id }}')"
                        class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50"
                    >
                        <div class="flex items-start gap-3">
                            {{-- Icon --}}
                            <div class="flex-shrink-0">
                                @switch($notification->data['type'] ?? 'default')
                                    @case('scan_completed')
                                        <div class="rounded-full bg-green-100 dark:bg-green-900 p-2">
                                            <svg class="h-4 w-4 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        @break
                                    @case('credits_low')
                                        <div class="rounded-full bg-yellow-100 dark:bg-yellow-900 p-2">
                                            <svg class="h-4 w-4 text-yellow-600 dark:text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                            </svg>
                                        </div>
                                        @break
                                    @case('credits_depleted')
                                        <div class="rounded-full bg-red-100 dark:bg-red-900 p-2">
                                            <svg class="h-4 w-4 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        @break
                                    @case('team_invite')
                                        <div class="rounded-full bg-indigo-100 dark:bg-indigo-900 p-2">
                                            <svg class="h-4 w-4 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                            </svg>
                                        </div>
                                        @break
                                    @default
                                        <div class="rounded-full bg-gray-100 dark:bg-gray-700 p-2">
                                            <svg class="h-4 w-4 text-gray-600 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                @endswitch
                            </div>

                            {{-- Content --}}
                            <div class="min-w-0 flex-1">
                                @switch($notification->data['type'] ?? 'default')
                                    @case('scan_completed')
                                        <p class="text-sm text-gray-900 dark:text-white">
                                            Scan completed for <span class="font-medium">{{ Str::limit($notification->data['url'] ?? '', 30) }}</span>
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $notification->data['issue_count'] ?? 0 }} issues found
                                        </p>
                                        @break
                                    @case('credits_low')
                                        <p class="text-sm text-gray-900 dark:text-white">
                                            <span class="font-medium">Low credit balance</span>
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $notification->data['remaining_credits'] ?? 0 }} credits remaining
                                        </p>
                                        @break
                                    @case('credits_depleted')
                                        <p class="text-sm text-gray-900 dark:text-white">
                                            <span class="font-medium text-red-600 dark:text-red-400">Credits depleted</span>
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            Purchase more credits to continue
                                        </p>
                                        @break
                                    @case('team_invite')
                                        <p class="text-sm text-gray-900 dark:text-white">
                                            Invited to <span class="font-medium">{{ $notification->data['organization_name'] ?? '' }}</span>
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            by {{ $notification->data['inviter_name'] ?? '' }}
                                        </p>
                                        @break
                                @endswitch
                                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                                    {{ $notification->created_at->diffForHumans() }}
                                </p>
                            </div>

                            {{-- Unread indicator --}}
                            @unless ($notification->read_at)
                                <div class="flex-shrink-0">
                                    <span class="inline-block h-2 w-2 rounded-full bg-indigo-500"></span>
                                </div>
                            @endunless
                        </div>
                    </a>
                </div>
            @empty
                <div class="px-4 py-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                    </svg>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No notifications yet</p>
                </div>
            @endforelse
        </div>

        {{-- Footer --}}
        @if ($this->notifications->count() > 0)
            <div class="border-t border-gray-200 dark:border-gray-700 px-4 py-3">
                <a href="{{ route('notifications.index') }}" class="block text-center text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                    View all notifications
                </a>
            </div>
        @endif
    </div>
</div>
