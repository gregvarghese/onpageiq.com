<div>
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Billing History</h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            View your credit transactions and usage history.
        </p>
    </div>

    {{-- Usage Stats --}}
    <div class="mb-8 grid gap-4 md:grid-cols-4">
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Current Balance</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($usageStats['current_balance']) }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Credits Added (30d)</p>
            <p class="mt-1 text-2xl font-bold text-green-600 dark:text-green-400">+{{ number_format($usageStats['credits_added']) }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Credits Used (30d)</p>
            <p class="mt-1 text-2xl font-bold text-red-600 dark:text-red-400">-{{ number_format($usageStats['credits_used']) }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Transactions (30d)</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($usageStats['transaction_count']) }}</p>
        </div>
    </div>

    {{-- Filter --}}
    <div class="mb-4">
        <select wire:model.live="filter" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="all">All Transactions</option>
            <option value="subscription_credit">Subscription Credits</option>
            <option value="purchase">Purchases</option>
            <option value="usage">Usage</option>
            <option value="refund">Refunds</option>
            <option value="bonus">Bonus</option>
            <option value="adjustment">Adjustments</option>
        </select>
    </div>

    {{-- Transaction Table --}}
    <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Date</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Type</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Description</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">User</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Amount</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Balance</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($transactions as $transaction)
                    <tr>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                            {{ $transaction->created_at->format('M j, Y g:i A') }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            @php
                                $typeColors = [
                                    'subscription_credit' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                    'purchase' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                    'usage' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
                                    'refund' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                    'bonus' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
                                    'adjustment' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
                                ];
                            @endphp
                            <span class="inline-flex rounded-full px-2 text-xs font-semibold leading-5 {{ $typeColors[$transaction->type] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ str_replace('_', ' ', ucfirst($transaction->type)) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                            {{ $transaction->description ?? '-' }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                            {{ $transaction->user?->name ?? 'System' }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium {{ $transaction->amount > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $transaction->amount > 0 ? '+' : '' }}{{ number_format($transaction->amount) }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-900 dark:text-white">
                            {{ number_format($transaction->balance_after) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                            No transactions found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="mt-4">
        {{ $transactions->links() }}
    </div>
</div>
