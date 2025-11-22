@props(['heading', 'description' => null])

<div {{ $attributes->class('flex flex-wrap items-center justify-between gap-4') }}>
    <div>
        <flux:heading size="xl">{{ $heading }}</flux:heading>

        @if ($description)
            <flux:text class="text-sm text-slate-500 dark:text-slate-400">
                {{ $description }}
            </flux:text>
        @endif
    </div>

    @isset($slot)
        @if ($slot->isNotEmpty())
            <div class="flex items-center gap-3">
                {{ $slot }}
            </div>
        @endif
    @endisset
</div>

