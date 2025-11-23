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
 * Property-Based Tests for Comment Audit Trail
 * 
 * These tests verify universal properties for comment approval audit trail functionality.
 * 
 * @group property-testing
 * @group comment-model
 * @group audit-trail
 */
final class CommentAuditTrailPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property: Approving a comment should track the approver
     * Validates: Audit trail requirements
     */
    public function test_approve_tracks_approver(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);
        $post = Post::factory()->for($user, 'author')->create();
        
        $comment = Comment::factory()->for($user)->for($post)->create([
            'status' => CommentStatus::Pending,
        ]);
        
        // Property: Before approval, approved_by should be null
        $this->assertNull($comment->approved_by, 'approved_by should be null before approval');
        
        // Approve with approver
        $comment->approve($admin);
        
        // Property: After approval, approved_by should be set
        $this->assertEquals($admin->id, $comment->approved_by, 'approved_by should be set to approver ID');
        
        // Property: Approver relationship should work
        $this->assertNotNull($comment->approver, 'approver relationship should return user');
        $this->assertEquals($admin->id, $comment->approver->id, 'approver should be the admin who approved');
    }

    /**
     * Property: Approving a comment should track the approval timestamp
     * Validates: Audit trail requirements
     */
    public function test_approve_tracks_timestamp(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);
        $post = Post::factory()->for($user, 'author')->create();
        
        $comment = Comment::factory()->for($user)->for($post)->create([
            'status' => CommentStatus::Pending,
        ]);
        
        // Property: Before approval, approved_at should be null
        $this->assertNull($comment->approved_at, 'approved_at should be null before approval');
        
        $beforeApproval = now();
        sleep(1); // Ensure timestamp difference
        
        // Approve with approver
        $comment->approve($admin);
        
        $afterApproval = now();
        
        // Property: After approval, approved_at should be set
        $this->assertNotNull($comment->approved_at, 'approved_at should be set after approval');
        
        // Property: Timestamp should be between before and after
        $this->assertTrue(
            $comment->approved_at->between($beforeApproval, $afterApproval),
            'approved_at should be set to current timestamp'
        );
    }

    /**
     * Property: Approving without approver parameter should still work
     * Validates: Backward compatibility
     */
    public function test_approve_without_approver_parameter(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'author')->create();
        
        $comment = Comment::factory()->for($user)->for($post)->create([
            'status' => CommentStatus::Pending,
        ]);
        
        // Approve without approver
        $result = $comment->approve();
        
        // Property: Should return true (status changed)
        $this->assertTrue($result, 'approve() without approver should return true');
        
        // Property: Status should be approved
        $this->assertTrue($comment->isApproved(), 'Comment should be approved');
        
        // Property: approved_at should be set
        $this->assertNotNull($comment->approved_at, 'approved_at should be set even without approver');
        
        // Property: approved_by should be null
        $this->assertNull($comment->approved_by, 'approved_by should be null when no approver provided');
    }

    /**
     * Property: Rejecting or marking pending should not affect audit trail
     * Validates: Audit trail persistence
     */
    public function test_audit_trail_persists_through_status_changes(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);
        $post = Post::factory()->for($user, 'author')->create();
        
        $comment = Comment::factory()->for($user)->for($post)->create([
            'status' => CommentStatus::Pending,
        ]);
        
        // Approve with approver
        $comment->approve($admin);
        $approvedAt = $comment->approved_at;
        $approvedBy = $comment->approved_by;
        
        // Property: Audit trail should be set
        $this->assertNotNull($approvedAt, 'approved_at should be set');
        $this->assertEquals($admin->id, $approvedBy, 'approved_by should be set');
        
        // Reject the comment
        $comment->reject();
        
        // Property: Audit trail should persist after rejection
        $this->assertEquals($approvedAt, $comment->approved_at, 'approved_at should persist after rejection');
        $this->assertEquals($approvedBy, $comment->approved_by, 'approved_by should persist after rejection');
        
        // Mark as pending
        $comment->markPending();
        
        // Property: Audit trail should still persist
        $this->assertEquals($approvedAt, $comment->approved_at, 'approved_at should persist after marking pending');
        $this->assertEquals($approvedBy, $comment->approved_by, 'approved_by should persist after marking pending');
    }

    /**
     * Property: Approver relationship should handle deleted approvers gracefully
     * Validates: ON DELETE SET NULL behavior
     */
    public function test_handles_deleted_approver_gracefully(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);
        $post = Post::factory()->for($user, 'author')->create();
        
        $comment = Comment::factory()->for($user)->for($post)->create([
            'status' => CommentStatus::Pending,
        ]);
        
        // Approve with approver
        $comment->approve($admin);
        $adminId = $admin->id;
        
        // Property: Approver should be set
        $this->assertEquals($adminId, $comment->approved_by, 'approved_by should be set');
        
        // Delete the approver
        $admin->delete();
        
        // Refresh comment
        $comment = $comment->fresh();
        
        // Property: approved_by should be set to null (ON DELETE SET NULL)
        $this->assertNull($comment->approved_by, 'approved_by should be null after approver is deleted');
        
        // Property: approved_at should still be set
        $this->assertNotNull($comment->approved_at, 'approved_at should persist after approver is deleted');
        
        // Property: approver relationship should return null
        $this->assertNull($comment->approver, 'approver relationship should return null after approver is deleted');
    }

    /**
     * Property: Multiple approvals should update audit trail
     * Validates: Audit trail updates on re-approval
     */
    public function test_audit_trail_updates_on_reapproval(): void
    {
        $user = User::factory()->create();
        $admin1 = User::factory()->create(['is_admin' => true]);
        $admin2 = User::factory()->create(['is_admin' => true]);
        $post = Post::factory()->for($user, 'author')->create();
        
        $comment = Comment::factory()->for($user)->for($post)->create([
            'status' => CommentStatus::Pending,
        ]);
        
        // First approval
        $comment->approve($admin1);
        $firstApprovedAt = $comment->approved_at;
        $firstApprovedBy = $comment->approved_by;
        
        // Property: First approval should be tracked
        $this->assertEquals($admin1->id, $firstApprovedBy, 'First approver should be tracked');
        
        // Reject and re-approve with different admin
        $comment->reject();
        sleep(1); // Ensure timestamp difference
        $comment->approve($admin2);
        
        // Property: Second approval should update approved_by
        $this->assertEquals($admin2->id, $comment->approved_by, 'Second approver should replace first approver');
        
        // Property: Second approval should update approved_at
        $this->assertNotEquals($firstApprovedAt, $comment->approved_at, 'approved_at should be updated on re-approval');
        $this->assertTrue($comment->approved_at->greaterThan($firstApprovedAt), 'New approved_at should be later');
    }

    /**
     * Property: Audit trail should persist to database
     * Validates: Database persistence
     */
    public function test_audit_trail_persists_to_database(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);
        $post = Post::factory()->for($user, 'author')->create();
        
        $comment = Comment::factory()->for($user)->for($post)->create([
            'status' => CommentStatus::Pending,
        ]);
        
        $commentId = $comment->id;
        
        // Approve with approver
        $comment->approve($admin);
        
        // Fetch fresh from database
        $freshComment = Comment::find($commentId);
        
        // Property: Audit trail should be persisted
        $this->assertEquals($admin->id, $freshComment->approved_by, 'approved_by should persist to database');
        $this->assertNotNull($freshComment->approved_at, 'approved_at should persist to database');
        
        // Property: Approver relationship should work on fresh instance
        $this->assertNotNull($freshComment->approver, 'approver relationship should work on fresh instance');
        $this->assertEquals($admin->id, $freshComment->approver->id, 'approver should be correct on fresh instance');
    }

    /**
     * Property: Eager loading approver should work correctly
     * Validates: Relationship eager loading
     */
    public function test_eager_loads_approver_correctly(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);
        $post = Post::factory()->for($user, 'author')->create();
        
        // Create multiple approved comments
        $comments = [];
        for ($i = 0; $i < 5; $i++) {
            $comment = Comment::factory()->for($user)->for($post)->create([
                'status' => CommentStatus::Pending,
            ]);
            $comment->approve($admin);
            $comments[] = $comment;
        }
        
        // Eager load approver
        $loadedComments = Comment::with('approver')->whereIn('id', array_map(fn($c) => $c->id, $comments))->get();
        
        // Property: All comments should have approver loaded
        foreach ($loadedComments as $comment) {
            $this->assertNotNull($comment->approver, 'approver should be eager loaded');
            $this->assertEquals($admin->id, $comment->approver->id, 'approver should be correct');
        }
    }
}

