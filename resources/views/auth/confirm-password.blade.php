<x-layouts.guest>
    <x-slot:title>Confirm password - OnPageIQ</x-slot:title>
    <x-slot:heading>Confirm your password</x-slot:heading>
    <x-slot:subheading>Please confirm your password before continuing</x-slot:subheading>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-6">
        @csrf

        <!-- Password -->
        <div>
            <label for="password" class="block text-sm font-medium leading-6 text-gray-900 dark:text-white">
                Password
            </label>
            <div class="mt-2">
                <input
                    id="password"
                    name="password"
                    type="password"
                    autocomplete="current-password"
                    required
                    class="block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-white shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-700 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:focus:ring-primary-500 sm:text-sm sm:leading-6 bg-white dark:bg-gray-800"
                />
            </div>
            @error('password')
                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <!-- Submit -->
        <div>
            <button
                type="submit"
                class="flex w-full justify-center rounded-md bg-primary-600 px-3 py-1.5 text-sm font-semibold leading-6 text-white shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"
            >
                Confirm password
            </button>
        </div>
    </form>
</x-layouts.guest>
