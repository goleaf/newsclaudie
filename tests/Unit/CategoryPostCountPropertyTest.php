<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\PropertyTesting;
use Tests\TestCase;

/**
 * Property-Based Tests for Category Post Count
 * 
 * These tests verify universal properties for category post count accuracy.
 */
final class CategoryPostCountPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Feature: admin-livewire-crud, Property 31: Category post count accuracy
     * Validates: Requirements 2.1, 2.7
     * 
     * For any category, the displayed post count should equal the actual number
     * of posts associated with that category.
     */
    public function test_category_post_count_accuracy(): void
    {
        // Run fewer iterations for database tests to avoid performance issues
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create a category
            $category = Category::factory()->create();
            
            // Generate random number of posts (including zero)
            $postCount = $faker->numberBetween(0, 10);
            
            // Property: Initially, category should have zero posts
            $categoryWithCount = Category::withCount('posts')->find($category->id);
            $this->assertSame(0, $categoryWithCount->posts_count, "New category should have zero posts");
            
            // Create posts and associate them with the category
            if ($postCount > 0) {
                // Set published_at to past date to make posts visible (avoid PublishedScope filtering)
                $posts = Post::factory()->count($postCount)->create([
                    'published_at' => now()->subDay(),
                ]);
                
                // Attach posts to category
                $category->posts()->attach($posts->pluck('id'));
            }
            
            // Property: Category post count should equal the number of associated posts
            $categoryWithCount = Category::withCount('posts')->find($category->id);
            $this->assertSame(
                $postCount,
                $categoryWithCount->posts_count,
                "Category post count should equal {$postCount}"
            );
            
            // Property: Direct relationship count should match withCount result
            $directCount = $category->posts()->count();
            $this->assertSame(
                $directCount,
                $categoryWithCount->posts_count,
                "withCount result should match direct relationship count"
            );
            
            // Property: Adding more posts should increase the count
            if ($postCount < 10) {
                $additionalPosts = $faker->numberBetween(1, 3);
                $newPosts = Post::factory()->count($additionalPosts)->create([
                    'published_at' => now()->subDay(),
                ]);
                $category->posts()->attach($newPosts->pluck('id'));
                
                $categoryWithCount = Category::withCount('posts')->find($category->id);
                $this->assertSame(
                    $postCount + $additionalPosts,
                    $categoryWithCount->posts_count,
                    "Category post count should increase by {$additionalPosts}"
                );
            }
            
            // Cleanup
            $category->posts()->detach();
            $category->delete();
        }
    }

    /**
     * Feature: admin-livewire-crud, Property 31: Category post count accuracy (multiple categories)
     * Validates: Requirements 2.1, 2.7
     * 
     * For any set of categories, each category's post count should be independent
     * and accurately reflect only its own associated posts.
     */
    public function test_multiple_categories_independent_post_counts(): void
    {
        // Run fewer iterations for database tests with multiple categories
        for ($i = 0; $i < 5; $i++) {
            $faker = fake();
            
            // Create multiple categories
            $categoryCount = $faker->numberBetween(2, 5);
            $categories = Category::factory()->count($categoryCount)->create();
            
            // Assign random number of posts to each category
            $expectedCounts = [];
            foreach ($categories as $category) {
                $postCount = $faker->numberBetween(0, 5);
                $expectedCounts[$category->id] = $postCount;
                
                if ($postCount > 0) {
                    $posts = Post::factory()->count($postCount)->create([
                        'published_at' => now()->subDay(),
                    ]);
                    $category->posts()->attach($posts->pluck('id'));
                }
            }
            
            // Property: Each category should have its own independent post count
            $categoriesWithCount = Category::withCount('posts')
                ->whereIn('id', $categories->pluck('id'))
                ->get();
            
            foreach ($categoriesWithCount as $category) {
                $this->assertSame(
                    $expectedCounts[$category->id],
                    $category->posts_count,
                    "Category {$category->id} should have {$expectedCounts[$category->id]} posts"
                );
            }
            
            // Property: Total posts across all categories should equal sum of individual counts
            $totalExpectedPosts = array_sum($expectedCounts);
            $totalActualPosts = $categoriesWithCount->sum('posts_count');
            $this->assertSame(
                $totalExpectedPosts,
                $totalActualPosts,
                "Total post count across all categories should equal sum of individual counts"
            );
            
            // Cleanup
            foreach ($categories as $category) {
                $category->posts()->detach();
                $category->delete();
            }
        }
    }

    /**
     * Feature: admin-livewire-crud, Property 31: Category post count accuracy (shared posts)
     * Validates: Requirements 2.1, 2.7
     * 
     * For any post associated with multiple categories, each category's post count
     * should include that post.
     */
    public function test_shared_posts_counted_in_all_categories(): void
    {
        // Run fewer iterations for database tests with shared posts
        for ($i = 0; $i < 5; $i++) {
            $faker = fake();
            
            // Create multiple categories
            $categoryCount = $faker->numberBetween(2, 4);
            $categories = Category::factory()->count($categoryCount)->create();
            
            // Create posts that will be shared across categories
            $sharedPostCount = $faker->numberBetween(1, 3);
            $sharedPosts = Post::factory()->count($sharedPostCount)->create([
                'published_at' => now()->subDay(),
            ]);
            
            // Attach shared posts to all categories
            foreach ($categories as $category) {
                $category->posts()->attach($sharedPosts->pluck('id'));
            }
            
            // Property: Each category should count all shared posts
            $categoriesWithCount = Category::withCount('posts')
                ->whereIn('id', $categories->pluck('id'))
                ->get();
            
            foreach ($categoriesWithCount as $category) {
                $this->assertSame(
                    $sharedPostCount,
                    $category->posts_count,
                    "Category {$category->id} should count all {$sharedPostCount} shared posts"
                );
            }
            
            // Property: Add category-specific posts and verify counts remain independent
            foreach ($categories as $category) {
                $exclusivePostCount = $faker->numberBetween(1, 2);
                $exclusivePosts = Post::factory()->count($exclusivePostCount)->create([
                    'published_at' => now()->subDay(),
                ]);
                $category->posts()->attach($exclusivePosts->pluck('id'));
                
                // Verify this category's count increased
                $categoryWithCount = Category::withCount('posts')->find($category->id);
                $this->assertSame(
                    $sharedPostCount + $exclusivePostCount,
                    $categoryWithCount->posts_count,
                    "Category {$category->id} should have {$sharedPostCount} shared + {$exclusivePostCount} exclusive posts"
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
     * Feature: admin-livewire-crud, Property 31: Category post count accuracy (after detachment)
     * Validates: Requirements 2.1, 2.7
     * 
     * For any category, removing post associations should decrease the post count
     * accordingly.
     */
    public function test_post_count_decreases_after_detachment(): void
    {
        // Run fewer iterations for database tests with detachment
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create a category with posts
            $category = Category::factory()->create();
            $initialPostCount = $faker->numberBetween(3, 8);
            $posts = Post::factory()->count($initialPostCount)->create([
                'published_at' => now()->subDay(),
            ]);
            $category->posts()->attach($posts->pluck('id'));
            
            // Property: Initial count should be correct
            $categoryWithCount = Category::withCount('posts')->find($category->id);
            $this->assertSame(
                $initialPostCount,
                $categoryWithCount->posts_count,
                "Initial post count should be {$initialPostCount}"
            );
            
            // Property: Detach some posts and verify count decreases
            $postsToDetach = $faker->numberBetween(1, min(2, $initialPostCount));
            $detachedPostIds = $posts->take($postsToDetach)->pluck('id');
            $category->posts()->detach($detachedPostIds);
            
            $categoryWithCount = Category::withCount('posts')->find($category->id);
            $expectedCount = $initialPostCount - $postsToDetach;
            $this->assertSame(
                $expectedCount,
                $categoryWithCount->posts_count,
                "Post count should decrease to {$expectedCount} after detaching {$postsToDetach} posts"
            );
            
            // Property: Detach all remaining posts and verify count is zero
            $category->posts()->detach();
            $categoryWithCount = Category::withCount('posts')->find($category->id);
            $this->assertSame(
                0,
                $categoryWithCount->posts_count,
                "Post count should be zero after detaching all posts"
            );
            
            // Cleanup
            $category->delete();
        }
    }

    /**
     * Feature: admin-livewire-crud, Property 31: Category post count accuracy (after post deletion)
     * Validates: Requirements 2.1, 2.7
     * 
     * For any category, deleting associated posts should decrease the post count
     * accordingly.
     */
    public function test_post_count_decreases_after_post_deletion(): void
    {
        // Run fewer iterations for database tests with post deletion
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create a category with posts
            $category = Category::factory()->create();
            $initialPostCount = $faker->numberBetween(3, 8);
            $posts = Post::factory()->count($initialPostCount)->create([
                'published_at' => now()->subDay(),
            ]);
            $category->posts()->attach($posts->pluck('id'));
            
            // Property: Initial count should be correct
            $categoryWithCount = Category::withCount('posts')->find($category->id);
            $this->assertSame(
                $initialPostCount,
                $categoryWithCount->posts_count,
                "Initial post count should be {$initialPostCount}"
            );
            
            // Property: Delete some posts and verify count decreases
            $postsToDelete = $faker->numberBetween(1, min(2, $initialPostCount));
            $deletedPosts = $posts->take($postsToDelete);
            foreach ($deletedPosts as $post) {
                $post->delete();
            }
            
            $categoryWithCount = Category::withCount('posts')->find($category->id);
            $expectedCount = $initialPostCount - $postsToDelete;
            $this->assertSame(
                $expectedCount,
                $categoryWithCount->posts_count,
                "Post count should decrease to {$expectedCount} after deleting {$postsToDelete} posts"
            );
            
            // Property: Delete all remaining posts and verify count is zero
            foreach ($posts->skip($postsToDelete) as $post) {
                $post->delete();
            }
            
            $categoryWithCount = Category::withCount('posts')->find($category->id);
            $this->assertSame(
                0,
                $categoryWithCount->posts_count,
                "Post count should be zero after deleting all posts"
            );
            
            // Cleanup
            $category->delete();
        }
    }
}
