@props(['post'])

<article class="group flex h-full flex-col overflow-hidden rounded-3xl border border-slate-200/80 bg-white/80 shadow-sm transition hover:-translate-y-1 hover:shadow-lg dark:border-slate-800/70 dark:bg-slate-900/70">
    <!-- Featured Image -->
    @if ($post->featured_image && basename($post->featured_image) !== 'default.jpg')
        <a href="{{ route('posts.show', $post) }}" class="relative block aspect-[16/9]">
            <img
                src="{{ $post->featured_image }}"
                alt="{{ $post->title }}"
                loading="lazy"
                class="absolute inset-0 h-full w-full object-cover"
            >
            <span class="absolute inset-0 bg-gradient-to-t from-slate-950/60 via-slate-950/10 to-transparent"></span>
        </a>
    @endif

    <!-- Card Content -->
    <div class="flex flex-1 flex-col space-y-3 p-5">
        <!-- Meta Information -->
        <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
            @if ($post->published_at)
                <time datetime="{{ $post->published_at->toIso8601String() }}" class="font-medium">
                    {{ $post->published_at->format('M j, Y') }}
                </time>
            @endif
            
            @if ($post->author)
                <span>&bull;</span>
                <span>
                    {{ __('By') }}
                    <a
                        href="{{ route('posts.index', ['author' => $post->author]) }}"
                        class="font-medium hover:text-indigo-600 dark:hover:text-indigo-400"
                        rel="author"
                    >
                        {{ $post->author->name }}
                    </a>
                </span>
            @endif
        </div>

        <!-- Title -->
        <a href="{{ route('posts.show', $post) }}" class="group-hover:text-indigo-600 dark:group-hover:text-indigo-400">
            <h3 class="text-lg font-semibold leading-tight text-slate-900 transition dark:text-white">
                {{ $post->title }}
            </h3>
        </a>

        <!-- Excerpt -->
        @if ($post->description)
            <p class="text-sm leading-relaxed text-slate-600 dark:text-slate-300 line-clamp-3">
                {{ $post->description }}
            </p>
        @endif

        <!-- Categories -->
        @if ($post->categories && $post->categories->isNotEmpty())
            <div class="flex flex-wrap gap-2 pt-2">
                @foreach ($post->categories as $category)
                    <a
                        href="{{ route('posts.index', ['category' => $category->slug]) }}"
                        class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700 transition hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700"
                    >
                        {{ $category->name }}
                    </a>
                @endforeach
            </div>
        @endif

        <!-- Read More Link -->
        <div class="mt-auto pt-3">
            <a
                href="{{ route('posts.show', $post) }}"
                class="inline-flex items-center gap-1 text-sm font-semibold text-indigo-600 transition hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300"
            >
                {{ __('Read more') }}
                <svg class="h-4 w-4 transition group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        </div>
    </div>
</article>
