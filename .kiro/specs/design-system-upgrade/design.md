# Design Document

## Overview

This design transforms the blog application into a modern, visually stunning interface leveraging Tailwind CSS 4's latest features. The architecture focuses on creating a comprehensive design system with reusable components, consistent design tokens, and enhanced user experiences across both the admin portal and public site.

The system will provide:
- Modern design tokens using CSS custom properties
- Enhanced component library with Tailwind CSS 4 features
- Improved animations and micro-interactions
- Better accessibility and responsive design
- Glassmorphism and modern visual effects
- Optimized dark mode implementation

### Design Intent & Metrics

- Single source of truth for spacing/color/typography via `config/design-tokens.php` and Tailwind theme extensions.
- Visual consistency: admin/public components share primitives; no ad-hoc utility sprawl.
- Accessibility: WCAG 2.1 AA contrast on default + dark themes; reduced-motion friendly animations.
- Performance: minimal bundle growth; lean component markup; prefer CSS effects over JS.
- Documentation: usage guidance captured in `docs/` and component-level examples to keep drift low.

## Architecture

### Design Token System

The design system uses a hierarchical token structure:

```
Design Tokens
├── Primitive Tokens (base values)
│   ├── Colors (brand, neutral, semantic)
│   ├── Spacing (scale from 0-96)
│   ├── Typography (families, sizes, weights)
│   ├── Borders (radius, width)
│   └── Shadows (elevation levels)
├── Semantic Tokens (contextual meanings)
│   ├── Surface colors
│   ├── Text colors
│   ├── Border colors
│   └── State colors
└── Component Tokens (component-specific)
    ├── Button variants
    ├── Input states
    ├── Card styles
    └── Badge colors
```

### Technology Stack

- **Tailwind CSS 4.1+**: Latest framework with container queries and enhanced features
- **Laravel Livewire 3**: Reactive components with wire:transition
- **Alpine.js 3**: Client-side interactivity
- **CSS Custom Properties**: Dynamic theming and dark mode
- **PostCSS**: CSS processing and optimization

### Component Architecture

Components follow a composition-based architecture:

```
Base Components (Primitives)
├── Surface (container with elevation)
├── Text (typography with variants)
├── Icon (SVG icon system)
└── Spacer (consistent spacing)

Composite Components
├── Card (surface + content)
├── Button (surface + text + icon)
├── Input (surface + label + validation)
└── Modal (surface + backdrop + content)

Layout Components
├── Container (responsive width)
├── Grid (responsive grid system)
├── Stack (vertical/horizontal spacing)
└── Cluster (flex with wrapping)

Feature Components
├── DataTable (with sorting, filtering)
├── Form (with validation)
├── Navigation (with dropdowns)
└── Notification (toast system)
```

## Components and Interfaces

### Design Token Configuration

```php
// config/design-tokens.php
return [
    'colors' => [
        'brand' => [
            'primary' => '#6366f1', // indigo-500
            'secondary' => '#8b5cf6', // violet-500
            'accent' => '#ec4899', // pink-500
        ],
        'semantic' => [
            'success' => '#10b981', // emerald-500
            'warning' => '#f59e0b', // amber-500
            'error' => '#ef4444', // red-500
            'info' => '#3b82f6', // blue-500
        ],
    ],
    'spacing' => [
        'xs' => '0.5rem',  // 8px
        'sm' => '0.75rem', // 12px
        'md' => '1rem',    // 16px
        'lg' => '1.5rem',  // 24px
        'xl' => '2rem',    // 32px
    ],
    'radius' => [
        'sm' => '0.5rem',   // 8px
        'md' => '0.75rem',  // 12px
        'lg' => '1rem',     // 16px
        'xl' => '1.5rem',   // 24px
        '2xl' => '2rem',    // 32px
    ],
];
```

### Enhanced Tailwind Configuration

