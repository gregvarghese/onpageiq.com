<div class="rounded-lg bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 overflow-hidden">
    <!-- Accordion Header -->
    <button
        type="button"
        wire:click="toggle"
        class="flex w-full items-center justify-between px-4 py-3 text-left hover:bg-gray-50 dark:hover:bg-gray-700/50"
    >
        <div class="flex items-center gap-x-3">
            <x-ui.icon name="share" class="size-5 text-gray-500 dark:text-gray-400" />
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Social Previews & Meta</h3>
            @if($this->totalWarnings > 0)
                <span class="inline-flex items-center rounded-full bg-yellow-100 dark:bg-yellow-900/30 px-2 py-0.5 text-xs font-medium text-yellow-700 dark:text-yellow-400">
                    {{ $this->totalWarnings }} {{ Str::plural('warning', $this->totalWarnings) }}
                </span>
            @else
                <span class="inline-flex items-center rounded-full bg-green-100 dark:bg-green-900/30 px-2 py-0.5 text-xs font-medium text-green-700 dark:text-green-400">
                    All good
                </span>
            @endif
        </div>
        <x-ui.icon name="chevron-down" class="size-5 text-gray-500 transition-transform {{ $expanded ? 'rotate-180' : '' }}" />
    </button>

    <!-- Accordion Content -->
    @if($expanded)
        <div class="border-t border-gray-200 dark:border-gray-700">
            @php $meta = $this->metaData; $warnings = $this->validationWarnings; @endphp

            <!-- Meta Data Overview -->
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Current Meta Tags</h4>
                    <button
                        wire:click="generateSuggestions"
                        wire:loading.attr="disabled"
                        wire:target="generateSuggestions"
                        class="inline-flex items-center gap-x-1.5 rounded-md bg-primary-600 px-2.5 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-primary-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <x-ui.icon name="sparkles" class="size-4" wire:loading.class="animate-spin" wire:target="generateSuggestions" />
                        Get AI Suggestions
                    </button>
                </div>
                <dl class="space-y-2">
                    <div>
                        <dt class="text-xs text-gray-500 dark:text-gray-400">Title</dt>
                        <dd class="text-sm text-gray-900 dark:text-white">
                            {{ $meta['title'] ?: '(not set)' }}
                            @if($meta['title'])
                                <span class="text-xs text-gray-500 dark:text-gray-400">({{ strlen($meta['title']) }} chars)</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 dark:text-gray-400">Description</dt>
                        <dd class="text-sm text-gray-900 dark:text-white">
                            {{ $meta['description'] ?: '(not set)' }}
                            @if($meta['description'])
                                <span class="text-xs text-gray-500 dark:text-gray-400">({{ strlen($meta['description']) }} chars)</span>
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>

            <!-- Platform Previews Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 p-4">
                <!-- Google SERP Preview -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center gap-x-2">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none">
                                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                            </svg>
                            Google Search
                        </h4>
                        @if(count($warnings['google']) > 0)
                            <span class="text-xs text-yellow-600 dark:text-yellow-400">{{ count($warnings['google']) }} {{ Str::plural('issue', count($warnings['google'])) }}</span>
                        @endif
                    </div>
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3 bg-white dark:bg-gray-900">
                        <p class="text-sm text-blue-600 dark:text-blue-400 hover:underline truncate">
                            {{ $meta['title'] ?: 'Page Title' }}
                        </p>
                        <p class="text-xs text-green-700 dark:text-green-500 truncate mt-0.5">
                            {{ parse_url($url->url, PHP_URL_HOST) }}{{ parse_url($url->url, PHP_URL_PATH) ?: '/' }}
                        </p>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1 line-clamp-2">
                            {{ $meta['description'] ?: 'No meta description set. Search engines may use content from the page instead.' }}
                        </p>
                    </div>
                    @if(count($warnings['google']) > 0)
                        <ul class="text-xs space-y-1">
                            @foreach($warnings['google'] as $warning)
                                <li class="flex items-center gap-x-1 text-yellow-600 dark:text-yellow-400">
                                    <x-ui.icon name="exclamation-triangle" class="size-3" />
                                    {{ $warning }}
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <!-- Facebook Preview -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center gap-x-2">
                            <svg class="size-4 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                            Facebook
                        </h4>
                        @if(count($warnings['facebook']) > 0)
                            <span class="text-xs text-yellow-600 dark:text-yellow-400">{{ count($warnings['facebook']) }} {{ Str::plural('issue', count($warnings['facebook'])) }}</span>
                        @endif
                    </div>
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden bg-gray-50 dark:bg-gray-900">
                        @if($meta['og']['image'])
                            <div class="aspect-[1.91/1] bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                <img src="{{ $meta['og']['image'] }}" alt="OG Image" class="w-full h-full object-cover" />
                            </div>
                        @else
                            <div class="aspect-[1.91/1] bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                <x-ui.icon name="photo" class="size-8 text-gray-400" />
                            </div>
                        @endif
                        <div class="p-2">
                            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">{{ parse_url($url->url, PHP_URL_HOST) }}</p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                {{ $meta['og']['title'] ?: $meta['title'] ?: 'Page Title' }}
                            </p>
                            <p class="text-xs text-gray-600 dark:text-gray-400 line-clamp-1">
                                {{ $meta['og']['description'] ?: $meta['description'] ?: 'No description' }}
                            </p>
                        </div>
                    </div>
                    @if(count($warnings['facebook']) > 0)
                        <ul class="text-xs space-y-1">
                            @foreach($warnings['facebook'] as $warning)
                                <li class="flex items-center gap-x-1 text-yellow-600 dark:text-yellow-400">
                                    <x-ui.icon name="exclamation-triangle" class="size-3" />
                                    {{ $warning }}
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <!-- Twitter/X Preview -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center gap-x-2">
                            <svg class="size-4" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                            </svg>
                            Twitter / X
                        </h4>
                        @if(count($warnings['twitter']) > 0)
                            <span class="text-xs text-yellow-600 dark:text-yellow-400">{{ count($warnings['twitter']) }} {{ Str::plural('issue', count($warnings['twitter'])) }}</span>
                        @endif
                    </div>
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden bg-white dark:bg-gray-900">
                        @php $twitterImage = $meta['twitter']['image'] ?: $meta['og']['image']; @endphp
                        @if($twitterImage)
                            <div class="aspect-[2/1] bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                <img src="{{ $twitterImage }}" alt="Twitter Card" class="w-full h-full object-cover" />
                            </div>
                        @else
                            <div class="aspect-[2/1] bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                <x-ui.icon name="photo" class="size-8 text-gray-400" />
                            </div>
                        @endif
                        <div class="p-3">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                {{ $meta['twitter']['title'] ?: $meta['og']['title'] ?: $meta['title'] ?: 'Page Title' }}
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 line-clamp-2">
                                {{ $meta['twitter']['description'] ?: $meta['og']['description'] ?: $meta['description'] ?: 'No description' }}
                            </p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1 flex items-center gap-x-1">
                                <x-ui.icon name="link" class="size-3" />
                                {{ parse_url($url->url, PHP_URL_HOST) }}
                            </p>
                        </div>
                    </div>
                    @if(count($warnings['twitter']) > 0)
                        <ul class="text-xs space-y-1">
                            @foreach($warnings['twitter'] as $warning)
                                <li class="flex items-center gap-x-1 text-yellow-600 dark:text-yellow-400">
                                    <x-ui.icon name="exclamation-triangle" class="size-3" />
                                    {{ $warning }}
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <!-- LinkedIn Preview -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center gap-x-2">
                            <svg class="size-4 text-blue-700" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                            </svg>
                            LinkedIn
                        </h4>
                        @if(count($warnings['linkedin']) > 0)
                            <span class="text-xs text-yellow-600 dark:text-yellow-400">{{ count($warnings['linkedin']) }} {{ Str::plural('issue', count($warnings['linkedin'])) }}</span>
                        @endif
                    </div>
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden bg-gray-50 dark:bg-gray-900">
                        @if($meta['og']['image'])
                            <div class="aspect-[1.91/1] bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                <img src="{{ $meta['og']['image'] }}" alt="LinkedIn Preview" class="w-full h-full object-cover" />
                            </div>
                        @else
                            <div class="aspect-[1.91/1] bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                <x-ui.icon name="photo" class="size-8 text-gray-400" />
                            </div>
                        @endif
                        <div class="p-3">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                {{ $meta['og']['title'] ?: $meta['title'] ?: 'Page Title' }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                {{ parse_url($url->url, PHP_URL_HOST) }}
                            </p>
                        </div>
                    </div>
                    @if(count($warnings['linkedin']) > 0)
                        <ul class="text-xs space-y-1">
                            @foreach($warnings['linkedin'] as $warning)
                                <li class="flex items-center gap-x-1 text-yellow-600 dark:text-yellow-400">
                                    <x-ui.icon name="exclamation-triangle" class="size-3" />
                                    {{ $warning }}
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            <!-- Raw OG/Twitter Tags (collapsible) -->
            <div x-data="{ showRaw: false }" class="border-t border-gray-200 dark:border-gray-700">
                <button
                    type="button"
                    @click="showRaw = !showRaw"
                    class="flex w-full items-center justify-between px-4 py-2 text-left text-xs text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700/50"
                >
                    <span>View Raw Tags</span>
                    <x-ui.icon name="chevron-down" class="size-4 transition-transform" x-bind:class="showRaw && 'rotate-180'" />
                </button>
                <div x-show="showRaw" x-collapse class="px-4 pb-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <h5 class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">Open Graph Tags</h5>
                            <dl class="space-y-1 text-xs font-mono">
                                <div><dt class="text-gray-400 inline">og:title:</dt> <dd class="text-gray-700 dark:text-gray-300 inline">{{ $meta['og']['title'] ?: '(not set)' }}</dd></div>
                                <div><dt class="text-gray-400 inline">og:description:</dt> <dd class="text-gray-700 dark:text-gray-300 inline">{{ Str::limit($meta['og']['description'], 50) ?: '(not set)' }}</dd></div>
                                <div><dt class="text-gray-400 inline">og:image:</dt> <dd class="text-gray-700 dark:text-gray-300 inline">{{ $meta['og']['image'] ? 'Set' : '(not set)' }}</dd></div>
                                <div><dt class="text-gray-400 inline">og:type:</dt> <dd class="text-gray-700 dark:text-gray-300 inline">{{ $meta['og']['type'] ?: '(not set)' }}</dd></div>
                                <div><dt class="text-gray-400 inline">og:site_name:</dt> <dd class="text-gray-700 dark:text-gray-300 inline">{{ $meta['og']['site_name'] ?: '(not set)' }}</dd></div>
                            </dl>
                        </div>
                        <div>
                            <h5 class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">Twitter Card Tags</h5>
                            <dl class="space-y-1 text-xs font-mono">
                                <div><dt class="text-gray-400 inline">twitter:card:</dt> <dd class="text-gray-700 dark:text-gray-300 inline">{{ $meta['twitter']['card'] ?: '(not set)' }}</dd></div>
                                <div><dt class="text-gray-400 inline">twitter:title:</dt> <dd class="text-gray-700 dark:text-gray-300 inline">{{ $meta['twitter']['title'] ?: '(not set)' }}</dd></div>
                                <div><dt class="text-gray-400 inline">twitter:description:</dt> <dd class="text-gray-700 dark:text-gray-300 inline">{{ Str::limit($meta['twitter']['description'], 50) ?: '(not set)' }}</dd></div>
                                <div><dt class="text-gray-400 inline">twitter:image:</dt> <dd class="text-gray-700 dark:text-gray-300 inline">{{ $meta['twitter']['image'] ? 'Set' : '(not set)' }}</dd></div>
                                <div><dt class="text-gray-400 inline">twitter:site:</dt> <dd class="text-gray-700 dark:text-gray-300 inline">{{ $meta['twitter']['site'] ?: '(not set)' }}</dd></div>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
