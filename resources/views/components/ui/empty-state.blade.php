@props([
    'title' => __('Nothing to see here yet'),
    'description' => null,
])

<div {{ $attributes->class('rounded-3xl border border-dashed border-slate-300/80 bg-white/70 px-6 py-16 text-center shadow-sm dark:border-slate-700/80 dark:bg-slate-900/70') }}>
    <h2 class="text-2xl font-semibold text-slate-800 dark:text-white">
        {{ $title }}
    </h2>
    @isset($description)
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
            {{ $description }}
        </p>
    @endisset

    @if ($slot->isNotEmpty())
        <div class="mt-6 flex flex-wrap justify-center gap-3 text-sm">
            {{ $slot }}
        </div>
    @endif
</div>

