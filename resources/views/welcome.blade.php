<x-app-layout>
    <x-ui.page-header
        :title="config('app.name')"
        :subtitle="__('nav.tagline')"
    >
        @if (config('blog.demoMode'))
            <x-slot name="meta">
                <x-ui.badge variant="success">{{ __('home.demo_label') }}</x-ui.badge>
            </x-slot>
        @endif
    </x-ui.page-header>

    <x-ui.section class="pb-16">
        @if ($postCount)
            <h2 class="mb-8 text-center text-2xl font-semibold text-slate-900 dark:text-white">
                {{ __('home.latest_heading') }}
            </h2>
            <div class="flex flex-wrap justify-center gap-6">
                @foreach ($latestPosts as $post)
                    <x-post-card :post="$post" />
                @endforeach
            </div>
        @else
            <x-ui.empty-state :title="__('home.empty')">
                <x-ui.button href="{{ route('posts.index') }}">
                    {{ __('View posts') }}
                </x-ui.button>
            </x-ui.empty-state>
        @endif
    </x-ui.section>
</x-app-layout>