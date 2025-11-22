@props([
    'variant' => 'default',
    'size' => 'md',
    'uppercase' => true,
])

@php
    $variantClasses = [
        'default' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200',
        'info' => 'bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-200',
        'success' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-200',
        'warning' => 'bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-200',
        'danger' => 'bg-rose-100 text-rose-800 dark:bg-rose-500/20 dark:text-rose-200',
        'ghost' => 'bg-white/20 text-white ring-1 ring-white/30 dark:bg-white/10',
    ];

    $sizes = [
        'md' => 'px-3 py-1 text-[11px]',
        'sm' => 'px-2.5 py-0.5 text-[10px]',
    ];

    $textCase = $uppercase ? 'font-semibold uppercase tracking-wide' : 'font-medium';
@endphp

<span {{ $attributes->class('inline-flex items-center rounded-full '.$textCase.' '.($sizes[$size] ?? $sizes['md']).' '.($variantClasses[$variant] ?? $variantClasses['default'])) }}>
    {{ $slot }}
</span>
