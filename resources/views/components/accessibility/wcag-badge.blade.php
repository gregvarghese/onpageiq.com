@props([
    'level',
    'size' => 'md',
])

@php
    $levelValue = $level instanceof \App\Enums\WcagLevel ? $level->value : $level;

    $sizeClasses = match($size) {
        'sm' => 'px-1.5 py-0.5 text-xs',
        'lg' => 'px-3 py-1.5 text-sm',
        default => 'px-2 py-1 text-xs',
    };

    $colorClasses = match($levelValue) {
        'A' => 'bg-blue-100 text-blue-700 ring-blue-700/20 dark:bg-blue-900/30 dark:text-blue-300 dark:ring-blue-400/30',
        'AA' => 'bg-green-100 text-green-700 ring-green-700/20 dark:bg-green-900/30 dark:text-green-300 dark:ring-green-400/30',
        'AAA' => 'bg-purple-100 text-purple-700 ring-purple-700/20 dark:bg-purple-900/30 dark:text-purple-300 dark:ring-purple-400/30',
        default => 'bg-gray-100 text-gray-700 ring-gray-700/20 dark:bg-gray-700 dark:text-gray-300 dark:ring-gray-400/30',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-md font-medium ring-1 ring-inset {$sizeClasses} {$colorClasses}"]) }}>
    WCAG {{ $levelValue }}
</span>
