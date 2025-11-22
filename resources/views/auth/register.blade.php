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
                    {{ __('Join the newsroom') }}
                </p>
                <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">
                    {{ __('Create your account') }}
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    {{ __('Publish stories, collaborate with editors, and keep all your writing in one place.') }}
                </p>
            </header>

            <x-auth-validation-errors :errors="$errors" />

            <form method="POST" action="{{ route('register') }}" class="space-y-5">
                @csrf

                <div class="space-y-1">
                    <x-label for="name" :value="__('Name')" />
                    <x-input
                        id="name"
                        class="mt-1 w-full"
                        type="text"
                        name="name"
                        :value="old('name')"
                        required
                        autofocus
                    />
                </div>

                <div class="space-y-1">
                    <x-label for="email" :value="__('Email')" />
                    <x-input
                        id="email"
                        class="mt-1 w-full"
                        type="email"
                        name="email"
                        :value="old('email')"
                        required
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
                        autocomplete="new-password"
                    />
                </div>

                <div class="space-y-1">
                    <x-label for="password_confirmation" :value="__('Confirm Password')" />
                    <x-input
                        id="password_confirmation"
                        class="mt-1 w-full"
                        type="password"
                        name="password_confirmation"
                        required
                    />
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3">
                    <x-link :href="route('login')" class="text-sm">
                        {{ __('Already registered?') }}
                    </x-link>

                    <x-ui.button type="submit">
                        {{ __('Register') }}
                    </x-ui.button>
                </div>
            </form>
        </div>
    </x-auth-card>
</x-guest-layout>
