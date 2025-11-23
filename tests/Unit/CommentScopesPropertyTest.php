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
 * These tests verify universal properties for comment query scopes.
 * 
 * @group property-testing
 * @group admin-livewire-crud
 */
final class CommentScopesPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Feature: admin-livewire-crud, Property 15: ForPost Scope Accuracy
     * Validates: Requirements 3.1
     * 
     * The forPost scope should return only comments for the specified post.
     */
    public function test_for_post_scope_returns_only_post_comments(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create test data
            $user = User::factory()->create();
            $post1 = Post::factory()->for($user, 'author')->create();
            $post2 = Post::factory()->for($user, 'author')->create();
            
            // Create comments for post1
            $post1CommentCount = $faker->numberBetween(2, 5);
            for ($j = 0; $j < $post1CommentCount; $j++) {
                Comment::factory()->create([
                    'user_id' => $user->id,
                    'post_id' => $post1->id,
                    'content' => $faker->sentence(),
                    'status' => CommentStatus::Approved,
                ]);
            }
            
            // Create comments for post2
            $post2CommentCount = $faker->numberBetween(2, 5);
            for ($j = 0; $j < $post2CommentCount; $j++) {
                Comment::factory()->create([
                    'user_id' => $user->id,
                    'post_id' => $post2->id,
                    'content' => $faker->sentence(),
                    'status' => CommentStatus::Approved,
                ]);
            }
            
            // Property: forPost(post1) should return only post1 comments
            $post1Comments = Comment::forPost($post1)->get();
            $this->assertCount($post1CommentCount, $post1Comments, "Should return exact count for post1");
            foreach ($post1Comments as $comment) {
                $this->assertEquals($post1->id, $comment->post_id, "All comments should belong to post1");
            }
            
            // Property: forPost(post2) should return only post2 comments
            $post2Comments = Comment::forPost($post2)->get();
            $this->assertCount($post2CommentCount, $post2Comments, "Should return exact count for post2");
            foreach ($post2Comments as $comment) {
                $this->assertEquals($post2->id, $comment->post_id, "All comments should belong to post2");
            }
            
            // Property: forPost with ID should work the same
            $post1CommentsById = Comment::forPost($post1->id)->get();
            $this->assertCount($post1CommentCount, $post1CommentsById, "Should work with post ID");
            
            // Cleanup
            Comment::whereIn('post_id', [$post1->id, $post2->id])->delete();
            $post1->delete();
            $post2->delete();
            $user->delete();
        }
    }

    /**
     * Feature: admin-livewire-crud, Property 16: ByUser Scope Accuracy
     * Validates: Requirements 3.1
     * 
     * The byUser scope should return only comments by the specified user.
     */
    public function test_by_user_scope_returns_only_user_comments(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $faker = fake();
            
            // Create test data
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();
            $post = Post::factory()->for($user1, 'author')->create();
            
            // Create comments by user1
            $user1CommentCount = $faker->numberBetween(2, 5);
            for ($j = 0; $j < $user1CommentCount; $j++) {
                Comment::factory()->create([
                    'user_id' => $user1->id,
                    'post_id' => $post->id,
                    'content' => $faker->sentence(),
                    'status' => CommentStatus::Approved,
                ]);
            }
            
            // Create comments by user2
            $user2CommentCount = $faker->numberBetween(2, 5);
            for ($j = 0; $j < $user2CommentCount; $j++) {
                Comment::factory()->create([
                    'user_id' => $user2->id,
                    'post_id' => $post->id,
                    'content' => $faker->sentence(),
                    'status' => CommentStatus::Approved,
                ]);
            }
            
            // Property: byUser(user1) should return only user1 comments
            $user1Comments = Comment::byUser($user1->id)->get();
            $this->assertCount($user1CommentCount, $user1Comments, "Should return exact count for user1");
            foreach ($user1Comments as $comment) {
                $this->assertEquals($user1->id, $comment->user_id, "All comments should belong to user1");
            }
            
            // Property: byUser(user2) should return only user2 comments
            $user2Comments = Comment::byUser($user2->id)->get();
            $this->assertCount($user2CommentCount, $user2Comments, "Should return exact count for user2");
            foreach ($user2Comments as $comment) {
                $this->assertEquals($user2->id, $comment->user_id, "All comments should belong to user2");
            }
            
            // Cleanup
            Comment::where('post_id', $post->id)->delete();
            $post->delete();
            $user1->delete();
            $user2->delete();
        }
    }

    /**
     * Feature: admin-livewire-crud, Property 17: Recent Scope Limit
     * Validates: Requirements 3.1
     * 
     * The recent scope should return at most the specified number of comments.
     */
    public function test_recent_scope_respects_limit(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $faker = fake();
            
            // Create test data
            $user = User::factory()->create();
            $post = Post::factory()->for($user, 'author')->create();
            
            // Create more comments than the limit
            $totalComments = 20;
            for ($j = 0; $j < $totalComments; $j++) {
                Comment::factory()->create([
                    'user_id' => $user->id,
                    'post_id' => $post->id,
                    'content' => $faker->sentence(),
                    'status' => CommentStatus::Approved,
                ]);
            }
            
            // Property: recent(10) should return exactly 10 comments
            $limit = 10;
            $recentComments = Comment::recent($limit)->get();
            $this->assertCount($limit, $recentComments, "Should return exactly {$limit} comments");
            
            // Property: Comments should be ordered by newest first
            $previousDate = null;
            foreach ($recentComments as $comment) {
                if ($previousDate !== null) {
                    $this->assertGreaterThanOrEqual(
                        $comment->created_at->timestamp,
                        $previousDate->timestamp,
                        "Comments should be ordered newest first"
                    );
                }
                $previousDate = $comment->created_at;
            }
            
            // Cleanup
            Comment::where('post_id', $post->id)->delete();
            $post->delete();
            $user->delete();
        }
    }

    /**
     * Feature: admin-livewire-crud, Property 18: OrderByDate Direction
     * Validates: Requirements 3.1
     * 
     * The orderByDate scope should respect the direction parameter.
     */
    public function test_order_by_date_respects_direction(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $faker = fake();
            
            // Create test data
            $user = User::factory()->create();
            $post = Post::factory()->for($user, 'author')->create();
            
            // Create comments with different timestamps
            $commentCount = 5;
            for ($j = 0; $j < $commentCount; $j++) {
                Comment::factory()->create([
                    'user_id' => $user->id,
                    'post_id' => $post->id,
                    'content' => $faker->sentence(),
                    'status' => CommentStatus::Approved,
                    'created_at' => now()->subMinutes($commentCount - $j),
                ]);
            }
            
            // Property: orderByDate('desc') should return newest first
            $descComments = Comment::orderByDate('desc')->get();
            $previousDate = null;
            foreach ($descComments as $comment) {
                if ($previousDate !== null) {
                    $this->assertGreaterThanOrEqual(
                        $comment->created_at->timestamp,
                        $previousDate->timestamp,
                        "DESC order should be newest first"
                    );
                }
                $previousDate = $comment->created_at;
            }
            
            // Property: orderByDate('asc') should return oldest first
            $ascComments = Comment::orderByDate('asc')->get();
            $previousDate = null;
            foreach ($ascComments as $comment) {
                if ($previousDate !== null) {
                    $this->assertLessThanOrEqual(
                        $comment->created_at->timestamp,
                        $previousDate->timestamp,
                        "ASC order should be oldest first"
                    );
                }
                $previousDate = $comment->created_at;
            }
            
            // Cleanup
            Comment::where('post_id', $post->id)->delete();
            $post->delete();
            $user->delete();
        }
    }
}
