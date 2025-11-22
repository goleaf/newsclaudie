<?php

use App\Livewire\Concerns\ManagesPerPage;
use App\Models\User;
use App\Support\Pagination\PageSize;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use function Livewire\Volt\layout;
use function Livewire\Volt\title;

layout('components.layouts.admin');
title(__('admin.users.title'));

new class extends Component {
    use AuthorizesRequests;
    use ManagesPerPage;
    use WithPagination;

    public ?string $search = null;

    public string $newName = '';
    public string $newEmail = '';
    public string $newPassword = '';
    public string $newPasswordConfirmation = '';
    public bool $newIsAdmin = false;
    public bool $newIsAuthor = false;

    protected $listeners = ['user-updated' => '$refresh'];
    protected array $queryString = [
        'search' => ['except' => ''],
    ];

    public function mount(): void
    {
        $this->queryString['perPage'] = ['except' => PageSize::contextDefault('admin')];
    }

    public function with(): array
    {
        $searchTerm = trim((string) $this->search);

        $users = User::query()
            ->when($searchTerm !== '', function ($query) use ($searchTerm) {
                $query->where(function ($inner) use ($searchTerm) {
                    $inner->where('name', 'like', '%'.$searchTerm.'%')
                        ->orWhere('email', 'like', '%'.$searchTerm.'%');
                });
            })
            ->orderByDesc('is_admin')
            ->orderByDesc('is_author')
            ->orderBy('name')
            ->paginate($this->perPage);

        return [
            'users' => $users,
            'searchTerm' => $searchTerm,
            'userCountLabel' => trans_choice('admin.users.count', $users->total(), ['count' => $users->total()]),
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function startCreate(): void
    {
        $this->authorize('create', User::class);

        $this->resetCreateForm();

        $this->dispatch('modal-show', name: 'create-user');
    }

    public function createUser(): void
    {
        $this->authorize('create', User::class);

        $messages = [
            'newEmail.unique' => __('admin.users.validation.email_unique'),
            'newPassword.same' => __('admin.users.validation.password_match'),
        ];

        $validated = $this->validate(
            [
                'newName' => ['required', 'string', 'max:255'],
                'newEmail' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
                'newPassword' => ['required', Password::defaults(), 'same:newPasswordConfirmation'],
                'newPasswordConfirmation' => ['required'],
                'newIsAdmin' => ['boolean'],
                'newIsAuthor' => ['boolean'],
            ],
            $messages,
            [
                'newName' => __('admin.users.fields.name'),
                'newEmail' => __('admin.users.fields.email'),
                'newPassword' => __('admin.users.fields.password'),
                'newPasswordConfirmation' => __('admin.users.fields.password_confirmation'),
            ],
        );

        $user = User::create([
            'name' => $validated['newName'],
            'email' => $validated['newEmail'],
            'password' => Hash::make($validated['newPassword']),
        ]);

        $user->forceFill([
            'is_admin' => (bool) $validated['newIsAdmin'],
            'is_author' => (bool) $validated['newIsAuthor'],
        ])->save();

        $this->resetCreateForm();
        $this->resetPage();

        session()->flash('status', __('admin.users.created', ['name' => $user->name]));

        $this->dispatch('modal-close', name: 'create-user');
        $this->dispatch('user-updated');
    }

    public function deleteUser(User $user): void
    {
        try {
            $this->authorize('delete', $user);
        } catch (AuthorizationException $exception) {
            session()->flash('error', $exception->getMessage());

            return;
        }

        $user->delete();

        session()->flash('status', __('admin.users.deleted', ['name' => $user->name]));

        $this->resetPage();
        $this->dispatch('user-updated');
    }

    public function closeCreateModal(): void
    {
        $this->resetCreateForm();
        $this->dispatch('modal-close', name: 'create-user');
    }

    public function toggleAuthor(User $user): void
    {
        $this->authorize('update', $user);

        if ($this->denySelfAction($user)) {
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

        if ($this->denySelfAction($user)) {
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

    private function resetCreateForm(): void
    {
        $this->reset([
            'newName',
            'newEmail',
            'newPassword',
            'newPasswordConfirmation',
            'newIsAdmin',
            'newIsAuthor',
        ]);

        $this->resetErrorBag();
        $this->resetValidation();
    }

    private function denySelfAction(User $user): bool
    {
        if ($user->is(auth()->user())) {
            session()->flash('error', __('admin.users.cannot_self_update'));

            return true;
        }

        return false;
    }
}; ?>

<div class="space-y-6">
    <flux:page-header
        :heading="__('admin.users.heading')"
        :description="__('admin.users.description')"
    />

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

    <x-admin.table
        :pagination="$users"
        per-page-mode="livewire"
        per-page-field="perPage"
        :per-page-options="$this->perPageOptions"
        :per-page-value="$perPage"
    >
        <x-slot name="toolbar">
            <div class="flex flex-1 flex-wrap items-center gap-3">
                <div class="w-full md:w-80">
                    <flux:input
                        type="search"
                        icon="magnifying-glass"
                        clearable
                        wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('admin.users.search_placeholder') }}"
                    />
                </div>

                @if ($searchTerm !== '')
                    <flux:badge color="blue">{{ __('admin.users.search_label', ['term' => $searchTerm]) }}</flux:badge>
                @endif

                <flux:badge>{{ $userCountLabel }}</flux:badge>
            </div>

            <div class="flex items-center gap-2">
                <flux:button
                    color="primary"
                    icon="plus"
                    wire:click="startCreate"
                >
                    {{ __('admin.users.create_button') }}
                </flux:button>
            </div>
        </x-slot>

        <x-slot name="head">
            <x-admin.table-head :columns="[
                ['label' => __('admin.users.table.user')],
                ['label' => __('admin.users.table.roles')],
                ['label' => __('admin.users.table.status')],
                ['label' => __('admin.users.table.joined')],
                ['label' => __('admin.users.table.actions'), 'class' => 'text-right'],
            ]" />
        </x-slot>

        @forelse ($users as $user)
            <x-admin.table-row wire:key="user-{{ $user->id }}">
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
                    {{ $user->created_at?->diffForHumans() ?? '—' }}
                </td>
                <td class="px-4 py-4 text-right">
                    <div class="flex flex-col items-end gap-3">
                        <div class="flex flex-wrap justify-end gap-4">
                            <div class="flex items-center gap-2">
                                <flux:switch
                                    wire:click="toggleAdmin({{ $user->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="toggleAdmin"
                                    :checked="$user->is_admin"
                                    :disabled="$user->is(auth()->user())"
                                    aria-label="{{ __('admin.users.action_toggle_admin', ['name' => $user->name]) }}"
                                />
                                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                    {{ __('admin.users.role_admin') }}
                                </span>
                            </div>

                            <div class="flex items-center gap-2">
                                <flux:switch
                                    wire:click="toggleAuthor({{ $user->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="toggleAuthor"
                                    :checked="$user->is_author"
                                    :disabled="$user->is(auth()->user())"
                                    aria-label="{{ __('admin.users.action_toggle_author', ['name' => $user->name]) }}"
                                />
                                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                    {{ __('admin.users.role_author') }}
                                </span>
                            </div>

                            <div class="flex items-center gap-2">
                                <flux:switch
                                    wire:click="toggleBan({{ $user->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="toggleBan"
                                    :checked="$user->is_banned"
                                    :disabled="$user->is(auth()->user())"
                                    aria-label="{{ $user->is_banned ? __('admin.users.action_unban') : __('admin.users.action_ban') }}"
                                />
                                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                    {{ __('admin.users.status_banned') }}
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <flux:button
                                type="button"
                                size="sm"
                                color="red"
                                icon="trash"
                                wire:click="deleteUser({{ $user->id }})"
                                wire:confirm="{{ __('admin.users.delete_confirm') }}"
                                :disabled="$user->is(auth()->user())"
                            >
                                {{ __('admin.users.action_delete') }}
                            </flux:button>
                        </div>
                    </div>
                </td>
            </x-admin.table-row>
        @empty
            <x-admin.table-empty colspan="5" :message="$searchTerm !== '' ? __('admin.users.search_empty') : __('admin.users.empty')" />
        @endforelse
    </x-admin.table>
</div>

<flux:modal name="create-user">
    <div class="space-y-6">
        <div class="flex items-start justify-between gap-3">
            <div>
                <flux:heading size="lg">{{ __('admin.users.modal.title') }}</flux:heading>
                <flux:text class="text-sm text-slate-500 dark:text-slate-400">
                    {{ __('admin.users.modal.subtitle') }}
                </flux:text>
            </div>
        </div>

        <form wire:submit.prevent="createUser" class="space-y-4">
            <flux:field>
                <flux:label for="newName">{{ __('admin.users.fields.name') }}</flux:label>
                <flux:input
                    id="newName"
                    type="text"
                    wire:model.live="newName"
                    autocomplete="name"
                />
                <flux:error name="newName" />
            </flux:field>

            <flux:field>
                <flux:label for="newEmail">{{ __('admin.users.fields.email') }}</flux:label>
                <flux:input
                    id="newEmail"
                    type="email"
                    wire:model.live.debounce.300ms="newEmail"
                    autocomplete="email"
                />
                <flux:error name="newEmail" />
            </flux:field>

            <div class="grid gap-4 md:grid-cols-2">
                <flux:field>
                    <flux:label for="newPassword">{{ __('admin.users.fields.password') }}</flux:label>
                    <flux:input
                        id="newPassword"
                        type="password"
                        wire:model.live="newPassword"
                        autocomplete="new-password"
                    />
                    <flux:error name="newPassword" />
                </flux:field>

                <flux:field>
                    <flux:label for="newPasswordConfirmation">{{ __('admin.users.fields.password_confirmation') }}</flux:label>
                    <flux:input
                        id="newPasswordConfirmation"
                        type="password"
                        wire:model.live="newPasswordConfirmation"
                        autocomplete="new-password"
                    />
                    <flux:error name="newPasswordConfirmation" />
                </flux:field>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <flux:field variant="inline">
                    <flux:label>{{ __('admin.users.fields.is_admin') }}</flux:label>
                    <flux:switch wire:model="newIsAdmin" />
                </flux:field>

                <flux:field variant="inline">
                    <flux:label>{{ __('admin.users.fields.is_author') }}</flux:label>
                    <flux:switch wire:model="newIsAuthor" />
                </flux:field>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-3">
                <flux:button type="button" variant="ghost" wire:click="closeCreateModal">
                    {{ __('admin.users.cancel') }}
                </flux:button>
                <flux:button
                    type="submit"
                    color="primary"
                    icon="plus"
                    wire:loading.attr="disabled"
                    wire:target="createUser"
                >
                    {{ __('admin.users.create_button') }}
                </flux:button>
            </div>
        </form>
    </div>
</flux:modal>
