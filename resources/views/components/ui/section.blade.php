@props([
    'tag' => 'section',
    'maxWidth' => 'max-w-7xl',
    'gutters' => 'px-4 sm:px-6 lg:px-8',
    'padding' => '',
])

@php
    $tagName = $tag ?: 'section';
    $baseClasses = trim("mx-auto w-full {$maxWidth}");
    $gutterClasses = trim($gutters);
    $paddingClasses = trim($padding);
    $composedClasses = trim("{$baseClasses} {$gutterClasses} {$paddingClasses}");
@endphp

<{{ $tagName }} {{ $attributes->class($composedClasses) }}>
    {{ $slot }}
</{{ $tagName }}>

