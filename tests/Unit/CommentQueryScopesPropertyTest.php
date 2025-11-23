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
 * Property-Based Tests for Comment Query Scopes
 * 
 * These tests verify universal properties for comment query scope functionality.
 * 
 * @group property-testing
 * @group comment-model
 * @group query-scopes
 */
final class CommentQueryScopesPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property: forPost scope should filter by post ID or Post model
     * Validates: Post filtering functionality
     */
    public function test_for_post_scope_filters_correctly(): void
    {
        $user = User::factory()->create();
        $post1 = Post::factory()->for($user, 'author')->create();
        $post2 = Post::factory()->for($user, 'author')->create();
        
        // Create comments for different posts
        $post1Comments = Comment::factory()->count(3)->create([
            'user_id' => $user->id,
            'post_id' => $post1->id,
        ]);
        
        $post2Comments = Comment::factory()->count(2)->create([
            'user_id' => $user->id,
            'post_id' => $post2->id,
        ]);
        
        // Property: forPost with Post model should return only that post's comments
        $result = Comment::forPost($post1)->get();
        $this->assertCount(3, $result, 'forPost should return correct count for post1');
        $this->assertTrue($result->every(fn($c) => $c->post_id === $post1->id), 'All comments should belong to post1');
        
        // Property: forPost with post ID should return only that post's comments
        $result = Comment::forPost($post2->id)->get();
        $this->assertCount(2, $result, 'forPost should return correct count for post2');
        $this->assertTrue($result->every(fn($c) => $c->post_id === $post2->id), 'All comments should belong to post2');
    }

    /**
     * Property: byUser scope should filter by user ID
     * Validates: User filtering functionality
     */
    public function test_by_user_scope_filters_correctly(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $post = Post::factory()->for($user1, 'author')->create();
        
        // Create comments by different users
        Comment::factory()->count(3)->create([
            'user_id' => $user1->id,
            'post_id' => $post->id,
        ]);
        
        Comment::factory()->count(2)->create([
            'user_id' => $user2->id,
            'post_id' => $post->id,
        ]);
        
        // Property: byUser should return only that user's comments
        $result = Comment::byUser($user1->id)->get();
        $this->assertCount(3, $result, 'byUser should return correct count for user1');
        $this->assertTrue($result->every(fn($c) => $c->user_id === $user1->id), 'All comments should belong to user1');
        
        $result = Comment::byUser($user2->id)->get();
        $this->assertCount(2, $result, 'byUser should return correct count for user2');
        $this->assertTrue($result->every(fn($c) => $c->user_id === $user2->id), 'All comments should belong to user2');
    }

    /**
     * Property: fromIp scope should filter by IP address
     * Validates: IP filtering functionality
     */
    public function test_from_ip_scope_filters_correctly(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'author')->create();
        
        $ip1 = '192.168.1.100';
        $ip2 = '192.168.1.200';
        
        // Create comments from different IPs
        Comment::factory()->count(3)->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'ip_address' => $ip1,
        ]);
        
        Comment::factory()->count(2)->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'ip_address' => $ip2,
        ]);
        
        // Property: fromIp should return only comments from that IP
        $result = Comment::fromIp($ip1)->get();
        $this->assertCount(3, $result, 'fromIp should return correct count for ip1');
        $this->assertTrue($result->every(fn($c) => $c->ip_address === $ip1), 'All comments should be from ip1');
        
        $result = Comment::fromIp($ip2)->get();
        $this->assertCount(2, $result, 'fromIp should return correct count for ip2');
        $this->assertTrue($result->every(fn($c) => $c->ip_address === $ip2), 'All comments should be from ip2');
    }

    /**
     * Property: awaitingModeration scope should return pending comments
     * Validates: Semantic alias functionality
     */
    public function test_awaiting_moderation_scope_returns_pending(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'author')->create();
        
        // Create comments with different statuses
        Comment::factory()->count(3)->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'status' => CommentStatus::Pending,
        ]);
        
        Comment::factory()->count(2)->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'status' => CommentStatus::Approved,
        ]);
        
        // Property: awaitingModeration should return same as pending
        $awaitingResult = Comment::awaitingModeration()->get();
        $pendingResult = Comment::pending()->get();
        
        $this->assertCount(3, $awaitingResult, 'awaitingModeration should return pending comments');
        $this->assertEquals($pendingResult->pluck('id')->sort()->values(), $awaitingResult->pluck('id')->sort()->values(), 'awaitingModeration should return same as pending');
    }

    /**
     * Property: approvedBetween scope should filter by date range
     * Validates: Date range filtering functionality
     */
    public function test_approved_between_scope_filters_by_date_range(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);
        $post = Post::factory()->for($user, 'author')->create();
        
        // Create comments approved at different times
        $oldComment = Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'status' => CommentStatus::Approved,
            'approved_at' => now()->subDays(10),
            'approved_by' => $admin->id,
        ]);
        
        $recentComment = Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'status' => CommentStatus::Approved,
            'approved_at' => now()->subDays(2),
            'approved_by' => $admin->id,
        ]);
        
        $todayComment = Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'status' => CommentStatus::Approved,
            'approved_at' => now(),
            'approved_by' => $admin->id,
        ]);
        
        // Property: Should return comments within date range
        $result = Comment::approvedBetween(
            now()->subDays(5)->format('Y-m-d'),
            now()->format('Y-m-d')
        )->get();
        
        $this->assertCount(2, $result, 'approvedBetween should return comments within range');
        $this->assertTrue($result->contains($recentComment), 'Should include recent comment');
        $this->assertTrue($result->contains($todayComment), 'Should include today comment');
        $this->assertFalse($result->contains($oldComment), 'Should not include old comment');
    }

    /**
     * Property: recent scope should limit results
     * Validates: Limit functionality
     */
    public function test_recent_scope_limits_results(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'author')->create();
        
        // Create 20 comments
        Comment::factory()->count(20)->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);
        
        // Property: recent(5) should return 5 comments
        $result = Comment::recent(5)->get();
        $this->assertCount(5, $result, 'recent(5) should return 5 comments');
        
        // Property: recent() with default should return 10 comments
        $result = Comment::recent()->get();
        $this->assertCount(10, $result, 'recent() should return 10 comments by default');
        
        // Property: Should return newest comments
        $allComments = Comment::latest()->get();
        $recentComments = Comment::recent(5)->get();
        $this->assertEquals(
            $allComments->take(5)->pluck('id'),
            $recentComments->pluck('id'),
            'recent should return newest comments'
        );
    }

    /**
     * Property: orderByDate scope should order correctly
     * Validates: Date ordering functionality
     */
    public function test_order_by_date_scope_orders_correctly(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'author')->create();
        
        // Create comments at different times
        $comment1 = Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'created_at' => now()->subDays(3),
        ]);
        
        $comment2 = Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'created_at' => now()->subDays(1),
        ]);
        
        $comment3 = Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'created_at' => now(),
        ]);
        
        // Property: orderByDate('desc') should return newest first
        $result = Comment::orderByDate('desc')->get();
        $this->assertEquals($comment3->id, $result->first()->id, 'Newest should be first with desc');
        $this->assertEquals($comment1->id, $result->last()->id, 'Oldest should be last with desc');
        
        // Property: orderByDate('asc') should return oldest first
        $result = Comment::orderByDate('asc')->get();
        $this->assertEquals($comment1->id, $result->first()->id, 'Oldest should be first with asc');
        $this->assertEquals($comment3->id, $result->last()->id, 'Newest should be last with asc');
    }

    /**
     * Property: Scopes should be chainable
     * Validates: Scope composition
     */
    public function test_scopes_are_chainable(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $post = Post::factory()->for($user1, 'author')->create();
        
        // Create various comments
        Comment::factory()->count(3)->create([
            'user_id' => $user1->id,
            'post_id' => $post->id,
            'status' => CommentStatus::Approved,
        ]);
        
        Comment::factory()->count(2)->create([
            'user_id' => $user1->id,
            'post_id' => $post->id,
            'status' => CommentStatus::Pending,
        ]);
        
        Comment::factory()->count(2)->create([
            'user_id' => $user2->id,
            'post_id' => $post->id,
            'status' => CommentStatus::Approved,
        ]);
        
        // Property: Should be able to chain multiple scopes
        $result = Comment::forPost($post)
            ->byUser($user1->id)
            ->approved()
            ->latest()
            ->get();
        
        $this->assertCount(3, $result, 'Chained scopes should filter correctly');
        $this->assertTrue($result->every(fn($c) => $c->post_id === $post->id), 'All should be from post');
        $this->assertTrue($result->every(fn($c) => $c->user_id === $user1->id), 'All should be from user1');
        $this->assertTrue($result->every(fn($c) => $c->isApproved()), 'All should be approved');
    }
}

