<?php

use App\Livewire\Concerns\ManagesPerPage;
use App\Livewire\Concerns\ManagesSearch;
use App\Livewire\Concerns\ManagesSorting;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use function Livewire\Volt\layout;
use function Livewire\Volt\title;

layout('components.layouts.admin');
title(__('admin.users.title'));

new class extends Component {
    use AuthorizesRequests;
    use ManagesPerPage;
    use ManagesSearch;
    use ManagesSorting;
    use WithPagination;

    #[Url(except: 1)]
    public int $page = 1;
    public bool $showCreateModal = false;
    public bool $showDeleteModal = false;
    public array $createForm = [
        'name' => '',
        'email' => '',
        'password' => '',
        'password_confirmation' => '',
        'is_admin' => false,
        'is_author' => false,
        'is_banned' => false,
    ];
    public ?int $deletingUserId = null;
    public array $deleteContext = [
        'name' => '',
        'posts' => 0,
        'comments' => 0,
    ];
    public string $deleteStrategy = 'transfer';
    public ?int $transferTarget = null;
    public array $transferOptions = [];

    protected $listeners = ['user-updated' => '$refresh'];
    protected $queryString = [
        'perPage' => ['except' => 20],
        'search' => ['except' => ''],
        'sortField' => ['as' => 'sort', 'except' => 'name'],
        'sortDirection' => ['as' => 'direction', 'except' => 'asc'],
    ];

    public function mount(int $page = 1): void
    {
        $requestedPage = request()->query('page', $page);
        $this->setPage(max(1, (int) $requestedPage));
        [$this->sortField, $this->sortDirection] = $this->resolvedSort();
    }

    protected function defaultSortField(): string
    {
        return 'name';
    }

    protected function defaultSortDirection(): string
    {
        return 'asc';
    }

    protected function sortableColumns(): array
    {
        return ['name', 'email', 'created_at', 'posts_count'];
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

    public function applySearchShortcut(): void
    {
        $this->resetPage();
        $this->search = $this->sanitizeSearch($this->search);
    }

    public function clearSearch(): void
    {
        $this->resetPage();
        $this->search = '';
    }

    public function clearFilters(): void
    {
        $this->clearSearch();
    }

    public function openCreateModal(): void
    {
        $this->resetCreateForm();
        $this->resetErrorBag();
        $this->resetValidation();
        $this->showCreateModal = true;
    }

    public function updatedCreateForm($value, string $key): void
    {
        $property = "createForm.{$key}";
        $this->validateOnly($property, $this->createRules());
    }

    public function createUser(): void
    {
        $this->authorize('create', User::class);

        $validated = $this->validate($this->createRules());

        $user = User::create([
            'name' => $validated['createForm']['name'],
            'email' => $validated['createForm']['email'],
            'password' => Hash::make($validated['createForm']['password']),
            'is_admin' => (bool) $validated['createForm']['is_admin'],
            'is_author' => (bool) $validated['createForm']['is_author'],
            'is_banned' => (bool) $validated['createForm']['is_banned'],
        ]);

        session()->flash('status', __('admin.users.created', ['name' => $user->name]));

        $this->resetCreateForm();
        $this->showCreateModal = false;
        $this->resetPage();
        $this->dispatch('user-updated');
    }

    public function confirmDelete(User $user): void
    {
        try {
            $this->authorize('delete', $user);
        } catch (AuthorizationException $exception) {
            session()->flash('error', $exception->getMessage());

            return;
        }

        $this->deletingUserId = $user->id;
        $this->deleteStrategy = 'transfer';
        $this->transferTarget = auth()->id();
        $this->deleteContext = [
            'name' => $user->name,
            'posts' => Post::withoutGlobalScopes()->where('user_id', $user->id)->count(),
            'comments' => Comment::where('user_id', $user->id)->count(),
        ];
        $this->transferOptions = User::query()
            ->whereKeyNot($user->id)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $candidate) => [
                'id' => $candidate->id,
                'name' => $candidate->name,
            ])
            ->all();

        $this->resetErrorBag();
        $this->resetValidation();
        $this->showDeleteModal = true;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->deletingUserId = null;
        $this->deleteContext = [
            'name' => '',
            'posts' => 0,
            'comments' => 0,
        ];
        $this->deleteStrategy = 'transfer';
        $this->transferTarget = auth()->id();
        $this->transferOptions = [];
    }

    public function deleteUser(): void
    {
        if (! $this->deletingUserId) {
            return;
        }

        $user = User::findOrFail($this->deletingUserId);

        try {
            $this->authorize('delete', $user);
        } catch (AuthorizationException $exception) {
            session()->flash('error', $exception->getMessage());
            $this->cancelDelete();

            return;
        }

        $strategy = $this->resolveDeleteStrategy($this->deleteStrategy);

        if ($strategy === 'delete') {
            $postIds = Post::withoutGlobalScopes()
                ->where('user_id', $user->id)
                ->pluck('id');

            if ($postIds->isNotEmpty()) {
                Comment::whereIn('post_id', $postIds)->delete();
                Post::withoutGlobalScopes()->whereIn('id', $postIds)->delete();
            }
        } else {
            $targetId = $this->transferTarget ?? auth()->id();

            if ($targetId === $user->id) {
                $targetId = auth()->id();
            }

            $target = User::find($targetId) ?? auth()->user();

            Post::withoutGlobalScopes()
                ->where('user_id', $user->id)
                ->update(['user_id' => $target->id]);
        }

        Comment::where('user_id', $user->id)->delete();

        $user->delete();

        session()->flash('status', __('admin.users.deleted', ['name' => $user->name]));

        $this->cancelDelete();
        $this->resetPage();
        $this->dispatch('user-updated');
    }

    protected function resolveDeleteStrategy(?string $strategy): string
    {
        return in_array($strategy, ['transfer', 'delete'], true) ? $strategy : 'transfer';
    }

    protected function createRules(): array
    {
        return [
            'createForm.name' => ['required', 'string', 'max:255'],
            'createForm.email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'createForm.password' => ['required', 'string', 'min:8', 'confirmed'],
            'createForm.is_admin' => ['boolean'],
            'createForm.is_author' => ['boolean'],
            'createForm.is_banned' => ['boolean'],
        ];
    }

    protected function resetCreateForm(): void
    {
        $this->createForm = [
            'name' => '',
            'email' => '',
            'password' => '',
            'password_confirmation' => '',
            'is_admin' => false,
            'is_author' => false,
            'is_banned' => false,
        ];
    }

    public function with(): array
    {
        $search = trim((string) $this->search);
        [$sortField, $sortDirection] = $this->resolvedSort();

        $query = User::query()
            ->withCount([
                'posts as posts_count' => fn ($query) => $query->withoutGlobalScopes(),
                'comments',
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            });

        // Apply sorting
        match ($sortField) {
            'name' => $query->orderBy('name', $sortDirection),
            'email' => $query->orderBy('email', $sortDirection),
            'created_at' => $query->orderBy('created_at', $sortDirection),
            'posts_count' => $query->orderBy('posts_count', $sortDirection),
            default => $query->orderBy('name', $sortDirection),
        };

        // Add secondary sorting for consistency
        if ($sortField !== 'name') {
            $query->orderBy('name');
        }

        $users = $query->paginate($this->perPage)->withQueryString();

        return [
            'users' => $users,
            'searchTerm' => $search,
            'isFiltered' => $search !== '',
        ];
    }

    public function toggleAuthor(User $user): void
    {
        $this->authorize('update', $user);

        if ($user->is(auth()->user())) {
            session()->flash('status', __('admin.users.cannot_self_update'));

            return;
        }

        $user->is_author = ! $user->is_author;
        $user->save();

        session()->flash('status', $user->is_author
            ? __('admin.users.made_author', ['name' => $user->name])
            : __('admin.users.removed_author', ['name' => $user->name]));

        $this->dispatch('user-updated');
    }

    public function toggleAdmin(User $user): void
    {
        $this->authorize('update', $user);

        if ($user->is(auth()->user())) {
            session()->flash('status', __('admin.users.cannot_self_update'));

            return;
        }

        $user->is_admin = ! $user->is_admin;
        $user->save();

        session()->flash('status', $user->is_admin
            ? __('admin.users.made_admin', ['name' => $user->name])
            : __('admin.users.removed_admin', ['name' => $user->name]));

        $this->dispatch('user-updated');
    }

    public function toggleBan(User $user): void
    {
        if ($user->is(auth()->user())) {
            session()->flash('status', __('admin.users.cannot_self_update'));

            return;
        }

        try {
            $this->authorize('ban', $user);
        } catch (AuthorizationException $exception) {
            session()->flash('error', $exception->getMessage());

            return;
        }

        $user->is_banned = ! $user->is_banned;
        $user->save();

        session()->flash('status', $user->is_banned
            ? __('admin.users.banned', ['name' => $user->name])
            : __('admin.users.unbanned', ['name' => $user->name]));

        $this->dispatch('user-updated');
    }
}; ?>

