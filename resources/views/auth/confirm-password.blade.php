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
                    {{ __('Secure area') }}
                </p>
                <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">
                    {{ __('Confirm your password') }}
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    {{ __('For your safety, please re-enter your password before continuing this sensitive action.') }}
                </p>
            </header>

            <x-auth-validation-errors :errors="$errors" />

            <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
                @csrf

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

                <x-ui.button type="submit" class="w-full justify-center">
                    {{ __('Confirm') }}
                </x-ui.button>
            </form>
        </div>
    </x-auth-card>
</x-guest-layout>
