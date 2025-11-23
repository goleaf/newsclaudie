<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Services\NewsFilterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property-Based Tests for News Filter Options
 * 
 * These tests verify universal properties that must hold true for the news filter
 * system regardless of database state. The tests use property-based testing to
 * validate behavior across many randomized scenarios.
 * 
 * ## Properties Tested
 * 
 * - **Property 4**: Category filter completeness - Only categories with published posts appear
 * - **Property 6**: Author filter completeness - Only authors with published posts appear
 * 
 * ## Testing Approach
 * 
 * Each test runs multiple iterations (10 for database tests to balance coverage and performance)
 * with randomized data to verify that the properties hold across diverse scenarios:
 * 
 * - Random number of entities (0-10 categories/users)
 * - Random associations (70% chance of having posts)
 * - Random post counts (1-5 per entity)
 * - Different publication states (published, draft, future)
 * 
 * ## Related Components
 * 
 * @see \App\Services\NewsFilterService The service being tested
 * @see \App\Models\Category Category model with post relationships
 * @see \App\Models\User User model (authors) with post relationships
 * @see \App\Models\Post Post model with publication state
 * 
 * ## Requirements Validated
 * 
 * - Requirement 2.1: Display all categories with published posts in filter panel
 * - Requirement 4.1: Display all authors with published posts in filter panel
 * 
 * @package Tests\Unit
 * @group property-testing
 * @group news-page
 * @group filters
 */
