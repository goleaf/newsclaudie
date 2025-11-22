@props([
    'interactive' => false,
])

@php
    $baseClasses = 'bg-white/80 text-slate-900 backdrop-blur dark:bg-slate-900/60 dark:text-slate-100';
    $hoverClasses = $interactive ? 'hover:bg-slate-50/70 dark:hover:bg-slate-900/70' : '';

    $tabIndex = $attributes->get('tabindex', '-1');
    $ariaSelected = $attributes->get('aria-selected', 'false');
@endphp

<tr
    {{ $attributes
        ->class(trim("{$baseClasses} {$hoverClasses}"))
        ->merge([
            'data-admin-row' => 'true',
            'tabindex' => $tabIndex,
            'aria-selected' => $ariaSelected,
            'role' => 'row',
        ]) }}
>
    {{ $slot }}
</tr>


