<x-app-layout>
    <x-slot name="title">
        {{ $title ?? __('posts.title') }}
    </x-slot>

    @php
        $isFiltered = isset($filter);
        $subtitle = $isFiltered ? $filter : __('posts.subtitle_latest');
        $countLabel = trans_choice('posts.count_label', $posts->total(), ['count' => $posts->total()]);
        $categories = $categories ?? collect();
        $activeCategory = $activeCategory ?? null;
        $perPageOptions = $postPageSizeOptions ?? [12, 18, 24, 36];
        $perPage = (int) ($perPage ?? ($perPageOptions[0] ?? 12));
        $perPageParam = $perPageParam ?? \App\Support\Pagination\PageSize::queryParam();
    @endphp

    <x-ui.page-header
        :title="__('posts.title')"
        :subtitle="$subtitle"
    >
        <x-slot name="meta">
            <x-ui.badge>{{ $countLabel }}</x-ui.badge>
        </x-slot>
    </x-ui.page-header>

    <x-ui.section class="pb-16">
        @if ($categories->isNotEmpty())
            <x-ui.surface tag="section" class="mb-10 space-y-4 dark:text-slate-100">
                <form action="{{ route('posts.index') }}" method="GET" class="flex flex-col gap-4 md:flex-row md:items-end">
                    <input type="hidden" name="{{ $perPageParam }}" value="{{ $perPage }}">
                    <div class="flex-1">
                        <label for="category-filter" class="text-sm font-semibold text-slate-600 dark:text-slate-300">
                            {{ __('posts.category_filter_label') }}
                        </label>
                        <select
                            id="category-filter"
                            name="category"
                            class="mt-2 block w-full rounded-2xl border-slate-200 bg-white/80 px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                        >
                            <option value="">{{ __('posts.category_filter_placeholder') }}</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->slug }}" @selected($activeCategory?->is($category))>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex gap-3">
                        <x-ui.button type="submit">
                            {{ __('posts.category_filter_apply') }}
                        </x-ui.button>
                        @if ($activeCategory)
                            <x-ui.button href="{{ route('posts.index', [$perPageParam => $perPage]) }}" variant="secondary">
                                {{ __('posts.clear_filters') }}
                            </x-ui.button>
                        @endif
                    </div>
                </form>
            </x-ui.surface>
        @endif

        @if ($posts->count())
            <div class="grid gap-6 lg:grid-cols-2 xl:grid-cols-3">
                @foreach ($posts as $post)
                    <x-post-card :post="$post" />
                @endforeach
            </div>

            <x-ui.pagination
                :paginator="$posts"
                per-page-mode="http"
                per-page-field="{{ $perPageParam }}"
                :per-page-value="$perPage"
                :per-page-options="$perPageOptions"
                :summary="trans('posts.pagination_summary', [
                    'from' => $posts->firstItem() ?? 0,
                    'to' => $posts->lastItem() ?? 0,
                    'total' => $posts->total(),
                ])"
                class="mt-10"
            />
        @else
            <x-ui.empty-state
                :title="__('posts.no_posts_found')"
                :description="$isFiltered ? __('posts.reset_filters_hint') : __('posts.empty_default')"
            >
                @if ($isFiltered)
                    <x-ui.button href="{{ route('posts.index', [$perPageParam => $perPage]) }}" variant="secondary">
                        {{ __('posts.clear_filters') }}
                    </x-ui.button>
                @endif
                <x-ui.button href="{{ route('home') }}">
                    {{ __('posts.go_home') }}
                </x-ui.button>
            </x-ui.empty-state>
        @endif
    </x-ui.section>
</x-app-layout>