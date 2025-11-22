<?php

use App\Livewire\Concerns\ManagesPerPage;
use App\Models\Comment;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use function Livewire\Volt\layout;
use function Livewire\Volt\title;

layout('components.layouts.admin');
title(__('admin.comments.title'));

new class extends Component {
    use AuthorizesRequests;
    use ManagesPerPage;
    use WithPagination;

    protected $listeners = ['comment-removed' => '$refresh'];
    protected $queryString = [
        'perPage' => ['except' => 20],
    ];

    public function with(): array
    {
        $comments = Comment::query()
            ->with(['user', 'post'])
            ->latest('created_at')
            ->paginate($this->perPage);

        return [
            'comments' => $comments,
        ];
    }

    public function deleteComment(Comment $comment): void
    {
        $this->authorize('delete', $comment);

        $comment->delete();

        session()->flash('status', __('admin.comments.deleted'));

        $this->dispatch('comment-removed');
    }

    protected function availablePerPageOptions(): array
    {
        return [10, 20, 50, 100];
    }

    protected function defaultPerPage(): int
    {
        return 20;
    }
}; ?>

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

    <x-admin.table
        :pagination="$comments"
        per-page-mode="livewire"
        per-page-field="perPage"
        :per-page-options="$this->perPageOptions"
        :summary="trans('pagination.summary', [
            'from' => $comments->firstItem(),
            'to' => $comments->lastItem(),
            'total' => $comments->total(),
        ])"
    >
        <x-slot name="head">
            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                <th class="px-4 py-3">{{ __('admin.comments.table.author') }}</th>
                <th class="px-4 py-3">{{ __('admin.comments.table.post') }}</th>
                <th class="px-4 py-3">{{ __('admin.comments.table.preview') }}</th>
                <th class="px-4 py-3">{{ __('admin.comments.table.date') }}</th>
                <th class="px-4 py-3 text-right">{{ __('admin.comments.table.actions') }}</th>
            </tr>
        </x-slot>

        @forelse ($comments as $comment)
            <x-admin.table-row>
                <td class="px-4 py-4">
                    <div class="flex flex-col">
                        <span class="font-semibold">{{ $comment->user?->name ?? __('admin.comments.unknown_user') }}</span>
                        <span class="text-xs text-slate-500 dark:text-slate-400">#{{ $comment->id }}</span>
                    </div>
                </td>
                <td class="px-4 py-4">
                    <flux:link :href="route('posts.show', $comment->post)" size="sm">
                        {{ $comment->post?->title ?? __('admin.comments.unknown_post') }}
                    </flux:link>
                </td>
                <td class="px-4 py-4">
                    <p class="text-sm text-slate-600 dark:text-slate-300">
                        {{ Str::limit($comment->content, 120) }}
                    </p>
                </td>
                <td class="px-4 py-4">
                    {{ $comment->created_at?->diffForHumans() ?? '—' }}
                </td>
                <td class="px-4 py-4 text-right">
                    <div class="inline-flex items-center gap-2">
                        <flux:link :href="route('comments.edit', $comment)" size="sm">
                            {{ __('admin.comments.action_edit') }}
                        </flux:link>

                        <flux:button
                            wire:click="deleteComment({{ $comment->id }})"
                            size="sm"
                            color="red"
                            icon="trash"
                        >
                            {{ __('admin.comments.action_delete') }}
                        </flux:button>
                    </div>
                </td>
            </x-admin.table-row>
        @empty
            <x-admin.table-empty colspan="5" :message="__('admin.comments.empty')" />
        @endforelse
    </x-admin.table>
</div>


