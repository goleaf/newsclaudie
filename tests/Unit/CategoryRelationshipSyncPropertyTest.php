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
 * Property-Based Tests for Category Relationship Synchronization
 * 
 * These tests verify universal properties for post-category relationship operations.
 * 
 * Feature: admin-livewire-crud, Property 3: Relationship synchronization
 * Validates: Requirements 1.7, 11.3, 11.5
 */
final class CategoryRelationshipSyncPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * For any post and any set of categories, assigning categories to the post
     * should correctly sync the many-to-many relationship in both directions.
     */
    public function test_category_assignment_syncs_relationship_bidirectionally(): void
    {
        // Run fewer iterations for database tests with relationships
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create a user and post
            $user = User::factory()->create();
            $post = Post::factory()->create(['user_id' => $user->id]);
            
            // Create random number of categories
            $categoryCount = $faker->numberBetween(1, 5);
            $categories = Category::factory()->count($categoryCount)->create();
            $categoryIds = $categories->pluck('id')->toArray();

            // Property: Initially, post should have no categories
            $this->assertSame(0, $post->categories()->count(), "Post should initially have no categories");

            // Property: Sync categories to post
            $post->categories()->sync($categoryIds);

            // Property: Post should have the correct number of categories
            $post->refresh();
            $this->assertSame($categoryCount, $post->categories()->count(), "Post should have {$categoryCount} categories after sync");

            // Property: Each category should be associated with the post (forward direction)
            foreach ($categoryIds as $categoryId) {
                $this->assertTrue(
                    $post->categories()->where('categories.id', $categoryId)->exists(),
                    "Post should have category {$categoryId} in its relationships"
                );
            }

            // Property: Each category should have the post in its relationships (reverse direction)
            foreach ($categories as $category) {
                $this->assertTrue(
                    $category->posts()->withoutGlobalScope(PublishedScope::class)->where('posts.id', $post->id)->exists(),
                    "Category should have post in its relationships"
                );
            }

            // Property: Pivot table should have correct entries
            foreach ($categoryIds as $categoryId) {
                $this->assertDatabaseHas('category_post', [
                    'category_id' => $categoryId,
                    'post_id' => $post->id,
                ]);
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
     * For any post with existing categories, updating the category assignment
     * should correctly sync the new relationships and remove old ones.
     */
    public function test_category_update_syncs_new_relationships_and_removes_old(): void
    {
        // Run fewer iterations for database tests
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create a user and post
            $user = User::factory()->create();
            $post = Post::factory()->create(['user_id' => $user->id]);
            
            // Create initial categories and attach to post
            $initialCategories = Category::factory()->count($faker->numberBetween(2, 4))->create();
            $initialCategoryIds = $initialCategories->pluck('id')->toArray();
            $post->categories()->sync($initialCategoryIds);

            // Property: Post should have initial categories
            $this->assertSame(count($initialCategoryIds), $post->categories()->count(), "Post should have initial categories");

            // Create new categories
            $newCategories = Category::factory()->count($faker->numberBetween(2, 4))->create();
            $newCategoryIds = $newCategories->pluck('id')->toArray();

            // Property: Sync new categories (replacing old ones)
            $post->categories()->sync($newCategoryIds);

            // Property: Post should have new categories
            $post->refresh();
            $this->assertSame(count($newCategoryIds), $post->categories()->count(), "Post should have new categories after sync");

            // Property: Post should have all new categories
            foreach ($newCategoryIds as $categoryId) {
                $this->assertTrue(
                    $post->categories()->where('categories.id', $categoryId)->exists(),
                    "Post should have new category {$categoryId}"
                );
            }

            // Property: Post should not have old categories
            foreach ($initialCategoryIds as $categoryId) {
                $this->assertFalse(
                    $post->categories()->where('categories.id', $categoryId)->exists(),
                    "Post should not have old category {$categoryId}"
                );
            }

            // Property: Old pivot entries should be removed
            foreach ($initialCategoryIds as $categoryId) {
                $this->assertDatabaseMissing('category_post', [
                    'category_id' => $categoryId,
                    'post_id' => $post->id,
                ]);
            }

            // Property: New pivot entries should exist
            foreach ($newCategoryIds as $categoryId) {
                $this->assertDatabaseHas('category_post', [
                    'category_id' => $categoryId,
                    'post_id' => $post->id,
                ]);
            }

            // Cleanup
            $post->delete();
            foreach ($initialCategories as $category) {
                $category->delete();
            }
            foreach ($newCategories as $category) {
                $category->delete();
            }
            $user->delete();
        }
    }

    /**
     * For any post, removing all category associations should result in
     * the post having no categories.
     */
    public function test_removing_all_categories_results_in_no_associations(): void
    {
        // Run fewer iterations for database tests
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create a user and post
            $user = User::factory()->create();
            $post = Post::factory()->create(['user_id' => $user->id]);
            
            // Create categories and attach to post
            $categories = Category::factory()->count($faker->numberBetween(2, 5))->create();
            $categoryIds = $categories->pluck('id')->toArray();
            $post->categories()->sync($categoryIds);

            // Property: Post should have categories
            $this->assertGreaterThan(0, $post->categories()->count(), "Post should have categories initially");

            // Property: Remove all categories
            $post->categories()->sync([]);

            // Property: Post should have no categories
            $post->refresh();
            $this->assertSame(0, $post->categories()->count(), "Post should have no categories after sync with empty array");

            // Property: No pivot entries should exist for this post
            foreach ($categoryIds as $categoryId) {
                $this->assertDatabaseMissing('category_post', [
                    'category_id' => $categoryId,
                    'post_id' => $post->id,
                ]);
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
     * For any post, adding categories incrementally should correctly
     * accumulate the relationships.
     */
    public function test_incremental_category_addition_accumulates_relationships(): void
    {
        // Run fewer iterations for database tests
        for ($i = 0; $i < 5; $i++) {
            $faker = fake();
            
            // Create a user and post
            $user = User::factory()->create();
            $post = Post::factory()->create(['user_id' => $user->id]);
            
            // Create categories
            $allCategories = Category::factory()->count(5)->create();
            $allCategoryIds = $allCategories->pluck('id')->toArray();

            // Property: Start with no categories
            $this->assertSame(0, $post->categories()->count(), "Post should start with no categories");

            // Property: Add categories one at a time using attach
            $expectedCount = 0;
            foreach ($allCategoryIds as $categoryId) {
                $post->categories()->attach($categoryId);
                $expectedCount++;
                
                $post->refresh();
                $this->assertSame($expectedCount, $post->categories()->count(), "Post should have {$expectedCount} categories");
                
                $this->assertTrue(
                    $post->categories()->where('categories.id', $categoryId)->exists(),
                    "Post should have category {$categoryId}"
                );
            }

            // Property: All categories should be associated
            $this->assertSame(count($allCategoryIds), $post->categories()->count(), "Post should have all categories");

            // Cleanup
            $post->delete();
            foreach ($allCategories as $category) {
                $category->delete();
            }
            $user->delete();
        }
    }

    /**
     * For any category, removing a category association from one post
     * should not affect other posts with the same category (isolation).
     * 
     * Validates: Requirements 11.5
     */
    public function test_category_removal_from_one_post_does_not_affect_other_posts(): void
    {
        // Run fewer iterations for database tests
        for ($i = 0; $i < 5; $i++) {
            $faker = fake();
            
            // Create a user and multiple posts
            $user = User::factory()->create();
            $post1 = Post::factory()->create(['user_id' => $user->id]);
            $post2 = Post::factory()->create(['user_id' => $user->id]);
            $post3 = Post::factory()->create(['user_id' => $user->id]);
            
            // Create a shared category
            $sharedCategory = Category::factory()->create();
            
            // Attach the category to all posts
            $post1->categories()->attach($sharedCategory->id);
            $post2->categories()->attach($sharedCategory->id);
            $post3->categories()->attach($sharedCategory->id);

            // Property: All posts should have the shared category
            $this->assertTrue($post1->categories()->where('categories.id', $sharedCategory->id)->exists(), "Post 1 should have shared category");
            $this->assertTrue($post2->categories()->where('categories.id', $sharedCategory->id)->exists(), "Post 2 should have shared category");
            $this->assertTrue($post3->categories()->where('categories.id', $sharedCategory->id)->exists(), "Post 3 should have shared category");

            // Property: Remove category from post1 only
            $post1->categories()->detach($sharedCategory->id);

            // Property: Post1 should not have the category
            $post1->refresh();
            $this->assertFalse($post1->categories()->where('categories.id', $sharedCategory->id)->exists(), "Post 1 should not have shared category after detach");

            // Property: Post2 and Post3 should still have the category (isolation)
            $post2->refresh();
            $post3->refresh();
            $this->assertTrue($post2->categories()->where('categories.id', $sharedCategory->id)->exists(), "Post 2 should still have shared category");
            $this->assertTrue($post3->categories()->where('categories.id', $sharedCategory->id)->exists(), "Post 3 should still have shared category");

            // Property: Category should still exist
            $this->assertDatabaseHas('categories', [
                'id' => $sharedCategory->id,
            ]);

            // Cleanup
            $post1->delete();
            $post2->delete();
            $post3->delete();
            $sharedCategory->delete();
            $user->delete();
        }
    }

    /**
     * For any post and categories, the relationship should persist
     * across post updates (non-category fields).
     */
    public function test_category_relationships_persist_across_post_updates(): void
    {
        // Run fewer iterations for database tests
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create a user and post
            $user = User::factory()->create();
            $post = Post::factory()->create(['user_id' => $user->id]);
            
            // Create and attach categories
            $categories = Category::factory()->count($faker->numberBetween(2, 4))->create();
            $categoryIds = $categories->pluck('id')->toArray();
            $post->categories()->sync($categoryIds);

            // Property: Post should have categories
            $initialCount = $post->categories()->count();
            $this->assertSame(count($categoryIds), $initialCount, "Post should have categories");

            // Property: Update post (non-category fields)
            $post->update([
                'title' => ucwords($faker->words(3, true)),
                'body' => $faker->paragraphs(3, true),
            ]);

            // Property: Categories should remain unchanged after post update
            $post->refresh();
            $this->assertSame($initialCount, $post->categories()->count(), "Category count should remain unchanged after post update");

            foreach ($categoryIds as $categoryId) {
                $this->assertTrue(
                    $post->categories()->where('categories.id', $categoryId)->exists(),
                    "Post should still have category {$categoryId} after update"
                );
            }

            // Cleanup
            $post->delete();
            foreach ($categories as $category) {
                $category->delete();
            }
            $user->delete();
        }
    }
}
