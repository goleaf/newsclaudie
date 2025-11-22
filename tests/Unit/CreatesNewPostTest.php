<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Controllers\CreatesNewPost;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Tests\TestCase;

final class CreatesNewPostTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_creates_a_post_and_redirects_to_show(): void
    {
        $user = User::factory()->create(['is_author' => true]);

        $input = [
            'title' => 'Sample Title',
            'body' => 'Body content',
            'description' => 'Short description',
        ];

        $response = (new CreatesNewPost())->store($user, $input);

        $this->assertInstanceOf(RedirectResponse::class, $response);

        $post = Post::withoutGlobalScopes()->first();

        $this->assertNotNull($post);
        $this->assertSame('sample-title', $post->slug);
        $this->assertSame(route('posts.show', ['post' => $post]), $response->getTargetUrl());
    }

    public function test_store_generates_unique_slug_when_duplicate_exists(): void
    {
        $user = User::factory()->create(['is_author' => true]);

        $existing = Post::withoutGlobalScopes()->forceCreate([
            'user_id' => $user->id,
            'title' => 'Sample Title',
            'slug' => 'sample-title',
            'body' => 'Existing body',
            'published_at' => now(),
        ]);

        $input = [
            'title' => 'Sample Title',
            'body' => 'New body',
        ];

        (new CreatesNewPost())->store($user, $input);

        $latest = Post::withoutGlobalScopes()->orderByDesc('id')->first();

        $this->assertStringStartsWith('sample-title-', $latest->slug);
        $this->assertNotSame($existing->slug, $latest->slug);
    }
}
