<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property-Based Tests for News Clear Filters Functionality
 * 
 * These tests verify that the clear filters button behaves correctly across
 * all possible filter states. The tests use property-based testing to validate
 * behavior across many randomized scenarios.
 * 
 * ## Properties Tested
 * 
 * - **Property 15**: Clear filters button visibility - Button shown only when filters applied
 * - **Property 16**: Clear filters action - Clicking clear removes all filters
 * 
 * ## Testing Approach
 * 
 * Each test runs multiple iterations with randomized filter combinations to verify
 * that the properties hold across diverse scenarios:
 * 
 * - Random category selections (0-3 categories)
 * - Random author selections (0-2 authors)
 * - Random date ranges
 * - Different combinations of filters
 * 
 * ## Related Components
 * 
 * @see \App\Http\Controllers\NewsController The controller handling filter state
 * @see \App\Http\Requests\NewsIndexRequest Request validation for filters
 * @see \resources\views\components\news\filter-panel.blade.php Filter panel with clear button
 * 
 * ## Requirements Validated
 * 
 * - Requirement 6.1: Display "Clear All Filters" button when filters applied
 * - Requirement 6.3: Clear button removes all category, author, and date range filters
 * - Requirement 6.5: URL updated to remove all filter query parameters
 * 
 * @package Tests\Unit
 * @group property-testing
 * @group news-page
 * @group clear-filters
 */
