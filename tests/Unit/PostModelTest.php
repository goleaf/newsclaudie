<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PostModelTest extends TestCase
{
    use RefreshDatabase;

    private User $author;

    protected function setUp(): void
    {
        parent::setUp();

        $this->author = User::factory()->create(['is_author' => true]);
    }

    public function test_description_falls_back_to_body_when_missing(): void
    {
        $body = str_repeat('A', 300);

        $post = $this->makePost([
            'title' => 'Fallback Description',
            'slug' => 'fallback-description',
            'body' => $body,
        ]);

        $this->assertSame(mb_substr($body, 0, 255), $post->description);
    }

    public function test_featured_image_returns_default_when_not_set(): void
    {
        $post = $this->makePost([
            'title' => 'No Image',
            'slug' => 'no-image',
        ]);

        $this->assertSame(asset('storage/default.jpg'), $post->featured_image);
    }

    public function test_is_published_checks_timestamp(): void
    {
        $draft = $this->makePost([
            'title' => 'Draft',
            'slug' => 'draft',
            'published_at' => null,
        ]);

        $published = $this->makePost([
            'title' => 'Published',
            'slug' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $this->assertFalse($draft->isPublished());
        $this->assertTrue($published->isPublished());
    }

    public function test_is_file_based_detects_existing_markdown_file(): void
    {
        $post = $this->makePost([
            'title' => 'Markdown-backed',
            'slug' => 'example-post',
        ]);

        $this->assertTrue($post->isFileBased());

        $post->slug = 'non-existent';

        $this->assertFalse($post->isFileBased());
    }

    private function makePost(array $attributes = []): Post
    {
        $defaults = [
            'user_id' => $this->author->id,
            'title' => 'Post '.uniqid(),
            'slug' => 'post-'.uniqid(),
            'body' => 'Body',
            'published_at' => now(),
        ];

        return Post::withoutGlobalScopes()->forceCreate(array_merge($defaults, $attributes));
    }
}
