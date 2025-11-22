@php
    $isDraft = ! $post->isPublished();
    $viewCount = config('analytics.enabled') ? number_format($post->getViewCount()) : null;
    $commentCount = null;

    if (config('blog.allowComments')) {
        $commentCount = $post->comments_count
            ?? ($post->relationLoaded('comments') ? $post->comments->count() : null);
    }
@endphp

<article class="group flex h-full flex-col overflow-hidden rounded-3xl border border-slate-200/80 bg-white/80 shadow-sm transition hover:-translate-y-1 hover:shadow-lg dark:border-slate-800/70 dark:bg-slate-900/70">
    <a href="{{ route('posts.show', $post) }}" class="relative block aspect-[4/3]">
        <span
            class="absolute inset-0 block object-cover"
            role="img"
            aria-label="{{ $post->title }}"
            style="background-image: url('{{ $post->featured_image }}'); background-size: cover; background-position: center;"
        ></span>
        <span class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-slate-950/10 to-transparent"></span>
        <div class="absolute bottom-4 left-4 flex flex-wrap gap-2 text-xs font-semibold uppercase tracking-wide text-white">
            @if ($isDraft)
                <span class="rounded-full bg-white/20 px-3 py-1 text-[11px]">{{ __('Draft') }}</span>
            @endif

            @if (config('blog.withTags') && config('blog.showTagsOnPostCard') && $post->tags)
                <x-post-tags :tags="$post->tags" class="text-[11px] uppercase tracking-wide" />
            @endif
        </div>
    </a>

    <div class="flex flex-1 flex-col space-y-4 p-6">
        <div class="flex flex-wrap items-center gap-2 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
            @if ($post->author)
                <span>
                    {{ __('By') }}
                    <x-link :href="route('posts.index', ['author' => $post->author])" rel="author">
                        {{ $post->author->name }}
                    </x-link>
                </span>
            @else
                <span>{{ __('By') }} {{ __('posts.unknown_author') }}</span>
            @endif
            @if ($post->isPublished())
                <span>&bull;</span>
                <time datetime="{{ $post->published_at }}" class="text-slate-400">
                    {{ $post->published_at->format('M j, Y') }}
                </time>
            @endif
        </div>

        <a href="{{ route('posts.show', $post) }}" class="group-hover:text-indigo-600 dark:group-hover:text-indigo-400">
            <h3 class="text-xl font-semibold text-slate-900 transition dark:text-white">
                {{ $post->title }}
            </h3>
        </a>

        <p class="text-sm leading-relaxed text-slate-600 dark:text-slate-300">
            {{ $post->description }}
        </p>

        <div class="mt-auto flex flex-wrap items-center justify-between text-xs text-slate-500 dark:text-slate-400">
            <div class="flex items-center gap-3">
                @if ($viewCount)
                    <span class="inline-flex items-center gap-1">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zm0 10.5a3 3 0 110-6 3 3 0 010 6z" />
                        </svg>
                        {{ $viewCount }}
                    </span>
                @endif

                @if ($commentCount)
                    <a href="{{ route('posts.show', $post) }}#comments" class="inline-flex items-center gap-1 hover:text-indigo-600 dark:hover:text-indigo-400">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M21 4H3a1 1 0 00-1 1v13l4-4h15a1 1 0 001-1V5a1 1 0 00-1-1z" />
                        </svg>
                        {{ $commentCount }}
                    </a>
                @endif
            </div>

            <x-ui.button href="{{ route('posts.show', $post) }}" variant="ghost">
                {{ __('Read more') }}
            </x-ui.button>
        </div>
    </div>
</article>