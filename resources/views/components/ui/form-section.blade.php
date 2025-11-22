@props([
    'title',
    'description' => null,
])

<section {{ $attributes->class('space-y-5') }}>
    <header class="space-y-1.5">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
            {{ $title }}
        </h2>

        @if ($description)
            <p class="text-sm text-slate-500 dark:text-slate-400">
                {{ $description }}
            </p>
        @endif
    </header>

    <div class="space-y-5">
        {{ $slot }}
    </div>
</section>

