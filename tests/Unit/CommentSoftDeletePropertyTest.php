<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\CommentStatus;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property-Based Tests for Comment Soft Deletes
 * 
 * These tests verify universal properties for comment soft delete functionality.
 * 
 * @group property-testing
 * @group admin-livewire-crud
 */
final class CommentSoftDeletePropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Feature: admin-livewire-crud, Property 19: Soft Delete Preservation
     * Validates: Data preservation requirements
     * 
     * Soft deleted comments should be hidden from normal queries but
     * retrievable with withTrashed().
     */
    public function test_soft_deleted_comments_are_preserved(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create test data
            $user = User::factory()->create();
            $post = Post::factory()->for($user, 'author')->create();
            
            $comment = Comment::factory()->create([
                'user_id' => $user->id,
                'post_id' => $post->id,
                'content' => $faker->sentence(),
                'status' => CommentStatus::Approved,
            ]);
            
            $commentId = $comment->id;
            
            // Property: Comment exists before deletion
            $this->assertNotNull(Comment::find($commentId), "Comment should exist before deletion");
            
            // Property: Soft delete hides comment from normal queries
            $comment->delete();
            $this->assertNull(Comment::find($commentId), "Comment should be hidden after soft delete");
            
            // Property: Soft deleted comment is retrievable with withTrashed
            $trashedComment = Comment::withTrashed()->find($commentId);
            $this->assertNotNull($trashedComment, "Comment should be retrievable with withTrashed");
            $this->assertNotNull($trashedComment->deleted_at, "Comment should have deleted_at timestamp");
            
            // Property: Comment data is preserved
            $this->assertEquals($comment->content, $trashedComment->content, "Content should be preserved");
            $this->assertEquals($comment->user_id, $trashedComment->user_id, "User ID should be preserved");
            $this->assertEquals($comment->post_id, $trashedComment->post_id, "Post ID should be preserved");
            $this->assertEquals($comment->status, $trashedComment->status, "Status should be preserved");
            
            // Cleanup
            $trashedComment->forceDelete();
            $post->delete();
            $user->delete();
        }
    }

    /**
     * Feature: admin-livewire-crud, Property 20: Soft Delete Restoration
     * Validates: Data restoration requirements
     * 
     * Soft deleted comments should be restorable to their original state.
     */
    public function test_soft_deleted_comments_can_be_restored(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create test data
            $user = User::factory()->create();
            $post = Post::factory()->for($user, 'author')->create();
            
            $comment = Comment::factory()->create([
                'user_id' => $user->id,
                'post_id' => $post->id,
                'content' => $faker->sentence(),
                'status' => CommentStatus::Approved,
            ]);
            
            $commentId = $comment->id;
            $originalContent = $comment->content;
            
            // Property: Soft delete and restore
            $comment->delete();
            $this->assertNull(Comment::find($commentId), "Comment should be hidden after delete");
            
            $trashedComment = Comment::withTrashed()->find($commentId);
            $trashedComment->restore();
            
            // Property: Restored comment is visible in normal queries
            $restoredComment = Comment::find($commentId);
            $this->assertNotNull($restoredComment, "Comment should be visible after restore");
            $this->assertNull($restoredComment->deleted_at, "deleted_at should be null after restore");
            
            // Property: Restored comment retains original data
            $this->assertEquals($originalContent, $restoredComment->content, "Content should be unchanged");
            $this->assertTrue($restoredComment->isApproved(), "Status should be unchanged");
            
            // Cleanup
            $restoredComment->forceDelete();
            $post->delete();
            $user->delete();
        }
    }

    /**
     * Feature: admin-livewire-crud, Property 21: Force Delete Permanence
     * Validates: Permanent deletion requirements
     * 
     * Force deleted comments should be permanently removed from the database.
     */
    public function test_force_deleted_comments_are_permanent(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create test data
            $user = User::factory()->create();
            $post = Post::factory()->for($user, 'author')->create();
            
            $comment = Comment::factory()->create([
                'user_id' => $user->id,
                'post_id' => $post->id,
                'content' => $faker->sentence(),
                'status' => CommentStatus::Approved,
            ]);
            
            $commentId = $comment->id;
            
            // Property: Force delete permanently removes comment
            $comment->forceDelete();
            
            $this->assertNull(Comment::find($commentId), "Comment should not be in normal queries");
            $this->assertNull(Comment::withTrashed()->find($commentId), "Comment should not be in trashed queries");
            $this->assertNull(Comment::onlyTrashed()->find($commentId), "Comment should not be in only trashed queries");
            
            // Cleanup
            $post->delete();
            $user->delete();
        }
    }

    /**
     * Feature: admin-livewire-crud, Property 22: Soft Delete Query Isolation
     * Validates: Query scope isolation
     * 
     * Soft deleted comments should not appear in filtered queries unless
     * explicitly included with withTrashed() or onlyTrashed().
     */
    public function test_soft_deleted_comments_are_isolated_from_queries(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $faker = fake();
            
            // Create test data
            $user = User::factory()->create();
            $post = Post::factory()->for($user, 'author')->create();
            
            // Create active comments
            $activeCount = 3;
            for ($j = 0; $j < $activeCount; $j++) {
                Comment::factory()->create([
                    'user_id' => $user->id,
                    'post_id' => $post->id,
                    'content' => $faker->sentence(),
                    'status' => CommentStatus::Approved,
                ]);
            }
            
            // Create and soft delete comments
            $deletedCount = 2;
            for ($j = 0; $j < $deletedCount; $j++) {
                $comment = Comment::factory()->create([
                    'user_id' => $user->id,
                    'post_id' => $post->id,
                    'content' => $faker->sentence(),
                    'status' => CommentStatus::Approved,
                ]);
                $comment->delete();
            }
            
            // Property: Normal queries exclude soft deleted
            $normalComments = Comment::all();
            $this->assertCount($activeCount, $normalComments, "Normal queries should exclude soft deleted");
            
            // Property: withTrashed includes soft deleted
            $allComments = Comment::withTrashed()->get();
            $this->assertCount($activeCount + $deletedCount, $allComments, "withTrashed should include all");
            
            // Property: onlyTrashed returns only soft deleted
            $trashedComments = Comment::onlyTrashed()->get();
            $this->assertCount($deletedCount, $trashedComments, "onlyTrashed should return only deleted");
            
            // Property: Scopes work with soft deletes
            $approvedActive = Comment::approved()->get();
            $this->assertCount($activeCount, $approvedActive, "Scopes should exclude soft deleted");
            
            $approvedAll = Comment::approved()->withTrashed()->get();
            $this->assertCount($activeCount + $deletedCount, $approvedAll, "Scopes should work with withTrashed");
            
            // Cleanup
            Comment::withTrashed()->where('post_id', $post->id)->forceDelete();
            $post->delete();
            $user->delete();
        }
    }
}
