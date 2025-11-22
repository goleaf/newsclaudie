<x-app-layout>
    <x-slot name="title">
        {{ __('categories.form.edit_title') }}
    </x-slot>

    <livewire:categories.form :category="$category" />
</x-app-layout>
