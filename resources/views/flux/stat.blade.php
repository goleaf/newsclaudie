@props(['label', 'value', 'hint' => null])

<div {{ $attributes->class('rounded-2xl border border-slate-200/80 bg-white/80 p-4 dark:border-slate-800/60 dark:bg-slate-900/60') }}>
    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
        {{ $label }}
    </p>
    <p class="mt-2 text-3xl font-bold text-slate-900 dark:text-white">
        {{ $value }}
    </p>
    @if ($hint)
        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $hint }}</p>
    @endif
</div>

