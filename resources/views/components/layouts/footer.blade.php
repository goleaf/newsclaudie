@props([
    'showCopyright' => true,
    'copyrightStart' => 2022,
    'showLicense' => true,
    'appLicense' => [
        'name' => 'MIT',
        'link' => 'https://opensource.org/licenses/MIT/',
    ],
    'contentLicense' => [
        'name' => config('blog.contentLicense.name'),
        'link' => config('blog.contentLicense.link'),
    ],
    'showCredit' => true,
    'showGithub' => true,
    'github' => [
        'user' => 'caendesilva',
        'repo' => 'laravel-blogkit',
    ],
    'showVersion' => true,
])

<footer {{ $attributes->class('border-t border-slate-200 bg-white/80 px-4 py-12 text-center text-sm text-slate-600 dark:border-slate-800 dark:bg-slate-950/80 dark:text-slate-300') }}>
    <div class="mx-auto flex max-w-5xl flex-col items-center gap-6">
        @if ($showCopyright)
            <p class="text-base font-semibold text-slate-900 dark:text-white">
                &copy; {{ $copyrightStart !== (int) date('Y') ? $copyrightStart . '–' . date('Y') : $copyrightStart }}
                {{ config('app.name') }}
            </p>
        @endif

        @if ($showLicense || $showVersion)
            <div class="flex flex-wrap items-center justify-center gap-x-4 gap-y-1 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                @if ($showLicense)
                    <span>
                        {{ __('License:') }}
                        <x-link :href="$appLicense['link']" rel="license noopener noreferrer nofollow">
                            {{ $appLicense['name'] }}
                        </x-link>
                        @if ($contentLicense && $contentLicense !== $appLicense)
                            <span class="px-2 text-slate-400">•</span>
                            {{ __('Content:') }}
                            <x-link :href="$contentLicense['link']" rel="license noopener noreferrer nofollow">
                                {{ $contentLicense['name'] }}
                            </x-link>
                        @endif
                    </span>
                @endif
                @if ($showVersion)
                    <span>{{ __('Version') }} v{{ \App\Providers\BlogServiceProvider::BLOGKIT_VERSION }}</span>
                @endif
            </div>
        @endif

        @if ($showCredit)
            <p class="text-base text-slate-600 dark:text-slate-200">
                {{ __('This site was built using the free and open source') }}
                <x-link href="https://github.com/caendesilva/laravel-blogkit/" target="_blank" rel="noopener">
                    Laravel Blog Starter Kit
                </x-link>
                🚀
            </p>
        @endif

        @if ($showGithub)
            <div class="flex flex-wrap justify-center gap-3">
                <x-ui.button href="https://github.com/{{ $github['user'] }}/{{ $github['repo'] }}" variant="secondary">
                    ⭐ {{ __('Star repo') }}
                </x-ui.button>
                <x-ui.button href="https://github.com/{{ $github['user'] }}/{{ $github['repo'] }}#readme" variant="ghost">
                    💻 {{ __('View source') }}
                </x-ui.button>
                <x-ui.button href="https://github.com/{{ $github['user'] }}" variant="ghost">
                    ➕ {{ __('Follow :user', ['user' => '@' . $github['user']]) }}
                </x-ui.button>
            </div>
        @endif
    </div>
</footer>

