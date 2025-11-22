@php
    $primaryLinks = collect([
        ['label' => __('nav.home'), 'route' => 'home', 'active' => 'home'],
        ['label' => __('nav.posts'), 'route' => 'posts.index', 'active' => 'posts.*'],
        ['label' => __('nav.categories'), 'route' => 'categories.index', 'active' => 'categories.*'],
    ])
        ->when(config('blog.readme'), function ($links) {
            $links->push([
                'label' => __('nav.readme'),
                'route' => 'readme',
                'active' => 'readme',
            ]);

            return $links;
        })
        ->when(
            \Illuminate\Support\Facades\Auth::check() &&
            \Illuminate\Support\Facades\Auth::user()->can('access-admin'),
            function ($links) {
                $links->push([
                    'label' => __('nav.admin_dashboard'),
                    'route' => 'admin.dashboard',
                    'active' => 'admin.*',
                ]);

                return $links;
            }
        )
        ->filter(fn ($link) => \Illuminate\Support\Facades\Route::has($link['route']))
        ->map(fn ($link) => [
            'label' => $link['label'],
            'url' => route($link['route']),
            'active' => request()->routeIs($link['active']),
        ])
        ->values();

    $availableLocales = array_values(array_unique(config('app.supported_locales', [config('app.locale')])));
@endphp

<nav {{ $attributes->class('sticky top-0 z-40 border-b border-slate-200 bg-white/80 backdrop-blur dark:border-slate-800 dark:bg-slate-900/80') }}>
    <div class="mx-auto flex h-16 max-w-7xl items-center gap-4 px-4 sm:px-6 lg:px-8">
        <a href="{{ \Illuminate\Support\Facades\Route::has('home') ? route('home') : url('/') }}" class="flex items-center gap-3">
            <x-application-logo class="h-10 w-auto fill-current text-indigo-600 dark:text-indigo-400" />
            <div class="flex flex-col">
                <span class="text-base font-semibold text-slate-900 dark:text-white">{{ config('app.name') }}</span>
                <span class="hidden text-xs text-slate-500 dark:text-slate-400 sm:block">{{ __('nav.tagline') }}</span>
            </div>
        </a>

        @if ($primaryLinks->isNotEmpty())
            <div class="hidden md:flex md:items-center md:gap-1">
                @foreach ($primaryLinks as $link)
                    <a
                        href="{{ $link['url'] }}"
                        class="rounded-full px-3 py-2 text-sm font-medium transition-colors {{ $link['active'] ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white' }}"
                    >
                        {{ $link['label'] }}
                    </a>
                @endforeach
            </div>
        @endif

        <div class="ml-auto flex items-center gap-2">
            @if (count($availableLocales) > 1)
                <form method="POST" action="{{ route('locale.update') }}" class="hidden items-center gap-1 rounded-full border border-slate-200 bg-white/70 p-1 dark:border-slate-700 dark:bg-slate-800/70 sm:flex" aria-label="{{ __('nav.language_label') }}">
                    @csrf
                    @foreach ($availableLocales as $locale)
                        <button type="submit" name="locale" value="{{ $locale }}" class="rounded-full px-3 py-1 text-xs font-semibold tracking-wide transition-colors {{ app()->getLocale() === $locale ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white' }}">
                            {{ strtoupper($locale) }}
                        </button>
                    @endforeach
                </form>
            @endif

            <button
                id="theme-toggle"
                type="button"
                aria-label="{{ __('nav.theme_label') }}"
                title="{{ __('nav.theme_label') }}"
                data-theme-label-light="{{ __('nav.theme_light') }}"
                data-theme-label-dark="{{ __('nav.theme_dark') }}"
                class="rounded-full border border-transparent p-2 text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white"
            >
                <svg id="theme-toggle-dark-icon" class="hidden h-5 w-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path></svg>
                <svg id="theme-toggle-light-icon" class="hidden h-5 w-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" fill-rule="evenodd" clip-rule="evenodd"></path></svg>
            </button>

            <button
                type="button"
                class="inline-flex items-center justify-center rounded-full border border-slate-200 p-2 text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white md:hidden"
                aria-label="{{ __('nav.menu') }}"
                aria-controls="primary-mobile-nav"
                aria-expanded="false"
                data-mobile-nav-toggle
            >
                <span class="sr-only">{{ __('nav.menu') }}</span>
                <svg data-icon="open" class="h-6 w-6" viewBox="0 0 24 24" stroke="currentColor" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
                <svg data-icon="close" class="hidden h-6 w-6" viewBox="0 0 24 24" stroke="currentColor" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 6l12 12M6 18L18 6" />
                </svg>
            </button>
        </div>
    </div>

    <div id="primary-mobile-nav" class="hidden border-t border-slate-200 bg-white/90 px-4 pb-4 pt-2 dark:border-slate-800 dark:bg-slate-900 md:hidden">
        @if ($primaryLinks->isNotEmpty())
            <div class="space-y-1 py-3">
                @foreach ($primaryLinks as $link)
                    <a
                        href="{{ $link['url'] }}"
                        class="block rounded-lg px-4 py-2 text-base font-semibold transition-colors {{ $link['active'] ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white' }}"
                    >
                        {{ $link['label'] }}
                    </a>
                @endforeach
            </div>
        @endif

        @if (count($availableLocales) > 1)
            <div class="border-t border-slate-200 pt-4 dark:border-slate-800">
                <p class="text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('nav.language_label') }}</p>
                <form method="POST" action="{{ route('locale.update') }}" class="mt-3 flex flex-wrap gap-2">
                    @csrf
                    @foreach ($availableLocales as $locale)
                        <button type="submit" name="locale" value="{{ $locale }}" class="rounded-full border border-slate-200 px-3 py-1 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 hover:text-slate-900 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white {{ app()->getLocale() === $locale ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : '' }}">
                            {{ strtoupper($locale) }}
                        </button>
                    @endforeach
                </form>
            </div>
        @endif
    </div>
</nav>
