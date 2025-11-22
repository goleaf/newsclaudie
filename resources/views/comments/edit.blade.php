<x-app-layout>
    <x-slot name="title">
        {{ __('comments.edit_title') }}
    </x-slot>

    <x-ui.page-header
        :title="__('comments.edit_heading')"
        :subtitle="__('comments.edit_subheading')"
    />

    <x-ui.section max-width="max-w-3xl" class="pb-16">
        <x-ui.card>
            <form method="POST" action="{{ route('comments.update', $comment) }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div class="space-y-2">
                    <x-label for="content" :value="__('comments.field_label')" />
                    <x-textarea
                        id="content"
                        name="content"
                        rows="6"
                        placeholder="{{ __('post.comments.placeholder') }}"
                    >{{ old('content', $comment->content) }}</x-textarea>
                    @error('content')
                        <p class="text-sm text-rose-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-end gap-3">
                    <x-ui.button :href="route('posts.show', $comment->post)" variant="ghost">
                        {{ __('comments.cancel') }}
                    </x-ui.button>
                    <x-ui.button type="submit">
                        {{ __('comments.update_button') }}
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </x-ui.section>
</x-app-layout>

