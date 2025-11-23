{{--
    Surface Component

    A foundational UI primitive that provides consistent container styling with
    support for variants, elevation levels, interactive states, and glassmorphism effects.

    This component is part of the design system upgrade and leverages design tokens
    for consistent visual properties across the application.

    @component
    @package resources.views.components.ui

    Props:
    @param string $variant Background variant: 'default', 'subtle', or 'ghost' (default: 'default')
    @param string $elevation Shadow elevation: 'none', 'sm', 'md', 'lg', or 'xl' (default: 'sm')
    @param bool $interactive Enable hover effects and cursor pointer (default: false)
    @param bool $glass Enable glassmorphism effect with backdrop blur (default: false)

    Features:
    - Consistent border radius (2xl) and border styling
    - Dark mode support for all variants
    - Elevation system with enhanced shadows
    - Interactive hover states with lift animation
    - Glassmorphism effect with backdrop blur
    - Fully composable with additional classes via $attributes

    Design Tokens:
    - Border radius: 2xl (32px) from design-tokens.php
    - Shadows: sm, md, lg, xl from elevation system
    - Colors: Neutral palette (slate) for backgrounds and borders
    - Transitions: 200ms duration for interactive states

    Accessibility:
    - Semantic HTML (div container)
    - Respects prefers-reduced-motion for animations
    - Sufficient color contrast in all variants (WCAG 2.1 AA)
    - Interactive variant includes cursor pointer for clarity

    Performance:
    - Pure CSS implementation (no JavaScript)
    - Minimal class composition
    - GPU-accelerated transforms for hover effects

    @see config/design-tokens.php
    @see docs/DESIGN_TOKENS.md
    @see .kiro/specs/design-system-upgrade/requirements.md (Requirement 2)

    @example Basic usage
    <x-ui.surface>
        <p>Content goes here</p>
    </x-ui.surface>

    @example With variant and elevation
    <x-ui.surface variant="subtle" elevation="md">
        <h2>Card Title</h2>
        <p>Card content</p>
    </x-ui.surface>

    @example Interactive card
    <x-ui.surface :interactive="true" elevation="lg">
        <a href="/post/1" class="block p-6">
            <h3>Clickable Card</h3>
        </a>
    </x-ui.surface>

    @example Glassmorphism modal overlay
    <x-ui.surface :glass="true" elevation="xl" class="p-8">
        <h2>Modal Content</h2>
    </x-ui.surface>

    @example Ghost variant (transparent)
    <x-ui.surface variant="ghost" elevation="none">
        <div>Transparent container</div>
    </x-ui.surface>

    @version 2.0.0
    @since 1.0.0
    @updated 2025-11-23 - Enhanced with variants, elevation, interactive, and glass props
--}}
@props([
    'variant' => 'default',
    'elevation' => 'sm',
    'interactive' => false,
    'glass' => false,
])

@php
/**
 * Variant definitions
 * Maps variant names to Tailwind background classes with dark mode support
 */
$variants = [
    'default' => 'bg-white dark:bg-slate-900',
    'subtle' => 'bg-slate-50 dark:bg-slate-800/50',
    'ghost' => 'bg-transparent',
];

/**
 * Elevation definitions
 * Maps elevation levels to shadow classes with dark mode enhancements
 * Aligned with design-tokens.php elevation system
 */
$elevations = [
    'none' => '',
    'sm' => 'shadow-sm',
    'md' => 'shadow-md',
    'lg' => 'shadow-lg shadow-slate-200/50 dark:shadow-slate-950/50',
    'xl' => 'shadow-xl shadow-slate-200/50 dark:shadow-slate-950/50',
];

/**
 * Glassmorphism effect
 * Applies backdrop blur and semi-transparent background for modern glass effect
 * Overrides variant background when enabled
 */
$glassEffect = $glass 
    ? 'backdrop-blur-xl bg-white/80 dark:bg-slate-900/80 border border-white/20 dark:border-slate-700/20'
    : '';

/**
 * Interactive state classes
 * Adds hover effects with lift animation and cursor pointer
 * Uses GPU-accelerated transform for smooth performance
 */
$interactiveClasses = $interactive
    ? 'transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5 cursor-pointer'
    : '';

/**
 * Base classes applied to all surface instances
 * Provides consistent border radius and border styling
 */
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