```javascript
// tailwind.config.js
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.{js,ts}',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'system-ui', 'sans-serif'],
                display: ['Cal Sans', 'Inter', 'sans-serif'],
            },
            colors: {
                brand: {
                    50: '#eef2ff',
                    100: '#e0e7ff',
                    500: '#6366f1',
                    600: '#4f46e5',
                    700: '#4338ca',
                },
            },
            animation: {
                'fade-in': 'fadeIn 0.3s ease-in-out',
                'slide-up': 'slideUp 0.3s ease-out',
                'scale-in': 'scaleIn 0.2s ease-out',
            },
            keyframes: {
                fadeIn: {
                    '0%': { opacity: '0' },
                    '100%': { opacity: '1' },
                },
                slideUp: {
                    '0%': { transform: 'translateY(10px)', opacity: '0' },
                    '100%': { transform: 'translateY(0)', opacity: '1' },
                },
                scaleIn: {
                    '0%': { transform: 'scale(0.95)', opacity: '0' },
                    '100%': { transform: 'scale(1)', opacity: '1' },
                },
            },
        },
    },
    plugins: [],
};
```

### Base Component: Enhanced Surface

```php
// resources/views/components/ui/surface.blade.php
@props([
    'variant' => 'default',
    'elevation' => 'sm',
    'interactive' => false,
    'glass' => false,
])

@php
$variants = [
    'default' => 'bg-white dark:bg-slate-900',
    'subtle' => 'bg-slate-50 dark:bg-slate-800/50',
    'ghost' => 'bg-transparent',
];

$elevations = [
    'none' => '',
    'sm' => 'shadow-sm',
    'md' => 'shadow-md',
    'lg' => 'shadow-lg shadow-slate-200/50 dark:shadow-slate-950/50',
    'xl' => 'shadow-xl shadow-slate-200/50 dark:shadow-slate-950/50',
];

$glassEffect = $glass 
    ? 'backdrop-blur-xl bg-white/80 dark:bg-slate-900/80 border border-white/20 dark:border-slate-700/20'
    : '';

$interactiveClasses = $interactive
    ? 'transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5 cursor-pointer'
    : '';

$baseClasses = 'rounded-2xl border border-slate-200/80 dark:border-slate-800/80';
@endphp

<div {{ $attributes->class([
    $baseClasses,
    $variants[$variant] ?? $variants['default'],
    $elevations[$elevation] ?? $elevations['sm'],
    $glassEffect,
    $interactiveClasses,
]) }}>
    {{ $slot }}
</div>
```

### Enhanced Card Component

```php
// resources/views/components/ui/card.blade.php
@props([
    'title' => null,
    'subtitle' => null,
    'actions' => null,
    'variant' => 'default',
    'padding' => 'p-6',
    'hover' => false,
    'glass' => false,
])

<x-ui.surface 
    :variant="$variant" 
    elevation="md" 
    :interactive="$hover"
    :glass="$glass"
    {{ $attributes->class($padding) }}
>
    @if ($title || $subtitle || $actions)
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div class="space-y-1">
                @isset($title)
                    <h3 class="text-lg font-semibold tracking-tight text-slate-900 dark:text-white">
                        {{ $title }}
                    </h3>
                @endisset
                @isset($subtitle)
                    <p class="text-sm text-slate-600 dark:text-slate-400">
                        {{ $subtitle }}
                    </p>
                @endisset
            </div>
            @isset($actions)
                <div class="flex items-center gap-2">
                    {{ $actions }}
                </div>
            @endisset
        </div>
    @endif

    <div class="space-y-4">
        {{ $slot }}
    </div>
</x-ui.surface>
```

### Enhanced Button Component

