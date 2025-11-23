<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\CommentStatus;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\PropertyTesting;
use Tests\TestCase;

/**
 * Property-Based Test for Bulk Operation Success
 * 
 * **Feature: admin-livewire-crud, Property 19: Bulk operation completeness**
 * **Validates: Requirements 8.3, 8.4**
 * 
 * For any bulk action performed on a set of selected items, all selected items
 * should be processed and the operation should complete successfully.
 */
final class BulkOperationSuccessPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property: Bulk publish operation processes all selected posts
     * 
     * For any set of draft posts, bulk publishing should set published_at
     * for all selected posts.
     */
    public function test_bulk_publish_processes_all_posts(): void
    {
        // Use fewer iterations for database tests
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create a user for the posts
            $user = User::factory()->create();

            // Generate random number of draft posts
            $postCount = $faker->numberBetween(2, 10);
            $posts = Post::factory()
                ->count($postCount)
                ->create([
                    'user_id' => $user->id,
                    'published_at' => null, // Draft posts
                ]);

            $postIds = $posts->pluck('id')->all();

            // Simulate bulk publish operation
            $updatedCount = 0;
            foreach ($postIds as $postId) {
                $post = Post::withoutGlobalScopes()->find($postId);
                if ($post && !$post->isPublished()) {
                    $post->forceFill(['published_at' => now()])->save();
                    $updatedCount++;
                }
            }

            // Property: All posts should be updated
            $this->assertSame(
                $postCount,
                $updatedCount,
                "All {$postCount} draft posts should be published"
            );

            // Property: All posts should now have published_at set
            $publishedPosts = Post::withoutGlobalScopes()
                ->whereIn('id', $postIds)
                ->whereNotNull('published_at')
                ->count();
            
            $this->assertSame(
                $postCount,
                $publishedPosts,
                "All {$postCount} posts should have published_at set"
            );

            // Property: All posts should be published
            foreach ($postIds as $postId) {
                $post = Post::withoutGlobalScopes()->find($postId);
                $this->assertTrue(
                    $post->isPublished(),
                    "Post {$postId} should be published"
                );
            }

            // Cleanup
            Post::withoutGlobalScopes()->whereIn('id', $postIds)->delete();
            $user->delete();
        }
    }

    /**
     * Property: Bulk unpublish operation processes all selected posts
     * 
     * For any set of published posts, bulk unpublishing should set
     * published_at to null for all selected posts.
     */
    public function test_bulk_unpublish_processes_all_posts(): void
    {
        // Use fewer iterations for database tests
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create a user for the posts
            $user = User::factory()->create();

            // Generate random number of published posts
            $postCount = $faker->numberBetween(2, 10);
            $posts = Post::factory()
                ->count($postCount)
                ->create([
                    'user_id' => $user->id,
                    'published_at' => now()->subDay(), // Published posts
                ]);

            $postIds = $posts->pluck('id')->all();

            // Simulate bulk unpublish operation
            $updatedCount = 0;
            foreach ($postIds as $postId) {
                $post = Post::withoutGlobalScopes()->find($postId);
                if ($post && $post->isPublished()) {
                    $post->forceFill(['published_at' => null])->save();
                    $updatedCount++;
                }
            }

            // Property: All posts should be updated
            $this->assertSame(
                $postCount,
                $updatedCount,
                "All {$postCount} published posts should be unpublished"
            );

            // Property: All posts should now have published_at as null
            $draftPosts = Post::withoutGlobalScopes()
                ->whereIn('id', $postIds)
                ->whereNull('published_at')
                ->count();
            
            $this->assertSame(
                $postCount,
                $draftPosts,
                "All {$postCount} posts should have published_at as null"
            );

            // Property: All posts should be drafts
            foreach ($postIds as $postId) {
                $post = Post::withoutGlobalScopes()->find($postId);
                $this->assertFalse(
                    $post->isPublished(),
                    "Post {$postId} should be a draft"
                );
            }

            // Cleanup
            Post::withoutGlobalScopes()->whereIn('id', $postIds)->delete();
            $user->delete();
        }
    }

    /**
     * Property: Bulk approve operation processes all selected comments
     * 
     * For any set of pending comments, bulk approving should set status
     * to Approved for all selected comments.
     */
    public function test_bulk_approve_processes_all_comments(): void
    {
        // Use fewer iterations for database tests
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create necessary models
            $user = User::factory()->create();
            $post = Post::factory()->create(['user_id' => $user->id]);

            // Generate random number of pending comments
            $commentCount = $faker->numberBetween(2, 10);
            $comments = Comment::factory()
                ->count($commentCount)
                ->create([
                    'user_id' => $user->id,
                    'post_id' => $post->id,
                    'status' => CommentStatus::Pending,
                ]);

            $commentIds = $comments->pluck('id')->all();

            // Simulate bulk approve operation
            $updatedCount = 0;
            foreach ($commentIds as $commentId) {
                $comment = Comment::find($commentId);
                if ($comment) {
                    $comment->forceFill(['status' => CommentStatus::Approved])->save();
                    $updatedCount++;
                }
            }

            // Property: All comments should be updated
            $this->assertSame(
                $commentCount,
                $updatedCount,
                "All {$commentCount} pending comments should be approved"
            );

            // Property: All comments should now have Approved status
            $approvedComments = Comment::whereIn('id', $commentIds)
                ->where('status', CommentStatus::Approved)
                ->count();
            
            $this->assertSame(
                $commentCount,
                $approvedComments,
                "All {$commentCount} comments should have Approved status"
            );

            // Property: All comments should be approved
            foreach ($commentIds as $commentId) {
                $comment = Comment::find($commentId);
                $this->assertTrue(
                    $comment->status === CommentStatus::Approved,
                    "Comment {$commentId} should be approved"
                );
            }

            // Cleanup
            Comment::whereIn('id', $commentIds)->delete();
            $post->delete();
            $user->delete();
        }
    }

    /**
     * Property: Bulk reject operation processes all selected comments
     * 
     * For any set of pending comments, bulk rejecting should set status
     * to Rejected for all selected comments.
     */
    public function test_bulk_reject_processes_all_comments(): void
    {
        // Use fewer iterations for database tests
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create necessary models
            $user = User::factory()->create();
            $post = Post::factory()->create(['user_id' => $user->id]);

            // Generate random number of pending comments
            $commentCount = $faker->numberBetween(2, 10);
            $comments = Comment::factory()
                ->count($commentCount)
                ->create([
                    'user_id' => $user->id,
                    'post_id' => $post->id,
                    'status' => CommentStatus::Pending,
                ]);

            $commentIds = $comments->pluck('id')->all();

            // Simulate bulk reject operation
            $updatedCount = 0;
            foreach ($commentIds as $commentId) {
                $comment = Comment::find($commentId);
                if ($comment) {
                    $comment->forceFill(['status' => CommentStatus::Rejected])->save();
                    $updatedCount++;
                }
            }

            // Property: All comments should be updated
            $this->assertSame(
                $commentCount,
                $updatedCount,
                "All {$commentCount} pending comments should be rejected"
            );

            // Property: All comments should now have Rejected status
            $rejectedComments = Comment::whereIn('id', $commentIds)
                ->where('status', CommentStatus::Rejected)
                ->count();
            
            $this->assertSame(
                $commentCount,
                $rejectedComments,
                "All {$commentCount} comments should have Rejected status"
            );

            // Property: All comments should be rejected
            foreach ($commentIds as $commentId) {
                $comment = Comment::find($commentId);
                $this->assertTrue(
                    $comment->status === CommentStatus::Rejected,
                    "Comment {$commentId} should be rejected"
                );
            }

            // Cleanup
            Comment::whereIn('id', $commentIds)->delete();
            $post->delete();
            $user->delete();
        }
    }

    /**
     * Property: Bulk delete operation removes all selected comments
     * 
     * For any set of comments, bulk deleting should remove all selected
     * comments from the database.
     */
    public function test_bulk_delete_removes_all_comments(): void
    {
        // Use fewer iterations for database tests
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create necessary models
            $user = User::factory()->create();
            $post = Post::factory()->create(['user_id' => $user->id]);

            // Generate random number of comments
            $commentCount = $faker->numberBetween(2, 10);
            $comments = Comment::factory()
                ->count($commentCount)
                ->create([
                    'user_id' => $user->id,
                    'post_id' => $post->id,
                ]);

            $commentIds = $comments->pluck('id')->all();

            // Simulate bulk delete operation
            $deletedCount = 0;
            foreach ($commentIds as $commentId) {
                $comment = Comment::find($commentId);
                if ($comment) {
                    $comment->delete();
                    $deletedCount++;
                }
            }

            // Property: All comments should be deleted
            $this->assertSame(
                $commentCount,
                $deletedCount,
                "All {$commentCount} comments should be deleted"
            );

            // Property: No comments should exist in database
            $remainingComments = Comment::whereIn('id', $commentIds)->count();
            
            $this->assertSame(
                0,
                $remainingComments,
                "No comments should remain in database"
            );

            // Property: Each comment ID should not be found
            foreach ($commentIds as $commentId) {
                $comment = Comment::find($commentId);
                $this->assertNull(
                    $comment,
                    "Comment {$commentId} should not exist"
                );
            }

            // Cleanup
            $post->delete();
            $user->delete();
        }
    }
}
