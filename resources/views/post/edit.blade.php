<x-app-layout>
    <x-slot name="title">
        {{ __('posts.form.edit_title') }}
    </x-slot>

    <x-ui.page-header
        :title="__('posts.form.edit_heading')"
        :subtitle="$post->title"
    />

    <x-ui.section max-width="max-w-5xl" class="pb-16 space-y-6">
        @if ($post->isFileBased())
            <x-ui.alert variant="danger">
                {{ __('posts.form.markdown_warning') }}
            </x-ui.alert>
        @endif

        <x-ui.card>
            <form action="{{ route('posts.update', $post) }}" method="POST" class="space-y-10 text-slate-900 dark:text-white">
                @csrf
                @method('PATCH')

                <x-ui.form-section :title="__('posts.form.required_heading')">
                    <div class="space-y-2">
                        <x-label for="title">
                            {{ __('posts.form.title_label') }}
                            <small class="text-slate-400 dark:text-slate-500">{{ __('posts.form.slug_note') }}</small>
                        </x-label>
                        <x-input id="title" name="title" :value="old('title', $post->title)" type="text" maxlength="255" required autofocus />
                        @error('title') <p class="text-sm text-rose-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <x-label for="body" :value="__('posts.form.body_label')" />
                        <x-textarea id="body" name="body" rows="8">{{ old('body', $post->body) }}</x-textarea>
                        @error('body') <p class="text-sm text-rose-500">{{ $message }}</p> @enderror
                    </div>

                    @if (config('blog.easyMDE.enabled'))
                        <x-markdown-editor :draft_id="$draft_id" />
                    @endif
                </x-ui.form-section>

                <x-ui.form-section :title="__('posts.form.optional_heading')">
                    <div class="grid gap-6 md:grid-cols-2">
                        <div class="space-y-2">
                            <x-label for="description" :value="__('posts.form.description_label')" />
                            <x-input id="description" name="description" :value="old('description', $post->description)" type="text" maxlength="255" placeholder="{{ __('posts.form.description_placeholder') }}" />
                            @error('description') <p class="text-sm text-rose-500">{{ $message }}</p> @enderror
                        </div>

                        <div class="space-y-2">
                            <x-label for="featured_image" :value="__('posts.form.featured_image_label')" />
                            <x-input id="featured_image" name="featured_image" :value="old('featured_image', $post->featured_image)" type="url" maxlength="255" placeholder="{{ __('posts.form.featured_image_placeholder') }}" />
                            @error('featured_image') <p class="text-sm text-rose-500">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="space-y-2">
                        <x-label for="published_at" :value="__('posts.form.publish_label')" />
                        <x-input
                            id="published_at"
                            name="published_at"
                            type="datetime-local"
                            :value="old('published_at', optional($post->published_at)->format('Y-m-d\TH:i'))"
                        />
                        @error('published_at') <p class="text-sm text-rose-500">{{ $message }}</p> @enderror
                        <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                            {!! __('forms.publish_hint', [
                                'action' => '<button type="button" class="font-semibold text-indigo-500 dark:text-indigo-300" data-clear-published-at="#published_at">'.e(__('forms.publish_clear')).'</button>',
                            ]) !!}
                        </p>
                    </div>

                    @if (config('blog.withTags'))
                        <x-tags-field :initial="old('tags_input', collect($post->tags ?? [])->implode(', '))" />
                    @endif

                    <div class="space-y-2">
                        <x-label :value="__('posts.form.categories_label')" />
                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            @foreach ($categories as $category)
                                <label class="flex items-center gap-2 rounded-xl border border-slate-200/70 px-3 py-2 text-sm dark:border-slate-700">
                                    <input
                                        type="checkbox"
                                        name="categories[]"
                                        value="{{ $category->id }}"
                                        class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                        {{ in_array($category->id, old('categories', $post->categories->pluck('id')->toArray())) ? 'checked' : '' }}
                                    >
                                    <span>{{ $category->name }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('categories') <p class="mt-1 text-sm text-rose-500">{{ $message }}</p> @enderror
                    </div>
                </x-ui.form-section>

                <div class="flex flex-wrap items-center justify-between gap-4">
                    <label class="inline-flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                        <input
                            id="is_draft"
                            name="is_draft"
                            type="checkbox"
                            value="1"
                            class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                            {{ old('is_draft', $post->published_at === null) ? 'checked' : '' }}
                        >
                        {{ __('posts.form.save_draft_label') }}
                    </label>
                    @error('is_draft') <p class="w-full text-sm text-rose-500">{{ $message }}</p> @enderror

                    <x-ui.button type="submit">
                        {{ __('posts.form.update_button') }}
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </x-ui.section>
</x-app-layout>