<div class="space-y-6">
    <flux:page-header
        :heading="__('admin.users.heading')"
        :description="__('admin.users.description')"
    >
        <flux:button color="primary" icon="user-plus" wire:click="openCreateModal" data-admin-create-trigger>
            {{ __('admin.users.create_button') }}
        </flux:button>
    </flux:page-header>

    @if (session('error'))
        <flux:callout color="red">
            {{ session('error') }}
        </flux:callout>
    @endif

    @if (session('status'))
        <flux:callout color="green">
            {{ session('status') }}
        </flux:callout>
    @endif
    @if (session('error'))
        <flux:callout color="red">
            {{ session('error') }}
        </flux:callout>
    @endif

    <x-admin.table
        :pagination="$users"
        per-page-mode="livewire"
        per-page-field="perPage"
        :per-page-options="$this->perPageOptions"
        :per-page-value="$perPage"
        aria-label="{{ __('admin.users.table.aria_label') }}"
    >
        <x-slot name="toolbar">
            <div class="flex w-full flex-wrap items-center gap-3">
                <div class="flex flex-1 items-center gap-2">
                    <div class="relative w-full max-w-md">
                        <input
                            type="search"
                            wire:model.live.debounce.400ms="search"
                            wire:keydown.enter.prevent="applySearchShortcut"
                            data-admin-search-input
                            placeholder="{{ __('admin.users.search_placeholder') }}"
                            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 pr-10 text-sm text-slate-800 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                        />
                        <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400">
                            ⌘K
                        </span>
                    </div>

                    @if ($isFiltered)
                        <flux:button
                            variant="ghost"
                            size="sm"
                            icon="x-mark"
                            wire:click="clearFilters"
                        >
                            {{ __('admin.users.clear_filters') }}
                        </flux:button>
                    @endif
                </div>

                <div class="flex items-center gap-2">
                    <label for="user-sort-field" class="sr-only">{{ __('admin.users.sort.label') }}</label>
                    <select
                        id="user-sort-field"
                        wire:model.live="sortField"
                        class="w-full rounded-xl border border-slate-200 bg-white/80 px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100 md:w-44"
                    >
                        <option value="name">{{ __('admin.users.sort.name') }}</option>
                        <option value="email">{{ __('admin.users.sort.email') }}</option>
                        <option value="created_at">{{ __('admin.users.sort.joined') }}</option>
                        <option value="posts_count">{{ __('admin.users.sort.posts') }}</option>
                    </select>

                    <flux:button
                        type="button"
                        size="sm"
                        variant="ghost"
                        icon="arrows-up-down"
                        wire:click="sortBy('{{ $sortField }}')"
                        aria-label="{{ $sortDirection === 'asc' ? __('admin.users.sort.asc') : __('admin.users.sort.desc') }}"
                    >
                        {{ $sortDirection === 'asc' ? __('admin.users.sort.asc') : __('admin.users.sort.desc') }}
                    </flux:button>
                </div>
            </div>

            @if ($isFiltered)
                <div class="flex items-center gap-2">
                    <flux:badge color="indigo">
                        {{ __('admin.users.filtering', ['term' => $searchTerm]) }}
                    </flux:badge>
                </div>
            @endif
        </x-slot>

        <x-slot name="head">
            <x-admin.table-head :columns="[
                ['label' => __('admin.users.table.user')],
                ['label' => __('admin.users.table.roles')],
                ['label' => __('admin.users.table.status')],
                ['label' => __('admin.users.table.content')],
                ['label' => __('admin.users.table.joined')],
                ['label' => __('admin.users.table.actions'), 'class' => 'text-right'],
            ]" />
        </x-slot>

        @forelse ($users as $user)
            <x-admin.table-row
                wire:key="user-{{ $user->id }}"
                :interactive="true"
                data-row-id="{{ $user->id }}"
                data-row-label="{{ $user->name }}"
            >
                <td class="px-4 py-4">
                    <div class="flex flex-col">
                        <span class="font-semibold">{{ $user->name }}</span>
                        <span class="text-xs text-slate-500 dark:text-slate-400">{{ $user->email }}</span>
                    </div>
                </td>
                <td class="px-4 py-4">
                    <div class="flex flex-wrap gap-2">
                        @if ($user->is_admin)
                            <flux:badge color="orange">{{ __('admin.users.role_admin') }}</flux:badge>
                        @endif
                        @if ($user->is_author)
                            <flux:badge color="blue">{{ __('admin.users.role_author') }}</flux:badge>
                        @endif
                        @if (! $user->is_admin && ! $user->is_author)
                            <flux:badge>{{ __('admin.users.role_reader') }}</flux:badge>
                        @endif
                    </div>
                </td>
                <td class="px-4 py-4">
                    @if ($user->is_banned)
                        <flux:badge color="red">{{ __('admin.users.status_banned') }}</flux:badge>
                    @else
                        <flux:badge color="green">{{ __('admin.users.status_active') }}</flux:badge>
                    @endif
                </td>
                <td class="px-4 py-4">
                    <div class="flex flex-col gap-1 text-xs text-slate-600 dark:text-slate-300">
                        <span class="font-medium">
                            {{ trans_choice('admin.users.posts_count', $user->posts_count, ['count' => $user->posts_count]) }}
                        </span>
                        <span>
                            {{ trans_choice('admin.users.comments_count', $user->comments_count, ['count' => $user->comments_count]) }}
                        </span>
                    </div>
                </td>
                <td class="px-4 py-4">
                    {{ $user->created_at?->diffForHumans() ?? '—' }}
                </td>
                <td class="px-4 py-4 text-right">
                    <div class="flex flex-col items-end gap-3">
                        <div class="flex flex-wrap justify-end gap-4">
                            <label class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                <div class="relative">
                                    <flux:switch
                                        wire:click="toggleAdmin({{ $user->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="toggleAdmin"
                                        :checked="$user->is_admin"
                                        :disabled="$user->is(auth()->user())"
                                        aria-label="{{ __('admin.users.action_toggle_admin', ['name' => $user->name]) }}"
                                    />
                                    <div wire:loading wire:target="toggleAdmin" class="absolute inset-0 flex items-center justify-center">
                                        <svg class="h-4 w-4 animate-spin text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <span>{{ __('admin.users.role_admin') }}</span>
                            </label>

                            <label class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                <div class="relative">
                                    <flux:switch
                                        wire:click="toggleAuthor({{ $user->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="toggleAuthor"
                                        :checked="$user->is_author"
                                        :disabled="$user->is(auth()->user())"
                                        aria-label="{{ __('admin.users.action_toggle_author', ['name' => $user->name]) }}"
                                    />
                                    <div wire:loading wire:target="toggleAuthor" class="absolute inset-0 flex items-center justify-center">
                                        <svg class="h-4 w-4 animate-spin text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <span>{{ __('admin.users.role_author') }}</span>
                            </label>

                            <label class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                <div class="relative">
                                    <flux:switch
                                        wire:click="toggleBan({{ $user->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="toggleBan"
                                        :checked="$user->is_banned"
                                        :disabled="$user->is(auth()->user())"
                                        aria-label="{{ $user->is_banned ? __('admin.users.action_unban') : __('admin.users.action_ban') }}"
                                    />
                                    <div wire:loading wire:target="toggleBan" class="absolute inset-0 flex items-center justify-center">
                                        <svg class="h-4 w-4 animate-spin text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <span>{{ __('admin.users.status_banned') }}</span>
                            </label>
                        </div>

                        <div class="flex items-center gap-2">
                            <flux:button
                                type="button"
                                size="sm"
                                color="red"
                                icon="trash"
                                wire:click="confirmDelete({{ $user->id }})"
                                data-row-delete
                            >
                                {{ __('admin.users.action_delete') }}
                            </flux:button>
                        </div>
                    </div>
                </td>
            </x-admin.table-row>
        @empty
            <x-admin.table-empty colspan="6" :message="__('admin.users.empty')" />
        @endforelse
    </x-admin.table>