```php
// resources/views/components/ui/button.blade.php
@props([
    'variant' => 'primary',
    'size' => 'md',
    'icon' => null,
    'iconPosition' => 'left',
    'loading' => false,
    'disabled' => false,
])

@php
$variants = [
    'primary' => 'bg-indigo-600 text-white hover:bg-indigo-700 active:bg-indigo-800 shadow-sm hover:shadow-md',
    'secondary' => 'bg-slate-100 text-slate-900 hover:bg-slate-200 active:bg-slate-300 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700',
    'ghost' => 'bg-transparent text-slate-700 hover:bg-slate-100 active:bg-slate-200 dark:text-slate-300 dark:hover:bg-slate-800',
    'danger' => 'bg-red-600 text-white hover:bg-red-700 active:bg-red-800 shadow-sm hover:shadow-md',
];

$sizes = [
    'sm' => 'px-3 py-1.5 text-sm',
    'md' => 'px-4 py-2 text-sm',
    'lg' => 'px-6 py-3 text-base',
];

$baseClasses = 'inline-flex items-center justify-center gap-2 rounded-xl font-medium transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed active:scale-95';
@endphp

<button 
    {{ $attributes->class([
        $baseClasses,
        $variants[$variant] ?? $variants['primary'],
        $sizes[$size] ?? $sizes['md'],
    ]) }}
    @if($disabled || $loading) disabled @endif
>
    @if($loading)
        <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    @elseif($icon && $iconPosition === 'left')
        <x-icon :name="$icon" class="h-4 w-4" />
    @endif
    
    {{ $slot }}
    
    @if($icon && $iconPosition === 'right' && !$loading)
        <x-icon :name="$icon" class="h-4 w-4" />
    @endif
</button>
```

### Enhanced Input Component

```php
// resources/views/components/ui/input.blade.php
@props([
    'label' => null,
    'error' => null,
    'hint' => null,
    'icon' => null,
    'iconPosition' => 'left',
    'floating' => false,
])

@php
$id = $attributes->get('id') ?? 'input-'.uniqid();
$hasError = !empty($error);
$baseClasses = 'block w-full rounded-xl border bg-white px-4 py-2.5 text-sm text-slate-900 transition-all duration-200 placeholder:text-slate-400 focus:outline-none focus:ring-2 dark:bg-slate-900 dark:text-slate-100';
$borderClasses = $hasError 
    ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20' 
    : 'border-slate-200 focus:border-indigo-500 focus:ring-indigo-500/20 dark:border-slate-800';
@endphp

<div class="space-y-2">
    @if($label && !$floating)
        <label for="{{ $id }}" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
            {{ $label }}
        </label>
    @endif
    
    <div class="relative">
        @if($icon && $iconPosition === 'left')
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <x-icon :name="$icon" class="h-5 w-5 text-slate-400" />
            </div>
        @endif
        
        <input 
            {{ $attributes->class([
                $baseClasses,
                $borderClasses,
                $icon && $iconPosition === 'left' ? 'pl-10' : '',
                $icon && $iconPosition === 'right' ? 'pr-10' : '',
            ]) }}
            id="{{ $id }}"
        />
        
        @if($floating && $label)
            <label 
                for="{{ $id }}" 
                class="absolute left-4 top-2.5 origin-left text-sm text-slate-500 transition-all duration-200 peer-placeholder-shown:top-2.5 peer-placeholder-shown:text-base peer-focus:top-0 peer-focus:text-xs peer-focus:text-indigo-600"
            >
                {{ $label }}
            </label>
        @endif
        
        @if($icon && $iconPosition === 'right')
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                <x-icon :name="$icon" class="h-5 w-5 text-slate-400" />
            </div>
        @endif
        
        @if($hasError)
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                <svg class="h-5 w-5 text-red-500" fill="currentColor" viewBox="0 0 20 20"></svg>           <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
            </div>
        @endif
    </div>
    
    @if($error)
        <p class="flex items-center gap-1 text-sm text-red-600 dark:text-red-400 animate-slide-up">
            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
            {{ $error }}
        </p>
    @elseif($hint)
        <p class="text-sm text-slate-500 dark:text-slate-400">
            {{ $hint }}
        </p>
    @endif
</div>
```

### Enhanced Modal Component

