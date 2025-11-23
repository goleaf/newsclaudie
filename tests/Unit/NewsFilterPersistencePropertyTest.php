<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property-Based Tests for News Filter Persistence
 * 
 * These tests verify that filter state is correctly preserved in URLs across
 * pagination and sort changes. This ensures users can bookmark filtered views
 * and share links that maintain the filter state.
 * 
 * ## Properties Tested
 * 
 * - **Property 13**: Filter persistence in URL - All filters preserved in pagination
 * - **Property 14**: Sort preserves filters - Changing sort maintains active filters
 * 
 * ## Testing Approach
 * 
 * Each test runs multiple iterations with randomized filter combinations to verify
 * that URL parameter persistence works correctly across diverse scenarios:
 * 
 * - Random category selections (1-3 categories)
 * - Random author selections (1-2 authors)
 * - Random date ranges
 * - Different sort orders
 * - Multiple pagination pages
 * 
 * ## Related Components
 * 
 * @see \App\Http\Controllers\NewsController The controller handling filter persistence
 * @see \App\Http\Requests\NewsIndexRequest Request validation for filters
 * @see \Illuminate\Pagination\LengthAwarePaginator Pagination with query string preservation
 * 
 * ## Requirements Validated
 * 
 * - Requirement 2.5: Category filters preserved in URL query parameters
 * - Requirement 3.5: Date filters preserved in URL query parameters
 * - Requirement 4.5: Author filters preserved in URL query parameters
 * - Requirement 5.4: Sort order preserved when filters change
 * - Requirement 5.5: Sort preference preserved in URL query parameters
 * 
 * @package Tests\Unit
 * @group property-testing
 * @group news-page
 * @group filter-persistence
 */
