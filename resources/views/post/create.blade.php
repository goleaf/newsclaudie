<x-app-layout>
    <x-slot name="title">
        {{ __('posts.form.create_title') }}
    </x-slot>

    <x-ui.page-header
        :title="__('posts.form.create_heading')"
        :subtitle="__('posts.form.create_subheading')"
    />

    <x-ui.section max-width="max-w-5xl" class="pb-16">
        <x-ui.card>
            <form action="{{ route('posts.store') }}" method="POST" class="space-y-10 text-slate-900 dark:text-white">
                @csrf

                @php
                    $titleError = $errors->first('title');
                    $bodyError = $errors->first('body');
                    $descriptionError = $errors->first('description');
                    $featuredImageError = $errors->first('featured_image');
                    $publishedAtError = $errors->first('published_at');
                @endphp

                <x-ui.form-section :title="__('posts.form.required_heading')">
                    <div class="space-y-2" data-validation-field="title">
                        <x-label for="title" :value="__('posts.form.title_label')" />
                        <x-input
                            id="title"
                            name="title"
                            :value="old('title')"
                            type="text"
                            maxlength="255"
                            required
                            autofocus
                            placeholder="{{ __('posts.form.title_placeholder') }}"
                            :invalid="(bool) $titleError"
                            @if ($titleError) aria-describedby="title-error" @endif
                        />
                        @if ($titleError)
                            <p id="title-error" class="text-sm text-rose-500" data-field-error role="alert">{{ $titleError }}</p>
                        @endif
                    </div>

                    <div class="space-y-2" data-validation-field="body">
                        <x-label for="body" :value="__('posts.form.body_label')" />
                        <x-textarea
                            id="body"
                            name="body"
                            rows="8"
                            required
                            placeholder="{{ __('posts.form.body_placeholder') }}"
                            :invalid="(bool) $bodyError"
                            @if ($bodyError) aria-describedby="body-error" @endif
                        >{{ old('body') }}</x-textarea>
                        @if ($bodyError)
                            <p id="body-error" class="text-sm text-rose-500" data-field-error role="alert">{{ $bodyError }}</p>
                        @endif
                    </div>

                    @if (config('blog.easyMDE.enabled'))
                        <x-markdown-editor :draft_id="$draft_id" />
                    @endif
                </x-ui.form-section>

                <x-ui.form-section :title="__('posts.form.optional_heading')">
                    <div class="grid gap-6 md:grid-cols-2">
                        <div class="space-y-2" data-validation-field="description">
                            <x-label for="description" :value="__('posts.form.description_label')" />
                            <x-input
                                id="description"
                                name="description"
                                :value="old('description')"
                                type="text"
                                maxlength="255"
                                placeholder="{{ __('posts.form.description_placeholder') }}"
                                :invalid="(bool) $descriptionError"
                                @if ($descriptionError) aria-describedby="description-error" @endif
                            />
                            @if ($descriptionError)
                                <p id="description-error" class="text-sm text-rose-500" data-field-error role="alert">{{ $descriptionError }}</p>
                            @endif
                        </div>

                        <div class="space-y-2" data-validation-field="featured_image">
                            <x-label for="featured_image" :value="__('posts.form.featured_image_label')" />
                            <x-input
                                id="featured_image"
                                name="featured_image"
                                :value="old('featured_image')"
                                type="url"
                                maxlength="255"
                                placeholder="{{ __('posts.form.featured_image_placeholder') }}"
                                :invalid="(bool) $featuredImageError"
                                aria-describedby="featured-image-hint{{ $featuredImageError ? ' featured-image-error' : '' }}"
                            />
                            <p id="featured-image-hint" class="text-xs text-slate-500 dark:text-slate-400" data-field-hint>
                                {{ __('posts.form.featured_image_help') }}
                            </p>
                            @if ($featuredImageError)
                                <p id="featured-image-error" class="text-sm text-rose-500" data-field-error role="alert">{{ $featuredImageError }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="space-y-2" data-validation-field="published_at">
                        <x-label for="published_at" :value="__('posts.form.publish_label')" />
                        <x-input
                            id="published_at"
                            name="published_at"
                            type="datetime-local"
                            value="{{ old('published_at', now()->format('Y-m-d\TH:i')) }}"
                            min="1971-01-01T00:00"
                            max="2038-01-09T03:14"
                            :invalid="(bool) $publishedAtError"
                            aria-describedby="publish-hint{{ $publishedAtError ? ' published-at-error' : '' }}"
                        />
                        @if ($publishedAtError)
                            <p id="published-at-error" class="text-sm text-rose-500" data-field-error role="alert">{{ $publishedAtError }}</p>
                        @endif
                        <p id="publish-hint" class="mt-2 text-xs text-slate-500 dark:text-slate-400" data-field-hint>
                            {!! __('forms.publish_hint', [
                                'action' => '<button type="button" class="font-semibold text-indigo-500 dark:text-indigo-300" data-clear-published-at="#published_at">'.e(__('forms.publish_clear')).'</button>',
                            ]) !!}
                        </p>
                    </div>

                    @if (config('blog.withTags'))
                        <x-tags-field :initial="old('tags_input')" />
                    @endif

                    <x-categories.multi-select :categories="$categories" />
                </x-ui.form-section>

                <div class="flex flex-wrap items-center justify-between gap-4">
                    <label class="inline-flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                        <input
                            id="is_draft"
                            name="is_draft"
                            type="checkbox"
                            value="1"
                            class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                            {{ old('is_draft') ? 'checked' : '' }}
                        >
                        {{ __('posts.form.save_draft_label') }}
                    </label>
                    @error('is_draft') <p class="w-full text-sm text-rose-500">{{ $message }}</p> @enderror

                    <x-ui.button type="submit">
                        {{ __('posts.form.create_button') }}
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </x-ui.section>
</x-app-layout>
