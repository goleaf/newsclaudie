<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

/**
 * Surface Component Property Tests
 *
 * Property-based tests for the x-ui.surface component to ensure
 * all variants, elevations, and combinations work correctly.
 *
 * @see resources/views/components/ui/surface.blade.php
 * @see docs/design-tokens/SURFACE_COMPONENT.md
 * @see .kiro/specs/design-system-upgrade/tasks.md (Task 2.1)
 */

/**
 * Property 4: Component variant application
 *
 * Validates that all variant values produce valid HTML output
 * and apply the correct background classes.
 *
 * Requirements: 2.1, 2.2, 2.3
 */
test('all variants produce valid output', function () {
    $variants = ['default', 'subtle', 'ghost'];
    
    foreach ($variants as $variant) {
        $html = Blade::render(
            '<x-ui.surface variant="' . $variant . '">Test Content</x-ui.surface>'
        );
        
        expect($html)
            ->toBeString()
            ->toContain('Test Content')
            ->toContain('rounded-2xl')
            ->toContain('border');
    }
})->group('property', 'surface', 'design-system');

/**
 * Property: Variant background classes
 *
 * Validates that each variant applies the correct background classes
 * for both light and dark modes.
 */
test('variants apply correct background classes', function (string $variant, string $lightClass, string $darkClass) {
    $html = Blade::render(
        '<x-ui.surface variant="' . $variant . '">Content</x-ui.surface>'
    );
    
    expect($html)
        ->toContain($lightClass)
        ->toContain($darkClass);
})->with([
    ['default', 'bg-white', 'dark:bg-slate-900'],
    ['subtle', 'bg-slate-50', 'dark:bg-slate-800/50'],
    ['ghost', 'bg-transparent', 'bg-transparent'],
])->group('property', 'surface', 'design-system');

/**
 * Property: Elevation shadow classes
 *
 * Validates that each elevation level applies the correct shadow classes.
 */
test('elevations apply correct shadow classes', function (string $elevation, string $expectedClass) {
    $html = Blade::render(
        '<x-ui.surface elevation="' . $elevation . '">Content</x-ui.surface>'
    );
    
    if ($expectedClass !== '') {
        expect($html)->toContain($expectedClass);
    } else {
        // For 'none', ensure no shadow classes are present
        expect($html)
            ->not->toContain('shadow-sm')
            ->not->toContain('shadow-md')
            ->not->toContain('shadow-lg')
            ->not->toContain('shadow-xl');
    }
})->with([
    ['none', ''],
    ['sm', 'shadow-sm'],
    ['md', 'shadow-md'],
    ['lg', 'shadow-lg'],
    ['xl', 'shadow-xl'],
])->group('property', 'surface', 'design-system');

/**
 * Property: Interactive state classes
 *
 * Validates that interactive prop adds hover effects and cursor pointer.
 */
test('interactive prop adds hover effects', function (bool $interactive) {
    $html = Blade::render(
        '<x-ui.surface :interactive="' . ($interactive ? 'true' : 'false') . '">Content</x-ui.surface>'
    );
    
    if ($interactive) {
        expect($html)
            ->toContain('hover:shadow-lg')
            ->toContain('hover:-translate-y-0.5')
            ->toContain('cursor-pointer')
            ->toContain('transition-all');
    } else {
        expect($html)
            ->not->toContain('hover:shadow-lg')
            ->not->toContain('hover:-translate-y-0.5')
            ->not->toContain('cursor-pointer');
    }
})->with([
    [true],
    [false],
])->group('property', 'surface', 'design-system');

/**
 * Property: Glass effect classes
 *
 * Validates that glass prop adds backdrop blur and semi-transparent background.
 */
test('glass prop adds glassmorphism effect', function (bool $glass) {
    $html = Blade::render(
        '<x-ui.surface :glass="' . ($glass ? 'true' : 'false') . '">Content</x-ui.surface>'
    );
    
    if ($glass) {
        expect($html)
            ->toContain('backdrop-blur-xl')
            ->toContain('bg-white/80')
            ->toContain('dark:bg-slate-900/80');
    } else {
        expect($html)
            ->not->toContain('backdrop-blur-xl');
    }
})->with([
    [true],
    [false],
])->group('property', 'surface', 'design-system');

/**
 * Property: Base classes always present
 *
 * Validates that base classes (border radius, border) are always applied
 * regardless of other props.
 */