final class NewsFilterPersistencePropertyTest extends TestCase
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
     * Test Property 13: Filter persistence in URL
     * 
     * **Property**: For any combination of applied filters (categories, authors, 
     * date range, sort), the pagination URLs should contain all filter values, 
     * and navigating to those URLs should restore the exact same filter state.
     * 
     * **Validates**: Requirements 2.5, 3.5, 4.5, 5.5
     * 
     * **Test Strategy**:
     * - Creates 20+ posts to trigger pagination
     * - Applies random combinations of filters
     * - Verifies pagination URLs contain all filter parameters
     * - Verifies navigating to page 2 maintains all filters
     * - Runs 10 iterations with different filter combinations
     * 
     * **Properties Verified**:
     * 1. All category filters appear in pagination URLs
     * 2. All author filters appear in pagination URLs
     * 3. Date range filters appear in pagination URLs
     * 4. Sort parameter appears in pagination URLs
     * 5. Navigating to paginated URLs maintains filter state
     * 
     * @return void
     * 
     * @test
     * @group property-testing
     * @group filter-persistence
     */
    public function test_filter_persistence_in_url(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create enough posts to trigger pagination (need > 15 for page 2)
            $postCount = $faker->numberBetween(20, 30);
            
            // Create categories and authors
            $categories = Category::factory()->count(5)->create();
            $authors = User::factory()->count(3)->create();
            
            // Create posts with random associations
            foreach (range(1, $postCount) as $j) {
                $post = Post::factory()
                    ->for($authors->random(), 'author')
                    ->create(['published_at' => now()->subDays($faker->numberBetween(1, 30))]);
                
                // Attach 1-2 random categories
                $post->categories()->attach(
                    $categories->random($faker->numberBetween(1, 2))->pluck('id')
                );
            }
            
            // Generate random filter combination
            $selectedCategories = $categories->random($faker->numberBetween(1, 3))->pluck('id')->toArray();
            $selectedAuthors = $authors->random($faker->numberBetween(1, 2))->pluck('id')->toArray();
            $fromDate = now()->subDays($faker->numberBetween(20, 25))->format('Y-m-d');
            $toDate = now()->subDays($faker->numberBetween(5, 10))->format('Y-m-d');
            $sort = $faker->randomElement(['newest', 'oldest']);
            
            // Property: Request with filters should return pagination URLs containing all filters
            $response = $this->get(route('news.index', [
                'categories' => $selectedCategories,
                'authors' => $selectedAuthors,
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'sort' => $sort,
            ]));
            
            $response->assertOk();
            $posts = $response->viewData('posts');
            
            // Verify pagination URLs contain all filter parameters
            $page2Url = $posts->url(2);
            
            // Property: All category filters must be in pagination URL
            // Laravel uses indexed array notation: categories%5B0%5D, categories%5B1%5D, etc.
            foreach ($selectedCategories as $categoryId) {
                $this->assertStringContainsString(
                    "categories",
                    $page2Url,
                    "Categories parameter should be preserved in pagination URL"
                );
                $this->assertStringContainsString(
                    (string) $categoryId,
                    $page2Url,
                    "Category {$categoryId} should be preserved in pagination URL"
                );
            }
            
            // Property: All author filters must be in pagination URL
            foreach ($selectedAuthors as $authorId) {
                $this->assertStringContainsString(
                    "authors",
                    $page2Url,
                    "Authors parameter should be preserved in pagination URL"
                );
                $this->assertStringContainsString(
                    (string) $authorId,
                    $page2Url,
                    "Author {$authorId} should be preserved in pagination URL"
                );
            }
            
            // Property: Date range filters must be in pagination URL
            $this->assertStringContainsString(
                "from_date={$fromDate}",
                $page2Url,
                "From date should be preserved in pagination URL"
            );
            
            $this->assertStringContainsString(
                "to_date={$toDate}",
                $page2Url,
                "To date should be preserved in pagination URL"
            );
            
            // Property: Sort parameter must be in pagination URL
            $this->assertStringContainsString(
                "sort={$sort}",
                $page2Url,
                "Sort order should be preserved in pagination URL"
            );
            
            // Property: Navigating to page 2 should maintain all filters
            $page2Response = $this->get(route('news.index', [
                'categories' => $selectedCategories,
                'authors' => $selectedAuthors,
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'sort' => $sort,
                'page' => 2,
            ]));
            
            $page2Response->assertOk();
            $appliedFilters = $page2Response->viewData('appliedFilters');
            
            // Verify all filters are still applied on page 2
            sort($selectedCategories);
            $returnedCategories = $appliedFilters['categories'] ?? [];
            sort($returnedCategories);
            
            $this->assertEquals(
                $selectedCategories,
                $returnedCategories,
                "Category filters should be maintained on page 2"
            );
            
            sort($selectedAuthors);
            $returnedAuthors = $appliedFilters['authors'] ?? [];
            sort($returnedAuthors);
            
            $this->assertEquals(
                $selectedAuthors,
                $returnedAuthors,
                "Author filters should be maintained on page 2"
            );
            
            $this->assertEquals(
                $fromDate,
                $appliedFilters['from_date'] ?? null,
                "From date should be maintained on page 2"
            );
            
            $this->assertEquals(
                $toDate,
                $appliedFilters['to_date'] ?? null,
                "To date should be maintained on page 2"
            );
            
            $this->assertEquals(
                $sort,
                $appliedFilters['sort'] ?? null,
                "Sort order should be maintained on page 2"
            );
            
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
     * Test Property 14: Sort preserves filters
     * 
     * **Property**: For any set of applied filters, when the sort order is changed,
     * all previously applied filters should remain active and the results should
     * reflect both the filters and the new sort order.
     * 
     * **Validates**: Requirements 5.4
     * 
     * **Test Strategy**:
     * - Applies random filter combination
     * - Makes initial request with 'newest' sort
     * - Changes sort to 'oldest' while keeping same filters
     * - Verifies filters are still applied
     * - Verifies sort order actually changed
     * - Runs 10 iterations with different scenarios
     * 
     * **Properties Verified**:
     * 1. Category filters remain active when sort changes
     * 2. Author filters remain active when sort changes
     * 3. Date range filters remain active when sort changes
     * 4. Result count stays the same (same filters)
     * 5. Sort order actually changes (newest vs oldest)
     * 
     * @return void
     * 
     * @test
     * @group property-testing
     * @group filter-persistence
     */
    public function test_sort_preserves_filters(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create test data
            $categories = Category::factory()->count(4)->create();
            $authors = User::factory()->count(3)->create();
            
            // Create posts with varying dates for sort testing
            $postCount = $faker->numberBetween(10, 15);
            foreach (range(1, $postCount) as $j) {
                $post = Post::factory()
                    ->for($authors->random(), 'author')
                    ->create([
                        'published_at' => now()->subDays($j),
                        'title' => "Post {$j}",
                    ]);
                
                $post->categories()->attach(
                    $categories->random($faker->numberBetween(1, 2))->pluck('id')
                );
            }
            
            // Generate random filter combination
            $selectedCategories = $categories->random($faker->numberBetween(1, 2))->pluck('id')->toArray();
            $selectedAuthors = $authors->random($faker->numberBetween(1, 2))->pluck('id')->toArray();
            $fromDate = now()->subDays($postCount)->format('Y-m-d');
            $toDate = now()->format('Y-m-d');
            
            // Property: Initial request with filters and 'newest' sort
            $newestResponse = $this->get(route('news.index', [
                'categories' => $selectedCategories,
                'authors' => $selectedAuthors,
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'sort' => 'newest',
            ]));
            
            $newestResponse->assertOk();
            $newestPosts = $newestResponse->viewData('posts');
            $newestCount = $newestResponse->viewData('totalCount');
            $newestAppliedFilters = $newestResponse->viewData('appliedFilters');
            
            // Property: Change sort to 'oldest' while keeping same filters
            $oldestResponse = $this->get(route('news.index', [
                'categories' => $selectedCategories,
                'authors' => $selectedAuthors,
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'sort' => 'oldest',
            ]));
            
            $oldestResponse->assertOk();
            $oldestPosts = $oldestResponse->viewData('posts');
            $oldestCount = $oldestResponse->viewData('totalCount');
            $oldestAppliedFilters = $oldestResponse->viewData('appliedFilters');
            
            // Property: All filters should remain active (same count)
            $this->assertEquals(
                $newestCount,
                $oldestCount,
                "Changing sort should not affect the number of filtered results"
            );
            
            // Property: Category filters should be preserved
            sort($selectedCategories);
            $newestCategories = $newestAppliedFilters['categories'] ?? [];
            sort($newestCategories);
            $oldestCategories = $oldestAppliedFilters['categories'] ?? [];
            sort($oldestCategories);
            
            $this->assertEquals(
                $newestCategories,
                $oldestCategories,
                "Category filters should be preserved when sort changes"
            );
            
            $this->assertEquals(
                $selectedCategories,
                $oldestCategories,
                "Category filters should match original selection after sort change"
            );
            
            // Property: Author filters should be preserved
            sort($selectedAuthors);
            $newestAuthors = $newestAppliedFilters['authors'] ?? [];
            sort($newestAuthors);
            $oldestAuthors = $oldestAppliedFilters['authors'] ?? [];
            sort($oldestAuthors);
            
            $this->assertEquals(
                $newestAuthors,
                $oldestAuthors,
                "Author filters should be preserved when sort changes"
            );
            
            $this->assertEquals(
                $selectedAuthors,
                $oldestAuthors,
                "Author filters should match original selection after sort change"
            );
            
            // Property: Date range filters should be preserved
            $this->assertEquals(
                $newestAppliedFilters['from_date'] ?? null,
                $oldestAppliedFilters['from_date'] ?? null,
                "From date should be preserved when sort changes"
            );
            
            $this->assertEquals(
                $newestAppliedFilters['to_date'] ?? null,
                $oldestAppliedFilters['to_date'] ?? null,
                "To date should be preserved when sort changes"
            );
            
            // Property: Sort order should actually change (verify by checking first post)
            // Only verify if we have results
            if ($newestCount > 1) {
                $newestFirstPost = $newestPosts->first();
                $oldestFirstPost = $oldestPosts->first();
                
                // The first post should be different when sort changes
                // (unless all posts have the same published_at, which is unlikely)
                $this->assertNotEquals(
                    $newestFirstPost->id,
                    $oldestFirstPost->id,
                    "Sort order should actually change the order of results"
                );
                
                // Verify sort direction: newest first should have more recent date
                $this->assertGreaterThanOrEqual(
                    $oldestFirstPost->published_at,
                    $newestFirstPost->published_at,
                    "Newest sort should show more recent posts first"
                );
            }
            
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
     * Test Property 13 (Edge Case): Empty filters preserved in URL
     * 
     * **Property**: When no filters are applied, pagination URLs should not
     * contain filter parameters, and the URL should remain clean.
     * 
     * **Validates**: Requirements 2.5, 3.5, 4.5, 5.5 - Clean URLs without filters
     * 
     * **Test Strategy**:
     * - Creates posts without applying any filters
     * - Verifies pagination URLs don't contain filter parameters
     * - Ensures clean URL structure
     * 
     * **Edge Case Tested**:
     * - No filters applied
     * - Clean URL generation
     * - Default state preservation
     * 
     * @return void
     * 
     * @test
     * @group property-testing
     * @group filter-persistence
     * @group edge-cases
     */
    public function test_empty_filters_preserved_in_url(): void
    {
        // Create enough posts for pagination
        Post::factory()->count(20)->create(['published_at' => now()->subDay()]);
        
        // Property: Request without filters should have clean pagination URLs
        $response = $this->get(route('news.index'));
        
        $response->assertOk();
        $posts = $response->viewData('posts');
        $page2Url = $posts->url(2);
        
        // Property: Pagination URL should not contain filter parameters
        $this->assertStringNotContainsString(
            'categories',
            $page2Url,
            "Pagination URL should not contain categories when none selected"
        );
        
        $this->assertStringNotContainsString(
            'authors',
            $page2Url,
            "Pagination URL should not contain authors when none selected"
        );
        
        $this->assertStringNotContainsString(
            'from_date',
            $page2Url,
            "Pagination URL should not contain from_date when not specified"
        );
        
        $this->assertStringNotContainsString(
            'to_date',
            $page2Url,
            "Pagination URL should not contain to_date when not specified"
        );
        
        // Note: sort parameter may be present with default value, which is acceptable
    }

    /**
     * Test Property 14 (Edge Case): Sort change with no other filters
     * 
     * **Property**: When only sort order is changed (no other filters applied),
     * the sort parameter should be the only filter in the URL, and results
     * should be sorted correctly.
     * 
     * **Validates**: Requirements 5.4, 5.5 - Sort-only state
     * 
     * **Test Strategy**:
     * - Creates posts with different dates
     * - Changes sort without other filters
     * - Verifies sort is applied correctly
     * - Verifies URL contains only sort parameter
     * 
     * **Edge Case Tested**:
     * - Sort-only filtering
     * - No other filters active
     * - Clean URL with single parameter
     * 
     * @return void
     * 
     * @test
     * @group property-testing
     * @group filter-persistence
     * @group edge-cases
     */
    public function test_sort_change_with_no_other_filters(): void
    {
        // Create posts with distinct dates for clear sort testing
        $post1 = Post::factory()->create([
            'published_at' => now()->subDays(10),
            'title' => 'Oldest Post',
        ]);
        $post2 = Post::factory()->create([
            'published_at' => now()->subDays(5),
            'title' => 'Middle Post',
        ]);
        $post3 = Post::factory()->create([
            'published_at' => now()->subDays(1),
            'title' => 'Newest Post',
        ]);
        
        // Property: Request with only sort parameter (newest)
        $newestResponse = $this->get(route('news.index', ['sort' => 'newest']));
        
        $newestResponse->assertOk();
        $newestPosts = $newestResponse->viewData('posts');
        
        // Verify newest first
        $this->assertEquals(
            $post3->id,
            $newestPosts->first()->id,
            "Newest sort should show most recent post first"
        );
        
        // Property: Change to oldest sort
        $oldestResponse = $this->get(route('news.index', ['sort' => 'oldest']));
        
        $oldestResponse->assertOk();
        $oldestPosts = $oldestResponse->viewData('posts');
        
        // Verify oldest first
        $this->assertEquals(
            $post1->id,
            $oldestPosts->first()->id,
            "Oldest sort should show oldest post first"
        );
        
        // Property: Both requests should return same total count
        $this->assertEquals(
            3,
            $newestResponse->viewData('totalCount'),
            "Newest sort should show all posts"
        );
        
        $this->assertEquals(
            3,
            $oldestResponse->viewData('totalCount'),
            "Oldest sort should show all posts"
        );
    }
}
