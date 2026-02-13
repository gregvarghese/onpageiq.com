<x-layouts.guest>
    <x-slot:title>Sign in - OnPageIQ</x-slot:title>
    <x-slot:heading>Sign in to your account</x-slot:heading>

    <form method="POST" action="{{ route('login') }}" class="space-y-6">
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
                    class="block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-white shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-700 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:focus:ring-primary-500 sm:text-sm sm:leading-6 bg-white dark:bg-gray-800"
                />
            </div>
            @error('email')
                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

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

        <!-- Remember me & Forgot password -->
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <input
                    id="remember"
                    name="remember"
                    type="checkbox"
                    class="size-4 rounded border-gray-300 dark:border-gray-700 text-primary-600 focus:ring-primary-600 dark:bg-gray-800"
                />
                <label for="remember" class="ml-3 block text-sm leading-6 text-gray-700 dark:text-gray-300">
                    Remember me
                </label>
            </div>

            <div class="text-sm leading-6">
                <a href="{{ route('password.request') }}" class="font-semibold text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300">
                    Forgot password?
                </a>
            </div>
        </div>

        <!-- Submit -->
        <div>
            <button
                type="submit"
                class="flex w-full justify-center rounded-md bg-primary-600 px-3 py-1.5 text-sm font-semibold leading-6 text-white shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"
            >
                Sign in
            </button>
        </div>
    </form>

    <!-- Social login divider -->
    <div class="mt-6">
        <div class="relative">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-gray-200 dark:border-gray-700"></div>
            </div>
            <div class="relative flex justify-center text-sm font-medium leading-6">
                <span class="bg-white dark:bg-gray-900 px-6 text-gray-500 dark:text-gray-400">Or continue with</span>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-2 gap-4">
            <a
                href="{{ route('auth.google') }}"
                class="flex w-full items-center justify-center gap-3 rounded-md bg-white dark:bg-gray-800 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-white shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700"
            >
                <svg class="size-5" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
                <span>Google</span>
            </a>

            <a
                href="{{ route('auth.microsoft') }}"
                class="flex w-full items-center justify-center gap-3 rounded-md bg-white dark:bg-gray-800 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-white shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700"
            >
                <svg class="size-5" viewBox="0 0 23 23">
                    <path fill="#f35325" d="M1 1h10v10H1z"/>
                    <path fill="#81bc06" d="M12 1h10v10H12z"/>
                    <path fill="#05a6f0" d="M1 12h10v10H1z"/>
                    <path fill="#ffba08" d="M12 12h10v10H12z"/>
                </svg>
                <span>Microsoft</span>
            </a>
        </div>
    </div>

    <x-slot:footer>
        <p class="text-center text-sm text-gray-500 dark:text-gray-400">
            Not a member?
            <a href="{{ route('register') }}" class="font-semibold leading-6 text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300">
                Create an account
            </a>
        </p>
    </x-slot:footer>
</x-layouts.guest>
