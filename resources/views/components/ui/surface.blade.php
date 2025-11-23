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




