<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'OnPageIQ') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Dark mode initialization (prevents FOUC) -->
    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>

    @livewireStyles
</head>
<body class="h-full bg-gray-50 dark:bg-gray-950 antialiased">
    <div x-data="{ sidebarOpen: false }" class="min-h-full">
        <!-- Off-canvas menu for mobile -->
        <div x-show="sidebarOpen" class="relative z-50 lg:hidden" x-cloak>
            <div
                x-show="sidebarOpen"
                x-transition:enter="transition-opacity ease-linear duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity ease-linear duration-300"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-gray-900/80 dark:bg-gray-950/80"
                @click="sidebarOpen = false"
            ></div>

            <div class="fixed inset-0 flex">
                <div
                    x-show="sidebarOpen"
                    x-transition:enter="transition ease-in-out duration-300 transform"
                    x-transition:enter-start="-translate-x-full"
                    x-transition:enter-end="translate-x-0"
                    x-transition:leave="transition ease-in-out duration-300 transform"
                    x-transition:leave-start="translate-x-0"
                    x-transition:leave-end="-translate-x-full"
                    class="relative mr-16 flex w-full max-w-xs flex-1"
                >
                    <div class="absolute left-full top-0 flex w-16 justify-center pt-5">
                        <button type="button" class="-m-2.5 p-2.5" @click="sidebarOpen = false">
                            <span class="sr-only">Close sidebar</span>
                            <x-ui.icon name="x-mark" class="size-6 text-white" />
                        </button>
                    </div>

                    <!-- Mobile sidebar content -->
                    <div class="flex grow flex-col gap-y-5 overflow-y-auto bg-white dark:bg-gray-900 px-6 pb-4 ring-1 ring-gray-900/10 dark:ring-white/10">
                        <x-ui.sidebar-content />
                    </div>
                </div>
            </div>
        </div>

        <!-- Static sidebar for desktop -->
        <div class="hidden lg:fixed lg:inset-y-0 lg:z-50 lg:flex lg:w-72 lg:flex-col">
            <div class="flex grow flex-col gap-y-5 overflow-y-auto border-r border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-6 pb-4">
                <x-ui.sidebar-content />
            </div>
        </div>

        <!-- Main content area -->
        <div class="lg:pl-72">
            <!-- Top bar -->
            <div class="sticky top-0 z-40 flex h-16 shrink-0 items-center gap-x-4 border-b border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 shadow-sm sm:gap-x-6 sm:px-6 lg:px-8">
                <button type="button" class="-m-2.5 p-2.5 text-gray-700 dark:text-gray-300 lg:hidden" @click="sidebarOpen = true">
                    <span class="sr-only">Open sidebar</span>
                    <x-ui.icon name="bars-3" class="size-6" />
                </button>

                <!-- Separator -->
                <div class="h-6 w-px bg-gray-200 dark:bg-gray-700 lg:hidden"></div>

                <div class="flex flex-1 gap-x-4 self-stretch lg:gap-x-6">
                    <!-- Search (optional slot) -->
                    <div class="flex flex-1 items-center">
                        {{ $search ?? '' }}
                    </div>

                    <div class="flex items-center gap-x-4 lg:gap-x-6">
                        <!-- Dark mode toggle -->
                        <x-ui.dark-mode-toggle />

                        <!-- Separator -->
                        <div class="hidden lg:block lg:h-6 lg:w-px lg:bg-gray-200 dark:lg:bg-gray-700"></div>

                        <!-- Profile dropdown -->
                        @auth
                            <x-ui.profile-dropdown />
                        @endauth
                    </div>
                </div>
            </div>

            <!-- Page content -->
            <main class="py-10">
                <div class="px-4 sm:px-6 lg:px-8">
                    <!-- Page header -->
                    @if(isset($header))
                        <div class="mb-8">
                            {{ $header }}
                        </div>
                    @endif

                    <!-- Main content -->
                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>

    @livewireScripts
</body>
</html>