```php
// resources/views/components/ui/modal.blade.php
@props([
    'name',
    'title' => null,
    'maxWidth' => 'md',
    'glass' => true,
])

@php
$maxWidths = [
    'sm' => 'max-w-sm',
    'md' => 'max-w-md',
    'lg' => 'max-w-lg',
    'xl' => 'max-w-xl',
    '2xl' => 'max-w-2xl',
];
@endphp

<div
    x-data="{ show: false }"
    x-on:modal-show.window="if ($event.detail.name === '{{ $name }}') show = true"
    x-on:modal-close.window="if ($event.detail.name === '{{ $name }}') show = false"
    x-show="show"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
>
    <!-- Backdrop -->
    <div 
        class="fixed inset-0 {{ $glass ? 'backdrop-blur-sm bg-slate-900/50' : 'bg-slate-900/75' }}"
        x-on:click="show = false"
    ></div>
    
    <!-- Modal -->
    <div class="flex min-h-screen items-center justify-center p-4">
        <div
            {{ $attributes->class([
                'relative w-full',
                $maxWidths[$maxWidth] ?? $maxWidths['md'],
            ]) }}
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            x-trap.noscroll="show"
        >
            <x-ui.surface 
                elevation="xl" 
                :glass="$glass"
                class="p-6"
            >
                @if($title)
                    <div class="mb-6 flex items-start justify-between">
                        <h2 class="text-xl font-semibold text-slate-900 dark:text-white">
                            {{ $title }}
                        </h2>
                        <button
                            type="button"
                            x-on:click="show = false"
                            class="rounded-lg p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800 dark:hover:text-slate-300"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                @endif
                
                {{ $slot }}
            </x-ui.surface>
        </div>
    </div>
</div>
```

### Enhanced Badge Component

```php
// resources/views/components/ui/badge.blade.php
@props([
    'variant' => 'default',
    'size' => 'md',
    'dot' => false,
    'removable' => false,
])

@php
$variants = [
    'default' => 'bg-slate-100 text-slate-700 ring-1 ring-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:ring-slate-700',
    'primary' => 'bg-indigo-100 text-indigo-700 ring-1 ring-indigo-200 dark:bg-indigo-500/20 dark:text-indigo-300 dark:ring-indigo-500/30',
    'success' => 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-500/20 dark:text-emerald-300 dark:ring-emerald-500/30',
    'warning' => 'bg-amber-100 text-amber-700 ring-1 ring-amber-200 dark:bg-amber-500/20 dark:text-amber-300 dark:ring-amber-500/30',
    'danger' => 'bg-red-100 text-red-700 ring-1 ring-red-200 dark:bg-red-500/20 dark:text-red-300 dark:ring-red-500/30',
];

$sizes = [
    'sm' => 'px-2 py-0.5 text-xs',
    'md' => 'px-2.5 py-1 text-sm',
    'lg' => 'px-3 py-1.5 text-sm',
];

$baseClasses = 'inline-flex items-center gap-1.5 rounded-full font-medium';
@endphp

<span {{ $attributes->class([
    $baseClasses,
    $variants[$variant] ?? $variants['default'],
    $sizes[$size] ?? $sizes['md'],
]) }}>
    @if($dot)
        <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
    @endif
    
    {{ $slot }}
    
    @if($removable)
        <button
            type="button"
            class="ml-0.5 inline-flex h-4 w-4 items-center justify-center rounded-full hover:bg-current/10"
        >
            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    @endif
</span>
```

### Enhanced Data Table Component

```php
// resources/views/components/admin/table.blade.php
@props([
    'pagination' => null,
    'stickyHeader' => true,
    'striped' => false,
    'hoverable' => true,
])

<x-ui.card padding="p-0" class="overflow-hidden">
    @isset($toolbar)
        <div class="border-b border-slate-200 p-4 dark:border-slate-800">
            {{ $toolbar }}
        </div>
    @endisset
    
    <div class="overflow-x-auto">
        <div class="inline-block min-w-full align-middle">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                <thead class="{{ $stickyHeader ? 'sticky top-0 z-10' : '' }} bg-slate-50/80 backdrop-blur-sm dark:bg-slate-800/80">
                    @isset($head)
                        {{ $head }}
                    @endisset
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white dark:divide-slate-800 dark:bg-slate-900">
                    {{ $slot }}
                </tbody>
            </table>
        </div>
    </div>
    
    @if($pagination)
        <div class="border-t border-slate-200 p-4 dark:border-slate-800">
            {{ $pagination->links() }}
        </div>
    @endif
</x-ui.card>
```

