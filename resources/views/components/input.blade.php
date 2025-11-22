@props([
    'disabled' => false,
    'invalid' => false,
])

@php
    $baseClasses = 'block w-full rounded-2xl border border-slate-200 bg-white/80 px-4 py-2 text-sm text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:ring-offset-0 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400 dark:border-slate-700 dark:bg-slate-900/70 dark:text-white dark:placeholder:text-slate-500 dark:focus:border-indigo-400 dark:focus:ring-indigo-400/40';
    $invalidClasses = 'border-rose-400 ring-2 ring-rose-100 focus:border-rose-500 focus:ring-rose-200 dark:border-rose-500 dark:ring-rose-500/30 dark:focus:border-rose-400 dark:focus:ring-rose-500/40';
    $classes = $invalid ? "{$baseClasses} {$invalidClasses}" : $baseClasses;
@endphp

<input
    {{ $disabled ? 'disabled' : '' }}
    data-invalid-styles="{{ $invalidClasses }}"
    @if ($invalid) aria-invalid="true" @endif
    {{ $attributes->class($classes) }}
>
