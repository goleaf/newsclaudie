@props(['target' => null, 'delay' => false])

<div 
    {{ $attributes->merge(['class' => 'fixed inset-0 z-50 flex items-center justify-center bg-slate-900/20 backdrop-blur-sm']) }}
    @if($target)
        wire:loading{{ $delay ? '.delay.500ms' : '' }}
        wire:target="{{ $target }}"
    @else
        wire:loading{{ $delay ? '.delay.500ms' : '' }}
    @endif
>
    <div class="rounded-2xl bg-white p-6 shadow-2xl dark:bg-slate-800">
        <div class="flex items-center gap-4">
            <svg class="h-8 w-8 animate-spin text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <div class="text-left">
                <p class="font-semibold text-slate-900 dark:text-slate-100">{{ $slot->isEmpty() ? __('admin.processing') : $slot }}</p>
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Please wait...') }}</p>
            </div>
        </div>
    </div>
</div>
