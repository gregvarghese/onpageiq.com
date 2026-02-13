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
    <div class="flex min-h-full flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <!-- Logo -->
            <a href="/" class="flex items-center justify-center">
                <img src="{{ asset('images/logo.png') }}" alt="OnPageIQ" class="h-12 w-auto" />
            </a>
            <h2 class="mt-6 text-center text-2xl font-bold leading-9 tracking-tight text-gray-900 dark:text-white">
                {{ $heading ?? 'Welcome' }}
            </h2>
            @if(isset($subheading))
                <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
                    {{ $subheading }}
                </p>
            @endif
        </div>

        <div class="mt-10 sm:mx-auto sm:w-full sm:max-w-[480px]">
            <div class="bg-white dark:bg-gray-900 px-6 py-12 shadow sm:rounded-lg sm:px-12 ring-1 ring-gray-900/5 dark:ring-white/10">
                {{ $slot }}
            </div>

            @if(isset($footer))
                <div class="mt-6">
                    {{ $footer }}
                </div>
            @endif
        </div>
    </div>

    @livewireScripts
</body>
</html>
