<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Scopes\PublishedScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property-Based Tests for Category Badge Display
 * 
 * These tests verify universal properties for category badge display in post listings.
 * 
 * Feature: admin-livewire-crud, Property 33: Category badge display
 * Validates: Requirements 1.7, 11.4
 */
final class CategoryBadgeDisplayPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * For any post with associated categories, the table view should display
     * badges for all associated categories.
     */
    public function test_post_displays_badges_for_all_associated_categories(): void
    {
        // Run fewer iterations for database tests
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create a user and post
            $user = User::factory()->create();
            $post = Post::factory()->create(['user_id' => $user->id]);
            
            // Create random number of categories and attach to post
            $categoryCount = $faker->numberBetween(1, 5);
            $categories = Category::factory()->count($categoryCount)->create();
            $post->categories()->sync($categories->pluck('id'));

            // Property: Post should have the correct number of categories
            $post->refresh();
            $this->assertSame($categoryCount, $post->categories()->count(), "Post should have {$categoryCount} categories");

            // Property: When loading post with categories, all categories should be present
            $postWithCategories = Post::withoutGlobalScope(PublishedScope::class)
                ->with('categories')
                ->find($post->id);
            
            $this->assertNotNull($postWithCategories, "Post should be loadable with categories");
            $this->assertSame($categoryCount, $postWithCategories->categories->count(), "Loaded post should have {$categoryCount} categories");

            // Property: Each category should be in the loaded collection
            foreach ($categories as $category) {
                $this->assertTrue(
                    $postWithCategories->categories->contains('id', $category->id),
                    "Post categories collection should contain category {$category->id}"
                );
                
                $this->assertTrue(
                    $postWithCategories->categories->contains('name', $category->name),
                    "Post categories collection should contain category name '{$category->name}'"
                );
            }

            // Property: Category names should be accessible for badge display
            $categoryNames = $postWithCategories->categories->pluck('name')->toArray();
            $this->assertSame($categoryCount, count($categoryNames), "Should have {$categoryCount} category names");
            
            foreach ($categories as $category) {
                $this->assertContains($category->name, $categoryNames, "Category names should include '{$category->name}'");
            }

            // Cleanup
            $post->delete();
            foreach ($categories as $category) {
                $category->delete();
            }
            $user->delete();
        }
    }

    /**
     * For any post with no categories, the categories collection should be empty.
     */
    public function test_post_with_no_categories_has_empty_collection(): void
    {
        // Run fewer iterations for database tests
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create a user and post without categories
            $user = User::factory()->create();
            $post = Post::factory()->create(['user_id' => $user->id]);

            // Property: Post should have no categories
            $this->assertSame(0, $post->categories()->count(), "Post should have no categories");

            // Property: When loading post with categories, collection should be empty
            $postWithCategories = Post::withoutGlobalScope(PublishedScope::class)
                ->with('categories')
                ->find($post->id);
            
            $this->assertNotNull($postWithCategories, "Post should be loadable");
            $this->assertTrue($postWithCategories->categories->isEmpty(), "Categories collection should be empty");
            $this->assertSame(0, $postWithCategories->categories->count(), "Categories count should be 0");

            // Cleanup
            $post->delete();
            $user->delete();
        }
    }

    /**
     * For any post, the categories relationship should be eager loadable
     * to avoid N+1 queries.
     */
    public function test_categories_are_eager_loadable(): void
    {
        // Run fewer iterations for database tests
        for ($i = 0; $i < 5; $i++) {
            $faker = fake();
            
            // Create a user and multiple posts with categories
            $user = User::factory()->create();
            $postCount = $faker->numberBetween(3, 5);
            $posts = Post::factory()->count($postCount)->create(['user_id' => $user->id]);
            
            // Attach random categories to each post
            foreach ($posts as $post) {
                $categories = Category::factory()->count($faker->numberBetween(1, 3))->create();
                $post->categories()->sync($categories->pluck('id'));
            }

            // Property: Eager load all posts with categories
            $loadedPosts = Post::withoutGlobalScope(PublishedScope::class)
                ->with('categories')
                ->whereIn('id', $posts->pluck('id'))
                ->get();

            // Property: All posts should be loaded
            $this->assertSame($postCount, $loadedPosts->count(), "Should load {$postCount} posts");

            // Property: Each post should have its categories loaded
            foreach ($loadedPosts as $loadedPost) {
                $this->assertTrue(
                    $loadedPost->relationLoaded('categories'),
                    "Post {$loadedPost->id} should have categories relation loaded"
                );
                
                $this->assertGreaterThan(
                    0,
                    $loadedPost->categories->count(),
                    "Post {$loadedPost->id} should have at least one category"
                );
            }

            // Cleanup
            foreach ($posts as $post) {
                $post->categories()->detach();
                $post->delete();
            }
            Category::whereIn('id', Category::all()->pluck('id'))->delete();
            $user->delete();
        }
    }

    /**
     * For any post with categories, the category order should be consistent
     * when loaded multiple times.
     */
    public function test_category_order_is_consistent_across_loads(): void
    {
        // Run fewer iterations for database tests
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create a user and post
            $user = User::factory()->create();
            $post = Post::factory()->create(['user_id' => $user->id]);
            
            // Create and attach categories
            $categories = Category::factory()->count($faker->numberBetween(2, 4))->create();
            $post->categories()->sync($categories->pluck('id'));

            // Property: Load post with categories multiple times
            $load1 = Post::withoutGlobalScope(PublishedScope::class)
                ->with('categories')
                ->find($post->id);
            
            $load2 = Post::withoutGlobalScope(PublishedScope::class)
                ->with('categories')
                ->find($post->id);
            
            $load3 = Post::withoutGlobalScope(PublishedScope::class)
                ->with('categories')
                ->find($post->id);

            // Property: Category IDs should be the same across loads
            $ids1 = $load1->categories->pluck('id')->sort()->values()->toArray();
            $ids2 = $load2->categories->pluck('id')->sort()->values()->toArray();
            $ids3 = $load3->categories->pluck('id')->sort()->values()->toArray();

            $this->assertEquals($ids1, $ids2, "Category IDs should be consistent between load 1 and 2");
            $this->assertEquals($ids2, $ids3, "Category IDs should be consistent between load 2 and 3");
            $this->assertEquals($ids1, $ids3, "Category IDs should be consistent between load 1 and 3");

            // Property: Category names should be the same across loads
            $names1 = $load1->categories->pluck('name')->sort()->values()->toArray();
            $names2 = $load2->categories->pluck('name')->sort()->values()->toArray();
            $names3 = $load3->categories->pluck('name')->sort()->values()->toArray();

            $this->assertEquals($names1, $names2, "Category names should be consistent between load 1 and 2");
            $this->assertEquals($names2, $names3, "Category names should be consistent between load 2 and 3");
            $this->assertEquals($names1, $names3, "Category names should be consistent between load 1 and 3");

            // Cleanup
            $post->delete();
            foreach ($categories as $category) {
                $category->delete();
            }
            $user->delete();
        }
    }

    /**
     * For any post, the categories collection should provide all necessary
     * data for badge rendering (id, name, slug).
     */
    public function test_categories_collection_provides_badge_rendering_data(): void
    {
        // Run fewer iterations for database tests
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create a user and post
            $user = User::factory()->create();
            $post = Post::factory()->create(['user_id' => $user->id]);
            
            // Create and attach categories
            $categories = Category::factory()->count($faker->numberBetween(1, 4))->create();
            $post->categories()->sync($categories->pluck('id'));

            // Property: Load post with categories
            $postWithCategories = Post::withoutGlobalScope(PublishedScope::class)
                ->with('categories:id,name,slug')
                ->find($post->id);

            // Property: Each category should have required attributes for badge display
            foreach ($postWithCategories->categories as $category) {
                $this->assertNotNull($category->id, "Category should have id");
                $this->assertNotNull($category->name, "Category should have name");
                $this->assertNotNull($category->slug, "Category should have slug");
                
                $this->assertIsInt($category->id, "Category id should be integer");
                $this->assertIsString($category->name, "Category name should be string");
                $this->assertIsString($category->slug, "Category slug should be string");
                
                $this->assertNotEmpty($category->name, "Category name should not be empty");
                $this->assertNotEmpty($category->slug, "Category slug should not be empty");
            }

            // Cleanup
            $post->delete();
            foreach ($categories as $category) {
                $category->delete();
            }
            $user->delete();
        }
    }

    /**
     * For any post with many categories, all categories should be displayable
     * without truncation or loss.
     */
    public function test_all_categories_are_displayable_without_loss(): void
    {
        // Run fewer iterations for database tests
        for ($i = 0; $i < 5; $i++) {
            $faker = fake();
            
            // Create a user and post
            $user = User::factory()->create();
            $post = Post::factory()->create(['user_id' => $user->id]);
            
            // Create many categories (edge case)
            $categoryCount = $faker->numberBetween(5, 10);
            $categories = Category::factory()->count($categoryCount)->create();
            $post->categories()->sync($categories->pluck('id'));

            // Property: Load post with all categories
            $postWithCategories = Post::withoutGlobalScope(PublishedScope::class)
                ->with('categories')
                ->find($post->id);

            // Property: All categories should be loaded
            $this->assertSame($categoryCount, $postWithCategories->categories->count(), "All {$categoryCount} categories should be loaded");

            // Property: Each original category should be present
            foreach ($categories as $category) {
                $this->assertTrue(
                    $postWithCategories->categories->contains('id', $category->id),
                    "Category {$category->id} should be present"
                );
            }

            // Property: No duplicate categories
            $categoryIds = $postWithCategories->categories->pluck('id')->toArray();
            $uniqueIds = array_unique($categoryIds);
            $this->assertSame(count($categoryIds), count($uniqueIds), "No duplicate categories should exist");

            // Cleanup
            $post->delete();
            foreach ($categories as $category) {
                $category->delete();
            }
            $user->delete();
        }
    }
}
