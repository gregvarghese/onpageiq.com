<div>
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Subscription Plans</h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Choose the plan that best fits your needs.
        </p>
    </div>

    <div class="grid gap-6 lg:grid-cols-4 md:grid-cols-2">
        @foreach ($tiers as $slug => $tier)
            <div class="relative rounded-lg border {{ $currentTier === $slug ? 'border-indigo-500 ring-2 ring-indigo-500' : 'border-gray-200 dark:border-gray-700' }} bg-white dark:bg-gray-800 p-6 shadow-sm">
                @if ($currentTier === $slug)
                    <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                        <span class="inline-flex items-center rounded-full bg-indigo-100 dark:bg-indigo-900 px-3 py-0.5 text-sm font-medium text-indigo-800 dark:text-indigo-200">
                            Current Plan
                        </span>
                    </div>
                @endif

                <div class="text-center">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $tier['name'] }}</h3>
                    <div class="mt-4">
                        @if ($tier['price_monthly'] === null)
                            <span class="text-4xl font-bold text-gray-900 dark:text-white">Custom</span>
                        @elseif ($tier['price_monthly'] === 0)
                            <span class="text-4xl font-bold text-gray-900 dark:text-white">Free</span>
                        @else
                            <span class="text-4xl font-bold text-gray-900 dark:text-white">${{ number_format($tier['price_monthly'] / 100, 0) }}</span>
                            <span class="text-gray-500 dark:text-gray-400">/month</span>
                        @endif
                    </div>
                </div>

                <ul class="mt-6 space-y-3">
                    <li class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                        <svg class="mr-2 h-4 w-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        {{ $tier['credits_monthly'] ?? ($tier['credits_onetime'] ?? 0) }} credits{{ $tier['credits_monthly'] ? '/month' : ' one-time' }}
                    </li>
                    <li class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                        <svg class="mr-2 h-4 w-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        {{ $tier['projects_limit'] ?? 'Unlimited' }} project{{ ($tier['projects_limit'] ?? 0) !== 1 ? 's' : '' }}
                    </li>
                    <li class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                        <svg class="mr-2 h-4 w-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        {{ $tier['team_size'] ?? 'Unlimited' }} team member{{ ($tier['team_size'] ?? 0) !== 1 ? 's' : '' }}
                    </li>
                    <li class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                        <svg class="mr-2 h-4 w-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        {{ implode(', ', array_map('ucfirst', $tier['checks'])) }}
                    </li>
                    @if ($tier['features']['pdf_export'])
                        <li class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                            <svg class="mr-2 h-4 w-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            PDF Export
                        </li>
                    @endif
                    @if ($tier['features']['api_access'])
                        <li class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                            <svg class="mr-2 h-4 w-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            API Access
                        </li>
                    @endif
                </ul>

                <div class="mt-6">
                    @if ($currentTier === $slug)
                        <button disabled class="w-full rounded-md bg-gray-100 dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-400 dark:text-gray-500 cursor-not-allowed">
                            Current Plan
                        </button>
                    @elseif ($slug === 'enterprise')
                        <a href="mailto:sales@onpageiq.com" class="block w-full rounded-md bg-gray-900 dark:bg-white px-4 py-2 text-center text-sm font-medium text-white dark:text-gray-900 hover:bg-gray-800 dark:hover:bg-gray-100">
                            Contact Sales
                        </a>
                    @else
                        <button wire:click="selectTier('{{ $slug }}')" class="w-full rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                            {{ array_search($currentTier, array_keys($tiers)) < array_search($slug, array_keys($tiers)) ? 'Upgrade' : 'Downgrade' }}
                        </button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    @error('upgrade')
        <div class="mt-4 rounded-md bg-red-50 dark:bg-red-900/50 p-4">
            <p class="text-sm text-red-700 dark:text-red-200">{{ $message }}</p>
        </div>
    @enderror

    @error('downgrade')
        <div class="mt-4 rounded-md bg-red-50 dark:bg-red-900/50 p-4">
            <p class="text-sm text-red-700 dark:text-red-200">{{ $message }}</p>
        </div>
    @enderror

    {{-- Upgrade Modal --}}
    @if ($showUpgradeModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="cancelModal"></div>
                <div class="inline-block transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 px-4 pb-4 pt-5 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6 sm:align-middle">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Confirm Upgrade</h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        You're about to upgrade to the {{ $tiers[$selectedTier]['name'] ?? '' }} plan. You will be redirected to complete the payment.
                    </p>
                    <div class="mt-4 flex justify-end gap-3">
                        <button wire:click="cancelModal" class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600">
                            Cancel
                        </button>
                        <button wire:click="confirmUpgrade" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                            Continue to Payment
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Downgrade Modal --}}
    @if ($showDowngradeModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="cancelModal"></div>
                <div class="inline-block transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 px-4 pb-4 pt-5 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6 sm:align-middle">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Confirm Downgrade</h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Are you sure you want to downgrade to the {{ $tiers[$selectedTier]['name'] ?? '' }} plan? Some features may no longer be available.
                    </p>
                    <div class="mt-4 flex justify-end gap-3">
                        <button wire:click="cancelModal" class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600">
                            Cancel
                        </button>
                        <button wire:click="confirmDowngrade" class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">
                            Confirm Downgrade
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
