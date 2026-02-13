<div>
    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-2">
            <a href="{{ route('api.webhooks') }}" class="hover:text-indigo-600">Webhooks</a>
            <span>/</span>
            <span>Deliveries</span>
        </div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
            @if ($endpoint)
                Deliveries for {{ Str::limit($endpoint->url, 50) }}
            @else
                All Webhook Deliveries
            @endif
        </h2>
    </div>

    {{-- Filters --}}
    <div class="mb-4 flex items-center gap-4">
        <select wire:model.live="statusFilter" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="all">All Status</option>
            <option value="pending">Pending</option>
            <option value="success">Success</option>
            <option value="failed">Failed</option>
        </select>
    </div>

    {{-- Deliveries List --}}
    <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Event</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Endpoint</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Response</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Attempts</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Time</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($deliveries as $delivery)
                    <tr>
                        <td class="whitespace-nowrap px-6 py-4">
                            <span class="inline-flex rounded-full bg-indigo-100 dark:bg-indigo-900 px-2 py-0.5 text-xs font-medium text-indigo-800 dark:text-indigo-200">
                                {{ $delivery->event }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                            <div class="max-w-xs truncate">{{ $delivery->endpoint->url ?? 'N/A' }}</div>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            @if ($delivery->status === 'success')
                                <span class="inline-flex items-center rounded-full bg-green-100 dark:bg-green-900 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:text-green-200">
                                    Success
                                </span>
                            @elseif ($delivery->status === 'pending')
                                <span class="inline-flex items-center rounded-full bg-yellow-100 dark:bg-yellow-900 px-2.5 py-0.5 text-xs font-medium text-yellow-800 dark:text-yellow-200">
                                    Pending
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-red-100 dark:bg-red-900 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:text-red-200">
                                    Failed
                                </span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                            @if ($delivery->response_status)
                                <span class="{{ $delivery->response_status >= 200 && $delivery->response_status < 300 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $delivery->response_status }}
                                </span>
                            @else
                                -
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                            {{ $delivery->attempts }} / 5
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                            {{ $delivery->created_at->diffForHumans() }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                            <button wire:click="viewDetails({{ $delivery->id }})" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 mr-2">
                                View
                            </button>
                            @if ($delivery->status !== 'success' && $delivery->attempts < 5)
                                <button wire:click="retryDelivery({{ $delivery->id }})" class="text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300">
                                    Retry
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                            No webhook deliveries found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $deliveries->links() }}
    </div>

    {{-- Details Modal --}}
    @if ($selectedDelivery)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeDetails"></div>
                <div class="inline-block transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 px-4 pb-4 pt-5 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl sm:p-6 sm:align-middle">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Delivery Details</h3>
                        <button wire:click="closeDetails" class="text-gray-400 hover:text-gray-500">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Event</p>
                                <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $selectedDelivery['event'] }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</p>
                                <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ ucfirst($selectedDelivery['status']) }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Response Status</p>
                                <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $selectedDelivery['response_status'] ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Attempts</p>
                                <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $selectedDelivery['attempts'] }} / 5</p>
                            </div>
                        </div>

                        <div>
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-2">Payload</p>
                            <pre class="rounded bg-gray-100 dark:bg-gray-900 p-3 text-xs overflow-x-auto max-h-48">{{ json_encode($selectedDelivery['payload'], JSON_PRETTY_PRINT) }}</pre>
                        </div>

                        @if ($selectedDelivery['response_body'])
                            <div>
                                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-2">Response Body</p>
                                <pre class="rounded bg-gray-100 dark:bg-gray-900 p-3 text-xs overflow-x-auto max-h-48">{{ $selectedDelivery['response_body'] }}</pre>
                            </div>
                        @endif
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button wire:click="closeDetails" class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
