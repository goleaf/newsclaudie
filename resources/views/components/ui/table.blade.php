@props([
    'paginator' => null,
    'summary' => true,
    'summaryKey' => 'ui.pagination.summary',
    'showSummary' => true,
    'pageSizeOptions' => [],
    'pageSizeParam' => \App\Support\Pagination\PageSize::queryParam(),
    'perPageMode' => 'http',
    'perPageField' => null,
    'perPageValue' => null,
    'perPageFormAction' => null,
    'paginationVariant' => 'plain',
    'paginationAlign' => 'between',
    'paginationEdge' => 1,
    'paginationClass' => '',
    'wrapperClass' => '',
    'tableClass' => '',
    'headClass' => '',
    'bodyClass' => '',
    'footerClass' => '',
    'showPerPage' => null,
    'query' => null,
    'ariaLabel' => null,
])

@php
    $paginatorInstance = $paginator instanceof \Illuminate\Contracts\Pagination\Paginator ? $paginator : null;
    $mode = $perPageMode ? strtolower((string) $perPageMode) : 'http';
    $mode = in_array($mode, ['http', 'livewire'], true) ? $mode : 'http';
    $resolvedPerPageField = $perPageField ?: $pageSizeParam;
    $shouldShowPerPage = $showPerPage ?? ($mode !== 'none' && ! empty($pageSizeOptions));

    $summaryToggle = $showSummary ?? true;
    $summaryEnabled = $summaryToggle;

    if (is_bool($summary)) {
        $summaryEnabled = $summaryToggle && $summary;
    } elseif (is_string($summary) && $summary !== '') {
        $summaryEnabled = true;
    }

    $hasPages = $paginatorInstance && method_exists($paginatorInstance, 'hasPages')
        ? $paginatorInstance->hasPages()
        : false;
    $shouldRenderPagination = $paginatorInstance && ($summaryEnabled || $shouldShowPerPage || $hasPages);
@endphp

@php
    $tableClasses = trim($tableClass);
    $headClasses = trim($headClass);
    $bodyClasses = trim($bodyClass);
    $paginationClasses = trim($paginationClass);
@endphp

<div {{ $attributes->class($wrapperClass) }}>
    <table @class([$tableClasses => $tableClasses !== ''])>
        @isset($head)
            <thead @class([$headClasses => $headClasses !== ''])>
                {{ $head }}
            </thead>
        @endisset

        <tbody @class([$bodyClasses => $bodyClasses !== ''])>
            {{ $slot }}
        </tbody>
    </table>

    @if ($shouldRenderPagination)
        @if ($footerClass)
            <div class="{{ $footerClass }}">
                <x-ui.pagination
                    class="{{ $paginationClasses }}"
                    :paginator="$paginatorInstance"
                    :summary="$summary"
                    :summary-key="$summaryKey"
                    :show-summary="$summaryToggle"
                    :show-per-page="$shouldShowPerPage"
                    :per-page-mode="$mode"
                    :per-page-field="$resolvedPerPageField"
                    :per-page-value="$perPageValue"
                    :per-page-options="$pageSizeOptions"
                    :per-page-form-action="$perPageFormAction"
                    :query="$query"
                    :edge="$paginationEdge"
                    :aria-label="$ariaLabel"
                    :align="$paginationAlign"
                    :variant="$paginationVariant"
                />
            </div>
        @else
            <x-ui.pagination
                class="{{ $paginationClasses }}"
                :paginator="$paginatorInstance"
                :summary="$summary"
                :summary-key="$summaryKey"
                :show-summary="$summaryToggle"
                :show-per-page="$shouldShowPerPage"
                :per-page-mode="$mode"
                :per-page-field="$resolvedPerPageField"
                :per-page-value="$perPageValue"
                :per-page-options="$pageSizeOptions"
                :per-page-form-action="$perPageFormAction"
                :query="$query"
                :edge="$paginationEdge"
                :aria-label="$ariaLabel"
                :align="$paginationAlign"
                :variant="$paginationVariant"
            />
        @endif
    @endif
</div>
