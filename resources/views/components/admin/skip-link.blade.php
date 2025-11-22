@props([
    'target' => '#main-content',
    'label' => null,
])

<a
    href="{{ $target }}"
    {{ $attributes->class('sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-indigo-600 focus:px-4 focus:py-2 focus:text-sm focus:font-medium focus:text-white focus:shadow-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2') }}
>
    {{ $label ?? __('admin.accessibility.skip_to_content') }}
</a>
