<?php

use App\Enums\CommentStatus;
use App\Livewire\Concerns\ManagesBulkActions;
use App\Livewire\Concerns\ManagesPerPage;
use App\Livewire\Concerns\ManagesSearch;
use App\Livewire\Concerns\ManagesSorting;
use App\Models\Comment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use function Livewire\Volt\layout;
use function Livewire\Volt\title;

layout('components.layouts.admin');
title(__('admin.comments.title'));

new class extends Component {
    use AuthorizesRequests;
    use ManagesBulkActions;
    use ManagesPerPage;
    use ManagesSearch;
    use ManagesSorting;
    use WithPagination;

    public ?string $status = null;
    public ?array $bulkFeedback = null;

    protected $queryString = [
        'status' => ['except' => null],
        'page' => ['except' => 1],
    ];

    public function mount(): void
    {
        $this->authorize('access-admin');

        $this->queryString = array_merge(
            $this->queryString,
            $this->queryStringManagesSorting()
        );
        $this->queryString['perPage'] = $this->perPageQueryStringConfig();
        [$this->sortField, $this->sortDirection] = $this->resolvedSort();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatedStatus($value): void
    {
        $this->status = $this->sanitizeStatus($value);
    }

    public function clearFilters(): void
    {
        $this->clearSearch();
        $this->status = null;
    }

    public function with(): array
    {
        $filters = $this->resolvedFilters();

        $comments = $this->baseQuery($filters)
            ->paginate($this->perPage)
            ->withQueryString();

        $this->setCurrentPageIds($comments->pluck('id'));

        return [
            'comments' => $comments,
            'searchTerm' => $filters['search'],
            'activeStatus' => $filters['status'],
            'isFiltered' => $this->hasSearch() || $filters['status'] !== null,
        ];
    }

    public function bulkApprove(): void
    {
        $this->processBulkStatusChange(CommentStatus::Approved, 'approved');
    }

    public function bulkReject(): void
    {
        $this->processBulkStatusChange(CommentStatus::Rejected, 'rejected');
    }

    public function bulkDelete(): void
    {
        $this->processBulkDelete();
    }

    public function deleteComment(int $commentId): void
    {
        $comment = $this->findComment($commentId);

        $this->authorize('access-admin');

        $post = $comment->post;
        $comment->delete();

        // Update post comment count
        if ($post) {
            $post->loadCount('comments');
        }

        session()->flash('status', __('admin.comments.deleted'));

        $this->resetPage();
    }

    public function approveComment(int $commentId): void
    {
        $this->changeCommentStatus($commentId, CommentStatus::Approved);
    }

    public function rejectComment(int $commentId): void
    {
        $this->changeCommentStatus($commentId, CommentStatus::Rejected);
    }

    private function changeCommentStatus(int $commentId, CommentStatus $status): void
    {
        $comment = $this->findComment($commentId);

        $this->authorize('access-admin');

        $comment->forceFill(['status' => $status])->save();

        session()->flash('status', __('admin.comments.status_changed'));
    }

    private function processBulkStatusChange(CommentStatus $status, string $action): void
    {
        $selectedIds = collect($this->getSelectedIds());

        if ($selectedIds->isEmpty()) {
            return;
        }

        $comments = $this->baseQuery()
            ->whereIn('id', $selectedIds)
            ->get()
            ->keyBy('id');

        $updated = 0;
        $failures = [];

        foreach ($selectedIds as $commentId) {
            $comment = $comments->get($commentId);

            if (! $comment) {
                $failures[] = [
                    'id' => $commentId,
                    'title' => "#{$commentId}",
                    'reason' => __('admin.comments.bulk_not_found'),
                ];

                continue;
            }

            try {
                $this->authorize('access-admin');
            } catch (\Throwable $exception) {
                $failures[] = [
                    'id' => $commentId,
                    'title' => "#{$commentId}",
                    'reason' => __('admin.comments.bulk_unauthorized'),
                ];

                continue;
            }

            $comment->forceFill(['status' => $status])->save();
            $updated++;
        }

        $this->bulkFeedback = [
            'status' => empty($failures) ? 'success' : 'warning',
            'action' => $action,
            'total' => $selectedIds->count(),
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
    }

    private function processBulkDelete(): void
    {
        $selectedIds = collect($this->getSelectedIds());

        if ($selectedIds->isEmpty()) {
            return;
        }

        $comments = $this->baseQuery()
            ->whereIn('id', $selectedIds)
            ->get()
            ->keyBy('id');

        $updated = 0;
        $failures = [];
        $affectedPosts = collect();

        foreach ($selectedIds as $commentId) {
            $comment = $comments->get($commentId);

            if (! $comment) {
                $failures[] = [
                    'id' => $commentId,
                    'title' => "#{$commentId}",
                    'reason' => __('admin.comments.bulk_not_found'),
                ];

                continue;
            }

            try {
                $this->authorize('access-admin');
            } catch (\Throwable $exception) {
                $failures[] = [
                    'id' => $commentId,
                    'title' => "#{$commentId}",
                    'reason' => __('admin.comments.bulk_unauthorized'),
                ];

                continue;
            }

            if ($comment->post) {
                $affectedPosts->push($comment->post);
            }

            $comment->delete();
            $updated++;
        }

        // Update comment counts for affected posts
        $affectedPosts->unique('id')->each(function ($post) {
            $post->loadCount('comments');
        });

        $this->bulkFeedback = [
            'status' => empty($failures) ? 'success' : 'warning',
            'action' => 'deleted',
            'total' => $selectedIds->count(),
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

        $this->resetPage();
    }

    private function baseQuery(?array $filters = null): Builder
    {
        $filters ??= $this->resolvedFilters();

        return Comment::query()
            ->with(['user:id,name', 'post:id,title,slug'])
            ->when($filters['search'] !== '', function ($query) use ($filters) {
                $query->where('content', 'like', '%'.$filters['search'].'%');
            })
            ->when($filters['status'] !== null, function ($query) use ($filters) {
                $query->where('status', CommentStatus::from($filters['status']));
            })
            ->tap(fn (Builder $builder) => $this->applySort($builder));
    }

    private function applySort(Builder $query): Builder
    {
        [$sortField, $sortDirection] = $this->resolvedSort();

        return match ($sortField) {
            'status' => $query->orderBy('status', $sortDirection),
            'created_at' => $query->orderBy('created_at', $sortDirection),
            default => $query->orderBy('created_at', $sortDirection),
        };
    }

    protected function defaultSortField(): string
    {
        return 'created_at';
    }

    protected function defaultSortDirection(): string
    {
        return 'desc';
    }

    protected function sortableColumns(): array
    {
        return ['status', 'created_at'];
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
        ];
    }

    private function hasSearch(): bool
    {
        return $this->getSearchTerm() !== '';
    }

    private function sanitizeStatus(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $status = CommentStatus::tryFrom($value);
        return $status?->value;
    }

    private function findComment(int $commentId): Comment
    {
        return Comment::query()->findOrFail($commentId);
    }
}; ?>

<div>
<div class="space-y-6">
    <flux:page-header
        :heading="__('admin.comments.heading')"
        :description="__('admin.comments.description')"
    />

    @if (session('status'))
        <flux:callout color="green">
            {{ session('status') }}
        </flux:callout>
    @endif

    @if ($bulkFeedback)
        <flux:callout color="{{ $bulkFeedback['status'] === 'success' ? 'green' : 'amber' }}">
            <div class="space-y-2">
                <p>
                    {{ __('admin.comments.bulk_success', ['action' => $bulkFeedback['action'], 'count' => $bulkFeedback['updated']]) }}
                    @if ($bulkFeedback['updated'] !== $bulkFeedback['total'])
                        {{ __('admin.comments.bulk_summary', ['updated' => $bulkFeedback['updated'], 'total' => $bulkFeedback['total']]) }}
                    @endif
                </p>
                @if (!empty($bulkFeedback['failures']))
                    <details class="text-sm">
                        <summary class="cursor-pointer font-semibold">{{ trans_choice('admin.comments.bulk_failures', count($bulkFeedback['failures']), ['count' => count($bulkFeedback['failures'])]) }}</summary>
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
                    {{ trans_choice('admin.comments.bulk_selected', $this->selectedCount, ['count' => $this->selectedCount]) }}
                </p>
                <div class="flex flex-wrap items-center gap-2">
                    <flux:button
                        type="button"
                        size="sm"
                        color="green"
                        wire:click="bulkApprove"
                        wire:confirm="{{ trans_choice('admin.comments.bulk_confirm_approve', $this->selectedCount, ['count' => $this->selectedCount]) }}"
                        wire:loading.attr="disabled"
                        wire:target="bulkApprove"
                    >
                        <span wire:loading.remove wire:target="bulkApprove">{{ __('admin.comments.bulk_approve') }}</span>
                        <span wire:loading.delay.500ms wire:target="bulkApprove" class="inline-flex items-center gap-1">
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
                        wire:click="bulkReject"
                        wire:confirm="{{ trans_choice('admin.comments.bulk_confirm_reject', $this->selectedCount, ['count' => $this->selectedCount]) }}"
                        wire:loading.attr="disabled"
                        wire:target="bulkReject"
                    >
                        <span wire:loading.remove wire:target="bulkReject">{{ __('admin.comments.bulk_reject') }}</span>
                        <span wire:loading.delay.500ms wire:target="bulkReject" class="inline-flex items-center gap-1">
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
                        color="red"
                        wire:click="bulkDelete"
                        wire:confirm="{{ trans_choice('admin.comments.bulk_confirm_delete', $this->selectedCount, ['count' => $this->selectedCount]) }}"
                        wire:loading.attr="disabled"
                        wire:target="bulkDelete"
                    >
                        <span wire:loading.remove wire:target="bulkDelete">{{ __('admin.comments.bulk_delete') }}</span>
                        <span wire:loading.delay.500ms wire:target="bulkDelete" class="inline-flex items-center gap-1">
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
                        {{ __('admin.comments.bulk_clear') }}
                    </flux:button>
                </div>
            </div>
        </div>
    @endif

    <x-admin.table
        :pagination="$comments"
        per-page-mode="livewire"
        per-page-field="perPage"
        :per-page-options="$this->perPageOptions"
        :per-page-value="$perPage"
        aria-label="{{ __('admin.comments.table.aria_label') }}"
    >
        <x-slot name="toolbar">
            <div class="flex w-full flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex flex-1 flex-wrap items-center gap-3">
                    <div class="relative w-full md:w-64">
                        <label for="comment-search" class="sr-only">{{ __('admin.comments.search.label') }}</label>
                        <input
                            id="comment-search"
                            type="search"
                            wire:model.live.debounce.300ms="search"
                            placeholder="{{ __('admin.comments.search.placeholder') }}"
                            class="block w-full rounded-xl border border-slate-200 bg-white/80 px-4 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100"
                        >
                        @if ($searchTerm !== '')
                            <button
                                type="button"
                                wire:click="$set('search', '')"
                                class="absolute inset-y-0 right-3 flex items-center text-slate-400 transition hover:text-slate-600 dark:hover:text-slate-200"
                                aria-label="{{ __('admin.comments.search.clear') }}"
                            >
                                &times;
                            </button>
                        @endif
                    </div>

                    <div class="w-full sm:w-48">
                        <label for="status-filter" class="sr-only">{{ __('admin.comments.filters.status') }}</label>
                        <select
                            id="status-filter"
                            wire:model.live="status"
                            class="block w-full rounded-xl border border-slate-200 bg-white/80 px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100"
                        >
                            <option value="">{{ __('admin.comments.filters.status_all') }}</option>
                            <option value="{{ \App\Enums\CommentStatus::Pending->value }}">{{ __('admin.comments.status.pending') }}</option>
                            <option value="{{ \App\Enums\CommentStatus::Approved->value }}">{{ __('admin.comments.status.approved') }}</option>
                            <option value="{{ \App\Enums\CommentStatus::Rejected->value }}">{{ __('admin.comments.status.rejected') }}</option>
                        </select>
                    </div>

                    @if ($isFiltered)
                        <flux:button type="button" size="sm" color="secondary" wire:click="clearFilters">
                            {{ __('admin.comments.filters.clear') }}
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
                        <option value="created_at">{{ __('admin.comments.table.date') }}</option>
                        <option value="status">{{ __('admin.comments.table.status') }}</option>
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
        </x-slot>

        <x-slot name="head">
            <tr>
                <th class="px-4 py-3 text-left">
                    <input
                        type="checkbox"
                        wire:model.live="selectAll"
                        class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                        aria-label="{{ __('admin.comments.table.select_all') }}"
                    >
                </th>
                <x-admin.table-head :columns="[
                    ['label' => __('admin.comments.table.author')],
                    ['label' => __('admin.comments.table.post')],
                    ['label' => __('admin.comments.table.comment')],
                    ['label' => __('admin.comments.table.status'), 'sortable' => true, 'field' => 'status'],
                    ['label' => __('admin.comments.table.date'), 'sortable' => true, 'field' => 'created_at'],
                    ['label' => __('admin.comments.table.actions'), 'class' => 'text-right'],
                ]" :sort-field="$sortField" :sort-direction="$sortDirection" />
            </tr>
        </x-slot>

        @forelse ($comments as $comment)
            <x-admin.table-row
                wire:key="comment-{{ $comment->id }}"
                :interactive="true"
            >
                <td class="px-4 py-4">
                    <input
                        type="checkbox"
                        wire:click="toggleSelection({{ $comment->id }})"
                        @checked(in_array($comment->id, $selected))
                        class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                        aria-label="{{ __('admin.comments.table.select_comment', ['id' => $comment->id]) }}"
                    >
                </td>
                <td class="px-4 py-4">
                    <div class="flex flex-col gap-1">
                        <span class="font-medium">{{ $comment->user?->name ?? __('admin.comments.unknown_user') }}</span>
                        <span class="text-xs text-slate-400 dark:text-slate-500">#{{ $comment->id }}</span>
                    </div>
                </td>
                <td class="px-4 py-4">
                    <div class="max-w-xs">
                        @if ($comment->post)
                            <a href="{{ route('posts.show', $comment->post) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300" target="_blank">
                                {{ \Illuminate\Support\Str::limit($comment->post->title, 40) }}
                            </a>
                        @else
                            <span class="text-sm text-slate-400 dark:text-slate-500">{{ __('admin.comments.unknown_post') }}</span>
                        @endif
                    </div>
                </td>
                <td class="px-4 py-4">
                    <div class="max-w-md">
                        <p class="text-sm text-slate-700 dark:text-slate-300">
                            {{ \Illuminate\Support\Str::limit($comment->content, 100) }}
                        </p>
                    </div>
                </td>
                <td class="px-4 py-4">
                    @if ($comment->status === \App\Enums\CommentStatus::Approved)
                        <flux:badge color="green">{{ __('admin.comments.status.approved') }}</flux:badge>
                    @elseif ($comment->status === \App\Enums\CommentStatus::Rejected)
                        <flux:badge color="red">{{ __('admin.comments.status.rejected') }}</flux:badge>
                    @else
                        <flux:badge color="amber">{{ __('admin.comments.status.pending') }}</flux:badge>
                    @endif
                </td>
                <td class="px-4 py-4">
                    {{ $comment->created_at?->diffForHumans() ?? '—' }}
                </td>
                <td class="px-4 py-4 text-right">
                    <div class="inline-flex items-center gap-2">
                        @if ($comment->status !== \App\Enums\CommentStatus::Approved)
                            <flux:button
                                type="button"
                                size="sm"
                                color="green"
                                wire:click="approveComment({{ $comment->id }})"
                                wire:loading.attr="disabled"
                                wire:target="approveComment"
                            >
                                <span wire:loading.remove wire:target="approveComment">{{ __('admin.comments.status.approved') }}</span>
                                <span wire:loading wire:target="approveComment" class="inline-flex items-center gap-1">
                                    <svg class="h-3 w-3 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </span>
                            </flux:button>
                        @endif

                        @if ($comment->status !== \App\Enums\CommentStatus::Rejected)
                            <flux:button
                                type="button"
                                size="sm"
                                color="amber"
                                wire:click="rejectComment({{ $comment->id }})"
                                wire:loading.attr="disabled"
                                wire:target="rejectComment"
                            >
                                <span wire:loading.remove wire:target="rejectComment">{{ __('admin.comments.status.rejected') }}</span>
                                <span wire:loading wire:target="rejectComment" class="inline-flex items-center gap-1">
                                    <svg class="h-3 w-3 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </span>
                            </flux:button>
                        @endif

                        <flux:button
                            type="button"
                            size="sm"
                            color="red"
                            icon="trash"
                            wire:click="deleteComment({{ $comment->id }})"
                            wire:confirm="{{ __('admin.comments.confirm_delete') }}"
                            wire:loading.attr="disabled"
                            wire:target="deleteComment"
                        >
                            <span wire:loading.remove wire:target="deleteComment">{{ __('admin.comments.action_delete') }}</span>
                            <span wire:loading.delay.500ms wire:target="deleteComment" class="inline-flex items-center gap-1">
                                <svg class="h-3 w-3 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </span>
                        </flux:button>
                    </div>
                </td>
            </x-admin.table-row>
        @empty
            <x-admin.table-empty colspan="7" :message="__('admin.comments.empty')" />
        @endforelse
    </x-admin.table>
</div>
</div>
