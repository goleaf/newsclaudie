<x-app-layout>
    <x-slot name="title">
        {{ $category->name }} — {{ __('categories.title') }}
    </x-slot>

    <x-ui.page-header
        :title="$category->name"
        :subtitle="$category->description ?? __('categories.show.subtitle')"
    >
        <x-slot name="meta">
            <x-ui.badge variant="info">
                {{ trans_choice(__('categories.show.count'), $posts->total(), ['count' => $posts->total()]) }}
            </x-ui.badge>
        </x-slot>
    </x-ui.page-header>

    <x-ui.section class="pb-16">
        @if ($posts->count())
            <div class="flex flex-wrap justify-start gap-6">
                @foreach ($posts as $post)
                    <x-post-card :post="$post" />
                @endforeach
            </div>

            <x-ui.pagination
                class="mt-8"
                :paginator="$posts"
                summary-key="categories.show.pagination_summary"
                per-page-mode="http"
                per-page-field="{{ \App\Support\Pagination\PageSize::queryParam() }}"
                :per-page-options="$categoryPostPageSizeOptions ?? []"
                :show-per-page="filled($categoryPostPageSizeOptions ?? null)"
            />
        @else
            <x-ui.empty-state
                :title="__('categories.show.empty_title')"
                :description="__('categories.show.empty_subtitle')"
            >
                <x-ui.button href="{{ route('categories.index') }}" variant="secondary">
                    {{ __('categories.show.view_all') }}
                </x-ui.button>
            </x-ui.empty-state>
        @endif
    </x-ui.section>
</x-app-layout>
