<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

/**
 * Shared helpers for Livewire tables that support multi-select and bulk actions.
 */
trait InteractsWithBulkSelection
{
    /**
     * Selected row identifiers across the table.
     *
     * @var array<int>
     */
    public array $selected = [];

    /**
     * Whether the current page is fully selected.
     */
    public bool $selectPage = false;

    /**
     * The IDs currently visible in the paginated result set.
     *
     * @var array<int>
     */
    public array $currentPageIds = [];

    public function updatedSelected(): void
    {
        $this->selected = $this->normalizeSelection($this->selected);
        $this->selectPage = $this->areAllCurrentPageItemsSelected();
    }

    public function updatedSelectPage(bool $checked): void
    {
        if ($checked) {
            $this->selectCurrentPage();
        } else {
            $this->deselectCurrentPage();
        }
    }

    public function clearSelection(): void
    {
        $this->selected = [];
        $this->selectPage = false;
    }

    /**
     * Normalize the current page IDs and recompute select-all state.
     *
     * @param  iterable<int>  $ids
     */
    protected function setCurrentPageItems(iterable $ids): void
    {
        $this->currentPageIds = collect($ids)
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $this->selectPage = $this->areAllCurrentPageItemsSelected();
    }

    /**
     * Return the current normalized selection.
     *
     * @return array<int>
     */
    protected function selectedIds(): array
    {
        return $this->normalizeSelection($this->selected);
    }

    protected function selectCurrentPage(): void
    {
        $this->selected = collect($this->selected)
            ->merge($this->currentPageIds)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $this->selectPage = $this->areAllCurrentPageItemsSelected();
    }

    protected function deselectCurrentPage(): void
    {
        $this->selected = collect($this->selected)
            ->reject(fn ($id) => in_array((int) $id, $this->currentPageIds, true))
            ->values()
            ->all();

        $this->selectPage = false;
    }

    protected function areAllCurrentPageItemsSelected(): bool
    {
        if (empty($this->currentPageIds)) {
            return false;
        }

        $selected = collect($this->selected)->map(fn ($id) => (int) $id)->unique();

        return $selected->intersect($this->currentPageIds)->count() === count($this->currentPageIds);
    }

    /**
     * @param  array<int>  $ids
     * @return array<int>
     */
    private function normalizeSelection(array $ids): array
    {
        return collect($ids)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
