@php
    $hasTeamFeatures = auth()->user()?->organization?->hasTeamFeatures() ?? false;
    $creditBalance = auth()->user()?->organization?->credit_balance ?? 5;
@endphp

<!-- Logo -->
<div class="flex h-16 shrink-0 items-center">
    <a href="{{ route('dashboard') }}" class="flex items-center">
        <img src="{{ asset('images/logo.png') }}" alt="OnPageIQ" class="h-10 w-auto" />
    </a>
</div>

<!-- Navigation -->
<nav class="flex flex-1 flex-col">
    <ul role="list" class="flex flex-1 flex-col gap-y-7">
        <!-- Main navigation -->
        <li>
            <ul role="list" class="-mx-2 space-y-1">
                <x-ui.nav-item href="{{ route('dashboard') }}" :active="request()->routeIs('dashboard')">
                    <x-ui.icon name="home" class="size-6 shrink-0" />
                    Dashboard
                </x-ui.nav-item>

                <x-ui.nav-item href="{{ route('scans.create') }}" :active="request()->routeIs('scans.create')">
                    <x-ui.icon name="plus-circle" class="size-6 shrink-0" />
                    New Scan
                </x-ui.nav-item>

                <x-ui.nav-item href="{{ route('projects.index') }}" :active="request()->routeIs('projects.*')">
                    <x-ui.icon name="folder" class="size-6 shrink-0" />
                    Projects
                </x-ui.nav-item>

                <x-ui.nav-item href="{{ route('reports.index') }}" :active="request()->routeIs('reports.*')">
                    <x-ui.icon name="document-text" class="size-6 shrink-0" />
                    Reports
                </x-ui.nav-item>
            </ul>
        </li>

        <!-- Team section (if on team plan) -->
        @if($hasTeamFeatures)
            <li>
                <div class="text-xs font-semibold leading-6 text-gray-400 dark:text-gray-500">Team</div>
                <ul role="list" class="-mx-2 mt-2 space-y-1">
                    <x-ui.nav-item href="{{ route('team.members') }}" :active="request()->routeIs('team.members')">
                        <x-ui.icon name="users" class="size-6 shrink-0" />
                        Members
                    </x-ui.nav-item>

                    <x-ui.nav-item href="{{ route('team.departments') }}" :active="request()->routeIs('team.departments')">
                        <x-ui.icon name="building-office" class="size-6 shrink-0" />
                        Departments
                    </x-ui.nav-item>
                </ul>
            </li>
        @endif

        <!-- Developer section -->
        <li>
            <div class="text-xs font-semibold leading-6 text-gray-400 dark:text-gray-500">Developer</div>
            <ul role="list" class="-mx-2 mt-2 space-y-1">
                <x-ui.nav-item href="{{ route('api.tokens') }}" :active="request()->routeIs('api.*')">
                    <x-ui.icon name="key" class="size-6 shrink-0" />
                    API & Webhooks
                </x-ui.nav-item>
            </ul>
        </li>

        <!-- Settings at bottom -->
        <li class="mt-auto">
            <x-ui.nav-item href="{{ route('settings.index') }}" :active="request()->routeIs('settings.*')">
                <x-ui.icon name="cog-6-tooth" class="size-6 shrink-0" />
                Settings
            </x-ui.nav-item>
        </li>

        <!-- Credit balance -->
        <li class="-mx-2 mb-2">
            <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Credits</span>
                    <span class="text-sm font-semibold text-primary-600 dark:text-primary-400">
                        {{ number_format($creditBalance) }}
                    </span>
                </div>
                <div class="mt-2">
                    <a href="{{ route('billing.credits') }}" class="text-xs font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300">
                        Add credits &rarr;
                    </a>
                </div>
            </div>
        </li>
    </ul>
</nav>
