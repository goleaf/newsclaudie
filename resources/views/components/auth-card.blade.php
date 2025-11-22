@props(['logo' => null])

<div class="flex min-h-screen items-center justify-center bg-gradient-to-b from-slate-50 via-white to-slate-100 px-4 py-12 dark:from-slate-950 dark:via-slate-900 dark:to-slate-950">
    <div class="w-full max-w-md space-y-6">
        @if ($logo)
            <div class="flex justify-center">
                {{ $logo }}
            </div>
        @endif

        <x-ui.card class="space-y-6 p-8">
            {{ $slot }}
        </x-ui.card>
    </div>
</div>
