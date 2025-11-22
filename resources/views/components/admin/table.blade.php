@props([
    'pagination' => null,
    'perPageMode' => null,
    'perPageField' => 'per_page',
    'perPageValue' => null,
    'perPageOptions' => [10, 20, 50],
    'summary' => null,
])

@php
    $hasPagination = $pagination && method_exists($pagination, 'links');
    $summaryText = $summary;

    if ($hasPagination && ! $summaryText && method_exists($pagination, 'total')) {
        $summaryText = trans('pagination.summary', [
            'from' => $pagination->firstItem(),
            'to' => $pagination->lastItem(),
            'total' => $pagination->total(),
        ]);
    }

    $showPerPage = filled($perPageMode);
@endphp

<flux:card {{ $attributes->class('space-y-4') }}>
    @isset($toolbar)
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 pb-3 dark:border-slate-800">
            {{ $toolbar }}
        </div>
    @endisset

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
            @isset($head)
                <thead>
                    {{ $head }}
                </thead>
            @endisset

            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                {{ $slot }}
            </tbody>
        </table>
    </div>

    @if ($hasPagination)
        <div class="border-t border-slate-200 pt-4 dark:border-slate-800">
            <x-ui.pagination
                :paginator="$pagination"
                :summary="$summaryText"
                :per-page-mode="$perPageMode"
                :per-page-field="$perPageField"
                :per-page-value="$perPageValue"
                :per-page-options="$perPageOptions"
                :show-per-page="$showPerPage"
                variant="plain"
                align="left"
            />
        </div>
    @endif
</flux:card>

