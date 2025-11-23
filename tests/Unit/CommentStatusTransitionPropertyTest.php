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
 * Property-Based Tests for Comment Status Transitions
 * 
 * These tests verify universal properties for comment status transition methods.
 * 
 * @group property-testing
 * @group admin-livewire-crud
 */
final class CommentStatusTransitionPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Feature: admin-livewire-crud, Property 12: Status Transition Idempotence
     * Validates: Requirements 3.3
     * 
     * Calling a status transition method multiple times should be idempotent.
     * The first call changes the status, subsequent calls return false.
     */
    public function test_status_transitions_are_idempotent(): void
    {
        for ($i = 0; $i < 10; $i++) {
            // Create test data
            $user = User::factory()->create();
            $post = Post::factory()->for($user, 'author')->create();
            
            $comment = Comment::factory()
                ->for($user)
                ->for($post)
                ->create(['status' => CommentStatus::Pending]);
            
            // Property: First approve() call should return true
            $this->assertTrue($comment->approve(), "First approve() should return true");
            $this->assertTrue($comment->isApproved(), "Comment should be approved");
            
            // Property: Second approve() call should return false (idempotent)
            $this->assertFalse($comment->approve(), "Second approve() should return false");
            $this->assertTrue($comment->isApproved(), "Comment should still be approved");
            
            // Property: reject() should work after approve()
            $this->assertTrue($comment->reject(), "reject() should return true");
            $this->assertTrue($comment->isRejected(), "Comment should be rejected");
            
            // Property: Second reject() call should return false (idempotent)
            $this->assertFalse($comment->reject(), "Second reject() should return false");
            $this->assertTrue($comment->isRejected(), "Comment should still be rejected");
            
            // Property: markPending() should work after reject()
            $this->assertTrue($comment->markPending(), "markPending() should return true");
            $this->assertTrue($comment->isPending(), "Comment should be pending");
            
            // Property: Second markPending() call should return false (idempotent)
            $this->assertFalse($comment->markPending(), "Second markPending() should return false");
            $this->assertTrue($comment->isPending(), "Comment should still be pending");
            
            // Cleanup
            $comment->delete();
            $post->delete();
            $user->delete();
        }
    }

    /**
     * Feature: admin-livewire-crud, Property 13: Status Transition Persistence
     * Validates: Requirements 3.3
     * 
     * Status transitions should persist to the database and be retrievable.
     */
    public function test_status_transitions_persist_to_database(): void
    {
        for ($i = 0; $i < 10; $i++) {
            // Create test data
            $user = User::factory()->create();
            $post = Post::factory()->for($user, 'author')->create();
            
            $comment = Comment::factory()
                ->for($user)
                ->for($post)
                ->create(['status' => CommentStatus::Pending]);
            
            $commentId = $comment->id;
            
            // Property: approve() should persist
            $comment->approve();
            $freshComment = Comment::find($commentId);
            $this->assertTrue($freshComment->isApproved(), "Approved status should persist");
            
            // Property: reject() should persist
            $comment->reject();
            $freshComment = Comment::find($commentId);
            $this->assertTrue($freshComment->isRejected(), "Rejected status should persist");
            
            // Property: markPending() should persist
            $comment->markPending();
            $freshComment = Comment::find($commentId);
            $this->assertTrue($freshComment->isPending(), "Pending status should persist");
            
            // Cleanup
            $comment->delete();
            $post->delete();
            $user->delete();
        }
    }

    /**
     * Feature: admin-livewire-crud, Property 14: Status Transition Completeness
     * Validates: Requirements 3.3
     * 
     * All status transitions should be possible from any starting state.
     */
    public function test_all_status_transitions_are_possible(): void
    {
        $statuses = [CommentStatus::Pending, CommentStatus::Approved, CommentStatus::Rejected];
        
        foreach ($statuses as $startStatus) {
            // Create test data
            $user = User::factory()->create();
            $post = Post::factory()->for($user, 'author')->create();
            
            $comment = Comment::factory()
                ->for($user)
                ->for($post)
                ->create(['status' => $startStatus]);
            
            // Property: Can transition to Approved from any state
            $comment->status = $startStatus;
            $comment->save();
            $result = $comment->approve();
            if ($startStatus !== CommentStatus::Approved) {
                $this->assertTrue($result, "Should be able to approve from {$startStatus->value}");
            }
            $this->assertTrue($comment->isApproved(), "Should be approved");
            
            // Property: Can transition to Rejected from any state
            $comment->status = $startStatus;
            $comment->save();
            $result = $comment->reject();
            if ($startStatus !== CommentStatus::Rejected) {
                $this->assertTrue($result, "Should be able to reject from {$startStatus->value}");
            }
            $this->assertTrue($comment->isRejected(), "Should be rejected");
            
            // Property: Can transition to Pending from any state
            $comment->status = $startStatus;
            $comment->save();
            $result = $comment->markPending();
            if ($startStatus !== CommentStatus::Pending) {
                $this->assertTrue($result, "Should be able to mark pending from {$startStatus->value}");
            }
            $this->assertTrue($comment->isPending(), "Should be pending");
            
            // Cleanup
            $comment->delete();
            $post->delete();
            $user->delete();
        }
    }
}
