<?php

use App\Livewire\Concerns\ManagesPerPage;
use App\Models\Post;
use App\Scopes\PublishedScope;
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

    protected $listeners = ['post-updated' => '$refresh'];
    protected $queryString = [
        'perPage' => ['except' => 20],
    ];

    public function with(): array
    {
        $posts = Post::query()
            ->withoutGlobalScope('order')
            ->withoutGlobalScope(PublishedScope::class)
            ->with(['author'])
            ->withCount('comments')
            ->orderByDesc('updated_at')
            ->paginate($this->perPage);

        return [
            'posts' => $posts,
        ];
    }

    private function findPost(int $postId): Post
    {
        return Post::query()
            ->withoutGlobalScope(PublishedScope::class)
            ->findOrFail($postId);
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
        :heading="__('admin.posts.heading')"
        :description="__('admin.posts.description')"
    >
        <flux:button color="primary" :href="route('posts.create')">
            {{ __('admin.posts.create_button') }}
        </flux:button>
    </flux:page-header>

    <x-admin.table
        :pagination="$posts"
        per-page-mode="livewire"
        per-page-field="perPage"
        :per-page-options="$this->perPageOptions"
        :summary="trans('pagination.summary', [
            'from' => $posts->firstItem(),
            'to' => $posts->lastItem(),
            'total' => $posts->total(),
        ])"
    >
        <x-slot name="head">
            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                <th class="px-4 py-3">{{ __('admin.posts.table.title') }}</th>
                <th class="px-4 py-3">{{ __('admin.posts.table.status') }}</th>
                <th class="px-4 py-3">{{ __('admin.posts.table.comments') }}</th>
                <th class="px-4 py-3">{{ __('admin.posts.table.updated') }}</th>
                <th class="px-4 py-3 text-right">{{ __('admin.posts.table.actions') }}</th>
            </tr>
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
                        <flux:link :href="route('posts.edit', $post)" size="sm">
                            {{ __('admin.posts.action_edit') }}
                        </flux:link>

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