### Enhanced Notification Component

```php
// resources/views/components/ui/notification.blade.php
@props([
    'type' => 'info',
    'title' => null,
    'dismissible' => true,
    'autoDismiss' => true,
    'duration' => 5000,
])

@php
$types = [
    'success' => [
        'bg' => 'bg-emerald-50 dark:bg-emerald-500/10',
        'border' => 'border-emerald-200 dark:border-emerald-500/20',
        'icon' => 'text-emerald-600 dark:text-emerald-400',
        'text' => 'text-emerald-900 dark:text-emerald-100',
    ],
    'error' => [
        'bg' => 'bg-red-50 dark:bg-red-500/10',
        'border' => 'border-red-200 dark:border-red-500/20',
        'icon' => 'text-red-600 dark:text-red-400',
        'text' => 'text-red-900 dark:text-red-100',
    ],
    'warning' => [
        'bg' => 'bg-amber-50 dark:bg-amber-500/10',
        'border' => 'border-amber-200 dark:border-amber-500/20',
        'icon' => 'text-amber-600 dark:text-amber-400',
        'text' => 'text-amber-900 dark:text-amber-100',
    ],
    'info' => [
        'bg' => 'bg-blue-50 dark:bg-blue-500/10',
        'border' => 'border-blue-200 dark:border-blue-500/20',
        'icon' => 'text-blue-600 dark:text-blue-400',
        'text' => 'text-blue-900 dark:text-blue-100',
    ],
];

$config = $types[$type] ?? $types['info'];
@endphp

<div
    x-data="{ show: true, progress: 100 }"
    x-show="show"
    x-init="
        @if($autoDismiss)
            let interval = setInterval(() => {
                progress -= (100 / {{ $duration }}) * 100;
                if (progress <= 0) {
                    show = false;
                    clearInterval(interval);
                }
            }, 100);
        @endif
    "
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-x-full"
    x-transition:enter-end="opacity-100 translate-x-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 translate-x-0"
    x-transition:leave-end="opacity-0 translate-x-full"
    {{ $attributes->class([
        'relative overflow-hidden rounded-xl border p-4 shadow-lg',
        $config['bg'],
        $config['border'],
    ]) }}
>
    <div class="flex items-start gap-3">
        <div class="{{ $config['icon'] }}">
            @if($type === 'success')
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
            @elseif($type === 'error')
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
            @elseif($type === 'warning')
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
            @else
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
            @endif
        </div>
        
        <div class="flex-1 {{ $config['text'] }}">
            @if($title)
                <p class="font-semibold">{{ $title }}</p>
            @endif
            <p class="text-sm">{{ $slot }}</p>
        </div>
        
        @if($dismissible)
            <button
                type="button"
                x-on:click="show = false"
                class="{{ $config['icon'] }} rounded-lg p-1 transition hover:bg-current/10"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        @endif
    </div>
    
    @if($autoDismiss)
        <div class="absolute bottom-0 left-0 h-1 bg-current/20">
            <div 
                class="h-full bg-current transition-all duration-100 ease-linear"
                :style="`width: ${progress}%`"
            ></div>
        </div>
    @endif
</div>
```

### Skeleton Loader Component

```php
// resources/views/components/ui/skeleton.blade.php
@props([
    'type' => 'text',
    'lines' => 3,
    'width' => 'full',
])

@php
$widths = [
    'full' => 'w-full',
    '3/4' => 'w-3/4',
    '1/2' => 'w-1/2',
    '1/4' => 'w-1/4',
];
@endphp

@if($type === 'text')
    <div class="animate-pulse space-y-3">
        @for($i = 0; $i < $lines; $i++)
            <div class="h-4 rounded {{ $i === $lines - 1 ? $widths['3/4'] : $widths['full'] }} bg-slate-200 dark:bg-slate-800"></div>
        @endfor
    </div>
@elseif($type === 'card')
    <div class="animate-pulse space-y-4 rounded-2xl border border-slate-200 p-6 dark:border-slate-800">
        <div class="h-6 w-1/2 rounded bg-slate-200 dark:bg-slate-800"></div>
        <div class="space-y-2">
            <div class="h-4 w-full rounded bg-slate-200 dark:bg-slate-800"></div>
            <div class="h-4 w-3/4 rounded bg-slate-200 dark:bg-slate-800"></div>
        </div>
    </div>
@elseif($type === 'avatar')
    <div class="h-10 w-10 animate-pulse rounded-full bg-slate-200 dark:bg-slate-800"></div>
@elseif($type === 'image')
    <div class="aspect-video w-full animate-pulse rounded-xl bg-slate-200 dark:bg-slate-800"></div>
@endif
```

