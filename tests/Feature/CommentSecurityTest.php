<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Security Tests for Comment Functionality
 * 
 * Tests critical security features including:
 * - Rate limiting
 * - Spam detection
 * - XSS prevention
 * - IP tracking
 * - Content sanitization
 * 
 * @group security
 * @group comments
 */
final class CommentSecurityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * SECURITY TEST: Rate limiting prevents comment spam
     */
    public function test_rate_limiting_prevents_excessive_comment_creation(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'author')->create();
        
        // Clear any existing rate limits
        RateLimiter::clear('comments:' . $user->id);
        
        $this->actingAs($user);
        
        // First 10 comments should succeed
        for ($i = 0; $i < 10; $i++) {
            $response = $this->post(route('posts.comments.store', $post), [
                'content' => "Test comment {$i}",
            ]);
            
            $response->assertSessionHasNoErrors();
        }
        
        // 11th comment should be rate limited
        $response = $this->post(route('posts.comments.store', $post), [
            'content' => 'This should be rate limited',
        ]);
        
        $response->assertStatus(429); // Too Many Requests
    }

    /**
     * SECURITY TEST: IP address is tracked for all comments
     */
    public function test_ip_address_is_tracked_on_comment_creation(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'author')->create();
        
        $this->actingAs($user);
        
        $response = $this->post(route('posts.comments.store', $post), [
            'content' => 'Test comment with IP tracking',
        ]);
        
        $comment = Comment::latest()->first();
        
        $this->assertNotNull($comment->ip_address, 'IP address should be tracked');
        $this->assertNotEmpty($comment->ip_address, 'IP address should not be empty');
    }

    /**
     * SECURITY TEST: User agent is tracked for all comments
     */
    public function test_user_agent_is_tracked_on_comment_creation(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'author')->create();
        
        $this->actingAs($user);
        
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Test Browser)',
        ])->post(route('posts.comments.store', $post), [
            'content' => 'Test comment with user agent tracking',
        ]);
        
        $comment = Comment::latest()->first();
        
        $this->assertNotNull($comment->user_agent, 'User agent should be tracked');
        $this->assertStringContainsString('Mozilla', $comment->user_agent);
    }

    /**
     * SECURITY TEST: HTML tags are stripped to prevent XSS
     */
    public function test_html_tags_are_stripped_from_comment_content(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'author')->create();
        
        $this->actingAs($user);
        
        $maliciousContent = '<script>alert("XSS")</script>Hello World<img src=x onerror="alert(1)">';
        
        $response = $this->post(route('posts.comments.store', $post), [
            'content' => $maliciousContent,
        ]);
        
        $comment = Comment::latest()->first();
        
        $this->assertStringNotContainsString('<script>', $comment->content);
        $this->assertStringNotContainsString('<img', $comment->content);
        $this->assertStringNotContainsString('onerror', $comment->content);
        $this->assertEquals('Hello World', $comment->content);
    }

    /**
     * SECURITY TEST: Excessive links are rejected
     */
    public function test_comments_with_excessive_links_are_rejected(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'author')->create();
        
        $this->actingAs($user);
        
        $spamContent = 'Check out http://spam1.com and http://spam2.com and http://spam3.com and http://spam4.com';
        
        $response = $this->post(route('posts.comments.store', $post), [
            'content' => $spamContent,
        ]);
        
        $response->assertSessionHasErrors('content');
    }

    /**
     * SECURITY TEST: Excessive uppercase is rejected
     */
    public function test_comments_with_excessive_uppercase_are_rejected(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'author')->create();
        
        $this->actingAs($user);
        
        $spamContent = 'THIS IS ALL UPPERCASE SPAM MESSAGE THAT SHOULD BE REJECTED';
        
        $response = $this->post(route('posts.comments.store', $post), [
            'content' => $spamContent,
        ]);
        
        $response->assertSessionHasErrors('content');
    }

    /**
     * SECURITY TEST: Spam detection flags suspicious comments
     */
    public function test_spam_detection_flags_suspicious_comments(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'author')->create();
        
        // Create a comment that will be flagged as spam
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'content' => 'BUY NOW!!! http://spam1.com http://spam2.com http://spam3.com http://spam4.com',
            'ip_address' => '192.168.1.100',
        ]);
        
        $this->assertTrue($comment->isPotentialSpam(), 'Comment should be flagged as spam');
    }

    /**
     * SECURITY TEST: Very short comments are flagged as spam
     */
    public function test_very_short_comments_are_flagged_as_spam(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'author')->create();
        
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'content' => 'ab', // Only 2 characters
            'ip_address' => '192.168.1.100',
        ]);
        
        $this->assertTrue($comment->isPotentialSpam(), 'Very short comment should be flagged as spam');
    }

    /**
     * SECURITY TEST: Minimum content length is enforced
     */
    public function test_minimum_content_length_is_enforced(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'author')->create();
        
        $this->actingAs($user);
        
        $response = $this->post(route('posts.comments.store', $post), [
            'content' => 'ab', // Only 2 characters
        ]);
        
        $response->assertSessionHasErrors('content');
    }

    /**
     * SECURITY TEST: Maximum content length is enforced
     */
    public function test_maximum_content_length_is_enforced(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'author')->create();
        
        $this->actingAs($user);
        
        $response = $this->post(route('posts.comments.store', $post), [
            'content' => str_repeat('a', 5001), // Exceeds 5000 character limit
        ]);
        
        $response->assertSessionHasErrors('content');
    }

    /**
     * SECURITY TEST: IP masking protects user privacy
     */
    public function test_ip_masking_protects_user_privacy(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'author')->create();
        
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'ip_address' => '192.168.1.100',
        ]);
        
        $maskedIp = $comment->masked_ip;
        
        $this->assertEquals('192.168.1.xxx', $maskedIp);
        $this->assertStringNotContainsString('100', $maskedIp);
    }

    /**
     * SECURITY TEST: IPv6 addresses are masked correctly
     */
    public function test_ipv6_addresses_are_masked_correctly(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'author')->create();
        
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'ip_address' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334',
        ]);
        
        $maskedIp = $comment->masked_ip;
        
        $this->assertStringEndsWith('xxxx', $maskedIp);
        $this->assertStringNotContainsString('7334', $maskedIp);
    }

    /**
     * SECURITY TEST: Comments from same IP are tracked
     */
    public function test_comments_from_same_ip_are_tracked(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'author')->create();
        $ipAddress = '192.168.1.100';
        
        // Create 5 comments from same IP
        for ($i = 0; $i < 5; $i++) {
            Comment::factory()->create([
                'user_id' => $user->id,
                'post_id' => $post->id,
                'content' => "Comment {$i}",
                'ip_address' => $ipAddress,
            ]);
        }
        
        $latestComment = Comment::latest()->first();
        $count = $latestComment->getCommentsFromSameIpCount();
        
        $this->assertEquals(4, $count, 'Should count 4 other comments from same IP');
    }

    /**
     * SECURITY TEST: High frequency from same IP is flagged as spam
     */
    public function test_high_frequency_from_same_ip_is_flagged_as_spam(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'author')->create();
        $ipAddress = '192.168.1.100';
        
        // Create 11 comments from same IP
        for ($i = 0; $i < 11; $i++) {
            Comment::factory()->create([
                'user_id' => $user->id,
                'post_id' => $post->id,
                'content' => "Comment {$i} with enough text to pass validation",
                'ip_address' => $ipAddress,
            ]);
        }
        
        $latestComment = Comment::latest()->first();
        
        $this->assertTrue($latestComment->isPotentialSpam(), 'High frequency from same IP should be flagged as spam');
    }

    /**
     * SECURITY TEST: Normal comments are not flagged as spam
     */
    public function test_normal_comments_are_not_flagged_as_spam(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'author')->create();
        
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'content' => 'This is a perfectly normal comment with good content.',
            'ip_address' => '192.168.1.100',
        ]);
        
        $this->assertFalse($comment->isPotentialSpam(), 'Normal comment should not be flagged as spam');
    }

    /**
     * SECURITY TEST: Unauthenticated users cannot create comments
     */
    public function test_unauthenticated_users_cannot_create_comments(): void
    {
        $post = Post::factory()->create();
        
        $response = $this->post(route('posts.comments.store', $post), [
            'content' => 'This should not be allowed',
        ]);
        
        $response->assertRedirect(route('login'));
    }

    /**
     * SECURITY TEST: Users can only edit their own comments
     */
    public function test_users_can_only_edit_their_own_comments(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $post = Post::factory()->for($user1, 'author')->create();
        
        $comment = Comment::factory()->create([
            'user_id' => $user1->id,
            'post_id' => $post->id,
        ]);
        
        $this->actingAs($user2);
        
        $response = $this->put(route('comments.update', $comment), [
            'content' => 'Trying to edit someone elses comment',
        ]);
        
        $response->assertForbidden();
    }

    /**
     * SECURITY TEST: Users can only delete their own comments
     */
    public function test_users_can_only_delete_their_own_comments(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $post = Post::factory()->for($user1, 'author')->create();
        
        $comment = Comment::factory()->create([
            'user_id' => $user1->id,
            'post_id' => $post->id,
        ]);
        
        $this->actingAs($user2);
        
        $response = $this->delete(route('comments.destroy', $comment));
        
        $response->assertForbidden();
    }

    /**
     * SECURITY TEST: Admins can moderate any comment
     */
    public function test_admins_can_moderate_any_comment(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'author')->create();
        
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);
        
        $this->actingAs($admin);
        
        // Admin should be able to edit
        $response = $this->put(route('comments.update', $comment), [
            'content' => 'Admin editing this comment',
        ]);
        
        $response->assertSessionHasNoErrors();
        
        // Admin should be able to delete
        $response = $this->delete(route('comments.destroy', $comment));
        
        $response->assertSessionHasNoErrors();
    }
}

