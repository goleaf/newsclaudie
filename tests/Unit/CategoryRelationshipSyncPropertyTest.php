<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Property-Based Tests for Category Relationship Synchronization
 * 
 * These tests verify universal properties for post-category relationship management.
 */
final class CategoryRelationshipSyncPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Helper method to create a published post for testing
     * Posts need published_at set to be visible through category->posts relationship
     */
    private function createPublishedPost(User $user, $faker): Post
    {
        return Post::withoutGlobalScopes()->create([
            'user_id' => $user->id,
            'title' => ucwords($faker->words(3, true)),
            'slug' => Str::slug($faker->words(3, true) . '-' . Str::random(8)),
            'body' => $faker->paragraphs(3, true),
            'published_at' => now()->subDay(), // Set to past date so it's considered published
        ]);
    }

    /**
     * Feature: admin-livewire-crud, Property 3: Relationship synchronization
     * Validates: Requirements 1.7, 11.3
     * 
     * For any post and any set of categories, assigning categories to the post
     * should correctly sync the many-to-many relationship in both directions.
     */
    public function test_category_assignment_syncs_bidirectional_relationship(): void
    {
        // Run fewer iterations for database tests to avoid performance issues
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create a user and post
            $user = User::factory()->create();
            $post = $this->createPublishedPost($user, $faker);
            
            // Create random number of categories (1-5)
            $categoryCount = $faker->numberBetween(1, 5);
            $categories = Category::factory()->count($categoryCount)->create();
            $categoryIds = $categories->pluck('id')->toArray();
            
            // Property: Sync categories to post
            $post->categories()->sync($categoryIds);
            
            // Property: Post should have all assigned categories
            $postCategories = $post->fresh()->categories;
            $this->assertCount($categoryCount, $postCategories, "Post should have {$categoryCount} categories");
            
            // Property: Each category should be in the post's categories
            foreach ($categories as $category) {
                $this->assertTrue(
                    $postCategories->contains('id', $category->id),
                    "Post should contain category {$category->id}"
                );
            }
            
            // Property: Relationship should work in reverse (category->posts)
            foreach ($categories as $category) {
                $categoryPosts = $category->fresh()->posts;
                $this->assertTrue(
                    $categoryPosts->contains('id', $post->id),
                    "Category {$category->id} should contain post {$post->id}"
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
            $post->categories()->detach();
            $post->delete();
            $categories->each->delete();
            $user->delete();
        }
    }

    /**
     * Feature: admin-livewire-crud, Property 3: Relationship synchronization (update)
     * Validates: Requirements 1.7, 11.3
     * 
     * For any post with existing categories, updating the category assignment
     * should correctly add new categories and remove old ones.
     */
    public function test_category_update_syncs_correctly(): void
    {
        // Run fewer iterations for database tests
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create a user and post
            $user = User::factory()->create();
            $post = Post::withoutGlobalScopes()->create([
                'user_id' => $user->id,
                'title' => ucwords($faker->words(3, true)),
                'slug' => Str::slug($faker->words(3, true) . '-' . Str::random(8)),
                'body' => $faker->paragraphs(3, true),
            ]);
            
            // Create initial categories
            $initialCategories = Category::factory()->count(3)->create();
            $initialCategoryIds = $initialCategories->pluck('id')->toArray();
            
            // Property: Assign initial categories
            $post->categories()->sync($initialCategoryIds);
            
            // Property: Post should have initial categories
            $this->assertCount(3, $post->fresh()->categories, "Post should have 3 initial categories");
            
            // Create new categories for update
            $newCategories = Category::factory()->count(2)->create();
            $newCategoryIds = $newCategories->pluck('id')->toArray();
            
            // Property: Update to new categories (replace all)
            $post->categories()->sync($newCategoryIds);
            
            // Property: Post should now have only new categories
            $postCategories = $post->fresh()->categories;
            $this->assertCount(2, $postCategories, "Post should have 2 new categories");
            
            // Property: New categories should be present
            foreach ($newCategoryIds as $categoryId) {
                $this->assertTrue(
                    $postCategories->contains('id', $categoryId),
                    "Post should contain new category {$categoryId}"
                );
            }
            
            // Property: Old categories should be removed
            foreach ($initialCategoryIds as $categoryId) {
                $this->assertFalse(
                    $postCategories->contains('id', $categoryId),
                    "Post should not contain old category {$categoryId}"
                );
            }
            
            // Property: Pivot table should reflect changes
            foreach ($newCategoryIds as $categoryId) {
                $this->assertDatabaseHas('category_post', [
                    'category_id' => $categoryId,
                    'post_id' => $post->id,
                ]);
            }
            
            foreach ($initialCategoryIds as $categoryId) {
                $this->assertDatabaseMissing('category_post', [
                    'category_id' => $categoryId,
                    'post_id' => $post->id,
                ]);
            }
            
            // Cleanup
            $post->categories()->detach();
            $post->delete();
            $initialCategories->each->delete();
            $newCategories->each->delete();
            $user->delete();
        }
    }

    /**
     * Feature: admin-livewire-crud, Property 3: Relationship synchronization (partial update)
     * Validates: Requirements 1.7, 11.3
     * 
     * For any post with existing categories, adding or removing specific categories
     * should correctly update the relationship while preserving others.
     */
    public function test_category_partial_update_preserves_existing(): void
    {
        // Run fewer iterations for database tests
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create a user and post
            $user = User::factory()->create();
            $post = Post::withoutGlobalScopes()->create([
                'user_id' => $user->id,
                'title' => ucwords($faker->words(3, true)),
                'slug' => Str::slug($faker->words(3, true) . '-' . Str::random(8)),
                'body' => $faker->paragraphs(3, true),
            ]);
            
            // Create initial categories
            $initialCategories = Category::factory()->count(3)->create();
            $initialCategoryIds = $initialCategories->pluck('id')->toArray();
            
            // Property: Assign initial categories
            $post->categories()->sync($initialCategoryIds);
            
            // Create additional category to add
            $additionalCategory = Category::factory()->create();
            
            // Property: Add one more category (keep existing + add new)
            $updatedCategoryIds = array_merge($initialCategoryIds, [$additionalCategory->id]);
            $post->categories()->sync($updatedCategoryIds);
            
            // Property: Post should have all 4 categories
            $postCategories = $post->fresh()->categories;
            $this->assertCount(4, $postCategories, "Post should have 4 categories after adding one");
            
            // Property: All initial categories should still be present
            foreach ($initialCategoryIds as $categoryId) {
                $this->assertTrue(
                    $postCategories->contains('id', $categoryId),
                    "Post should still contain initial category {$categoryId}"
                );
            }
            
            // Property: New category should be present
            $this->assertTrue(
                $postCategories->contains('id', $additionalCategory->id),
                "Post should contain newly added category {$additionalCategory->id}"
            );
            
            // Property: Remove one category (keep others)
            $categoryToRemove = $initialCategoryIds[0];
            $remainingCategoryIds = array_diff($updatedCategoryIds, [$categoryToRemove]);
            $post->categories()->sync($remainingCategoryIds);
            
            // Property: Post should have 3 categories after removing one
            $postCategories = $post->fresh()->categories;
            $this->assertCount(3, $postCategories, "Post should have 3 categories after removing one");
            
            // Property: Removed category should not be present
            $this->assertFalse(
                $postCategories->contains('id', $categoryToRemove),
                "Post should not contain removed category {$categoryToRemove}"
            );
            
            // Property: Other categories should still be present
            foreach ($remainingCategoryIds as $categoryId) {
                $this->assertTrue(
                    $postCategories->contains('id', $categoryId),
                    "Post should still contain category {$categoryId}"
                );
            }
            
            // Cleanup
            $post->categories()->detach();
            $post->delete();
            $initialCategories->each->delete();
            $additionalCategory->delete();
            $user->delete();
        }
    }

    /**
     * Feature: admin-livewire-crud, Property 3: Relationship synchronization (empty sync)
     * Validates: Requirements 1.7, 11.3
     * 
     * For any post with categories, syncing with an empty array should remove
     * all category associations.
     */
    public function test_empty_category_sync_removes_all_associations(): void
    {
        // Run fewer iterations for database tests
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create a user and post
            $user = User::factory()->create();
            $post = Post::withoutGlobalScopes()->create([
                'user_id' => $user->id,
                'title' => ucwords($faker->words(3, true)),
                'slug' => Str::slug($faker->words(3, true) . '-' . Str::random(8)),
                'body' => $faker->paragraphs(3, true),
            ]);
            
            // Create categories
            $categories = Category::factory()->count($faker->numberBetween(2, 5))->create();
            $categoryIds = $categories->pluck('id')->toArray();
            
            // Property: Assign categories
            $post->categories()->sync($categoryIds);
            
            // Property: Post should have categories
            $this->assertGreaterThan(0, $post->fresh()->categories->count(), "Post should have categories");
            
            // Property: Sync with empty array
            $post->categories()->sync([]);
            
            // Property: Post should have no categories
            $this->assertCount(0, $post->fresh()->categories, "Post should have no categories after empty sync");
            
            // Property: Pivot table should have no entries for this post
            foreach ($categoryIds as $categoryId) {
                $this->assertDatabaseMissing('category_post', [
                    'category_id' => $categoryId,
                    'post_id' => $post->id,
                ]);
            }
            
            // Property: Categories should still exist (not deleted)
            foreach ($categories as $category) {
                $this->assertDatabaseHas('categories', [
                    'id' => $category->id,
                ]);
            }
            
            // Cleanup
            $post->delete();
            $categories->each->delete();
            $user->delete();
        }
    }

    /**
     * Feature: admin-livewire-crud, Property 3: Relationship synchronization (isolation)
     * Validates: Requirements 11.5
     * 
     * For any post with categories, removing a category association from one post
     * should not affect the same category's association with other posts.
     */
    public function test_category_removal_isolation_between_posts(): void
    {
        // Run fewer iterations for database tests
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create a user
            $user = User::factory()->create();
            
            // Create two posts
            $post1 = Post::withoutGlobalScopes()->create([
                'user_id' => $user->id,
                'title' => ucwords($faker->words(3, true)),
                'slug' => Str::slug($faker->words(3, true) . '-' . Str::random(8)),
                'body' => $faker->paragraphs(3, true),
            ]);
            
            $post2 = Post::withoutGlobalScopes()->create([
                'user_id' => $user->id,
                'title' => ucwords($faker->words(3, true)),
                'slug' => Str::slug($faker->words(3, true) . '-' . Str::random(8)),
                'body' => $faker->paragraphs(3, true),
            ]);
            
            // Create shared categories
            $sharedCategories = Category::factory()->count(3)->create();
            $sharedCategoryIds = $sharedCategories->pluck('id')->toArray();
            
            // Property: Assign same categories to both posts
            $post1->categories()->sync($sharedCategoryIds);
            $post2->categories()->sync($sharedCategoryIds);
            
            // Property: Both posts should have all categories
            $this->assertCount(3, $post1->fresh()->categories, "Post 1 should have 3 categories");
            $this->assertCount(3, $post2->fresh()->categories, "Post 2 should have 3 categories");
            
            // Property: Remove one category from post1 only
            $categoryToRemove = $sharedCategoryIds[0];
            $post1RemainingIds = array_diff($sharedCategoryIds, [$categoryToRemove]);
            $post1->categories()->sync($post1RemainingIds);
            
            // Property: Post1 should have 2 categories
            $post1Categories = $post1->fresh()->categories;
            $this->assertCount(2, $post1Categories, "Post 1 should have 2 categories after removal");
            $this->assertFalse(
                $post1Categories->contains('id', $categoryToRemove),
                "Post 1 should not contain removed category"
            );
            
            // Property: Post2 should still have all 3 categories (isolation)
            $post2Categories = $post2->fresh()->categories;
            $this->assertCount(3, $post2Categories, "Post 2 should still have 3 categories");
            $this->assertTrue(
                $post2Categories->contains('id', $categoryToRemove),
                "Post 2 should still contain the category removed from Post 1"
            );
            
            // Property: Category should still be associated with post2 in pivot table
            $this->assertDatabaseHas('category_post', [
                'category_id' => $categoryToRemove,
                'post_id' => $post2->id,
            ]);
            
            // Property: Category should not be associated with post1 in pivot table
            $this->assertDatabaseMissing('category_post', [
                'category_id' => $categoryToRemove,
                'post_id' => $post1->id,
            ]);
            
            // Cleanup
            $post1->categories()->detach();
            $post2->categories()->detach();
            $post1->delete();
            $post2->delete();
            $sharedCategories->each->delete();
            $user->delete();
        }
    }

    /**
     * Feature: admin-livewire-crud, Property 3: Relationship synchronization (multiple posts)
     * Validates: Requirements 1.7, 11.3
     * 
     * For any category, it should be able to be associated with multiple posts
     * simultaneously, and the relationship should work correctly in both directions.
     */
    public function test_category_can_be_associated_with_multiple_posts(): void
    {
        // Run fewer iterations for database tests
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create a user
            $user = User::factory()->create();
            
            // Create a single category
            $category = Category::factory()->create();
            
            // Create multiple posts (2-5)
            $postCount = $faker->numberBetween(2, 5);
            $posts = collect();
            
            for ($j = 0; $j < $postCount; $j++) {
                $post = $this->createPublishedPost($user, $faker);
                $posts->push($post);
            }
            
            // Property: Assign the same category to all posts
            foreach ($posts as $post) {
                $post->categories()->sync([$category->id]);
            }
            
            // Property: Each post should have the category
            foreach ($posts as $post) {
                $postCategories = $post->fresh()->categories;
                $this->assertCount(1, $postCategories, "Post should have 1 category");
                $this->assertTrue(
                    $postCategories->contains('id', $category->id),
                    "Post should contain the category"
                );
            }
            
            // Property: Category should have all posts
            $categoryPosts = $category->fresh()->posts;
            $this->assertCount($postCount, $categoryPosts, "Category should have {$postCount} posts");
            
            // Property: Each post should be in the category's posts
            foreach ($posts as $post) {
                $this->assertTrue(
                    $categoryPosts->contains('id', $post->id),
                    "Category should contain post {$post->id}"
                );
            }
            
            // Property: Pivot table should have correct entries
            foreach ($posts as $post) {
                $this->assertDatabaseHas('category_post', [
                    'category_id' => $category->id,
                    'post_id' => $post->id,
                ]);
            }
            
            // Cleanup
            foreach ($posts as $post) {
                $post->categories()->detach();
                $post->delete();
            }
            $category->delete();
            $user->delete();
        }
    }

    /**
     * Feature: admin-livewire-crud, Property 3: Relationship synchronization (attach/detach)
     * Validates: Requirements 1.7, 11.3
     * 
     * For any post and category, using attach() and detach() methods should
     * correctly manage the relationship without affecting other associations.
     */
    public function test_attach_and_detach_methods_work_correctly(): void
    {
        // Run fewer iterations for database tests
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create a user and post
            $user = User::factory()->create();
            $post = Post::withoutGlobalScopes()->create([
                'user_id' => $user->id,
                'title' => ucwords($faker->words(3, true)),
                'slug' => Str::slug($faker->words(3, true) . '-' . Str::random(8)),
                'body' => $faker->paragraphs(3, true),
            ]);
            
            // Create categories
            $category1 = Category::factory()->create();
            $category2 = Category::factory()->create();
            $category3 = Category::factory()->create();
            
            // Property: Attach first category
            $post->categories()->attach($category1->id);
            
            // Property: Post should have 1 category
            $this->assertCount(1, $post->fresh()->categories, "Post should have 1 category after attach");
            $this->assertTrue(
                $post->fresh()->categories->contains('id', $category1->id),
                "Post should contain attached category"
            );
            
            // Property: Attach second category
            $post->categories()->attach($category2->id);
            
            // Property: Post should have 2 categories
            $postCategories = $post->fresh()->categories;
            $this->assertCount(2, $postCategories, "Post should have 2 categories after second attach");
            $this->assertTrue($postCategories->contains('id', $category1->id), "Post should contain first category");
            $this->assertTrue($postCategories->contains('id', $category2->id), "Post should contain second category");
            
            // Property: Attach third category
            $post->categories()->attach($category3->id);
            
            // Property: Post should have 3 categories
            $this->assertCount(3, $post->fresh()->categories, "Post should have 3 categories after third attach");
            
            // Property: Detach second category
            $post->categories()->detach($category2->id);
            
            // Property: Post should have 2 categories
            $postCategories = $post->fresh()->categories;
            $this->assertCount(2, $postCategories, "Post should have 2 categories after detach");
            $this->assertTrue($postCategories->contains('id', $category1->id), "Post should still contain first category");
            $this->assertFalse($postCategories->contains('id', $category2->id), "Post should not contain detached category");
            $this->assertTrue($postCategories->contains('id', $category3->id), "Post should still contain third category");
            
            // Property: Detach all remaining categories
            $post->categories()->detach();
            
            // Property: Post should have no categories
            $this->assertCount(0, $post->fresh()->categories, "Post should have no categories after detach all");
            
            // Cleanup
            $post->delete();
            $category1->delete();
            $category2->delete();
            $category3->delete();
            $user->delete();
        }
    }
}

