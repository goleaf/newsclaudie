<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use Illuminate\Support\Collection;

/**
 * Shared helpers for Livewire components that expose a configurable $perPage value.
 *
 * Intended for use alongside Livewire's WithPagination trait.
 */
trait ManagesPerPage
{
    public int $perPage = 20;

    public function bootManagesPerPage(): void
    {
        $this->perPage = $this->sanitizePerPage($this->perPage ?? $this->defaultPerPage());
    }

    public function updatingPerPage(): void
    {
        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }

    /**
     * Ensure any manual assignments or query-string driven updates stay within the allowed range.
     */
    public function updatedPerPage($value): void
    {
        $this->perPage = $this->sanitizePerPage((int) $value);
    }

    /**
     * Expose the allowed per-page options to Blade via $this->perPageOptions.
     *
     * @return array<int>
     */
    public function getPerPageOptionsProperty(): array
    {
        return $this->availablePerPageOptions();
    }

    /**
     * Override this in the component when a different default is required.
     */
    protected function defaultPerPage(): int
    {
        return $this->availablePerPageOptions()[0] ?? 20;
    }

    /**
     * Override this in the component to customize the selectable options.
     *
     * @return array<int>
     */
    protected function availablePerPageOptions(): array
    {
        return [10, 20, 50];
    }

    protected function sanitizePerPage(int $value): int
    {
        /** @var Collection<int, int> $options */
        $options = collect($this->availablePerPageOptions())
            ->map(fn ($option) => (int) $option)
            ->filter(fn ($option) => $option > 0)
            ->unique()
            ->values();

        if ($options->isEmpty()) {
            return 10;
        }

        return $options->contains($value) ? $value : $options->first();
    }
}

