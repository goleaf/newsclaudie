<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use Livewire\Attributes\Url;

/**
 * Shared helpers for Livewire components that support bulk actions.
 *
 * Provides selection tracking, select all functionality, and query string persistence.
 */
trait ManagesBulkActions
{
    /**
     * Selected item identifiers.
     *
     * @var array<int>
     */
    #[Url(except: [], as: 'selected')]
    public array $selected = [];

    /**
     * Whether "select all" is active for the current page.
     */
    public bool $selectAll = false;

    /**
     * The IDs currently visible on the page.
     *
     * @var array<int>
     */
    public array $currentPageIds = [];

    /**
     * Called when selectAll is updated.
     */
    public function updatedSelectAll(bool $value): void
    {
        if ($value) {
            $this->selectCurrentPage();
        } else {
            $this->deselectCurrentPage();
        }
    }

    /**
     * Toggle selection for a specific item ID.
     */
    public function toggleSelection(int $id): void
    {
        $normalized = $this->normalizeSelection($this->selected);

        if (in_array($id, $normalized, true)) {
            $this->selected = array_values(array_filter($normalized, fn($item) => $item !== $id));
        } else {
            $this->selected = array_values(array_merge($normalized, [$id]));
        }

        $this->updateSelectAllState();
    }

    /**
     * Clear all selections.
     */
    public function clearSelection(): void
    {
        $this->selected = [];
        $this->selectAll = false;
    }

    /**
     * Get the count of selected items.
     */
    public function getSelectedCountProperty(): int
    {
        return count($this->normalizeSelection($this->selected));
    }

    /**
     * Set the current page IDs for select all functionality.
     *
     * @param  iterable<int>  $ids
     */
    public function setCurrentPageIds(iterable $ids): void
    {
        $this->currentPageIds = collect($ids)
            ->map(fn($id) => (int) $id)
            ->values()
            ->all();

        $this->updateSelectAllState();
    }

    /**
     * Get normalized selected IDs.
     *
     * @return array<int>
     */
    public function getSelectedIds(): array
    {
        return $this->normalizeSelection($this->selected);
    }

    /**
     * Select all items on the current page.
     */
    protected function selectCurrentPage(): void
    {
        $this->selected = collect($this->selected)
            ->merge($this->currentPageIds)
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $this->selectAll = true;
    }

    /**
     * Deselect all items on the current page.
     */
    protected function deselectCurrentPage(): void
    {
        $normalized = $this->normalizeSelection($this->selected);
        
        $this->selected = collect($normalized)
            ->reject(fn($id) => in_array($id, $this->currentPageIds, true))
            ->values()
            ->all();

        $this->selectAll = false;
    }

    /**
     * Update the selectAll state based on current selections.
     */
    protected function updateSelectAllState(): void
    {
        if (empty($this->currentPageIds)) {
            $this->selectAll = false;
            return;
        }

        $normalized = $this->normalizeSelection($this->selected);
        $selectedOnPage = collect($normalized)->intersect($this->currentPageIds);

        $this->selectAll = $selectedOnPage->count() === count($this->currentPageIds);
    }

    /**
     * Normalize selection array to ensure all values are integers.
     *
     * @param  array<int>  $ids
     * @return array<int>
     */
    protected function normalizeSelection(array $ids): array
    {
        return collect($ids)
            ->map(fn($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
