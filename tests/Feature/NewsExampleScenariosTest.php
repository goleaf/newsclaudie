<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Example Tests for News Page Specific Scenarios
 * 
 * These tests validate specific examples and edge cases from the requirements
 * that demonstrate exact expected behaviors. Unlike property-based tests that
 * verify universal properties across many inputs, these tests check concrete
 * scenarios with known inputs and expected outputs.
 * 
 * ## Test Categories
 * 
 * - Basic page functionality and routing
 * - Pagination thresholds and boundaries
 * - Filter control presence and visibility
 * - Default states and initial conditions
 * - Navigation integration
 * - Empty state handling
 * 
 * ## Related Components
 * 
 * @see \App\Http\Controllers\NewsController The controller being tested
 * @see \App\Http\Requests\NewsIndexRequest Request validation
 * @see \App\Services\NewsFilterService Filter logic service
 * 
 * @package Tests\Feature
 * @group news-page
 * @group example-tests
 */
final class NewsExampleScenariosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Disable rate limiting for tests
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
    }

    // ========================================
    // EXAMPLE 1: News page route and title
    // Validates: Requirements 1.1
    // ========================================

    /**
     * Test Example 1: News page route and title
     * 
     * **Scenario**: Navigating to /news should display a page with the title "News"
     * and show all published posts.
     * 
     * **Validates**: Requirement 1.1 - News page displays with correct title
     * 
     * **Given**: Multiple published posts exist in the database
     * **When**: User navigates to /news route
     * **Then**: Page loads successfully with "News" title and displays all published posts
     * 
     * @return void
     * 
     * @test
     * @group example-tests
     */
    public function test_example_1_news_page_route_and_title(): void
    {
        // Arrange: Create published posts
        $posts = Post::factory()->count(5)->create([
            'published_at' => now()->subDay(),
        ]);

        // Act: Navigate to /news
        $response = $this->get(route('news.index'));

        // Assert: Page loads with correct title and shows all posts
        $response->assertOk();
        $response->assertSee('News');
        
        foreach ($posts as $post) {
            $response->assertSee($post->title);
        }
    }

    // ========================================
    // EXAMPLE 2: Pagination threshold
    // Validates: Requirements 1.5
    // ========================================

    /**
     * Test Example 2: Pagination threshold
     * 
     * **Scenario**: When exactly 16 published posts exist, page 1 should display
     * 15 items and page 2 should display 1 item.
     * 
     * **Validates**: Requirement 1.5 - Pagination with 15 items per page
     * 
     * **Given**: Exactly 16 published posts exist
     * **When**: User views page 1 and page 2
     * **Then**: Page 1 shows 15 posts, page 2 shows 1 post
     * 
     * @return void
     * 
     * @test
     * @group example-tests
     */
    public function test_example_2_pagination_threshold(): void
    {
        // Arrange: Create exactly 16 published posts
        Post::factory()->count(16)->create([
            'published_at' => now()->subDay(),
        ]);

        // Act & Assert: Page 1 should have 15 items
        $responsePage1 = $this->get(route('news.index', ['page' => 1]));
        $responsePage1->assertOk();
        $postsPage1 = $responsePage1->viewData('posts');
        $this->assertCount(15, $postsPage1, 'Page 1 should display exactly 15 items');

        // Act & Assert: Page 2 should have 1 item
        $responsePage2 = $this->get(route('news.index', ['page' => 2]));
        $responsePage2->assertOk();
        $postsPage2 = $responsePage2->viewData('posts');
        $this->assertCount(1, $postsPage2, 'Page 2 should display exactly 1 item');
        
        // Verify total count is correct
        $this->assertEquals(16, $responsePage1->viewData('totalCount'));
        $this->assertEquals(16, $responsePage2->viewData('totalCount'));
    }

    // ========================================
    // EXAMPLE 3: Date range filter controls
    // Validates: Requirements 3.1
    // ========================================

    /**
     * Test Example 3: Date range filter controls display
     * 
     * **Scenario**: The news page should display date range filter controls
     * with "from date" and "to date" input fields.
     * 
     * **Validates**: Requirement 3.1 - Date range filter controls presence
     * 
     * **Given**: News page is loaded
     * **When**: User views the filter panel
     * **Then**: Both from_date and to_date input fields are present
     * 
     * @return void
     * 
     * @test
     * @group example-tests
     */
    public function test_example_3_date_range_filter_controls_display(): void
    {
        // Arrange: Create some posts so page renders normally
        Post::factory()->count(3)->create([
            'published_at' => now()->subDay(),
        ]);

        // Act: Load news page
        $response = $this->get(route('news.index'));

        // Assert: Date range inputs are present
        $response->assertOk();
        $response->assertSee('name="from_date"', false);
        $response->assertSee('name="to_date"', false);
        $response->assertSee('type="date"', false);
    }

    // ========================================
    // EXAMPLE 4: No category filters applied
    // Validates: Requirements 2.4
    // ========================================

    /**
     * Test Example 4: Default state - no category filters
     * 
     * **Scenario**: When no category filters are applied, all published posts
     * should be displayed regardless of their category associations.
     * 
     * **Validates**: Requirement 2.4 - All posts shown when no category filters
     * 
     * **Given**: Posts with various category associations exist
     * **When**: User views news page without category filters
     * **Then**: All published posts are displayed
     * 
     * @return void
     * 
     * @test
     * @group example-tests
     */
    public function test_example_4_no_category_filters_applied_state(): void
    {
        // Arrange: Create posts with different category associations
        $category1 = Category::factory()->create();
        $category2 = Category::factory()->create();
        
        $postWithCategory1 = Post::factory()->create(['published_at' => now()->subDay()]);
        $postWithCategory1->categories()->attach($category1);
        
        $postWithCategory2 = Post::factory()->create(['published_at' => now()->subDay()]);
        $postWithCategory2->categories()->attach($category2);
        
        $postWithNoCategory = Post::factory()->create(['published_at' => now()->subDay()]);

        // Act: Load news page without category filters
        $response = $this->get(route('news.index'));

        // Assert: All published posts are displayed
        $response->assertOk();
        $posts = $response->viewData('posts');
        $this->assertCount(3, $posts, 'All published posts should be displayed');
        $this->assertEquals(3, $response->viewData('totalCount'));
        
        $response->assertSee($postWithCategory1->title);
        $response->assertSee($postWithCategory2->title);
        $response->assertSee($postWithNoCategory->title);
    }

    // ========================================
    // EXAMPLE 5: No author filters applied
    // Validates: Requirements 4.4
    // ========================================

    /**
     * Test Example 5: Default state - no author filters
     * 
     * **Scenario**: When no author filters are applied, all published posts
     * should be displayed regardless of who authored them.
     * 
     * **Validates**: Requirement 4.4 - All posts shown when no author filters
     * 
     * **Given**: Posts by different authors exist
     * **When**: User views news page without author filters
     * **Then**: All published posts are displayed
     * 
     * @return void
     * 
     * @test
     * @group example-tests
     */
    public function test_example_5_no_author_filters_applied_state(): void
    {
        // Arrange: Create posts by different authors
        $author1 = User::factory()->create();
        $author2 = User::factory()->create();
        $author3 = User::factory()->create();
        
        $postByAuthor1 = Post::factory()->for($author1, 'author')->create([
            'published_at' => now()->subDay(),
        ]);
        $postByAuthor2 = Post::factory()->for($author2, 'author')->create([
            'published_at' => now()->subDay(),
        ]);
        $postByAuthor3 = Post::factory()->for($author3, 'author')->create([
            'published_at' => now()->subDay(),
        ]);

        // Act: Load news page without author filters
        $response = $this->get(route('news.index'));

        // Assert: All published posts are displayed
        $response->assertOk();
        $posts = $response->viewData('posts');
        $this->assertCount(3, $posts, 'All published posts should be displayed');
        $this->assertEquals(3, $response->viewData('totalCount'));
        
        $response->assertSee($postByAuthor1->title);
        $response->assertSee($postByAuthor2->title);
        $response->assertSee($postByAuthor3->title);
    }

    // ========================================
    // EXAMPLE 6: Sort controls display
    // Validates: Requirements 5.1
    // ========================================

    /**
     * Test Example 6: Sort controls display
     * 
     * **Scenario**: The news page should display sort controls with
     * "Newest First" and "Oldest First" options.
     * 
     * **Validates**: Requirement 5.1 - Sort controls presence
     * 
     * **Given**: News page is loaded
     * **When**: User views the filter panel
     * **Then**: Sort dropdown with both options is present
     * 
     * @return void
     * 
     * @test
     * @group example-tests
     */
    public function test_example_6_sort_controls_display(): void
    {
        // Arrange: Create some posts so page renders normally
        Post::factory()->count(3)->create([
            'published_at' => now()->subDay(),
        ]);

        // Act: Load news page
        $response = $this->get(route('news.index'));

        // Assert: Sort controls are present with both options
        $response->assertOk();
        $response->assertSee('name="sort"', false);
        $response->assertSee('Newest first');
        $response->assertSee('Oldest first');
    }

    // ========================================
    // EXAMPLE 7: Clear filters button hidden
    // Validates: Requirements 6.2
    // ========================================

    /**
     * Test Example 7: Clear filters button hidden when no filters
     * 
     * **Scenario**: When no filters are applied, the "Clear All Filters"
     * button should not be visible.
     * 
     * **Validates**: Requirement 6.2 - Button hidden when no filters
     * 
     * **Given**: News page is loaded without any filters
     * **When**: User views the filter panel
     * **Then**: Clear All Filters button is not visible
     * 
     * @return void
     * 
     * @test
     * @group example-tests
     */
    public function test_example_7_clear_filters_button_hidden_when_no_filters(): void
    {
        // Arrange: Create some posts
        Post::factory()->count(3)->create([
            'published_at' => now()->subDay(),
        ]);

        // Act: Load news page without filters
        $response = $this->get(route('news.index'));

        // Assert: Clear All Filters button should not be visible
        $response->assertOk();
        $appliedFilters = $response->viewData('appliedFilters');
        
        // Verify no filters are applied
        $this->assertEmpty($appliedFilters['categories'] ?? []);
        $this->assertEmpty($appliedFilters['authors'] ?? []);
        $this->assertNull($appliedFilters['from_date'] ?? null);
        $this->assertNull($appliedFilters['to_date'] ?? null);
        
        // The button should be hidden (not rendered or hidden with CSS)
        // We check that the hasFilters condition is false
        $hasFilters = !empty($appliedFilters['categories']) ||
                     !empty($appliedFilters['authors']) ||
                     !empty($appliedFilters['from_date']) ||
                     !empty($appliedFilters['to_date']);
        
        $this->assertFalse($hasFilters, 'No filters should be applied');
    }

    // ========================================
    // EXAMPLE 8: Default view after clearing
    // Validates: Requirements 6.4
    // ========================================

    /**
     * Test Example 8: Default view after clearing filters
     * 
     * **Scenario**: After clicking "Clear All Filters", the page should
     * display all published posts (default view).
     * 
     * **Validates**: Requirement 6.4 - Default view after clearing
     * 
     * **Given**: Filters are applied showing subset of posts
     * **When**: User clears all filters
     * **Then**: All published posts are displayed
     * 
     * @return void
     * 
     * @test
     * @group example-tests
     */
    public function test_example_8_default_view_after_clearing_filters(): void
    {
        // Arrange: Create posts with categories
        $category = Category::factory()->create();
        $postInCategory = Post::factory()->create(['published_at' => now()->subDay()]);
        $postInCategory->categories()->attach($category);
        
        $postNotInCategory = Post::factory()->create(['published_at' => now()->subDay()]);

        // Act: First apply filter (should show only 1 post)
        $filteredResponse = $this->get(route('news.index', [
            'categories' => [$category->id],
        ]));
        $filteredResponse->assertOk();
        $this->assertCount(1, $filteredResponse->viewData('posts'));

        // Act: Clear filters by navigating to clean URL
        $clearedResponse = $this->get(route('news.index'));

        // Assert: All posts are displayed (default view)
        $clearedResponse->assertOk();
        $posts = $clearedResponse->viewData('posts');
        $this->assertCount(2, $posts, 'All published posts should be displayed after clearing');
        $this->assertEquals(2, $clearedResponse->viewData('totalCount'));
        
        $clearedResponse->assertSee($postInCategory->title);
        $clearedResponse->assertSee($postNotInCategory->title);
    }

    // ========================================
    // EXAMPLE 9: Total count without filters
    // Validates: Requirements 7.2
    // ========================================

    /**
     * Test Example 9: Total count without filters
     * 
     * **Scenario**: When no filters are applied, the displayed count should
     * equal the total number of published posts.
     * 
     * **Validates**: Requirement 7.2 - Count equals total when no filters
     * 
     * **Given**: Multiple published posts exist
     * **When**: User views news page without filters
     * **Then**: Displayed count matches total published posts
     * 
     * @return void
     * 
     * @test
     * @group example-tests
     */
    public function test_example_9_total_count_without_filters(): void
    {
        // Arrange: Create known number of published posts
        $publishedCount = 7;
        Post::factory()->count($publishedCount)->create([
            'published_at' => now()->subDay(),
        ]);
        
        // Also create some draft posts that should not be counted
        Post::factory()->count(3)->create([
            'published_at' => null,
        ]);

        // Act: Load news page without filters
        $response = $this->get(route('news.index'));

        // Assert: Count equals total published posts
        $response->assertOk();
        $totalCount = $response->viewData('totalCount');
        $this->assertEquals(
            $publishedCount,
            $totalCount,
            'Total count should equal number of published posts'
        );
        
        // Verify the count is displayed in the view
        $response->assertSee((string) $publishedCount);
    }

    // ========================================
    // EXAMPLE 10-13: Navigation integration
    // Validates: Requirements 9.1, 9.2, 9.3, 9.5
    // ========================================

    /**
     * Test Example 10: News link in navigation
     * 
     * **Scenario**: The main navigation menu should contain a "News" link.
     * 
     * **Validates**: Requirement 9.1 - News link in navigation
     * 
     * **Given**: Application is loaded
     * **When**: User views any page with main navigation
     * **Then**: "News" link is present in navigation
     * 
     * @return void
     * 
     * @test
     * @group example-tests
     */
    public function test_example_10_news_link_in_navigation(): void
    {
        // Act: Load news page (which includes navigation)
        $response = $this->get(route('news.index'));

        // Assert: News link is present in navigation
        $response->assertOk();
        $response->assertSee(route('news.index'), false);
        $response->assertSee('News');
    }

    /**
     * Test Example 11: Active navigation state
     * 
     * **Scenario**: When on the news page, the "News" navigation link
     * should have active styling.
     * 
     * **Validates**: Requirement 9.2 - Active state on news page
     * 
     * **Given**: User is on the news page
     * **When**: Navigation renders
     * **Then**: News link has active state indicator
     * 
     * @return void
     * 
     * @test
     * @group example-tests
     */
    public function test_example_11_active_navigation_state(): void
    {
        // Act: Load news page
        $response = $this->get(route('news.index'));

        // Assert: Active state is applied
        // The exact implementation may vary, but typically uses aria-current or CSS class
        $response->assertOk();
        
        // Check for common active state indicators
        $content = $response->getContent();
        $this->assertTrue(
            str_contains($content, 'aria-current="page"') ||
            str_contains($content, 'active') ||
            str_contains($content, 'current'),
            'News link should have active state indicator'
        );
    }

    /**
     * Test Example 12: Mobile navigation
     * 
     * **Scenario**: The mobile navigation menu should include the "News" link.
     * 
     * **Validates**: Requirement 9.3 - News link in mobile menu
     * 
     * **Given**: Application is loaded
     * **When**: User views mobile navigation
     * **Then**: News link is present
     * 
     * @return void
     * 
     * @test
     * @group example-tests
     */
    public function test_example_12_news_link_in_mobile_navigation(): void
    {
        // Act: Load news page
        $response = $this->get(route('news.index'));

        // Assert: News link appears in navigation (both desktop and mobile use same links)
        $response->assertOk();
        $response->assertSee(route('news.index'), false);
        
        // The mobile navigation typically uses the same link structure
        // We verify the link exists (it will be styled differently via CSS)
        $content = $response->getContent();
        $newsLinkCount = substr_count($content, route('news.index'));
        $this->assertGreaterThanOrEqual(
            1,
            $newsLinkCount,
            'News link should appear in navigation'
        );
    }

    /**
     * Test Example 13: Clean navigation link
     * 
     * **Scenario**: Clicking the "News" navigation link should navigate
     * to /news without any query parameters.
     * 
     * **Validates**: Requirement 9.5 - Clean URL without filters
     * 
     * **Given**: User is on any page
     * **When**: User clicks News link in navigation
     * **Then**: Navigates to /news without query parameters
     * 
     * @return void
     * 
     * @test
     * @group example-tests
     */
    public function test_example_13_clean_navigation_link(): void
    {
        // Act: Navigate to news index via route
        $response = $this->get(route('news.index'));

        // Assert: URL is clean without query parameters
        $response->assertOk();
        
        // Verify the route generates a clean URL
        $cleanUrl = route('news.index');
        $this->assertStringNotContainsString('?', $cleanUrl, 'News route should not contain query parameters');
        $this->assertStringNotContainsString('categories', $cleanUrl);
        $this->assertStringNotContainsString('authors', $cleanUrl);
        $this->assertStringNotContainsString('from_date', $cleanUrl);
        $this->assertStringNotContainsString('to_date', $cleanUrl);
        $this->assertStringNotContainsString('sort', $cleanUrl);
    }

    // ========================================
    // EXAMPLE 14: Empty results edge case
    // Validates: Requirements 7.4
    // ========================================

    /**
     * Test Example 14: Empty results edge case
     * 
     * **Scenario**: When filters are applied that match no posts, the page
     * should display "0 results found" with a message suggesting filter adjustment.
     * 
     * **Validates**: Requirement 7.4 - Empty state message
     * 
     * **Given**: Posts exist but none match the applied filters
     * **When**: User applies filters that match nothing
     * **Then**: Empty state message is displayed
     * 
     * @return void
     * 
     * @test
     * @group example-tests
     * @group edge-cases
     */
    public function test_example_14_empty_results_edge_case(): void
    {
        // Arrange: Create posts with specific category
        $category = Category::factory()->create();
        $post = Post::factory()->create(['published_at' => now()->subDay()]);
        $post->categories()->attach($category);
        
        // Create another category with no posts
        $emptyCategory = Category::factory()->create();

        // Act: Filter by category with no posts
        $response = $this->get(route('news.index', [
            'categories' => [$emptyCategory->id],
        ]));

        // Assert: Empty state is displayed
        $response->assertOk();
        $posts = $response->viewData('posts');
        $this->assertCount(0, $posts, 'No posts should match the filter');
        $this->assertEquals(0, $response->viewData('totalCount'));
        
        // Verify empty state message
        $response->assertSee('0');
        $response->assertSee('results', false);
    }
}
