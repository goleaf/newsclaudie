@props([
    'categories',
    'authors',
    'appliedFilters',
])

@php
    $hasFilters = !empty($appliedFilters['categories']) 
        || !empty($appliedFilters['authors']) 
        || !empty($appliedFilters['from_date']) 
        || !empty($appliedFilters['to_date']);
@endphp

<div x-data="{ mobileOpen: false }" class="space-y-6">
    <!-- Mobile Filter Toggle -->
    <div class="lg:hidden">
        <button
            @click="mobileOpen = !mobileOpen"
            type="button"
            class="flex w-full items-center justify-between rounded-2xl border border-slate-200 bg-white/80 px-4 py-3 text-sm font-semibold text-slate-900 shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900/70 dark:text-white dark:hover:bg-slate-800"
        >
            <span>{{ __('Filters') }}</span>
            <svg class="h-5 w-5 transition" :class="{ 'rotate-180': mobileOpen }" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>
    </div>

    <!-- Filter Form -->
    <form
        method="GET"
        action="{{ route('news.index') }}"
        x-show="mobileOpen"
        x-transition
        class="space-y-6 lg:!block"
    >
        <x-ui.card padding="p-6">
            <div class="space-y-6">
                <!-- Clear Filters Button -->
                @if ($hasFilters)
                    <div class="flex justify-between items-center pb-4 border-b border-slate-200 dark:border-slate-700">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">
                            {{ __('Active Filters') }}
                        </h3>
                        <a
                            href="{{ route('news.index') }}"
                            class="text-xs font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300"
                        >
                            {{ __('Clear All') }}
                        </a>
                    </div>
                @endif

                <!-- Sort Order -->
                <div>
                    <label class="block text-sm font-semibold text-slate-900 dark:text-white mb-3">
                        {{ __('Sort By') }}
                    </label>
                    <select
                        name="sort"
                        onchange="this.form.submit()"
                        class="block w-full rounded-2xl border border-slate-200 bg-white/80 px-4 py-2 text-sm text-slate-900 shadow-sm transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 dark:border-slate-700 dark:bg-slate-900/70 dark:text-white dark:focus:border-indigo-400 dark:focus:ring-indigo-400/40"
                    >
                        <option value="newest" {{ ($appliedFilters['sort'] ?? 'newest') === 'newest' ? 'selected' : '' }}>
                            {{ __('Newest First') }}
                        </option>
                        <option value="oldest" {{ ($appliedFilters['sort'] ?? 'newest') === 'oldest' ? 'selected' : '' }}>
                            {{ __('Oldest First') }}
                        </option>
                    </select>
                </div>

                <!-- Categories Filter -->
                @if ($categories->isNotEmpty())
                    <div>
                        <label class="block text-sm font-semibold text-slate-900 dark:text-white mb-3">
                            {{ __('Categories') }}
                        </label>
                        <div class="space-y-2 max-h-48 overflow-y-auto">
                            @foreach ($categories as $category)
                                <label class="flex items-center gap-2 cursor-pointer group">
                                    <input
                                        type="checkbox"
                                        name="categories[]"
                                        value="{{ $category->id }}"
                                        {{ in_array($category->id, $appliedFilters['categories'] ?? []) ? 'checked' : '' }}
                                        onchange="this.form.submit()"
                                        class="h-4 w-4 rounded border-slate-300 text-indigo-600 transition focus:ring-2 focus:ring-indigo-500 focus:ring-offset-0 dark:border-slate-600 dark:bg-slate-800 dark:focus:ring-indigo-400"
                                    >
                                    <span class="text-sm text-slate-700 group-hover:text-slate-900 dark:text-slate-300 dark:group-hover:text-white">
                                        {{ $category->name }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Authors Filter -->
                @if ($authors->isNotEmpty())
                    <div>
                        <label class="block text-sm font-semibold text-slate-900 dark:text-white mb-3">
                            {{ __('Authors') }}
                        </label>
                        <div class="space-y-2 max-h-48 overflow-y-auto">
                            @foreach ($authors as $author)
                                <label class="flex items-center gap-2 cursor-pointer group">
                                    <input
                                        type="checkbox"
                                        name="authors[]"
                                        value="{{ $author->id }}"
                                        {{ in_array($author->id, $appliedFilters['authors'] ?? []) ? 'checked' : '' }}
                                        onchange="this.form.submit()"
                                        class="h-4 w-4 rounded border-slate-300 text-indigo-600 transition focus:ring-2 focus:ring-indigo-500 focus:ring-offset-0 dark:border-slate-600 dark:bg-slate-800 dark:focus:ring-indigo-400"
                                    >
                                    <span class="text-sm text-slate-700 group-hover:text-slate-900 dark:text-slate-300 dark:group-hover:text-white">
                                        {{ $author->name }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Date Range Filter -->
                <div>
                    <label class="block text-sm font-semibold text-slate-900 dark:text-white mb-3">
                        {{ __('Date Range') }}
                    </label>
                    <div class="space-y-3">
                        <div>
                            <label for="from_date" class="block text-xs text-slate-600 dark:text-slate-400 mb-1">
                                {{ __('From Date') }}
                            </label>
                            <input
                                type="date"
                                id="from_date"
                                name="from_date"
                                value="{{ $appliedFilters['from_date'] ?? '' }}"
                                onchange="this.form.submit()"
                                class="block w-full rounded-2xl border border-slate-200 bg-white/80 px-4 py-2 text-sm text-slate-900 shadow-sm transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 dark:border-slate-700 dark:bg-slate-900/70 dark:text-white dark:focus:border-indigo-400 dark:focus:ring-indigo-400/40"
                            >
                        </div>
                        <div>
                            <label for="to_date" class="block text-xs text-slate-600 dark:text-slate-400 mb-1">
                                {{ __('To Date') }}
                            </label>
                            <input
                                type="date"
                                id="to_date"
                                name="to_date"
                                value="{{ $appliedFilters['to_date'] ?? '' }}"
                                onchange="this.form.submit()"
                                class="block w-full rounded-2xl border border-slate-200 bg-white/80 px-4 py-2 text-sm text-slate-900 shadow-sm transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 dark:border-slate-700 dark:bg-slate-900/70 dark:text-white dark:focus:border-indigo-400 dark:focus:ring-indigo-400/40"
                            >
                        </div>
                    </div>
                </div>
            </div>
        </x-ui.card>
    </form>
</div>
