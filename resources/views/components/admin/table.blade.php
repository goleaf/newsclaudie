@props([
    'pagination' => null,
    'summary' => true,
    'summaryKey' => 'ui.pagination.summary',
    'showSummary' => true,
    'perPageMode' => 'livewire',
    'perPageField' => null,
    'perPageValue' => null,
    'perPageOptions' => null,
    'perPageFormAction' => null,
    'showPerPage' => null,
    'align' => 'left',
    'variant' => 'plain',
    'edge' => 1,
    'query' => null,
    'ariaLabel' => null,
    'perPageContext' => 'admin',
])

@php
    $paginator = $pagination instanceof \Illuminate\Contracts\Pagination\Paginator ? $pagination : null;

    $mode = $perPageMode ? strtolower((string) $perPageMode) : 'livewire';
    $mode = in_array($mode, ['http', 'livewire'], true) ? $mode : 'livewire';
    $resolvedField = $perPageField ?: ($mode === 'livewire' ? 'perPage' : \App\Support\Pagination\PageSize::queryParam());
    $perPageContext = $perPageContext ?: 'admin';

    $defaultPerPage = $perPageValue
        ?? ($paginator && method_exists($paginator, 'perPage') ? (int) $paginator->perPage() : \App\Support\Pagination\PageSize::contextDefault($perPageContext));

    $options = \App\Support\Pagination\PageSize::options(
        is_iterable($perPageOptions) && ! empty($perPageOptions)
            ? collect($perPageOptions)->all()
            : \App\Support\Pagination\PageSize::contextOptions($perPageContext),
        $defaultPerPage,
    );

    $shouldShowPerPage = is_bool($showPerPage)
        ? $showPerPage
        : ($paginator && count($options) > 1 && $mode !== 'none');

    $summaryToggle = $showSummary ?? true;

    $tableLabel = $ariaLabel ?: __('admin.table.default_label');
    $tableId = \Illuminate\Support\Str::slug($tableLabel) ?: 'admin-table';
@endphp

<flux:card {{ $attributes->class('space-y-4') }}>
    @isset($toolbar)
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 pb-3 dark:border-slate-800">
            {{ $toolbar }}
        </div>
    @endisset

    <x-ui.table
        :paginator="$paginator"
        :summary="$summary"
        :summary-key="$summaryKey"
        :show-summary="$summaryToggle"
        :page-size-options="$options"
        :page-size-param="$resolvedField"
        :per-page-mode="$mode"
        :per-page-field="$resolvedField"
        :per-page-value="$perPageValue ?? $defaultPerPage"
        :per-page-form-action="$perPageFormAction"
        :show-per-page="$shouldShowPerPage"
        :pagination-align="$align"
        :pagination-variant="$variant"
        :pagination-edge="$edge"
        :query="$query"
        aria-label="{{ $tableLabel }}"
        data-admin-table="true"
        data-admin-table-id="{{ $tableId }}"
        data-admin-table-label="{{ $tableLabel }}"
        wrapper-class="overflow-x-auto"
        table-class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800"
        head-class="bg-slate-50/70 dark:bg-slate-800/40"
        body-class="divide-y divide-slate-100 dark:divide-slate-800"
        footer-class="border-t border-slate-200 pt-4 dark:border-slate-800"
    >
        @isset($head)
            <x-slot name="head">
                {{ $head }}
            </x-slot>
        @endisset

        {{ $slot }}
    </x-ui.table>
</flux:card>
