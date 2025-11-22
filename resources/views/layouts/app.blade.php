<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        
        @stack('meta')
        <meta property="og:site_name" content="{{ config('app.name', 'Laravel') }}">

        <title>
            {{ (isset($title) ? $title . ' - ' : '') . config('app.name', 'Laravel') }}
        </title>

        @vite(['resources/css/app.css', 'resources/js/app.ts'])
        @livewireStyles
    </head>
    <body class="font-sans antialiased text-slate-900 dark:text-slate-100 bg-slate-50 dark:bg-slate-950">
        <div class="min-h-screen flex flex-col">
            <x-navigation.main />

            <!-- Page Content -->
            <main class="flex-1">
                {{ $slot }}
            </main>

            <x-layouts.footer />
        </div>

        @stack('scripts')
        @livewireScripts
    </body>
</html>
