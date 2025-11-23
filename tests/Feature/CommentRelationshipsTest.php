<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CommentStatus;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Feature Tests for Comment Model Relationships
 * 
 * Tests the relationships and eager loading behavior of the Comment model
 * in realistic application scenarios.
 * 
 * @group feature
 * @group comment-relationships
 */
final class CommentRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test displaying a list of comments with user information doesn't cause N+1 queries.
     */
    public function test_displaying_comments_list_with_users_is_optimized(): void
    {
        // Arrange - Create realistic blog scenario
        $author = User::factory()->create(['name' => 'Blog Author']);
        $post = Post::factory()->for($author, 'author')->create([
            'title' => 'Popular Blog Post',
        ]);
        
        // Create 10 comments from different users
        $commenters = User::factory()->count(10)->create();
        foreach ($commenters as $commenter) {
            Comment::factory()->create([
                'user_id' => $commenter->id,
                'post_id' => $post->id,
                'status' => CommentStatus::Approved,
                'content' => "Comment by {$commenter->name}",
            ]);
        }

        // Act - Simulate displaying comments on a blog post
        DB::enableQueryLog();
        
        $comments = Comment::where('post_id', $post->id)
            ->approved()
            ->latest()
            ->get();
        
        // Simulate rendering comments with user names
        $renderedComments = [];
        foreach ($comments as $comment) {
            $renderedComments[] = [
                'content' => $comment->content,
                'author' => $comment->user->name,
                'date' => $comment->created_at->diffForHumans(),
            ];
        }
        
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Assert
        $this->assertCount(10, $renderedComments);
        $this->assertLessThanOrEqual(2, $queryCount, 'Should use eager loading to prevent N+1 queries');
        
        // Verify data integrity
        foreach ($renderedComments as $rendered) {
            $this->assertNotEmpty($rendered['author']);
            $this->assertNotEmpty($rendered['content']);
        }
    }

    /**
     * Test comment belongs to correct user.
     */
    public function test_comment_belongs_to_correct_user(): void
    {
        // Arrange
        $user = User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $post = Post::factory()->for($user, 'author')->create();
        
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'content' => 'Great article!',
        ]);

        // Act
        $commentUser = $comment->user;

        // Assert
        $this->assertInstanceOf(User::class, $commentUser);
        $this->assertEquals($user->id, $commentUser->id);
        $this->assertEquals('John Doe', $commentUser->name);
        $this->assertEquals('john@example.com', $commentUser->email);
    }

    /**
     * Test comment belongs to correct post.
     */
    public function test_comment_belongs_to_correct_post(): void
    {
        // Arrange
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'author')->create([
            'title' => 'Laravel Testing Best Practices',
            'slug' => 'laravel-testing-best-practices',
        ]);
        
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);

        // Act
        $commentPost = $comment->post;

        // Assert
        $this->assertInstanceOf(Post::class, $commentPost);
        $this->assertEquals($post->id, $commentPost->id);
        $this->assertEquals('Laravel Testing Best Practices', $commentPost->title);
        $this->assertEquals('laravel-testing-best-practices', $commentPost->slug);
    }

    /**
     * Test user can have multiple comments.
     */
    public function test_user_can_have_multiple_comments(): void
    {
        // Arrange
        $user = User::factory()->create();
        $post1 = Post::factory()->for($user, 'author')->create();
        $post2 = Post::factory()->for($user, 'author')->create();
        
        $comment1 = Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post1->id,
            'content' => 'First comment',
        ]);
        
        $comment2 = Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post2->id,
            'content' => 'Second comment',
        ]);
        
        $comment3 = Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post1->id,
            'content' => 'Third comment',
        ]);

        // Act
        $userComments = Comment::where('user_id', $user->id)->get();

        // Assert
        $this->assertCount(3, $userComments);
        $this->assertTrue($userComments->contains($comment1));
        $this->assertTrue($userComments->contains($comment2));
        $this->assertTrue($userComments->contains($comment3));
    }

    /**
     * Test post can have multiple comments.
     */
    public function test_post_can_have_multiple_comments(): void
    {
        // Arrange
        $author = User::factory()->create();
        $post = Post::factory()->for($author, 'author')->create();
        
        $commenters = User::factory()->count(5)->create();
        $createdComments = [];
        
        foreach ($commenters as $commenter) {
            $createdComments[] = Comment::factory()->create([
                'user_id' => $commenter->id,
                'post_id' => $post->id,
            ]);
        }

        // Act
        $postComments = Comment::where('post_id', $post->id)->get();

        // Assert
        $this->assertCount(5, $postComments);
        foreach ($createdComments as $comment) {
            $this->assertTrue($postComments->contains($comment));
        }
    }

    /**
     * Test eager loading works with query scopes.
     */
    public function test_eager_loading_works_with_approved_scope(): void
    {
        // Arrange
        $users = User::factory()->count(3)->create();
        $post = Post::factory()->for($users->first(), 'author')->create();
        
        // Create approved and pending comments
        Comment::factory()->create([
            'user_id' => $users[0]->id,
            'post_id' => $post->id,
            'status' => CommentStatus::Approved,
        ]);
        
        Comment::factory()->create([
            'user_id' => $users[1]->id,
            'post_id' => $post->id,
            'status' => CommentStatus::Pending,
        ]);
        
        Comment::factory()->create([
            'user_id' => $users[2]->id,
            'post_id' => $post->id,
            'status' => CommentStatus::Approved,
        ]);

        // Act
        DB::enableQueryLog();
        
        $approvedComments = Comment::approved()->get();
        
        // Access user relationship
        foreach ($approvedComments as $comment) {
            $userName = $comment->user->name;
        }
        
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Assert
        $this->assertCount(2, $approvedComments);
        $this->assertLessThanOrEqual(2, $queryCount, 'Eager loading should work with scopes');
    }

    /**
     * Test relationship integrity when user is deleted.
     * 
     * With cascade delete enabled, comments are automatically deleted when user is deleted.
     */
    public function test_comment_is_deleted_when_user_is_deleted(): void
    {
        // Arrange
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'author')->create();
        
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);
        
        $commentId = $comment->id;

        // Assert comment exists before deletion
        $this->assertDatabaseHas('comments', ['id' => $commentId]);

        // Act - Delete the user (cascade delete should remove comments)
        $user->delete();

        // Assert - Comment should be deleted due to cascade
        $this->assertDatabaseMissing('comments', ['id' => $commentId]);
        $this->assertNull(Comment::find($commentId));
    }

    /**
     * Test displaying comments with post information.
     */
    public function test_displaying_comments_with_post_information(): void
    {
        // Arrange
        $author = User::factory()->create();
        $commenter = User::factory()->create(); // Separate user for comments
        $posts = Post::factory()->count(3)->for($author, 'author')->create();
        
        foreach ($posts as $post) {
            Comment::factory()->count(2)->create([
                'user_id' => $commenter->id,
                'post_id' => $post->id,
                'status' => CommentStatus::Approved,
            ]);
        }

        // Act
        $comments = Comment::approved()->with('post')->get();

        // Assert
        $this->assertCount(6, $comments);
        
        foreach ($comments as $comment) {
            $this->assertInstanceOf(Post::class, $comment->post);
            $this->assertNotEmpty($comment->post->title);
            $this->assertNotEmpty($comment->post->slug);
        }
    }

    /**
     * Test comment creation with relationships.
     */
    public function test_creating_comment_establishes_relationships(): void
    {
        // Arrange
        $user = User::factory()->create(['name' => 'Commenter']);
        $post = Post::factory()->for($user, 'author')->create(['title' => 'Test Post']);

        // Act
        $comment = Comment::create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'content' => 'This is a test comment',
            'status' => CommentStatus::Pending,
        ]);

        // Assert
        $this->assertNotNull($comment->id);
        $this->assertEquals($user->id, $comment->user->id);
        $this->assertEquals('Commenter', $comment->user->name);
        $this->assertEquals($post->id, $comment->post->id);
        $this->assertEquals('Test Post', $comment->post->title);
    }

    /**
     * Test querying comments through relationships.
     */
    public function test_querying_comments_through_relationships(): void
    {
        // Arrange
        $author = User::factory()->create(); // Post author
        $commenter = User::factory()->create(); // Comment author
        $post = Post::factory()->for($author, 'author')->create();
        
        Comment::factory()->count(3)->create([
            'user_id' => $commenter->id,
            'post_id' => $post->id,
            'status' => CommentStatus::Approved,
        ]);
        
        Comment::factory()->count(2)->create([
            'user_id' => $commenter->id,
            'post_id' => $post->id,
            'status' => CommentStatus::Pending,
        ]);

        // Act
        $approvedComments = Comment::whereHas('post', function ($query) use ($post) {
            $query->where('id', $post->id);
        })->approved()->get();

        // Assert
        $this->assertCount(3, $approvedComments);
        foreach ($approvedComments as $comment) {
            $this->assertTrue($comment->isApproved());
            $this->assertEquals($post->id, $comment->post_id);
        }
    }

    /**
     * Test eager loading with multiple relationships.
     */
    public function test_eager_loading_multiple_relationships(): void
    {
        // Arrange
        $author = User::factory()->create(); // Post author
        $commenters = User::factory()->count(5)->create(); // Comment authors
        $posts = Post::factory()->count(3)->for($author, 'author')->create();
        
        foreach ($posts as $post) {
            foreach ($commenters as $commenter) {
                Comment::factory()->create([
                    'user_id' => $commenter->id,
                    'post_id' => $post->id,
                ]);
            }
        }

        // Act
        DB::enableQueryLog();
        
        // Note: 'user' is already eager loaded by default, so we just add 'post'
        $comments = Comment::with('post')->get();
        
        // Access both relationships
        foreach ($comments as $comment) {
            $userName = $comment->user->name;
            $postTitle = $comment->post->title;
        }
        
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Assert
        $this->assertCount(15, $comments); // 3 posts * 5 commenters
        // Should be 3 queries: 1 for comments, 1 for users (auto-eager), 1 for posts (explicit with)
        $this->assertLessThanOrEqual(4, $queryCount, 'Should load comments, users, and posts efficiently');
    }

    /**
     * Test relationship data is consistent across queries.
     */
    public function test_relationship_data_consistency(): void
    {
        // Arrange
        $user = User::factory()->create(['name' => 'Consistent User']);
        $post = Post::factory()->for($user, 'author')->create(['title' => 'Consistent Post']);
        
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);

        // Act - Query comment multiple times
        $comment1 = Comment::find($comment->id);
        $comment2 = Comment::with('user')->find($comment->id);
        $comment3 = Comment::with(['user', 'post'])->find($comment->id);

        // Assert - All should have consistent relationship data
        $this->assertEquals('Consistent User', $comment1->user->name);
        $this->assertEquals('Consistent User', $comment2->user->name);
        $this->assertEquals('Consistent User', $comment3->user->name);
        
        $this->assertEquals('Consistent Post', $comment1->post->title);
        $this->assertEquals('Consistent Post', $comment2->post->title);
        $this->assertEquals('Consistent Post', $comment3->post->title);
    }
}
