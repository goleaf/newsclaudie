@props([
    'action',
    'method' => 'POST',
    'category' => null,
    'submitLabel' => null,
    'cancelHref' => null,
])

@php
    $httpMethod = strtoupper($method);
    $submitLabel ??= $httpMethod === 'POST'
        ? __('categories.form.create')
        : __('categories.form.update');
    $cancelHref ??= route('categories.index');
@endphp

<x-ui.card>
    <form action="{{ $action }}" method="POST" class="space-y-6">
        @csrf
        @if ($httpMethod !== 'POST')
            @method($httpMethod)
        @endif

        <div class="space-y-2">
            <x-label for="name" :value="__('categories.form.name_label')" />
            <x-input
                id="name"
                name="name"
                :value="old('name', optional($category)->name)"
                type="text"
                maxlength="255"
                required
                autofocus
                placeholder="{{ __('categories.form.name_placeholder') }}"
                data-slug-target="#slug"
            />
            @error('name') <p class="text-sm text-rose-500">{{ $message }}</p> @enderror
        </div>

        <div class="space-y-2">
            <x-label for="slug" :value="__('categories.form.slug_label')" />
            <x-input
                id="slug"
                name="slug"
                :value="old('slug', optional($category)->slug)"
                type="text"
                maxlength="255"
                required
                placeholder="{{ __('categories.form.slug_placeholder') }}"
            />
            <p class="text-xs text-slate-500 dark:text-slate-400">
                {{ __('categories.form.slug_help') }}
            </p>
            @error('slug') <p class="text-sm text-rose-500">{{ $message }}</p> @enderror
        </div>

        <div class="space-y-2">
            <x-label for="description" :value="__('categories.form.description_label')" />
            <x-textarea
                id="description"
                name="description"
                rows="4"
                placeholder="{{ __('categories.form.description_placeholder') }}"
            >{{ old('description', optional($category)->description) }}</x-textarea>
            @error('description') <p class="text-sm text-rose-500">{{ $message }}</p> @enderror
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <x-ui.button :href="$cancelHref" variant="secondary">
                {{ __('categories.form.cancel') }}
            </x-ui.button>
            <x-ui.button type="submit">
                {{ $submitLabel }}
            </x-ui.button>
        </div>
    </form>
</x-ui.card>

