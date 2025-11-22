@props([
    'columns' => [],
    'sortField' => null,
    'sortDirection' => 'asc',
    'sortHandler' => 'sortBy',
])

@php
    $columns = collect($columns)
        ->map(function ($column) {
            if (is_string($column) || $column instanceof \Stringable) {
                return [
                    'label' => (string) $column,
                    'class' => null,
                    'sortable' => false,
                    'field' => null,
                ];
            }

            if (is_array($column)) {
                return [
                    'label' => $column['label'] ?? null,
                    'class' => trim(($column['class'] ?? '').' '.($column['align'] ?? '')),
                    'sortable' => (bool) ($column['sortable'] ?? false),
                    'field' => $column['field'] ?? null,
                ];
            }

            return [
                'label' => null,
                'class' => null,
                'sortable' => false,
                'field' => null,
            ];
        })
        ->filter(fn ($column) => filled($column['label'] ?? null));

    $rowClasses = 'text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400';
    $direction = strtolower((string) $sortDirection) === 'desc' ? 'desc' : 'asc';
@endphp

<tr {{ $attributes->class($rowClasses) }}>
    @isset($before)
        {{ $before }}
    @endisset

    @if ($columns->isNotEmpty())
        @foreach ($columns as $column)
            @php
                $isSortable = $column['sortable'] && filled($column['field']);
                $isActive = $isSortable && $sortField === $column['field'];
                $ariaSort = $isSortable
                    ? ($isActive ? ($direction === 'desc' ? 'descending' : 'ascending') : 'none')
                    : null;
            @endphp
            <th
                class="px-4 py-3 {{ $column['class'] ?? '' }}"
                @if ($ariaSort) aria-sort="{{ $ariaSort }}" @endif
            >
                @if ($isSortable && $sortHandler)
                    <button
                        type="button"
                        wire:click="{{ $sortHandler }}('{{ $column['field'] }}')"
                        class="group inline-flex items-center gap-2 rounded-lg px-2 py-1 transition hover:bg-slate-100 hover:text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 dark:hover:bg-slate-800/70 dark:hover:text-white"
                    >
                        <span>{{ $column['label'] }}</span>
                        <span class="flex flex-col text-[10px] leading-none text-slate-400">
                            <svg
                                class="h-3 w-3 {{ $isActive && $direction === 'asc' ? 'text-indigo-600 opacity-100' : 'opacity-40 group-hover:opacity-80' }}"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                                aria-hidden="true"
                            >
                                <path fill-rule="evenodd" d="M10 5a.75.75 0 01.53.22l4 4a.75.75 0 11-1.06 1.06L10 6.81 6.53 10.28a.75.75 0 01-1.06-1.06l4-4A.75.75 0 0110 5z" clip-rule="evenodd" />
                            </svg>
                            <svg
                                class="h-3 w-3 {{ $isActive && $direction === 'desc' ? 'text-indigo-600 opacity-100' : 'opacity-40 group-hover:opacity-80' }}"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                                aria-hidden="true"
                            >
                                <path fill-rule="evenodd" d="M10 15a.75.75 0 01-.53-.22l-4-4a.75.75 0 111.06-1.06L10 13.19l3.47-3.47a.75.75 0 111.06 1.06l-4 4A.75.75 0 0110 15z" clip-rule="evenodd" />
                            </svg>
                        </span>
                    </button>
                @else
                    {{ $column['label'] }}
                @endif
            </th>
        @endforeach
    @else
        {{ $slot }}
    @endif

    @isset($after)
        {{ $after }}
    @endisset
</tr>
