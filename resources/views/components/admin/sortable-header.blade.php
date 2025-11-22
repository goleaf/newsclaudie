@props([
    'field' => null,
    'sortField' => null,
    'sortDirection' => 'asc',
    'label' => null,
    'align' => 'left',
])

@php
    $isActive = $field && $sortField === $field;
    $direction = strtolower((string) $sortDirection) === 'desc' ? 'desc' : 'asc';
    $ariaSort = $isActive ? ($direction === 'desc' ? 'descending' : 'ascending') : 'none';
    
    $alignClasses = [
        'left' => 'text-left',
        'center' => 'text-center',
        'right' => 'text-right',
    ];
    
    $alignClass = $alignClasses[$align] ?? $alignClasses['left'];
@endphp

<th
    {{ $attributes->class(['px-4 py-3', $alignClass]) }}
    aria-sort="{{ $ariaSort }}"
    scope="col"
>
    @if ($field)
        <button
            type="button"
            wire:click="sortBy('{{ $field }}')"
            class="group inline-flex items-center gap-2 rounded-lg px-2 py-1 text-xs font-semibold uppercase tracking-wide text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 dark:text-slate-400 dark:hover:bg-slate-800/70 dark:hover:text-white"
            aria-label="{{ __('admin.table.sort_by', ['field' => $label ?? $field]) }}"
        >
            <span>{{ $label ?? $slot }}</span>
            <span class="flex flex-col text-[10px] leading-none text-slate-400" aria-hidden="true">
                <svg
                    class="h-3 w-3 transition {{ $isActive && $direction === 'asc' ? 'text-indigo-600 opacity-100' : 'opacity-40 group-hover:opacity-80' }}"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                >
                    <path fill-rule="evenodd" d="M10 5a.75.75 0 01.53.22l4 4a.75.75 0 11-1.06 1.06L10 6.81 6.53 10.28a.75.75 0 01-1.06-1.06l4-4A.75.75 0 0110 5z" clip-rule="evenodd" />
                </svg>
                <svg
                    class="h-3 w-3 transition {{ $isActive && $direction === 'desc' ? 'text-indigo-600 opacity-100' : 'opacity-40 group-hover:opacity-80' }}"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                >
                    <path fill-rule="evenodd" d="M10 15a.75.75 0 01-.53-.22l-4-4a.75.75 0 111.06-1.06L10 13.19l3.47-3.47a.75.75 0 111.06 1.06l-4 4A.75.75 0 0110 15z" clip-rule="evenodd" />
                </svg>
            </span>
        </button>
    @else
        <span class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
            {{ $label ?? $slot }}
        </span>
    @endif
</th>
