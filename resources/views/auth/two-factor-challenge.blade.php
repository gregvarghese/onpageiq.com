<x-layouts.guest>
    <x-slot:title>Two-factor authentication - OnPageIQ</x-slot:title>
    <x-slot:heading>Two-factor authentication</x-slot:heading>

    <div x-data="{ recovery: false }">
        <div x-show="!recovery" class="space-y-6">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Please enter the authentication code from your authenticator app.
            </p>

            <form method="POST" action="{{ route('two-factor.login') }}" class="space-y-6">
                @csrf

                <div>
                    <label for="code" class="block text-sm font-medium leading-6 text-gray-900 dark:text-white">
                        Authentication code
                    </label>
                    <div class="mt-2">
                        <input
                            id="code"
                            name="code"
                            type="text"
                            inputmode="numeric"
                            autocomplete="one-time-code"
                            required
                            autofocus
                            class="block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-white shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-700 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:focus:ring-primary-500 sm:text-sm sm:leading-6 bg-white dark:bg-gray-800"
                        />
                    </div>
                    @error('code')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <button
                        type="submit"
                        class="flex w-full justify-center rounded-md bg-primary-600 px-3 py-1.5 text-sm font-semibold leading-6 text-white shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"
                    >
                        Verify
                    </button>
                </div>
            </form>
        </div>

        <div x-show="recovery" x-cloak class="space-y-6">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Please enter one of your emergency recovery codes.
            </p>

            <form method="POST" action="{{ route('two-factor.login') }}" class="space-y-6">
                @csrf

                <div>
                    <label for="recovery_code" class="block text-sm font-medium leading-6 text-gray-900 dark:text-white">
                        Recovery code
                    </label>
                    <div class="mt-2">
                        <input
                            id="recovery_code"
                            name="recovery_code"
                            type="text"
                            autocomplete="one-time-code"
                            required
                            class="block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-white shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-700 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:focus:ring-primary-500 sm:text-sm sm:leading-6 bg-white dark:bg-gray-800"
                        />
                    </div>
                    @error('recovery_code')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <button
                        type="submit"
                        class="flex w-full justify-center rounded-md bg-primary-600 px-3 py-1.5 text-sm font-semibold leading-6 text-white shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"
                    >
                        Verify
                    </button>
                </div>
            </form>
        </div>

        <div class="mt-4 text-center">
            <button
                type="button"
                @click="recovery = !recovery"
                class="text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300"
            >
                <span x-show="!recovery">Use a recovery code</span>
                <span x-show="recovery" x-cloak>Use an authentication code</span>
            </button>
        </div>
    </div>
</x-layouts.guest>
