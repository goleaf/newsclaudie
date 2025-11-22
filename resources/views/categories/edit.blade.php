<x-app-layout>
    <x-slot name="title">
        {{ __('Edit Category') }}
    </x-slot>

    <x-ui.page-header
        :title="__('Edit Category')"
        :subtitle="$category->name"
    />

    <x-ui.section max-width="max-w-3xl" class="pb-16">
        <x-categories.form
            :action="route('categories.update', $category)"
            method="PUT"
            :category="$category"
        />
    </x-ui.section>
</x-app-layout>

