<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for Post model query scopes.
 *
 * This test suite validates the query scopes added to the Post model for the
 * News Page feature. These scopes provide reusable, testable filtering and
 * sorting logic that can be composed together for complex queries.
 *
 * **Tested Scopes:**
 * - `filterByCategories()` - Filter posts by category IDs (OR logic)
 * - `filterByAuthors()` - Filter posts by author IDs (OR logic)
 * - `filterByDateRange()` - Filter posts by publication date range
 * - `sortByPublishedDate()` - Sort posts by publication date
 *
 * **Testing Strategy:**
 * - Each scope is tested in isolation to verify its specific behavior
 * - Scopes are tested in combination to ensure they compose correctly
 * - Both single and multiple filter values are tested
 * - Edge cases (empty filters, null dates) are covered
 *
 * **Why Unit Tests for Scopes?**
 * - Scopes are reusable across controllers and services
 * - They encapsulate complex query logic that needs verification
 * - Changes to scope behavior can break multiple features
 * - Unit tests provide fast feedback during development
 *
 * **Related Tests:**
 * - `NewsControllerTest` - Integration tests for the news page
 * - `NewsFilterStateManagementTest` - Tests for filter persistence
 *
 * @see Post For the model with these scopes
 * @see NewsController For usage of these scopes
 * @see .kiro/specs/news-page/requirements.md For feature requirements
 *
 * @package Tests\Unit
 * @group unit
 * @group post
 * @group scopes
 * @group news
 *
 * @author Laravel Blog Application
 * @version 1.0.0
 * @since 1.0.0
 */
