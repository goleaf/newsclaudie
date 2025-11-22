<?php

use App\Livewire\Concerns\ManagesPerPage;
use App\Livewire\Concerns\ManagesSearch;
use App\Models\Category;
use App\Support\Pagination\PageSize;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use function Livewire\Volt\layout;
use function Livewire\Volt\title;

layout('layouts.app');
title(__('categories.title'));

new class extends Component {
    use WithPagination;
    use ManagesPerPage;
    use ManagesSearch;

    public ?string $statusMessage = null;
    public bool $canManage = false;

    protected array $queryString = [
        'page' => ['except' => 1],
    ];

    protected function perPageContext(): string
    {
        return 'categories';
    }

    public function mount(): void
    {
        $this->canManage = auth()->user()?->can('access-admin') ?? false;
        $this->statusMessage = session('success');
        $this->queryString['perPage'] = ['as' => PageSize::queryParam(), 'except' => null];

        $this->perPage = $this->sanitizePerPage(
            $this->perPage ?: $this->defaultPerPage()
        );
    }

    public function deleteCategory(int $categoryId): void
    {
        Gate::authorize('access-admin');

        Category::query()->findOrFail($categoryId)->delete();

        $this->statusMessage = __('messages.category_deleted');
        session()->flash('success', $this->statusMessage);

        $this->resetPage();
    }

    public function with(): array
    {
        $searchTerm = trim((string) $this->search);

        $query = Category::query()->withCount('posts');
        
        // Apply search if present
        if ($searchTerm !== '') {
            $query->where(function ($builder) use ($searchTerm) {
                $builder->where('name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('slug', 'like', '%' . $searchTerm . '%')
                    ->orWhere('description', 'like', '%' . $searchTerm . '%');
            });
        }

        $categories = $query
            ->orderBy('name')
            ->paginate($this->perPage)
            ->withQueryString();

        $filterLabel = $searchTerm !== ''
            ? __('categories.search_active', ['term' => $searchTerm])
            : null;

        return [
            'categories' => $categories,
            'countLabel' => trans_choice('categories.count_label', $categories->total(), ['count' => $categories->total()]),
            'filterLabel' => $filterLabel,
            'searchTerm' => $searchTerm,
        ];
    }
}; ?>

<div class="space-y-8">
    <x-ui.page-header
        :title="__('categories.title')"
        :subtitle="__('categories.subtitle')"
    >
        <x-slot name="meta">
            <x-ui.badge>
                {{ $countLabel }}
            </x-ui.badge>
        </x-slot>
    </x-ui.page-header>

    <x-ui.section class="space-y-6 pb-16">
        @if ($statusMessage)
            <x-auth-session-status :status="$statusMessage" />
        @endif

        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div class="flex flex-1 items-center gap-3">
                <div class="relative w-full md:w-80">
                    <input
                        type="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('categories.search_placeholder') }}"
                        class="block w-full rounded-2xl border border-slate-200 bg-white/80 px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                    >
                    @if ($searchTerm !== '')
                        <button
                            type="button"
                            wire:click="$set('search', '')"
                            class="absolute inset-y-0 right-3 flex items-center text-slate-400 transition hover:text-slate-600 dark:hover:text-slate-200"
                            aria-label="{{ __('categories.clear_filters') }}"
                        >
                            &times;
                        </button>
                    @endif
                </div>
                @if ($filterLabel)
                    <x-ui.badge variant="info" :uppercase="false">
                        {{ $filterLabel }}
                    </x-ui.badge>
                @endif
            </div>
            @if ($canManage)
                <x-ui.button href="{{ route('categories.create') }}">
                    {{ __('categories.create_button') }}
                </x-ui.button>
            @endif
        </div>

        <p class="text-sm text-slate-500 dark:text-slate-400">
            {{ __('categories.guidance') }}
        </p>

        @if ($categories->count())
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($categories as $category)
                    <x-ui.surface
                        tag="article"
                        class="transition hover:-translate-y-0.5 hover:shadow-lg"
                        wire:key="category-{{ $category->id }}"
                    >
                        <header class="flex items-start justify-between gap-3">
                            <div>
                                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
                                    {{ $category->name }}
                                </h2>
                                <p class="text-xs uppercase tracking-wide text-slate-400 dark:text-slate-500">
                                    {{ trans_choice('categories.posts_count', $category->posts_count, ['count' => $category->posts_count]) }}
                                </p>
                            </div>
                            <x-ui.badge variant="ghost" size="sm" :uppercase="false">
                                {{ $category->slug }}
                            </x-ui.badge>
                        </header>

                        @if ($category->description)
                            <p class="mt-4 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                                {{ Str::limit($category->description, 140) }}
                            </p>
                        @endif

                        <footer class="mt-6 flex items-center justify-between text-sm">
                            <x-ui.button href="{{ route('categories.show', $category) }}" variant="ghost">
                                {{ __('categories.view_posts') }}
                            </x-ui.button>

                            @if ($canManage)
                                <div class="flex items-center gap-3 text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">
                                    <a href="{{ route('categories.edit', $category) }}" class="hover:text-indigo-500 dark:hover:text-indigo-300">
                                        {{ __('categories.edit') }}
                                    </a>
                                    <button
                                        type="button"
                                        class="hover:text-rose-500 dark:hover:text-rose-300"
                                        wire:click="deleteCategory({{ $category->id }})"
                                        wire:confirm="{{ __('categories.confirm_delete') }}"
                                    >
                                        {{ __('categories.delete') }}
                                    </button>
                                </div>
                            @endif
                        </footer>
                    </x-ui.surface>
                @endforeach
            </div>

            <x-ui.pagination
                class="mt-8"
                :paginator="$categories"
                per-page-mode="livewire"
                per-page-field="perPage"
                :per-page-value="$perPage"
                :per-page-options="$this->perPageOptions"
                :show-per-page="count($this->perPageOptions) > 1"
            />
        @else
            <x-ui.empty-state
                :title="__('categories.empty_title')"
                :description="$searchTerm !== '' ? __('categories.search_empty_subtitle') : __('categories.empty_subtitle')"
            >
                @if ($canManage)
                    <x-ui.button href="{{ route('categories.create') }}">
                        {{ __('categories.create_button') }}
                    </x-ui.button>
                @endif
            </x-ui.empty-state>
        @endif
    </x-ui.section>
</div>
