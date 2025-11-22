<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PostTagInputTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function admins_can_store_posts_with_comma_separated_tags(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('posts.store'), [
            'title' => 'Investigative Dispatch',
            'body' => 'Long-form reporting body text.',
            'description' => 'Deep dive.',
            'featured_image' => 'https://example.com/cover.jpg',
            'tags_input' => 'climate, policy , climate , insights',
            'published_at' => now()->format('Y-m-d\TH:i'),
            'is_draft' => 0,
        ]);

        $response->assertRedirect();

        $post = Post::where('title', 'Investigative Dispatch')->first();

        $this->assertNotNull($post, 'Post should have been created.');
        $this->assertSame(['climate', 'policy', 'insights'], $post->tags);
    }
}
