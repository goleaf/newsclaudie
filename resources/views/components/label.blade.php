@props([
    'value' => null,
])

<label {{ $attributes->class('block text-sm font-semibold text-slate-700 dark:text-slate-200') }}>
    {{ $value ?? $slot }}
</label>
