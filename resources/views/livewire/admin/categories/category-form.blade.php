<?php

use App\Models\Category;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component {
    use AuthorizesRequests;

    public ?int $categoryId = null;
    public string $name = '';
    public string $slug = '';
    public ?string $description = null;
    public bool $slugManuallyEdited = false;
    public bool $isEditing = false;

    protected $listeners = [
        'open-category-form' => 'open',
    ];

    public function mount(): void
    {
        $this->authorize('access-admin');
    }

    public function open($categoryId = null): void
    {
        $this->authorize('access-admin');

        $this->resetForm();

        if (is_array($categoryId)) {
            $categoryId = $categoryId['categoryId'] ?? $categoryId['id'] ?? null;
        }

        $categoryId = $categoryId ? (int) $categoryId : null;

        if ($categoryId) {
            $category = Category::query()->findOrFail($categoryId);

            $this->categoryId = $category->id;
            $this->name = $category->name;
            $this->slug = $category->slug;
            $this->description = $category->description;
            $this->slugManuallyEdited = true;
            $this->isEditing = true;
        }

        $this->dispatch('modal-show', name: 'category-form', scope: $this->getId());
    }

    public function close(): void
    {
        $this->dispatch('modal-close', name: 'category-form', scope: $this->getId());
        $this->resetForm();
    }

    public function save(): void
    {
        $this->authorize('access-admin');

        $validated = $this->validate();

        $payload = [
            'name' => trim($validated['name']),
            'slug' => Str::slug($validated['slug']),
            'description' => $validated['description'] ?? null,
        ];

        if ($this->isEditing && $this->categoryId) {
            $category = Category::query()->findOrFail($this->categoryId);
            $category->update($payload);
            $message = __('messages.category_updated');
        } else {
            $category = Category::create($payload);
            $this->categoryId = $category->id;
            $message = __('messages.category_created');
        }

        session()->flash('success', $message);

        $this->dispatch('category-saved', categoryId: $category->id);
        $this->dispatch('modal-close', name: 'category-form', scope: $this->getId());

        $this->resetForm();
    }

    public function updatedName(string $value): void
    {
        $this->name = trim($value);

        if (! $this->slugManuallyEdited) {
            $this->slug = Str::slug($value);
            $this->validateOnly('slug');
        }

        $this->validateOnly('name');
    }

    public function updatedSlug(string $value): void
    {
        $trimmed = trim($value);

        $this->slugManuallyEdited = $trimmed !== '';
        $this->slug = $trimmed === '' ? '' : Str::slug($trimmed);

        $this->validateOnly('slug');
    }

    public function updatedDescription($value): void
    {
        $this->description = $value;

        $this->validateOnly('description');
    }

    protected function rules(): array
    {
        $uniqueRule = Rule::unique('categories', 'slug');

        if ($this->categoryId) {
            $uniqueRule = $uniqueRule->ignore($this->categoryId);
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $uniqueRule],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function messages(): array
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

    public function resetForm(): void
    {
        $this->reset([
            'categoryId',
            'name',
            'slug',
            'description',
            'slugManuallyEdited',
            'isEditing',
        ]);

        $this->resetErrorBag();
        $this->resetValidation();
    }
}; ?>

<flux:modal name="category-form" class="max-w-xl" @close="$wire.resetForm()">
    <div class="space-y-6 p-6">
        <div class="flex items-start justify-between gap-3">
            <div class="space-y-1">
                <flux:heading size="lg">
                    {{ $isEditing ? __('categories.form.edit_title') : __('categories.form.create_title') }}
                </flux:heading>
                <flux:description>
                    {{ $isEditing
                        ? __('categories.form.edit_subtitle', ['name' => $name ?: __('admin.categories.form.this_category')])
                        : __('categories.form.subtitle') }}
                </flux:description>
            </div>
            <flux:badge>
                {{ $isEditing ? __('admin.categories.form.editing_badge') : __('admin.categories.form.creating_badge') }}
            </flux:badge>
        </div>

        <form class="space-y-4" wire:submit.prevent="save">
            <div class="space-y-2">
                <flux:input
                    wire:model.live.debounce.300ms="name"
                    label="{{ __('categories.form.name_label') }}"
                    placeholder="{{ __('categories.form.name_placeholder') }}"
                    required
                    autofocus
                />
                <flux:error name="name" />
            </div>

            <div class="space-y-2">
                <flux:input
                    wire:model.live.debounce.300ms="slug"
                    label="{{ __('categories.form.slug_label') }}"
                    placeholder="{{ __('categories.form.slug_placeholder') }}"
                    required
                    description="{{ $slugManuallyEdited ? __('admin.categories.form.slug_locked') : __('categories.form.slug_help') }}"
                />
                <flux:error name="slug" />
                @if (!$errors->has('slug'))
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        {{ __('admin.categories.form.slug_format_hint') }}
                    </p>
                @endif
            </div>

            <div class="space-y-2">
                <flux:textarea
                    wire:model.live.debounce.400ms="description"
                    rows="3"
                    label="{{ __('categories.form.description_label') }}"
                    placeholder="{{ __('categories.form.description_placeholder') }}"
                />
                <flux:error name="description" />
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <flux:button type="button" variant="ghost" wire:click="close">
                    {{ __('categories.form.cancel') }}
                </flux:button>
                <flux:button type="submit" color="primary" wire:target="save" wire:loading.attr="disabled">
                    {{ $isEditing ? __('categories.form.update') : __('categories.form.create') }}
                </flux:button>
            </div>
        </form>
    </div>
</flux:modal>
