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

    protected $queryString = [
        'perPage' => ['except' => PageSize::contextDefault('admin')],
    ];

    public function deleteCategory(int $categoryId): void
    {
        $this->authorize('access-admin');

        if ($this->editingId === $categoryId) {
            $this->cancelInlineEdit();
        }

        Category::findOrFail($categoryId)->delete();

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

    public function updated(string $property, mixed $value): void
    {
        if (! $this->editingId || ! str_starts_with($property, 'editingValues.')) {
            return;
        }

        $field = Str::after($property, 'editingValues.');

        if (! $this->isInlineField($field)) {
            return;
        }

        $this->validateOnly($property, $this->rules(), $this->messages());
    }

    public function saveInlineEdit(): void
    {
        if (! $this->editingId || ! $this->editingField || ! $this->isInlineField($this->editingField)) {
            return;
        }

        $property = "editingValues.{$this->editingField}";

        $this->validateOnly($property, $this->rules(), $this->messages());

        $category = Category::findOrFail($this->editingId);

        $this->authorize('access-admin');

        $category->update([
            $this->editingField => $this->editingValues[$this->editingField],
        ]);

        $this->cancelInlineEdit();
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

    protected function rules(): array
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

    protected function messages(): array
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

    private function isInlineField(string $field): bool
    {
        return in_array($field, ['name', 'slug'], true);
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

}; ?>

<div class="space-y-6">
    <flux:page-header
        :heading="__('admin.categories.heading')"
        :description="__('admin.categories.description')"
    >
        <flux:button color="primary" :href="route('categories.create')">
            {{ __('admin.categories.create_button') }}
        </flux:button>
    </flux:page-header>

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
                    <flux:badge color="indigo">
                        {{ trans_choice('admin.categories.posts_count', $category->posts_count, ['count' => $category->posts_count]) }}
                    </flux:badge>
                </td>
                <td class="px-4 py-4">
                    {{ $category->updated_at?->diffForHumans() ?? '—' }}
                </td>
                <td class="px-4 py-4 text-right">
                    <div class="inline-flex items-center gap-2">
                        <flux:link :href="route('categories.edit', $category)" size="sm">
                            {{ __('admin.categories.action_edit') }}
                        </flux:link>

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
