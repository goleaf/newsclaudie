@props([
    'title' => null,
    'subtitle' => null,
    'actions' => null,
    'variant' => 'default',
    'padding' => 'p-6',
    'bodyClass' => 'space-y-3 text-sm text-slate-600 dark:text-slate-200',
])

@php
    $variants = [
        'default' => 'rounded-3xl border border-slate-200/80 bg-white/80 shadow-sm ring-1 ring-slate-100/60 dark:border-slate-800/80 dark:bg-slate-900/70 dark:ring-white/5',
        'flat' => 'rounded-2xl border border-slate-200/70 bg-white/70 shadow-sm dark:border-slate-800/60 dark:bg-slate-900/60',
    ];

    $containerClasses = trim(($variants[$variant] ?? $variants['default']).' '.$padding);
@endphp

<div {{ $attributes->class('flex h-full flex-col '.$containerClasses) }}>
    @if ($title || $subtitle || $actions)
        <div class="mb-4 flex flex-wrap items-start justify-between gap-4">
            <div>
                @isset($title)
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ $title }}</h3>
                @endisset
                @isset($subtitle)
                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ $subtitle }}</p>
                @endisset
            </div>
            @isset($actions)
                <div class="flex items-center gap-2">
                    {{ $actions }}
                </div>
            @endisset
        </div>
    @endif

    <div class="{{ trim('flex-1 '.$bodyClass) }}">
        {{ $slot }}
    </div>
</div>
