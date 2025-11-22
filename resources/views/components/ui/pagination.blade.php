@props([
    'paginator' => null,
    'results' => null,
    'summary' => null,
    'summaryKey' => 'pagination.summary',
    'showSummary' => true,
    'variant' => 'card',
    'edge' => 1,
    'perPageMode' => null,
    'perPageField' => 'per_page',
    'perPageValue' => null,
    'perPageOptions' => [10, 20, 50],
    'perPageFormAction' => null,
    'perPageAnchor' => null,
    'query' => null,
    'showPerPage' => null,
    'align' => 'center',
])

@php use Illuminate\Support\Str; @endphp

@php
    $collection = $results ?? $paginator;

    if (! $collection instanceof \Illuminate\Contracts\Pagination\Paginator) {
        return;
    }

    $edge = max(0, (int) $edge);
    $mode = $perPageMode ? strtolower((string) $perPageMode) : null;

    $options = collect($perPageOptions)
        ->map(fn ($option) => (int) $option)
        ->filter(fn ($option) => $option > 0)
        ->unique()
        ->values();

    if ($options->isEmpty()) {
        $options = collect([10, 20, 50]);
    }

    $currentPerPage = $perPageValue;

    if ($currentPerPage === null && $mode) {
        $currentPerPage = $mode === 'livewire'
            ? (method_exists($collection, 'perPage') ? $collection->perPage() : $options->first())
            : (int) request()->input($perPageField, method_exists($collection, 'perPage') ? $collection->perPage() : $options->first());
    }

    if ($currentPerPage === null) {
        $currentPerPage = method_exists($collection, 'perPage') ? $collection->perPage() : $options->first();
    }

    $summaryText = null;

    if (is_string($summary)) {
        $summaryText = $summary;
    } elseif (is_bool($summary)) {
        $showSummary = $summary;
    }

    if ($summaryText === null && $showSummary && method_exists($collection, 'firstItem') && $collection->firstItem() !== null) {
        $summaryText = __($summaryKey, [
            'from' => number_format($collection->firstItem()),
            'to' => number_format($collection->lastItem()),
            'total' => number_format($collection->total()),
        ]);
    }

    $queryParams = collect($query ?? request()->query())->except([$perPageField, 'page']);
    $hiddenInputs = [];

    $pushHiddenInput = static function (string $name, $value) use (&$pushHiddenInput, &$hiddenInputs): void {
        if (is_array($value)) {
            foreach ($value as $key => $nested) {
                $childName = sprintf('%s[%s]', $name, $key);
                $pushHiddenInput($childName, $nested);
            }

            return;
        }

        if ($value === null || $value === '') {
            return;
        }

        $hiddenInputs[] = ['name' => $name, 'value' => $value];
    };

    foreach ($queryParams as $name => $value) {
        $pushHiddenInput($name, $value);
    }

    $shouldShowPerPage = match (true) {
        $showPerPage === true => $options->isNotEmpty(),
        $showPerPage === false => false,
        default => $mode !== null && $options->isNotEmpty(),
    };

    $fieldId = Str::slug($perPageField ?: 'per_page').'-select';
    $hasPages = $collection->hasPages();
    $hasItems = $collection->count() > 0;

    if (! $hasItems && ! $hasPages) {
        return;
    }

    $perPageAnchor = $perPageAnchor ? ltrim((string) $perPageAnchor, '#') : null;
    $formAction = $perPageFormAction ?? request()->url();

    if ($perPageAnchor) {
        $formAction .= '#'.$perPageAnchor;
    }

    $variants = [
        'inline' => 'flex flex-col gap-3 rounded-2xl border border-slate-200/70 bg-white/70 p-4 text-sm text-slate-600 dark:border-slate-800/60 dark:bg-slate-900/60 dark:text-slate-300 sm:flex-row sm:items-center sm:justify-between',
        'plain' => 'space-y-3 text-sm text-slate-600 dark:text-slate-300',
        'card' => 'mt-10 space-y-4 rounded-3xl border border-slate-200/80 bg-white/80 p-6 text-sm text-slate-600 shadow-sm dark:border-slate-800/70 dark:bg-slate-900/60 dark:text-slate-300',
    ];

    $containerClasses = $variants[$variant] ?? $variants['card'];

    $alignmentClasses = [
        'left' => ['summary' => 'text-left', 'controls' => 'justify-start'],
        'center' => ['summary' => 'text-center', 'controls' => 'justify-center'],
        'right' => ['summary' => 'text-right', 'controls' => 'justify-end'],
    ];

    $alignment = $alignmentClasses[strtolower((string) $align)] ?? $alignmentClasses['center'];
@endphp

<div {{ $attributes->class($containerClasses) }}>
    @if ($summaryText)
        <p class="text-sm text-slate-600 dark:text-slate-300 {{ $alignment['summary'] }}">
            {{ $summaryText }}
        </p>
    @endif

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        @if ($shouldShowPerPage)
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                <label for="{{ $fieldId }}" class="sr-only">
                    {{ __('ui.pagination.per_page_label') }}
                </label>

                @if ($mode === 'livewire')
                    <select
                        id="{{ $fieldId }}"
                        wire:model.live="{{ $perPageField }}"
                        class="rounded-2xl border border-slate-200 bg-white/80 px-3 py-2 text-sm text-slate-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                    >
                        @foreach ($options as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                @else
                    <form method="GET" action="{{ $formAction }}" class="inline-flex items-center gap-2">
                        @foreach ($hiddenInputs as $input)
                            <input type="hidden" name="{{ $input['name'] }}" value="{{ $input['value'] }}">
                        @endforeach

                        <select
                            id="{{ $fieldId }}"
                            name="{{ $perPageField }}"
                            class="rounded-2xl border border-slate-200 bg-white/80 px-3 py-2 text-sm text-slate-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                            onchange="this.form.submit()"
                        >
                            @foreach ($options as $option)
                                <option value="{{ $option }}" @selected($option === (int) $currentPerPage)>
                                    {{ $option }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                @endif
            </div>
        @endif

        @if ($hasPages)
            <div class="flex flex-wrap gap-2 {{ $alignment['controls'] }} text-slate-600 dark:text-slate-300">
                {{ method_exists($collection, 'onEachSide') ? $collection->onEachSide($edge)->links() : $collection->links() }}
            </div>
        @endif
    </div>
</div>
