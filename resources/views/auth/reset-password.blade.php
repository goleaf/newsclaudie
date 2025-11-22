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
                    {{ __('Security first') }}
                </p>
                <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">
                    {{ __('Choose a new password') }}
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    {{ __('Use a strong phrase you haven’t used before to keep your newsroom account protected.') }}
                </p>
            </header>

            <x-auth-validation-errors :errors="$errors" />

            <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
                @csrf

                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                <div class="space-y-1">
                    <x-label for="email" :value="__('Email')" />
                    <x-input
                        id="email"
                        class="mt-1 w-full"
                        type="email"
                        name="email"
                        :value="old('email', $request->email)"
                        required
                        autofocus
                    />
                </div>

                <div class="space-y-1">
                    <x-label for="password" :value="__('New password')" />
                    <x-input
                        id="password"
                        class="mt-1 w-full"
                        type="password"
                        name="password"
                        required
                    />
                </div>

                <div class="space-y-1">
                    <x-label for="password_confirmation" :value="__('Confirm password')" />
                    <x-input
                        id="password_confirmation"
                        class="mt-1 w-full"
                        type="password"
                        name="password_confirmation"
                        required
                    />
                </div>

                <x-ui.button type="submit" class="w-full justify-center">
                    {{ __('Reset Password') }}
                </x-ui.button>
            </form>
        </div>
    </x-auth-card>
</x-guest-layout>
