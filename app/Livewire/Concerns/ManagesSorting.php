<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;

/**
 * Shared helpers for Livewire components that support sortable columns.
 *
 * Provides sortable column logic with direction toggle and query string persistence.
 */
trait ManagesSorting
{
    /**
     * The field to sort by.
     */
    #[Url(except: null, as: 'sort')]
    public ?string $sortField = null;

    /**
     * The sort direction (asc or desc).
     */
    #[Url(except: 'asc', as: 'direction')]
    public string $sortDirection = 'asc';

    /**
     * Sort by a specific field.
     * Toggles direction if already sorting by this field.
     */
    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            // Toggle direction if already sorting by this field
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            // New field, default to ascending
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    /**
     * Clear the sort state.
     */
    public function clearSort(): void
    {
        $this->sortField = null;
        $this->sortDirection = 'asc';
    }

    /**
     * Apply sorting to a query builder.
     *
     * @param  Builder  $query  The query builder to apply sorting to
     * @param  array<string>  $sortableFields  Optional list of allowed sortable fields for validation
     * @return Builder  The modified query builder
     */
    protected function applySorting(Builder $query, array $sortableFields = []): Builder
    {
        if (empty($this->sortField)) {
            return $query;
        }

        // Validate field if sortable fields are provided
        if (!empty($sortableFields) && !in_array($this->sortField, $sortableFields, true)) {
            return $query;
        }

        $direction = $this->normalizeSortDirection($this->sortDirection);

        return $query->orderBy($this->sortField, $direction);
    }

    /**
     * Check if currently sorting by a specific field.
     */
    public function isSortedBy(string $field): bool
    {
        return $this->sortField === $field;
    }

    /**
     * Get the current sort direction for a field.
     * Returns null if not sorting by this field.
     */
    public function getSortDirection(string $field): ?string
    {
        return $this->isSortedBy($field) ? $this->sortDirection : null;
    }

    /**
     * Normalize sort direction to ensure it's either 'asc' or 'desc'.
     */
    protected function normalizeSortDirection(string $direction): string
    {
        $normalized = strtolower(trim($direction));

        return in_array($normalized, ['asc', 'desc'], true) ? $normalized : 'asc';
    }
}
