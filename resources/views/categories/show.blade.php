<x-app-layout>
    <x-slot name="title">
        {{ $category->name }} - {{ __('Categories') }}
    </x-slot>

    <div class="relative flex items-top justify-center sm:items-center py-4 sm:pt-0">
        <section class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <header class="text-center py-5 mt-5">
                <h1 class="text-3xl font-bold dark:text-white">
                    {{ $category->name }}
                </h1>
                @if($category->description)
                <p class="text-lg text-gray-600 dark:text-gray-400 mt-3 max-w-2xl mx-auto">
                    {{ $category->description }}
                </p>
                @endif
                <p class="text-sm text-gray-500 dark:text-gray-500 mt-2">
                    {{ $posts->total() }} {{ __('posts in this category') }}
                </p>
            </header>

            @if($posts->count())
            <div class="flex flex-row flex-wrap justify-start">
                @foreach ($posts as $post)
                    <x-post-card :post="$post" />
                @endforeach
            </div>

            <div class="mt-6">
                {{ $posts->links() }}
            </div>
            @else
            <div class="text-center py-12">
                <h2 class="text-2xl font-medium dark:text-white mb-3">
                    {{ __('No posts found in this category!') }}
                </h2>
                <x-link :href="route('categories.index')">{{ __('View All Categories') }}</x-link>
            </div>
            @endif
        </section>
    </div>
</x-app-layout>

