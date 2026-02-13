<div>
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Buy Credits</h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Purchase additional credits for your scans. Credits never expire.
        </p>
    </div>

    <div class="mb-6 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Current Balance</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($organization->credit_balance) }} credits</p>
            </div>
        </div>
    </div>

    <div class="grid gap-6 md:grid-cols-3">
        @foreach ($packs as $slug => $pack)
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm hover:border-indigo-500 transition-colors">
                <div class="text-center">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $pack['name'] }}</h3>
                    <div class="mt-4">
                        <span class="text-4xl font-bold text-gray-900 dark:text-white">${{ number_format($pack['price'] / 100, 2) }}</span>
                    </div>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        ${{ number_format($pack['price'] / $pack['credits'] / 100, 3) }} per credit
                    </p>
                </div>

                <div class="mt-6">
                    <button
                        wire:click="selectPack('{{ $slug }}')"
                        class="w-full rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                    >
                        Buy {{ $pack['credits'] }} Credits
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    @error('purchase')
        <div class="mt-4 rounded-md bg-red-50 dark:bg-red-900/50 p-4">
            <p class="text-sm text-red-700 dark:text-red-200">{{ $message }}</p>
        </div>
    @enderror

    {{-- Confirm Modal --}}
    @if ($showConfirmModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="cancelModal"></div>
                <div class="inline-block transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 px-4 pb-4 pt-5 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6 sm:align-middle">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Confirm Purchase</h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        You're about to purchase {{ $packs[$selectedPack]['credits'] ?? 0 }} credits for ${{ number_format(($packs[$selectedPack]['price'] ?? 0) / 100, 2) }}.
                    </p>
                    <div class="mt-4 flex justify-end gap-3">
                        <button wire:click="cancelModal" class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600">
                            Cancel
                        </button>
                        <button wire:click="confirmPurchase" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                            Continue to Payment
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