test('base classes are always present', function () {
    $variants = ['default', 'subtle', 'ghost'];
    $elevations = ['none', 'sm', 'md', 'lg', 'xl'];
    
    foreach ($variants as $variant) {
        foreach ($elevations as $elevation) {
            $html = Blade::render(
                '<x-ui.surface variant="' . $variant . '" elevation="' . $elevation . '">Content</x-ui.surface>'
            );
            
            expect($html)
                ->toContain('rounded-2xl')
                ->toContain('border')
                ->toContain('border-slate-200/80')
                ->toContain('dark:border-slate-800/80');
        }
    }
})->group('property', 'surface', 'design-system');

/**
 * Property: Additional classes via attributes
 *
 * Validates that additional classes can be passed via $attributes
 * and are properly merged with component classes.
 */
test('additional classes are merged correctly', function () {
    $html = Blade::render(
        '<x-ui.surface class="p-6 custom-class">Content</x-ui.surface>'
    );
    
    expect($html)
        ->toContain('p-6')
        ->toContain('custom-class')
        ->toContain('rounded-2xl')
        ->toContain('border');
})->group('property', 'surface', 'design-system');

/**
 * Property: Slot content rendering
 *
 * Validates that slot content is rendered correctly and not escaped.
 */
test('slot content renders correctly', function () {
    $html = Blade::render(
        '<x-ui.surface><h1>Title</h1><p>Paragraph</p></x-ui.surface>'
    );
    
    expect($html)
        ->toContain('<h1>Title</h1>')
        ->toContain('<p>Paragraph</p>');
})->group('property', 'surface', 'design-system');

/**
 * Property: Invalid variant fallback
 *
 * Validates that invalid variant values fall back to 'default'.
 */
test('invalid variant falls back to default', function () {
    $html = Blade::render(
        '<x-ui.surface variant="invalid">Content</x-ui.surface>'
    );
    
    expect($html)
        ->toContain('bg-white')
        ->toContain('dark:bg-slate-900');
})->group('property', 'surface', 'design-system');

/**
 * Property: Invalid elevation fallback
 *
 * Validates that invalid elevation values fall back to 'sm'.
 */
test('invalid elevation falls back to sm', function () {
    $html = Blade::render(
        '<x-ui.surface elevation="invalid">Content</x-ui.surface>'
    );
    
    expect($html)
        ->toContain('shadow-sm');
})->group('property', 'surface', 'design-system');

/**
 * Property: Combined props interaction
 *
 * Validates that multiple props can be combined without conflicts.
 */
test('multiple props combine correctly', function () {
    $html = Blade::render(
        '<x-ui.surface variant="subtle" elevation="lg" :interactive="true" :glass="false" class="p-8">Content</x-ui.surface>'
    );
    
    expect($html)
        ->toContain('bg-slate-50')
        ->toContain('dark:bg-slate-800/50')
        ->toContain('shadow-lg')
        ->toContain('hover:shadow-lg')
        ->toContain('cursor-pointer')
        ->toContain('p-8')
        ->not->toContain('backdrop-blur-xl');
})->group('property', 'surface', 'design-system');

/**
 * Property: Glass overrides variant background
 *
 * Validates that when glass is enabled, it overrides the variant background.
 */
test('glass effect overrides variant background', function () {
    $html = Blade::render(
        '<x-ui.surface variant="subtle" :glass="true">Content</x-ui.surface>'
    );
    
    expect($html)
        ->toContain('backdrop-blur-xl')
        ->toContain('bg-white/80')
        ->toContain('dark:bg-slate-900/80');
})->group('property', 'surface', 'design-system');

/**
 * Property: Dark mode classes present
 *
 * Validates that all variants include dark mode classes.
 */
test('all variants include dark mode classes', function () {
    $variants = ['default', 'subtle', 'ghost'];
    
    foreach ($variants as $variant) {
        $html = Blade::render(
            '<x-ui.surface variant="' . $variant . '">Content</x-ui.surface>'
        );
        
        expect($html)->toContain('dark:');
    }
})->group('property', 'surface', 'design-system', 'dark-mode');

/**
 * Property: Accessibility attributes
 *
 * Validates that the component renders as a semantic div
 * without unnecessary ARIA attributes.
 */
test('renders as semantic div without aria attributes', function () {
    $html = Blade::render(
        '<x-ui.surface>Content</x-ui.surface>'
    );
    
    expect($html)
        ->toContain('<div')
        ->toContain('</div>')
        ->not->toContain('role=')
        ->not->toContain('aria-');
})->group('property', 'surface', 'design-system', 'accessibility');
