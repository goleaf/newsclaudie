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
                    {{ __('One last step') }}
                </p>
                <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">
                    {{ __('Verify your email address') }}
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    {{ __('We’ve sent you a link. Confirming it helps keep your account secure and ensures you get important updates.') }}
                </p>
            </header>

            @if (session('status') === 'verification-link-sent')
                <x-ui.alert variant="success" size="md">
                    {{ __('A new verification link is on its way to :email.', ['email' => optional(auth()->user())->email]) }}
                </x-ui.alert>
            @endif

            <div class="flex flex-wrap items-center justify-between gap-3">
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf
                    <x-ui.button type="submit">
                        {{ __('Resend verification email') }}
                    </x-ui.button>
                </form>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-sm font-semibold text-slate-500 hover:text-slate-700 dark:text-slate-300 dark:hover:text-slate-100">
                        {{ __('Log out') }}
                    </button>
                </form>
            </div>
        </div>
    </x-auth-card>
</x-guest-layout>
