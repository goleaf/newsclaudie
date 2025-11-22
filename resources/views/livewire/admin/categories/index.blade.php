<?php

use App\Livewire\Concerns\ManagesPerPage;
use App\Models\Category;
use App\Support\Pagination\PageSize;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use function Livewire\Volt\layout;
use function Livewire\Volt\title;

layout('components.layouts.admin');
title(__('admin.categories.title'));

new class extends Component {
    use AuthorizesRequests;
    use ManagesPerPage;
    use WithPagination;

    public ?int $editingId = null;
    public ?string $editingField = null;
    public array $editingValues = [
        'name' => '',
        'slug' => '',
    ];

    public bool $formOpen = false;
    public ?int $formCategoryId = null;
    public string $formName = '';
    public string $formSlug = '';
    public ?string $formDescription = null;
    public bool $formSlugManuallyEdited = false;
    public ?string $statusMessage = null;
    public string $statusLevel = 'success';

    protected array $queryString = [];

    public function mount(): void
    {
        $this->authorize('access-admin');
        $this->queryString['perPage'] = ['except' => PageSize::contextDefault('admin')];
        $this->statusMessage = session('success');
    }

    public function updated(string $property, mixed $value): void
    {
        if ($this->isInlineProperty($property)) {
            $this->validateOnly($property, $this->inlineRules(), $this->inlineMessages());
        }
    }

    public function deleteCategory(int $categoryId): void
    {
        $this->authorize('access-admin');

        $category = Category::query()->findOrFail($categoryId);

        if ($this->editingId === $categoryId) {
            $this->cancelInlineEdit();
        }

        if ($this->formCategoryId === $categoryId) {
            $this->resetForm();
            $this->formOpen = false;
        }

        $category->delete();

        $this->statusMessage = __('messages.category_deleted');
        $this->statusLevel = 'success';

        $this->resetPage();
    }

    public function startEditing(int $categoryId, string $field): void
    {
        if (! $this->isInlineField($field)) {
            return;
        }

        $this->authorize('access-admin');

        $category = Category::findOrFail($categoryId);

        $this->resetErrorBag();
        $this->resetValidation();

        $this->editingId = $category->id;
        $this->editingField = $field;
        $this->editingValues = [
            'name' => $category->name,
            'slug' => $category->slug,
        ];
    }

    public function saveInlineEdit(): void
    {
        if (! $this->editingId || ! $this->editingField || ! $this->isInlineField($this->editingField)) {
            return;
        }

        $property = "editingValues.{$this->editingField}";

        $this->validateOnly($property, $this->inlineRules(), $this->inlineMessages());

        $category = Category::findOrFail($this->editingId);

        $this->authorize('access-admin');

        $category->update([
            $this->editingField => $this->editingValues[$this->editingField],
        ]);

        $this->statusMessage = __('messages.category_updated');
        $this->statusLevel = 'success';

        $this->cancelInlineEdit();
        $this->resetPage();
    }

    public function cancelInlineEdit(): void
    {
        $this->reset(['editingId', 'editingField']);
        $this->editingValues = [
            'name' => '',
            'slug' => '',
        ];
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function startCreateForm(): void
    {
        $this->authorize('access-admin');

        $this->resetForm();
        $this->formOpen = true;
    }

    public function startEditForm(int $categoryId): void
    {
        $this->authorize('access-admin');

        $category = Category::query()->findOrFail($categoryId);

        $this->formCategoryId = $category->id;
        $this->formName = $category->name;
        $this->formSlug = $category->slug;
        $this->formDescription = $category->description;
        $this->formSlugManuallyEdited = true;
        $this->formOpen = true;

        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function cancelForm(): void
    {
        $this->resetForm();
        $this->formOpen = false;
    }

    public function updatedFormName(string $value): void
    {
        if ($this->formSlugManuallyEdited) {
            return;
        }

        $this->formSlug = Str::slug($value);
    }

    public function updatedFormSlug(string $value): void
    {
        $this->formSlugManuallyEdited = true;
        $this->formSlug = Str::slug($value);

        $this->validateOnly('formSlug', $this->formRules(), $this->formMessages());
    }

    public function saveCategory(): void
    {
        $this->authorize('access-admin');

        $validated = $this->validate($this->formRules(), $this->formMessages());

        $payload = [
            'name' => $validated['formName'],
            'slug' => $validated['formSlug'],
            'description' => $validated['formDescription'],
        ];

        if ($this->formCategoryId) {
            $category = Category::findOrFail($this->formCategoryId);
            $category->update($payload);
            $message = __('messages.category_updated');
        } else {
            $category = Category::create($payload);
            $message = __('messages.category_created');
        }

        $this->statusMessage = $message;
        $this->statusLevel = 'success';

        $this->resetForm();
        $this->formOpen = true;
        $this->resetPage();
    }

    protected function inlineRules(): array
    {
        return [
            'editingValues.name' => ['required', 'string', 'max:255'],
            'editingValues.slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('categories', 'slug')->ignore($this->editingId),
            ],
        ];
    }

    protected function inlineMessages(): array
    {
        return [
            'editingValues.name.required' => __('validation.category.name_required'),
            'editingValues.name.string' => __('validation.category.name_string'),
            'editingValues.name.max' => __('validation.category.name_max'),
            'editingValues.slug.required' => __('validation.category.slug_required'),
            'editingValues.slug.string' => __('validation.category.slug_string'),
            'editingValues.slug.max' => __('validation.category.slug_max'),
            'editingValues.slug.regex' => __('validation.category.slug_regex'),
            'editingValues.slug.unique' => __('validation.category.slug_unique'),
        ];
    }

    protected function formRules(): array
    {
        $uniqueRule = Rule::unique('categories', 'slug');

        if ($this->formCategoryId) {
            $uniqueRule = $uniqueRule->ignore($this->formCategoryId);
        }

        return [
            'formName' => ['required', 'string', 'max:255'],
            'formSlug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $uniqueRule],
            'formDescription' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function formMessages(): array
    {
        return [
            'formName.required' => __('validation.category.name_required'),
            'formName.string' => __('validation.category.name_string'),
            'formName.max' => __('validation.category.name_max'),
            'formSlug.required' => __('validation.category.slug_required'),
            'formSlug.string' => __('validation.category.slug_string'),
            'formSlug.max' => __('validation.category.slug_max'),
            'formSlug.regex' => __('validation.category.slug_regex'),
            'formSlug.unique' => __('validation.category.slug_unique'),
            'formDescription.string' => __('validation.category.description_string'),
            'formDescription.max' => __('validation.category.description_max'),
        ];
    }

    public function getFormTitleProperty(): string
    {
        return $this->formCategoryId
            ? __('categories.form.edit_title')
            : __('categories.form.create_title');
    }

    public function getFormSubtitleProperty(): string
    {
        return $this->formCategoryId
            ? __('categories.form.edit_subtitle', ['name' => $this->formName ?: __('admin.categories.form.this_category')])
            : __('admin.categories.form.subtitle');
    }

    public function with(): array
    {
        $categories = Category::query()
            ->withCount('posts')
            ->orderBy('name')
            ->paginate($this->perPage);

        return [
            'categories' => $categories,
        ];
    }

    protected function resetForm(): void
    {
        $this->reset([
            'formCategoryId',
            'formName',
            'formSlug',
            'formDescription',
            'formSlugManuallyEdited',
        ]);

        $this->resetErrorBag();
        $this->resetValidation();
    }

    private function isInlineField(string $field): bool
    {
        return in_array($field, ['name', 'slug'], true);
    }

    private function isInlineProperty(string $property): bool
    {
        if (! $this->editingId || ! str_starts_with($property, 'editingValues.')) {
            return false;
        }

        $field = Str::after($property, 'editingValues.');

        return $this->isInlineField($field);
    }

}; ?>

<div class="space-y-6">
    <flux:page-header
        :heading="__('admin.categories.heading')"
        :description="__('admin.categories.description')"
    >
        <flux:button color="primary" type="button" wire:click="startCreateForm">
            {{ $formOpen ? __('admin.categories.form.create_another') : __('admin.categories.create_button') }}
        </flux:button>
    </flux:page-header>

    @if ($statusMessage)
        <flux:callout color="{{ $statusLevel === 'error' ? 'red' : 'green' }}">
            {{ $statusMessage }}
        </flux:callout>
    @endif

    <div class="grid gap-6 lg:grid-cols-[minmax(340px,380px)_1fr]">
        <flux:card class="space-y-6">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <flux:heading size="md">{{ $this->formTitle }}</flux:heading>
                    <flux:text class="text-sm text-slate-500 dark:text-slate-400">
                        {{ $this->formSubtitle }}
                    </flux:text>
                </div>
                @if ($formCategoryId)
                    <flux:badge color="indigo" size="sm">
                        {{ __('admin.categories.form.editing_badge') }}
                    </flux:badge>
                @elseif ($formOpen)
                    <flux:badge size="sm">
                        {{ __('admin.categories.form.creating_badge') }}
                    </flux:badge>
                @endif
            </div>

            @if ($formOpen)
                <form wire:submit.prevent="saveCategory" class="space-y-4">
                    <div class="space-y-2">
                        <x-label for="form-name" :value="__('categories.form.name_label')" />
                        <x-input
                            id="form-name"
                            type="text"
                            maxlength="255"
                            required
                            autofocus
                            placeholder="{{ __('categories.form.name_placeholder') }}"
                            wire:model.live.debounce.300ms="formName"
                        />
                        @error('formName') <p class="text-sm text-rose-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <x-label for="form-slug" :value="__('categories.form.slug_label')" />
                        <x-input
                            id="form-slug"
                            type="text"
                            maxlength="255"
                            required
                            placeholder="{{ __('categories.form.slug_placeholder') }}"
                            wire:model.live.debounce.300ms="formSlug"
                        />
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            {{ $formSlugManuallyEdited ? __('admin.categories.form.slug_locked') : __('categories.form.slug_help') }}
                        </p>
                        @error('formSlug') <p class="text-sm text-rose-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <x-label for="form-description" :value="__('categories.form.description_label')" />
                        <x-textarea
                            id="form-description"
                            rows="3"
                            placeholder="{{ __('categories.form.description_placeholder') }}"
                            wire:model.live="formDescription"
                        ></x-textarea>
                        @error('formDescription') <p class="text-sm text-rose-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <x-ui.button variant="ghost" type="button" wire:click="cancelForm">
                            {{ __('admin.categories.form.cancel_edit') }}
                        </x-ui.button>

                        <flux:button type="submit" color="primary" wire:loading.attr="disabled">
                            {{ $formCategoryId ? __('categories.form.update') : __('categories.form.create') }}
                        </flux:button>
                    </div>
                </form>
            @else
                <div class="flex items-center justify-between gap-4">
                    <flux:text class="text-sm text-slate-500 dark:text-slate-400">
                        {{ __('admin.categories.form.closed_hint') }}
                    </flux:text>
                    <flux:button color="primary" type="button" wire:click="startCreateForm">
                        {{ __('admin.categories.create_button') }}
                    </flux:button>
                </div>
            @endif
        </flux:card>

        <x-admin.table
            :pagination="$categories"
            per-page-mode="livewire"
            per-page-field="perPage"
            :per-page-options="$this->perPageOptions"
            :per-page-value="$perPage"
        >
            <x-slot name="head">
                <x-admin.table-head :columns="[
                    ['label' => __('admin.categories.table.name')],
                    ['label' => __('admin.categories.table.posts')],
                    ['label' => __('admin.categories.table.updated')],
                    ['label' => __('admin.categories.table.actions'), 'class' => 'text-right'],
                ]" />
            </x-slot>

            @forelse ($categories as $category)
                @php
                    $isEditingName = $editingId === $category->id && $editingField === 'name';
                    $isEditingSlug = $editingId === $category->id && $editingField === 'slug';
                @endphp
                <x-admin.table-row wire:key="category-{{ $category->id }}">
                    <td class="px-4 py-4">
                        <div class="flex flex-col gap-2">
                            <div class="flex flex-col gap-2">
                                @if ($isEditingName)
                                    <div class="space-y-2">
                                        <input
                                            type="text"
                                            wire:model.live="editingValues.name"
                                            wire:keydown.enter.prevent="saveInlineEdit"
                                            wire:keydown.escape.prevent="cancelInlineEdit"
                                            class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100"
                                            aria-label="{{ __('admin.categories.table.name') }}"
                                            autofocus
                                        />
                                        @error('editingValues.name')
                                            <p class="text-xs text-rose-500">{{ $message }}</p>
                                        @enderror
                                        <div class="flex items-center gap-2">
                                            <flux:button
                                                size="sm"
                                                variant="primary"
                                                color="green"
                                                wire:click="saveInlineEdit"
                                                wire:target="saveInlineEdit"
                                                wire:loading.attr="disabled"
                                            >
                                                {{ __('admin.inline.save') }}
                                            </flux:button>
                                            <flux:button
                                                size="sm"
                                                variant="ghost"
                                                wire:click="cancelInlineEdit"
                                                wire:target="saveInlineEdit"
                                                wire:loading.attr="disabled"
                                            >
                                                {{ __('admin.inline.cancel') }}
                                            </flux:button>
                                        </div>
                                    </div>
                                @else
                                    <button
                                        type="button"
                                        wire:click="startEditing({{ $category->id }}, 'name')"
                                        class="group inline-flex w-full items-center justify-between gap-2 rounded-lg px-1 py-0.5 text-left transition hover:bg-slate-50/70 hover:text-indigo-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 dark:hover:bg-slate-900/60"
                                        aria-label="{{ __('admin.inline.edit_field', ['field' => __('admin.categories.table.name')]) }}"
                                    >
                                        <span class="font-semibold">{{ $category->name }}</span>
                                        <span class="text-xs font-medium text-indigo-500 opacity-0 transition group-hover:opacity-100">
                                            {{ __('admin.inline.edit_label') }}
                                        </span>
                                    </button>
                                @endif
                            </div>
                            <div class="flex flex-col gap-2 text-xs text-slate-500 dark:text-slate-400">
                                @if ($isEditingSlug)
                                    <div class="space-y-2">
                                        <input
                                            type="text"
                                            wire:model.live="editingValues.slug"
                                            wire:keydown.enter.prevent="saveInlineEdit"
                                            wire:keydown.escape.prevent="cancelInlineEdit"
                                            class="w-full rounded-lg border border-dashed border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200"
                                            aria-label="{{ __('admin.categories.slug_label', ['slug' => $category->slug]) }}"
                                        />
                                        @error('editingValues.slug')
                                            <p class="text-xs text-rose-500">{{ $message }}</p>
                                        @enderror
                                        <div class="flex items-center gap-2">
                                            <flux:button
                                                size="xs"
                                                variant="primary"
                                                color="indigo"
                                                wire:click="saveInlineEdit"
                                                wire:target="saveInlineEdit"
                                                wire:loading.attr="disabled"
                                            >
                                                {{ __('admin.inline.save') }}
                                            </flux:button>
                                            <flux:button
                                                size="xs"
                                                variant="ghost"
                                                wire:click="cancelInlineEdit"
                                                wire:target="saveInlineEdit"
                                                wire:loading.attr="disabled"
                                            >
                                                {{ __('admin.inline.cancel') }}
                                            </flux:button>
                                        </div>
                                    </div>
                                @else
                                    <button
                                        type="button"
                                        wire:click="startEditing({{ $category->id }}, 'slug')"
                                        class="group inline-flex items-center gap-2 rounded-md px-1 py-0.5 text-xs font-medium text-slate-500 transition hover:bg-slate-50/70 hover:text-indigo-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 dark:hover:bg-slate-900/60"
                                        aria-label="{{ __('admin.inline.edit_field', ['field' => __('admin.categories.slug_label', ['slug' => $category->slug])]) }}"
                                    >
                                        <span>{{ __('admin.categories.slug_label', ['slug' => $category->slug]) }}</span>
                                        <span class="rounded-full border border-slate-200 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-400 transition group-hover:border-indigo-200 group-hover:text-indigo-500 dark:border-slate-700 dark:text-slate-500">
                                            {{ __('admin.inline.edit_label') }}
                                        </span>
                                    </button>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-4">
                        <flux:badge color="{{ $category->posts_count ? 'amber' : 'indigo' }}">
                            {{ trans_choice('admin.categories.posts_count', $category->posts_count, ['count' => $category->posts_count]) }}
                        </flux:badge>
                    </td>
                    <td class="px-4 py-4">
                        {{ $category->updated_at?->diffForHumans() ?? '—' }}
                    </td>
                    <td class="px-4 py-4 text-right">
                        <div class="inline-flex items-center gap-2">
                            <flux:button
                                type="button"
                                size="sm"
                                color="primary"
                                wire:click="startEditForm({{ $category->id }})"
                            >
                                {{ __('admin.categories.action_edit') }}
                            </flux:button>

                            <flux:button
                                type="button"
                                size="sm"
                                color="red"
                                icon="trash"
                                wire:click="deleteCategory({{ $category->id }})"
                                wire:confirm="{{ __('admin.categories.confirm_delete') }}"
                            >
                                {{ __('admin.categories.action_delete') }}
                            </flux:button>
                        </div>
                    </td>
                </x-admin.table-row>
            @empty
                <x-admin.table-empty colspan="4" :message="__('admin.categories.empty')" />
            @endforelse
        </x-admin.table>
    </div>
</div>
