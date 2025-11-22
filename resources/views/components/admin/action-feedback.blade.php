@props([
    'type' => 'success', // success, error, warning, info
    'message' => null,
    'dismissible' => true,
    'autoHide' => false,
    'hideDelay' => 5000,
])

@php
    $colors = [
        'success' => 'green',
        'error' => 'red',
        'warning' => 'amber',
        'info' => 'blue',
    ];
    
    $color = $colors[$type] ?? 'green';
    $alpineData = $autoHide ? "{ show: true, init() { setTimeout(() => this.show = false, {$hideDelay}) } }" : "{ show: true }";
@endphp

@if($message)
    <div 
        x-data="{{ $alpineData }}"
        x-show="show"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 transform scale-95"
        x-transition:enter-end="opacity-100 transform scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 transform scale-100"
        x-transition:leave-end="opacity-0 transform scale-95"
        {{ $attributes->merge(['class' => 'relative']) }}
        role="alert"
        aria-live="polite"
    >
        <flux:callout color="{{ $color }}">
            <div class="flex items-start justify-between gap-3">
                <div class="flex-1">
                    {{ $message }}
                    {{ $slot }}
                </div>
                
                @if($dismissible)
                    <button
                        type="button"
                        @click="show = false"
                        class="flex-shrink-0 rounded-lg p-1 text-{{ $color }}-600 hover:bg-{{ $color }}-100 dark:text-{{ $color }}-400 dark:hover:bg-{{ $color }}-900/30"
                        aria-label="{{ __('admin.dismiss') }}"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                @endif
            </div>
        </flux:callout>
    </div>
@endif
