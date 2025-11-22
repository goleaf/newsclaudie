<?php

use App\Http\Controllers\CreatesNewPost;
use App\Livewire\Concerns\ManagesBulkActions;
use App\Livewire\Concerns\ManagesPerPage;
use App\Livewire\Concerns\ManagesSearch;
use App\Livewire\Concerns\ManagesSorting;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Scopes\PublishedScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use function Livewire\Volt\layout;
use function Livewire\Volt\title;

layout('components.layouts.admin');
title(__('admin.posts.title'));

new class extends Component {
    use AuthorizesRequests;
    use ManagesBulkActions;
    use ManagesPerPage;
    use ManagesSearch;
    use ManagesSorting;
    use WithPagination {
        resetPage as baseResetPage;
        setPage as baseSetPage;
    }

    public int $page = 1;
    public ?string $status = null;
    public ?int $author = null;
    public ?array $bulkFeedback = null;

    public ?int $editingId = null;
    public bool $isEditing = false;
    public bool $editingFileBased = false;

    public array $form = [
        'title' => '',
        'slug' => '',
        'body' => '',
        'description' => '',
        'featured_image' => '',
        'published_at' => '',
        'tags_input' => '',
        'tags' => null,
        'categories' => [],
        'is_draft' => false,
    ];

    public bool $slugManuallyEdited = false;

    protected $listeners = [
        'post-updated' => '$refresh',
        'post-deleted' => '$refresh',
        'category-saved' => 'handleCategorySaved',
    ];

    protected $queryString = [
        'status' => ['except' => null],
        'author' => ['except' => null],
        'page' => ['except' => 1],
    ];

    public function mount(): void
    {
        $this->queryString = array_merge(
            $this->queryString,
            $this->queryStringManagesSorting()
        );
        $this->queryString['perPage'] = $this->perPageQueryStringConfig();
        $this->form = $this->defaultForm();
        [$this->sortField, $this->sortDirection] = $this->resolvedSort();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingAuthor(): void
    {
        $this->resetPage();
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

    public function applySearchShortcut(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->clearSearch();
        $this->status = null;
        $this->author = null;
    }

    public function updatedForm($value, string $key): void
    {
        $this->prepareFormState();

        $property = "form.{$key}";

        // Handle title changes for slug auto-generation
        if ($key === 'title' && !$this->slugManuallyEdited && !$this->isEditing) {
            $this->form['slug'] = \Illuminate\Support\Str::slug($value);
        }

        // Handle slug manual editing
        if ($key === 'slug') {
            $this->slugManuallyEdited = true;
            $this->form['slug'] = \Illuminate\Support\Str::slug($value);
        }

        if ($key === 'tags_input') {
            $this->validateOnly($property, $this->rules());
            $this->validateOnly('form.tags', $this->rules());
            $this->validateOnly('form.tags.*', $this->rules());
        } elseif ($key === 'categories' || str_starts_with($key, 'categories')) {
            $this->validateOnly('form.categories', $this->rules());
            $this->validateOnly('form.categories.*', $this->rules());
        } else {
            $this->validateOnly($property, $this->rules());
        }
    }

    public function with(): array
    {
        $filters = $this->resolvedFilters();
        $this->resolvedSort();

        $posts = $this->baseQuery($filters)
            ->paginate($this->perPage)
            ->withQueryString();

        $this->setCurrentPageIds($posts->pluck('id'));

        return [
            'posts' => $posts,
            'authors' => $this->authors(),
            'categories' => $this->categories(),
            'searchTerm' => $filters['search'],
            'activeStatus' => $filters['status'],
            'activeAuthor' => $filters['author'],
            'isFiltered' => $this->hasSearch() || $filters['status'] !== null || $filters['author'] !== null,
        ];
    }

    public function bulkPublish(): void
    {
        $this->processBulkAction('publish');
    }

    public function bulkUnpublish(): void
    {
        $this->processBulkAction('unpublish');
    }

    public function openCreateModal(): void
    {
        $this->authorize('create', Post::class);

        $this->isEditing = false;
        $this->editingId = null;
        $this->editingFileBased = false;
        $this->resetForm();

        $this->dispatch('modal-show', name: 'post-form', scope: $this->getId());
    }

    public function openEditModal(int $postId): void
    {
        $post = $this->findPost($postId)->loadMissing('categories');

        $this->authorize('update', $post);

        $this->resetErrorBag();
        $this->resetValidation();

        $this->isEditing = true;
        $this->editingId = $post->id;
        $this->editingFileBased = $post->isFileBased();
        $this->slugManuallyEdited = true; // Existing posts have slugs

        $this->form = [
            'title' => $post->title,
            'slug' => $post->slug,
            'body' => $post->body,
            'description' => $post->description ?? '',
            'featured_image' => $post->featured_image ?? '',
            'published_at' => $post->published_at?->format('Y-m-d\\TH:i') ?? now()->format('Y-m-d\\TH:i'),
            'tags_input' => collect($post->tags ?? [])->implode(', '),
            'tags' => $post->tags ?? null,
            'categories' => $post->categories->pluck('id')->map(fn ($id) => (int) $id)->all(),
            'is_draft' => ! $post->isPublished(),
        ];

        $this->dispatch('modal-show', name: 'post-form', scope: $this->getId());
    }

    public function closeForm(): void
    {
        $this->resetForm();

        $this->dispatch('modal-close', name: 'post-form', scope: $this->getId());
    }

    public function savePost(): void
    {
        $this->prepareFormState();

        $validated = Validator::make(
            ['form' => $this->form],
            $this->rules()
        )->validate();

        $payload = $this->preparePayload($validated['form']);

        $categoryIds = $payload['categories'] ?? [];
        unset($payload['categories']);

        if ($this->isEditing && $this->editingId) {
            $post = $this->findPost($this->editingId);

            $this->authorize('update', $post);

            $post->fill($payload)->save();
            $post->categories()->sync($categoryIds);

            $message = __('admin.posts.updated');
        } else {
            $this->authorize('create', Post::class);

            $user = auth()->user();

            if ($user === null) {
                abort(403);
            }

            $creator = new CreatesNewPost();
            $post = $creator->createPost($user, $payload);
            $post->categories()->sync($categoryIds);

            $message = __('admin.posts.created');
        }

        $this->resetPage();
        $this->dispatch('post-updated');
        $this->dispatch('modal-close', name: 'post-form', scope: $this->getId());

        session()->flash('status', $message);
        session()->flash('success', $message);

        $this->resetForm();
    }

    public function openCategoryModal(): void
    {
        $this->authorize('access-admin');

        $this->dispatch('open-category-form');
    }

    public function handleCategorySaved(?int $categoryId = null): void
    {
        if ($categoryId) {
            $this->form['categories'] = collect($this->form['categories'] ?? [])
                ->push((int) $categoryId)
                ->unique()
                ->values()
                ->all();
        }

        $this->resetPage();
    }

    private function prepareFormState(): void
    {
        $this->form['title'] = trim((string) ($this->form['title'] ?? ''));
        $this->form['slug'] = trim((string) ($this->form['slug'] ?? ''));
        $this->form['body'] = (string) ($this->form['body'] ?? '');
        $this->form['description'] = $this->form['description'] !== '' ? $this->form['description'] : null;
        $this->form['featured_image'] = $this->form['featured_image'] !== '' ? $this->form['featured_image'] : null;
        $this->form['published_at'] = ($this->form['is_draft'] ?? false)
            ? null
            : ($this->form['published_at'] ?: null);
        $this->form['tags_input'] = trim((string) ($this->form['tags_input'] ?? ''));
        $this->form['tags'] = $this->normalizeTags($this->form['tags_input']);
        $this->form['categories'] = collect($this->form['categories'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
        $this->form['is_draft'] = (bool) ($this->form['is_draft'] ?? false);
    }

    protected function rules(): array
    {
        $slugUniqueRule = Rule::unique('posts', 'slug');
        
        if ($this->isEditing && $this->editingId) {
            $slugUniqueRule = $slugUniqueRule->ignore($this->editingId);
        }

        return [
            'form.title' => ['required', 'string', 'max:255'],
            'form.slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slugUniqueRule],
            'form.body' => ['required', 'string'],
            'form.description' => ['nullable', 'string', 'max:255'],
            'form.featured_image' => ['nullable', 'url', 'max:255'],
            'form.published_at' => ['nullable', 'date', 'after:1970-12-31T12:00', 'before:2038-01-09T03:14'],
            'form.categories' => ['array'],
            'form.categories.*' => ['integer', 'exists:categories,id'],
            'form.tags_input' => ['nullable', 'string', 'max:255'],
            'form.tags' => ['nullable', 'array'],
            'form.tags.*' => ['string', 'max:50'],
            'form.is_draft' => ['boolean'],
        ];
    }

    private function preparePayload(array $data): array
    {
        $publishedAt = $data['published_at'];
        $publishedAt = $publishedAt ? Carbon::parse($publishedAt) : null;

        return [
            'title' => $data['title'],
            'slug' => \Illuminate\Support\Str::slug($data['slug']),
            'body' => $data['body'],
            'description' => $data['description'],
            'featured_image' => $data['featured_image'],
            'published_at' => ($data['is_draft'] ?? false) ? null : $publishedAt,
            'tags' => $data['tags'] ?? null,
            'categories' => $data['categories'] ?? [],
        ];
    }

    private function defaultForm(): array
    {
        return [
            'title' => '',
            'slug' => '',
            'body' => '',
            'description' => '',
            'featured_image' => '',
            'published_at' => now()->format('Y-m-d\\TH:i'),
            'tags_input' => '',
            'tags' => null,
            'categories' => [],
            'is_draft' => false,
        ];
    }

    private function normalizeTags(?string $raw): ?array
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        $tags = collect(explode(',', $raw))
            ->map(fn ($tag) => trim($tag))
            ->filter()
            ->unique()
            ->values();

        return $tags->isEmpty() ? null : $tags->all();
    }

    private function baseQuery(?array $filters = null): Builder
    {
        $filters ??= $this->resolvedFilters();

        return Post::query()
            ->withoutGlobalScope('order')
            ->withoutGlobalScope(PublishedScope::class)
            ->with(['author', 'categories:id,name'])
            ->withCount('comments')
            ->when($filters['search'] !== '', function ($query) use ($filters) {
                $query->where(function ($inner) use ($filters) {
                    $inner->where('title', 'like', '%'.$filters['search'].'%')
                        ->orWhere('slug', 'like', '%'.$filters['search'].'%');
                });
            })
            ->when($filters['status'] === 'published', fn ($query) => $query->whereNotNull('published_at'))
            ->when($filters['status'] === 'draft', fn ($query) => $query->whereNull('published_at'))
            ->when($filters['author'] !== null, fn ($query) => $query->where('user_id', $filters['author']))
            ->tap(fn (Builder $builder) => $this->applySort($builder));
    }

    private function applySort(Builder $query): Builder
    {
        [$sortField, $sortDirection] = $this->resolvedSort();

        return match ($sortField) {
            'title' => $query->orderBy('title', $sortDirection),
            'published_at' => $query
                ->orderByRaw('published_at is null')
                ->orderBy('published_at', $sortDirection),
            'comments_count' => $query->orderBy('comments_count', $sortDirection),
            default => $query->orderBy('updated_at', $sortDirection),
        };
    }

    protected function defaultSortField(): string
    {
        return 'updated_at';
    }

    protected function defaultSortDirection(): string
    {
        return 'desc';
    }

    protected function sortableColumns(): array
    {
        return ['title', 'published_at', 'comments_count', 'updated_at'];
    }

    protected function queryStringManagesSorting(): array
    {
        return [
            'sortField' => ['as' => 'sort', 'except' => $this->defaultSortField()],
            'sortDirection' => ['as' => 'direction', 'except' => $this->defaultSortDirection()],
        ];
    }

    protected function resolvedSort(): array
    {
        $field = $this->sortField ?: $this->defaultSortField();
        $direction = $this->sortDirection ?: $this->defaultSortDirection();

        if (!in_array($field, $this->sortableColumns(), true)) {
            $field = $this->defaultSortField();
        }

        return [$field, $direction];
    }

    private function resolvedFilters(): array
    {
        return [
            'search' => $this->getSearchTerm(),
            'status' => $this->sanitizeStatus($this->status),
            'author' => $this->author !== null ? (int) $this->author : null,
        ];
    }

    private function hasSearch(): bool
    {
        return $this->getSearchTerm() !== '';
    }

    private function authors()
    {
        return User::query()
            ->whereHas('posts', fn ($query) => $query->withoutGlobalScope(PublishedScope::class))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function categories()
    {
        return Category::query()
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function processBulkAction(string $action): void
    {
        $selectedIds = collect($this->getSelectedIds());

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
            $this->selected = collect($failures)
                ->pluck('id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();
        }

        $this->dispatch('post-updated');
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

    private function resetForm(): void
    {
        $this->form = $this->defaultForm();
        $this->isEditing = false;
        $this->editingId = null;
        $this->editingFileBased = false;
        $this->slugManuallyEdited = false;
        $this->resetErrorBag();
        $this->resetValidation();
    }
}; ?>

<div>
<div class="space-y-6">
    <flux:page-header
        :heading="__('admin.posts.heading')"
        :description="__('admin.posts.description')"
    >
        <flux:button color="primary" icon="plus" type="button" wire:click="openCreateModal" data-admin-create-trigger>
            {{ __('admin.posts.create_button') }}
        </flux:button>
    </flux:page-header>

    @if (session('status'))
        <flux:callout color="green">
            {{ session('status') }}
        </flux:callout>
    @endif

    @if ($bulkFeedback)
        <flux:callout color="{{ $bulkFeedback['status'] === 'success' ? 'green' : 'amber' }}">
            <div class="space-y-2">
                <p>
                    @if ($bulkFeedback['action'] === 'publish')
                        {{ __('admin.posts.bulk_publish_result', ['updated' => $bulkFeedback['updated'], 'attempted' => $bulkFeedback['attempted']]) }}
                    @else
                        {{ __('admin.posts.bulk_unpublish_result', ['updated' => $bulkFeedback['updated'], 'attempted' => $bulkFeedback['attempted']]) }}
                    @endif
                </p>
                @if (!empty($bulkFeedback['failures']))
                    <details class="text-sm">
                        <summary class="cursor-pointer font-semibold">{{ __('admin.posts.bulk_failures', ['count' => count($bulkFeedback['failures'])]) }}</summary>
                        <ul class="mt-2 list-inside list-disc space-y-1">
                            @foreach ($bulkFeedback['failures'] as $failure)
                                <li>{{ $failure['title'] }}: {{ $failure['reason'] }}</li>
                            @endforeach
                        </ul>
                    </details>
                @endif
            </div>
        </flux:callout>
    @endif

    @if ($this->selectedCount > 0)
        <div class="rounded-xl border border-indigo-200 bg-indigo-50 p-4 dark:border-indigo-800 dark:bg-indigo-950">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm font-semibold text-indigo-900 dark:text-indigo-100">
                    {{ trans_choice('admin.posts.selected_count', $this->selectedCount, ['count' => $this->selectedCount]) }}
                </p>
                <div class="flex flex-wrap items-center gap-2">
                    <flux:button
                        type="button"
                        size="sm"
                        color="green"
                        wire:click="bulkPublish"
                        wire:confirm="{{ __('admin.posts.bulk_publish_confirm') }}"
                        wire:loading.attr="disabled"
                        wire:target="bulkPublish"
                    >
                        <span wire:loading.remove wire:target="bulkPublish">{{ __('admin.posts.bulk_publish') }}</span>
                        <span wire:loading.delay.500ms wire:target="bulkPublish" class="inline-flex items-center gap-1">
                            <svg class="h-3 w-3 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ __('admin.processing') }}
                        </span>
                    </flux:button>
                    <flux:button
                        type="button"
                        size="sm"
                        color="amber"
                        wire:click="bulkUnpublish"
                        wire:confirm="{{ __('admin.posts.bulk_unpublish_confirm') }}"
                        wire:loading.attr="disabled"
                        wire:target="bulkUnpublish"
                    >
                        <span wire:loading.remove wire:target="bulkUnpublish">{{ __('admin.posts.bulk_unpublish') }}</span>
                        <span wire:loading.delay.500ms wire:target="bulkUnpublish" class="inline-flex items-center gap-1">
                            <svg class="h-3 w-3 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ __('admin.processing') }}
                        </span>
                    </flux:button>
                    <flux:button
                        type="button"
                        size="sm"
                        variant="ghost"
                        wire:click="clearSelection"
                    >
                        {{ __('admin.posts.clear_selection') }}
                    </flux:button>
                </div>
            </div>
        </div>
    @endif

    <x-admin.table
        :pagination="$posts"
        per-page-mode="livewire"
        per-page-field="perPage"
        :per-page-options="$this->perPageOptions"
        :per-page-value="$perPage"
        aria-label="{{ __('admin.posts.table.aria_label') }}"
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
                            wire:keydown.enter.prevent="applySearchShortcut"
                            data-admin-search-input
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

                <div class="flex flex-wrap items-center gap-2">
                    <label for="sort-field" class="sr-only">{{ __('admin.posts.sort.label') }}</label>
                    <select
                        id="sort-field"
                        wire:model.live="sortField"
                        class="w-full rounded-xl border border-slate-200 bg-white/80 px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100 md:w-44"
                    >
                        <option value="updated_at">{{ __('admin.posts.sort.updated') }}</option>
                        <option value="title">{{ __('admin.posts.sort.title') }}</option>
                        <option value="published_at">{{ __('admin.posts.sort.status') }}</option>
                        <option value="comments_count">{{ __('admin.posts.sort.comments') }}</option>
                    </select>

                    <flux:button
                        type="button"
                        size="sm"
                        variant="ghost"
                        icon="arrows-up-down"
                        wire:click="sortBy('{{ $sortField }}')"
                        aria-label="{{ $sortDirection === 'asc' ? __('admin.posts.sort.asc') : __('admin.posts.sort.desc') }}"
                    >
                        {{ $sortDirection === 'asc' ? __('admin.posts.sort.asc') : __('admin.posts.sort.desc') }}
                    </flux:button>
                </div>
            </div>

            <p class="text-xs text-slate-500 dark:text-slate-400">
                {{ __('admin.shortcuts.help_text', ['create' => __('admin.shortcuts.create_shortcut')]) }}
            </p>
        </x-slot>

        <x-slot name="head">
            <tr>
                <th class="px-4 py-3 text-left">
                    <input
                        type="checkbox"
                        wire:model.live="selectAll"
                        class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                        aria-label="{{ __('admin.posts.select_all') }}"
                    >
                </th>
                <x-admin.table-head :columns="[
                    ['label' => __('admin.posts.table.title'), 'sortable' => true, 'field' => 'title'],
                    ['label' => __('admin.posts.table.status'), 'sortable' => true, 'field' => 'published_at'],
                    ['label' => __('admin.posts.table.comments')],
                    ['label' => __('admin.posts.table.updated'), 'sortable' => true, 'field' => 'updated_at'],
                    ['label' => __('admin.posts.table.actions'), 'class' => 'text-right'],
                ]" :sort-field="$sortField" :sort-direction="$sortDirection" />
            </tr>
        </x-slot>

        @forelse ($posts as $post)
            <x-admin.table-row
                wire:key="post-{{ $post->id }}"
                :interactive="true"
                data-row-id="{{ $post->id }}"
                data-row-label="{{ $post->title }}"
            >
                <td class="px-4 py-4">
                    <input
                        type="checkbox"
                        wire:click="toggleSelection({{ $post->id }})"
                        @checked(in_array($post->id, $selected))
                        class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                        aria-label="{{ __('admin.posts.select_post', ['title' => $post->title]) }}"
                    >
                </td>
                <td class="px-4 py-4">
                    <div class="flex flex-col gap-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-semibold">{{ $post->title }}</span>
                            <span class="text-xs text-slate-400 dark:text-slate-500">#{{ $post->id }}</span>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                            <span>{{ __('admin.posts.slug_label', ['slug' => $post->slug]) }}</span>
                        </div>
                        @if ($post->categories->isNotEmpty())
                            <div class="flex flex-wrap gap-1.5">
                                @foreach ($post->categories as $category)
                                    <flux:badge variant="solid" color="sky" size="sm">{{ $category->name }}</flux:badge>
                                @endforeach
                            </div>
                        @endif
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
                        <flux:button type="button" size="sm" variant="ghost" wire:click="openEditModal({{ $post->id }})">
                            {{ __('admin.posts.action_edit') }}
                        </flux:button>

                        <flux:button
                            type="button"
                            size="sm"
                            color="red"
                            icon="trash"
                            wire:click="deletePost({{ $post->id }})"
                            wire:confirm="{{ __('admin.posts.confirm_delete') }}"
                            wire:loading.attr="disabled"
                            wire:target="deletePost"
                            data-row-delete
                        >
                            <span wire:loading.remove wire:target="deletePost">{{ __('admin.posts.action_delete') }}</span>
                            <span wire:loading.delay.500ms wire:target="deletePost" class="inline-flex items-center gap-1">
                                <svg class="h-3 w-3 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </span>
                        </flux:button>

                        @if ($post->isPublished())
                            <flux:button
                                wire:click="unpublish({{ $post->id }})"
                                size="sm"
                                color="amber"
                                icon="arrow-down-tray"
                                wire:loading.attr="disabled"
                                wire:target="unpublish"
                            >
                                <span wire:loading.remove wire:target="unpublish">{{ __('admin.posts.action_unpublish') }}</span>
                                <span wire:loading wire:target="unpublish" class="inline-flex items-center gap-1">
                                    <svg class="h-3 w-3 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </span>
                            </flux:button>
                        @else
                            <flux:button
                                wire:click="publish({{ $post->id }})"
                                size="sm"
                                color="green"
                                icon="arrow-up-tray"
                                wire:loading.attr="disabled"
                                wire:target="publish"
                            >
                                <span wire:loading.remove wire:target="publish">{{ __('admin.posts.action_publish') }}</span>
                                <span wire:loading wire:target="publish" class="inline-flex items-center gap-1">
                                    <svg class="h-3 w-3 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </span>
                            </flux:button>
                        @endif
                    </div>
                </td>
            </x-admin.table-row>
        @empty
            <x-admin.table-empty colspan="6" :message="__('admin.posts.empty')" />
        @endforelse
    </x-admin.table>
</div>

{{-- Post Form Modal --}}
<flux:modal name="post-form" class="max-w-4xl" @close="$wire.call('closeForm')">
    <div class="space-y-6 p-6">
        <div class="flex items-start justify-between gap-3">
            <div class="space-y-1">
                <flux:heading size="lg">
                    {{ $isEditing ? __('admin.posts.form.edit_title') : __('admin.posts.form.create_title') }}
                </flux:heading>
                <flux:description>
                    {{ $isEditing
                        ? __('admin.posts.form.edit_subtitle', ['title' => $form['title'] ?: __('admin.posts.form.this_post')])
                        : __('admin.posts.form.subtitle') }}
                </flux:description>
            </div>
            <flux:badge>
                {{ $isEditing ? __('admin.posts.form.editing_badge') : __('admin.posts.form.creating_badge') }}
            </flux:badge>
        </div>

        @if ($editingFileBased)
            <flux:callout color="amber" icon="exclamation-triangle">
                {{ __('posts.form.markdown_warning') }}
            </flux:callout>
        @endif

        <form class="space-y-6" wire:submit.prevent="savePost">
            {{-- Required Fields Section --}}
            <div class="space-y-4">
                <flux:heading size="sm">{{ __('posts.form.required_heading') }}</flux:heading>

                <div class="space-y-2">
                    <flux:input
                        wire:model.live.debounce.300ms="form.title"
                        label="{{ __('posts.form.title_label') }}"
                        placeholder="{{ __('posts.form.title_placeholder') }}"
                        required
                        autofocus
                        maxlength="255"
                    />
                    <flux:error name="form.title" />
                </div>

                <div class="space-y-2">
                    <flux:input
                        wire:model.live.debounce.300ms="form.slug"
                        label="{{ __('posts.form.slug_label') }}"
                        placeholder="{{ __('posts.form.slug_placeholder') }}"
                        required
                        maxlength="255"
                        description="{{ $slugManuallyEdited ? __('posts.form.slug_locked') : __('posts.form.slug_help') }}"
                    />
                    <flux:error name="form.slug" />
                </div>

                <div class="space-y-2">
                    <flux:textarea
                        wire:model.live.debounce.400ms="form.body"
                        rows="8"
                        label="{{ __('posts.form.body_label') }}"
                        placeholder="{{ __('posts.form.body_placeholder') }}"
                        required
                    />
                    <flux:error name="form.body" />
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        {{ __('posts.form.body_help') }}
                    </p>
                </div>
            </div>

            {{-- Optional Fields Section --}}
            <div class="space-y-4">
                <flux:heading size="sm">{{ __('posts.form.optional_heading') }}</flux:heading>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <flux:input
                            wire:model.live.debounce.400ms="form.description"
                            label="{{ __('posts.form.description_label') }}"
                            placeholder="{{ __('posts.form.description_placeholder') }}"
                            maxlength="255"
                        />
                        <flux:error name="form.description" />
                    </div>

                    <div class="space-y-2">
                        <flux:input
                            wire:model.live.debounce.400ms="form.featured_image"
                            type="url"
                            label="{{ __('posts.form.featured_image_label') }}"
                            placeholder="{{ __('posts.form.featured_image_placeholder') }}"
                            maxlength="255"
                        />
                        <flux:error name="form.featured_image" />
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            {{ __('posts.form.featured_image_help') }}
                        </p>
                    </div>
                </div>

                {{-- Categories Multi-Select --}}
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <flux:label>{{ __('posts.form.categories_label') }}</flux:label>
                        <flux:button
                            type="button"
                            size="xs"
                            variant="ghost"
                            icon="plus"
                            wire:click="openCategoryModal"
                        >
                            {{ __('admin.posts.form.new_category') }}
                        </flux:button>
                    </div>

                    @if ($categories->isNotEmpty())
                        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($categories as $category)
                                <label class="flex items-center gap-2 rounded-lg border border-slate-200 p-3 transition hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800">
                                    <input
                                        type="checkbox"
                                        wire:model.live="form.categories"
                                        value="{{ $category->id }}"
                                        class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                    >
                                    <span class="text-sm">{{ $category->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            {{ __('admin.posts.form.no_categories') }}
                        </p>
                    @endif
                    <flux:error name="form.categories" />
                    <flux:error name="form.categories.*" />
                </div>

                {{-- Tags Input --}}
                @if (config('blog.withTags'))
                    <div class="space-y-2">
                        <flux:input
                            wire:model.live.debounce.400ms="form.tags_input"
                            label="{{ __('posts.form.tags_label') }}"
                            placeholder="{{ __('posts.form.tags_placeholder') }}"
                            maxlength="255"
                        />
                        <flux:error name="form.tags_input" />
                        <flux:error name="form.tags" />
                        <flux:error name="form.tags.*" />
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            {{ __('posts.form.tags_help') }}
                        </p>
                    </div>
                @endif

                {{-- Publication Settings --}}
                <div class="space-y-3 rounded-lg border border-slate-200 p-4 dark:border-slate-700">
                    <flux:heading size="sm">{{ __('posts.form.publication_heading') }}</flux:heading>

                    <div class="flex items-center gap-2">
                        <input
                            id="is_draft"
                            type="checkbox"
                            wire:model.live="form.is_draft"
                            class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                        >
                        <flux:label for="is_draft">{{ __('posts.form.save_draft_label') }}</flux:label>
                    </div>
                    <flux:error name="form.is_draft" />

                    @if (!($form['is_draft'] ?? false))
                        <div class="space-y-2">
                            <flux:input
                                wire:model.live.debounce.400ms="form.published_at"
                                type="datetime-local"
                                label="{{ __('posts.form.publish_label') }}"
                                min="1971-01-01T00:00"
                                max="2038-01-09T03:14"
                            />
                            <flux:error name="form.published_at" />
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                {{ __('posts.form.publish_help') }}
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-slate-200 pt-4 dark:border-slate-700">
                <flux:button type="button" variant="ghost" wire:click="closeForm">
                    {{ __('admin.posts.form.cancel') }}
                </flux:button>
                <flux:button type="submit" color="primary" wire:target="savePost" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="savePost">
                        {{ $isEditing ? __('admin.posts.form.update') : __('admin.posts.form.create') }}
                    </span>
                    <span wire:loading wire:target="savePost">
                        {{ __('admin.posts.form.saving') }}
                    </span>
                </flux:button>
            </div>
        </form>
    </div>
</flux:modal>

@livewire('admin.categories.category-form')
</div>
