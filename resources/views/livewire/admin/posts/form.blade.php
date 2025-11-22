<?php

use App\Models\Category;
use App\Models\Post;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Volt\Component;
use function Livewire\Volt\layout;
use function Livewire\Volt\title;

layout('components.layouts.admin');

new class extends Component {
    use AuthorizesRequests;

    public ?Post $post = null;
    public bool $isEditing = false;
    public int $draftId;

    public function mount(?Post $post = null): void
    {
        $this->post = $post?->loadMissing('categories');
        $this->isEditing = (bool) $post?->exists;

        $this->authorize($this->isEditing ? 'update' : 'create', $this->post ?? Post::class);

        $this->draftId = request()->integer('draft_id', time());

        title($this->isEditing ? __('posts.form.edit_title') : __('posts.form.create_title'));
    }

    public function with(): array
    {
        return [
            'categories' => Category::orderBy('name')->get(),
            'post' => $this->post,
            'isEditing' => $this->isEditing,
            'draftId' => $this->draftId,
            'redirectRoute' => 'admin.posts.index',
        ];
    }
}; ?>

@php
    $action = $isEditing && $post ? route('posts.update', $post) : route('posts.store');
    $publishedValue = old(
        'published_at',
        $isEditing
            ? optional($post->published_at)->format('Y-m-d\TH:i')
            : now()->format('Y-m-d\TH:i')
    );
    $selectedCategories = $isEditing && $post ? $post->categories->pluck('id')->toArray() : [];
    $tagsInitial = old(
        'tags_input',
        $isEditing && $post ? collect($post->tags ?? [])->implode(', ') : ''
    );
@endphp

<div class="space-y-6">
    <flux:page-header
        :heading="$isEditing ? __('posts.form.edit_heading') : __('posts.form.create_heading')"
        :description="$isEditing && $post ? $post->title : __('posts.form.create_subheading')"
    >
        <flux:button color="secondary" :href="route('admin.posts.index')" icon="arrow-left">
            {{ __('ui.actions.back') }}
        </flux:button>
    </flux:page-header>

    <x-ui.section max-width="max-w-5xl" class="pb-16 space-y-6">
        @if (session('status'))
            <flux:callout color="green">
                {{ session('status') }}
            </flux:callout>
        @endif

        @if ($isEditing && $post?->isFileBased())
            <x-ui.alert variant="danger">
                {{ __('posts.form.markdown_warning') }}
            </x-ui.alert>
        @endif

        <x-ui.card>
            <form action="{{ $action }}" method="POST" class="space-y-10 text-slate-900 dark:text-white">
                @csrf
                @if ($isEditing)
                    @method('PATCH')
                @endif
                <input type="hidden" name="redirect_to" value="{{ $redirectRoute }}">

                <x-ui.form-section :title="__('posts.form.required_heading')">
                    <div class="space-y-2">
                        <x-label for="title">
                            {{ __('posts.form.title_label') }}
                            @if ($isEditing)
                                <small class="text-slate-400 dark:text-slate-500">{{ __('posts.form.slug_note') }}</small>
                            @endif
                        </x-label>
                        <x-input id="title" name="title" :value="old('title', $post->title ?? '')" type="text" maxlength="255" required autofocus />
                        @error('title') <p class="text-sm text-rose-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <x-label for="body" :value="__('posts.form.body_label')" />
                        <x-textarea id="body" name="body" rows="8">{{ old('body', $post->body ?? '') }}</x-textarea>
                        @error('body') <p class="text-sm text-rose-500">{{ $message }}</p> @enderror
                    </div>

                    @if (config('blog.easyMDE.enabled'))
                        <x-markdown-editor :draft_id="$draftId" />
                    @endif
                </x-ui.form-section>

                <x-ui.form-section :title="__('posts.form.optional_heading')">
                    <div class="grid gap-6 md:grid-cols-2">
                        <div class="space-y-2">
                            <x-label for="description" :value="__('posts.form.description_label')" />
                            <x-input id="description" name="description" :value="old('description', $post->description ?? '')" type="text" maxlength="255" placeholder="{{ __('posts.form.description_placeholder') }}" />
                            @error('description') <p class="text-sm text-rose-500">{{ $message }}</p> @enderror
                        </div>

                        <div class="space-y-2">
                            <x-label for="featured_image" :value="__('posts.form.featured_image_label')" />
                            <x-input id="featured_image" name="featured_image" :value="old('featured_image', $post->featured_image ?? '')" type="url" maxlength="255" placeholder="{{ __('posts.form.featured_image_placeholder') }}" />
                            @error('featured_image') <p class="text-sm text-rose-500">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="space-y-2">
                        <x-label for="published_at" :value="__('posts.form.publish_label')" />
                        <x-input
                            id="published_at"
                            name="published_at"
                            type="datetime-local"
                            :value="$publishedValue"
                            min="1971-01-01T00:00"
                            max="2038-01-09T03:14"
                        />
                        @error('published_at') <p class="text-sm text-rose-500">{{ $message }}</p> @enderror
                        <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                            {!! __('forms.publish_hint', [
                                'action' => '<button type="button" class="font-semibold text-indigo-500 dark:text-indigo-300" data-clear-published-at="#published_at">'.e(__('forms.publish_clear')).'</button>',
                            ]) !!}
                        </p>
                    </div>

                    @if (config('blog.withTags'))
                        <x-tags-field :initial="$tagsInitial" />
                    @endif

                    <x-categories.multi-select
                        :categories="$categories"
                        :selected="$selectedCategories"
                    />
                </x-ui.form-section>

                <div class="flex flex-wrap items-center justify-between gap-4">
                    <label class="inline-flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                        <input
                            id="is_draft"
                            name="is_draft"
                            type="checkbox"
                            value="1"
                            class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                            {{ old('is_draft', $isEditing ? $post?->published_at === null : false) ? 'checked' : '' }}
                        >
                        {{ __('posts.form.save_draft_label') }}
                    </label>
                    @error('is_draft') <p class="w-full text-sm text-rose-500">{{ $message }}</p> @enderror

                    <flux:button type="submit" color="primary">
                        {{ $isEditing ? __('posts.form.update_button') : __('posts.form.create_button') }}
                    </flux:button>
                </div>
            </form>
        </x-ui.card>
    </x-ui.section>
</div>