## Data Models

No new database models are required. This feature enhances the presentation layer only.

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Design Token Properties

Property 1: Color contrast compliance
*For any* text and background color combination, the contrast ratio should meet WCAG AA standards (minimum 4.5:1 for normal text, 3:1 for large text)
**Validates: Requirements 16.4, 17.4**

Property 2: Spacing consistency
*For any* component using spacing tokens, the spacing should follow the defined scale and maintain visual rhythm
**Validates: Requirements 1.2**

Property 3: Dark mode color mapping
*For any* color token, when dark mode is enabled, the system should apply the corresponding dark mode variant
**Validates: Requirements 1.6, 4.5**

### Component Rendering Properties

Property 4: Component variant application
*For any* component with variant props, the correct CSS classes should be applied based on the variant value
**Validates: Requirements 2.1, 2.2, 2.3**

Property 5: Responsive breakpoint behavior
*For any* responsive component, layout changes should occur at the defined breakpoints
**Validates: Requirements 20.1, 20.2, 20.3, 20.4**

Property 6: Interactive state transitions
*For any* interactive component, state changes should animate smoothly with defined durations
**Validates: Requirements 3.2, 3.5, 9.2, 13.2**

### Accessibility Properties

Property 7: Focus indicator visibility
*For any* interactive element, when focused, a visible focus ring should be displayed
**Validates: Requirements 16.1**

Property 8: ARIA attribute presence
*For any* interactive component, appropriate ARIA attributes should be present
**Validates: Requirements 16.2, 16.4**

Property 9: Keyboard navigation support
*For any* interactive component, all actions should be accessible via keyboard
**Validates: Requirements 9.5**

Property 10: Screen reader announcement
*For any* dynamic content change, screen readers should receive appropriate announcements
**Validates: Requirements 16.3**

### Animation Properties

Property 11: Animation duration consistency
*For any* animated transition, the duration should match the defined animation tokens
**Validates: Requirements 18.1, 18.2, 18.3, 18.4**

Property 12: Reduced motion respect
*For any* animation, when prefers-reduced-motion is enabled, animations should be disabled or simplified
**Validates: Requirements 18.5**

Property 13: Loading state display
*For any* async operation exceeding 300ms, a loading indicator should be displayed
**Validates: Requirements 12.1, 12.2**

### Form Component Properties

Property 14: Validation error display
*For any* form field with validation errors, error messages should be displayed with appropriate styling
**Validates: Requirements 8.2, 16.3**

Property 15: Form submission state
*For any* form being submitted, all inputs should be disabled and a loading state should be shown
**Validates: Requirements 8.5, 15.2**

Property 16: Floating label behavior
*For any* input with floating labels, the label should float when the input has focus or contains a value
**Validates: Requirements 8.1**

### Modal Properties

Property 17: Modal focus trap
*For any* open modal, focus should be trapped within the modal and not escape to background content
**Validates: Requirements 10.4, 16.4**

Property 18: Modal backdrop interaction
*For any* modal with a backdrop, clicking the backdrop should close the modal
**Validates: Requirements 10.2**

Property 19: Modal animation sequence
*For any* modal opening or closing, the animation should complete before state changes
**Validates: Requirements 10.1, 10.3**

### Table Properties

Property 20: Sticky header positioning
*For any* table with sticky headers, the header should remain visible during vertical scroll
**Validates: Requirements 7.1**

