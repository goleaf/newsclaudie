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
                    {{ __('Reset access') }}
                </p>
                <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">
                    {{ __('Forgot your password?') }}
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    {{ __('Enter the email tied to your account and we\'ll send a secure link to choose a new password.') }}
                </p>
            </header>

            <x-auth-session-status :status="session('status')" />

            <x-auth-validation-errors :errors="$errors" />

            <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
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

                <x-ui.button type="submit" class="w-full justify-center">
                    {{ __('Email Password Reset Link') }}
                </x-ui.button>
            </form>
        </div>
    </x-auth-card>
</x-guest-layout>
