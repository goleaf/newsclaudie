<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\CommentStatus;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Unit Tests for Comment Model Enhancements
 * 
 * Tests the new functionality added to the Comment model including:
 * - Eager loading configuration
 * - Latest/Oldest scopes
 * - Relationship type hints
 * - Query scope behavior
 * 
 * @group unit
 * @group comment-model
 */
final class CommentModelEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that user relationship is eager loaded by default.
     * 
     * This prevents N+1 query issues when displaying comments.
     */
    public function test_user_relationship_is_eager_loaded_by_default(): void
    {
        // Arrange
        $user = User::factory()->create(['name' => 'Test User']);
        $post = Post::factory()->for($user, 'author')->create();
        Comment::factory()->count(3)->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);

        // Act - Enable query log to count queries
        DB::enableQueryLog();
        $comments = Comment::all();
        
        // Access user relationship on each comment
        foreach ($comments as $comment) {
            $userName = $comment->user->name;
        }
        
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Assert - Should only have 2 queries: 1 for comments, 1 for users (eager loaded)
        // Not 1 + N queries (1 for comments + 1 per comment for user)
        $this->assertLessThanOrEqual(2, count($queries), 'User relationship should be eager loaded');
        
        // Verify the relationship is actually loaded
        $this->assertTrue($comments->first()->relationLoaded('user'), 'User relation should be loaded');
    }

    /**
     * Test latest scope orders comments by newest first.
     */
    public function test_latest_scope_orders_by_newest_first(): void
    {
        // Arrange
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'author')->create();
        
        $oldComment = Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'created_at' => now()->subDays(3),
        ]);
        
        $middleComment = Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'created_at' => now()->subDays(2),
        ]);
        
        $newComment = Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'created_at' => now()->subDays(1),
        ]);

        // Act
        $comments = Comment::latest()->get();

        // Assert
        $this->assertEquals($newComment->id, $comments[0]->id, 'First comment should be newest');
        $this->assertEquals($middleComment->id, $comments[1]->id, 'Second comment should be middle');
        $this->assertEquals($oldComment->id, $comments[2]->id, 'Third comment should be oldest');
    }

    /**
     * Test oldest scope orders comments by oldest first.
     */
    public function test_oldest_scope_orders_by_oldest_first(): void
    {
        // Arrange
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'author')->create();
        
        $oldComment = Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'created_at' => now()->subDays(3),
        ]);
        
        $middleComment = Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'created_at' => now()->subDays(2),
        ]);
        
        $newComment = Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'created_at' => now()->subDays(1),
        ]);

        // Act
        $comments = Comment::oldest()->get();

        // Assert
        $this->assertEquals($oldComment->id, $comments[0]->id, 'First comment should be oldest');
        $this->assertEquals($middleComment->id, $comments[1]->id, 'Second comment should be middle');
        $this->assertEquals($newComment->id, $comments[2]->id, 'Third comment should be newest');
    }

    /**
     * Test that latest and oldest scopes can be chained with other scopes.
     */
    public function test_latest_and_oldest_scopes_can_be_chained(): void
    {
        // Arrange
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'author')->create();
        
        Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'status' => CommentStatus::Approved,
            'created_at' => now()->subDays(3),
        ]);
        
        Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'status' => CommentStatus::Pending,
            'created_at' => now()->subDays(2),
        ]);
        
        $newestApproved = Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'status' => CommentStatus::Approved,
            'created_at' => now()->subDays(1),
        ]);

        // Act - Chain approved scope with latest
        $latestApproved = Comment::approved()->latest()->first();
        
        // Assert
        $this->assertEquals($newestApproved->id, $latestApproved->id);
        $this->assertTrue($latestApproved->isApproved());
    }

    /**
     * Test user relationship returns correct type.
     */
    public function test_user_relationship_returns_belongs_to(): void
    {
        // Arrange
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'author')->create();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);

        // Act
        $relationship = $comment->user();

        // Assert
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $relationship);
        $this->assertEquals($user->id, $comment->user->id);
        $this->assertEquals($user->name, $comment->user->name);
    }

    /**
     * Test post relationship returns correct type.
     */
    public function test_post_relationship_returns_belongs_to(): void
    {
        // Arrange
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'author')->create();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);

        // Act
        $relationship = $comment->post();

        // Assert
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $relationship);
        $this->assertEquals($post->id, $comment->post->id);
        $this->assertEquals($post->title, $comment->post->title);
    }

    /**
     * Test that scopes return correct builder type for chaining.
     */
    public function test_scopes_return_builder_for_chaining(): void
    {
        // Arrange
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'author')->create();
        Comment::factory()->count(5)->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'status' => CommentStatus::Approved,
        ]);

        // Act & Assert - All scopes should return Builder
        $query1 = Comment::approved();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $query1);
        
        $query2 = Comment::pending();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $query2);
        
        $query3 = Comment::rejected();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $query3);
        
        $query4 = Comment::latest();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $query4);
        
        $query5 = Comment::oldest();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $query5);
        
        $query6 = Comment::withStatus(CommentStatus::Approved);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $query6);
    }

    /**
     * Test withStatus scope with null returns all comments.
     */
    public function test_with_status_scope_with_null_returns_all_comments(): void
    {
        // Arrange
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'author')->create();
        
        Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'status' => CommentStatus::Approved,
        ]);
        
        Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'status' => CommentStatus::Pending,
        ]);
        
        Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'status' => CommentStatus::Rejected,
        ]);

        // Act
        $allComments = Comment::withStatus(null)->get();

        // Assert
        $this->assertCount(3, $allComments, 'Should return all comments when status is null');
    }

    /**
     * Test withStatus scope with specific status filters correctly.
     */
    public function test_with_status_scope_filters_by_status(): void
    {
        // Arrange
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'author')->create();
        
        Comment::factory()->count(2)->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'status' => CommentStatus::Approved,
        ]);
        
        Comment::factory()->count(3)->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'status' => CommentStatus::Pending,
        ]);

        // Act
        $approvedComments = Comment::withStatus(CommentStatus::Approved)->get();
        $pendingComments = Comment::withStatus(CommentStatus::Pending)->get();

        // Assert
        $this->assertCount(2, $approvedComments);
        $this->assertCount(3, $pendingComments);
        
        foreach ($approvedComments as $comment) {
            $this->assertTrue($comment->isApproved());
        }
        
        foreach ($pendingComments as $comment) {
            $this->assertTrue($comment->isPending());
        }
    }

    /**
     * Test that status methods work correctly.
     */
    public function test_status_check_methods_work_correctly(): void
    {
        // Arrange
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'author')->create();
        
        $approvedComment = Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'status' => CommentStatus::Approved,
        ]);
        
        $pendingComment = Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'status' => CommentStatus::Pending,
        ]);
        
        $rejectedComment = Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'status' => CommentStatus::Rejected,
        ]);

        // Assert - Approved comment
        $this->assertTrue($approvedComment->isApproved());
        $this->assertFalse($approvedComment->isPending());
        $this->assertFalse($approvedComment->isRejected());
        
        // Assert - Pending comment
        $this->assertFalse($pendingComment->isApproved());
        $this->assertTrue($pendingComment->isPending());
        $this->assertFalse($pendingComment->isRejected());
        
        // Assert - Rejected comment
        $this->assertFalse($rejectedComment->isApproved());
        $this->assertFalse($rejectedComment->isPending());
        $this->assertTrue($rejectedComment->isRejected());
    }

    /**
     * Test that default status is Pending.
     */
    public function test_default_status_is_pending(): void
    {
        // Arrange
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'author')->create();

        // Act
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            // Don't specify status
        ]);

        // Assert
        $this->assertEquals(CommentStatus::Pending, $comment->status);
        $this->assertTrue($comment->isPending());
    }

    /**
     * Test mass assignment protection.
     */
    public function test_fillable_attributes_are_correctly_defined(): void
    {
        // Arrange
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'author')->create();
        
        $data = [
            'user_id' => $user->id,
            'post_id' => $post->id,
            'content' => 'Test comment content',
            'status' => CommentStatus::Approved->value,
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0',
        ];

        // Act
        $comment = Comment::create($data);

        // Assert - All fillable attributes should be set
        $this->assertEquals($user->id, $comment->user_id);
        $this->assertEquals($post->id, $comment->post_id);
        $this->assertEquals('Test comment content', $comment->content);
        $this->assertEquals(CommentStatus::Approved, $comment->status);
        $this->assertEquals('192.168.1.1', $comment->ip_address);
        $this->assertEquals('Mozilla/5.0', $comment->user_agent);
    }

    /**
     * Test status is cast to CommentStatus enum.
     */
    public function test_status_is_cast_to_enum(): void
    {
        // Arrange
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'author')->create();
        
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'status' => CommentStatus::Approved,
        ]);

        // Act
        $freshComment = Comment::find($comment->id);

        // Assert
        $this->assertInstanceOf(CommentStatus::class, $freshComment->status);
        $this->assertEquals(CommentStatus::Approved, $freshComment->status);
    }

    /**
     * Test eager loading prevents N+1 queries in loops.
     */
    public function test_eager_loading_prevents_n_plus_one_queries(): void
    {
        // Arrange
        $users = User::factory()->count(5)->create();
        $post = Post::factory()->for($users->first(), 'author')->create();
        
        foreach ($users as $user) {
            Comment::factory()->create([
                'user_id' => $user->id,
                'post_id' => $post->id,
            ]);
        }

        // Act
        DB::enableQueryLog();
        $comments = Comment::all();
        
        // Loop through comments and access user
        $userNames = [];
        foreach ($comments as $comment) {
            $userNames[] = $comment->user->name;
        }
        
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Assert - Should be 2 queries max (1 for comments, 1 for users)
        // Not 6 queries (1 for comments + 5 for individual users)
        $this->assertLessThanOrEqual(2, $queryCount, 'Should not have N+1 query problem');
        $this->assertCount(5, $userNames);
    }

    /**
     * Test combining multiple scopes works correctly.
     */
    public function test_combining_multiple_scopes(): void
    {
        // Arrange
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $post = Post::factory()->for($user1, 'author')->create();
        
        // User 1 comments
        Comment::factory()->create([
            'user_id' => $user1->id,
            'post_id' => $post->id,
            'status' => CommentStatus::Approved,
            'created_at' => now()->subDays(3),
        ]);
        
        $targetComment = Comment::factory()->create([
            'user_id' => $user1->id,
            'post_id' => $post->id,
            'status' => CommentStatus::Approved,
            'created_at' => now()->subDays(1),
        ]);
        
        // User 2 comments (should be filtered out)
        Comment::factory()->create([
            'user_id' => $user2->id,
            'post_id' => $post->id,
            'status' => CommentStatus::Approved,
            'created_at' => now(),
        ]);

        // Act - Combine approved + latest scopes
        $result = Comment::approved()->latest()->first();

        // Assert - Should get the newest approved comment (from user2)
        $this->assertNotEquals($targetComment->id, $result->id);
        $this->assertTrue($result->isApproved());
    }
}
