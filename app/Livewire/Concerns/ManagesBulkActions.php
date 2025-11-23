<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use Livewire\Attributes\Url;

/**
 * Shared helpers for Livewire components that support bulk actions.
 *
 * Provides selection tracking, select all functionality, and query string persistence
 * for admin CRUD tables (posts, categories, comments, users).
 * 
 * ## Features
 * 
 * - Individual item selection with toggle
 * - Select all items on current page
 * - URL-persisted selection state
 * - Normalized ID handling (integers, unique, filtered)
 * - Computed property for selection count
 * 
 * ## Usage
 * 
 * ```php
 * use App\Livewire\Concerns\ManagesBulkActions;
 * 
 * class PostsIndex extends Component
 * {
 *     use ManagesBulkActions;
 * 
 *     public function mount()
 *     {
 *         // Set current page IDs for select all
 *         $this->setCurrentPageIds($this->posts->pluck('id'));
 *     }
 * 
 *     public function bulkPublish()
 *     {
 *         $ids = $this->getSelectedIds();
 *         Post::whereIn('id', $ids)->update(['published_at' => now()]);
 *         $this->clearSelection();
 *     }
 * }
 * ```
 * 
 * ## Blade Template
 * 
 * ```blade
 * <input type="checkbox" wire:model.live="selectAll" />
 * 
 * @foreach($posts as $post)
 *     <input type="checkbox" wire:click="toggleSelection({{ $post->id }})" />
 * @endforeach
 * 
 * @if($this->selectedCount > 0)
 *     <div>{{ $this->selectedCount }} selected</div>
 *     <button wire:click="bulkPublish">Publish</button>
 * @endif
 * ```
 * 
 * ## Properties
 * 
 * - `$selected` - Array of selected item IDs (URL-persisted)
 * - `$selectAll` - Boolean for select all checkbox state
 * - `$currentPageIds` - IDs visible on current page
 * - `$selectedCount` - Computed property for selection count
 * 
 * @see tests/Unit/BulkSelectionDisplayPropertyTest.php
 * @see tests/Unit/BulkOperationSuccessPropertyTest.php
 * @see tests/Unit/BULK_ACTIONS_PROPERTY_TESTS_INDEX.md
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
     * 
     * Livewire lifecycle hook that responds to changes in the selectAll property.
     * Selects or deselects all items on the current page.
     * 
     * @param bool $value New value of selectAll
     * @return void
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
     * 
     * If the item is selected, it will be deselected. If deselected, it will be selected.
     * Updates the selectAll state after toggling.
     * 
     * @param int $id Item ID to toggle
     * @return void
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
     * 
     * Resets both the selected array and selectAll state.
     * Typically called after successful bulk operations.
     * 
     * @return void
     */
    public function clearSelection(): void
    {
        $this->selected = [];
        $this->selectAll = false;
    }

    /**
     * Get the count of selected items.
     * 
     * Computed property accessible as $this->selectedCount in Livewire components.
     * Returns the count of unique, normalized selected IDs.
     * 
     * @return int Number of selected items
     */
    public function getSelectedCountProperty(): int
    {
        return count($this->normalizeSelection($this->selected));
    }

    /**
     * Set the current page IDs for select all functionality.
     * 
     * Should be called in mount() or after pagination changes to keep
     * the selectAll checkbox state synchronized with visible items.
     *
     * @param iterable<int> $ids IDs of items on current page
     * @return void
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
     * Returns an array of unique integer IDs, filtered and normalized.
     * Use this method when performing bulk operations.
     *
     * @return array<int> Normalized array of selected IDs
     */
    public function getSelectedIds(): array
    {
        return $this->normalizeSelection($this->selected);
    }

    /**
     * Select all items on the current page.
     * 
     * Merges current page IDs with existing selections and normalizes.
     * 
     * @return void
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
     * 
     * Removes current page IDs from selections while preserving
     * selections from other pages.
     * 
     * @return void
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
     * 
     * Sets selectAll to true only if all items on the current page are selected.
     * Called automatically after selection changes.
     * 
     * @return void
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
     * Filters out falsy values, removes duplicates, and ensures integer types.
     * This prevents issues with string IDs from URL parameters.
     *
     * @param array<int> $ids Raw array of IDs
     * @return array<int> Normalized array of unique integer IDs
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
