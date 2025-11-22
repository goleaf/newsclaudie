@php
    /** @var \Illuminate\Pagination\LengthAwarePaginator|null $paginator */
    $paginator = $paginator instanceof \Illuminate\Contracts\Pagination\Paginator ? $paginator : null;
    $pageName = $paginator?->getPageName() ?? 'page';

    $elements = [];

    if ($paginator instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator) {
        $window = \Illuminate\Pagination\UrlWindow::make($paginator);

        $elements = array_filter([
            $window['first'],
            is_array($window['slider']) ? '...' : null,
            $window['slider'],
            is_array($window['last']) ? '...' : null,
            $window['last'],
        ]);
    }
@endphp

@if ($paginator?->hasPages())
    @if (! empty($elements))
        <nav aria-label="{{ $ariaLabel ?? __('pagination.aria_label') }}" class="flex w-full justify-end">
            <div class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white/80 px-2 py-1 shadow-sm dark:border-slate-800 dark:bg-slate-900/70">
                {{-- Previous Page Link --}}
                @if ($paginator->onFirstPage())
                    <span class="inline-flex items-center rounded-xl px-3 py-2 text-sm text-slate-400" aria-disabled="true" aria-label="@lang('pagination.previous')">
                        &lsaquo;
                    </span>
                @else
                    <button
                        type="button"
                        class="inline-flex items-center rounded-xl px-3 py-2 text-sm text-slate-700 transition hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800"
                        wire:click="previousPage('{{ $pageName }}')"
                        wire:loading.attr="disabled"
                        rel="prev"
                        aria-label="@lang('pagination.previous')"
                    >
                        &lsaquo;
                    </button>
                @endif

                {{-- Pagination Elements --}}
                @foreach ($elements as $element)
                    {{-- "Three Dots" Separator --}}
                    @if (is_string($element))
                        <span class="px-2 text-sm text-slate-400">…</span>
                    @endif

                    {{-- Array Of Links --}}
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span class="inline-flex items-center rounded-xl bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm dark:bg-indigo-500" aria-current="page">
                                    {{ $page }}
                                </span>
                            @else
                                <button
                                    type="button"
                                    class="inline-flex items-center rounded-xl px-3 py-2 text-sm text-slate-700 transition hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800"
                                    wire:click="gotoPage({{ $page }}, '{{ $pageName }}')"
                                    aria-label="{{ __('Page :page', ['page' => $page]) }}"
                                    wire:key="paginator-{{ $pageName }}-page{{ $page }}"
                                >
                                    {{ $page }}
                                </button>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Next Page Link --}}
                @if ($paginator->hasMorePages())
                    <button
                        type="button"
                        class="inline-flex items-center rounded-xl px-3 py-2 text-sm text-slate-700 transition hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800"
                        wire:click="nextPage('{{ $pageName }}')"
                        wire:loading.attr="disabled"
                        rel="next"
                        aria-label="@lang('pagination.next')"
                    >
                        &rsaquo;
                    </button>
                @else
                    <span class="inline-flex items-center rounded-xl px-3 py-2 text-sm text-slate-400" aria-disabled="true" aria-label="@lang('pagination.next')">
                        &rsaquo;
                    </span>
                @endif
            </div>
        </nav>
    @else
        <nav aria-label="{{ $ariaLabel ?? __('pagination.aria_label') }}" class="flex w-full justify-end">
            {{ $paginator->links() }}
        </nav>
    @endif
@endif
