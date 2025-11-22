@props([
    'variant' => 'info',
    'title' => null,
    'icon' => null,
    'compact' => false,
    'size' => 'md',
])

@php
    $variants = [
        'success' => 'border-emerald-200/70 bg-emerald-50/80 text-emerald-900 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-100',
        'info' => 'border-indigo-200/70 bg-indigo-50/80 text-indigo-900 dark:border-indigo-400/40 dark:bg-indigo-500/10 dark:text-indigo-100',
        'warning' => 'border-amber-200/80 bg-amber-50/80 text-amber-900 dark:border-amber-400/40 dark:bg-amber-500/10 dark:text-amber-100',
        'danger' => 'border-rose-200/80 bg-rose-50/80 text-rose-900 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-100',
        'neutral' => 'border-slate-200/80 bg-white/80 text-slate-700 dark:border-slate-800/70 dark:bg-slate-900/60 dark:text-slate-200',
    ];

    $resolvedSize = $compact ? 'sm' : $size;
    $padding = $resolvedSize === 'sm' ? 'px-4 py-3' : 'px-5 py-4';
    $classes = trim('rounded-3xl border text-sm shadow-sm '.$padding.' '.($variants[$variant] ?? $variants['info']));
@endphp

<div {{ $attributes->class($classes) }}>
    <div class="flex gap-3">
        @isset($icon)
            <span class="text-lg text-current">{{ $icon }}</span>
        @endisset

        <div class="space-y-2">
            @isset($title)
                <p class="text-base font-semibold leading-tight">{{ $title }}</p>
            @endisset

            <div @class(['text-slate-600 dark:text-slate-300' => ! $title])>
                {{ $slot }}
            </div>
        </div>
    </div>
</div>

