<?php

use App\Livewire\Concerns\ManagesPerPage;
use App\Models\Category;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use function Livewire\Volt\layout;
use function Livewire\Volt\title;

layout('components.layouts.admin');
title(__('admin.categories.title'));

new class extends Component {
    use ManagesPerPage;
    use WithPagination;

    protected $queryString = [
        'perPage' => ['except' => 20],
    ];

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

    protected function availablePerPageOptions(): array
    {
        return [10, 20, 50, 100];
    }

    protected function defaultPerPage(): int
    {
        return 20;
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
        :summary="trans('pagination.summary', [
            'from' => $categories->firstItem(),
            'to' => $categories->lastItem(),
            'total' => $categories->total(),
        ])"
    >
        <x-slot name="head">
            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                <th class="px-4 py-3">{{ __('admin.categories.table.name') }}</th>
                <th class="px-4 py-3">{{ __('admin.categories.table.posts') }}</th>
                <th class="px-4 py-3">{{ __('admin.categories.table.updated') }}</th>
                <th class="px-4 py-3 text-right">{{ __('admin.categories.table.actions') }}</th>
            </tr>
        </x-slot>

        @forelse ($categories as $category)
            <x-admin.table-row>
                <td class="px-4 py-4">
                    <div class="flex flex-col">
                        <span class="font-semibold">{{ $category->name }}</span>
                        <span class="text-xs text-slate-500 dark:text-slate-400">
                            {{ __('admin.categories.slug_label', ['slug' => $category->slug]) }}
                        </span>
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

                        <form
                            action="{{ route('categories.destroy', $category) }}"
                            method="POST"
                            onsubmit="return confirm('{{ __('admin.categories.confirm_delete') }}');"
                        >
                            @csrf
                            @method('DELETE')
                            <flux:button type="submit" size="sm" color="red" icon="trash">
                                {{ __('admin.categories.action_delete') }}
                            </flux:button>
                        </form>
                    </div>
                </td>
            </x-admin.table-row>
        @empty
            <x-admin.table-empty colspan="4" :message="__('admin.categories.empty')" />
        @endforelse
    </x-admin.table>
</div>