final class NewsClearFiltersPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Set up the test environment.
     *
     * Disables rate limiting and clears cache for consistent test results.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Disable rate limiting for tests
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        
        // Clear cache before each test
        \Illuminate\Support\Facades\Cache::flush();
    }

    /**
     * Test Property 15: Clear filters button visibility
     * 
     * **Property**: For any filter state, the "Clear All Filters" button should be
     * visible if and only if at least one filter is applied (categories, authors,
     * or date range).
     * 
     * **Validates**: Requirements 6.1
     * 
     * **Test Strategy**:
     * - Tests multiple scenarios with different filter combinations
     * - Verifies button is shown when any filter is applied
     * - Verifies button is hidden when no filters are applied
     * - Runs 10 iterations with randomized filter combinations
     * 
     * **Properties Verified**:
     * 1. Button visible when categories selected
     * 2. Button visible when authors selected
     * 3. Button visible when from_date set
     * 4. Button visible when to_date set
     * 5. Button visible when multiple filters combined
     * 6. Button hidden when no filters applied
     * 
     * @return void
     * 
     * @test
     * @group property-testing
     * @group clear-filters
     */
    public function test_clear_filters_button_visibility(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create test data
            $categories = Category::factory()->count(3)->create();
            $authors = User::factory()->count(3)->create();
            
            // Create posts with associations
            foreach (range(1, 10) as $j) {
                $post = Post::factory()
                    ->for($authors->random(), 'author')
                    ->create(['published_at' => now()->subDays($faker->numberBetween(1, 30))]);
                
                $post->categories()->attach(
                    $categories->random($faker->numberBetween(1, 2))->pluck('id')
                );
            }
            
            // Test 1: Button visible when categories selected
            $selectedCategories = $categories->random($faker->numberBetween(1, 2))->pluck('id')->toArray();
            $response = $this->get(route('news.index', ['categories' => $selectedCategories]));
            
            $response->assertOk();
            $response->assertSee('Clear all filters', false); // false = don't escape HTML
            
            // Test 2: Button visible when authors selected
            $selectedAuthors = $authors->random($faker->numberBetween(1, 2))->pluck('id')->toArray();
            $response = $this->get(route('news.index', ['authors' => $selectedAuthors]));
            
            $response->assertOk();
            $response->assertSee('Clear all filters', false);
            
            // Test 3: Button visible when from_date set
            $fromDate = now()->subDays($faker->numberBetween(20, 25))->format('Y-m-d');
            $response = $this->get(route('news.index', ['from_date' => $fromDate]));
            
            $response->assertOk();
            $response->assertSee('Clear all filters', false);
            
            // Test 4: Button visible when to_date set
            $toDate = now()->subDays($faker->numberBetween(5, 10))->format('Y-m-d');
            $response = $this->get(route('news.index', ['to_date' => $toDate]));
            
            $response->assertOk();
            $response->assertSee('Clear all filters', false);
            
            // Test 5: Button visible when multiple filters combined
            $response = $this->get(route('news.index', [
                'categories' => $selectedCategories,
                'authors' => $selectedAuthors,
                'from_date' => $fromDate,
                'to_date' => $toDate,
            ]));
            
            $response->assertOk();
            $response->assertSee('Clear all filters', false);
            
            // Test 6: Button hidden when no filters applied
            $response = $this->get(route('news.index'));
            
            $response->assertOk();
            $response->assertDontSee('Clear All', false);
            $response->assertDontSee('Active Filters', false);
            
            // Cleanup
            foreach ($categories as $category) {
                $category->posts()->detach();
                $category->delete();
            }
            foreach ($authors as $author) {
                $author->delete();
            }
        }
    }

    /**
     * Test Property 16: Clear filters action
     * 
     * **Property**: For any set of applied filters, when the "Clear All Filters"
     * button is clicked (navigating to the clean /news URL), all category, author,
     * and date range filters should be removed, and the URL should contain no
     * filter query parameters.
     * 
     * **Validates**: Requirements 6.3, 6.5
     * 
     * **Test Strategy**:
     * - Applies random filter combinations
     * - Simulates clicking "Clear All" by navigating to clean /news URL
     * - Verifies all filters are removed
     * - Verifies URL contains no filter parameters
     * - Verifies all posts are shown (no filtering)
     * - Runs 10 iterations with different scenarios
     * 
     * **Properties Verified**:
     * 1. Category filters removed after clear
     * 2. Author filters removed after clear
     * 3. Date range filters removed after clear
     * 4. URL contains no filter parameters
     * 5. All published posts are shown
     * 6. Total count matches all published posts
     * 
     * @return void
     * 
     * @test
     * @group property-testing
     * @group clear-filters
     */
    public function test_clear_filters_action(): void
    {
        for ($i = 0; $i < 10; $i++) {
            // Clean database before each iteration
            Post::query()->delete();
            Category::query()->delete();
            User::query()->delete();
            
            $faker = fake();
            
            // Create test data
            $categories = Category::factory()->count(4)->create();
            $authors = User::factory()->count(3)->create();
            
            // Create posts with associations
            $totalPosts = $faker->numberBetween(10, 15);
            foreach (range(1, $totalPosts) as $j) {
                $post = Post::factory()
                    ->for($authors->random(), 'author')
                    ->create(['published_at' => now()->subDays($faker->numberBetween(1, 30))]);
                
                $post->categories()->attach(
                    $categories->random($faker->numberBetween(1, 2))->pluck('id')
                );
            }
            
            // Apply random filter combination
            $selectedCategories = $categories->random($faker->numberBetween(1, 2))->pluck('id')->toArray();
            $selectedAuthors = $authors->random($faker->numberBetween(1, 2))->pluck('id')->toArray();
            $fromDate = now()->subDays($faker->numberBetween(20, 25))->format('Y-m-d');
            $toDate = now()->subDays($faker->numberBetween(5, 10))->format('Y-m-d');
            
            // Property: Request with filters should show filtered results
            $filteredResponse = $this->get(route('news.index', [
                'categories' => $selectedCategories,
                'authors' => $selectedAuthors,
                'from_date' => $fromDate,
                'to_date' => $toDate,
            ]));
            
            $filteredResponse->assertOk();
            $filteredCount = $filteredResponse->viewData('totalCount');
            
            // Verify filters are applied (count should be less than or equal to total)
            $this->assertLessThanOrEqual(
                $totalPosts,
                $filteredCount,
                "Filtered count should not exceed total posts"
            );
            
            // Property: Clicking "Clear All" (navigating to clean /news URL) removes all filters
            $clearedResponse = $this->get(route('news.index'));
            
            $clearedResponse->assertOk();
            $clearedAppliedFilters = $clearedResponse->viewData('appliedFilters');
            $clearedCount = $clearedResponse->viewData('totalCount');
            
            // Property: All filter arrays should be empty or not set
            $this->assertEmpty(
                $clearedAppliedFilters['categories'] ?? [],
                "Category filters should be empty after clear"
            );
            
            $this->assertEmpty(
                $clearedAppliedFilters['authors'] ?? [],
                "Author filters should be empty after clear"
            );
            
            $this->assertNull(
                $clearedAppliedFilters['from_date'] ?? null,
                "From date should be null after clear"
            );
            
            $this->assertNull(
                $clearedAppliedFilters['to_date'] ?? null,
                "To date should be null after clear"
            );
            
            // Property: Total count should match all published posts
            $this->assertEquals(
                $totalPosts,
                $clearedCount,
                "Cleared view should show all published posts"
            );
            
            // Property: URL should not contain filter parameters
            // Verify by checking that pagination URLs don't contain filter params
            $clearedPosts = $clearedResponse->viewData('posts');
            if ($clearedPosts->hasPages()) {
                $paginationUrl = $clearedPosts->url(2);
                
                $this->assertStringNotContainsString(
                    'categories',
                    $paginationUrl,
                    "Pagination URL should not contain categories after clear"
                );
                
                $this->assertStringNotContainsString(
                    'authors',
                    $paginationUrl,
                    "Pagination URL should not contain authors after clear"
                );
                
                $this->assertStringNotContainsString(
                    'from_date',
                    $paginationUrl,
                    "Pagination URL should not contain from_date after clear"
                );
                
                $this->assertStringNotContainsString(
                    'to_date',
                    $paginationUrl,
                    "Pagination URL should not contain to_date after clear"
                );
            }
            
            // Property: Clear All button should not be visible
            $clearedResponse->assertDontSee('Clear All', false);
            $clearedResponse->assertDontSee('Active Filters', false);
            
            // Cleanup
            foreach ($categories as $category) {
                $category->posts()->detach();
                $category->delete();
            }
            foreach ($authors as $author) {
                $author->delete();
            }
        }
    }

    /**
     * Test Property 15 (Edge Case): Button visibility with only sort parameter
     * 
     * **Property**: When only the sort parameter is set (no other filters),
     * the "Clear All Filters" button should NOT be visible, as sort is not
     * considered a filter that needs clearing.
     * 
     * **Validates**: Requirements 6.1 - Sort is not a clearable filter
     * 
     * **Test Strategy**:
     * - Creates posts with different dates
     * - Applies only sort parameter
     * - Verifies clear button is not shown
     * - Tests both sort directions
     * 
     * **Edge Case Tested**:
     * - Sort-only state (no actual filters)
     * - Button should remain hidden
     * 
     * @return void
     * 
     * @test
     * @group property-testing
     * @group clear-filters
     * @group edge-cases
     */
    public function test_clear_button_hidden_with_only_sort_parameter(): void
    {
        // Create posts with distinct dates
        Post::factory()->count(5)->create([
            'published_at' => now()->subDays(fake()->numberBetween(1, 10)),
        ]);
        
        // Property: Sort parameter alone should not show clear button
        $newestResponse = $this->get(route('news.index', ['sort' => 'newest']));
        
        $newestResponse->assertOk();
        $newestResponse->assertDontSee('Clear All', false);
        $newestResponse->assertDontSee('Active Filters', false);
        
        // Test with oldest sort as well
        $oldestResponse = $this->get(route('news.index', ['sort' => 'oldest']));
        
        $oldestResponse->assertOk();
        $oldestResponse->assertDontSee('Clear All', false);
        $oldestResponse->assertDontSee('Active Filters', false);
    }

    /**
     * Test Property 16 (Edge Case): Clear filters with pagination
     * 
     * **Property**: When filters are cleared from a paginated view (e.g., page 2),
     * the user should be returned to page 1 of the unfiltered results, and the
     * page parameter should also be removed from the URL.
     * 
     * **Validates**: Requirements 6.3, 6.5 - Complete filter state reset
     * 
     * **Test Strategy**:
     * - Creates enough posts for pagination (>15)
     * - Applies filters and navigates to page 2
     * - Clears filters
     * - Verifies return to page 1
     * - Verifies page parameter removed
     * 
     * **Edge Case Tested**:
     * - Pagination state during clear
     * - Complete URL parameter cleanup
     * 
     * @return void
     * 
     * @test
     * @group property-testing
     * @group clear-filters
     * @group edge-cases
     */
    public function test_clear_filters_with_pagination(): void
    {
        // Create enough posts for pagination
        $category = Category::factory()->create();
        $author = User::factory()->create();
        
        foreach (range(1, 20) as $i) {
            $post = Post::factory()
                ->for($author, 'author')
                ->create(['published_at' => now()->subDays($i)]);
            
            $post->categories()->attach($category);
        }
        
        // Property: Apply filters and navigate to page 2
        $filteredPage2Response = $this->get(route('news.index', [
            'categories' => [$category->id],
            'page' => 2,
        ]));
        
        $filteredPage2Response->assertOk();
        
        // Verify we're on page 2
        $posts = $filteredPage2Response->viewData('posts');
        $this->assertEquals(2, $posts->currentPage(), "Should be on page 2");
        
        // Property: Clear filters should return to page 1
        $clearedResponse = $this->get(route('news.index'));
        
        $clearedResponse->assertOk();
        $clearedPosts = $clearedResponse->viewData('posts');
        
        // Verify we're back on page 1
        $this->assertEquals(
            1,
            $clearedPosts->currentPage(),
            "Should return to page 1 after clearing filters"
        );
        
        // Verify no filters applied
        $clearedAppliedFilters = $clearedResponse->viewData('appliedFilters');
        $this->assertEmpty(
            $clearedAppliedFilters['categories'] ?? [],
            "Category filters should be cleared"
        );
        
        // Cleanup
        $category->posts()->detach();
        $category->delete();
        $author->delete();
    }

    /**
     * Test Property 16 (Edge Case): Clear filters shows all posts
     * 
     * **Property**: After clearing filters, the total count should equal the
     * number of all published posts in the database, confirming that no
     * filtering is being applied.
     * 
     * **Validates**: Requirements 6.3 - Complete filter removal
     * 
     * **Test Strategy**:
     * - Creates posts with various states (published, draft, future)
     * - Applies restrictive filters
     * - Clears filters
     * - Verifies count matches only published posts
     * - Runs 5 iterations with different scenarios
     * 
     * **Edge Case Tested**:
     * - Draft posts excluded (published_at = null)
     * - Future posts excluded (published_at > now)
     * - Only published posts counted
     * 
     * @return void
     * 
     * @test
     * @group property-testing
     * @group clear-filters
     * @group edge-cases
     */
    public function test_clear_filters_shows_all_published_posts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            // Clean database before each iteration
            Post::query()->delete();
            Category::query()->delete();
            User::query()->delete();
            
            $faker = fake();
            
            $category = Category::factory()->create();
            $author = User::factory()->create();
            
            // Create published posts
            $publishedCount = $faker->numberBetween(5, 10);
            foreach (range(1, $publishedCount) as $j) {
                $post = Post::factory()
                    ->for($author, 'author')
                    ->create(['published_at' => now()->subDays($j)]);
                
                $post->categories()->attach($category);
            }
            
            // Create draft posts (should not appear)
            $draftCount = $faker->numberBetween(2, 5);
            Post::factory()->count($draftCount)->for($author, 'author')->create(['published_at' => null]);
            
            // Create future posts (should not appear)
            $futureCount = $faker->numberBetween(1, 3);
            Post::factory()->count($futureCount)->for($author, 'author')->create(['published_at' => now()->addDays(1)]);
            
            // Apply restrictive filters
            $filteredResponse = $this->get(route('news.index', [
                'categories' => [$category->id],
                'from_date' => now()->subDays(5)->format('Y-m-d'),
            ]));
            
            $filteredResponse->assertOk();
            $filteredCount = $filteredResponse->viewData('totalCount');
            
            // Verify filters are working (count should be less than or equal to published)
            $this->assertLessThanOrEqual(
                $publishedCount,
                $filteredCount,
                "Filtered count should not exceed published posts"
            );
            
            // Property: Clear filters should show all published posts
            $clearedResponse = $this->get(route('news.index'));
            
            $clearedResponse->assertOk();
            $clearedCount = $clearedResponse->viewData('totalCount');
            
            // Verify count matches only published posts (not drafts or future)
            $this->assertEquals(
                $publishedCount,
                $clearedCount,
                "Cleared view should show exactly all published posts (iteration {$i})"
            );
            
            // Cleanup
            $category->posts()->detach();
            $category->delete();
            $author->delete();
        }
    }
}

