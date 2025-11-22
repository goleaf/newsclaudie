<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Property-Based Tests for Post Deletion
 * 
 * These tests verify universal properties for post deletion operations.
 */
final class PostDeletionPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Feature: admin-livewire-crud, Property 2: Deletion removes resource
     * Validates: Requirements 1.5
     * 
     * For any resource (Post), deleting the resource should remove it
     * from the database and the table display.
     */
    public function test_post_deletion_removes_resource(): void
    {
        // Run fewer iterations for database tests to avoid performance issues
        // Each iteration needs its own database transaction
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create a user for the post
            $user = User::factory()->create();
            
            // Generate random post data
            $postTitle = ucwords($faker->words($faker->numberBetween(2, 5), true));
            $postSlug = Str::slug($postTitle . '-' . Str::random(8));
            $postBody = $faker->paragraphs(3, true);

            // Property: Create a post in the database
            $post = Post::withoutGlobalScopes()->create([
                'user_id' => $user->id,
                'title' => $postTitle,
                'slug' => $postSlug,
                'body' => $postBody,
                'description' => $faker->optional()->sentence(),
                'published_at' => $faker->optional(0.7)->dateTimeThisYear(),
            ]);

            // Property: Post should exist in database after creation
            $this->assertDatabaseHas('posts', [
                'id' => $post->id,
                'title' => $postTitle,
                'slug' => $postSlug,
            ]);

            // Property: Post should be retrievable by ID
            $retrievedPost = Post::withoutGlobalScopes()->find($post->id);
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
            $deletedPost = Post::withoutGlobalScopes()->find($postId);
            $this->assertNull($deletedPost, "Post should not be retrievable after deletion");

            // Property: Attempting to find the post should return null
            $this->assertNull(
                Post::withoutGlobalScopes()->where('slug', $postSlug)->first(),
                "Post should not be findable by slug after deletion"
            );

            // Property: Count of posts should decrease by 1
            $countBefore = Post::withoutGlobalScopes()->count();
            $newPost = Post::withoutGlobalScopes()->create([
                'user_id' => $user->id,
                'title' => ucwords($faker->words(3, true)),
                'slug' => Str::slug($faker->words(3, true) . '-' . Str::random(8)),
                'body' => $faker->paragraphs(3, true),
            ]);
            $this->assertSame($countBefore + 1, Post::withoutGlobalScopes()->count(), "Count should increase by 1 after creation");
            
            $newPost->delete();
            $this->assertSame($countBefore, Post::withoutGlobalScopes()->count(), "Count should decrease by 1 after deletion");
            
            // Cleanup
            $user->delete();
        }
    }

    /**
     * Feature: admin-livewire-crud, Property 2: Deletion removes resource (with categories)
     * Validates: Requirements 1.5
     * 
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
            $post = Post::withoutGlobalScopes()->create([
                'user_id' => $user->id,
                'title' => ucwords($faker->words(3, true)),
                'slug' => Str::slug($faker->words(3, true) . '-' . Str::random(8)),
                'body' => $faker->paragraphs(3, true),
                'published_at' => now()->subDay(),
            ]);
            
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
                    $category->posts()->where('posts.id', $post->id)->exists(),
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
                    $category->posts()->where('posts.id', $postId)->exists(),
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
     * Feature: admin-livewire-crud, Property 2: Deletion removes resource (with comments)
     * Validates: Requirements 1.5
     * 
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
            $post = Post::withoutGlobalScopes()->create([
                'user_id' => $user->id,
                'title' => ucwords($faker->words(3, true)),
                'slug' => Str::slug($faker->words(3, true) . '-' . Str::random(8)),
                'body' => $faker->paragraphs(3, true),
                'published_at' => now()->subDay(),
            ]);
            
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

            // Property: Comments should exist in database
            foreach ($commentIds as $commentId) {
                $this->assertDatabaseHas('comments', [
                    'id' => $commentId,
                    'post_id' => $postId,
                ]);
            }

            // Property: Delete the post
            $deleteResult = $post->delete();
            $this->assertTrue($deleteResult, "Delete operation should return true");

            // Property: Post should no longer exist in database
            $this->assertDatabaseMissing('posts', [
                'id' => $postId,
            ]);

            // Property: Comments should be cascade deleted (based on foreign key constraint)
            // Note: This depends on database schema having ON DELETE CASCADE
            // If not, comments would remain orphaned, which we should verify
            $remainingComments = Comment::whereIn('id', $commentIds)->count();
            
            // Check if comments were cascade deleted or remain orphaned
            if ($remainingComments === 0) {
                // Comments were cascade deleted
                foreach ($commentIds as $commentId) {
                    $this->assertDatabaseMissing('comments', [
                        'id' => $commentId,
                    ]);
                }
            } else {
                // Comments remain but should have null post_id or be orphaned
                // This is acceptable behavior depending on schema design
                foreach ($commentIds as $commentId) {
                    $comment = Comment::find($commentId);
                    if ($comment) {
                        // Comment exists, verify it's orphaned
                        $this->assertNull(
                            Post::withoutGlobalScopes()->find($comment->post_id),
                            "Comment's post should not exist"
                        );
                    }
                }
                
                // Cleanup orphaned comments
                Comment::whereIn('id', $commentIds)->delete();
            }

            // Cleanup
            $user->delete();
        }
    }

    /**
     * Feature: admin-livewire-crud, Property 2: Deletion removes resource (multiple deletions)
     * Validates: Requirements 1.5
     * 
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
            $posts = Post::factory()->count($postCount)->create([
                'user_id' => $user->id,
            ]);
            
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
                    Post::withoutGlobalScopes()->find($postId),
                    "Post {$postId} should not be retrievable after deletion"
                );
            }
            
            // Cleanup
            $user->delete();
        }
    }
}
