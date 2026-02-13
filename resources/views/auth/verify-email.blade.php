<x-layouts.guest>
    <x-slot:title>Verify email - OnPageIQ</x-slot:title>
    <x-slot:heading>Verify your email</x-slot:heading>
    <x-slot:subheading>We've sent a verification link to your email address</x-slot:subheading>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 rounded-md bg-green-50 dark:bg-green-900/50 p-4">
            <p class="text-sm text-green-700 dark:text-green-300">
                A new verification link has been sent to your email address.
            </p>
        </div>
    @endif

    <div class="text-sm text-gray-600 dark:text-gray-400">
        <p>
            Before continuing, please verify your email address by clicking on the link we just emailed to you.
            If you didn't receive the email, we can send you another.
        </p>
    </div>

    <div class="mt-6 flex items-center justify-between gap-4">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button
                type="submit"
                class="rounded-md bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"
            >
                Resend verification email
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button
                type="submit"
                class="text-sm font-semibold text-gray-700 dark:text-gray-300 hover:text-gray-500 dark:hover:text-gray-100"
            >
                Sign out
            </button>
        </form>
    </div>
</x-layouts.guest>
