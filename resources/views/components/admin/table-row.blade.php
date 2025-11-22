@props([
    'interactive' => false,
])

@php
    $baseClasses = 'bg-white/80 text-slate-900 backdrop-blur dark:bg-slate-900/60 dark:text-slate-100';
    $hoverClasses = $interactive ? 'hover:bg-slate-50/70 dark:hover:bg-slate-900/70' : '';
@endphp

<tr {{ $attributes->class(trim("{$baseClasses} {$hoverClasses}")) }}>
    {{ $slot }}
</tr>

