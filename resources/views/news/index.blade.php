<x-app-layout>
    <x-slot name="title">
        {{ __('news.title') }}
    </x-slot>

    <x-ui.page-header
        :title="__('news.title')"
        :subtitle="__('news.subtitle')"
        kicker="{{ config('app.name') }}"
    />

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="md:grid md:grid-cols-12 md:gap-6 lg:gap-8">
            <!-- Filter Panel (Sidebar on tablet/desktop) -->
            <aside class="md:col-span-4 lg:col-span-3">
                <div class="lg:sticky lg:top-8">
                    <x-news.filter-panel
                        :categories="$categories"
                        :authors="$authors"
                        :appliedFilters="$appliedFilters"
                    />
                </div>
            </aside>

            <!-- Main Content -->
            <div class="mt-6 md:col-span-8 md:mt-0 lg:col-span-9">
                <!-- Results Count -->
                <div class="mb-6 flex items-center justify-between">
                    <p class="text-sm text-slate-600 dark:text-slate-400">
                        @if ($totalCount === 0)
                            {{ trans_choice('news.results_count', 0) }}
                        @else
                            @if ($posts->hasPages())
                                {{ __('news.showing_range', [
                                    'from' => $posts->firstItem(),
                                    'to' => $posts->lastItem(),
                                    'total' => number_format($posts->total())
                                ]) }}
                            @else
                                {{ trans_choice('news.results_count', $posts->total(), ['count' => number_format($posts->total())]) }}
                            @endif
                        @endif
                    </p>
                </div>

                @if ($posts->isEmpty())
                    <!-- Empty State -->
                    <x-ui.card class="text-center">
                        <div class="py-12">
                            <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <h3 class="mt-4 text-lg font-semibold text-slate-900 dark:text-white">
                                {{ __('news.empty_title') }}
                            </h3>
                            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                                {{ __('news.empty_message') }}
                            </p>
                        </div>
                    </x-ui.card>
                @else
                    <!-- News Items Grid -->
                    <div class="grid gap-6 grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3">
                        @foreach ($posts as $post)
                            <x-news.news-card :post="$post" />
                        @endforeach
                    </div>

                    <!-- Pagination -->
                    @if ($posts->hasPages())
                        <div class="mt-8">
                            {{ $posts->links() }}
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
