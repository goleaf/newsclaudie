@props([
    'columns' => [],
])

@php
    $columns = collect($columns)
        ->map(function ($column) {
            if (is_string($column) || $column instanceof \Stringable) {
                return ['label' => (string) $column, 'class' => null];
            }

            if (is_array($column)) {
                return [
                    'label' => $column['label'] ?? null,
                    'class' => trim(($column['class'] ?? '').' '.($column['align'] ?? '')),
                ];
            }

            return ['label' => null, 'class' => null];
        })
        ->filter(fn ($column) => filled($column['label'] ?? null));

    $rowClasses = 'text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400';
@endphp

<tr {{ $attributes->class($rowClasses) }}>
    @if ($columns->isNotEmpty())
        @foreach ($columns as $column)
            <th class="px-4 py-3 {{ $column['class'] ?? '' }}">
                {{ $column['label'] }}
            </th>
        @endforeach
    @else
        {{ $slot }}
    @endif
</tr>
