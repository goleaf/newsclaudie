<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CommentControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function admins_can_store_comments_via_the_http_form(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $post = Post::factory()->for($admin, 'author')->create();

        $response = $this
            ->actingAs($admin)
            ->post(route('posts.comments.store', $post), [
                'content' => 'Great read.',
            ]);

        $response->assertRedirect(route('posts.show', $post));

        $this->assertDatabaseHas('comments', [
            'post_id' => $post->id,
            'user_id' => $admin->id,
            'content' => 'Great read.',
        ]);
    }

    /** @test */
    public function comment_authors_can_update_their_entries(): void
    {
        $user = User::factory()->create([
            'is_admin' => true,
        ]);

        $post = Post::factory()->for($user, 'author')->create();

        $comment = Comment::factory()
            ->for($post)
            ->for($user)
            ->create([
                'content' => 'Original body',
            ]);

        $response = $this
            ->actingAs($user)
            ->put(route('comments.update', $comment), [
                'content' => 'Updated body copy',
            ]);

        $response->assertRedirect(route('posts.show', $post));
        $this->assertSame('Updated body copy', $comment->fresh()->content);
    }

    /** @test */
    public function guests_cannot_store_comments(): void
    {
        $post = Post::factory()->create();

        $response = $this->post(route('posts.comments.store', $post), [
            'content' => 'Unauthorized content',
        ]);

        $response->assertForbidden();

        $this->assertDatabaseMissing('comments', [
            'post_id' => $post->id,
            'content' => 'Unauthorized content',
        ]);
    }
}


