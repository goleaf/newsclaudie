<?php

use App\Models\Category;
use Livewire\Volt\Component;
use function Livewire\Volt\layout;
use function Livewire\Volt\title;

layout('layouts.app');

new class extends Component {
    public Category $category;
    public int $postCount = 0;
    public string $subtitle = '';

    public function mount(Category $category): void
    {
        $this->category = $category->loadCount('posts');
        $this->postCount = $this->category->posts_count ?? $this->category->posts()->count();
        $this->subtitle = $this->category->description ?: __('categories.show.subtitle');

        title($this->category->name.' — '.trans('categories.title'));
    }

    public function with(): array
    {
        return [
            'postCount' => $this->postCount,
            'subtitle' => $this->subtitle,
        ];
    }
}; ?>

<div class="space-y-8">
    <x-ui.page-header
        :title="$category->name"
        :subtitle="$subtitle"
    >
        <x-slot name="meta">
            <x-ui.badge variant="info">
                {{ trans_choice('categories.show.count', $postCount, ['count' => $postCount]) }}
            </x-ui.badge>
        </x-slot>
    </x-ui.page-header>

    <x-ui.section class="pb-16">
        <livewire:category-posts :category="$category" />
    </x-ui.section>
</div>
