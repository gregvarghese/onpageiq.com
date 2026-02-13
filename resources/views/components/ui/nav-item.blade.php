@props(['href', 'active' => false])

<li>
    <a
        href="{{ $href }}"
        @class([
            'group flex gap-x-3 rounded-md p-2 text-sm font-semibold leading-6',
            'bg-gray-100 dark:bg-gray-800 text-primary-600 dark:text-primary-400' => $active,
            'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-primary-600 dark:hover:text-primary-400' => !$active,
        ])
    >
        {{ $slot }}
    </a>
</li>
