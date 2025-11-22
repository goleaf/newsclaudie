<x-app-layout>
    <x-ui.page-header
        :title="$title ?? config('app.name')"
        :subtitle="$subtitle ?? null"
    />

    <x-ui.section max-width="max-w-4xl" class="pb-16">
        <x-ui.card>
            <div class="prose prose-slate max-w-none dark:prose-invert">
                {!! $markdown !!}
            </div>
        </x-ui.card>
    </x-ui.section>
</x-app-layout>