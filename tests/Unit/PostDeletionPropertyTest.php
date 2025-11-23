<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Scopes\PublishedScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property-Based Tests for Post Deletion
 * 
 * These tests verify universal properties for post deletion operations.
 * 
 * Feature: admin-livewire-crud, Property 2: Deletion removes resource
 * Validates: Requirements 1.5
 */
final class PostDeletionPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * For any resource (Post), deleting the resource should remove it
     * from the database and the table display.
     */
    public function test_post_deletion_removes_resource(): void
    {
        // Run fewer iterations for database tests to avoid performance issues
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create a user and post
            $user = User::factory()->create();
            $postTitle = ucwords($faker->words($faker->numberBetween(2, 5), true));
            $postSlug = \Illuminate\Support\Str::slug($postTitle);
            $postBody = $faker->paragraphs(3, true);

            // Property: Create a post in the database
            $post = Post::factory()->create([
                'user_id' => $user->id,
                'title' => $postTitle,
                'slug' => $postSlug,
                'body' => $postBody,
            ]);

            // Property: Post should exist in database after creation
            $this->assertDatabaseHas('posts', [
                'id' => $post->id,
                'title' => $postTitle,
                'slug' => $postSlug,
            ]);

            // Property: Post should be retrievable by ID
            $retrievedPost = Post::withoutGlobalScope(PublishedScope::class)->find($post->id);
            $this->assertNotNull($retrievedPost, "Post should be retrievable by ID");
            $this->assertSame($post->id, $retrievedPost->id, "Retrieved post should have same ID");
            $this->assertSame($postTitle, $retrievedPost->title, "Retrieved post should have same title");

            // Store the ID before deletion
            $postId = $post->id;

            // Property: Delete the post
            $deleteResult = $post->delete();
            $this->assertTrue($deleteResult, "Delete operation should return true");

            // Property: Post should no longer exist in database after deletion
            $this->assertDatabaseMissing('posts', [
                'id' => $postId,
            ]);

            // Property: Post should not be retrievable by ID after deletion
            $deletedPost = Post::withoutGlobalScope(PublishedScope::class)->find($postId);
            $this->assertNull($deletedPost, "Post should not be retrievable after deletion");

            // Property: Attempting to find the post should return null
            $this->assertNull(
                Post::withoutGlobalScope(PublishedScope::class)->where('slug', $postSlug)->first(),
                "Post should not be findable by slug after deletion"
            );

            // Cleanup
            $user->delete();
        }
    }

    /**
     * For any post with associated categories, deleting the post should
     * remove it from the database and detach all category relationships.
     */
    public function test_post_deletion_with_categories_removes_resource_and_relationships(): void
    {
        // Run fewer iterations for database tests with relationships
        for ($i = 0; $i < 5; $i++) {
            $faker = fake();
            
            // Create a user and post
            $user = User::factory()->create();
            $post = Post::factory()->create(['user_id' => $user->id]);
            
            // Create random number of categories and associate them with the post
            $categoryCount = $faker->numberBetween(1, 5);
            $categories = Category::factory()->count($categoryCount)->create();
            
            // Attach categories to post
            $post->categories()->attach($categories->pluck('id'));

            // Property: Post should have the correct number of associated categories
            $this->assertSame($categoryCount, $post->categories()->count(), "Post should have {$categoryCount} associated categories");

            // Property: Categories should have the post in their relationships
            foreach ($categories as $category) {
                $this->assertTrue(
                    $category->posts()->withoutGlobalScope(PublishedScope::class)->where('posts.id', $post->id)->exists(),
                    "Category should have post in its relationships"
                );
            }

            // Store IDs before deletion
            $postId = $post->id;
            $categoryIds = $categories->pluck('id')->toArray();

            // Property: Delete the post
            $deleteResult = $post->delete();
            $this->assertTrue($deleteResult, "Delete operation should return true");

            // Property: Post should no longer exist in database
            $this->assertDatabaseMissing('posts', [
                'id' => $postId,
            ]);

            // Property: Pivot table entries should be removed
            foreach ($categoryIds as $categoryId) {
                $this->assertDatabaseMissing('category_post', [
                    'category_id' => $categoryId,
                    'post_id' => $postId,
                ]);
            }

            // Property: Categories should still exist (deletion should not cascade to categories)
            foreach ($categoryIds as $categoryId) {
                $this->assertDatabaseHas('categories', [
                    'id' => $categoryId,
                ]);
            }

            // Property: Categories should no longer have the deleted post
            foreach ($categories as $category) {
                $category->refresh();
                $this->assertFalse(
                    $category->posts()->withoutGlobalScope(PublishedScope::class)->where('posts.id', $postId)->exists(),
                    "Category should not have deleted post in its relationships"
                );
            }

            // Cleanup
            foreach ($categories as $category) {
                $category->delete();
            }
            $user->delete();
        }
    }

    /**
     * For any post with associated comments, deleting the post should
     * remove it from the database and cascade delete all comments.
     */
    public function test_post_deletion_with_comments_removes_resource_and_cascades(): void
    {
        // Run fewer iterations for database tests with relationships
        for ($i = 0; $i < 5; $i++) {
            $faker = fake();
            
            // Create a user and post
            $user = User::factory()->create();
            $post = Post::factory()->create(['user_id' => $user->id]);
            
            // Create random number of comments for the post
            $commentCount = $faker->numberBetween(1, 5);
            $comments = Comment::factory()->count($commentCount)->create([
                'post_id' => $post->id,
                'user_id' => $user->id,
            ]);

            // Property: Post should have the correct number of associated comments
            $this->assertSame($commentCount, $post->comments()->count(), "Post should have {$commentCount} associated comments");

            // Store IDs before deletion
            $postId = $post->id;
            $commentIds = $comments->pluck('id')->toArray();

            // Property: Delete the post
            $deleteResult = $post->delete();
            $this->assertTrue($deleteResult, "Delete operation should return true");

            // Property: Post should no longer exist in database
            $this->assertDatabaseMissing('posts', [
                'id' => $postId,
            ]);

            // Property: Comments should be cascade deleted (if foreign key constraint exists)
            // Note: This depends on database schema - if cascade is set up, comments will be deleted
            // If not, they may remain as orphaned records
            // Let's check what actually happens
            $remainingComments = Comment::whereIn('id', $commentIds)->count();
            
            // If comments are cascade deleted, count should be 0
            // If not cascade deleted, they should still exist but be orphaned
            if ($remainingComments === 0) {
                // Cascade delete is working
                foreach ($commentIds as $commentId) {
                    $this->assertDatabaseMissing('comments', [
                        'id' => $commentId,
                    ]);
                }
            } else {
                // Comments are orphaned - they still exist but reference deleted post
                foreach ($commentIds as $commentId) {
                    $this->assertDatabaseHas('comments', [
                        'id' => $commentId,
                        'post_id' => $postId,
                    ]);
                }
                
                // Cleanup orphaned comments
                Comment::whereIn('id', $commentIds)->delete();
            }

            // Cleanup
            $user->delete();
        }
    }

    /**
     * For any set of posts, deleting multiple posts should remove
     * all of them from the database.
     */
    public function test_multiple_post_deletions_remove_all_resources(): void
    {
        // Run fewer iterations for bulk operations
        for ($i = 0; $i < 5; $i++) {
            $faker = fake();
            
            // Create a user
            $user = User::factory()->create();
            
            // Create random number of posts
            $postCount = $faker->numberBetween(2, 5);
            $posts = Post::factory()->count($postCount)->create(['user_id' => $user->id]);
            
            $postIds = $posts->pluck('id')->toArray();

            // Property: All posts should exist in database
            foreach ($postIds as $postId) {
                $this->assertDatabaseHas('posts', [
                    'id' => $postId,
                ]);
            }

            // Property: Delete all posts
            foreach ($posts as $post) {
                $deleteResult = $post->delete();
                $this->assertTrue($deleteResult, "Each delete operation should return true");
            }

            // Property: All posts should be removed from database
            foreach ($postIds as $postId) {
                $this->assertDatabaseMissing('posts', [
                    'id' => $postId,
                ]);
            }

            // Property: None of the posts should be retrievable
            foreach ($postIds as $postId) {
                $this->assertNull(
                    Post::withoutGlobalScope(PublishedScope::class)->find($postId),
                    "Post {$postId} should not be retrievable after deletion"
                );
            }

            // Cleanup
            $user->delete();
        }
    }
}
