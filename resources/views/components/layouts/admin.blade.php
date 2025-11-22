@php
    $title = $title ?? null;

    $navLinks = collect([
        ['route' => 'admin.dashboard', 'label' => __('nav.admin_dashboard'), 'icon' => 'squares-2x2'],
        ['route' => 'admin.posts.index', 'label' => __('nav.posts'), 'icon' => 'newspaper'],
        ['route' => 'admin.categories.index', 'label' => __('nav.categories'), 'icon' => 'tag'],
        ['route' => 'admin.comments.index', 'label' => __('admin.comments.title'), 'icon' => 'chat-bubble-oval-left-ellipsis'],
        ['route' => 'admin.users.index', 'label' => __('admin.users.title'), 'icon' => 'users'],
    ])
        ->filter(fn ($link) => \Illuminate\Support\Facades\Route::has($link['route']))
        ->values();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="bg-white dark:bg-slate-950">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>
            {{ $title ? $title.' • ' : '' }}{{ config('app.name') }} Admin
        </title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
        @fluxAppearance
        @stack('meta')
    </head>
    <body class="min-h-screen bg-slate-50 text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100" data-admin-keyboard>
        <flux:sidebar sticky stashable class="border-e border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

            <a href="{{ route('home') }}" class="me-5 flex items-center gap-2 text-lg font-semibold tracking-tight" wire:navigate>
                <x-application-logo class="h-8 w-auto text-indigo-500" />
                <span>{{ config('app.name') }}</span>
            </a>

            <flux:navlist variant="outline" class="mt-4">
                @foreach ($navLinks as $link)
                    <flux:navlist.item
                        :href="route($link['route'])"
                        :icon="$link['icon']"
                        :current="request()->routeIs($link['route'])"
                    >
                        {{ $link['label'] }}
                    </flux:navlist.item>
                @endforeach
            </flux:navlist>

            <flux:spacer />

            <flux:text class="px-2 text-xs text-slate-500 dark:text-slate-400">
                {{ __('admin.sidebar_note') }}
            </flux:text>
        </flux:sidebar>

        <flux:header class="border-b border-slate-200 bg-white/70 backdrop-blur dark:border-slate-800 dark:bg-slate-900/70 lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:badge>{{ config('app.name') }}</flux:badge>
        </flux:header>

        <flux:main class="min-h-screen bg-slate-50/80 px-4 py-6 sm:px-6 lg:ps-[20rem]">
            <div class="mx-auto flex w-full max-w-6xl flex-col gap-6">
                {{ $slot }}
            </div>
        </flux:main>

        @livewireScripts
        @fluxScripts
        @stack('scripts')
    </body>
</html>
