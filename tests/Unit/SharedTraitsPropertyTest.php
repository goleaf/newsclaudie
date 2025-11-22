<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Livewire\Concerns\ManagesBulkActions;
use App\Livewire\Concerns\ManagesSearch;
use App\Livewire\Concerns\ManagesSorting;
use App\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Component;
use Livewire\WithPagination;
use Tests\Helpers\PropertyTesting;
use Tests\TestCase;

/**
 * Property-Based Tests for Shared Livewire Traits
 * 
 * These tests verify universal properties that should hold across all inputs
 * for the shared traits used in admin CRUD components.
 */
final class SharedTraitsPropertyTest extends TestCase
{
    use RefreshDatabase;
    /**
     * Feature: admin-livewire-crud, Property 10: Search filtering accuracy
     * Validates: Requirements 7.1
     * 
     * For any search term and any resource collection, the filtered results
     * should only include items where the search term appears in searchable fields.
     */
    public function test_search_filtering_accuracy(): void
    {
        // Run fewer iterations for database tests to avoid performance issues
        // Each iteration needs its own database transaction
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create a component with ManagesSearch trait
            $component = new class extends Component
            {
                use ManagesSearch;
                use WithPagination;

                public function render()
                {
                    return '<div></div>';
                }
            };

            // Generate a unique search term that won't accidentally match other content
            $searchTerm = 'uniqueterm' . $faker->numberBetween(10000, 99999) . uniqid();
            $component->search = $searchTerm;

            // Create test posts with known content
            // Set published_at to past date to avoid PublishedScope filtering
            $matchingPost = Post::factory()->create([
                'title' => "This contains {$searchTerm} in title",
                'body' => 'Some body content without the term',
                'published_at' => now()->subDay(),
            ]);

            $nonMatchingPost = Post::factory()->create([
                'title' => 'Different title without term',
                'body' => 'Different body content without term',
                'published_at' => now()->subDay(),
            ]);

            // Apply search to query
            $query = Post::query();
            $reflection = new \ReflectionClass($component);
            $method = $reflection->getMethod('applySearch');
            $method->setAccessible(true);
            
            $filteredQuery = $method->invoke($component, $query, ['title', 'body']);
            $results = $filteredQuery->get();

            // Property: All results should contain the search term in at least one searchable field
            foreach ($results as $result) {
                $containsInTitle = str_contains(strtolower($result->title), strtolower($searchTerm));
                $containsInBody = str_contains(strtolower($result->body), strtolower($searchTerm));
                
                $this->assertTrue(
                    $containsInTitle || $containsInBody,
                    "Result should contain search term '{$searchTerm}' in title or body"
                );
            }

            // Property: The matching post should be in results
            $this->assertTrue(
                $results->contains('id', $matchingPost->id),
                "Matching post should be included in search results"
            );

            // Property: The non-matching post should not be in results
            $this->assertFalse(
                $results->contains('id', $nonMatchingPost->id),
                "Non-matching post should not be included in search results"
            );

            // Cleanup
            $matchingPost->delete();
            $nonMatchingPost->delete();
        }
    }

    /**
     * Feature: admin-livewire-crud, Property 15: Column sort ordering
     * Validates: Requirements 9.1, 9.2
     * 
     * For any sortable column, clicking the column header should sort the table
     * by that column in ascending order, and clicking again should toggle to
     * descending order.
     */
    public function test_column_sort_ordering(): void
    {
        PropertyTesting::run(function ($faker) {
            // Create a component with ManagesSorting trait
            $component = new class extends Component
            {
                use ManagesSorting;

                public function render()
                {
                    return '<div></div>';
                }
            };

            // Generate random sortable field name
            $sortableFields = ['title', 'created_at', 'updated_at', 'published_at'];
            $field = $faker->randomElement($sortableFields);

            // Property: First click should set ascending order
            $component->sortBy($field);
            $this->assertSame($field, $component->sortField, "Sort field should be set to {$field}");
            $this->assertSame('asc', $component->sortDirection, "First click should set ascending order");

            // Property: Second click on same field should toggle to descending
            $component->sortBy($field);
            $this->assertSame($field, $component->sortField, "Sort field should remain {$field}");
            $this->assertSame('desc', $component->sortDirection, "Second click should toggle to descending");

            // Property: Third click should toggle back to ascending
            $component->sortBy($field);
            $this->assertSame('asc', $component->sortDirection, "Third click should toggle back to ascending");

            // Property: Clicking different field should reset to ascending
            $differentField = $faker->randomElement(array_diff($sortableFields, [$field]));
            $component->sortBy($differentField);
            $this->assertSame($differentField, $component->sortField, "Sort field should change to {$differentField}");
            $this->assertSame('asc', $component->sortDirection, "New field should start with ascending order");

            // Property: isSortedBy should return true for current field
            $this->assertTrue($component->isSortedBy($differentField), "isSortedBy should return true for current field");
            $this->assertFalse($component->isSortedBy($field), "isSortedBy should return false for previous field");

            // Property: getSortDirection should return direction for current field
            $this->assertSame('asc', $component->getSortDirection($differentField), "getSortDirection should return 'asc' for current field");
            $this->assertNull($component->getSortDirection($field), "getSortDirection should return null for non-current field");
        });
    }

    /**
     * Feature: admin-livewire-crud, Property 18: Select all page scope
     * Validates: Requirements 8.2
     * 
     * For any paginated table, clicking "select all" should select only the
     * items visible on the current page.
     */
    public function test_select_all_page_scope(): void
    {
        PropertyTesting::run(function ($faker) {
            // Create a component with ManagesBulkActions trait
            $component = new class extends Component
            {
                use ManagesBulkActions;

                public function render()
                {
                    return '<div></div>';
                }
            };

            // Generate random page IDs (simulating current page items)
            $pageSize = $faker->numberBetween(5, 20);
            $currentPageIds = [];
            for ($i = 0; $i < $pageSize; $i++) {
                $currentPageIds[] = $faker->unique()->numberBetween(1, 1000);
            }

            // Set current page IDs
            $component->setCurrentPageIds($currentPageIds);

            // Property: Initially, nothing should be selected
            $this->assertSame([], $component->selected, "Initially, selection should be empty");
            $this->assertFalse($component->selectAll, "Initially, selectAll should be false");

            // Property: Activating selectAll should select only current page items
            $component->updatedSelectAll(true);
            
            $selectedIds = $component->getSelectedIds();
            $this->assertCount($pageSize, $selectedIds, "Should select exactly {$pageSize} items from current page");
            
            // Property: All current page IDs should be in selection
            foreach ($currentPageIds as $id) {
                $this->assertContains($id, $selectedIds, "Current page ID {$id} should be selected");
            }

            // Property: selectAll flag should be true
            $this->assertTrue($component->selectAll, "selectAll flag should be true after selecting all");

            // Property: Deactivating selectAll should deselect only current page items
            // First, add some IDs that are NOT on current page
            $otherPageIds = [$faker->numberBetween(2000, 3000), $faker->numberBetween(3001, 4000)];
            $component->selected = array_merge($selectedIds, $otherPageIds);

            $component->updatedSelectAll(false);
            
            $remainingIds = $component->getSelectedIds();
            
            // Property: Current page IDs should be removed
            foreach ($currentPageIds as $id) {
                $this->assertNotContains($id, $remainingIds, "Current page ID {$id} should be deselected");
            }
            
            // Property: Other page IDs should remain selected
            foreach ($otherPageIds as $id) {
                $this->assertContains($id, $remainingIds, "Other page ID {$id} should remain selected");
            }

            // Property: selectAll flag should be false
            $this->assertFalse($component->selectAll, "selectAll flag should be false after deselecting all");

            // Property: Selected count should only reflect current page scope
            $component->clearSelection();
            $component->setCurrentPageIds($currentPageIds);
            $component->updatedSelectAll(true);
            
            $this->assertSame($pageSize, $component->selectedCount, "Selected count should match page size");
        });
    }
}
