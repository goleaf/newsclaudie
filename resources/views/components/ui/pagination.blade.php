@props([
    'paginator' => null,
    'results' => null,
    'summary' => null,
    'summaryKey' => 'ui.pagination.summary',
    'showSummary' => true,
    'variant' => 'card',
    'align' => 'between',
    'edge' => 1,
    'perPageMode' => 'http',
    'perPageField' => 'per_page',
    'perPageValue' => null,
    'perPageOptions' => [],
    'perPageFormAction' => null,
    'query' => null,
    'showPerPage' => false,
    'ariaLabel' => null,
])

@php use Illuminate\Support\Str; @endphp

@php
    $collection = $results ?? $paginator;

    if (! $collection instanceof \Illuminate\Contracts\Pagination\Paginator) {
        return;
    }

    $edge = max(0, (int) $edge);
    $mode = $perPageMode ? strtolower((string) $perPageMode) : 'http';
    $mode = in_array($mode, ['http', 'livewire'], true) ? $mode : 'http';

    $options = collect($perPageOptions)
        ->map(fn ($option) => (int) $option)
        ->filter(fn ($option) => $option > 0)
        ->unique()
        ->values();

    $showPerPageControls = $showPerPage && $options->isNotEmpty() && $mode !== 'none';
    $hasPages = $collection->hasPages();
    $slotHasContent = isset($slot) && trim($slot) !== '';

    $summaryText = null;
    $summaryEnabled = (bool) $showSummary;

    if (is_bool($summary)) {
        $summaryEnabled = $summaryEnabled && $summary;
    } elseif (is_string($summary) && $summary !== '') {
        $summaryText = $summary;
        $summaryEnabled = true;
    }

    if ($summaryText === null && $summaryEnabled && method_exists($collection, 'firstItem') && $collection->firstItem() !== null) {
        $summaryText = __($summaryKey, [
            'from' => number_format($collection->firstItem()),
            'to' => number_format($collection->lastItem()),
            'total' => number_format(method_exists($collection, 'total') ? $collection->total() : $collection->count()),
        ]);
    }

    $currentPerPage = $perPageValue;

    if ($mode === 'http' && ($currentPerPage === null || $currentPerPage <= 0)) {
        $currentPerPage = (int) request()->input($perPageField, method_exists($collection, 'perPage') ? $collection->perPage() : null);
    }

    $queryParams = collect($query ?? request()->query())->except([$perPageField, 'page']);
    $hiddenInputs = [];

    $appendHiddenInput = static function (string $name, $value) use (&$appendHiddenInput, &$hiddenInputs): void {
        if (is_array($value)) {
            foreach ($value as $key => $nested) {
                $childName = is_int($key) ? "{$name}[]" : "{$name}[{$key}]";
                $appendHiddenInput($childName, $nested);
            }

            return;
        }

        if ($value === null || $value === '') {
            return;
        }

        $hiddenInputs[] = ['name' => $name, 'value' => $value];
    };

    foreach ($queryParams as $name => $value) {
        $appendHiddenInput($name, $value);
    }

    $selectId = Str::slug(($perPageField ?: 'per_page').'-'.spl_object_hash($collection));
    $ariaLabelText = $ariaLabel ?? trans('ui.pagination.aria_label');

    $variants = [
        'inline' => 'flex flex-col gap-3 rounded-2xl border border-slate-200/70 bg-white/70 p-4 text-sm text-slate-600 dark:border-slate-800/60 dark:bg-slate-900/60 dark:text-slate-300 sm:flex-row sm:items-center sm:justify-between',
        'plain' => 'flex flex-col gap-4 text-sm text-slate-600 dark:text-slate-300',
        'card' => 'mt-10 space-y-4 rounded-3xl border border-slate-200/80 bg-white/80 p-6 text-sm text-slate-600 shadow-sm dark:border-slate-800/70 dark:bg-slate-900/60 dark:text-slate-300',
    ];

    $containerClasses = $variants[$variant] ?? $variants['card'];

    $alignmentClasses = [
        'left' => ['summary' => 'sm:justify-start text-left', 'links' => 'sm:justify-start'],
        'center' => ['summary' => 'sm:justify-center text-center', 'links' => 'sm:justify-center'],
        'right' => ['summary' => 'sm:justify-end text-right', 'links' => 'sm:justify-end'],
        'between' => ['summary' => 'sm:justify-between text-left', 'links' => 'sm:justify-between'],
    ];

    $alignment = $alignmentClasses[strtolower((string) $align)] ?? $alignmentClasses['between'];
    $shouldRender = $summaryText || $showPerPageControls || $slotHasContent || $hasPages;
@endphp

@if ($shouldRender)
    <div {{ $attributes->class($containerClasses) }}>
        @if ($summaryText || $showPerPageControls)
            <div class="flex flex-col gap-2 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400 sm:flex-row sm:items-center {{ $alignment['summary'] }}">
                @if ($summaryText)
                    <p>{{ $summaryText }}</p>
                @endif

                @if ($showPerPageControls)
                    <div class="flex items-center gap-2">
                        <label for="{{ $selectId }}" class="sr-only">
                            {{ trans('ui.pagination.per_page') }}
                        </label>

                        @if ($mode === 'livewire')
                            <select
                                id="{{ $selectId }}"
                                wire:model.live="{{ $perPageField }}"
                                class="rounded-2xl border border-slate-200 bg-white/80 px-3 py-2 text-sm text-slate-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                            >
                                @foreach ($options as $option)
                                    <option value="{{ $option }}">{{ $option }}</option>
                                @endforeach
                            </select>
                        @else
                            <form
                                method="GET"
                                action="{{ $perPageFormAction ?? request()->url() }}"
                                class="inline-flex items-center gap-2"
                            >
                                @foreach ($hiddenInputs as $input)
                                    <input type="hidden" name="{{ $input['name'] }}" value="{{ $input['value'] }}">
                                @endforeach

                                <select
                                    id="{{ $selectId }}"
                                    name="{{ $perPageField }}"
                                    class="rounded-2xl border border-slate-200 bg-white/80 px-3 py-2 text-sm text-slate-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                                    onchange="this.form.submit()"
                                >
                                    @foreach ($options as $option)
                                        <option value="{{ $option }}" @selected($option === (int) $currentPerPage)>{{ $option }}</option>
                                    @endforeach
                                </select>
                            </form>
                        @endif
                    </div>
                @endif
            </div>
        @endif

        @if ($slotHasContent || $hasPages)
            <div class="flex flex-col gap-3 text-sm text-slate-600 dark:text-slate-300 sm:flex-row sm:items-center {{ $alignment['links'] }}">
                @if ($slotHasContent)
                    <div class="flex flex-wrap items-center gap-2">
                        {{ $slot }}
                    </div>
                @endif

                @if ($hasPages)
                    <nav aria-label="{{ $ariaLabelText }}" class="flex w-full justify-end sm:w-auto">
                        {{ method_exists($collection, 'onEachSide') ? $collection->onEachSide($edge)->links() : $collection->links() }}
                    </nav>
                @endif
            </div>
        @endif
    </div>
@endif

