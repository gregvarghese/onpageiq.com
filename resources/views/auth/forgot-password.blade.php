<x-layouts.guest>
    <x-slot:title>Reset password - OnPageIQ</x-slot:title>
    <x-slot:heading>Reset your password</x-slot:heading>
    <x-slot:subheading>Enter your email and we'll send you a reset link</x-slot:subheading>

    @if (session('status'))
        <div class="mb-4 rounded-md bg-green-50 dark:bg-green-900/50 p-4">
            <p class="text-sm text-green-700 dark:text-green-300">{{ session('status') }}</p>
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-6">
        @csrf

        <!-- Email -->
        <div>
            <label for="email" class="block text-sm font-medium leading-6 text-gray-900 dark:text-white">
                Email address
            </label>
            <div class="mt-2">
                <input
                    id="email"
                    name="email"
                    type="email"
                    autocomplete="email"
                    required
                    value="{{ old('email') }}"
                    class="block w-full rounded-md border-0 px-3 py-1.5 text-gray-900 dark:text-white shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-700 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:focus:ring-primary-500 sm:text-sm sm:leading-6 bg-white dark:bg-gray-800"
                />
            </div>
            @error('email')
                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <!-- Submit -->
        <div>
            <button
                type="submit"
                class="flex w-full justify-center rounded-md bg-primary-600 px-3 py-1.5 text-sm font-semibold leading-6 text-white shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"
            >
                Send reset link
            </button>
        </div>
    </form>

    <x-slot:footer>
        <p class="text-center text-sm text-gray-500 dark:text-gray-400">
            Remember your password?
            <a href="{{ route('login') }}" class="font-semibold leading-6 text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300">
                Sign in
            </a>
        </p>
    </x-slot:footer>
</x-layouts.guest>
