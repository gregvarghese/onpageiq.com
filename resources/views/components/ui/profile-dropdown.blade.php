<div x-data="{ open: false }" class="relative">
    <button
        type="button"
        @click="open = !open"
        class="-m-1.5 flex items-center p-1.5"
    >
        <span class="sr-only">Open user menu</span>
        <span class="flex size-8 items-center justify-center rounded-full bg-gray-200 dark:bg-gray-700 text-sm font-medium text-gray-600 dark:text-gray-300">
            {{ substr(auth()->user()->name ?? 'U', 0, 1) }}
        </span>
        <span class="hidden lg:flex lg:items-center">
            <span class="ml-4 text-sm font-semibold leading-6 text-gray-900 dark:text-white">
                {{ auth()->user()->name ?? 'User' }}
            </span>
            <x-ui.icon name="chevron-down" class="ml-2 size-5 text-gray-400" />
        </span>
    </button>

    <!-- Dropdown menu -->
    <div
        x-show="open"
        @click.away="open = false"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute right-0 z-10 mt-2.5 w-48 origin-top-right rounded-md bg-white dark:bg-gray-800 py-2 shadow-lg ring-1 ring-gray-900/5 dark:ring-white/10"
        x-cloak
    >
        <a
            href="{{ route('profile.edit') }}"
            class="flex items-center gap-x-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
        >
            <x-ui.icon name="user" class="size-5" />
            Your profile
        </a>

        <a
            href="{{ route('billing.index') }}"
            class="flex items-center gap-x-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
        >
            <x-ui.icon name="credit-card" class="size-5" />
            Billing
        </a>

        <div class="my-1 h-px bg-gray-200 dark:bg-gray-700"></div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button
                type="submit"
                class="flex w-full items-center gap-x-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
            >
                <x-ui.icon name="arrow-right-on-rectangle" class="size-5" />
                Sign out
            </button>
        </form>
    </div>
</div>
