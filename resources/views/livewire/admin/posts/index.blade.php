<?php

use App\Livewire\Concerns\ManagesPerPage;
use App\Models\Post;
use App\Models\User;
use App\Scopes\PublishedScope;
use App\Support\Pagination\PageSize;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use function Livewire\Volt\layout;
use function Livewire\Volt\title;

layout('components.layouts.admin');
title(__('admin.posts.title'));

new class extends Component {
    use AuthorizesRequests;
    use ManagesPerPage;
    use WithPagination;

    public ?string $search = '';
    public ?string $status = null;
    public ?int $author = null;
    public string $sortField = 'updated_at';
    public string $sortDirection = 'desc';
    public array $selectedPosts = [];
    public bool $selectPage = false;
    public array $currentPageIds = [];
    public ?array $bulkFeedback = null;

    protected $listeners = ['post-updated' => '$refresh', 'post-deleted' => '$refresh'];
    protected $queryString = [
        'perPage' => ['except' => PageSize::contextDefault('admin')],
        'search' => ['except' => ''],
        'status' => ['except' => null],
        'author' => ['except' => null],
        'sortField' => ['as' => 'sort', 'except' => 'updated_at'],
        'sortDirection' => ['as' => 'direction', 'except' => 'desc'],
        'page' => ['except' => 1],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingAuthor(): void
    {
        $this->resetPage();
    }

    public function updatingSortField(): void
    {
        $this->resetPage();
    }

    public function updatingSortDirection(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        $field = $this->sanitizeSortField($field);

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function updatedStatus($value): void
    {
        $this->status = $this->sanitizeStatus($value);
    }

    public function updatedAuthor($value): void
    {
        $this->author = $value !== null && $value !== ''
            ? (int) $value
            : null;
    }

    public function updatedSearch(?string $value): void
    {
        $this->search = $value !== null ? trim($value) : '';
    }

    public function clearFilters(): void
    {
        $this->resetPage();
        $this->search = '';
        $this->status = null;
        $this->author = null;
    }

    public function with(): array
    {
        $filters = $this->resolvedFilters();

        [$sortField, $sortDirection] = $this->resolvedSort();

        $posts = $this->baseQuery($filters)
            ->orderBy($sortField, $sortDirection)
            ->orderByDesc('id')
            ->paginate($this->perPage)
            ->withQueryString();

        $this->currentPageIds = $posts->pluck('id')->map(fn ($id) => (int) $id)->all();
        $this->selectPage = $this->areAllCurrentPageItemsSelected();

        return [
            'posts' => $posts,
            'authors' => $this->authors(),
            'searchTerm' => $filters['search'],
            'activeStatus' => $filters['status'],
            'activeAuthor' => $filters['author'],
            'isFiltered' => $filters['search'] !== '' || $filters['status'] !== null || $filters['author'] !== null,
        ];
    }

    public function updatedSelectedPosts(): void
    {
        $this->selectedPosts = collect($this->selectedPosts)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $this->selectPage = $this->areAllCurrentPageItemsSelected();
    }

    public function updatedSelectPage(bool $checked): void
    {
        if ($checked) {
            $this->selectCurrentPage();
        } else {
            $this->deselectCurrentPage();
        }
    }

    public function clearSelection(): void
    {
        $this->selectedPosts = [];
        $this->selectPage = false;
    }

    public function bulkPublish(): void
    {
        $this->processBulkAction('publish');
    }

    public function bulkUnpublish(): void
    {
        $this->processBulkAction('unpublish');
    }

    private function baseQuery(?array $filters = null): Builder
    {
        $filters ??= $this->resolvedFilters();

        return Post::query()
            ->withoutGlobalScope('order')
            ->withoutGlobalScope(PublishedScope::class)
            ->with(['author'])
            ->withCount([
                'comments as comments_count' => fn ($query) => $query->approved(),
            ])
            ->when($filters['search'] !== '', function ($query) use ($filters) {
                $query->where(function ($inner) use ($filters) {
                    $inner->where('title', 'like', '%'.$filters['search'].'%')
                        ->orWhere('slug', 'like', '%'.$filters['search'].'%');
                });
            })
            ->when($filters['status'] === 'published', fn ($query) => $query->whereNotNull('published_at'))
            ->when($filters['status'] === 'draft', fn ($query) => $query->whereNull('published_at'))
            ->when($filters['author'] !== null, fn ($query) => $query->where('user_id', $filters['author']));
    }

    private function resolvedFilters(): array
    {
        return [
            'search' => trim((string) $this->search),
            'status' => $this->sanitizeStatus($this->status),
            'author' => $this->author !== null ? (int) $this->author : null,
        ];
    }

    private function resolvedSort(): array
    {
        $field = $this->sanitizeSortField($this->sortField);
        $direction = $this->sanitizeSortDirection($this->sortDirection);

        $this->sortField = $field;
        $this->sortDirection = $direction;

        return [$field, $direction];
    }

    private function authors()
    {
        return User::query()
            ->whereHas('posts')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function selectCurrentPage(): void
    {
        $this->selectedPosts = collect($this->selectedPosts)
            ->merge($this->currentPageIds)
            ->unique()
            ->values()
            ->all();
    }

    private function deselectCurrentPage(): void
    {
        $this->selectedPosts = collect($this->selectedPosts)
            ->reject(fn ($id) => in_array((int) $id, $this->currentPageIds, true))
            ->values()
            ->all();
    }

    private function areAllCurrentPageItemsSelected(): bool
    {
        if (empty($this->currentPageIds)) {
            return false;
        }

        $selected = collect($this->selectedPosts)->map(fn ($id) => (int) $id)->unique();

        return $selected->intersect($this->currentPageIds)->count() === count($this->currentPageIds);
    }

    private function processBulkAction(string $action): void
    {
        $selectedIds = collect($this->selectedPosts)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($selectedIds->isEmpty()) {
            return;
        }

        $posts = $this->baseQuery()
            ->whereIn('id', $selectedIds)
            ->get()
            ->keyBy('id');

        $updated = 0;
        $failures = [];

        foreach ($selectedIds as $postId) {
            $post = $posts->get($postId);

            if (! $post) {
                $failures[] = [
                    'id' => $postId,
                    'title' => "#{$postId}",
                    'reason' => __('admin.posts.bulk_not_found'),
                ];

                continue;
            }

            try {
                $this->authorize('update', $post);
            } catch (\Throwable $exception) {
                $failures[] = [
                    'id' => $postId,
                    'title' => $post->title,
                    'reason' => __('admin.posts.bulk_unauthorized'),
                ];

                continue;
            }

            if ($action === 'publish') {
                if (! $post->isPublished()) {
                    $post->forceFill(['published_at' => now()])->save();
                    $updated++;
                }
            } else {
                if ($post->isPublished()) {
                    $post->forceFill(['published_at' => null])->save();
                    $updated++;
                }
            }
        }

        $this->bulkFeedback = [
            'status' => empty($failures) ? 'success' : 'warning',
            'action' => $action,
            'attempted' => $selectedIds->count(),
            'updated' => $updated,
            'failures' => $failures,
        ];

        if (empty($failures)) {
            $this->clearSelection();
        } else {
            $this->selectedPosts = collect($failures)
                ->pluck('id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            $this->selectPage = $this->areAllCurrentPageItemsSelected();
        }

        $this->dispatch('post-updated');
    }

    private function sanitizeSortField(?string $field): string
    {
        return in_array($field, $this->sortableColumns(), true)
            ? $field
            : 'updated_at';
    }

    private function sanitizeSortDirection(?string $direction): string
    {
        $direction = strtolower((string) $direction);

        return in_array($direction, ['asc', 'desc'], true)
            ? $direction
            : 'desc';
    }

    /**
     * @return array<string>
     */
    private function sortableColumns(): array
    {
        return ['title', 'published_at', 'comments_count', 'updated_at'];
    }

    private function sanitizeStatus(?string $value): ?string
    {
        $validStatuses = ['published', 'draft'];

        return in_array($value, $validStatuses, true) ? $value : null;
    }

    private function findPost(int $postId): Post
    {
        return Post::query()
            ->withoutGlobalScope(PublishedScope::class)
            ->findOrFail($postId);
    }

    public function deletePost(int $postId): void
    {
        $post = $this->findPost($postId);

        $this->authorize('delete', $post);

        $post->delete();

        session()->flash('status', __('admin.posts.deleted'));

        $this->resetPage();
        $this->dispatch('post-deleted');
    }

    public function publish(int $postId): void
    {
        $post = $this->findPost($postId);

        $this->authorize('update', $post);

        $post->forceFill(['published_at' => now()])->save();

        $this->dispatch('post-updated');
    }

    public function unpublish(int $postId): void
    {
        $post = $this->findPost($postId);

        $this->authorize('update', $post);

        $post->forceFill(['published_at' => null])->save();

        $this->dispatch('post-updated');
    }
}; ?>

@php
    $postStates = $posts->mapWithKeys(fn ($post) => [$post->id => $post->isPublished()])->toArray();
@endphp

<div
    class="space-y-6"
    x-data="adminPostActions({
        initialStates: @js($postStates),
        defaultError: @js(__('admin.posts.optimistic_error')),
    })"
    x-effect="mergeServerState(@js($postStates))"
    x-cloak
>
    <flux:page-header
        :heading="__('admin.posts.heading')"
        :description="__('admin.posts.description')"
    >
        <flux:button color="primary" :href="route('admin.posts.create')">
            {{ __('admin.posts.create_button') }}
        </flux:button>
    </flux:page-header>

    @if (session('status'))
        <flux:callout color="green">
            {{ session('status') }}
        </flux:callout>
    @endif

    <x-admin.table
        :pagination="$posts"
        per-page-mode="livewire"
        per-page-field="perPage"
        :per-page-options="$this->perPageOptions"
        :per-page-value="$perPage"
    >
        <x-slot name="toolbar">
            <div class="flex w-full flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex flex-1 flex-wrap items-center gap-3">
                    <div class="relative w-full md:w-64">
                        <label for="post-search" class="sr-only">{{ __('admin.posts.filters.search') }}</label>
                        <input
                            id="post-search"
                            type="search"
                            wire:model.live.debounce.300ms="search"
                            placeholder="{{ __('admin.posts.filters.search_placeholder') }}"
                            class="block w-full rounded-xl border border-slate-200 bg-white/80 px-4 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100"
                        >
                        @if ($searchTerm !== '')
                            <button
                                type="button"
                                wire:click="$set('search', '')"
                                class="absolute inset-y-0 right-3 flex items-center text-slate-400 transition hover:text-slate-600 dark:hover:text-slate-200"
                                aria-label="{{ __('admin.posts.filters.clear_search') }}"
                            >
                                &times;
                            </button>
                        @endif
                    </div>

                    <div class="w-full sm:w-48">
                        <label for="status-filter" class="sr-only">{{ __('admin.posts.filters.status') }}</label>
                        <select
                            id="status-filter"
                            wire:model.live="status"
                            class="block w-full rounded-xl border border-slate-200 bg-white/80 px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100"
                        >
                            <option value="">{{ __('admin.posts.filters.status_all') }}</option>
                            <option value="published">{{ __('admin.posts.filters.status_published') }}</option>
                            <option value="draft">{{ __('admin.posts.filters.status_draft') }}</option>
                        </select>
                    </div>

                    @if ($authors->isNotEmpty())
                        <div class="w-full sm:w-52">
                            <label for="author-filter" class="sr-only">{{ __('admin.posts.filters.author') }}</label>
                            <select
                                id="author-filter"
                                wire:model.live="author"
                                class="block w-full rounded-xl border border-slate-200 bg-white/80 px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100"
                            >
                                <option value="">{{ __('admin.posts.filters.author_all') }}</option>
                                @foreach ($authors as $authorOption)
                                    <option value="{{ $authorOption->id }}">{{ $authorOption->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if ($isFiltered)
                        <flux:button type="button" size="sm" color="secondary" wire:click="clearFilters">
                            {{ __('admin.posts.filters.clear') }}
                        </flux:button>
                    @endif
                </div>

                <div class="flex flex-wrap gap-2 text-xs text-slate-500 dark:text-slate-400">
                    @if ($searchTerm !== '')
                        <flux:badge>{{ __('admin.posts.filters.searching', ['term' => $searchTerm]) }}</flux:badge>
                    @endif
                    @if ($activeStatus)
                        <flux:badge>
                            {{ $activeStatus === 'published' ? __('admin.posts.filters.status_published') : __('admin.posts.filters.status_draft') }}
                        </flux:badge>
                    @endif
                    @if ($activeAuthor)
                        @php $authorLabel = $authors->firstWhere('id', $activeAuthor)?->name; @endphp
                        @if ($authorLabel)
                            <flux:badge>{{ __('admin.posts.filters.author_label', ['name' => $authorLabel]) }}</flux:badge>
                        @endif
                    @endif
                </div>
            </div>
        </x-slot>

        <x-slot name="head">
            <x-admin.table-head :columns="[
                ['label' => __('admin.posts.table.title')],
                ['label' => __('admin.posts.table.status')],
                ['label' => __('admin.posts.table.comments')],
                ['label' => __('admin.posts.table.updated')],
                ['label' => __('admin.posts.table.actions'), 'class' => 'text-right'],
            ]" />
        </x-slot>

        @forelse ($posts as $post)
            <x-admin.table-row>
                <td class="px-4 py-4">
                    <div class="flex flex-col gap-1">
                        <span class="font-semibold">{{ $post->title }}</span>
                        <span class="text-xs text-slate-500 dark:text-slate-400">
                            {{ __('admin.posts.slug_label', ['slug' => $post->slug]) }}
                        </span>
                    </div>
                </td>
                <td class="px-4 py-4">
                    @if ($post->isPublished())
                        <flux:badge color="green">{{ __('admin.posts.status.published') }}</flux:badge>
                    @else
                        <flux:badge color="amber">{{ __('admin.posts.status.draft') }}</flux:badge>
                    @endif
                </td>
                <td class="px-4 py-4">
                    <flux:badge color="indigo">
                        {{ trans_choice('admin.posts.comments_count', $post->comments_count, ['count' => $post->comments_count]) }}
                    </flux:badge>
                </td>
                <td class="px-4 py-4">
                    {{ $post->updated_at?->diffForHumans() ?? '—' }}
                </td>
                <td class="px-4 py-4 text-right">
                    <div class="inline-flex items-center gap-2">
                        <flux:link :href="route('admin.posts.edit', $post)" size="sm">
                            {{ __('admin.posts.action_edit') }}
                        </flux:link>

                        <flux:button
                            type="button"
                            size="sm"
                            color="red"
                            icon="trash"
                            wire:click="deletePost({{ $post->id }})"
                            wire:confirm="{{ __('admin.posts.confirm_delete') }}"
                        >
                            {{ __('admin.posts.action_delete') }}
                        </flux:button>

                        @if ($post->isPublished())
                            <flux:button
                                wire:click="unpublish({{ $post->id }})"
                                size="sm"
                                color="amber"
                                icon="arrow-down-tray"
                            >
                                {{ __('admin.posts.action_unpublish') }}
                            </flux:button>
                        @else
                            <flux:button
                                wire:click="publish({{ $post->id }})"
                                size="sm"
                                color="green"
                                icon="arrow-up-tray"
                            >
                                {{ __('admin.posts.action_publish') }}
                            </flux:button>
                        @endif
                    </div>
                </td>
            </x-admin.table-row>
        @empty
            <x-admin.table-empty colspan="5" :message="__('admin.posts.empty')" />
        @endforelse
    </x-admin.table>
</div>
