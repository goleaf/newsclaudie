<?php

use App\Models\Category;
use App\Models\Comment;
use App\Models\Post;
use Livewire\Volt\Component;
use function Livewire\Volt\layout;
use function Livewire\Volt\title;

layout('components.layouts.admin');
title(__('nav.admin_dashboard'));

new class extends Component {
    public function with(): array
    {
        $postQuery = Post::query()->withoutGlobalScopes();

        $publishedPosts = (clone $postQuery)->whereNotNull('published_at')->count();
        $draftPosts = (clone $postQuery)->whereNull('published_at')->count();
        $totalPosts = (clone $postQuery)->count();

        $categories = Category::count();
        $comments = Comment::count();

        $recentPosts = Post::query()
            ->withoutGlobalScopes()
            ->with(['author'])
            ->latest('updated_at')
            ->limit(5)
            ->get();

        return [
            'stats' => [
                'published' => $publishedPosts,
                'drafts' => $draftPosts,
                'categories' => $categories,
                'comments' => $comments,
            ],
            'activity' => [
                [
                    'label' => __('admin.activity.total_posts'),
                    'value' => $totalPosts,
                    'hint' => __('admin.activity.total_posts_hint'),
                ],
                [
                    'label' => __('admin.activity.total_categories'),
                    'value' => $categories,
                    'hint' => __('admin.activity.total_categories_hint'),
                ],
                [
                    'label' => __('admin.activity.total_comments'),
                    'value' => $comments,
                    'hint' => __('admin.activity.total_comments_hint'),
                ],
            ],
            'recentPosts' => $recentPosts,
        ];
    }
}; ?>

<div class="space-y-6">
    <flux:page-header
        :heading="__('admin.dashboard.heading')"
        :description="__('admin.dashboard.description')"
    />

    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <flux:stat label="{{ __('admin.stats.published_posts') }}" value="{{ number_format($stats['published']) }}" />
        <flux:stat label="{{ __('admin.stats.draft_posts') }}" value="{{ number_format($stats['drafts']) }}" />
        <flux:stat label="{{ __('admin.stats.categories') }}" value="{{ number_format($stats['categories']) }}" />
        <flux:stat label="{{ __('admin.stats.comments') }}" value="{{ number_format($stats['comments']) }}" />
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <flux:card>
            <flux:heading size="lg">{{ __('admin.cards.recent_posts') }}</flux:heading>
            <div class="mt-4 space-y-4">
                @forelse ($recentPosts as $post)
                    <div class="rounded-xl border border-slate-200/80 bg-white/70 px-4 py-3 dark:border-slate-800/60 dark:bg-slate-900/60">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $post->title }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">
                                    {{ $post->author?->name ?? __('admin.posts.unknown_author') }}
                                </p>
                            </div>
                            @if ($post->isPublished())
                                <flux:badge color="green">{{ __('admin.posts.status.published') }}</flux:badge>
                            @else
                                <flux:badge color="amber">{{ __('admin.posts.status.draft') }}</flux:badge>
                            @endif
                        </div>
                        <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                            {{ __('admin.dashboard.updated_hint', ['time' => $post->updated_at?->diffForHumans() ?? '—']) }}
                        </p>
                    </div>
                @empty
                    <flux:empty-state>
                        <flux:heading size="md">{{ __('admin.cards.recent_empty') }}</flux:heading>
                    </flux:empty-state>
                @endforelse
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="lg">{{ __('admin.cards.activity') }}</flux:heading>
            <div class="mt-4 space-y-4">
                @foreach ($activity as $item)
                    <div class="rounded-xl border border-slate-200/80 bg-white/70 px-4 py-3 dark:border-slate-800/60 dark:bg-slate-900/60">
                        <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $item['label'] }}</p>
                        <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($item['value']) }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $item['hint'] }}</p>
                    </div>
                @endforeach
            </div>
        </flux:card>
    </div>
</div>

