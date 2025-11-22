<x-app-layout>
    <x-slot name="title">
        {{ __('Dashboard') }}
    </x-slot>

    <x-ui.page-header
        :title="__('Editorial overview')"
        :subtitle="__('Track posts, people, comments, and traffic from one space.')"
    >
        <x-slot name="meta">
            @if ($posts)
                <x-ui.badge>
                    {{ trans_choice('{0} No posts yet|{1} :count post|[2,*] :count posts', $posts->count(), ['count' => $posts->count()]) }}
                </x-ui.badge>
            @endif
            @if ($users)
                <x-ui.badge variant="info">
                    {{ trans_choice('{1} :count user|[2,*] :count users', $users->count(), ['count' => $users->count()]) }}
                </x-ui.badge>
            @endif
        </x-slot>
    </x-ui.page-header>

    <x-ui.section class="space-y-8 pb-16">
        @if (session('success'))
            <x-ui.alert variant="success" class="rounded-3xl px-5 py-4 text-base">
                {{ session('success') }}
            </x-ui.alert>
        @endif

        @if (config('blog.demoMode'))
            <x-ui.alert
                variant="warning"
                :title="__('Demo mode enabled')"
                class="rounded-3xl px-6 py-4"
            >
                {{ __('User-created posts stay hidden from the public index, and the database resets every six hours.') }}
            </x-ui.alert>
        @endif

        @if ($posts)
            <x-ui.card class="space-y-6">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h3 class="text-xl font-semibold text-slate-900 dark:text-white">{{ __('Manage posts') }}</h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            {{ __('Approve drafts, polish metadata, and publish when ready.') }}
                        </p>
                    </div>
                    @can('create', App\Models\Post::class)
                        <x-ui.button href="{{ route('posts.create') }}">
                            {{ __('New post') }}
                        </x-ui.button>
                    @endcan
                </div>

                @if ($posts->count())
                    <x-ui.data-table class="hidden lg:block">
                        <x-slot name="head">
                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                <th class="px-6 py-3">{{ __('Author') }}</th>
                                <th class="px-6 py-3">{{ __('Title') }}</th>
                                <th class="px-6 py-3">{{ __('Published') }}</th>
                                <th class="px-6 py-3 text-right">{{ __('Actions') }}</th>
                            </tr>
                        </x-slot>

                        @foreach ($posts as $post)
                            <tr class="text-slate-700 transition hover:bg-slate-50/60 dark:text-slate-300 dark:hover:bg-slate-800/60">
                                <td class="px-6 py-4">
                                    <x-link :href="route('posts.index', ['author' => $post->author])" rel="author">
                                        {{ $post->author->name }}
                                    </x-link>
                                </td>
                                <td class="px-6 py-4">
                                    <x-link :href="route('posts.show', $post)">
                                        {{ $post->title }}
                                    </x-link>
                                </td>
                                <td class="px-6 py-4">
                                    @if ($post->isPublished())
                                        <time datetime="{{ $post->published_at }}" class="text-slate-500 dark:text-slate-400">
                                            {{ $post->published_at->format('M j, Y') }}
                                        </time>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-slate-200 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-700 dark:bg-slate-700/60 dark:text-slate-200">
                                            {{ __('Draft') }}
                                        </span>
                                        @can('update', $post)
                                            <form action="{{ route('posts.publish', $post) }}" method="POST" class="inline-flex pl-3" onsubmit="return confirm('{{ __('Publish this post now?') }}');">
                                                @csrf
                                                <button type="submit" class="text-xs font-semibold text-emerald-600 hover:text-emerald-500 dark:text-emerald-300">
                                                    {{ __('Publish') }}
                                                </button>
                                            </form>
                                        @endcan
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-3 text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">
                                        @can('update', $post)
                                            <x-link :href="route('posts.edit', $post)">{{ __('Edit') }}</x-link>
                                        @endcan
                                        @can('delete', $post)
                                            <form action="{{ route('posts.destroy', $post) }}" method="POST" onsubmit="return confirm('{{ __('Are you sure you want to delete this post?') }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-rose-500 hover:text-rose-400">{{ __('Delete') }}</button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </x-ui.data-table>

                    <div class="space-y-4 lg:hidden">
                        @foreach ($posts as $post)
                            <div class="rounded-2xl border border-slate-200/70 p-4 dark:border-slate-800/70">
                                <div class="flex items-center justify-between gap-3">
                                    <x-link :href="route('posts.show', $post)" class="text-base font-semibold text-slate-900 dark:text-white">
                                        {{ $post->title }}
                                    </x-link>
                                    @if ($post->isPublished())
                                        <span class="text-xs uppercase tracking-wide text-slate-400">
                                            {{ $post->published_at->format('M j, Y') }}
                                        </span>
                                    @else
                                        <span class="rounded-full bg-slate-200 px-2 py-1 text-xs font-semibold uppercase tracking-wide text-slate-700 dark:bg-slate-700/60 dark:text-slate-200">
                                            {{ __('Draft') }}
                                        </span>
                                    @endif
                                </div>
                                <p class="mt-1 text-xs uppercase tracking-wide text-slate-400">
                                    {{ __('By :author', ['author' => $post->author->name]) }}
                                </p>
                                <div class="mt-4 flex flex-wrap justify-between gap-3 text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">
                                    <div class="flex gap-3">
                                        @can('update', $post)
                                            <x-link :href="route('posts.edit', $post)">{{ __('Edit') }}</x-link>
                                        @endcan
                                        @can('delete', $post)
                                            <form action="{{ route('posts.destroy', $post) }}" method="POST" onsubmit="return confirm('{{ __('Delete this post?') }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-rose-500">{{ __('Delete') }}</button>
                                            </form>
                                        @endcan
                                    </div>
                                    @if (! $post->isPublished() && Gate::check('update', $post))
                                        <form action="{{ route('posts.publish', $post) }}" method="POST" onsubmit="return confirm('{{ __('Publish this post now?') }}');">
                                            @csrf
                                            <button type="submit" class="text-emerald-600 dark:text-emerald-300">{{ __('Publish') }}</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <x-ui.empty-state :title="__('No posts yet')">
                        @can('create', App\Models\Post::class)
                            <x-ui.button href="{{ route('posts.create') }}">{{ __('Write the first one') }}</x-ui.button>
                        @endcan
                    </x-ui.empty-state>
                @endif
            </x-ui.card>
        @endif

        @if ($users)
            <x-ui.card class="space-y-6">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h3 class="text-xl font-semibold text-slate-900 dark:text-white">{{ __('Manage users') }}</h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            {{ __('Review author access, roles, and verification status.') }}
                        </p>
                    </div>
                </div>

                <x-ui.data-table class="hidden md:block">
                    <x-slot name="head">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            <th class="px-6 py-3">{{ __('ID') }}</th>
                            <th class="px-6 py-3">{{ __('Name') }}</th>
                            <th class="px-6 py-3">{{ __('Email') }}</th>
                            <th class="px-6 py-3">{{ __('Roles') }}</th>
                            <th class="px-6 py-3 text-right">{{ __('Actions') }}</th>
                        </tr>
                    </x-slot>

                    @foreach ($users as $user)
                        <tr class="text-slate-700 dark:text-slate-300">
                            <td class="px-6 py-4 text-slate-400 dark:text-slate-500">#{{ $user->id }}</td>
                            <td class="px-6 py-4">
                                <x-link :href="route('posts.index', ['author' => $user])">{{ $user->name }}</x-link>
                            </td>
                            <td class="px-6 py-4 break-words">
                                {{ $user->email }}
                                @if ($user->email_verified_at)
                                    <span class="ml-2 text-xs text-emerald-500">{{ __('Verified') }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-2">
                                    @if ($user->is_admin)
                                        <span class="rounded-full bg-orange-100 px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-orange-900 dark:bg-orange-500/20 dark:text-orange-200">{{ __('Admin') }}</span>
                                    @endif
                                    @if ($user->is_author)
                                        <span class="rounded-full bg-blue-100 px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-blue-900 dark:bg-blue-500/20 dark:text-blue-200">{{ __('Author') }}</span>
                                    @endif
                                    @if ($user->is_banned)
                                        <span class="rounded-full bg-rose-100 px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-rose-900 dark:bg-rose-500/20 dark:text-rose-200">{{ __('Banned') }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                    {{ __('dashboard.admin_only') }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </x-ui.data-table>

                <div class="space-y-4 md:hidden">
                    @foreach ($users as $user)
                        <div class="rounded-2xl border border-slate-200/70 p-4 dark:border-slate-800/70">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-xs uppercase tracking-wide text-slate-400">#{{ $user->id }}</p>
                                    <x-link :href="route('posts.index', ['author' => $user])" class="text-base font-semibold">
                                        {{ $user->name }}
                                    </x-link>
                                </div>
                                <span class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                    {{ __('dashboard.admin_only') }}
                                </span>
                            </div>
                            <p class="mt-2 text-sm text-slate-500 break-words dark:text-slate-400">
                                {{ $user->email }}
                                @if ($user->email_verified_at)
                                    <span class="ml-2 text-xs text-emerald-500">{{ __('Verified') }}</span>
                                @endif
                            </p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                @if ($user->is_admin)
                                    <span class="rounded-full bg-orange-100 px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-orange-900 dark:bg-orange-500/20 dark:text-orange-200">{{ __('Admin') }}</span>
                                @endif
                                @if ($user->is_author)
                                    <span class="rounded-full bg-blue-100 px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-blue-900 dark:bg-blue-500/20 dark:text-blue-200">{{ __('Author') }}</span>
                                @endif
                                @if ($user->is_banned)
                                    <span class="rounded-full bg-rose-100 px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-rose-900 dark:bg-rose-500/20 dark:text-rose-200">{{ __('Banned') }}</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-ui.card>
        @endif

        @if ($comments)
            <x-ui.card class="space-y-6">
                <div>
                    <h3 class="text-xl font-semibold text-slate-900 dark:text-white">{{ __('Manage comments') }}</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        {{ __('Moderate community feedback across every story.') }}
                    </p>
                </div>

                @if ($comments->count())
                    <x-ui.data-table class="hidden lg:block">
                        <x-slot name="head">
                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                <th class="px-6 py-3">{{ __('User') }}</th>
                                <th class="px-6 py-3">{{ __('Post') }}</th>
                                <th class="px-6 py-3">{{ __('Comment') }}</th>
                                <th class="px-6 py-3 text-right">{{ __('Actions') }}</th>
                            </tr>
                        </x-slot>

                        @foreach ($comments as $comment)
                            <tr class="text-slate-700 dark:text-slate-300">
                                <td class="px-6 py-4">
                                    <x-link :href="route('posts.index', ['author' => $comment->user])">
                                        {{ $comment->user->name }}
                                    </x-link>
                                </td>
                                <td class="px-6 py-4">
                                    <x-link :href="route('posts.show', $comment->post)">
                                        {{ $comment->post->title }}
                                    </x-link>
                                </td>
                                <td class="px-6 py-4 text-slate-500 dark:text-slate-400">
                                    {{ \Illuminate\Support\Str::limit($comment->content, 96) }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-3 text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">
                                        @can('update', $comment)
                                            <x-link :href="route('comments.edit', $comment)">{{ __('Edit') }}</x-link>
                                        @endcan
                                        @can('delete', $comment)
                                            <form action="{{ route('comments.destroy', $comment) }}" method="POST" onsubmit="return confirm('{{ __('Delete this comment?') }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-rose-500 hover:text-rose-400">{{ __('Delete') }}</button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </x-ui.data-table>

                    <div class="space-y-4 lg:hidden">
                        @foreach ($comments as $comment)
                            <div class="rounded-2xl border border-slate-200/70 p-4 dark:border-slate-800/70">
                                <div class="text-xs uppercase tracking-wide text-slate-400">
                                    {{ $comment->created_at->diffForHumans() }}
                                </div>
                                <x-link :href="route('posts.show', $comment->post)" class="text-sm font-semibold text-slate-900 dark:text-white">
                                    {{ $comment->post->title }}
                                </x-link>
                                <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                                    {{ $comment->content }}
                                </p>
                                <div class="mt-3 flex flex-wrap justify-between gap-3 text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">
                                    @can('update', $comment)
                                        <x-link :href="route('comments.edit', $comment)">{{ __('Edit') }}</x-link>
                                    @endcan
                                    @can('delete', $comment)
                                        <form action="{{ route('comments.destroy', $comment) }}" method="POST" onsubmit="return confirm('{{ __('Delete this comment?') }}');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-rose-500">{{ __('Delete') }}</button>
                                        </form>
                                    @endcan
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <x-ui.empty-state :title="__('No comments awaiting review')" />
                @endif
            </x-ui.card>
        @endif

        @if (isset($analytics))
            <x-ui.card class="space-y-8">
                <div>
                    <h3 class="text-xl font-semibold text-slate-900 dark:text-white">{{ __('Analytics overview') }}</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        {{ __('Daily traffic and referrers pulled from your analytics pipeline.') }}
                    </p>
                </div>

                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-2xl border border-slate-200/70 bg-white/50 p-4 dark:border-slate-800/60 dark:bg-slate-900/40">
                        <p class="text-xs uppercase tracking-wide text-slate-400">{{ __('Total views') }}</p>
                        <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-white">{{ number_format($analytics['total_views']) }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200/70 bg-white/50 p-4 dark:border-slate-800/60 dark:bg-slate-900/40">
                        <p class="text-xs uppercase tracking-wide text-slate-400">{{ __('Unique visitors') }}</p>
                        <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-white">{{ number_format($analytics['unique_visitors']) }}</p>
                    </div>
                </div>

                <div>
                    <h4 class="text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                        {{ __('Traffic (last 30 days)') }}
                    </h4>
                    <canvas id="trafficChart" class="mt-4 h-64 w-full"></canvas>
                </div>

                <div class="grid gap-6 lg:grid-cols-2">
                    <div>
                        <h4 class="text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            {{ __('Popular pages') }}
                        </h4>
                        <div class="mt-3 overflow-hidden rounded-2xl border border-slate-200/70 dark:border-slate-800/60">
                            <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                                <thead class="bg-slate-50/70 dark:bg-slate-800/40">
                                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                        <th class="px-4 py-3">{{ __('Page') }}</th>
                                        <th class="px-4 py-3">{{ __('Visitors') }}</th>
                                        <th class="px-4 py-3">{{ __('Views') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                                    @foreach ($analytics['popular_pages'] as $page)
                                        <tr>
                                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $page->page }}</td>
                                            <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ number_format($page->visitors) }}</td>
                                            <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ number_format($page->views) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div x-data="{ tab: 'referrers' }">
                        <div class="flex gap-3">
                            <button type="button" class="text-xs font-semibold uppercase tracking-wide" :class="tab === 'referrers' ? 'text-indigo-500' : 'text-slate-400'" @click="tab = 'referrers'">
                                {{ __('Referrers') }}
                            </button>
                            <button type="button" class="text-xs font-semibold uppercase tracking-wide" :class="tab === 'refs' ? 'text-indigo-500' : 'text-slate-400'" @click="tab = 'refs'">
                                {{ __('Refs') }}
                            </button>
                        </div>

                        <div class="mt-3 overflow-hidden rounded-2xl border border-slate-200/70 dark:border-slate-800/60">
                            <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                                <thead class="bg-slate-50/70 dark:bg-slate-800/40">
                                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                        <th class="px-4 py-3">{{ __('Source') }}</th>
                                        <th class="px-4 py-3">{{ __('Visitors') }}</th>
                                        <th class="px-4 py-3">{{ __('Views') }}</th>
                                    </tr>
                                </thead>
                                <tbody x-show="tab === 'referrers'" class="divide-y divide-slate-100 dark:divide-slate-800/80">
                                    @foreach ($analytics['top_referrers'] as $referrer)
                                        <tr>
                                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $referrer->referrer ?: __('Direct / Unknown') }}</td>
                                            <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ number_format($referrer->visitors) }}</td>
                                            <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ number_format($referrer->views) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tbody x-show="tab === 'refs'" class="divide-y divide-slate-100 dark:divide-slate-800/80">
                                    @foreach ($analytics['top_refs'] as $ref)
                                        <tr>
                                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ \Illuminate\Support\Str::after($ref->referrer, '?ref=') }}</td>
                                            <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ number_format($ref->visitors) }}</td>
                                            <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ number_format($ref->views) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </x-ui.card>

            @push('scripts')
                <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                <script>
                    const trafficCtx = document.getElementById('trafficChart');
                    if (trafficCtx) {
                        new Chart(trafficCtx.getContext('2d'), {
                            type: 'line',
                            data: {
                                labels: @json($analytics['traffic_data']['dates']),
                                datasets: [{
                                    label: '{{ __('Page views') }}',
                                    data: @json($analytics['traffic_data']['views']),
                                    borderColor: 'rgb(79, 70, 229)',
                                    backgroundColor: 'rgba(79, 70, 229, 0.15)',
                                    tension: 0.35,
                                }, {
                                    label: '{{ __('Unique visitors') }}',
                                    data: @json($analytics['traffic_data']['unique']),
                                    borderColor: 'rgb(16, 185, 129)',
                                    backgroundColor: 'rgba(16, 185, 129, 0.15)',
                                    tension: 0.35,
                                }],
                            },
                            options: {
                                responsive: true,
                                interaction: { mode: 'index', intersect: false },
                                plugins: { legend: { display: true } },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        grid: { color: 'rgba(148, 163, 184, 0.2)' },
                                    },
                                    x: { grid: { display: false } },
                                },
                            },
                        });
                    }
                </script>
            @endpush
        @endif
    </x-ui.section>

</x-app-layout>