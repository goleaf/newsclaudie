<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\PropertyTesting;
use Tests\TestCase;

/**
 * Property-Based Tests for Category Deletion
 * 
 * These tests verify universal properties for category deletion operations.
 */
final class CategoryDeletionPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Feature: admin-livewire-crud, Property 2: Deletion removes resource
     * Validates: Requirements 2.6
     * 
     * For any resource (Category), deleting the resource should remove it
     * from the database and the table display.
     */
    public function test_category_deletion_removes_resource(): void
    {
        // Run fewer iterations for database tests to avoid performance issues
        // Each iteration needs its own database transaction
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Generate random category data
            $categoryName = ucwords($faker->words($faker->numberBetween(1, 3), true));
            $categorySlug = \Illuminate\Support\Str::slug($categoryName);
            $categoryDescription = $faker->sentence();

            // Property: Create a category in the database
            $category = Category::factory()->create([
                'name' => $categoryName,
                'slug' => $categorySlug,
                'description' => $categoryDescription,
            ]);

            // Property: Category should exist in database after creation
            $this->assertDatabaseHas('categories', [
                'id' => $category->id,
                'name' => $categoryName,
                'slug' => $categorySlug,
            ]);

            // Property: Category should be retrievable by ID
            $retrievedCategory = Category::find($category->id);
            $this->assertNotNull($retrievedCategory, "Category should be retrievable by ID");
            $this->assertSame($category->id, $retrievedCategory->id, "Retrieved category should have same ID");
            $this->assertSame($categoryName, $retrievedCategory->name, "Retrieved category should have same name");

            // Store the ID before deletion
            $categoryId = $category->id;

            // Property: Delete the category
            $deleteResult = $category->delete();
            $this->assertTrue($deleteResult, "Delete operation should return true");

            // Property: Category should no longer exist in database after deletion
            $this->assertDatabaseMissing('categories', [
                'id' => $categoryId,
            ]);

            // Property: Category should not be retrievable by ID after deletion
            $deletedCategory = Category::find($categoryId);
            $this->assertNull($deletedCategory, "Category should not be retrievable after deletion");

            // Property: Attempting to find the category should return null
            $this->assertNull(
                Category::where('slug', $categorySlug)->first(),
                "Category should not be findable by slug after deletion"
            );

            // Property: Count of categories should decrease by 1
            $countBefore = Category::count();
            $newCategory = Category::factory()->create();
            $this->assertSame($countBefore + 1, Category::count(), "Count should increase by 1 after creation");
            
            $newCategory->delete();
            $this->assertSame($countBefore, Category::count(), "Count should decrease by 1 after deletion");
        }
    }

    /**
     * Feature: admin-livewire-crud, Property 2: Deletion removes resource (with relationships)
     * Validates: Requirements 2.6
     * 
     * For any category with associated posts, deleting the category should
     * remove it from the database and detach all post relationships.
     */
    public function test_category_deletion_with_posts_removes_resource_and_relationships(): void
    {
        // Run fewer iterations for database tests with relationships
        for ($i = 0; $i < 5; $i++) {
            $faker = fake();
            
            // Create a category
            $category = Category::factory()->create();
            
            // Create random number of posts and associate them with the category
            // Set published_at to past date to make posts visible (avoid PublishedScope filtering)
            $postCount = $faker->numberBetween(1, 5);
            $posts = \App\Models\Post::factory()->count($postCount)->create([
                'published_at' => now()->subDay(),
            ]);
            
            // Attach posts to category
            $category->posts()->attach($posts->pluck('id'));

            // Property: Category should have the correct number of associated posts
            $this->assertSame($postCount, $category->posts()->count(), "Category should have {$postCount} associated posts");

            // Property: Posts should have the category in their relationships
            foreach ($posts as $post) {
                $this->assertTrue(
                    $post->categories()->where('categories.id', $category->id)->exists(),
                    "Post should have category in its relationships"
                );
            }

            // Store IDs before deletion
            $categoryId = $category->id;
            $postIds = $posts->pluck('id')->toArray();

            // Property: Delete the category
            $deleteResult = $category->delete();
            $this->assertTrue($deleteResult, "Delete operation should return true");

            // Property: Category should no longer exist in database
            $this->assertDatabaseMissing('categories', [
                'id' => $categoryId,
            ]);

            // Property: Pivot table entries should be removed
            foreach ($postIds as $postId) {
                $this->assertDatabaseMissing('category_post', [
                    'category_id' => $categoryId,
                    'post_id' => $postId,
                ]);
            }

            // Property: Posts should still exist (deletion should not cascade to posts)
            foreach ($postIds as $postId) {
                $this->assertDatabaseHas('posts', [
                    'id' => $postId,
                ]);
            }

            // Property: Posts should no longer have the deleted category
            foreach ($posts as $post) {
                $post->refresh();
                $this->assertFalse(
                    $post->categories()->where('categories.id', $categoryId)->exists(),
                    "Post should not have deleted category in its relationships"
                );
            }

            // Cleanup posts
            foreach ($posts as $post) {
                $post->delete();
            }
        }
    }

    /**
     * Feature: admin-livewire-crud, Property 2: Deletion removes resource (multiple deletions)
     * Validates: Requirements 2.6
     * 
     * For any set of categories, deleting multiple categories should remove
     * all of them from the database.
     */
    public function test_multiple_category_deletions_remove_all_resources(): void
    {
        // Run fewer iterations for bulk operations
        for ($i = 0; $i < 5; $i++) {
            $faker = fake();
            
            // Create random number of categories
            $categoryCount = $faker->numberBetween(2, 5);
            $categories = Category::factory()->count($categoryCount)->create();
            
            $categoryIds = $categories->pluck('id')->toArray();

            // Property: All categories should exist in database
            foreach ($categoryIds as $categoryId) {
                $this->assertDatabaseHas('categories', [
                    'id' => $categoryId,
                ]);
            }

            // Property: Delete all categories
            foreach ($categories as $category) {
                $deleteResult = $category->delete();
                $this->assertTrue($deleteResult, "Each delete operation should return true");
            }

            // Property: All categories should be removed from database
            foreach ($categoryIds as $categoryId) {
                $this->assertDatabaseMissing('categories', [
                    'id' => $categoryId,
                ]);
            }

            // Property: None of the categories should be retrievable
            foreach ($categoryIds as $categoryId) {
                $this->assertNull(
                    Category::find($categoryId),
                    "Category {$categoryId} should not be retrievable after deletion"
                );
            }
        }
    }
}
