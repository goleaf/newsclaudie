<x-app-layout>
    <x-slot name="title">
        {{ $post->title }}
    </x-slot>

	@push('meta')
	<meta property="og:title" content="{{ $post->title }}">
	<meta property="og:type" content="article" />
	<meta property="og:description" content="{{ $post->description }}">
	<meta property="og:image" content="{{ $post->featured_image }}">
	<meta property="og:url" content="{{ route('posts.show', ['post' => $post]) }}">
	@if($post->isPublished())
	    <meta property="og:article:published_time" content="{{ $post->published_at }}">
	@endif
	@if(config('blog.showUpdatedAt'))
	    <meta property="og:article:modified_time " content="{{ $post->updated_at }}">
	@endif
	<meta name="twitter:card" content="summary_large_image">
    @php
        $authorName = $post->author->name ?? __('posts.unknown_author');
    @endphp
	<meta name="author" content="{{ $authorName }}">
	<meta name="description" content="{{ $post->description }}">
	@if(config('blog.withTags') && $post->tags)
	    <meta name="keywords" itemprop="keywords" content="{{ implode(', ', $post->tags) }}">
	@endif
	@if(config('blog.contentLicense.enabled'))
	    <meta itemprop="license" content="{{ config('blog.contentLicense.link') }}">
	@endif
	@endpush
	
    <x-ui.page-header
        :title="$post->title"
        :subtitle="$post->description"
    >
        <x-slot name="meta">
            @if (! $post->isPublished())
                <x-ui.badge variant="info">{{ __('post.details.draft') }}</x-ui.badge>
            @else
                <x-ui.badge variant="info">
                    {{ __('post.details.published_on', ['date' => $post->published_at->format('M j, Y')]) }}
                </x-ui.badge>
            @endif
            @if (config('analytics.enabled'))
                <x-ui.badge>
                    {{ __('post.details.views', ['count' => number_format($post->getViewCount())]) }}
                </x-ui.badge>
            @endif
            <x-ui.badge>
                {{ __('post.details.byline', ['author' => $authorName]) }}
            </x-ui.badge>
        </x-slot>
    </x-ui.page-header>

    @if ($post->categories->isNotEmpty())
        <div class="mx-auto mt-4 max-w-4xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-3xl border border-slate-200/70 bg-white/70 p-4 shadow-sm dark:border-slate-800/60 dark:bg-slate-900/50">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                    {{ __('post.details.categories_label') }}
                </p>
                <div class="mt-3 flex flex-wrap gap-3">
                    @foreach ($post->categories as $category)
                        <x-ui.button
                            href="{{ route('posts.index', ['category' => $category->slug]) }}"
                            variant="secondary"
                            class="border-slate-300/70 text-xs font-semibold uppercase tracking-wide dark:border-slate-700"
                        >
                            {{ $category->name }}
                        </x-ui.button>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <x-ui.section max-width="max-w-4xl" class="flex flex-col gap-8" itemscope itemtype="http://schema.org/Article">
        <meta itemprop="identifier" content="{{ $post->slug }}">
        <meta itemprop="url" content="{{ route('posts.show', $post) }}">
        @if ($post->featured_image && basename($post->featured_image) !== 'default.jpg')
            <figure class="overflow-hidden rounded-3xl shadow-lg" itemprop="image" itemscope itemtype="https://schema.org/ImageObject">
                <meta itemprop="url" content="{{ $post->featured_image }}">
                <img src="{{ $post->featured_image }}" alt="{{ $post->title }}" class="h-80 w-full object-cover">
            </figure>
        @endif

        <x-ui.card>
            <div class="flex flex-wrap gap-2 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                @if (config('blog.withTags') && $post->tags)
                    <x-post-tags :tags="$post->tags" class="text-[11px]" />
                @endif
                @if ($post->isPublished() && config('blog.showUpdatedAt') && $post->updated_at->ne($post->published_at))
                    <span>{{ __('Updated :date', ['date' => $post->updated_at->format('M j, Y')]) }}</span>
                @endif
            </div>

            <div class="prose prose-slate max-w-none dark:prose-invert" itemprop="articleBody">
                {!! $markdown !!}
            </div>

        </x-ui.card>

        @if (config('blog.contentLicense.enabled'))
            <p class="text-xs uppercase tracking-wide text-slate-400">
                {{ __('This post is licensed under') }}
                <x-link href="{{ config('blog.contentLicense.link') }}" target="_blank" rel="noopener">
                    {{ config('blog.contentLicense.name') }}
                </x-link>
            </p>
        @endif

        <div class="flex flex-wrap justify-between gap-4 text-sm text-slate-500 dark:text-slate-300">
            <div class="space-x-3">
                <x-link href="{{ route('home') }}">{{ __('post.back_to_home') }}</x-link>
                @can('update', $post)
                    <x-link href="{{ route('posts.edit', $post) }}">{{ __('post.edit') }}</x-link>
                @endcan
            </div>
        </div>

        @if (config('blog.allowComments'))
            <x-ui.card id="comments" class="space-y-6">
                <header class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-slate-900 dark:text-white">
                        {{ __('post.comments.title') }}
                    </h2>
                    @if ($comments && $comments->total())
                        <span class="text-sm text-slate-500">
                            {{ trans_choice('post.comments.count', $comments->total(), ['count' => $comments->total()]) }}
                        </span>
                    @endif
                </header>

                @if (session('success'))
                    <x-ui.alert variant="success" size="md">
                        {{ session('success') }}
                    </x-ui.alert>
                @endif

                @if ($comments)
                    <x-comments.list
                        :comments="$comments"
                        :empty-message="__('post.comments.empty')"
                        :per-page-options="$commentPerPageOptions"
                        :per-page-value="$commentsPerPage"
                        per-page-field="comments_per_page"
                        per-page-anchor="comments"
                    />
                @endif

                <div class="pt-4">
                    @php
                        $commentGate = Gate::inspect('create', App\Models\Comment::class);
                    @endphp

                    @if ($commentGate->allowed())
                        <form action="{{ route('posts.comments.store', $post) }}" method="POST" class="space-y-4">
                            @csrf
                            <div class="space-y-2">
                                <x-label for="comment_content" :value="__('post.comments.label')" />
                                <x-textarea
                                    id="comment_content"
                                    name="content"
                                    rows="4"
                                    placeholder="{{ __('post.comments.placeholder') }}"
                                >{{ old('content') }}</x-textarea>
                                @error('content')
                                    <p class="text-sm text-rose-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="flex items-center justify-end">
                                <x-ui.button type="submit">
                                    {{ __('post.comments.submit') }}
                                </x-ui.button>
                            </div>
                        </form>
                    @elseif ($commentGate->message() === 'Your email must be verified to comment.')
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            {{ __('post.verify_email_prompt') }}
                            <x-link :href="route('verification.notice')">{{ __('post.resend_email') }}</x-link>
                        </p>
                    @endif

                    @guest
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            <x-link :href="route('login')">{{ __('post.login') }}</x-link>
                            @if (Route::has('register'))
                                {{ __('post.or') }}
                                <x-link :href="route('register')">{{ __('post.sign_up') }}</x-link>
                            @endif
                            {{ __('post.comments.login_prompt') }}
                        </p>
                    @endguest
                </div>
            </x-ui.card>
        @endif
    </x-ui.section>
</x-app-layout>