@props([
    'status' => 'default',
    'size' => 'md',
    'icon' => null,
])

@php
    $statusConfig = [
        'published' => ['color' => 'green', 'label' => __('admin.status.published')],
        'draft' => ['color' => 'amber', 'label' => __('admin.status.draft')],
        'approved' => ['color' => 'green', 'label' => __('admin.status.approved')],
        'pending' => ['color' => 'amber', 'label' => __('admin.status.pending')],
        'rejected' => ['color' => 'red', 'label' => __('admin.status.rejected')],
        'active' => ['color' => 'green', 'label' => __('admin.status.active')],
        'banned' => ['color' => 'red', 'label' => __('admin.status.banned')],
        'admin' => ['color' => 'orange', 'label' => __('admin.status.admin')],
        'author' => ['color' => 'blue', 'label' => __('admin.status.author')],
        'reader' => ['color' => 'slate', 'label' => __('admin.status.reader')],
        'default' => ['color' => 'slate', 'label' => ''],
    ];

    $config = $statusConfig[$status] ?? $statusConfig['default'];
    $color = $config['color'];
    $label = $slot->isEmpty() ? $config['label'] : '';
@endphp

<flux:badge
    {{ $attributes }}
    color="{{ $color }}"
    size="{{ $size }}"
>
    @if ($icon)
        <flux:icon :name="$icon" class="mr-1" />
    @endif
    {{ $slot->isEmpty() ? $label : $slot }}
</flux:badge>
