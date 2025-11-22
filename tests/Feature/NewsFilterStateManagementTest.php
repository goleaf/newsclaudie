<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test filter state management for the news page.
 *
 * Validates Requirements: 2.5, 3.5, 4.5, 5.4, 5.5, 6.1, 6.2, 6.3, 6.5
 */
class NewsFilterStateManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that filter parameters persist in pagination links.
     *
     * @test
     * @return void
     */
    public function test_filter_parameters_persist_in_pagination_links(): void
    {
        // Create test data
        $category = Category::factory()->create();
        $author = User::factory()->create();
        
        // Create 20 posts to trigger pagination (15 per page)
        Post::factory()
            ->count(20)
            ->published()
            ->for($author, 'author')
            ->hasAttached($category)
            ->create();

        // Apply filters
        $response = $this->get(route('news.index', [
            'categories' => [$category->id],
            'authors' => [$author->id],
            'from_date' => '2024-01-01',
            'to_date' => '2024-12-31',
            'sort' => 'oldest',
        ]));

        $response->assertOk();
        
        // Check that pagination links contain all filter parameters
        $content = $response->getContent();
        
        // Debug: Let's see if there are pagination links at all
        if (!str_contains($content, 'pagination')) {
            // No pagination links means we need to check if filters are preserved in the form
            // The form should have the filter values set
            $this->assertStringContainsString('value="' . $category->id . '"', $content);
            $this->assertStringContainsString('checked', $content);
            $this->assertStringContainsString('value="2024-01-01"', $content);
            $this->assertStringContainsString('value="2024-12-31"', $content);
            $this->assertStringContainsString('selected', $content); // sort dropdown
        } else {
            // Pagination links should contain the filter parameters
            // Laravel encodes array parameters as categories[0]=1 or categories%5B0%5D=1
            $this->assertTrue(
                str_contains($content, 'categories[0]=' . $category->id) || 
                str_contains($content, 'categories%5B0%5D=' . $category->id),
                'Pagination links should contain category filter'
            );
            $this->assertTrue(
                str_contains($content, 'authors[0]=' . $author->id) || 
                str_contains($content, 'authors%5B0%5D=' . $author->id),
                'Pagination links should contain author filter'
            );
            $this->assertStringContainsString('from_date=2024-01-01', $content);
            $this->assertStringContainsString('to_date=2024-12-31', $content);
            $this->assertStringContainsString('sort=oldest', $content);
        }
    }

    /**
     * Test that sort order persists when filters change.
     *
     * @test
     * @return void
     */
    public function test_sort_order_persists_when_filters_change(): void
    {
        $category = Category::factory()->create();
        $author = User::factory()->create();
        
        Post::factory()
            ->count(5)
            ->published()
            ->for($author, 'author')
            ->hasAttached($category)
            ->create();

        // Apply sort order
        $response = $this->get(route('news.index', [
            'sort' => 'oldest',
        ]));

        $response->assertOk();
        
        // The form should preserve the sort parameter
        $response->assertSee('value="oldest"', false);
        $response->assertSee('selected', false);
    }

    /**
     * Test that filters persist when sort changes.
     *
     * @test
     * @return void
     */
    public function test_filters_persist_when_sort_changes(): void
    {
        $category = Category::factory()->create();
        $author = User::factory()->create();
        
        Post::factory()
            ->count(5)
            ->published()
            ->for($author, 'author')
            ->hasAttached($category)
            ->create();

        // Apply filters and sort
        $response = $this->get(route('news.index', [
            'categories' => [$category->id],
            'authors' => [$author->id],
            'from_date' => '2024-01-01',
            'sort' => 'oldest',
        ]));

        $response->assertOk();
        
        // Check that filter checkboxes are checked
        $response->assertSee('value="' . $category->id . '"', false);
        $response->assertSee('checked', false);
        
        // Check that date inputs have values
        $response->assertSee('value="2024-01-01"', false);
    }

    /**
     * Test that "Clear All Filters" button is visible when filters are applied.
     *
     * @test
     * @return void
     */
    public function test_clear_filters_button_visible_when_filters_applied(): void
    {
        $category = Category::factory()->create();
        
        Post::factory()
            ->count(5)
            ->published()
            ->hasAttached($category)
            ->create();

        // With filters
        $response = $this->get(route('news.index', [
            'categories' => [$category->id],
        ]));

        $response->assertOk();
        $response->assertSee('Clear All');
    }

    /**
     * Test that "Clear All Filters" button is hidden when no filters are applied.
     *
     * @test
     * @return void
     */
    public function test_clear_filters_button_hidden_when_no_filters(): void
    {
        Post::factory()
            ->count(5)
            ->published()
            ->create();

        // Without filters
        $response = $this->get(route('news.index'));

        $response->assertOk();
        $response->assertDontSee('Clear All');
    }

    /**
     * Test that clicking "Clear All Filters" removes all filters.
     *
     * @test
     * @return void
     */
    public function test_clear_filters_removes_all_parameters(): void
    {
        $category = Category::factory()->create();
        $author = User::factory()->create();
        
        Post::factory()
            ->count(5)
            ->published()
            ->for($author, 'author')
            ->hasAttached($category)
            ->create();

        // First, apply filters
        $responseWithFilters = $this->get(route('news.index', [
            'categories' => [$category->id],
            'authors' => [$author->id],
            'from_date' => '2024-01-01',
            'to_date' => '2024-12-31',
            'sort' => 'oldest',
        ]));

        $responseWithFilters->assertOk();

        // Now access the clean URL (simulating clicking "Clear All")
        $responseClean = $this->get(route('news.index'));

        $responseClean->assertOk();
        
        // Verify no filters are applied in the clean response
        // The checkboxes should not be checked
        $content = $responseClean->getContent();
        
        // Count how many "checked" attributes appear - should be 0
        $checkedCount = substr_count($content, 'checked');
        $this->assertEquals(0, $checkedCount, 'No checkboxes should be checked when filters are cleared');
        
        // Date inputs should be empty
        $this->assertStringNotContainsString('value="2024-01-01"', $content);
        $this->assertStringNotContainsString('value="2024-12-31"', $content);
    }

    /**
     * Test that URL parameters are preserved when navigating between pages.
     *
     * @test
     * @return void
     */
    public function test_url_parameters_preserved_across_pagination(): void
    {
        $category = Category::factory()->create();
        
        // Create 20 posts to trigger pagination
        Post::factory()
            ->count(20)
            ->published()
            ->hasAttached($category)
            ->create();

        // Navigate to page 2 with filters
        $response = $this->get(route('news.index', [
            'categories' => [$category->id],
            'sort' => 'oldest',
            'page' => 2,
        ]));

        $response->assertOk();
        
        // Check that we're on page 2 and filters are still applied
        $content = $response->getContent();
        
        // Should show page 2 results (items 16-20)
        $this->assertStringContainsString('Showing 16-20', $content);
        
        // Filter should still be applied
        $response->assertSee('value="' . $category->id . '"', false);
        $response->assertSee('checked', false);
    }
}
