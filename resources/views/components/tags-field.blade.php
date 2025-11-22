@props([
    'initial' => '',
])

@php
    $inputId = 'tags_input';
    $value = old($inputId, $initial);
@endphp

<div class="space-y-2">
    <x-label for="{{ $inputId }}" :value="__('forms.tags_label')" />
    <x-input
        id="{{ $inputId }}"
        name="{{ $inputId }}"
        type="text"
        :value="$value"
        placeholder="{{ __('forms.tags_placeholder') }}"
        maxlength="255"
    />
    <p class="text-sm text-slate-500 dark:text-slate-400">
        {{ __('forms.tags_help') }}
    </p>
    @error('tags')
        <p class="text-sm text-rose-500">{{ $message }}</p>
    @enderror
</div>

