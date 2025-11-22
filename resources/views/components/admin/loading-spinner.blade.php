@props([
    'size' => 'md',
    'color' => 'indigo',
])

@php
    $sizes = [
        'xs' => 'h-3 w-3',
        'sm' => 'h-4 w-4',
        'md' => 'h-5 w-5',
        'lg' => 'h-6 w-6',
        'xl' => 'h-8 w-8',
    ];

    $colors = [
        'indigo' => 'text-indigo-600',
        'slate' => 'text-slate-600',
        'white' => 'text-white',
        'green' => 'text-green-600',
        'amber' => 'text-amber-600',
        'red' => 'text-red-600',
    ];

    $sizeClass = $sizes[$size] ?? $sizes['md'];
    $colorClass = $colors[$color] ?? $colors['indigo'];
@endphp

<svg
    {{ $attributes->class(['animate-spin', $sizeClass, $colorClass]) }}
    xmlns="http://www.w3.org/2000/svg"
    fill="none"
    viewBox="0 0 24 24"
    role="status"
    aria-label="{{ __('admin.loading') }}"
>
    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
</svg>
