@props([
    'variant' => 'primary',
    'href' => null,
    'as' => null,
    'type' => null,
])

@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-full px-4 py-2 text-sm font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-indigo-500 disabled:opacity-60 disabled:pointer-events-none';

    $variants = [
        'primary' => 'bg-indigo-600 text-white shadow-sm hover:bg-indigo-500 dark:bg-indigo-500 dark:hover:bg-indigo-400',
        'secondary' => 'border border-slate-200 text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800',
        'ghost' => 'text-slate-600 hover:text-indigo-600 dark:text-slate-300 dark:hover:text-indigo-300',
        'danger' => 'bg-rose-600 text-white hover:bg-rose-500',
    ];

    $componentTag = $href ? 'a' : ($as ?? 'button');
    $attributes = $attributes->class($base . ' ' . ($variants[$variant] ?? $variants['primary']));
@endphp

<{{ $componentTag }}
    @if ($href) href="{{ $href }}" @endif
    @if ($componentTag === 'button')
        type="{{ $type ?? 'button' }}"
    @endif
    {{ $attributes }}
>
    {{ $slot }}
</{{ $componentTag }}>
