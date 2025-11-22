@props([
    'title',
    'subtitle' => null,
    'meta' => null,
    'kicker' => null,
    'align' => 'center',
])

@php
    $alignment = [
        'center' => 'text-center',
        'left' => 'text-left',
    ][$align] ?? 'text-center';

    $kickerText = $kicker ?? config('app.name');
@endphp

<header {{ $attributes->class('mx-auto max-w-5xl space-y-4 px-4 py-10 sm:py-14 '.$alignment) }}>
    @if ($kickerText)
        <p class="text-xs font-semibold uppercase tracking-[0.3em] text-indigo-400 dark:text-indigo-300">
            {{ $kickerText }}
        </p>
    @endif

    <h1 class="text-3xl font-bold text-slate-900 dark:text-white sm:text-4xl">
        {{ $title }}
    </h1>

    @isset($subtitle)
        <p class="text-base text-slate-600 dark:text-slate-300">
            {{ $subtitle }}
        </p>
    @endisset

    @isset($meta)
        <div class="flex flex-wrap gap-2 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400 {{ $align === 'center' ? 'justify-center' : 'justify-start' }}">
            {{ $meta }}
        </div>
    @endisset
</header>
