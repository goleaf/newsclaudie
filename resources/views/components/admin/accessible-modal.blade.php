@props([
    'show' => false,
    'title' => null,
    'maxWidth' => '2xl',
    'closeable' => true,
])

@php
    $maxWidthClasses = [
        'sm' => 'sm:max-w-sm',
        'md' => 'sm:max-w-md',
        'lg' => 'sm:max-w-lg',
        'xl' => 'sm:max-w-xl',
        '2xl' => 'sm:max-w-2xl',
        '3xl' => 'sm:max-w-3xl',
        '4xl' => 'sm:max-w-4xl',
        '5xl' => 'sm:max-w-5xl',
        '6xl' => 'sm:max-w-6xl',
        '7xl' => 'sm:max-w-7xl',
    ];
    
    $maxWidthClass = $maxWidthClasses[$maxWidth] ?? $maxWidthClasses['2xl'];
@endphp

<div
    x-data="{ show: @entangle('show') }"
    x-show="show"
    x-on:keydown.escape.window="show = false"
    x-on:close.stop="show = false"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto"
    aria-labelledby="modal-title"
    role="dialog"
    aria-modal="true"
    x-trap.inert.noscroll="show"
>
    <!-- Backdrop -->
    <div
        x-show="show"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity"
        @if ($closeable) @click="show = false" @endif
        aria-hidden="true"
    ></div>

    <!-- Modal -->
    <div class="flex min-h-full items-center justify-center p-4">
        <div
            x-show="show"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            {{ $attributes->class(['w-full rounded-lg bg-white shadow-xl dark:bg-slate-900', $maxWidthClass]) }}
        >
            @if ($title || $closeable)
                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4 dark:border-slate-800">
                    @if ($title)
                        <h2 id="modal-title" class="text-lg font-semibold text-slate-900 dark:text-slate-100">
                            {{ $title }}
                        </h2>
                    @endif
                    
                    @if ($closeable)
                        <button
                            type="button"
                            @click="show = false"
                            class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:hover:bg-slate-800 dark:hover:text-slate-200"
                            aria-label="{{ __('admin.accessibility.close_dialog') }}"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    @endif
                </div>
            @endif

            <div class="p-6">
                {{ $slot }}
            </div>

            @isset($footer)
                <div class="border-t border-slate-200 px-6 py-4 dark:border-slate-800">
                    {{ $footer }}
                </div>
            @endisset
            
            <!-- Screen reader instructions -->
            <div class="sr-only" role="status" aria-live="polite">
                {{ __('admin.accessibility.modal_instructions') }}
            </div>
        </div>
    </div>
</div>
