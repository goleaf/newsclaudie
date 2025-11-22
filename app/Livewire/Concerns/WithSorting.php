<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

/**
 * Normalizes sort field/direction handling for Livewire data tables.
 */
trait WithSorting
{
    public function updatingSortField(): void
    {
        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }

    public function updatingSortDirection(): void
    {
        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }

    public function updatedSortField($value): void
    {
        $this->sortField = $this->sanitizeSortField($value);
    }

    public function updatedSortDirection($value): void
    {
        $this->sortDirection = $this->sanitizeSortDirection($value);
    }

    public function sortBy(string $field): void
    {
        $field = $this->sanitizeSortField($field);

        if ($field === '') {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = $this->defaultSortDirectionFor($field);
        }

        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }

    /**
     * Resolve and persist the current sort tuple.
     *
     * @return array{0: string, 1: string}
     */
    protected function resolveSort(): array
    {
        $field = $this->sanitizeSortField($this->sortField ?? $this->defaultSortField());
        $direction = $this->sanitizeSortDirection($this->sortDirection ?? $this->defaultSortDirection());

        $this->sortField = $field;
        $this->sortDirection = $direction;

        return [$field, $direction];
    }

    /**
     * Override to expose allowed sort columns.
     *
     * @return array<string>
     */
    protected function sortableColumns(): array
    {
        return [];
    }

    protected function defaultSortField(): string
    {
        return $this->sortableColumns()[0] ?? '';
    }

    protected function defaultSortDirection(): string
    {
        return 'asc';
    }

    protected function defaultSortDirectionFor(string $field): string
    {
        return $this->defaultSortDirection();
    }

    protected function sanitizeSortField(?string $field): string
    {
        $field = is_string($field) ? trim($field) : '';

        return in_array($field, $this->sortableColumns(), true)
            ? $field
            : $this->defaultSortField();
    }

    protected function sanitizeSortDirection(?string $direction): string
    {
        $direction = strtolower(is_string($direction) ? $direction : '');

        return in_array($direction, ['asc', 'desc'], true)
            ? $direction
            : $this->defaultSortDirection();
    }
}
