@props([
    'shortcuts' => [],
])

@php
    $defaultShortcuts = [
        ['key' => 'Ctrl+K', 'description' => __('admin.shortcuts.create_shortcut')],
        ['key' => 'Escape', 'description' => __('admin.accessibility.close_dialog')],
        ['key' => '/', 'description' => __('admin.accessibility.search_hint')],
        ['key' => 'Tab', 'description' => __('admin.accessibility.table_navigation')],
        ['key' => 'Space', 'description' => __('admin.accessibility.checkbox_select')],
    ];
    
    $allShortcuts = array_merge($defaultShortcuts, $shortcuts);
@endphp

<div {{ $attributes->class('rounded-lg border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-900/50') }}>
    <h3 class="mb-3 text-sm font-semibold text-slate-900 dark:text-slate-100">
        {{ __('admin.accessibility.keyboard_shortcuts') }}
    </h3>
    <dl class="space-y-2">
        @foreach ($allShortcuts as $shortcut)
            <div class="flex items-center justify-between gap-4">
                <dt class="text-sm text-slate-600 dark:text-slate-400">
                    {{ $shortcut['description'] }}
                </dt>
                <dd>
                    <kbd class="inline-flex items-center rounded border border-slate-300 bg-white px-2 py-1 text-xs font-mono text-slate-900 shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                        {{ $shortcut['key'] }}
                    </kbd>
                </dd>
            </div>
        @endforeach
    </dl>
</div>
