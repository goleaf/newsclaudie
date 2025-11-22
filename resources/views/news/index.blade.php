<x-app-layout>
    <x-slot name="title">
        {{ __('News') }}
    </x-slot>

    <x-ui.page-header
        :title="__('News')"
        :subtitle="__('Browse our latest news and updates')"
        kicker="{{ config('app.name') }}"
    />

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="lg:grid lg:grid-cols-12 lg:gap-8">
            <!-- Filter Panel (Sidebar on desktop) -->
            <aside class="lg:col-span-3">
                <x-news.filter-panel
                    :categories="$categories"
                    :authors="$authors"
                    :appliedFilters="$appliedFilters"
                />
            </aside>

            <!-- Main Content -->
            <div class="mt-8 lg:col-span-9 lg:mt-0">
                <!-- Results Count -->
                <div class="mb-6 flex items-center justify-between">
                    <p class="text-sm text-slate-600 dark:text-slate-400">
                        @if ($totalCount === 0)
                            {{ __('0 results found') }}
                        @elseif ($posts->total() === 1)
                            {{ __('1 result found') }}
                        @else
                            @if ($posts->hasPages())
                                {{ __('Showing :from-:to of :total results', [
                                    'from' => $posts->firstItem(),
                                    'to' => $posts->lastItem(),
                                    'total' => number_format($posts->total())
                                ]) }}
                            @else
                                {{ __(':total results found', ['total' => number_format($posts->total())]) }}
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
                                {{ __('No results found') }}
                            </h3>
                            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                                {{ __('Try adjusting your filters to find what you\'re looking for.') }}
                            </p>
                        </div>
                    </x-ui.card>
                @else
                    <!-- News Items Grid -->
                    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3">
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
