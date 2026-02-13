<div>
    @if ($show)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                {{-- Background overlay --}}
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="cancel"></div>

                {{-- Modal panel --}}
                <div class="inline-block transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left align-bottom shadow-xl transition-all dark:bg-gray-800 sm:my-8 sm:w-full sm:max-w-lg sm:p-6 sm:align-middle">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-yellow-100 dark:bg-yellow-900 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-yellow-600 dark:text-yellow-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                            <h3 class="text-lg font-semibold leading-6 text-gray-900 dark:text-white" id="modal-title">
                                Budget Limit Exceeded
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $message }}
                                </p>
                            </div>

                            {{-- Budget details --}}
                            <div class="mt-4 rounded-lg bg-gray-50 p-4 dark:bg-gray-700">
                                <dl class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <dt class="text-gray-500 dark:text-gray-400">Monthly Limit:</dt>
                                        <dd class="font-medium text-gray-900 dark:text-white">${{ number_format($monthlyLimit ?? 0, 2) }}</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-gray-500 dark:text-gray-400">Current Usage:</dt>
                                        <dd class="font-medium text-gray-900 dark:text-white">${{ number_format($currentUsage ?? 0, 2) }}</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-gray-500 dark:text-gray-400">Usage:</dt>
                                        <dd class="font-medium text-red-600 dark:text-red-400">{{ number_format($usagePercentage ?? 0, 1) }}%</dd>
                                    </div>
                                    @if ($this->getOverageAmount() > 0)
                                        <div class="flex justify-between border-t border-gray-200 pt-2 dark:border-gray-600">
                                            <dt class="text-gray-500 dark:text-gray-400">Over Budget By:</dt>
                                            <dd class="font-medium text-red-600 dark:text-red-400">${{ number_format($this->getOverageAmount(), 2) }}</dd>
                                        </div>
                                    @endif
                                </dl>
                            </div>

                            <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                                Do you want to proceed with this AI operation anyway?
                            </p>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                        <button
                            type="button"
                            wire:click="confirm"
                            class="inline-flex w-full justify-center rounded-md bg-yellow-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-yellow-500 sm:ml-3 sm:w-auto"
                        >
                            Proceed Anyway
                        </button>
                        <button
                            type="button"
                            wire:click="cancel"
                            class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-gray-700 dark:text-white dark:ring-gray-600 dark:hover:bg-gray-600 sm:mt-0 sm:w-auto"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
