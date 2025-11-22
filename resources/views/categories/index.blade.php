<x-app-layout>
    <x-slot name="title">
        {{ __('categories.title') }}
    </x-slot>

    <x-ui.page-header
        :title="__('categories.title')"
        :subtitle="__('categories.subtitle')"
    >
        <x-slot name="meta">
            <x-ui.badge>
                {{ trans_choice(__('categories.count_label'), $categories->total(), ['count' => $categories->total()]) }}
            </x-ui.badge>
        </x-slot>
    </x-ui.page-header>

    <x-ui.section class="space-y-6 pb-16">
        <x-auth-session-status :status="session('success')" />

        <div class="flex flex-wrap justify-between gap-3">
            <p class="text-sm text-slate-500 dark:text-slate-400">
                {{ __('categories.guidance') }}
            </p>
            <x-ui.button href="{{ route('categories.create') }}">
                {{ __('categories.create_button') }}
            </x-ui.button>
        </div>

        @if ($categories->count())
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($categories as $category)
                    <x-ui.surface
                        tag="article"
                        class="transition hover:-translate-y-0.5 hover:shadow-lg"
                    >
                        <header class="flex items-start justify-between gap-3">
                            <div>
                                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
                                    {{ $category->name }}
                                </h2>
                                <p class="text-xs uppercase tracking-wide text-slate-400 dark:text-slate-500">
                                    {{ trans_choice(__('categories.posts_count'), $category->posts_count, ['count' => $category->posts_count]) }}
                                </p>
                            </div>
                            <x-ui.badge variant="ghost" size="sm" :uppercase="false">
                                {{ $category->slug }}
                            </x-ui.badge>
                        </header>

                        @if ($category->description)
                            <p class="mt-4 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                                {{ \Illuminate\Support\Str::limit($category->description, 140) }}
                            </p>
                        @endif

                        <footer class="mt-6 flex items-center justify-between text-sm">
                            <x-ui.button href="{{ route('categories.show', $category) }}" variant="ghost">
                                {{ __('categories.view_posts') }}
                            </x-ui.button>

                            <div class="flex items-center gap-3 text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">
                                <a href="{{ route('categories.edit', $category) }}" class="hover:text-indigo-500 dark:hover:text-indigo-300">
                                    {{ __('categories.edit') }}
                                </a>
                                <form action="{{ route('categories.destroy', $category) }}" method="POST" onsubmit="return confirm('{{ __('categories.confirm_delete') }}');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="hover:text-rose-500 dark:hover:text-rose-300">
                                        {{ __('categories.delete') }}
                                    </button>
                                </form>
                            </div>
                        </footer>
                    </x-ui.surface>
                @endforeach
            </div>

            <x-ui.pagination
                class="mt-8"
                :paginator="$categories"
                summary-key="categories.pagination_summary"
                per-page-mode="http"
                per-page-field="{{ \App\Support\Pagination\PageSize::queryParam() }}"
                :per-page-options="$categoryPageSizeOptions ?? []"
                :show-per-page="filled($categoryPageSizeOptions ?? null)"
            />
        @else
            <x-ui.empty-state
                :title="__('categories.empty_title')"
                :description="__('categories.empty_subtitle')"
            >
                <x-ui.button href="{{ route('categories.create') }}">
                    {{ __('categories.create_button') }}
                </x-ui.button>
            </x-ui.empty-state>
        @endif
    </x-ui.section>
</x-app-layout>
