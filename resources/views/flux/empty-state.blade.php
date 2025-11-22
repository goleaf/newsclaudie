@props(['heading' => null, 'description' => null])

<div {{ $attributes->class('rounded-2xl border border-dashed border-slate-200 bg-white/70 px-6 py-10 text-center dark:border-slate-800 dark:bg-slate-900/50') }}>
    @if ($heading)
        <flux:heading size="md">{{ $heading }}</flux:heading>
    @endif

    @if ($description)
        <flux:text class="mt-2 text-sm text-slate-500 dark:text-slate-400">
            {{ $description }}
        </flux:text>
    @endif

    @isset($slot)
        @if ($slot->isNotEmpty())
            <div class="mt-4">
                {{ $slot }}
            </div>
        @endif
    @endisset
</div>

