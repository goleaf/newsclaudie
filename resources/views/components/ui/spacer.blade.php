@props([
    'size' => 'md',
    'direction' => 'vertical',
])

@php
// Vertical spacing (margin-top/bottom or padding-top/bottom)
$verticalSizes = [
    'xs' => 'h-2',   // 8px
    'sm' => 'h-3',   // 12px
    'md' => 'h-4',   // 16px
    'lg' => 'h-6',   // 24px
    'xl' => 'h-8',   // 32px
    '2xl' => 'h-12', // 48px
    '3xl' => 'h-16', // 64px
    '4xl' => 'h-24', // 96px
];

// Horizontal spacing (margin-left/right or padding-left/right)
$horizontalSizes = [
    'xs' => 'w-2',   // 8px
    'sm' => 'w-3',   // 12px
    'md' => 'w-4',   // 16px
    'lg' => 'w-6',   // 24px
    'xl' => 'w-8',   // 32px
    '2xl' => 'w-12', // 48px
    '3xl' => 'w-16', // 64px
    '4xl' => 'w-24', // 96px
];

$sizeClass = $direction === 'horizontal' 
    ? ($horizontalSizes[$size] ?? $horizontalSizes['md'])
    : ($verticalSizes[$size] ?? $verticalSizes['md']);
@endphp

<div {{ $attributes->class([$sizeClass, 'shrink-0']) }} aria-hidden="true"></div>
