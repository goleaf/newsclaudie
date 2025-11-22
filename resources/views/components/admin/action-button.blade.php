@props([
    'action' => null,
    'confirm' => null,
    'icon' => null,
    'color' => 'primary',
    'size' => 'sm',
    'loadingText' => null,
    'disabled' => false,
])

@php
    $wireTarget = $action ? "wire:target=\"{$action}\"" : '';
    $wireClick = $action ? "wire:click=\"{$action}\"" : '';
    $wireConfirm = $confirm ? "wire:confirm=\"{$confirm}\"" : '';
    $loadingDelay = 'wire:loading.delay.500ms';
@endphp

<flux:button
    {{ $attributes }}
    :color="$color"
    :size="$size"
    :icon="$icon"
    @if ($action) wire:click="{{ $action }}" @endif
    @if ($confirm) wire:confirm="{{ $confirm }}" @endif
    wire:loading.attr="disabled"
    @if ($action) wire:target="{{ $action }}" @endif
    :disabled="$disabled"
>
    <span wire:loading.remove @if ($action) wire:target="{{ $action }}" @endif>
        {{ $slot }}
    </span>
    <span wire:loading.delay.500ms @if ($action) wire:target="{{ $action }}" @endif class="inline-flex items-center gap-1.5">
        <x-admin.loading-spinner size="xs" color="white" />
        {{ $loadingText ?? $slot }}
    </span>
</flux:button>
