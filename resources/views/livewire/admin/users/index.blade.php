<?php

use App\Livewire\Concerns\ManagesPerPage;
use App\Models\User;
use App\Support\Pagination\PageSize;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
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

    protected $listeners = ['user-updated' => '$refresh'];
    protected $queryString = [
        'perPage' => ['except' => PageSize::contextDefault('admin')],
    ];

    public function with(): array
    {
        $users = User::query()
            ->orderByDesc('is_admin')
            ->orderByDesc('is_author')
            ->orderBy('name')
            ->paginate($this->perPage);

        return [
            'users' => $users,
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
        $this->authorize('update', $user);

        if ($user->is(auth()->user())) {
            session()->flash('status', __('admin.users.cannot_self_update'));

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
    />

    @if (session('status'))
        <flux:callout color="indigo">
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
                    <div class="flex flex-wrap justify-end gap-2">
                        <flux:button
                            wire:click="toggleAuthor({{ $user->id }})"
                            size="sm"
                            color="purple"
                            :icon="$user->is_author ? 'minus' : 'plus'"
                        >
                            {{ $user->is_author ? __('admin.users.action_remove_author') : __('admin.users.action_make_author') }}
                        </flux:button>

                        <flux:button
                            wire:click="toggleAdmin({{ $user->id }})"
                            size="sm"
                            color="orange"
                            :icon="$user->is_admin ? 'minus' : 'plus'"
                        >
                            {{ $user->is_admin ? __('admin.users.action_remove_admin') : __('admin.users.action_make_admin') }}
                        </flux:button>

                        <flux:button
                            wire:click="toggleBan({{ $user->id }})"
                            size="sm"
                            color="{{ $user->is_banned ? 'green' : 'red' }}"
                            :icon="$user->is_banned ? 'check' : 'x-mark'"
                        >
                            {{ $user->is_banned ? __('admin.users.action_unban') : __('admin.users.action_ban') }}
                        </flux:button>
                    </div>
                </td>
            </x-admin.table-row>
        @empty
            <x-admin.table-empty colspan="5" :message="__('admin.users.empty')" />
        @endforelse
    </x-admin.table>
</div>
