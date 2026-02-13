<div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Credit Balance</p>
            <p class="mt-1 text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($balance) }}</p>
        </div>
        <div class="rounded-full bg-indigo-100 dark:bg-indigo-900 p-3">
            <svg class="h-6 w-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
    </div>

    @if ($overdraft > 0)
        <div class="mt-4 rounded-md bg-amber-50 dark:bg-amber-900/50 p-3">
            <div class="flex">
                <svg class="h-5 w-5 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <div class="ml-3">
                    <p class="text-sm text-amber-700 dark:text-amber-200">
                        Overdraft: {{ number_format($overdraft) }} credits
                    </p>
                </div>
            </div>
        </div>
    @endif

    <div class="mt-4">
        <a href="{{ route('billing.credits') }}" class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-500">
            Buy more credits &rarr;
        </a>
    </div>
</div>
