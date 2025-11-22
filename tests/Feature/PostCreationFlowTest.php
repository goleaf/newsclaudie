<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PostCreationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admins_can_attach_categories_when_creating_posts(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $categories = Category::factory()->count(2)->create();

        $response = $this->actingAs($admin)->post(route('posts.store'), [
            'title' => 'Categories are synced',
            'body' => 'Full body content',
            'description' => 'Post with categories',
            'featured_image' => 'https://example.com/cover.jpg',
            'published_at' => now()->addDay()->format('Y-m-d\TH:i'),
            'is_draft' => 0,
            'tags_input' => 'analysis,investigation',
            'categories' => $categories->pluck('id')->all(),
        ]);

        $response->assertRedirect();

        $post = Post::latest()->firstOrFail();

        $this->assertEqualsCanonicalizing(
            $categories->pluck('id')->all(),
            $post->categories()->pluck('categories.id')->all()
        );
    }

    public function test_draft_posts_leave_published_at_null_for_admins(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('posts.store'), [
            'title' => 'Draft post',
            'body' => 'Still being written',
            'is_draft' => 1,
            'published_at' => now()->format('Y-m-d\TH:i'),
        ]);

        $response->assertRedirect();

        $post = Post::latest()->firstOrFail();

        $this->assertNull($post->published_at);
    }

    public function test_non_admins_cannot_create_posts(): void
    {
        $author = User::factory()->create([
            'is_author' => true,
            'is_admin' => false,
        ]);

        $response = $this->actingAs($author)->post(route('posts.store'), [
            'title' => 'Unauthorized attempt',
            'body' => 'Should be rejected',
            'is_draft' => 1,
        ]);

        $response->assertForbidden();

        $this->assertDatabaseCount('posts', 0);
    }
}
