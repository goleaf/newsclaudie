<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CommentStatus;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Integration Tests for Comment Model Scopes
 * 
 * Tests realistic scenarios combining multiple scopes and filters
 * to ensure they work correctly together in production use cases.
 * 
 * @group feature
 * @group comment-scopes
 * @group integration
 */
final class CommentScopeIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test admin moderation queue scenario.
     * 
     * Admins need to see pending comments ordered by newest first.
     */
    public function test_admin_moderation_queue_scenario(): void
    {
        // Arrange - Create realistic moderation scenario
        $users = User::factory()->count(5)->create();
        $post = Post::factory()->for($users->first(), 'author')->create();
        
        // Create comments with different statuses and timestamps
        $oldPending = Comment::factory()->create([
            'user_id' => $users[0]->id,
            'post_id' => $post->id,
            'status' => CommentStatus::Pending,
            'created_at' => now()->subDays(5),
            'content' => 'Old pending comment',
        ]);
        
        Comment::factory()->create([
            'user_id' => $users[1]->id,
            'post_id' => $post->id,
            'status' => CommentStatus::Approved,
            'created_at' => now()->subDays(3),
        ]);
        
        $newPending = Comment::factory()->create([
            'user_id' => $users[2]->id,
            'post_id' => $post->id,
            'status' => CommentStatus::Pending,
            'created_at' => now()->subDays(1),
            'content' => 'New pending comment',
        ]);
        
        Comment::factory()->create([
            'user_id' => $users[3]->id,
            'post_id' => $post->id,
            'status' => CommentStatus::Rejected,
            'created_at' => now()->subDays(2),
        ]);

        // Act - Get moderation queue (pending comments, newest first)
        $moderationQueue = Comment::pending()->latest()->get();

        // Assert
        $this->assertCount(2, $moderationQueue);
        $this->assertEquals($newPending->id, $moderationQueue[0]->id, 'Newest pending should be first');
        $this->assertEquals($oldPending->id, $moderationQueue[1]->id, 'Older pending should be second');
        
        foreach ($moderationQueue as $comment) {
            $this->assertTrue($comment->isPending());
        }
    }

    /**
     * Test public blog post comment display scenario.
     * 
     * Public users should only see approved comments for a specific post,
     * ordered by oldest first (chronological discussion).
     */
    public function test_public_blog_post_comments_display(): void
    {
        // Arrange
        $author = User::factory()->create();
        $post = Post::factory()->for($author, 'author')->create();
        $otherPost = Post::factory()->for($author, 'author')->create();
        
        // Create approved comments for target post
        $firstComment = Comment::factory()->create([
            'user_id' => $author->id,
            'post_id' => $post->id,
            'status' => CommentStatus::Approved,
            'created_at' => now()->subDays(3),
            'content' => 'First comment',
        ]);
        
        $secondComment = Comment::factory()->create([
            'user_id' => $author->id,
            'post_id' => $post->id,
            'status' => CommentStatus::Approved,
            'created_at' => now()->subDays(2),
            'content' => 'Second comment',
        ]);
        
        // Create pending comment (should not appear)
        Comment::factory()->create([
            'user_id' => $author->id,
            'post_id' => $post->id,
            'status' => CommentStatus::Pending,
            'created_at' => now()->subDays(1),
        ]);
        
        // Create approved comment for other post (should not appear)
        Comment::factory()->create([
            'user_id' => $author->id,
            'post_id' => $otherPost->id,
            'status' => CommentStatus::Approved,
            'created_at' => now()->subDays(1),
        ]);

        // Act - Get public comments for post
        $publicComments = Comment::query()
            ->where('post_id', $post->id)
            ->approved()
            ->oldest()
            ->get();

        // Assert
        $this->assertCount(2, $publicComments);
        $this->assertEquals($firstComment->id, $publicComments[0]->id);
        $this->assertEquals($secondComment->id, $publicComments[1]->id);
        
        foreach ($publicComments as $comment) {
            $this->assertTrue($comment->isApproved());
            $this->assertEquals($post->id, $comment->post_id);
        }
    }

    /**
     * Test user profile comments scenario.
     * 
     * Display all approved comments by a specific user across all posts.
     */
    public function test_user_profile_comments_display(): void
    {
        // Arrange
        $targetUser = User::factory()->create(['name' => 'Active Commenter']);
        $otherUser = User::factory()->create();
        $author = User::factory()->create();
        
        $post1 = Post::factory()->for($author, 'author')->create();
        $post2 = Post::factory()->for($author, 'author')->create();
        
        // Target user's approved comments
        $comment1 = Comment::factory()->create([
            'user_id' => $targetUser->id,
            'post_id' => $post1->id,
            'status' => CommentStatus::Approved,
            'created_at' => now()->subDays(3),
        ]);
        
        $comment2 = Comment::factory()->create([
            'user_id' => $targetUser->id,
            'post_id' => $post2->id,
            'status' => CommentStatus::Approved,
            'created_at' => now()->subDays(1),
        ]);
        
        // Target user's pending comment (should not appear)
        Comment::factory()->create([
            'user_id' => $targetUser->id,
            'post_id' => $post1->id,
            'status' => CommentStatus::Pending,
        ]);
        
        // Other user's comment (should not appear)
        Comment::factory()->create([
            'user_id' => $otherUser->id,
            'post_id' => $post1->id,
            'status' => CommentStatus::Approved,
        ]);

        // Act - Get user's public comments
        $userComments = Comment::query()
            ->where('user_id', $targetUser->id)
            ->approved()
            ->latest()
            ->get();

        // Assert
        $this->assertCount(2, $userComments);
        $this->assertEquals($comment2->id, $userComments[0]->id, 'Newest first');
        $this->assertEquals($comment1->id, $userComments[1]->id, 'Older second');
        
        foreach ($userComments as $comment) {
            $this->assertEquals($targetUser->id, $comment->user_id);
            $this->assertTrue($comment->isApproved());
        }
    }

    /**
     * Test filtering comments by status with pagination.
     */
    public function test_filtering_comments_by_status_with_pagination(): void
    {
        // Arrange
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'author')->create();
        
        // Create 15 approved comments
        Comment::factory()->count(15)->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'status' => CommentStatus::Approved,
        ]);
        
        // Create 10 pending comments
        Comment::factory()->count(10)->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'status' => CommentStatus::Pending,
        ]);

        // Act
        $approvedPage1 = Comment::approved()->latest()->paginate(10);
        $approvedPage2 = Comment::approved()->latest()->paginate(10, ['*'], 'page', 2);
        $pendingPage1 = Comment::pending()->latest()->paginate(10);

        // Assert
        $this->assertCount(10, $approvedPage1);
        $this->assertCount(5, $approvedPage2);
        $this->assertCount(10, $pendingPage1);
        $this->assertEquals(15, $approvedPage1->total());
        $this->assertEquals(10, $pendingPage1->total());
    }

    /**
     * Test combining withStatus scope with other filters.
     */
    public function test_with_status_scope_combined_with_filters(): void
    {
        // Arrange
        $user = User::factory()->create();
        $post1 = Post::factory()->for($user, 'author')->create();
        $post2 = Post::factory()->for($user, 'author')->create();
        
        // Post 1 comments
        Comment::factory()->count(3)->create([
            'user_id' => $user->id,
            'post_id' => $post1->id,
            'status' => CommentStatus::Approved,
        ]);
        
        Comment::factory()->count(2)->create([
            'user_id' => $user->id,
            'post_id' => $post1->id,
            'status' => CommentStatus::Pending,
        ]);
        
        // Post 2 comments
        Comment::factory()->count(4)->create([
            'user_id' => $user->id,
            'post_id' => $post2->id,
            'status' => CommentStatus::Approved,
        ]);

        // Act - Filter by post and status
        $post1Approved = Comment::query()
            ->where('post_id', $post1->id)
            ->withStatus(CommentStatus::Approved)
            ->get();
        
        $post1All = Comment::query()
            ->where('post_id', $post1->id)
            ->withStatus(null)
            ->get();

        // Assert
        $this->assertCount(3, $post1Approved);
        $this->assertCount(5, $post1All);
    }

    /**
     * Test recent comments widget scenario.
     * 
     * Display the 5 most recent approved comments across all posts.
     */
    public function test_recent_comments_widget(): void
    {
        // Arrange
        $users = User::factory()->count(10)->create();
        $post = Post::factory()->for($users->first(), 'author')->create();
        
        // Create 10 approved comments
        $comments = [];
        for ($i = 0; $i < 10; $i++) {
            $comments[] = Comment::factory()->create([
                'user_id' => $users[$i]->id,
                'post_id' => $post->id,
                'status' => CommentStatus::Approved,
                'created_at' => now()->subDays(10 - $i),
            ]);
        }
        
        // Create some pending comments (should not appear)
        Comment::factory()->count(3)->create([
            'user_id' => $users[0]->id,
            'post_id' => $post->id,
            'status' => CommentStatus::Pending,
            'created_at' => now(),
        ]);

        // Act - Get 5 most recent approved comments
        $recentComments = Comment::approved()->latest()->limit(5)->get();

        // Assert
        $this->assertCount(5, $recentComments);
        
        // Verify they are the 5 newest
        $expectedIds = array_slice(array_reverse(array_column($comments, 'id')), 0, 5);
        $actualIds = $recentComments->pluck('id')->toArray();
        
        $this->assertEquals($expectedIds, $actualIds);
        
        foreach ($recentComments as $comment) {
            $this->assertTrue($comment->isApproved());
        }
    }

    /**
     * Test comment statistics scenario.
     * 
     * Get counts of comments by status for dashboard.
     */
    public function test_comment_statistics_by_status(): void
    {
        // Arrange
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'author')->create();
        
        Comment::factory()->count(15)->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'status' => CommentStatus::Approved,
        ]);
        
        Comment::factory()->count(8)->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'status' => CommentStatus::Pending,
        ]);
        
        Comment::factory()->count(3)->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'status' => CommentStatus::Rejected,
        ]);

        // Act
        $approvedCount = Comment::approved()->count();
        $pendingCount = Comment::pending()->count();
        $rejectedCount = Comment::rejected()->count();
        $totalCount = Comment::count();

        // Assert
        $this->assertEquals(15, $approvedCount);
        $this->assertEquals(8, $pendingCount);
        $this->assertEquals(3, $rejectedCount);
        $this->assertEquals(26, $totalCount);
    }

    /**
     * Test searching comments with status filter.
     */
    public function test_searching_comments_with_status_filter(): void
    {
        // Arrange
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'author')->create();
        
        Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'status' => CommentStatus::Approved,
            'content' => 'This is about Laravel testing',
        ]);
        
        Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'status' => CommentStatus::Pending,
            'content' => 'Laravel is awesome for testing',
        ]);
        
        Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'status' => CommentStatus::Approved,
            'content' => 'PHP is great',
        ]);

        // Act - Search for "Laravel" in approved comments only
        $results = Comment::approved()
            ->where('content', 'like', '%Laravel%')
            ->get();

        // Assert
        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->isApproved());
        $this->assertStringContainsString('Laravel', $results->first()->content);
    }

    /**
     * Test ordering comments by different criteria.
     */
    public function test_ordering_comments_by_different_criteria(): void
    {
        // Arrange
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'author')->create();
        
        $old = Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'created_at' => now()->subDays(5),
            'content' => 'Old comment',
        ]);
        
        $middle = Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'created_at' => now()->subDays(3),
            'content' => 'Middle comment',
        ]);
        
        $new = Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'created_at' => now()->subDays(1),
            'content' => 'New comment',
        ]);

        // Act
        $latestFirst = Comment::latest()->get();
        $oldestFirst = Comment::oldest()->get();

        // Assert - Latest first
        $this->assertEquals($new->id, $latestFirst[0]->id);
        $this->assertEquals($middle->id, $latestFirst[1]->id);
        $this->assertEquals($old->id, $latestFirst[2]->id);
        
        // Assert - Oldest first
        $this->assertEquals($old->id, $oldestFirst[0]->id);
        $this->assertEquals($middle->id, $oldestFirst[1]->id);
        $this->assertEquals($new->id, $oldestFirst[2]->id);
    }

    /**
     * Test complex query combining multiple scopes and conditions.
     */
    public function test_complex_query_with_multiple_scopes(): void
    {
        // Arrange
        $targetUser = User::factory()->create();
        $otherUser = User::factory()->create();
        $post1 = Post::factory()->for($targetUser, 'author')->create();
        $post2 = Post::factory()->for($targetUser, 'author')->create();
        
        // Target: approved comments by targetUser on post1, newest first
        $target1 = Comment::factory()->create([
            'user_id' => $targetUser->id,
            'post_id' => $post1->id,
            'status' => CommentStatus::Approved,
            'created_at' => now()->subDays(2),
        ]);
        
        $target2 = Comment::factory()->create([
            'user_id' => $targetUser->id,
            'post_id' => $post1->id,
            'status' => CommentStatus::Approved,
            'created_at' => now()->subDays(1),
        ]);
        
        // Noise: different combinations that should be filtered out
        Comment::factory()->create([
            'user_id' => $targetUser->id,
            'post_id' => $post1->id,
            'status' => CommentStatus::Pending, // Wrong status
        ]);
        
        Comment::factory()->create([
            'user_id' => $targetUser->id,
            'post_id' => $post2->id, // Wrong post
            'status' => CommentStatus::Approved,
        ]);
        
        Comment::factory()->create([
            'user_id' => $otherUser->id, // Wrong user
            'post_id' => $post1->id,
            'status' => CommentStatus::Approved,
        ]);

        // Act - Complex query
        $results = Comment::query()
            ->where('user_id', $targetUser->id)
            ->where('post_id', $post1->id)
            ->approved()
            ->latest()
            ->get();

        // Assert
        $this->assertCount(2, $results);
        $this->assertEquals($target2->id, $results[0]->id);
        $this->assertEquals($target1->id, $results[1]->id);
        
        foreach ($results as $comment) {
            $this->assertEquals($targetUser->id, $comment->user_id);
            $this->assertEquals($post1->id, $comment->post_id);
            $this->assertTrue($comment->isApproved());
        }
    }
}
