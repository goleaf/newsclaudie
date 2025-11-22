@props([
    'initial' => '',
])

@php
    $inputId = 'tags_input';
    $value = old($inputId, $initial);
    $tagMaxLength = 50;
    $tagsError = $errors->first('tags') ?? $errors->first('tags.*');
@endphp

<div class="space-y-2" data-validation-field="tags">
    <x-label for="{{ $inputId }}" :value="__('forms.tags_label')" />
    <x-input
        id="{{ $inputId }}"
        name="{{ $inputId }}"
        type="text"
        :value="$value"
        placeholder="{{ __('forms.tags_placeholder') }}"
        maxlength="255"
        :invalid="(bool) $tagsError"
        data-validation-type="tags"
        data-tag-max-length="{{ $tagMaxLength }}"
        data-tag-length-error="{{ __('validation.posts.tags_length', ['max' => $tagMaxLength]) }}"
        aria-describedby="tags-hint{{ $tagsError ? ' tags-error' : '' }}"
    />
    <p id="tags-hint" class="text-sm text-slate-500 dark:text-slate-400" data-field-hint>
        {{ __('forms.tags_help') }}
    </p>
    <p
        id="tags-error"
        class="text-sm text-rose-500 {{ $tagsError ? '' : 'hidden' }}"
        data-field-error
        role="alert"
    >
        {{ $tagsError ?: __('validation.posts.tags_length', ['max' => $tagMaxLength]) }}
    </p>
</div>
