<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Livewire\Concerns\ManagesBulkActions;
use Livewire\Component;
use Tests\TestCase;

final class ManagesBulkActionsTest extends TestCase
{
    private function makeComponent(): object
    {
        return new class extends Component
        {
            use ManagesBulkActions;

            public function render()
            {
                return '<div></div>';
            }
        };
    }

    public function test_initializes_with_empty_selection(): void
    {
        $component = $this->makeComponent();

        $this->assertSame([], $component->selected);
        $this->assertFalse($component->selectAll);
        $this->assertSame(0, $component->selectedCount);
    }

    public function test_toggle_selection_adds_id(): void
    {
        $component = $this->makeComponent();

        $component->toggleSelection(1);

        $this->assertSame([1], $component->selected);
    }

    public function test_toggle_selection_removes_id(): void
    {
        $component = $this->makeComponent();
        $component->selected = [1, 2, 3];

        $component->toggleSelection(2);

        $this->assertSame([1, 3], $component->selected);
    }

    public function test_clear_selection_resets_state(): void
    {
        $component = $this->makeComponent();
        $component->selected = [1, 2, 3];
        $component->selectAll = true;

        $component->clearSelection();

        $this->assertSame([], $component->selected);
        $this->assertFalse($component->selectAll);
    }

    public function test_selected_count_property(): void
    {
        $component = $this->makeComponent();
        $component->selected = [1, 2, 3];

        $this->assertSame(3, $component->selectedCount);
    }

    public function test_updated_select_all_selects_current_page(): void
    {
        $component = $this->makeComponent();
        $component->setCurrentPageIds([1, 2, 3]);

        $component->updatedSelectAll(true);

        $this->assertSame([1, 2, 3], $component->selected);
        $this->assertTrue($component->selectAll);
    }

    public function test_updated_select_all_deselects_current_page(): void
    {
        $component = $this->makeComponent();
        $component->setCurrentPageIds([1, 2, 3]);
        $component->selected = [1, 2, 3, 4, 5];

        $component->updatedSelectAll(false);

        $this->assertSame([4, 5], $component->selected);
        $this->assertFalse($component->selectAll);
    }

    public function test_select_all_state_updates_when_all_page_items_selected(): void
    {
        $component = $this->makeComponent();
        $component->setCurrentPageIds([1, 2, 3]);

        $component->toggleSelection(1);
        $this->assertFalse($component->selectAll);

        $component->toggleSelection(2);
        $this->assertFalse($component->selectAll);

        $component->toggleSelection(3);
        $this->assertTrue($component->selectAll);
    }

    public function test_normalizes_selection_to_integers(): void
    {
        $component = $this->makeComponent();
        $component->selected = ['1', '2', '3'];

        $ids = $component->getSelectedIds();

        $this->assertSame([1, 2, 3], $ids);
    }

    public function test_removes_duplicates_from_selection(): void
    {
        $component = $this->makeComponent();
        $component->selected = [1, 2, 2, 3, 3, 3];

        $ids = $component->getSelectedIds();

        $this->assertSame([1, 2, 3], $ids);
    }
}
