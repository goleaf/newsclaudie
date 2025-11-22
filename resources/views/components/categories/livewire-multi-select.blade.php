@props([
    'categories' => collect(),
    'wireModel',
])

@php
    $optionData = collect($categories)
        ->map(fn ($category) => [
            'id' => (int) $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
        ])
        ->values();
@endphp

<div
    x-data="(function () {
        const normalize = (items) => {
            return Array.from(items || [])
                .map((id) => Number.parseInt(id, 10))
                .filter((id) => Number.isFinite(id));
        };

        return {
            open: false,
            search: '',
            options: @js($optionData->all()),
            selected: @entangle($wireModel).defer,
            normalizeSelected() {
                const normalized = normalize(this.selected);

                // Avoid triggering Livewire updates when nothing changed
                if (JSON.stringify(normalized) !== JSON.stringify(this.selected)) {
                    this.selected = normalized;
                }
            },
            toggle(id) {
                const numericId = Number.parseInt(id, 10);
                if (! Number.isFinite(numericId)) {
                    return;
                }

                const current = new Set(normalize(this.selected));

                if (current.has(numericId)) {
                    current.delete(numericId);
                } else {
                    current.add(numericId);
                }

                this.selected = Array.from(current.values());
            },
            isSelected(id) {
                const numericId = Number.parseInt(id, 10);

                return Number.isFinite(numericId) && normalize(this.selected).includes(numericId);
            },
            filteredOptions() {
                const term = this.search.toLowerCase().trim();

                if (! term) {
                    return this.options;
                }

                return this.options.filter((option) =>
                    option.name.toLowerCase().includes(term) ||
                    (option.slug && option.slug.toLowerCase().includes(term))
                );
            },
            selectedOptions() {
                const selectedIds = normalize(this.selected);

                return this.options.filter((option) => selectedIds.includes(option.id));
            },
        };
    })()"
    x-init="normalizeSelected()"
    x-on:click.outside="open = false"
    x-on:keydown.escape.window="open = false"
    class="space-y-3"
    x-cloak
>
    <div class="flex items-center justify-between">
        <x-label :value="__('posts.form.categories_label')" />
        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400" x-text="selected.length ? `${selected.length} {{ __('posts.form.categories_selected_suffix') }}` : '{{ __('posts.form.categories_helper') }}'"></span>
    </div>

    <div class="rounded-2xl border border-slate-200/80 bg-white/70 shadow-sm transition focus-within:ring-2 focus-within:ring-indigo-500 dark:border-slate-800/70 dark:bg-slate-900/70">
        <button
            type="button"
            class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left text-sm text-slate-700 dark:text-slate-200"
            x-on:click="open = !open"
            :aria-expanded="open.toString()"
        >
            <span class="truncate" x-text="selectedOptions().length ? selectedOptions().map(option => option.name).join(', ') : '{{ __('posts.form.categories_placeholder') }}'"></span>
            <svg class="h-5 w-5 text-slate-400 transition" :class="{ 'rotate-180': open }" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.939l3.71-3.71a.75.75 0 111.08 1.04l-4.24 4.25a.75.75 0 01-1.08 0L5.25 8.27a.75.75 0 01-.02-1.06z" clip-rule="evenodd" />
            </svg>
        </button>

        <div
            x-show="open"
            x-transition.origin.top.left
            class="border-t border-slate-200 bg-white/90 p-4 dark:border-slate-800 dark:bg-slate-900/95"
        >
            <div class="space-y-3">
                <div class="relative">
                    <input
                        type="text"
                        x-model="search"
                        placeholder="{{ __('posts.form.categories_search_placeholder') }}"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                    />
                    <span class="pointer-events-none absolute right-3 top-2.5 text-slate-400">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 104.387 2.253l3.43-3.43a.75.75 0 111.06 1.06l-3.43 3.43A5.5 5.5 0 109 3.5zm0 1.5a4 4 0 100 8 4 4 0 000-8z" clip-rule="evenodd" />
                        </svg>
                    </span>
                </div>

                <div class="max-h-60 space-y-2 overflow-y-auto pr-2">
                    <template x-if="filteredOptions().length === 0">
                        <p class="px-1 text-sm text-slate-500 dark:text-slate-400">{{ __('posts.form.categories_empty') }}</p>
                    </template>

                    <template x-for="option in filteredOptions()" :key="option.id">
                        <label class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm transition hover:bg-slate-50 dark:hover:bg-slate-800/70">
                            <input
                                type="checkbox"
                                class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                :value="option.id"
                                :checked="isSelected(option.id)"
                                @change="toggle(option.id)"
                            >
                            <div class="flex flex-col">
                                <span class="font-medium text-slate-900 dark:text-white" x-text="option.name"></span>
                                <span class="text-xs text-slate-500 dark:text-slate-400" x-text="option.slug"></span>
                            </div>
                        </label>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <div class="flex flex-wrap gap-2">
        <template x-if="selectedOptions().length === 0">
            <span class="text-sm text-slate-500 dark:text-slate-400">{{ __('posts.form.categories_hint') }}</span>
        </template>

        <template x-for="option in selectedOptions()" :key="'badge-' + option.id">
            <span class="inline-flex items-center gap-2 rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700 shadow-sm dark:bg-indigo-900/50 dark:text-indigo-100">
                <span x-text="option.name"></span>
                <button
                    type="button"
                    class="text-slate-500 transition hover:text-rose-500 dark:text-slate-400"
                    @click="toggle(option.id)"
                    :aria-label="`{{ __('posts.form.remove_category') }} ${option.name}`"
                >
                    &times;
                </button>
            </span>
        </template>
    </div>
</div>