</div>

@if ($showCreateModal)
    <div class="fixed inset-0 z-50 flex items-start justify-center bg-slate-900/60 px-4 py-10 backdrop-blur" data-admin-modal="user-create">
        <div class="absolute inset-0" wire:click="$set('showCreateModal', false)" data-admin-modal-close></div>
        <div class="relative z-10 w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-slate-900">
            <div class="flex items-start justify-between border-b border-slate-200 px-6 py-4 dark:border-slate-800">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-50">
                        {{ __('admin.users.create_heading') }}
                    </h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        {{ __('admin.users.create_subheading') }}
                    </p>
                </div>
                <button
                    type="button"
                    class="rounded-full p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 dark:hover:bg-slate-800"
                    wire:click="$set('showCreateModal', false)"
                    aria-label="{{ __('admin.users.close_modal') }}"
                    data-admin-modal-close
                >
                    &times;
                </button>
            </div>

            <form wire:submit.prevent="createUser" class="space-y-6 p-6">
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <label class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                            {{ __('admin.users.field_name') }}
                        </label>
                        <input
                            type="text"
                            wire:model.live="createForm.name"
                            class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                            required
                            autofocus
                        />
                        @error('createForm.name')
                            <p class="text-xs text-rose-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                            {{ __('admin.users.field_email') }}
                        </label>
                        <input
                            type="email"
                            wire:model.live="createForm.email"
                            class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                            required
                        />
                        @error('createForm.email')
                            <p class="text-xs text-rose-500">{{ $message }}</p>
                        @else
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                {{ __('admin.users.email_format_hint') }}
                            </p>
                        @enderror
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <label class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                            {{ __('admin.users.field_password') }}
                        </label>
                        <input
                            type="password"
                            wire:model.live="createForm.password"
                            class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                            required
                        />
                        @error('createForm.password')
                            <p class="text-xs text-rose-500">{{ $message }}</p>
                        @else
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                {{ __('admin.users.password_format_hint') }}
                            </p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                            {{ __('admin.users.field_password_confirmation') }}
                        </label>
                        <input
                            type="password"
                            wire:model.live="createForm.password_confirmation"
                            class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                            required
                        />
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <label class="flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 transition dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200">
                        <input
                            type="checkbox"
                            wire:model.live="createForm.is_admin"
                            class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                        >
                        {{ __('admin.users.role_admin') }}
                    </label>

                    <label class="flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 transition dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200">
                        <input
                            type="checkbox"
                            wire:model.live="createForm.is_author"
                            class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                        >
                        {{ __('admin.users.role_author') }}
                    </label>

                    <label class="flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 transition dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200">
                        <input
                            type="checkbox"
                            wire:model.live="createForm.is_banned"
                            class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                        >
                        {{ __('admin.users.status_banned') }}
                    </label>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3">
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        {{ __('admin.users.email_unique_hint') }}
                    </p>

                    <div class="flex items-center gap-2">
                        <flux:button type="button" variant="ghost" wire:click="$set('showCreateModal', false)" data-admin-modal-close>
                            {{ __('admin.users.action_cancel') }}
                        </flux:button>
                        <flux:button type="submit" color="primary" icon="check">
                            {{ __('admin.users.action_save') }}
                        </flux:button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endif

