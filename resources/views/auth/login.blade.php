<x-guest-layout>
    <x-auth-card>
        <x-slot name="logo">
            <a href="/">
                <x-application-logo class="h-12 w-12 text-indigo-600" />
            </a>
        </x-slot>

        <div class="space-y-6">
            <header class="space-y-2 text-center">
                <p class="text-xs font-semibold uppercase tracking-[0.3em] text-indigo-400">
                    {{ __('Welcome back') }}
                </p>
                <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">
                    {{ __('Log in to your account') }}
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    {{ __('Sign in to continue writing, editing, and publishing stories.') }}
                </p>
            </header>

            @if (config('blog.demoMode'))
                <x-ui.alert variant="info" :title="__('Demo accounts')" size="md">
                    <p>
                        {{ __('Use any of the accounts below (password: :password).', ['password' => 'password']) }}
                        @if (Route::has('register'))
                            {{ __('You can also') }}
                            <x-link :href="route('register')">{{ __('register a guest profile') }}</x-link>
                            .
                        @endif
                    </p>
                    <ul class="mt-3 space-y-1 text-sm">
                        <li><span class="font-semibold">{{ __('Admin') }}:</span> admin@example.org</li>
                        <li><span class="font-semibold">{{ __('Author') }}:</span> author@example.org</li>
                        <li><span class="font-semibold">{{ __('Guest') }}:</span> guest@example.org</li>
                        @if (config('blog.bans'))
                            <li><span class="font-semibold">{{ __('Banned') }}:</span> banned@example.org</li>
                        @endif
                    </ul>
                </x-ui.alert>
            @endif

            <x-auth-session-status :status="session('status')" />

            <x-auth-validation-errors :errors="$errors" />

            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf

                <div class="space-y-1">
                    <x-label for="email" :value="__('Email')" />
                    <x-input
                        id="email"
                        class="mt-1 w-full"
                        type="email"
                        name="email"
                        :value="old('email')"
                        required
                        autofocus
                    />
                </div>

                <div class="space-y-1">
                    <x-label for="password" :value="__('Password')" />
                    <x-input
                        id="password"
                        class="mt-1 w-full"
                        type="password"
                        name="password"
                        required
                        autocomplete="current-password"
                    />
                </div>

                <label for="remember_me" class="inline-flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                    <input
                        id="remember_me"
                        type="checkbox"
                        name="remember"
                        class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                    >
                    {{ __('Remember me') }}
                </label>

                <div class="flex flex-wrap items-center justify-between gap-3">
                    @if (Route::has('password.request'))
                        <x-link :href="route('password.request')" class="text-sm">
                            {{ __('Forgot your password?') }}
                        </x-link>
                    @endif

                    <x-ui.button type="submit">
                        {{ __('Log in') }}
                    </x-ui.button>
                </div>
            </form>
        </div>
    </x-auth-card>
</x-guest-layout>
