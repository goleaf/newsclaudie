<x-app-layout>
    <x-slot name="title">
        {{ __('Create Category') }}
    </x-slot>

    <x-ui.page-header
        :title="__('Create New Category')"
        :subtitle="__('Keep your posts organized with clear taxonomy.')"
    />

    <x-ui.section max-width="max-w-3xl" class="pb-16">
        <x-categories.form :action="route('categories.store')" />
    </x-ui.section>
</x-app-layout>

