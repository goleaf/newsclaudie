@props([
    'paginator' => null,
    'summary' => true,
    'summaryKey' => 'ui.pagination.summary',
    'pageSizeOptions' => [],
    'pageSizeParam' => \App\Support\Pagination\PageSize::queryParam(),
    'perPageOptions' => null,
    'perPageField' => null,
    'perPageMode' => 'http',
    'perPageValue' => null,
    'perPageFormAction' => null,
    'showPerPage' => null,
    'align' => 'between',
    'variant' => 'plain',
    'tableClass' => 'min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800',
    'headClass' => 'bg-slate-50/70 dark:bg-slate-800/40',
    'bodyClass' => 'divide-y divide-slate-100 dark:divide-slate-800/80',
    'wrapperClass' => 'overflow-hidden overflow-x-auto rounded-3xl border border-slate-200/70 dark:border-slate-800/60',
    'footerClass' => 'border-t border-slate-100/70 bg-white/50 px-3 py-4 text-sm text-slate-600 dark:border-slate-800/80 dark:bg-slate-900/40 dark:text-slate-300',
])

@php
    $optionsSource = $perPageOptions ?? $pageSizeOptions;
    $field = $perPageField ?? $pageSizeParam;
    $defaultPerPage = $perPageValue ?? ($paginator && method_exists($paginator, 'perPage') ? (int) $paginator->perPage() : \App\Support\Pagination\PageSize::contextDefault('table'));
    $options = \App\Support\Pagination\PageSize::options(is_iterable($optionsSource) ? collect($optionsSource)->all() : [], $defaultPerPage);

    $mode = $perPageMode ? strtolower((string) $perPageMode) : 'http';
    $mode = in_array($mode, ['http', 'livewire'], true) ? $mode : 'http';
    $showPerPageControls = is_bool($showPerPage)
        ? $showPerPage
        : (count($options) > 1 && $mode !== 'none');
@endphp

<x-ui.table
    {{ $attributes }}
    :paginator="$paginator"
    :summary="$summary"
    :summary-key="$summaryKey"
    :page-size-options="$options"
    :page-size-param="$field"
    :per-page-mode="$mode"
    :per-page-field="$field"
    :per-page-value="$perPageValue"
    :per-page-form-action="$perPageFormAction"
    :show-per-page="$showPerPageControls"
    table-class="{{ $tableClass }}"
    head-class="{{ $headClass }}"
    body-class="{{ $bodyClass }}"
    wrapper-class="{{ $wrapperClass }}"
    pagination-class="{{ $footerClass }}"
    pagination-align="{{ $align }}"
    pagination-variant="{{ $variant }}"
>
    @isset($head)
        <x-slot name="head">
            {{ $head }}
        </x-slot>
    @endisset

    {{ $slot }}
</x-ui.table>