Property 21: Table row hover state
*For any* interactive table row, hovering should apply the defined hover styles
**Validates: Requirements 7.3**

Property 22: Horizontal scroll indicators
*For any* table exceeding container width, shadow indicators should appear on scrollable edges
**Validates: Requirements 7.2**

### Notification Properties

Property 23: Notification auto-dismiss timing
*For any* notification with auto-dismiss enabled, it should dismiss after the specified duration
**Validates: Requirements 11.3**

Property 24: Notification stacking
*For any* multiple notifications, they should stack with proper spacing
**Validates: Requirements 11.4**

Property 25: Notification animation
*For any* notification appearing or dismissing, it should animate smoothly
**Validates: Requirements 11.1, 11.5**

### Button Properties

Property 26: Button loading state
*For any* button in loading state, a spinner should be displayed and the button should be disabled
**Validates: Requirements 15.2**

Property 27: Button press feedback
*For any* button click, a scale animation should provide tactile feedback
**Validates: Requirements 15.1**

Property 28: Button icon alignment
*For any* button with an icon, the icon should be properly aligned with the text
**Validates: Requirements 15.4**

### Card Properties

Property 29: Card hover elevation
*For any* interactive card, hovering should increase the shadow elevation
**Validates: Requirements 13.2**

Property 30: Card image aspect ratio
*For any* card containing an image, the image should maintain the defined aspect ratio
**Validates: Requirements 13.3**

## Error Handling

### Component Prop Validation

All components validate props and provide sensible defaults:
- Invalid variant values fall back to 'default'
- Invalid size values fall back to 'md'
- Missing required props trigger console warnings in development

### Animation Fallbacks

When animations fail or are disabled:
- Instant state changes replace animated transitions
- Layout shifts are minimized
- Functionality remains intact

### Dark Mode Fallbacks

When dark mode detection fails:
- System defaults to light mode
- Manual toggle remains functional
- Color contrast is maintained

## Testing Strategy

### Visual Regression Testing

Use Playwright for visual regression tests:
- Capture screenshots of all component variants
- Compare against baseline images
- Flag visual differences for review

### Accessibility Testing

Use automated accessibility testing:
- axe-core for WCAG compliance
- Keyboard navigation testing
- Screen reader compatibility testing

### Property-Based Testing

Property-based tests will verify universal properties using Pest PHP:
- Generate random component props
- Verify correct class application
- Test responsive behavior
- Validate accessibility attributes

### Browser Testing

Test across browsers and devices:
- Chrome, Firefox, Safari, Edge
- Mobile iOS and Android
- Tablet devices
- Different screen sizes

## Performance Considerations

### CSS Optimization

- Use Tailwind's JIT compiler for minimal CSS bundle
- Purge unused styles in production
- Leverage CSS custom properties for theming
- Minimize @apply usage

### Animation Performance

- Use transform and opacity for animations (GPU-accelerated)
- Avoid animating layout properties
- Use will-change sparingly
- Implement intersection observer for scroll animations

### Component Loading

- Lazy load heavy components
- Use skeleton loaders for perceived performance
- Implement code splitting for large features
- Optimize image loading with lazy loading

### Dark Mode Performance

- Use CSS custom properties for instant theme switching
- Avoid JavaScript-based theme calculations
- Cache theme preference in localStorage
- Minimize repaints during theme changes

## Security Considerations

### XSS Prevention

- All user input is escaped by Blade
- Component props are validated
- HTML attributes are sanitized
- No eval() or innerHTML usage

### CSRF Protection

- All forms include CSRF tokens
- Livewire handles CSRF automatically
- API requests include proper headers

## Deployment Considerations

### Build Process

```bash
# Install dependencies
npm install

# Build for production
npm run build

# Optimize images
# (if image optimization is added)
```

### Browser Support

- Modern browsers (last 2 versions)
- Progressive enhancement for older browsers
- Graceful degradation of advanced features
- Polyfills for critical features if needed

### Performance Monitoring

- Monitor Core Web Vitals
- Track animation performance
- Measure component render times
- Monitor bundle sizes