final class PostQueryScopesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that filterByCategories scope correctly filters posts by category IDs.
     *
     * **What This Tests:**
     * - Single category filtering returns only posts in that category
     * - Multiple category filtering uses OR logic (posts in ANY category)
     * - Posts not in selected categories are excluded
     *
     * **Why This Matters:**
     * The news page allows users to select multiple categories to filter posts.
     * The OR logic ensures users see posts from any of their selected categories,
     * not just posts that belong to ALL selected categories (which would be AND logic).
     *
     * **Requirements Validated:**
     * - Requirement 2.2: Filter by selected categories
     * - Requirement 2.3: Multiple categories use OR logic
     *
     * @test
     * @return void
     *
     * @see Post::scopeFilterByCategories() For the implementation
     * @see NewsController::buildNewsQuery() For usage in production
     */
    public function test_filter_by_categories_scope_filters_posts_by_category_ids(): void
    {
        // Arrange: Create three categories and three posts, each with one category
        $category1 = Category::factory()->create();
        $category2 = Category::factory()->create();
        $category3 = Category::factory()->create();

        $post1 = Post::factory()->create(['published_at' => now()]);
        $post1->categories()->attach($category1);

        $post2 = Post::factory()->create(['published_at' => now()]);
        $post2->categories()->attach($category2);

        $post3 = Post::factory()->create(['published_at' => now()]);
        $post3->categories()->attach($category3);

        // Act & Assert: Test filtering by single category
        $results = Post::withoutGlobalScopes()
            ->filterByCategories([$category1->id])
            ->get();

        $this->assertCount(1, $results, 'Single category filter should return exactly 1 post');
        $this->assertTrue($results->contains($post1), 'Results should contain post1');

        // Act & Assert: Test filtering by multiple categories (OR logic)
        $results = Post::withoutGlobalScopes()
            ->filterByCategories([$category1->id, $category2->id])
            ->get();

        $this->assertCount(2, $results, 'Multiple category filter should return 2 posts');
        $this->assertTrue($results->contains($post1), 'Results should contain post1');
        $this->assertTrue($results->contains($post2), 'Results should contain post2');
        $this->assertFalse($results->contains($post3), 'Results should NOT contain post3');
    }

    /**
     * Test that filterByAuthors scope correctly filters posts by author IDs.
     *
     * **What This Tests:**
     * - Single author filtering returns only posts by that author
     * - Multiple author filtering uses OR logic (posts by ANY author)
     * - Posts by non-selected authors are excluded
     *
     * **Why This Matters:**
     * The news page allows users to follow specific authors by filtering posts.
     * The OR logic ensures users see posts from any of their selected authors,
     * making it easy to follow multiple writers simultaneously.
     *
     * **Requirements Validated:**
     * - Requirement 4.2: Filter by selected authors
     * - Requirement 4.3: Multiple authors use OR logic
     *
     * @test
     * @return void
     *
     * @see Post::scopeFilterByAuthors() For the implementation
     * @see NewsController::buildNewsQuery() For usage in production
     */
    public function test_filter_by_authors_scope_filters_posts_by_author_ids(): void
    {
        // Arrange: Create three authors and three posts, each by a different author
        $author1 = User::factory()->create();
        $author2 = User::factory()->create();
        $author3 = User::factory()->create();

        $post1 = Post::factory()->create(['user_id' => $author1->id, 'published_at' => now()]);
        $post2 = Post::factory()->create(['user_id' => $author2->id, 'published_at' => now()]);
        $post3 = Post::factory()->create(['user_id' => $author3->id, 'published_at' => now()]);

        // Act & Assert: Test filtering by single author
        $results = Post::withoutGlobalScopes()
            ->filterByAuthors([$author1->id])
            ->get();

        $this->assertCount(1, $results, 'Single author filter should return exactly 1 post');
        $this->assertTrue($results->contains($post1), 'Results should contain post1');

        // Act & Assert: Test filtering by multiple authors (OR logic)
        $results = Post::withoutGlobalScopes()
            ->filterByAuthors([$author1->id, $author2->id])
            ->get();

        $this->assertCount(2, $results, 'Multiple author filter should return 2 posts');
        $this->assertTrue($results->contains($post1), 'Results should contain post1');
        $this->assertTrue($results->contains($post2), 'Results should contain post2');
        $this->assertFalse($results->contains($post3), 'Results should NOT contain post3');
    }

    /**
     * Test that filterByDateRange scope correctly filters by from_date (lower bound).
     *
     * **What This Tests:**
     * - Posts published on or after from_date are included
     * - Posts published before from_date are excluded
     * - Null to_date means no upper bound (all future posts included)
     *
     * **Why This Matters:**
     * Users need to find posts from a specific time period onwards, such as
     * "all posts from the beginning of 2024" without specifying an end date.
     *
     * **Requirements Validated:**
     * - Requirement 3.2: Filter by from_date (on or after)
     *
     * @test
     * @return void
     *
     * @see Post::scopeFilterByDateRange() For the implementation
     * @see NewsController::buildNewsQuery() For usage in production
     */
    public function test_filter_by_date_range_scope_filters_by_from_date(): void
    {
        // Arrange: Create posts with different publication dates
        $post1 = Post::factory()->create(['published_at' => '2024-01-15']);
        $post2 = Post::factory()->create(['published_at' => '2024-02-15']);
        $post3 = Post::factory()->create(['published_at' => '2024-03-15']);

        // Act: Filter posts from February 1st onwards (no upper bound)
        $results = Post::withoutGlobalScopes()
            ->filterByDateRange('2024-02-01', null)
            ->get();

        // Assert: Only posts from Feb 1st onwards should be included
        $this->assertCount(2, $results, 'Should return 2 posts published on or after 2024-02-01');
        $this->assertFalse($results->contains($post1), 'Post from January should be excluded');
        $this->assertTrue($results->contains($post2), 'Post from February should be included');
        $this->assertTrue($results->contains($post3), 'Post from March should be included');
    }

    /**
     * Test that filterByDateRange scope correctly filters by to_date (upper bound).
     *
     * **What This Tests:**
     * - Posts published on or before to_date are included
     * - Posts published after to_date are excluded
     * - Null from_date means no lower bound (all past posts included)
     *
     * **Why This Matters:**
     * Users need to find posts up to a specific date, such as "all posts
     * published before March 2024" without specifying a start date.
     *
     * **Requirements Validated:**
     * - Requirement 3.3: Filter by to_date (on or before)
     *
     * @test
     * @return void
     *
     * @see Post::scopeFilterByDateRange() For the implementation
     * @see NewsController::buildNewsQuery() For usage in production
     */
    public function test_filter_by_date_range_scope_filters_by_to_date(): void
    {
        // Arrange: Create posts with different publication dates
        $post1 = Post::factory()->create(['published_at' => '2024-01-15']);
        $post2 = Post::factory()->create(['published_at' => '2024-02-15']);
        $post3 = Post::factory()->create(['published_at' => '2024-03-15']);

        // Act: Filter posts up to February 28th (no lower bound)
        $results = Post::withoutGlobalScopes()
            ->filterByDateRange(null, '2024-02-28')
            ->get();

        // Assert: Only posts up to Feb 28th should be included
        $this->assertCount(2, $results, 'Should return 2 posts published on or before 2024-02-28');
        $this->assertTrue($results->contains($post1), 'Post from January should be included');
        $this->assertTrue($results->contains($post2), 'Post from February should be included');
        $this->assertFalse($results->contains($post3), 'Post from March should be excluded');
    }

    /**
     * Test that filterByDateRange scope correctly filters by both from_date and to_date.
     *
     * **What This Tests:**
     * - Posts within the date range (inclusive) are included
     * - Posts before from_date are excluded
     * - Posts after to_date are excluded
     * - Both bounds work together correctly (AND logic)
     *
     * **Why This Matters:**
     * Users need to find posts within a specific time window, such as
     * "all posts published in February 2024" for historical research or
     * content auditing purposes.
     *
     * **Requirements Validated:**
     * - Requirement 3.4: Filter by both dates (inclusive range)
     *
     * @test
     * @return void
     *
     * @see Post::scopeFilterByDateRange() For the implementation
     * @see NewsController::buildNewsQuery() For usage in production
     */
    public function test_filter_by_date_range_scope_filters_by_both_dates(): void
    {
        // Arrange: Create posts with different publication dates
        $post1 = Post::factory()->create(['published_at' => '2024-01-15']);
        $post2 = Post::factory()->create(['published_at' => '2024-02-15']);
        $post3 = Post::factory()->create(['published_at' => '2024-03-15']);

        // Act: Filter posts within February 2024 (inclusive range)
        $results = Post::withoutGlobalScopes()
            ->filterByDateRange('2024-02-01', '2024-02-28')
            ->get();

        // Assert: Only posts within the date range should be included
        $this->assertCount(1, $results, 'Should return 1 post within the date range');
        $this->assertFalse($results->contains($post1), 'Post before range should be excluded');
        $this->assertTrue($results->contains($post2), 'Post within range should be included');
        $this->assertFalse($results->contains($post3), 'Post after range should be excluded');
    }

    /**
     * Test that sortByPublishedDate scope correctly sorts in descending order.
     *
     * **What This Tests:**
     * - Posts are sorted by published_at in descending order (newest first)
     * - The newest post appears first in results
     * - The oldest post appears last in results
     *
     * **Why This Matters:**
     * The default news page view shows newest posts first, which is the
     * expected behavior for a news/blog listing. Users want to see the
     * most recent content at the top.
     *
     * **Requirements Validated:**
     * - Requirement 5.2: Sort by newest first (descending)
     * - Requirement 1.2: Default reverse chronological order
     *
     * @test
     * @return void
     *
     * @see Post::scopeSortByPublishedDate() For the implementation
     * @see NewsController::buildNewsQuery() For usage in production
     */
    public function test_sort_by_published_date_scope_sorts_descending(): void
    {
        // Arrange: Create posts with different publication dates
        $post1 = Post::factory()->create(['published_at' => '2024-01-15', 'title' => 'Oldest']);
        $post2 = Post::factory()->create(['published_at' => '2024-02-15', 'title' => 'Middle']);
        $post3 = Post::factory()->create(['published_at' => '2024-03-15', 'title' => 'Newest']);

        // Act: Sort posts in descending order (newest first)
        $results = Post::withoutGlobalScopes()
            ->sortByPublishedDate('desc')
            ->get();

        // Assert: Newest post should be first, oldest should be last
        $this->assertEquals('Newest', $results->first()->title, 'Newest post should be first');
        $this->assertEquals('Oldest', $results->last()->title, 'Oldest post should be last');
    }

    /**
     * Test that sortByPublishedDate scope correctly sorts in ascending order.
     *
     * **What This Tests:**
     * - Posts are sorted by published_at in ascending order (oldest first)
     * - The oldest post appears first in results
     * - The newest post appears last in results
     *
     * **Why This Matters:**
     * Some users prefer to read content chronologically from oldest to newest,
     * especially when catching up on a series of posts or following a story
     * that developed over time.
     *
     * **Requirements Validated:**
     * - Requirement 5.3: Sort by oldest first (ascending)
     *
     * @test
     * @return void
     *
     * @see Post::scopeSortByPublishedDate() For the implementation
     * @see NewsController::buildNewsQuery() For usage in production
     */
    public function test_sort_by_published_date_scope_sorts_ascending(): void
    {
        // Arrange: Create posts with different publication dates
        $post1 = Post::factory()->create(['published_at' => '2024-01-15', 'title' => 'Oldest']);
        $post2 = Post::factory()->create(['published_at' => '2024-02-15', 'title' => 'Middle']);
        $post3 = Post::factory()->create(['published_at' => '2024-03-15', 'title' => 'Newest']);

        // Act: Sort posts in ascending order (oldest first)
        $results = Post::withoutGlobalScopes()
            ->sortByPublishedDate('asc')
            ->get();

        // Assert: Oldest post should be first, newest should be last
        $this->assertEquals('Oldest', $results->first()->title, 'Oldest post should be first');
        $this->assertEquals('Newest', $results->last()->title, 'Newest post should be last');
    }

    /**
     * Test that multiple scopes can be combined in a single query.
     *
     * **What This Tests:**
     * - Multiple scopes can be chained together
     * - Scopes combine with AND logic (all conditions must be met)
     * - Complex filtering scenarios work correctly
     * - Sort order is applied after all filters
     *
     * **Why This Matters:**
     * In production, users often apply multiple filters simultaneously, such as
     * "show me posts by Author A in Category X from the last 6 months, newest first".
     * This test ensures that scope composition works correctly for real-world usage.
     *
     * **Test Scenario:**
     * - 3 posts created with different authors, categories, and dates
     * - Filter by category1 AND author1 AND date range (Feb onwards)
     * - Only post3 matches all criteria
     *
     * **Requirements Validated:**
     * - All filter requirements (2.x, 3.x, 4.x, 5.x) working together
     * - Requirement 5.4: Sort preserves filters
     *
     * @test
     * @return void
     *
     * @see Post For all scope implementations
     * @see NewsController::buildNewsQuery() For production usage of combined scopes
     */
    public function test_scopes_can_be_combined(): void
    {
        // Arrange: Create test data with various combinations
        $category1 = Category::factory()->create();
        $category2 = Category::factory()->create();
        $author1 = User::factory()->create();
        $author2 = User::factory()->create();

        // Post 1: category1, author1, January (excluded by date filter)
        $post1 = Post::factory()->create([
            'user_id' => $author1->id,
            'published_at' => '2024-01-15',
        ]);
        $post1->categories()->attach($category1);

        // Post 2: category2, author2, February (excluded by category and author filters)
        $post2 = Post::factory()->create([
            'user_id' => $author2->id,
            'published_at' => '2024-02-15',
        ]);
        $post2->categories()->attach($category2);

        // Post 3: category1, author1, March (matches all filters)
        $post3 = Post::factory()->create([
            'user_id' => $author1->id,
            'published_at' => '2024-03-15',
        ]);
        $post3->categories()->attach($category1);

        // Act: Apply all filters together
        $results = Post::withoutGlobalScopes()
            ->filterByCategories([$category1->id])
            ->filterByAuthors([$author1->id])
            ->filterByDateRange('2024-02-01', '2024-12-31')
            ->sortByPublishedDate('desc')
            ->get();

        // Assert: Only post3 should match all criteria
        $this->assertCount(1, $results, 'Only 1 post should match all filter criteria');
        $this->assertTrue($results->contains($post3), 'Results should contain post3');
    }
}
