@props([
    'tag' => 'div',
])

@php
    $tagName = $tag ?? 'div';
@endphp

<{{ $tagName }} {{ $attributes->class('rounded-3xl border border-slate-200/80 bg-white/80 p-6 shadow-sm dark:border-slate-800/70 dark:bg-slate-900/60') }}>
    {{ $slot }}
</{{ $tagName }}>




