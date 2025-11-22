<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;

/**
 * Shared helpers for Livewire components that support search functionality.
 *
 * Provides search with debouncing, query string persistence, and query builder integration.
 */
trait ManagesSearch
{
    /**
     * The current search term.
     */
    #[Url(except: '', as: 'search')]
    public ?string $search = null;

    /**
     * Called when search is being updated (before the value changes).
     * Resets pagination to page 1 when search changes.
     */
    public function updatingSearch(): void
    {
        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }

    /**
     * Called after search is updated.
     * Normalizes the search term by trimming whitespace.
     */
    public function updatedSearch(?string $value): void
    {
        $this->search = $this->normalizeSearch($value);
    }

    /**
     * Clear the search term and reset to default view.
     */
    public function clearSearch(): void
    {
        $this->search = null;
        
        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }

    /**
     * Apply search filtering to a query builder.
     *
     * @param  Builder  $query  The query builder to apply search to
     * @param  array<string>  $searchableFields  The fields to search in
     * @return Builder  The modified query builder
     */
    protected function applySearch(Builder $query, array $searchableFields): Builder
    {
        $searchTerm = $this->getSearchTerm();

        if (empty($searchTerm) || empty($searchableFields)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($searchTerm, $searchableFields) {
            foreach ($searchableFields as $field) {
                $q->orWhere($field, 'LIKE', "%{$searchTerm}%");
            }
        });
    }

    /**
     * Get the normalized search term.
     */
    protected function getSearchTerm(): string
    {
        return $this->normalizeSearch($this->search);
    }

    /**
     * Normalize a search value by trimming whitespace.
     */
    protected function normalizeSearch(?string $value): string
    {
        return trim((string) $value);
    }
}
