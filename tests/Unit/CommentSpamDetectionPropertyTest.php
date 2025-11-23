<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property-Based Tests for Comment Spam Detection
 * 
 * These tests verify universal properties for comment spam detection functionality.
 * 
 * @group property-testing
 * @group comment-model
 * @group spam-detection
 */
final class CommentSpamDetectionPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property: Comments with excessive links (>3) should be detected as spam
     * Validates: Spam detection heuristics
     */
    public function test_detects_excessive_links_as_spam(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'author')->create();
        
        // Property: 4 or more links should be spam
        $comment = Comment::factory()->for($user)->for($post)->create([
            'content' => 'Check out http://spam1.com and http://spam2.com and http://spam3.com and http://spam4.com',
        ]);
        
        $this->assertTrue($comment->isPotentialSpam(), 'Comment with 4 links should be detected as spam');
        
        // Property: 3 or fewer links should not be spam (based on this heuristic alone)
        $comment2 = Comment::factory()->for($user)->for($post)->create([
            'content' => 'Check out http://link1.com and http://link2.com and http://link3.com',
        ]);
        
        $this->assertFalse($comment2->isPotentialSpam(), 'Comment with 3 links should not be spam');
    }

    /**
     * Property: Comments with excessive uppercase (>50%) should be detected as spam
     * Validates: Spam detection heuristics
     */
    public function test_detects_excessive_uppercase_as_spam(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'author')->create();
        
        // Property: More than 50% uppercase should be spam
        $comment = Comment::factory()->for($user)->for($post)->create([
            'content' => 'THIS IS ALL UPPERCASE SPAM MESSAGE',
        ]);
        
        $this->assertTrue($comment->isPotentialSpam(), 'Comment with excessive uppercase should be detected as spam');
        
        // Property: Normal case should not be spam
        $comment2 = Comment::factory()->for($user)->for($post)->create([
            'content' => 'This is a normal comment with proper capitalization.',
        ]);
        
        $this->assertFalse($comment2->isPotentialSpam(), 'Comment with normal capitalization should not be spam');
    }

    /**
     * Property: Very short comments (<3 chars) should be detected as spam
     * Validates: Spam detection heuristics
     */
    public function test_detects_very_short_content_as_spam(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'author')->create();
        
        // Property: Less than 3 characters should be spam
        $comment = Comment::factory()->for($user)->for($post)->create([
            'content' => 'ab',
        ]);
        
        $this->assertTrue($comment->isPotentialSpam(), 'Comment with less than 3 characters should be detected as spam');
        
        // Property: 3 or more characters should not be spam (based on this heuristic alone)
        $comment2 = Comment::factory()->for($user)->for($post)->create([
            'content' => 'abc',
        ]);
        
        $this->assertFalse($comment2->isPotentialSpam(), 'Comment with 3 or more characters should not be spam');
    }

    /**
     * Property: Multiple comments from same IP (>10) should be detected as spam
     * Validates: IP-based spam detection
     */
    public function test_detects_excessive_comments_from_same_ip_as_spam(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'author')->create();
        $ipAddress = '192.168.1.100';
        
        // Create first comment and verify it's not spam initially
        $firstComment = Comment::factory()->for($user)->for($post)->create([
            'content' => 'First comment',
            'ip_address' => $ipAddress,
        ]);
        
        // Property: First comment should not be spam (has 0 other comments from same IP)
        $this->assertFalse($firstComment->isPotentialSpam(), 'First comment from IP should not be spam');
        
        // Create 11 more comments from the same IP (total 12)
        $comments = [$firstComment];
        for ($i = 1; $i < 12; $i++) {
            $comments[] = Comment::factory()->for($user)->for($post)->create([
                'content' => "Comment number {$i}",
                'ip_address' => $ipAddress,
            ]);
        }
        
        // Property: The 12th comment should be detected as spam (has 11 other comments from same IP)
        $lastComment = end($comments);
        $this->assertTrue($lastComment->isPotentialSpam(), 'Comment from IP with >10 other comments should be detected as spam');
    }

    /**
     * Property: getCommentsFromSameIpCount should return accurate count
     * Validates: IP-based comment counting
     */
    public function test_counts_comments_from_same_ip_accurately(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'author')->create();
        $ipAddress = '192.168.1.100';
        
        // Create first comment
        $comment1 = Comment::factory()->for($user)->for($post)->create([
            'content' => 'First comment',
            'ip_address' => $ipAddress,
        ]);
        
        // Property: Should return 0 for first comment
        $this->assertEquals(0, $comment1->getCommentsFromSameIpCount(), 'First comment should have 0 other comments from same IP');
        
        // Create second comment from same IP
        $comment2 = Comment::factory()->for($user)->for($post)->create([
            'content' => 'Second comment',
            'ip_address' => $ipAddress,
        ]);
        
        // Property: Should return 1 for second comment
        $this->assertEquals(1, $comment2->getCommentsFromSameIpCount(), 'Second comment should have 1 other comment from same IP');
        
        // Property: First comment should now also return 1
        $this->assertEquals(1, $comment1->fresh()->getCommentsFromSameIpCount(), 'First comment should now have 1 other comment from same IP');
        
        // Create comment from different IP
        $comment3 = Comment::factory()->for($user)->for($post)->create([
            'content' => 'Third comment',
            'ip_address' => '192.168.1.200',
        ]);
        
        // Property: Comment from different IP should return 0
        $this->assertEquals(0, $comment3->getCommentsFromSameIpCount(), 'Comment from different IP should have 0 other comments from same IP');
    }

    /**
     * Property: Comments without IP address should return 0 count
     * Validates: Null IP handling
     */
    public function test_handles_null_ip_address_gracefully(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'author')->create();
        
        $comment = Comment::factory()->for($user)->for($post)->create([
            'content' => 'Comment without IP',
            'ip_address' => null,
        ]);
        
        // Property: Should return 0 for null IP
        $this->assertEquals(0, $comment->getCommentsFromSameIpCount(), 'Comment with null IP should return 0 count');
        
        // Property: Should not be detected as spam based on IP count
        $this->assertFalse($comment->isPotentialSpam(), 'Comment with null IP should not be spam based on IP heuristic');
    }

    /**
     * Property: Normal comments should not be detected as spam
     * Validates: False positive prevention
     */
    public function test_normal_comments_are_not_detected_as_spam(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'author')->create();
        
        $normalComments = [
            'This is a great article! Thanks for sharing.',
            'I completely agree with your points here.',
            'Could you elaborate more on this topic?',
            'Interesting perspective. I learned something new today.',
            'Well written and informative. Keep up the good work!',
        ];
        
        foreach ($normalComments as $content) {
            $comment = Comment::factory()->for($user)->for($post)->create([
                'content' => $content,
                'ip_address' => '192.168.1.' . rand(1, 254),
            ]);
            
            // Property: Normal comments should not be spam
            $this->assertFalse($comment->isPotentialSpam(), "Normal comment should not be detected as spam: {$content}");
        }
    }
}

