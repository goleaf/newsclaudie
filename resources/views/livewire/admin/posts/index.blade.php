<?php

use App\Livewire\Concerns\ManagesPerPage;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Scopes\PublishedScope;
use App\Support\Pagination\PageSize;
use Carbon\Carbon;
use Flux\Concerns\InteractsWithComponents;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use function Livewire\Volt\layout;
use function Livewire\Volt\title;

layout('components.layouts.admin');
title(__('admin.posts.title'));

new class extends Component {
    use AuthorizesRequests;
    use InteractsWithComponents;
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
    public array $postStates = [];
    public ?array $bulkFeedback = null;
    public ?int $inlineEditingId = null;
    public ?string $inlineEditingField = null;
    public array $inlineEditingValues = [
        'title' => '',
        'slug' => '',
    ];
    public string $formMode = 'create';
    public ?int $editingId = null;
    public string $title = '';
    public string $body = '';
    public ?string $description = null;
    public ?string $featuredImage = null;
    public ?string $publishedAt = null;
    public bool $isDraft = false;
    public array $categories = [];
    public array $tags = [];
    public ?string $tagsInput = null;

    protected $listeners = ['post-updated' => '$refresh', 'post-deleted' => '$refresh'];
    protected $queryString = [
        'perPage' => ['except' => PageSize::FALLBACK],
        'search' => ['except' => ''],
        'status' => ['except' => null],
        'author' => ['except' => null],
        'sortField' => ['as' => 'sort', 'except' => 'updated_at'],
        'sortDirection' => ['as' => 'direction', 'except' => 'desc'],
        'page' => ['except' => 1],
    ];

    public function mount(): void
    {
        $this->queryString['perPage'] = ['except' => PageSize::contextDefault('admin')];
        $this->publishedAt = $this->defaultPublishedAt();
    }

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

    public function startInlineEdit(int $postId, string $field): void
    {
        if (! $this->isInlineField($field)) {
            return;
        }

        $post = $this->findPost($postId);

        $this->authorize('update', $post);

        $this->resetErrorBag();
        $this->resetValidation();

        $this->inlineEditingId = $post->id;
        $this->inlineEditingField = $field;
        $this->inlineEditingValues = [
            'title' => $post->title,
            'slug' => $post->slug,
        ];
    }

    public function updatedInlineEditingValuesTitle(): void
    {
        $this->validateInlineField('title');
    }

    public function updatedInlineEditingValuesSlug(): void
    {
        $this->validateInlineField('slug');
    }

    public function saveInlineEdit(): void
    {
        if (! $this->inlineEditingId || ! $this->inlineEditingField || ! $this->isInlineField($this->inlineEditingField)) {
            return;
        }

        $property = "inlineEditingValues.{$this->inlineEditingField}";

        $this->validateOnly($property, $this->inlineRules(), $this->inlineMessages());

        $post = $this->findPost($this->inlineEditingId);

        $this->authorize('update', $post);

        $post->forceFill([
            $this->inlineEditingField => $this->inlineEditingValues[$this->inlineEditingField],
        ])->save();

        $this->cancelInlineEdit();

        $this->dispatch('post-updated');
    }

    public function cancelInlineEdit(): void
    {
        $this->reset([
            'inlineEditingId',
            'inlineEditingField',
        ]);

        $this->inlineEditingValues = [
            'title' => '',
            'slug' => '',
        ];

        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function startCreate(): void
    {
        $this->authorize('create', Post::class);

        $this->cancelInlineEdit();
        $this->resetForm();
        $this->formMode = 'create';

        $this->modal('post-form')->show();
    }

    public function startEdit(int $postId): void
    {
        $post = $this->findPost($postId)->loadMissing('categories');

        $this->authorize('update', $post);

        $this->cancelInlineEdit();
        $this->formMode = 'edit';
        $this->editingId = $post->id;
        $this->title = $post->title;
        $this->body = $post->body;
        $this->description = $post->getRawOriginal('description');
        $this->featuredImage = $post->getRawOriginal('featured_image');
        $this->isDraft = $post->published_at === null;
        $this->publishedAt = $post->published_at?->format('Y-m-d\TH:i') ?? $this->defaultPublishedAt();
        $this->categories = $post->categories->pluck('id')->map(fn ($id) => (int) $id)->all();
        $this->tags = $post->tags ?? [];
        $this->tagsInput = $this->tags ? implode(', ', $this->tags) : null;

        $this->resetValidation();

        $this->modal('post-form')->show();
    }

    public function savePost(): void
    {
        $this->tags = $this->prepareTags($this->tagsInput);

        $validated = $this->validate($this->rules());

        $payload = [
            'title' => $validated['title'],
            'body' => $validated['body'],
            'description' => $this->nullableValue($validated['description'] ?? null),
            'featured_image' => $this->nullableValue($validated['featuredImage'] ?? null),
            'tags' => $this->tags ?: null,
            'published_at' => $this->normalizePublishedAt($validated['publishedAt'] ?? null),
        ];

        if ($this->editingId) {
            $post = $this->findPost($this->editingId);

            $this->authorize('update', $post);

            $post->forceFill($payload)->save();
        } else {
            $this->authorize('create', Post::class);

            $post = new Post();
            $post->forceFill(array_merge($payload, [
                'user_id' => auth()->id(),
                'slug' => $this->generateUniqueSlug($payload['title']),
            ]));
            $post->save();
        }

        $post->categories()->sync($validated['categories'] ?? []);

        $this->dispatch('post-updated');
        $this->modal('post-form')->close();
        $this->resetForm();
    }

    public function closeModal(): void
    {
        $this->modal('post-form')->close();
        $this->resetForm();
    }

    public function handleModalClosed(): void
    {
        $this->resetForm();
    }

    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'description' => ['nullable', 'string', 'max:255'],
            'featuredImage' => ['nullable', 'url', 'max:255'],
            'publishedAt' => ['nullable', 'date', 'after:1970-12-31T12:00', 'before:2038-01-09T03:14'],
            'categories' => ['array'],
            'categories.*' => ['integer', 'exists:categories,id'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'isDraft' => ['boolean'],
        ];
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
        $this->postStates = $posts
            ->mapWithKeys(fn ($post) => [$post->id => $post->isPublished()])
            ->toArray();

        return [
            'posts' => $posts,
            'authors' => $this->authors(),
            'availableCategories' => Category::orderBy('name')->get(),
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
        $filters = array_merge($this->defaultFilters(), $filters ?? $this->resolvedFilters());

        return Post::query()
            ->withoutGlobalScope('order')
            ->withoutGlobalScope(PublishedScope::class)
            ->with(['author', 'categories'])
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
        return array_merge($this->defaultFilters(), [
            'search' => trim((string) $this->search),
            'status' => $this->sanitizeStatus($this->status),
            'author' => $this->author !== null ? (int) $this->author : null,
        ]);
    }

    private function defaultFilters(): array
    {
        return [
            'search' => '',
            'status' => null,
            'author' => null,
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

        $posts = $this->baseQuery($this->defaultFilters())
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

    private function validateInlineField(string $field): void
    {
        if (! $this->inlineEditingId || $this->inlineEditingField !== $field || ! $this->isInlineField($field)) {
            return;
        }

        $this->validateOnly("inlineEditingValues.{$field}", $this->inlineRules(), $this->inlineMessages());
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

    private function isInlineField(string $field): bool
    {
        return in_array($field, ['title', 'slug'], true);
    }

    private function inlineRules(): array
    {
        return [
            'inlineEditingValues.title' => ['required', 'string', 'max:255'],
            'inlineEditingValues.slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('posts', 'slug')->ignore($this->inlineEditingId),
            ],
        ];
    }

    private function inlineMessages(): array
    {
        return [
            'inlineEditingValues.title.required' => __('validation.post_title_required'),
            'inlineEditingValues.title.string' => __('validation.post_title_string'),
            'inlineEditingValues.title.max' => __('validation.post_title_max'),
            'inlineEditingValues.slug.required' => __('validation.post_slug_required'),
            'inlineEditingValues.slug.string' => __('validation.post_slug_string'),
            'inlineEditingValues.slug.max' => __('validation.post_slug_max'),
            'inlineEditingValues.slug.regex' => __('validation.post_slug_regex'),
            'inlineEditingValues.slug.unique' => __('validation.post_slug_unique'),
        ];
    }

    private function findPost(int $postId): Post
    {
        return Post::query()
            ->withoutGlobalScope(PublishedScope::class)
            ->findOrFail($postId);
    }

    public function deletePost(int $postId): void
    {
        if ($this->inlineEditingId === $postId) {
            $this->cancelInlineEdit();
        }

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
        $this->reset([
            'editingId',
            'title',
            'body',
            'description',
            'featuredImage',
            'categories',
            'tags',
            'tagsInput',
        ]);

        $this->isDraft = false;
        $this->publishedAt = $this->defaultPublishedAt();
        $this->formMode = 'create';

        $this->resetValidation();
    }

    private function prepareTags(?string $raw): array
    {
        if ($raw === null) {
            return [];
        }

        return collect(explode(',', $raw))
            ->map(fn ($tag) => trim($tag))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function nullableValue(?string $value): ?string
    {
        return $value === null || $value === '' ? null : $value;
    }

    private function normalizePublishedAt(?string $value): ?Carbon
    {
        if ($this->isDraft) {
            return null;
        }

        return $this->nullableValue($value) ? Carbon::parse((string) $value) : null;
    }

    private function defaultPublishedAt(): string
    {
        return now()->format('Y-m-d\TH:i');
    }

    private function generateUniqueSlug(string $title): string
    {
        $slug = Str::slug($title);

        if (in_array($slug, ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'], true)) {
            $slug .= '-post';
        }

        $query = Post::withoutGlobalScopes();

        if ($query->where('slug', $slug)->exists()) {
            $nextId = ($query->max('id') ?? 0) + 1;

            return sprintf('%s-%d', $slug, $nextId);
        }

        return $slug;
    }
}; ?>

<div
    class="space-y-6"
    x-data="(() => {
        const state = adminPostActions({
            initialStates: @js($postStates),
            defaultError: @js(__('admin.posts.optimistic_error')),
        });

        state.serverStates = @entangle('postStates');

        return state;
    })()"
    x-effect="mergeServerState(serverStates)"
    x-cloak
>
    <flux:page-header
        :heading="__('admin.posts.heading')"
        :description="__('admin.posts.description')"
    >
        <flux:button color="primary" type="button" wire:click="startCreate">
            {{ __('admin.posts.create_button') }}
        </flux:button>
    </flux:page-header>

    @if (session('status'))
        <flux:callout color="green">
            {{ session('status') }}
        </flux:callout>
    @endif

    <div class="space-y-3">
        <template x-if="errorMessage">
            <flux:callout color="red">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-sm" x-text="errorMessage"></p>
                    <button
                        type="button"
                        class="text-xs font-semibold text-red-700 underline-offset-2 hover:underline dark:text-red-300"
                        x-on:click="clearError"
                    >
                        {{ __('admin.posts.optimistic_dismiss') }}
                    </button>
                </div>
            </flux:callout>
        </template>

        <div
            class="flex flex-wrap items-center gap-3"
            x-show="queueSize()"
            x-transition
        >
            <div class="inline-flex items-center gap-2 rounded-full bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-800 shadow-sm ring-1 ring-indigo-100 dark:bg-indigo-900/30 dark:text-indigo-100 dark:ring-indigo-800/50">
                <svg class="h-4 w-4 animate-spin text-indigo-600 dark:text-indigo-300" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z" />
                </svg>
                <span x-text="queueSize() === 1 ? '{{ __('admin.posts.optimistic_single') }}' : '{{ __('admin.posts.optimistic_many') }}'.replace(':count', queueSize())"></span>
            </div>

            <div
                class="inline-flex items-center gap-2 rounded-full bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-800 shadow-sm ring-1 ring-amber-100 dark:bg-amber-900/30 dark:text-amber-100 dark:ring-amber-800/50"
                x-show="lagging"
                x-transition
            >
                <svg class="h-4 w-4 text-amber-500" viewBox="0 0 24 24" fill="none">
                    <path d="M12 2a10 10 0 110 20 10 10 0 010-20z" fill="currentColor" fill-opacity=".15" />
                    <path d="M12 7v5m0 4h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <span>{{ __('admin.posts.optimistic_lagging') }}</span>
            </div>
        </div>
    </div>

    @if ($bulkFeedback)
        @php
            $failedCount = count($bulkFeedback['failures'] ?? []);
            $actionLabel = $bulkFeedback['action'] === 'publish'
                ? __('admin.posts.action_publish')
                : __('admin.posts.action_unpublish');
        @endphp

        <flux:callout color="{{ $bulkFeedback['status'] === 'success' ? 'green' : 'amber' }}">
            <div class="space-y-2">
                <p class="font-semibold">
                    {{ trans_choice('admin.posts.bulk_success', $bulkFeedback['updated'], ['count' => $bulkFeedback['updated'], 'action' => $actionLabel]) }}
                </p>

                <p class="text-sm text-slate-600 dark:text-slate-300">
                    {{ __('admin.posts.bulk_summary', ['updated' => $bulkFeedback['updated'], 'total' => $bulkFeedback['attempted']]) }}
                </p>

                @if ($failedCount)
                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                        {{ trans_choice('admin.posts.bulk_failures', $failedCount, ['count' => $failedCount]) }}
                    </p>
                    <ul class="list-disc space-y-1 pl-5 text-sm text-slate-600 dark:text-slate-300">
                        @foreach ($bulkFeedback['failures'] as $failure)
                            <li>
                                <span class="font-semibold">{{ $failure['title'] }}</span>
                                <span>— {{ $failure['reason'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </flux:callout>
    @endif

    @php
        $selectedCount = count($selectedPosts);
    @endphp

    <x-admin.table
        :pagination="$posts"
        per-page-mode="livewire"
        per-page-field="perPage"
        :per-page-options="$this->perPageOptions"
        :per-page-value="$perPage"
    >
        <x-slot name="toolbar">
            <div class="flex w-full flex-col gap-4">
                @if ($selectedCount > 0)
                    <div class="flex flex-wrap items-center gap-3 rounded-xl bg-indigo-50/60 px-3 py-2 dark:bg-slate-800/70">
                        <flux:badge color="indigo">
                            {{ trans_choice('admin.posts.bulk_selected', $selectedCount, ['count' => $selectedCount]) }}
                        </flux:badge>

                        @if ($selectPage && count($currentPageIds) > 0)
                            <span class="text-xs font-semibold text-indigo-900 dark:text-indigo-200">
                                {{ trans_choice('admin.posts.bulk_page_selected', count($currentPageIds), ['count' => count($currentPageIds)]) }}
                            </span>
                        @endif

                        <div class="flex flex-wrap items-center gap-2">
                            <flux:button
                                wire:click="bulkPublish"
                                size="sm"
                                color="green"
                                icon="arrow-up-tray"
                                wire:confirm="{{ trans_choice('admin.posts.bulk_confirm_publish', $selectedCount, ['count' => $selectedCount]) }}"
                            >
                                {{ __('admin.posts.bulk_publish') }}
                            </flux:button>

                            <flux:button
                                wire:click="bulkUnpublish"
                                size="sm"
                                color="amber"
                                icon="arrow-down-tray"
                                wire:confirm="{{ trans_choice('admin.posts.bulk_confirm_unpublish', $selectedCount, ['count' => $selectedCount]) }}"
                            >
                                {{ __('admin.posts.bulk_unpublish') }}
                            </flux:button>

                            <x-ui.button variant="secondary" wire:click="clearSelection">
                                {{ __('admin.posts.bulk_clear') }}
                            </x-ui.button>
                        </div>
                    </div>
                @endif

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
            </div>
        </x-slot>

        <x-slot name="head">
            <x-admin.table-head
                :columns="[
                    ['label' => __('admin.posts.table.title'), 'sortable' => true, 'field' => 'title'],
                    ['label' => __('admin.posts.table.categories')],
                    ['label' => __('admin.posts.table.status'), 'sortable' => true, 'field' => 'published_at'],
                    ['label' => __('admin.posts.table.comments'), 'sortable' => true, 'field' => 'comments_count'],
                    ['label' => __('admin.posts.table.updated'), 'sortable' => true, 'field' => 'updated_at'],
                    ['label' => __('admin.posts.table.actions'), 'class' => 'text-right'],
                ]"
                :sort-field="$sortField"
                :sort-direction="$sortDirection"
            >
                <x-slot name="before">
                    <th class="w-12 px-4 py-3">
                        <label class="inline-flex items-center gap-2">
                            <input
                                type="checkbox"
                                wire:model.live="selectPage"
                                @disabled($posts->isEmpty())
                                class="h-4 w-4 rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-slate-800 dark:bg-slate-900"
                                aria-label="{{ __('admin.posts.table.select_all') }}"
                            />
                            <span class="sr-only">{{ __('admin.posts.table.select_all') }}</span>
                        </label>
                    </th>
                </x-slot>
            </x-admin.table-head>
        </x-slot>

        @forelse ($posts as $post)
            <x-admin.table-row wire:key="post-row-{{ $post->id }}">
                <td class="px-4 py-4">
                    <label class="inline-flex items-center gap-2">
                        <input
                            type="checkbox"
                            wire:model.live="selectedPosts"
                            value="{{ $post->id }}"
                            class="h-4 w-4 rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-slate-800 dark:bg-slate-900"
                            aria-label="{{ __('admin.posts.table.select_post', ['title' => $post->title]) }}"
                        />
                        <span class="sr-only">{{ __('admin.posts.table.select_post', ['title' => $post->title]) }}</span>
                    </label>
                </td>
                @php
                    $isEditingTitle = $inlineEditingId === $post->id && $inlineEditingField === 'title';
                    $isEditingSlug = $inlineEditingId === $post->id && $inlineEditingField === 'slug';
                @endphp
                <td class="px-4 py-4">
                    <div class="flex flex-col gap-3">
                        <div class="flex flex-col gap-2">
                            @if ($isEditingTitle)
                                <div class="space-y-2">
                                    <input
                                        type="text"
                                        wire:model.live="inlineEditingValues.title"
                                        wire:keydown.enter.prevent="saveInlineEdit"
                                        wire:keydown.escape.prevent="cancelInlineEdit"
                                        class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100"
                                        aria-label="{{ __('admin.posts.table.title') }}"
                                        autofocus
                                    />
                                    @error('inlineEditingValues.title')
                                        <p class="text-xs text-rose-500">{{ $message }}</p>
                                    @enderror
                                    <div class="flex items-center gap-2">
                                        <flux:button
                                            size="sm"
                                            color="green"
                                            wire:click="saveInlineEdit"
                                            wire:target="saveInlineEdit"
                                            wire:loading.attr="disabled"
                                        >
                                            {{ __('admin.inline.save') }}
                                        </flux:button>
                                        <flux:button
                                            size="sm"
                                            variant="ghost"
                                            wire:click="cancelInlineEdit"
                                            wire:target="saveInlineEdit"
                                            wire:loading.attr="disabled"
                                        >
                                            {{ __('admin.inline.cancel') }}
                                        </flux:button>
                                    </div>
                                </div>
                            @else
                                <button
                                    type="button"
                                    wire:click="startInlineEdit({{ $post->id }}, 'title')"
                                    class="group inline-flex w-full items-center justify-between gap-2 rounded-lg px-1 py-0.5 text-left transition hover:bg-slate-50/70 hover:text-indigo-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 dark:hover:bg-slate-900/60"
                                    aria-label="{{ __('admin.inline.edit_field', ['field' => __('admin.posts.table.title')]) }}"
                                >
                                    <span class="font-semibold">{{ $post->title }}</span>
                                    <span class="text-xs font-medium text-indigo-500 opacity-0 transition group-hover:opacity-100">
                                        {{ __('admin.inline.edit_label') }}
                                    </span>
                                </button>
                            @endif
                        </div>

                        <div class="flex flex-col gap-2 text-xs text-slate-500 dark:text-slate-400">
                            @if ($isEditingSlug)
                                <div class="space-y-2">
                                    <input
                                        type="text"
                                        wire:model.live="inlineEditingValues.slug"
                                        wire:keydown.enter.prevent="saveInlineEdit"
                                        wire:keydown.escape.prevent="cancelInlineEdit"
                                        class="w-full rounded-lg border border-dashed border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200"
                                        aria-label="{{ __('admin.posts.slug_label', ['slug' => $post->slug]) }}"
                                    />
                                    @error('inlineEditingValues.slug')
                                        <p class="text-xs text-rose-500">{{ $message }}</p>
                                    @enderror
                                    <div class="flex items-center gap-2">
                                        <flux:button
                                            size="xs"
                                            variant="primary"
                                            color="indigo"
                                            wire:click="saveInlineEdit"
                                            wire:target="saveInlineEdit"
                                            wire:loading.attr="disabled"
                                        >
                                            {{ __('admin.inline.save') }}
                                        </flux:button>
                                        <flux:button
                                            size="xs"
                                            variant="ghost"
                                            wire:click="cancelInlineEdit"
                                            wire:target="saveInlineEdit"
                                            wire:loading.attr="disabled"
                                        >
                                            {{ __('admin.inline.cancel') }}
                                        </flux:button>
                                    </div>
                                </div>
                            @else
                                <button
                                    type="button"
                                    wire:click="startInlineEdit({{ $post->id }}, 'slug')"
                                    class="group inline-flex items-center gap-2 rounded-md px-1 py-0.5 text-xs font-medium text-slate-500 transition hover:bg-slate-50/70 hover:text-indigo-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 dark:hover:bg-slate-900/60"
                                    aria-label="{{ __('admin.inline.edit_field', ['field' => __('admin.posts.slug_label', ['slug' => $post->slug])]) }}"
                                >
                                    <span>{{ __('admin.posts.slug_label', ['slug' => $post->slug]) }}</span>
                                    <span class="rounded-full border border-slate-200 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-400 transition group-hover:border-indigo-200 group-hover:text-indigo-500 dark:border-slate-700 dark:text-slate-500">
                                        {{ __('admin.inline.edit_label') }}
                                    </span>
                                </button>
                            @endif
                        </div>
                    </div>
                </td>
                <td class="px-4 py-4">
                    @if ($post->categories->isNotEmpty())
                        <div class="flex flex-wrap gap-2">
                            @foreach ($post->categories as $category)
                                <flux:badge color="slate">{{ $category->name }}</flux:badge>
                            @endforeach
                        </div>
                    @else
                        <span class="text-xs text-slate-400 dark:text-slate-500">
                            {{ __('admin.posts.no_categories') }}
                        </span>
                    @endif
                </td>
                <td class="px-4 py-4">
                    <div class="flex flex-col gap-1">
                        <div class="flex items-center gap-2">
                            <flux:badge
                                x-show="isPublished({{ $post->id }}, {{ $post->isPublished() ? 'true' : 'false' }})"
                                color="green"
                                x-transition
                            >
                                <span x-text="isInFlight({{ $post->id }}) ? '{{ __('admin.posts.optimistic_inflight') }}' : '{{ __('admin.posts.status.published') }}'"></span>
                            </flux:badge>
                            <flux:badge
                                x-show="!isPublished({{ $post->id }}, {{ $post->isPublished() ? 'true' : 'false' }})"
                                color="amber"
                                x-transition
                            >
                                <span x-text="isInFlight({{ $post->id }}) ? '{{ __('admin.posts.optimistic_inflight') }}' : '{{ __('admin.posts.status.draft') }}'"></span>
                            </flux:badge>
                        </div>

                        <div
                            class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400"
                            x-show="isPending({{ $post->id }})"
                            x-transition
                        >
                            <svg class="h-4 w-4 animate-spin text-slate-400" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z" />
                            </svg>
                            <span x-text="lagging ? '{{ __('admin.posts.optimistic_waiting') }}' : '{{ __('admin.posts.optimistic_pending') }}'"></span>
                            <span
                                class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-700 ring-1 ring-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:ring-slate-700"
                                x-show="queuedPosition({{ $post->id }})"
                                x-text="'{{ __('admin.posts.optimistic_queued') }} #' + queuedPosition({{ $post->id }})"
                            ></span>
                        </div>
                    </div>
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
                        <flux:button
                            type="button"
                            size="sm"
                            variant="ghost"
                            wire:click="startEdit({{ $post->id }})"
                        >
                            {{ __('admin.posts.action_edit') }}
                        </flux:button>

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

                        <flux:button
                            x-show="isPublished({{ $post->id }}, {{ $post->isPublished() ? 'true' : 'false' }})"
                            x-on:click.prevent="queueAction('unpublish', {{ $post->id }}, {{ $post->isPublished() ? 'true' : 'false' }})"
                            size="sm"
                            color="amber"
                            icon="arrow-down-tray"
                            x-bind:disabled="isPending({{ $post->id }})"
                            x-bind:class="isPending({{ $post->id }}) ? 'opacity-60 cursor-not-allowed' : ''"
                        >
                            <span x-text="isPending({{ $post->id }}) ? '{{ __('admin.posts.optimistic_updating') }}' : '{{ __('admin.posts.action_unpublish') }}'"></span>
                        </flux:button>

                        <flux:button
                            x-show="!isPublished({{ $post->id }}, {{ $post->isPublished() ? 'true' : 'false' }})"
                            x-on:click.prevent="queueAction('publish', {{ $post->id }}, {{ $post->isPublished() ? 'true' : 'false' }})"
                            size="sm"
                            color="green"
                            icon="arrow-up-tray"
                            x-bind:disabled="isPending({{ $post->id }})"
                            x-bind:class="isPending({{ $post->id }}) ? 'opacity-60 cursor-not-allowed' : ''"
                        >
                            <span x-text="isPending({{ $post->id }}) ? '{{ __('admin.posts.optimistic_updating') }}' : '{{ __('admin.posts.action_publish') }}'"></span>
                        </flux:button>
                    </div>
                </td>
            </x-admin.table-row>
        @empty
            <x-admin.table-empty colspan="7" :message="__('admin.posts.empty')" />
        @endforelse
    </x-admin.table>
    <flux:modal name="post-form" wire:close="handleModalClosed">
        <div class="flex items-start justify-between gap-4">
            <div class="space-y-1">
                <flux:heading size="lg">
                    {{ $formMode === 'edit' ? __('admin.posts.modal.edit_title') : __('admin.posts.modal.create_title') }}
                </flux:heading>
                <flux:description>
                    {{ __('admin.posts.modal.description') }}
                </flux:description>
            </div>
            <flux:badge>
                {{ $formMode === 'edit' ? __('admin.posts.status.editing') : __('admin.posts.status.creating') }}
            </flux:badge>
        </div>

        <form class="mt-6 space-y-4" wire:submit.prevent="savePost">
            <flux:input
                wire:model.defer="title"
                label="{{ __('admin.posts.modal.fields.title') }}"
                placeholder="{{ __('admin.posts.modal.placeholders.title') }}"
                required
            />

            <flux:textarea
                wire:model.defer="body"
                rows="6"
                label="{{ __('admin.posts.modal.fields.body') }}"
                placeholder="{{ __('admin.posts.modal.placeholders.body') }}"
            />

            <flux:input
                wire:model.defer="description"
                label="{{ __('admin.posts.modal.fields.description') }}"
                placeholder="{{ __('admin.posts.modal.placeholders.description') }}"
            />

            <flux:input
                wire:model.defer="featuredImage"
                type="url"
                label="{{ __('admin.posts.modal.fields.featured_image') }}"
                placeholder="{{ __('admin.posts.modal.placeholders.featured_image') }}"
            />

            <div class="grid gap-4 md:grid-cols-2">
                <flux:input
                    wire:model.defer="publishedAt"
                    type="datetime-local"
                    label="{{ __('admin.posts.modal.fields.publish_at') }}"
                    description="{{ __('admin.posts.modal.hints.publish_at') }}"
                />

                <label class="flex items-center gap-2 rounded-lg border border-slate-200/80 bg-slate-50/70 px-3 py-2 text-sm font-medium text-slate-700 dark:border-slate-800 dark:bg-slate-900/40 dark:text-slate-200">
                    <input
                        type="checkbox"
                        class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-700"
                        wire:model.defer="isDraft"
                    >
                    <span>{{ __('admin.posts.modal.fields.save_as_draft') }}</span>
                </label>
            </div>

            <flux:input
                wire:model.defer="tagsInput"
                label="{{ __('admin.posts.modal.fields.tags') }}"
                placeholder="{{ __('admin.posts.modal.placeholders.tags') }}"
            />

            <flux:fieldset legend="{{ __('admin.posts.modal.fields.categories') }}">
                @if ($availableCategories->isNotEmpty())
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach ($availableCategories as $category)
                            <label class="flex items-center gap-2 rounded-lg border border-slate-200/80 bg-white/70 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-900/60">
                                <input
                                    type="checkbox"
                                    class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-700"
                                    wire:model.defer="categories"
                                    value="{{ $category->id }}"
                                >
                                <span class="truncate">{{ $category->name }}</span>
                            </label>
                        @endforeach
                    </div>
                @else
                    <flux:description>{{ __('admin.posts.modal.empty_categories') }}</flux:description>
                @endif
                <flux:error name="categories" />
            </flux:fieldset>

            <div class="flex items-center justify-end gap-3 pt-4">
                <flux:button type="button" variant="ghost" wire:click="closeModal">
                    {{ __('admin.posts.modal.cancel') }}
                </flux:button>
                <flux:button type="submit" color="primary" wire:target="savePost" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="savePost">
                        {{ $formMode === 'edit' ? __('admin.posts.modal.update') : __('admin.posts.modal.save') }}
                    </span>
                    <span wire:loading wire:target="savePost">
                        {{ __('admin.posts.modal.saving') }}
                    </span>
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