@if ($showDeleteModal)
    <div class="fixed inset-0 z-50 flex items-start justify-center bg-slate-900/60 px-4 py-10 backdrop-blur" data-admin-modal="user-delete">
        <div class="absolute inset-0" wire:click="cancelDelete" data-admin-modal-close></div>
        <div class="relative z-10 w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-slate-900">
            <div class="flex items-start justify-between border-b border-slate-200 px-6 py-4 dark:border-slate-800">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-50">
                        {{ __('admin.users.delete_heading', ['name' => $deleteContext['name']]) }}
                    </h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        {{ __('admin.users.delete_subheading') }}
                    </p>
                </div>
                <button
                    type="button"
                    class="rounded-full p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 dark:hover:bg-slate-800"
                    wire:click="cancelDelete"
                    aria-label="{{ __('admin.users.close_modal') }}"
                    data-admin-modal-close
                >
                    &times;
                </button>
            </div>

            <div class="space-y-6 p-6">
                <div class="grid gap-4 md:grid-cols-3">
                    <flux:badge color="indigo">
                        {{ trans_choice('admin.users.posts_count', $deleteContext['posts'], ['count' => $deleteContext['posts']]) }}
                    </flux:badge>
                    <flux:badge color="blue">
                        {{ trans_choice('admin.users.comments_count', $deleteContext['comments'], ['count' => $deleteContext['comments']]) }}
                    </flux:badge>
                    <flux:badge color="slate">
                        {{ __('admin.users.delete_status_label', ['status' => $deleteStrategy === 'delete' ? __('admin.users.delete_remove') : __('admin.users.delete_transfer')]) }}
                    </flux:badge>
                </div>

                <div class="space-y-3">
                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                        {{ __('admin.users.delete_strategy_label') }}
                    </p>
                    <div class="grid gap-3 md:grid-cols-2">
                        <label class="flex w-full cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm transition hover:border-indigo-200 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-indigo-500">
                            <input
                                type="radio"
                                class="mt-1 h-4 w-4 border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                value="transfer"
                                wire:model.live="deleteStrategy"
                            >
                            <div class="space-y-1">
                                <p class="font-semibold text-slate-800 dark:text-slate-100">
                                    {{ __('admin.users.delete_transfer_label') }}
                                </p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">
                                    {{ __('admin.users.delete_transfer_help') }}
                                </p>
                            </div>
                        </label>

                        <label class="flex w-full cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm transition hover:border-rose-200 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-rose-500">
                            <input
                                type="radio"
                                class="mt-1 h-4 w-4 border-slate-300 text-rose-600 focus:ring-rose-500"
                                value="delete"
                                wire:model.live="deleteStrategy"
                            >
                            <div class="space-y-1">
                                <p class="font-semibold text-rose-700 dark:text-rose-200">
                                    {{ __('admin.users.delete_remove_label') }}
                                </p>
                                <p class="text-xs text-rose-500 dark:text-rose-300">
                                    {{ __('admin.users.delete_remove_help') }}
                                </p>
                            </div>
                        </label>
                    </div>
                </div>

                @if ($deleteStrategy === 'transfer')
                    <div class="space-y-2">
                        <label class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                            {{ __('admin.users.transfer_to_label') }}
                        </label>
                        <select
                            wire:model.live="transferTarget"
                            class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100"
                        >
                            @forelse ($transferOptions as $option)
                                <option value="{{ $option['id'] }}">
                                    {{ $option['name'] }}
                                </option>
                            @empty
                                <option value="{{ auth()->id() }}">
                                    {{ auth()->user()->name }}
                                </option>
                            @endforelse
                        </select>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            {{ __('admin.users.transfer_hint') }}
                        </p>
                    </div>
                @endif

                <x-ui.alert variant="danger">
                    {{ __('admin.users.delete_warning') }}
                </x-ui.alert>

                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <flux:button variant="ghost" wire:click="cancelDelete" data-admin-modal-close>
                            {{ __('admin.users.action_cancel') }}
                        </flux:button>
                        <flux:button
                            color="red"
                            icon="trash"
                            wire:click="deleteUser"
                            wire:loading.attr="disabled"
                            wire:target="deleteUser"
                        >
                            <span wire:loading.remove wire:target="deleteUser">{{ __('admin.users.action_confirm_delete') }}</span>
                            <span wire:loading.delay.500ms wire:target="deleteUser" class="inline-flex items-center gap-1">
                                <svg class="h-3 w-3 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ __('admin.deleting') }}
                            </span>
                        </flux:button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
