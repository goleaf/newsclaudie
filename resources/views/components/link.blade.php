@props([
    'variant' => 'primary',
])

@php
    $variants = [
        'primary' => 'inline-flex items-center gap-1 text-sm font-semibold text-indigo-600 underline-offset-4 decoration-transparent transition-colors hover:text-indigo-500 hover:decoration-current focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400 dark:text-indigo-300 dark:hover:text-indigo-200',
        'muted' => 'inline-flex items-center gap-1 text-sm font-medium text-slate-600 underline-offset-4 decoration-transparent transition-colors hover:text-slate-900 hover:decoration-current focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400 dark:text-slate-300 dark:hover:text-white',
    ];
@endphp

<a {{ $attributes->class($variants[$variant] ?? $variants['primary']) }}>
    {{ $slot }}
</a>