final class NewsFilterOptionsPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The news filter service instance under test.
     *
     * @var NewsFilterService
     */
    private NewsFilterService $service;

    /**
     * Set up the test environment.
     *
     * Initializes a fresh NewsFilterService instance for each test.
     * Database is refreshed via RefreshDatabase trait.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new NewsFilterService();
    }

    /**
     * Test Property 4: Category filter completeness
     * 
     * **Property**: For any set of categories in the database, the filter options
     * should include all and only those categories that have at least one published post.
     * 
     * **Validates**: Requirement 2.1 - Display all categories with published posts
     * 
     * **Test Strategy**:
     * - Creates 0-10 random categories
     * - Randomly assigns published posts to 70% of categories
     * - Verifies filter options match exactly the categories with posts
     * - Runs 10 iterations with different random scenarios
     * 
     * **Properties Verified**:
     * 1. Filter includes all categories with published posts
     * 2. Filter excludes all categories without published posts
     * 3. Filter count matches expected count
     * 
     * @return void
     * 
     * @test
     * @group property-testing
     * @group news-filters
     */
    public function test_category_filter_completeness(): void
    {
        // Run fewer iterations for database tests to avoid performance issues
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create random number of categories (0-10)
            $totalCategories = $faker->numberBetween(0, 10);
            $categories = Category::factory()->count($totalCategories)->create();
            
            // Randomly assign published posts to some categories
            $categoriesWithPosts = [];
            foreach ($categories as $category) {
                // 70% chance a category has posts
                if ($faker->boolean(70)) {
                    $postCount = $faker->numberBetween(1, 5);
                    $posts = Post::factory()->count($postCount)->create([
                        'published_at' => now()->subDay(),
                    ]);
                    $category->posts()->attach($posts->pluck('id'));
                    $categoriesWithPosts[] = $category->id;
                }
            }
            
            // Property: Filter options should include all and only categories with published posts
            $filterOptions = $this->service->getFilterOptions();
            $returnedCategoryIds = $filterOptions['categories']->pluck('id')->toArray();
            
            // Sort both arrays to ensure order-independent comparison
            sort($categoriesWithPosts);
            sort($returnedCategoryIds);
            
            // Assert exact match: same IDs, same count, no extras
            $this->assertSame(
                $categoriesWithPosts,
                $returnedCategoryIds,
                "Filter options should include exactly the categories with published posts"
            );
            
            // Property: No category without published posts should appear in filter options
            // Calculate set difference: all categories minus those with posts
            $categoriesWithoutPosts = $categories->pluck('id')
                ->diff($categoriesWithPosts)
                ->toArray();
            
            // Verify each category without posts is excluded from filter options
            foreach ($categoriesWithoutPosts as $categoryId) {
                $this->assertNotContains(
                    $categoryId,
                    $returnedCategoryIds,
                    "Category {$categoryId} without published posts should not appear in filter options"
                );
            }
            
            // Property: All categories with published posts should appear in filter options
            foreach ($categoriesWithPosts as $categoryId) {
                $this->assertContains(
                    $categoryId,
                    $returnedCategoryIds,
                    "Category {$categoryId} with published posts should appear in filter options"
                );
            }
            
            // Cleanup
            foreach ($categories as $category) {
                $category->posts()->detach();
                $category->delete();
            }
        }
    }

    /**
     * Test Property 4 (Edge Case): Category filter excludes draft-only categories
     * 
     * **Property**: Categories associated only with draft or future posts should
     * not appear in filter options, only categories with currently published posts.
     * 
     * **Validates**: Requirement 2.1 - Only published posts affect filter options
     * 
     * **Test Strategy**:
     * - Creates three categories with different post states:
     *   1. Category with published posts (should appear)
     *   2. Category with only draft posts (should NOT appear)
     *   3. Category with only future posts (should NOT appear)
     * - Verifies correct inclusion/exclusion
     * - Runs 10 iterations to ensure consistency
     * 
     * **Edge Cases Tested**:
     * - Draft posts (published_at = null)
     * - Future posts (published_at > now)
     * - Published posts (published_at <= now)
     * 
     * @return void
     * 
     * @test
     * @group property-testing
     * @group news-filters
     * @group edge-cases
     */
    public function test_category_filter_excludes_categories_with_only_draft_posts(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create categories with different post states
            $categoryWithPublished = Category::factory()->create();
            $publishedPost = Post::factory()->create(['published_at' => now()->subDay()]);
            $categoryWithPublished->posts()->attach($publishedPost);
            
            $categoryWithDrafts = Category::factory()->create();
            $draftCount = $faker->numberBetween(1, 3);
            $draftPosts = Post::factory()->count($draftCount)->create(['published_at' => null]);
            $categoryWithDrafts->posts()->attach($draftPosts->pluck('id'));
            
            $categoryWithFuture = Category::factory()->create();
            $futurePost = Post::factory()->create(['published_at' => now()->addDay()]);
            $categoryWithFuture->posts()->attach($futurePost);
            
            // Property: Only categories with published posts should appear
            $filterOptions = $this->service->getFilterOptions();
            $returnedCategoryIds = $filterOptions['categories']->pluck('id')->toArray();
            
            $this->assertContains(
                $categoryWithPublished->id,
                $returnedCategoryIds,
                "Category with published posts should appear in filter options"
            );
            
            $this->assertNotContains(
                $categoryWithDrafts->id,
                $returnedCategoryIds,
                "Category with only draft posts should not appear in filter options"
            );
            
            $this->assertNotContains(
                $categoryWithFuture->id,
                $returnedCategoryIds,
                "Category with only future posts should not appear in filter options"
            );
            
            // Cleanup
            $categoryWithPublished->posts()->detach();
            $categoryWithPublished->delete();
            $categoryWithDrafts->posts()->detach();
            $categoryWithDrafts->delete();
            $categoryWithFuture->posts()->detach();
            $categoryWithFuture->delete();
        }
    }

    /**
     * Test Property 6: Author filter completeness
     * 
     * **Property**: For any set of users in the database, the filter options
     * should include all and only those users who have authored at least one
     * published post.
     * 
     * **Validates**: Requirement 4.1 - Display all authors with published posts
     * 
     * **Test Strategy**:
     * - Creates 0-10 random users
     * - Randomly assigns published posts to 70% of users
     * - Verifies filter options match exactly the users with posts
     * - Runs 10 iterations with different random scenarios
     * 
     * **Properties Verified**:
     * 1. Filter includes all users with published posts
     * 2. Filter excludes all users without published posts
     * 3. Filter count matches expected count
     * 
     * @return void
     * 
     * @test
     * @group property-testing
     * @group news-filters
     */
    public function test_author_filter_completeness(): void
    {
        // Run fewer iterations for database tests to avoid performance issues
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create random number of users (0-10)
            $totalUsers = $faker->numberBetween(0, 10);
            $users = User::factory()->count($totalUsers)->create();
            
            // Randomly assign published posts to some users
            $authorsWithPosts = [];
            foreach ($users as $user) {
                // 70% chance a user has posts
                if ($faker->boolean(70)) {
                    $postCount = $faker->numberBetween(1, 5);
                    Post::factory()->count($postCount)->for($user, 'author')->create([
                        'published_at' => now()->subDay(),
                    ]);
                    $authorsWithPosts[] = $user->id;
                }
            }
            
            // Property: Filter options should include all and only users with published posts
            $filterOptions = $this->service->getFilterOptions();
            $returnedAuthorIds = $filterOptions['authors']->pluck('id')->toArray();
            
            sort($authorsWithPosts);
            sort($returnedAuthorIds);
            
            $this->assertSame(
                $authorsWithPosts,
                $returnedAuthorIds,
                "Filter options should include exactly the users with published posts"
            );
            
            // Property: No user without published posts should appear in filter options
            $usersWithoutPosts = $users->pluck('id')
                ->diff($authorsWithPosts)
                ->toArray();
            
            foreach ($usersWithoutPosts as $userId) {
                $this->assertNotContains(
                    $userId,
                    $returnedAuthorIds,
                    "User {$userId} without published posts should not appear in filter options"
                );
            }
            
            // Property: All users with published posts should appear in filter options
            foreach ($authorsWithPosts as $userId) {
                $this->assertContains(
                    $userId,
                    $returnedAuthorIds,
                    "User {$userId} with published posts should appear in filter options"
                );
            }
            
            // Cleanup - posts will be cascade deleted with users
            foreach ($users as $user) {
                $user->delete();
            }
        }
    }

    /**
     * Test Property 6 (Edge Case): Author filter excludes draft-only authors
     * 
     * **Property**: Users who have only authored draft or future posts should
     * not appear in filter options, only users with currently published posts.
     * 
     * **Validates**: Requirement 4.1 - Only published posts affect filter options
     * 
     * **Test Strategy**:
     * - Creates three users with different post states:
     *   1. User with published posts (should appear)
     *   2. User with only draft posts (should NOT appear)
     *   3. User with only future posts (should NOT appear)
     * - Verifies correct inclusion/exclusion
     * - Runs 10 iterations to ensure consistency
     * 
     * **Edge Cases Tested**:
     * - Draft posts (published_at = null)
     * - Future posts (published_at > now)
     * - Published posts (published_at <= now)
     * 
     * @return void
     * 
     * @test
     * @group property-testing
     * @group news-filters
     * @group edge-cases
     */
    public function test_author_filter_excludes_authors_with_only_draft_posts(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create users with different post states
            $authorWithPublished = User::factory()->create();
            Post::factory()->for($authorWithPublished, 'author')->create([
                'published_at' => now()->subDay(),
            ]);
            
            $authorWithDrafts = User::factory()->create();
            $draftCount = $faker->numberBetween(1, 3);
            Post::factory()->count($draftCount)->for($authorWithDrafts, 'author')->create([
                'published_at' => null,
            ]);
            
            $authorWithFuture = User::factory()->create();
            Post::factory()->for($authorWithFuture, 'author')->create([
                'published_at' => now()->addDay(),
            ]);
            
            // Property: Only users with published posts should appear
            $filterOptions = $this->service->getFilterOptions();
            $returnedAuthorIds = $filterOptions['authors']->pluck('id')->toArray();
            
            $this->assertContains(
                $authorWithPublished->id,
                $returnedAuthorIds,
                "User with published posts should appear in filter options"
            );
            
            $this->assertNotContains(
                $authorWithDrafts->id,
                $returnedAuthorIds,
                "User with only draft posts should not appear in filter options"
            );
            
            $this->assertNotContains(
                $authorWithFuture->id,
                $returnedAuthorIds,
                "User with only future posts should not appear in filter options"
            );
            
            // Cleanup
            $authorWithPublished->delete();
            $authorWithDrafts->delete();
            $authorWithFuture->delete();
        }
    }

    /**
     * Test Properties 4 & 6: Filter options consistency (idempotence)
     * 
     * **Property**: For any database state, calling getFilterOptions() multiple
     * times without database changes should return identical results (idempotence).
     * 
     * **Validates**: Requirements 2.1, 4.1 - Consistent filter behavior
     * 
     * **Test Strategy**:
     * - Creates random database state (2-5 categories, 2-5 users, 5-15 posts)
     * - Calls getFilterOptions() three times
     * - Verifies all three calls return identical results
     * - Runs 5 iterations (fewer due to complexity of setup)
     * 
     * **Properties Verified**:
     * 1. Category filters are consistent across calls
     * 2. Author filters are consistent across calls
     * 3. No side effects from reading filter options
     * 
     * **Why This Matters**:
     * Ensures the service is stateless and doesn't modify data during reads,
     * which is critical for caching and concurrent requests.
     * 
     * @return void
     * 
     * @test
     * @group property-testing
     * @group news-filters
     * @group idempotence
     */
    public function test_filter_options_consistency(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $faker = fake();
            
            // Create a random database state
            $categoryCount = $faker->numberBetween(2, 5);
            $userCount = $faker->numberBetween(2, 5);
            
            $categories = Category::factory()->count($categoryCount)->create();
            $users = User::factory()->count($userCount)->create();
            
            // Create posts with random associations to build complex database state
            $postCount = $faker->numberBetween(5, 15);
            foreach (range(1, $postCount) as $j) {
                // Each post gets a random author from the created users
                $post = Post::factory()
                    ->for($users->random(), 'author')
                    ->create(['published_at' => now()->subDay()]);
                
                // Attach 1-3 random categories (but not more than available)
                // This creates realistic many-to-many relationships
                $maxCategories = min(3, $categoryCount);
                $numCategories = $faker->numberBetween(1, $maxCategories);
                $categoriesToAttach = $categories->random($numCategories);
                $post->categories()->attach($categoriesToAttach->pluck('id'));
            }
            
            // Property: Multiple calls should return identical results
            $firstCall = $this->service->getFilterOptions();
            $secondCall = $this->service->getFilterOptions();
            $thirdCall = $this->service->getFilterOptions();
            
            $this->assertEquals(
                $firstCall['categories']->pluck('id')->toArray(),
                $secondCall['categories']->pluck('id')->toArray(),
                "Category filter options should be consistent across calls"
            );
            
            $this->assertEquals(
                $firstCall['authors']->pluck('id')->toArray(),
                $secondCall['authors']->pluck('id')->toArray(),
                "Author filter options should be consistent across calls"
            );
            
            $this->assertEquals(
                $secondCall['categories']->pluck('id')->toArray(),
                $thirdCall['categories']->pluck('id')->toArray(),
                "Category filter options should remain consistent"
            );
            
            $this->assertEquals(
                $secondCall['authors']->pluck('id')->toArray(),
                $thirdCall['authors']->pluck('id')->toArray(),
                "Author filter options should remain consistent"
            );
            
            // Cleanup
            foreach ($categories as $category) {
                $category->posts()->detach();
                $category->delete();
            }
            foreach ($users as $user) {
                $user->delete();
            }
        }
    }

    /**
     * Test Properties 4 & 6: Empty database edge case
     * 
     * **Property**: For an empty database (no posts, categories, or users),
     * getFilterOptions() should return empty collections without errors.
     * 
     * **Validates**: Requirements 2.1, 4.1 - Graceful handling of empty state
     * 
     * **Test Strategy**:
     * - Uses RefreshDatabase to ensure clean state
     * - Calls getFilterOptions() on empty database
     * - Verifies both categories and authors collections are empty
     * 
     * **Edge Case Tested**:
     * - Zero entities in database
     * - No relationships to query
     * - Empty result sets
     * 
     * **Why This Matters**:
     * Ensures the application handles initial state gracefully without
     * throwing exceptions or returning null values.
     * 
     * @return void
     * 
     * @test
     * @group property-testing
     * @group news-filters
     * @group edge-cases
     */
    public function test_filter_options_empty_database(): void
    {
        // Property: Empty database should return empty filter options
        $filterOptions = $this->service->getFilterOptions();
        
        $this->assertCount(
            0,
            $filterOptions['categories'],
            "Empty database should return no category filter options"
        );
        
        $this->assertCount(
            0,
            $filterOptions['authors'],
            "Empty database should return no author filter options"
        );
    }
}
