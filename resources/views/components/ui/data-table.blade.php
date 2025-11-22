@props([
    'paginator' => null,
    'summary' => true,
    'summaryKey' => 'ui.pagination.summary',
    'pageSizeOptions' => [],
    'pageSizeParam' => 'per_page',
    'tableClass' => 'min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800',
    'bodyClass' => 'divide-y divide-slate-100 dark:divide-slate-800/80',
    'wrapperClass' => 'overflow-hidden rounded-3xl border border-slate-200/70 dark:border-slate-800/60',
])

<div {{ $attributes->class($wrapperClass) }}>
    <table class="{{ $tableClass }}">
        @isset($head)
            <thead class="bg-slate-50/70 dark:bg-slate-800/40">
                {{ $head }}
            </thead>
        @endisset

        <tbody class="{{ $bodyClass }}">
            {{ $slot }}
        </tbody>
    </table>

    <x-ui.pagination
        :paginator="$paginator"
        :summary="$summary"
        :summary-key="$summaryKey"
        :show-per-page="! empty($pageSizeOptions)"
        :per-page-options="$pageSizeOptions"
        :per-page-field="$pageSizeParam"
        variant="plain"
        class="border-t border-slate-100/70 bg-white/50 px-3 py-4 text-sm text-slate-600 dark:border-slate-800/80 dark:bg-slate-900/40 dark:text-slate-300"
    />
</div>
