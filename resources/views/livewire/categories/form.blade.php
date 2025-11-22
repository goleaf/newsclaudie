<?php

use App\Models\Category;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component {
    use AuthorizesRequests;

    public ?Category $category = null;

    public string $name = '';
    public string $slug = '';
    public ?string $description = null;

    public bool $isEditing = false;
    public bool $slugManuallyEdited = false;

    public function mount(?Category $category = null): void
    {
        $this->authorize('access-admin');

        $this->category = $category;
        $this->isEditing = (bool) $category?->exists;

        if ($category) {
            $this->name = $category->name;
            $this->slug = $category->slug;
            $this->description = $category->description;
        }
    }

    public function rules(): array
    {
        $uniqueRule = Rule::unique('categories', 'slug');

        if ($this->isEditing && $this->category) {
            $uniqueRule = $uniqueRule->ignore($this->category->id);
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $uniqueRule],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('validation.category.name_required'),
            'name.string' => __('validation.category.name_string'),
            'name.max' => __('validation.category.name_max'),
            'slug.required' => __('validation.category.slug_required'),
            'slug.string' => __('validation.category.slug_string'),
            'slug.max' => __('validation.category.slug_max'),
            'slug.regex' => __('validation.category.slug_regex'),
            'slug.unique' => __('validation.category.slug_unique'),
            'description.string' => __('validation.category.description_string'),
            'description.max' => __('validation.category.description_max'),
        ];
    }

    public function updated(string $propertyName): void
    {
        if (in_array($propertyName, ['name', 'slug', 'description'], true)) {
            $this->validateOnly($propertyName);
        }
    }

    public function updatedName(string $value): void
    {
        if ($this->slugManuallyEdited) {
            return;
        }

        $this->slug = Str::slug($value);
    }

    public function updatedSlug(string $value): void
    {
        $this->slugManuallyEdited = true;
        $this->slug = Str::slug($value);
    }

    public function save(): mixed
    {
        $this->authorize('access-admin');

        $validated = $this->validate();

        if ($this->isEditing && $this->category) {
            $this->category->update($validated);
            $message = __('messages.category_updated');
        } else {
            $this->category = Category::create($validated);
            $this->isEditing = true;
            $message = __('messages.category_created');
        }

        session()->flash('success', $message);

        return redirect()->route('categories.index');
    }

    public function with(): array
    {
        return [
            'pageTitle' => $this->isEditing
                ? __('categories.form.edit_title')
                : __('categories.form.create_title'),
            'pageSubtitle' => $this->isEditing
                ? __('categories.form.edit_subtitle', ['name' => $this->category?->name ?? ''])
                : __('categories.form.subtitle'),
        ];
    }
}; ?>

<div class="space-y-6">
    <x-ui.page-header
        :title="$pageTitle"
        :subtitle="$pageSubtitle"
    />

    <x-ui.section max-width="max-w-3xl" class="pb-16">
        <x-auth-session-status :status="session('success')" />

        <x-ui.card>
            <form wire:submit.prevent="save" class="space-y-6">
                @php
                    $nameError = $errors->first('name');
                    $slugError = $errors->first('slug');
                    $descriptionError = $errors->first('description');
                @endphp

                <div class="space-y-2" data-validation-field="name">
                    <x-label for="name" :value="__('categories.form.name_label')" />
                    <x-input
                        id="name"
                        type="text"
                        maxlength="255"
                        required
                        autofocus
                        placeholder="{{ __('categories.form.name_placeholder') }}"
                        wire:model.live.debounce.300ms="name"
                        :invalid="(bool) $nameError"
                        @if ($nameError) aria-describedby="name-error" @endif
                    />
                    @if ($nameError)
                        <p id="name-error" class="text-sm text-rose-500" data-field-error>{{ $nameError }}</p>
                    @endif
                </div>

                <div class="space-y-2" data-validation-field="slug">
                    <x-label for="slug" :value="__('categories.form.slug_label')" />
                    <x-input
                        id="slug"
                        type="text"
                        maxlength="255"
                        required
                        placeholder="{{ __('categories.form.slug_placeholder') }}"
                        wire:model.live.debounce.300ms="slug"
                        pattern="^[a-z0-9]+(?:-[a-z0-9]+)*$"
                        :invalid="(bool) $slugError"
                        aria-describedby="slug-hint{{ $slugError ? ' slug-error' : '' }}"
                    />
                    <p id="slug-hint" class="text-xs text-slate-500 dark:text-slate-400" data-field-hint>
                        {{ __('categories.form.slug_help') }}
                    </p>
                    @if ($slugError)
                        <p id="slug-error" class="text-sm text-rose-500" data-field-error>{{ $slugError }}</p>
                    @endif
                </div>

                <div class="space-y-2" data-validation-field="description">
                    <x-label for="description" :value="__('categories.form.description_label')" />
                    <x-textarea
                        id="description"
                        rows="4"
                        placeholder="{{ __('categories.form.description_placeholder') }}"
                        wire:model.live="description"
                        :invalid="(bool) $descriptionError"
                        @if ($descriptionError) aria-describedby="description-error" @endif
                    ></x-textarea>
                    @if ($descriptionError)
                        <p id="description-error" class="text-sm text-rose-500" data-field-error>{{ $descriptionError }}</p>
                    @endif
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3">
                    <x-ui.button :href="route('categories.index')" variant="secondary">
                        {{ __('categories.form.cancel') }}
                    </x-ui.button>
                    <x-ui.button type="submit">
                        {{ $isEditing ? __('categories.form.update') : __('categories.form.create') }}
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </x-ui.section>
</div>
